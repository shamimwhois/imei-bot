<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

use IMEIBot\Models\Payment;

final class BankCardGateway extends BaseGateway
{
    private const SANDBOX_URL = 'https://api.sandbox.stripe.com';
    private const PRODUCTION_URL = 'https://api.stripe.com';
    
    private string $publishableKey;
    private string $secretKey;
    
    public function __construct(array $config, \IMEIBot\Core\Logger $logger)
    {
        parent::__construct($config, $logger);
        
        $this->publishableKey = $config['publishable_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
    }
    
    public function createPayment(Payment $payment, array $options = []): array
    {
        try {
            if (!$this->validateAmount($payment->amount)) {
                return [
                    'success' => false,
                    'error' => 'Invalid amount. Minimum: ' . $this->config['min_amount'] . ', Maximum: ' . $this->config['max_amount']
                ];
            }
            
            if (!$this->validateCurrency($payment->currency)) {
                return [
                    'success' => false,
                    'error' => 'Unsupported currency'
                ];
            }
            
            // Create payment intent
            $paymentIntentData = [
                'amount' => (int) ($payment->amount * 100), // Convert to cents
                'currency' => strtolower($payment->currency),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'order_id' => $payment->transaction_id,
                    'user_id' => $payment->user_id,
                    'platform' => 'imei_bot'
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
                'confirmation_method' => 'manual',
                'confirm' => false,
                'return_url' => $options['return_url'] ?? ''
            ];
            
            $url = $this->getApiUrl('v1/payment_intents');
            $headers = [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Stripe-Version' => '2023-10-16'
            ];
            
            $response = $this->makeRequest('POST', $url, $paymentIntentData, $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to create payment intent: ' . ($response['error'] ?? 'Unknown error')
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'Payment intent creation failed'
                ];
            }
            
            $this->logTransaction('bank_card_payment_created', [
                'payment_id' => $payment->id,
                'stripe_payment_intent_id' => $data['id']
            ]);
            
            return [
                'success' => true,
                'gateway_transaction_id' => $data['id'],
                'payment_details' => [
                    'client_secret' => $data['client_secret'],
                    'payment_intent_id' => $data['id'],
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'status' => $data['status'],
                    'created' => $data['created'],
                    'publishable_key' => $this->publishableKey
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Bank card payment creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getPaymentStatus(string $gatewayTransactionId): array
    {
        try {
            $url = $this->getApiUrl('v1/payment_intents/' . $gatewayTransactionId);
            $headers = [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Stripe-Version' => '2023-10-16'
            ];
            
            $response = $this->makeRequest('GET', $url, [], $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment status'
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'Status check failed'
                ];
            }
            
            $statusMap = [
                'requires_payment_method' => 'pending',
                'requires_confirmation' => 'pending',
                'requires_action' => 'pending',
                'processing' => 'processing',
                'succeeded' => 'completed',
                'canceled' => 'cancelled',
                'requires_capture' => 'pending'
            ];
            
            $status = $statusMap[$data['status']] ?? 'pending';
            
            return [
                'success' => true,
                'status' => $status,
                'details' => [
                    'status' => $data['status'],
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'created' => $data['created'],
                    'payment_method' => $data['payment_method'] ?? null,
                    'charges' => $data['charges']['data'] ?? [],
                    'last_payment_error' => $data['last_payment_error'] ?? null
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundPayment(string $gatewayTransactionId, float $amount, string $reason = ''): array
    {
        try {
            // First get the payment intent to get the charge ID
            $paymentIntent = $this->getPaymentStatus($gatewayTransactionId);
            
            if (!$paymentIntent['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment intent for refund'
                ];
            }
            
            $charges = $paymentIntent['details']['charges'] ?? [];
            
            if (empty($charges)) {
                return [
                    'success' => false,
                    'error' => 'No charge found for refund'
                ];
            }
            
            $chargeId = $charges[0]['id'];
            
            // Create refund
            $refundData = [
                'charge' => $chargeId,
                'amount' => (int) ($amount * 100), // Convert to cents
                'reason' => $this->mapRefundReason($reason),
                'metadata' => [
                    'payment_intent_id' => $gatewayTransactionId,
                    'refund_reason' => $reason
                ]
            ];
            
            $url = $this->getApiUrl('v1/refunds');
            $headers = [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Stripe-Version' => '2023-10-16'
            ];
            
            $response = $this->makeRequest('POST', $url, $refundData, $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to process refund'
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'Refund failed'
                ];
            }
            
            $this->logTransaction('bank_card_refund_processed', [
                'payment_intent_id' => $gatewayTransactionId,
                'refund_id' => $data['id'],
                'amount' => $amount
            ]);
            
            return [
                'success' => true,
                'refund_id' => $data['id'],
                'message' => 'Refund processed successfully'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function processWebhook(array $webhookData): array
    {
        try {
            $this->logTransaction('bank_card_webhook_received', $webhookData);
            
            // Verify webhook signature
            if (!$this->verifyWebhook($webhookData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature'
                ];
            }
            
            $eventType = $webhookData['type'] ?? '';
            $eventData = $webhookData['data']['object'] ?? [];
            
            switch ($eventType) {
                case 'payment_intent.succeeded':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $eventData['id'] ?? '',
                        'status' => 'completed',
                        'details' => $eventData
                    ];
                    
                case 'payment_intent.payment_failed':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $eventData['id'] ?? '',
                        'status' => 'failed',
                        'details' => [
                            'failure_reason' => $eventData['last_payment_error']['message'] ?? 'Payment failed'
                        ]
                    ];
                    
                case 'payment_intent.canceled':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $eventData['id'] ?? '',
                        'status' => 'cancelled',
                        'details' => $eventData
                    ];
                    
                default:
                    return [
                        'success' => false,
                        'error' => 'Unknown webhook event type: ' . $eventType
                    ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyWebhook(array $webhookData): bool
    {
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret = $this->getWebhookSecret();
        
        if (empty($signature) || empty($secret)) {
            return false;
        }
        
        try {
            $payload = json_encode($webhookData);
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $secret
            );
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function getDisplayName(): string
    {
        return 'Bank Card';
    }
    
    public function getDescription(): string
    {
        return 'Pay with credit/debit card - Visa, Mastercard, American Express, Discover';
    }
    
    public function getSupportedPaymentMethods(): array
    {
        return [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'amex' => 'American Express',
            'discover' => 'Discover',
            'jcb' => 'JCB',
            'diners_club' => 'Diners Club'
        ];
    }
    
    public function testConnection(): array
    {
        try {
            // Test by getting account info
            $url = $this->getApiUrl('v1/account');
            $headers = [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Stripe-Version' => '2023-10-16'
            ];
            
            $response = $this->makeRequest('GET', $url, [], $headers);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Stripe API connection successful',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to connect to Stripe API'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    private function mapRefundReason(string $reason): string
    {
        $reasonMap = [
            'duplicate' => 'duplicate',
            'fraudulent' => 'fraudulent',
            'requested_by_customer' => 'requested_by_customer'
        ];
        
        return $reasonMap[strtolower($reason)] ?? 'requested_by_customer';
    }
    
    protected function getApiUrl(string $endpoint = ''): string
    {
        $baseUrl = $this->isTestMode ? self::SANDBOX_URL : self::PRODUCTION_URL;
        return $baseUrl . '/' . ltrim($endpoint, '/');
    }
}
