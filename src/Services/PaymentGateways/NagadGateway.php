<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

use IMEIBot\Models\Payment;

final class NagadGateway extends BaseGateway
{
    private const SANDBOX_URL = 'https://sandbox.mynagad.com';
    private const PRODUCTION_URL = 'https://api.mynagad.com';
    
    private string $merchantId;
    private string $merchantNumber;
    private string $privateKey;
    private string $publicKey;
    
    public function __construct(array $config, \IMEIBot\Core\Logger $logger)
    {
        parent::__construct($config, $logger);
        
        $this->merchantId = $config['merchant_id'] ?? '';
        $this->merchantNumber = $config['merchant_number'] ?? '';
        $this->privateKey = $config['private_key'] ?? '';
        $this->publicKey = $config['public_key'] ?? '';
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
            
            if ($payment->currency !== 'BDT') {
                return [
                    'success' => false,
                    'error' => 'Nagad only supports BDT currency'
                ];
            }
            
            $accountType = $options['account_type'] ?? 'personal'; // personal or merchant
            $paymentMethod = $options['payment_method'] ?? 'mobile_banking';
            
            // Create payment based on account type
            if ($accountType === 'merchant') {
                return $this->createMerchantPayment($payment, $options);
            } else {
                return $this->createPersonalPayment($payment, $options);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Nagad payment creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
                'account_type' => $options['account_type'] ?? 'personal'
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function createPersonalPayment(Payment $payment, array $options = []): array
    {
        // Personal Nagad payment (mobile banking)
        $orderData = [
            'merchantId' => $this->merchantId,
            'merchantNumber' => $this->merchantNumber,
            'amount' => $payment->amount,
            'orderId' => $payment->transaction_id,
            'currency' => 'BDT',
            'productDetails' => [
                'product_name' => 'IMEI Bot Platform Payment',
                'product_category' => 'digital_service',
                'product_description' => 'Payment for IMEI Bot Platform services'
            ],
            'callbackURL' => $this->getWebhookUrl(),
            'clientIP' => $payment->ip_address ?? '127.0.0.1',
            'accountType' => 'personal'
        ];
        
        // Generate signature
        $signature = $this->generateNagadSignature($orderData);
        $orderData['signature'] = $signature;
        
        $url = $this->getApiUrl('checkout/create');
        $headers = [
            'X-KM-IP-V4' => $payment->ip_address ?? '127.0.0.1',
            'X-KM-ApiKey' => $this->getApiKey(),
            'X-KM-Api-Secret' => $this->getApiSecret(),
            'X-KM-Auth-Timestamp' => time(),
            'X-KM-Auth-Token' => $this->generateAuthToken()
        ];
        
        $response = $this->makeRequest('POST', $url, $orderData, $headers);
        
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create payment: ' . ($response['error'] ?? 'Unknown error')
            ];
        }
        
        $data = $response['data'];
        
        if (isset($data['error_code'])) {
            return [
                'success' => false,
                'error' => $data['error_message'] ?? 'Payment creation failed'
            ];
        }
        
        $this->logTransaction('nagad_personal_payment_created', [
            'payment_id' => $payment->id,
            'nagad_order_id' => $data['order_id'] ?? null
        ]);
        
        return [
            'success' => true,
            'gateway_transaction_id' => $data['order_id'] ?? $payment->transaction_id,
            'payment_details' => [
                'order_id' => $data['order_id'] ?? null,
                'payment_url' => $data['payment_url'] ?? null,
                'checkout_url' => $data['checkout_url'] ?? null,
                'qr_code' => $data['qr_code'] ?? null,
                'expires_at' => $data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'account_type' => 'personal',
                'payment_method' => 'mobile_banking'
            ]
        ];
    }
    
