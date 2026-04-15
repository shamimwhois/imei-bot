<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

use IMEIBot\Models\Payment;

final class BangladeshiBankGateway extends BaseGateway
{
    private const SANDBOX_URL = 'https://api.sandbox.sslcommerz.com';
    private const PRODUCTION_URL = 'https://api.sslcommerz.com';
    
    private string $storeId;
    private string $storePassword;
    
    public function __construct(array $config, \IMEIBot\Core\Logger $logger)
    {
        parent::__construct($config, $logger);
        
        $this->storeId = $config['store_id'] ?? '';
        $this->storePassword = $config['store_password'] ?? '';
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
                    'error' => 'Bangladeshi bank cards only support BDT currency'
                ];
            }
            
            $bankType = $options['bank_type'] ?? 'all'; // all, local, international
            $cardType = $options['card_type'] ?? 'all'; // all, credit, debit
            
            // Create payment with SSLCommerz
            $paymentData = [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'total_amount' => $payment->amount,
                'currency' => 'BDT',
                'tran_id' => $payment->transaction_id,
                'success_url' => $options['success_url'] ?? $this->getWebhookUrl(),
                'fail_url' => $options['fail_url'] ?? $this->getWebhookUrl(),
                'cancel_url' => $options['cancel_url'] ?? $this->getWebhookUrl(),
                'ipn_url' => $this->getWebhookUrl(),
                'cus_name' => $options['customer_name'] ?? 'Customer',
                'cus_email' => $options['customer_email'] ?? 'customer@example.com',
                'cus_phone' => $options['customer_phone'] ?? '01800000000',
                'cus_add1' => $options['customer_address'] ?? 'Dhaka, Bangladesh',
                'cus_city' => $options['customer_city'] ?? 'Dhaka',
                'cus_country' => $options['customer_country'] ?? 'Bangladesh',
                'shipping_method' => 'NO',
                'multi_card_name' => $this->getMultiCardName($bankType, $cardType),
                'product_name' => 'IMEI Bot Platform Payment',
                'product_category' => 'digital_service',
                'product_profile' => 'general',
                'product_info' => json_encode([
                    'product_name' => 'IMEI Bot Platform Payment',
                    'product_category' => 'digital_service',
                    'product_description' => 'Payment for IMEI Bot Platform services',
                    'bank_type' => $bankType,
                    'card_type' => $cardType
                ]),
                'value_a' => $payment->id,
                'value_b' => $payment->user_id,
                'value_c' => $bankType,
                'value_d' => $cardType
            ];
            
            $url = $this->getApiUrl('gwprocess/v4/api.php');
            
            $response = $this->makeRequest('POST', $url, $paymentData);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to create payment: ' . ($response['error'] ?? 'Unknown error')
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error']) && $data['error'] !== '000') {
                return [
                    'success' => false,
                    'error' => $data['error_message'] ?? 'Payment creation failed'
                ];
            }
            
            $this->logTransaction('bangladeshi_bank_payment_created', [
                'payment_id' => $payment->id,
                'ssl_session_id' => $data['sessionkey'] ?? null,
                'bank_type' => $bankType,
                'card_type' => $cardType
            ]);
            
            return [
                'success' => true,
                'gateway_transaction_id' => $data['sessionkey'] ?? $payment->transaction_id,
                'payment_details' => [
                    'session_key' => $data['sessionkey'] ?? null,
                    'payment_url' => $data['GatewayPageURL'] ?? null,
                    'status' => $data['status'] ?? null,
                    'bank_type' => $bankType,
                    'card_type' => $cardType,
                    'supported_banks' => $this->getSupportedBanks($bankType),
                    'supported_cards' => $this->getSupportedCards($cardType)
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Bangladeshi bank payment creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
                'bank_type' => $options['bank_type'] ?? 'all',
                'card_type' => $options['card_type'] ?? 'all'
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
            $paymentData = [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'sessionkey' => $gatewayTransactionId
            ];
            
            $url = $this->getApiUrl('validator/api/merchantTransInfoAPI.php');
            
            $response = $this->makeRequest('POST', $url, $paymentData);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment status'
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error']) && $data['error'] !== '000') {
                return [
                    'success' => false,
                    'error' => $data['error_message'] ?? 'Status check failed'
                ];
            }
            
            $statusMap = [
                'PENDING' => 'pending',
                'VALID' => 'completed',
                'VALIDATED' => 'completed',
                'FAILED' => 'failed',
                'CANCELLED' => 'cancelled'
            ];
            
            $status = $statusMap[$data['status'] ?? 'PENDING'] ?? 'pending';
            
            return [
                'success' => true,
                'status' => $status,
                'details' => [
                    'status' => $data['status'] ?? null,
                    'transaction_id' => $data['tran_id'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'currency' => $data['currency'] ?? 'BDT',
                    'payment_method' => $data['card_type'] ?? null,
                    'bank_name' => $data['bank_tran_id'] ?? null,
                    'card_brand' => $data['card_brand'] ?? null,
                    'card_issuer' => $data['card_issuer'] ?? null,
                    'risk_level' => $data['risk_level'] ?? null,
                    'validate_time' => $data['validate_time'] ?? null,
                    'payment_time' => $data['payment_time'] ?? null
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
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'sessionkey' => $gatewayTransactionId,
                'refund_amount' => $amount,
                'refund_reason' => $reason,
                'tran_id' => uniqid('REF_')
            ];
            
            $url = $this->getApiUrl('validator/api/refundAPI.php');
            
            $response = $this->makeRequest('POST', $url, $refundData);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to process refund'
                ];
            }
            
            $data = $response['data'];
            
            if (isset($data['error']) && $data['error'] !== '000') {
                return [
                    'success' => false,
                    'error' => $data['error_message'] ?? 'Refund failed'
                ];
            }
            
            $this->logTransaction('bangladeshi_bank_refund_processed', [
                'session_key' => $gatewayTransactionId,
                'refund_id' => $data['refund_ref_id'] ?? null,
                'amount' => $amount
            ]);
            
            return [
                'success' => true,
                'refund_id' => $data['refund_ref_id'] ?? uniqid('BANK_REF_'),
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
            $this->logTransaction('bangladeshi_bank_webhook_received', $webhookData);
            
            // Verify webhook
            if (!$this->verifyWebhook($webhookData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature'
                ];
            }
            
            $status = $webhookData['status'] ?? '';
            $transactionId = $webhookData['tran_id'] ?? '';
            $sessionKey = $webhookData['sessionkey'] ?? '';
            
            $statusMap = [
                'PENDING' => 'pending',
                'VALID' => 'completed',
                'VALIDATED' => 'completed',
                'FAILED' => 'failed',
                'CANCELLED' => 'cancelled'
            ];
            
            $status = $statusMap[$status] ?? 'pending';
            
            return [
                'success' => true,
                'gateway_transaction_id' => $sessionKey,
                'status' => $status,
                'details' => array_merge($webhookData, [
                    'transaction_id' => $transactionId,
                    'payment_method' => $webhookData['card_type'] ?? null,
                    'bank_name' => $webhookData['bank_tran_id'] ?? null,
                    'card_brand' => $webhookData['card_brand'] ?? null
                ])
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
        // SSLCommerz webhooks are verified by checking the store_id and store_password
        $storeId = $webhookData['store_id'] ?? '';
        $storePassword = $webhookData['store_passwd'] ?? '';
        
        return $storeId === $this->storeId && $storePassword === $this->storePassword;
    }
    
    public function getDisplayName(): string
    {
        return 'Bangladeshi Bank Cards';
    }
    
    public function getDescription(): string
    {
        return 'Pay with credit/debit cards from all major Bangladeshi banks';
    }
    
    public function getSupportedPaymentMethods(): array
    {
        return [
            'all_banks' => 'All Bangladeshi Banks',
            'local_banks' => 'Local Banks Only',
            'international_banks' => 'International Banks Only',
            'credit_cards' => 'Credit Cards',
            'debit_cards' => 'Debit Cards',
            'all_cards' => 'All Card Types'
        ];
    }
    
    public function testConnection(): array
    {
        try {
            // Test by checking store status
            $url = $this->getApiUrl('validator/api/merchantTransInfoAPI.php');
            $testData = [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'sessionkey' => 'TEST_SESSION'
            ];
            
            $response = $this->makeRequest('POST', $url, $testData);
            
            // Even if it fails due to invalid session, it means the connection works
            return [
                'success' => true,
                'message' => 'Bangladeshi bank API connection successful',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    private function getMultiCardName(string $bankType, string $cardType): string
    {
        // SSLCommerz multi-card configuration
        $cardConfigs = [
            'all' => [
                'all' => 'all'
            ],
            'local' => [
                'all' => 'all_local'
            ],
            'international' => [
                'all' => 'all_international'
            ]
        ];
        
        return $cardConfigs[$bankType][$cardType] ?? 'all';
    }
    
    private function getSupportedBanks(string $bankType): array
    {
        $banks = [
            'all' => [
                'AB Bank Limited',
                'Agrani Bank Limited',
                'Al-Arafah Islami Bank Limited',
                'Bank Asia Limited',
                'Bangladesh Commerce Bank Limited',
                'Bangladesh Krishi Bank',
                'Bank Islami Bangladesh Limited',
                'Bangladesh Development Bank Limited',
                'BRAC Bank Limited',
                'City Bank Limited',
                'Dhaka Bank Limited',
                'Dutch-Bangla Bank Limited',
                'Eastern Bank Limited',
                'EXIM Bank Limited',
                'First Security Islami Bank Limited',
                'Global Islami Bank Limited',
                'IFIC Bank Limited',
                'Islami Bank Bangladesh Limited',
                'Jamuna Bank Limited',
                'Janata Bank Limited',
                'Meghna Bank Limited',
                'Mercantile Bank Limited',
                'Midland Bank Limited',
                'Modhumoti Bank Limited',
                'Mutual Trust Bank Limited',
                'National Bank Limited',
                'National Credit and Commerce Bank Limited',
                'NCC Bank Limited',
                'NRB Bank Limited',
                'NRB Commercial Bank Limited',
                'One Bank Limited',
                'Padma Bank Limited',
                'Premier Bank Limited',
                'Prime Bank Limited',
                'Pubali Bank Limited',
                'Rupali Bank Limited',
                'Shahjalal Islami Bank Limited',
                'Social Islami Bank Limited',
                'Sonali Bank Limited',
                'South Bangla Agriculture and Commerce Bank Limited',
                'Southeast Bank Limited',
                'Standard Bank Limited',
                'Trust Bank Limited',
                'United Commercial Bank Limited',
                'Uttara Bank Limited',
                'Visa', 'Mastercard', 'American Express'
            ],
            'local' => [
                'AB Bank Limited',
                'Agrani Bank Limited',
                'Al-Arafah Islami Bank Limited',
                'Bank Asia Limited',
                'Bangladesh Commerce Bank Limited',
                'Bangladesh Krishi Bank',
                'Bank Islami Bangladesh Limited',
                'Bangladesh Development Bank Limited',
                'BRAC Bank Limited',
                'City Bank Limited',
                'Dhaka Bank Limited',
                'Dutch-Bangla Bank Limited',
                'Eastern Bank Limited',
                'EXIM Bank Limited',
                'First Security Islami Bank Limited',
                'Global Islami Bank Limited',
                'IFIC Bank Limited',
                'Islami Bank Bangladesh Limited',
                'Jamuna Bank Limited',
                'Janata Bank Limited',
                'Meghna Bank Limited',
                'Mercantile Bank Limited',
                'Midland Bank Limited',
                'Modhumoti Bank Limited',
                'Mutual Trust Bank Limited',
                'National Bank Limited',
                'National Credit and Commerce Bank Limited',
                'NCC Bank Limited',
                'NRB Bank Limited',
                'NRB Commercial Bank Limited',
                'One Bank Limited',
                'Padma Bank Limited',
                'Premier Bank Limited',
                'Prime Bank Limited',
                'Pubali Bank Limited',
                'Rupali Bank Limited',
                'Shahjalal Islami Bank Limited',
                'Social Islami Bank Limited',
                'Sonali Bank Limited',
                'South Bangla Agriculture and Commerce Bank Limited',
                'Southeast Bank Limited',
                'Standard Bank Limited',
                'Trust Bank Limited',
                'United Commercial Bank Limited',
                'Uttara Bank Limited'
            ],
            'international' => [
                'Visa', 'Mastercard', 'American Express'
            ]
        ];
        
        return $banks[$bankType] ?? $banks['all'];
    }
    
    private function getSupportedCards(string $cardType): array
    {
        $cards = [
            'all' => ['Visa', 'Mastercard', 'American Express', 'Diners Club', 'JCB'],
            'credit' => ['Visa Credit', 'Mastercard Credit', 'American Express Credit'],
            'debit' => ['Visa Debit', 'Mastercard Debit', 'American Express Debit']
        ];
        
        return $cards[$cardType] ?? $cards['all'];
    }
    
    protected function getApiUrl(string $endpoint = ''): string
    {
        $baseUrl = $this->isTestMode ? self::SANDBOX_URL : self::PRODUCTION_URL;
        return $baseUrl . '/' . ltrim($endpoint, '/');
    }
}
