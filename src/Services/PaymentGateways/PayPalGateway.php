<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

use IMEIBot\Models\Payment;

final class PayPalGateway extends BaseGateway
{
    private const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    private const PRODUCTION_URL = 'https://api-m.paypal.com';
    
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    
    public function __construct(array $config, \IMEIBot\Core\Logger $logger)
    {
        parent::__construct($config, $logger);
        
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
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
            
            // Get access token
            if (!$this->getAccessToken()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get access token'
                ];
            }
            
            // Create PayPal order
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $payment->transaction_id,
                        'description' => 'IMEI Bot Platform Payment',
                        'amount' => [
                            'currency_code' => $payment->currency,
                            'value' => number_format($payment->amount, 2, '.', '')
                        ],
                        'custom_id' => (string) $payment->user_id
                    ]
                ],
                'application_context' => [
                    'brand_name' => 'IMEI Bot Platform',
                    'landing_page' => 'BILLING',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $options['return_url'] ?? '',
                    'cancel_url' => $options['cancel_url'] ?? ''
                ]
            ];
            
            $url = $this->getApiUrl('v2/checkout/orders');
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'Accept-Language' => 'en_US'
            ];
            
            $response = $this->makeRequest('POST', $url, $orderData, $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to create PayPal order: ' . ($response['error'] ?? 'Unknown error')
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'PayPal order creation failed'
                ];
            }
            
            $this->logTransaction('paypal_payment_created', [
                'payment_id' => $payment->id,
                'paypal_order_id' => $data['id']
            ]);
            
            // Extract approval URL
            $approvalUrl = '';
            foreach ($data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
            
            return [
                'success' => true,
                'gateway_transaction_id' => $data['id'],
                'payment_details' => [
                    'order_id' => $data['id'],
                    'status' => $data['status'],
                    'approval_url' => $approvalUrl,
                    'links' => $data['links'],
                    'create_time' => $data['create_time']
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('PayPal payment creation failed', [
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
            if (!$this->getAccessToken()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get access token'
                ];
            }
            
            $url = $this->getApiUrl('v2/checkout/orders/' . $gatewayTransactionId);
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
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
                'CREATED' => 'pending',
                'SAVED' => 'pending',
                'APPROVED' => 'pending',
                'VOIDED' => 'cancelled',
                'COMPLETED' => 'completed',
                'PAYER_ACTION_REQUIRED' => 'pending'
            ];
            
            $status = $statusMap[$data['status']] ?? 'pending';
            
            return [
                'success' => true,
                'status' => $status,
                'details' => [
                    'status' => $data['status'],
                    'intent' => $data['intent'],
                    'purchase_units' => $data['purchase_units'] ?? [],
                    'payer' => $data['payer'] ?? [],
                    'create_time' => $data['create_time'],
                    'update_time' => $data['update_time']
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
            if (!$this->getAccessToken()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get access token'
                ];
            }
            
            // First capture the payment if not already captured
            $captureData = [
                'payment_source' => [
                    'paypal' => [
                        'name' => [
                            'given_name' => 'IMEI Bot',
                            'surname' => 'Platform'
                        ],
                        'email_address' => 'noreply@imeibot.com'
                    ]
                ]
            ];
            
            $url = $this->getApiUrl('v2/checkout/orders/' . $gatewayTransactionId . '/capture');
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];
            
            $response = $this->makeRequest('POST', $url, $captureData, $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to capture payment for refund'
                ];
            }
            
            $captureData = $response['data'];
            
            if (isset($captureData['error'])) {
                return [
                    'success' => false,
                    'error' => $captureData['error']['message'] ?? 'Payment capture failed'
                ];
            }
            
            // Get the capture ID
            $captureId = null;
            if (isset($captureData['purchase_units'][0]['payments']['captures'][0]['id'])) {
                $captureId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'];
            }
            
            if (!$captureId) {
                return [
                    'success' => false,
                    'error' => 'No capture ID found for refund'
                ];
            }
            
            // Create refund
            $refundData = [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amount, 2, '.', '')
                ],
                'note_to_payer' => $reason ?: 'Refund from IMEI Bot Platform'
            ];
            
            $url = $this->getApiUrl('v2/payments/captures/' . $captureId . '/refund');
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
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
            
            $this->logTransaction('paypal_refund_processed', [
                'order_id' => $gatewayTransactionId,
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
            $this->logTransaction('paypal_webhook_received', $webhookData);
            
            // Verify webhook
            if (!$this->verifyWebhook($webhookData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature'
                ];
            }
            
            $eventType = $webhookData['event_type'] ?? '';
            $resource = $webhookData['resource'] ?? [];
            
            switch ($eventType) {
                case 'CHECKOUT.ORDER.APPROVED':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $resource['id'] ?? '',
                        'status' => 'pending',
                        'details' => $resource
                    ];
                    
                case 'CHECKOUT.ORDER.COMPLETED':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $resource['id'] ?? '',
                        'status' => 'completed',
                        'details' => $resource
                    ];
                    
                case 'CHECKOUT.ORDER.CANCELLED':
                case 'CHECKOUT.ORDER.VOIDED':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $resource['id'] ?? '',
                        'status' => 'cancelled',
                        'details' => $resource
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
        $signature = $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? '';
        $certId = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '';
        $transmissionId = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '';
        $timestamp = $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '';
        
        if (empty($signature) || empty($certId)) {
            return false;
        }
        
        try {
            // In a real implementation, you would verify the webhook signature
            // For now, we'll just check if the required headers are present
            return !empty($signature) && !empty($certId);
        } catch (\Exception $e) {
            $this->logger->error('PayPal webhook verification failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function getDisplayName(): string
    {
        return 'PayPal';
    }
    
    public function getDescription(): string
    {
        return 'Pay with PayPal - Fast, secure, and trusted worldwide';
    }
    
    public function getSupportedPaymentMethods(): array
    {
        return [
            'paypal_balance' => 'PayPal Balance',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_account' => 'Bank Account'
        ];
    }
    
    public function testConnection(): array
    {
        try {
            // Test by getting access token
            if ($this->getAccessToken()) {
                return [
                    'success' => true,
                    'message' => 'PayPal API connection successful',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with PayPal API'
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
    
    private function getAccessToken(): bool
    {
        try {
            if (!empty($this->accessToken)) {
                return true;
            }
            
            $url = $this->getApiUrl('v1/oauth2/token');
            $headers = [
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            $data = 'grant_type=client_credentials';
            
            // For form-encoded data, we need to make a direct request
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => !$this->isTestMode
            ]);
            
            $response = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => ['grant_type' => 'client_credentials']
            ]);
            
            $responseData = [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'data' => json_decode($response->getBody()->getContents(), true)
            ];
            
            if (!$responseData['success']) {
                return false;
            }
            
            $data = $responseData['data'];
            
            if (isset($data['error'])) {
                return false;
            }
            
            $this->accessToken = $data['access_token'];
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('PayPal token generation failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    protected function getApiUrl(string $endpoint = ''): string
    {
        $baseUrl = $this->isTestMode ? self::SANDBOX_URL : self::PRODUCTION_URL;
        return $baseUrl . '/' . ltrim($endpoint, '/');
    }
}
