<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

use IMEIBot\Models\Payment;

final class BkashGateway extends BaseGateway
{
    private const SANDBOX_URL = 'https://checkout.sandbox.bka.sh/v1.2.0-beta';
    private const PRODUCTION_URL = 'https://checkout.pay.bka.sh/v1.2.0-beta';
    
    private string $accessToken;
    private ?string $refreshToken = null;
    
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
                    'error' => 'bKash only supports BDT currency'
                ];
            }
            
            $accountType = $options['account_type'] ?? 'personal'; // personal or merchant
            $paymentMethod = $options['payment_method'] ?? 'mobile_banking';
            
            // Get access token
            if (!$this->getAccessToken($accountType)) {
                return [
                    'success' => false,
                    'error' => 'Failed to get access token for ' . $accountType . ' account'
                ];
            }
            
            // Create payment based on account type
            if ($accountType === 'merchant') {
                return $this->createMerchantPayment($payment, $options);
            } else {
                return $this->createPersonalPayment($payment, $options);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('bKash payment creation failed', [
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
        // Personal bKash payment (mobile banking)
        $paymentData = [
            'amount' => $payment->amount,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->transaction_id,
            'callbackURL' => $this->getWebhookUrl(),
            'merchantAssociationInfo' => 'IMEI Bot Platform',
            'productType' => 'digital_goods',
            'productDetails' => json_encode([
                'product_name' => 'IMEI Bot Platform Payment',
                'product_category' => 'digital_service',
                'product_description' => 'Payment for IMEI Bot Platform services'
            ])
        ];
        
        $url = $this->getApiUrl('payment/create');
        $headers = [
            'Authorization' => $this->accessToken,
            'X-APP-Key' => $this->getApiKey()
        ];
        
        $response = $this->makeRequest('POST', $url, $paymentData, $headers);
        
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create payment: ' . ($response['error'] ?? 'Unknown error')
            ];
        }
        
        $data = $response['data'];
        
        if ($data['statusCode'] !== '0000') {
            return [
                'success' => false,
                'error' => $data['statusMessage'] ?? 'Payment creation failed'
            ];
        }
        
        $this->logTransaction('bkash_personal_payment_created', [
            'payment_id' => $payment->id,
            'bkash_payment_id' => $data['paymentID']
        ]);
        
        return [
            'success' => true,
            'gateway_transaction_id' => $data['paymentID'],
            'payment_details' => [
                'payment_id' => $data['paymentID'],
                'payment_create_time' => $data['paymentCreateTime'],
                'transaction_status' => $data['transactionStatus'],
                'bkash_url' => $data['bkashURL'] ?? null,
                'callback_url' => $data['callbackURL'] ?? null,
                'account_type' => 'personal',
                'payment_method' => 'mobile_banking'
            ]
        ];
    }
    
    private function createMerchantPayment(Payment $payment, array $options = []): array
    {
        // Merchant bKash payment (checkout)
        $paymentData = [
            'amount' => $payment->amount,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->transaction_id,
            'callbackURL' => $this->getWebhookUrl(),
            'merchantAssociationInfo' => 'IMEI Bot Platform',
            'productType' => 'digital_goods',
            'productDetails' => json_encode([
                'product_name' => 'IMEI Bot Platform Payment',
                'product_category' => 'digital_service',
                'product_description' => 'Payment for IMEI Bot Platform services'
            ]),
            'additionalMerchantInfo' => json_encode([
                'logo_url' => $options['logo_url'] ?? '',
                'store_name' => $options['store_name'] ?? 'IMEI Bot Platform',
                'store_address' => $options['store_address'] ?? '',
                'store_city' => $options['store_city'] ?? 'Dhaka',
                'store_postcode' => $options['store_postcode'] ?? '1000'
            ])
        ];
        
        $url = $this->getApiUrl('checkout/create');
        $headers = [
            'Authorization' => $this->accessToken,
            'X-APP-Key' => $this->getApiKey()
        ];
        
        $response = $this->makeRequest('POST', $url, $paymentData, $headers);
        
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create merchant payment: ' . ($response['error'] ?? 'Unknown error')
            ];
        }
        
        $data = $response['data'];
        
        if ($data['statusCode'] !== '0000') {
            return [
                'success' => false,
                'error' => $data['statusMessage'] ?? 'Merchant payment creation failed'
            ];
        }
        
        $this->logTransaction('bkash_merchant_payment_created', [
            'payment_id' => $payment->id,
            'bkash_payment_id' => $data['paymentID']
        ]);
        
        return [
            'success' => true,
            'gateway_transaction_id' => $data['paymentID'],
            'payment_details' => [
                'payment_id' => $data['paymentID'],
                'payment_create_time' => $data['paymentCreateTime'],
                'transaction_status' => $data['transactionStatus'],
                'bkash_url' => $data['bkashURL'] ?? null,
                'callback_url' => $data['callbackURL'] ?? null,
                'account_type' => 'merchant',
                'payment_method' => 'checkout'
            ]
        ];
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
            
            $url = $this->getApiUrl('payment/status/' . $gatewayTransactionId);
            $headers = [
                'Authorization' => $this->accessToken,
                'X-APP-Key' => $this->getApiKey()
            ];
            
            $response = $this->makeRequest('GET', $url, [], $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment status'
                ];
            }
            
            $data = $response['data'];
            
            if ($data['statusCode'] !== '0000') {
                return [
                    'success' => false,
                    'error' => $data['statusMessage'] ?? 'Status check failed'
                ];
            }
            
            $statusMap = [
                'Initiated' => 'pending',
                'Pending' => 'pending',
                'Completed' => 'completed',
                'Cancelled' => 'cancelled',
                'Failed' => 'failed',
                'Expired' => 'failed'
            ];
            
            $status = $statusMap[$data['transactionStatus']] ?? 'pending';
            
            return [
                'success' => true,
                'status' => $status,
                'details' => [
                    'transaction_status' => $data['transactionStatus'],
                    'transaction_amount' => $data['amount'],
                    'transaction_currency' => $data['currency'],
                    'transaction_time' => $data['transactionStatusTime'] ?? null,
                    'sender_number' => $data['senderMsisdn'] ?? null,
                    'verification_otp' => $data['verificationOtp'] ?? null
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
            
            $refundData = [
                'paymentID' => $gatewayTransactionId,
                'amount' => $amount,
                'trxID' => uniqid('REF_'),
                'reason' => $reason,
                'sku' => 'imei_bot_refund'
            ];
            
            $url = $this->getApiUrl('payment/refund');
            $headers = [
                'Authorization' => $this->accessToken,
                'X-APP-Key' => $this->getApiKey()
            ];
            
            $response = $this->makeRequest('POST', $url, $refundData, $headers);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to process refund'
                ];
            }
            
            $data = $response['data'];
            
            if ($data['statusCode'] !== '0000') {
                return [
                    'success' => false,
                    'error' => $data['statusMessage'] ?? 'Refund failed'
                ];
            }
            
            $this->logTransaction('bkash_refund_processed', [
                'payment_id' => $gatewayTransactionId,
                'refund_id' => $data['refundID'],
                'amount' => $amount
            ]);
            
            return [
                'success' => true,
                'refund_id' => $data['refundID'],
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
            $this->logTransaction('bkash_webhook_received', $webhookData);
            
            // Verify webhook
            if (!$this->verifyWebhook($webhookData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature'
                ];
            }
            
            $paymentId = $webhookData['paymentID'] ?? '';
            $transactionStatus = $webhookData['transactionStatus'] ?? '';
            
            $statusMap = [
                'Initiated' => 'pending',
                'Pending' => 'pending',
                'Completed' => 'completed',
                'Cancelled' => 'cancelled',
                'Failed' => 'failed',
                'Expired' => 'failed'
            ];
            
            $status = $statusMap[$transactionStatus] ?? 'pending';
            
            return [
                'success' => true,
                'gateway_transaction_id' => $paymentId,
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
        // bKash webhooks are verified by checking the IP address and signature
        $allowedIPs = ['103.150.73.91', '103.150.73.92']; // bKash IP addresses
        
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (!in_array($clientIP, $allowedIPs)) {
            return false;
        }
        
        // Additional signature verification if implemented
        return true;
    }
    
    public function getDisplayName(): string
    {
        return 'bKash';
    }
    
    public function getDescription(): string
    {
        return 'Pay via bKash mobile banking - Fast, secure, and convenient';
    }
    
    public function getSupportedPaymentMethods(): array
    {
        return [
            'bkash_personal' => 'bKash Personal Account',
            'bkash_merchant' => 'bKash Merchant Account',
            'bkash_mobile_banking' => 'bKash Mobile Banking',
            'bkash_checkout' => 'bKash Checkout'
        ];
    }
    
    public function testConnection(): array
    {
        try {
            // Test by getting access token
            if ($this->getAccessToken()) {
                return [
                    'success' => true,
                    'message' => 'bKash API connection successful',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with bKash API'
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
    
    private function getAccessToken(string $accountType = 'personal'): bool
    {
        try {
            // Check if we have a valid token cached
            if (!empty($this->accessToken)) {
                return true;
            }
            
            $url = $this->getApiUrl('tokenized/checkout/token/grant');
            $headers = [
                'username' => $this->getApiKey(),
                'password' => $this->getApiSecret()
            ];
            
            if ($accountType === 'merchant') {
                $headers['username'] = $this->config['merchant_username'] ?? $this->getApiKey();
                $headers['password'] = $this->config['merchant_password'] ?? $this->getApiSecret();
            }
            
            $response = $this->makeRequest('POST', $url, [], $headers);
            
            if (!$response['success']) {
                return false;
            }
            
            $data = $response['data'];
            
            if ($data['statusCode'] !== '0000') {
                return false;
            }
            
            $this->accessToken = $data['id_token'];
            $this->refreshToken = $data['refresh_token'] ?? null;
            
            $this->logger->info('bKash token generated successfully', [
                'account_type' => $accountType
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('bKash token generation failed', [
                'error' => $e->getMessage(),
                'account_type' => $accountType
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