    private function createMerchantPayment(Payment $payment, array $options = []): array
    {
        // Merchant Nagad payment (checkout)
        $orderData = [
            'merchantId' => $this->merchantId,
            'merchantNumber' => $this->merchantNumber,
            'amount' => $payment->amount,
            'orderId' => $payment->transaction_id,
            'currency' => 'BDT',
            'productDetails' => [
                'product_name' => 'IMEI Bot Platform Payment',
                'product_category' => 'digital_service',
                'product_description' => 'Payment for IMEI Bot Platform services'
            ],
            'additionalMerchantInfo' => [
                'logo_url' => $options['logo_url'] ?? '',
                'store_name' => $options['store_name'] ?? 'IMEI Bot Platform',
                'store_address' => $options['store_address'] ?? '',
                'store_city' => $options['store_city'] ?? 'Dhaka',
                'store_postcode' => $options['store_postcode'] ?? '1000'
            ],
            'callbackURL' => $this->getWebhookUrl(),
            'clientIP' => $payment->ip_address ?? '127.0.0.1',
            'accountType' => 'merchant'
        ];
        
        // Generate signature
        $signature = $this->generateNagadSignature($orderData);
        $orderData['signature'] = $signature;
        
        $url = $this->getApiUrl('checkout/create');
        $headers = [
            'X-KM-IP-V4' => $payment->ip_address ?? '127.0.0.1',
            'X-KM-ApiKey' => $this->getApiKey(),
            'X-KM-Api-Secret' => $this->getApiSecret(),
            'X-KM-Auth-Timestamp' => time(),
            'X-KM-Auth-Token' => $this->generateAuthToken()
        ];
        
        $response = $this->makeRequest('POST', $url, $orderData, $headers);
        
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create merchant payment: ' . ($response['error'] ?? 'Unknown error')
            ];
        }
        
        $data = $response['data'];
        
        if (isset($data['error_code'])) {
            return [
                'success' => false,
                'error' => $data['error_message'] ?? 'Merchant payment creation failed'
            ];
        }
        
        $this->logTransaction('nagad_merchant_payment_created', [
            'payment_id' => $payment->id,
            'nagad_order_id' => $data['order_id'] ?? null
        ]);
        
        return [
            'success' => true,
            'gateway_transaction_id' => $data['order_id'] ?? $payment->transaction_id,
            'payment_details' => [
                'order_id' => $data['order_id'] ?? null,
                'payment_url' => $data['payment_url'] ?? null,
                'checkout_url' => $data['checkout_url'] ?? null,
                'qr_code' => $data['qr_code'] ?? null,
                'expires_at' => $data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'account_type' => 'merchant',
                'payment_method' => 'checkout'
            ]
        ];
    }
    
    public function getPaymentStatus(string $gatewayTransactionId): array
    {
        try {
            $url = $this->getApiUrl('checkout/status/' . $gatewayTransactionId);
            $headers = [
                'X-KM-IP-V4' => '127.0.0.1',
                'X-KM-ApiKey' => $this->getApiKey(),
                'X-KM-Api-Secret' => $this->getApiSecret(),
                'X-KM-Auth-Timestamp' => time(),
                'X-KM-Auth-Token' => $this->generateAuthToken()
            ];
            
            $response = $this->makeRequest('GET', $url, [], $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment status'
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error_code'])) {
                return [
                    'success' => false,
                    'error' => $data['error_message'] ?? 'Status check failed'
                ];
            }
            
            $statusMap = [
                'Initiated' => 'pending',
                'Pending' => 'pending',
                'Success' => 'completed',
                'Cancelled' => 'cancelled',
                'Failed' => 'failed',
                'Expired' => 'failed'
            ];
            
            $status = $statusMap[$data['payment_status'] ?? 'Initiated'] ?? 'pending';
            
            return [
                'success' => true,
                'status' => $status,
                'details' => [
                    'payment_status' => $data['payment_status'] ?? null,
                    'transaction_amount' => $data['amount'] ?? null,
                    'transaction_currency' => $data['currency'] ?? 'BDT',
                    'transaction_time' => $data['transaction_time'] ?? null,
                    'sender_number' => $data['sender_msisdn'] ?? null,
                    'transaction_id' => $data['transaction_id'] ?? null
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
            $refundData = [
                'order_id' => $gatewayTransactionId,
                'refund_amount' => $amount,
                'refund_reason' => $reason,
                'merchant_id' => $this->merchantId
            ];
            
            $signature = $this->generateNagadSignature($refundData);
            $refundData['signature'] = $signature;
            
            $url = $this->getApiUrl('checkout/refund');
            $headers = [
                'X-KM-IP-V4' => '127.0.0.1',
                'X-KM-ApiKey' => $this->getApiKey(),
                'X-KM-Api-Secret' => $this->getApiSecret(),
                'X-KM-Auth-Timestamp' => time(),
                'X-KM-Auth-Token' => $this->generateAuthToken()
            ];
            
            $response = $this->makeRequest('POST', $url, $refundData, $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to process refund'
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error_code'])) {
                return [
                    'success' => false,
                    'error' => $data['error_message'] ?? 'Refund failed'
                ];
            }
            
            $this->logTransaction('nagad_refund_processed', [
                'order_id' => $gatewayTransactionId,
                'refund_id' => $data['refund_id'] ?? null,
                'amount' => $amount
            ]);
            
            return [
                'success' => true,
                'refund_id' => $data['refund_id'] ?? uniqid('NAGAD_REF_'),
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
            $this->logTransaction('nagad_webhook_received', $webhookData);
            
            // Verify webhook
            if (!$this->verifyWebhook($webhookData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature'
                ];
            }
            
            $orderId = $webhookData['order_id'] ?? '';
            $paymentStatus = $webhookData['payment_status'] ?? '';
            
            $statusMap = [
                'Initiated' => 'pending',
                'Pending' => 'pending',
                'Success' => 'completed',
                'Cancelled' => 'cancelled',
                'Failed' => 'failed',
                'Expired' => 'failed'
            ];
            
            $status = $statusMap[$paymentStatus] ?? 'pending';
            
            return [
                'success' => true,
                'gateway_transaction_id' => $orderId,
                'status' => $status,
                'details' => $webhookData
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyWebhook(array $webhookData): bool
    {
        // Nagad webhooks are verified by checking the signature
        $signature = $webhookData['signature'] ?? '';
        
        if (empty($signature)) {
            return false;
        }
        
        // Remove signature from data for verification
        $dataForVerification = $webhookData;
        unset($dataForVerification['signature']);
        
        $expectedSignature = $this->generateNagadSignature($dataForVerification);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    public function getDisplayName(): string
    {
        return 'Nagad';
    }
    
    public function getDescription(): string
    {
        return 'Pay via Nagad mobile banking - Fast, secure, and convenient';
    }
    
    public function getSupportedPaymentMethods(): array
    {
        return [
            'nagad_personal' => 'Nagad Personal Account',
            'nagad_merchant' => 'Nagad Merchant Account',
            'nagad_mobile_banking' => 'Nagad Mobile Banking',
            'nagad_checkout' => 'Nagad Checkout'
        ];
    }
    
    public function testConnection(): array
    {
        try {
            // Test by checking merchant balance
            $url = $this->getApiUrl('merchant/balance');
            $headers = [
                'X-KM-IP-V4' => '127.0.0.1',
                'X-KM-ApiKey' => $this->getApiKey(),
                'X-KM-Api-Secret' => $this->getApiSecret(),
                'X-KM-Auth-Timestamp' => time(),
                'X-KM-Auth-Token' => $this->generateAuthToken()
            ];
            
            $response = $this->makeRequest('GET', $url, [], $headers);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Nagad API connection successful',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to connect to Nagad API'
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
    
    private function generateNagadSignature(array $data): string
    {
        ksort($data);
        $string = '';
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $string .= $key . $value;
        }
        
        return hash_hmac('sha256', $string, $this->privateKey);
    }
    
    private function generateAuthToken(): string
    {
        $timestamp = time();
        $data = [
            'merchant_id' => $this->merchantId,
            'timestamp' => $timestamp
        ];
        
        return $this->generateNagadSignature($data);
    }
    
    protected function getApiUrl(string $endpoint = ''): string
    {
        $baseUrl = $this->isTestMode ? self::SANDBOX_URL : self::PRODUCTION_URL;
        return $baseUrl . '/v1.2.0/' . ltrim($endpoint, '/');
    }
}
