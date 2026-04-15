<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

use IMEIBot\Models\Payment;

final class BinanceGateway extends BaseGateway
{
    private const P2P_API_URL = 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/portal';
    private const B2B_API_URL = 'https://api.binance.com';
    
    public function createPayment(Payment $payment, array $options = []): array
    {
        try {
            if (!$this->validateAmount($payment->amount)) {
                return [
                    'success' => false,
                    'error' => 'Invalid amount'
                ];
            }
            
            if (!$this->validateCurrency($payment->currency)) {
                return [
                    'success' => false,
                    'error' => 'Unsupported currency'
                ];
            }
            
            $gatewayType = $this->config['gateway_type'];
            
            if ($gatewayType === 'binance_p2p') {
                return $this->createP2PPayment($payment, $options);
            } else {
                return $this->createB2BPayment($payment, $options);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Binance payment creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function createP2PPayment(Payment $payment, array $options = []): array
    {
        // For P2P, we create a payment order that user can pay via P2P trading
        $orderData = [
            'order_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $options['payment_method'] ?? 'bank_transfer',
            'description' => $options['description'] ?? 'IMEI Bot Platform Payment',
            'callback_url' => $this->getWebhookUrl(),
            'expire_time' => 1800 // 30 minutes
        ];
        
        // In real implementation, this would call Binance P2P API
        // For now, we simulate the response
        $this->logTransaction('p2p_payment_created', $orderData);
        
        return [
            'success' => true,
            'gateway_transaction_id' => 'P2P_' . $payment->transaction_id,
            'payment_details' => [
                'payment_url' => "https://p2p.binance.com/en/order/{$payment->transaction_id}",
                'qr_code' => $this->generateQRCode($payment),
                'bank_details' => $this->getBankDetails(),
                'instructions' => $this->getP2PInstructions()
            ],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ];
    }
    
    private function createB2BPayment(Payment $payment, array $options = []): array
    {
        // For B2B, we use Binance Pay API
        $orderData = [
            'env' => [
                'terminalType' => 'WEB'
            ],
            'merchantTradeNo' => $payment->transaction_id,
            'orderAmount' => $payment->amount,
            'currency' => $payment->currency,
            'goods' => [
                'goodsType' => 'VIRTUAL',
                'goodsCategory' => 'DIGITAL_GOODS',
                'referenceGoodsId' => 'IMEI_BOT_PAYMENT',
                'goodsName' => 'IMEI Bot Platform Payment',
                'goodsDetail' => 'Payment for IMEI Bot Platform services'
            ],
            'buyer' => [
                'buyerEmail' => $options['email'] ?? null
            ],
            'returnUrl' => $options['return_url'] ?? '',
            'cancelUrl' => $options['cancel_url'] ?? '',
            'notifyUrl' => $this->getWebhookUrl()
        ];
        
        // In real implementation, this would call Binance Pay API
        $this->logTransaction('b2b_payment_created', $orderData);
        
        return [
            'success' => true,
            'gateway_transaction_id' => 'B2B_' . $payment->transaction_id,
            'payment_details' => [
                'payment_url' => "https://pay.binance.com/checkout/{$payment->transaction_id}",
                'checkout_id' => 'checkout_' . $payment->transaction_id,
                'qrcode_link' => "https://pay.binance.com/qrcode/{$payment->transaction_id}"
            ],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ];
    }
    
    public function getPaymentStatus(string $gatewayTransactionId): array
    {
        try {
            // In real implementation, this would call the respective Binance API
            // For now, we simulate the response
            
            $this->logTransaction('status_check', [
                'gateway_transaction_id' => $gatewayTransactionId
            ]);
            
            // Simulate different statuses based on transaction ID
            if (strpos($gatewayTransactionId, 'SUCCESS') !== false) {
                return [
                    'success' => true,
                    'status' => 'completed',
                    'details' => [
                        'paid_amount' => 100.00,
                        'paid_currency' => 'USD',
                        'payment_time' => date('Y-m-d H:i:s')
                    ]
                ];
            } elseif (strpos($gatewayTransactionId, 'PENDING') !== false) {
                return [
                    'success' => true,
                    'status' => 'pending',
                    'details' => [
                        'created_at' => date('Y-m-d H:i:s'),
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
                    ]
                ];
            } else {
                return [
                    'success' => true,
                    'status' => 'failed',
                    'details' => [
                        'failure_reason' => 'Payment expired'
                    ]
                ];
            }
            
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
                'refund_id' => 'REFUND_' . uniqid(),
                'gateway_transaction_id' => $gatewayTransactionId,
                'refund_amount' => $amount,
                'reason' => $reason
            ];
            
            $this->logTransaction('refund_initiated', $refundData);
            
            // In real implementation, this would call Binance refund API
            return [
                'success' => true,
                'refund_id' => $refundData['refund_id'],
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
            $this->logTransaction('webhook_received', $webhookData);
            
            // Verify webhook signature
            if (!$this->verifyWebhook($webhookData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature'
                ];
            }
            
            // Process webhook data
            $eventType = $webhookData['eventType'] ?? '';
            $transactionId = $webhookData['merchantTradeNo'] ?? '';
            
            switch ($eventType) {
                case 'PAYMENT_SUCCESS':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $webhookData['transactionId'] ?? '',
                        'status' => 'completed',
                        'details' => $webhookData
                    ];
                    
                case 'PAYMENT_FAILED':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $webhookData['transactionId'] ?? '',
                        'status' => 'failed',
                        'details' => $webhookData
                    ];
                    
                case 'PAYMENT_EXPIRED':
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $webhookData['transactionId'] ?? '',
                        'status' => 'failed',
                        'details' => [
                            'failure_reason' => 'Payment expired'
                        ]
                    ];
                    
                default:
                    return [
                        'success' => false,
                        'error' => 'Unknown webhook event type'
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
        $signature = $webhookData['signature'] ?? '';
        $secret = $this->getWebhookSecret();
        
        if (empty($secret) || empty($signature)) {
            return false;
        }
        
        // Remove signature from data for verification
        $dataForVerification = $webhookData;
        unset($dataForVerification['signature']);
        
        return $this->verifySignature($dataForVerification, $signature, $secret);
    }
    
    public function getDisplayName(): string
    {
        $gatewayType = $this->config['gateway_type'];
        return $gatewayType === 'binance_p2p' ? 'Binance P2P' : 'Binance Pay';
    }
    
    public function getDescription(): string
    {
        $gatewayType = $this->config['gateway_type'];
        return $gatewayType === 'binance_p2p' 
            ? 'Pay via Binance P2P trading with various payment methods'
            : 'Pay via Binance Pay with cryptocurrency';
    }
    
    public function getSupportedPaymentMethods(): array
    {
        $gatewayType = $this->config['gateway_type'];
        
        if ($gatewayType === 'binance_p2p') {
            return [
                'bank_transfer' => 'Bank Transfer',
                'mobile_banking' => 'Mobile Banking',
                'crypto_wallet' => 'Crypto Wallet',
                'cash_deposit' => 'Cash Deposit'
            ];
        } else {
            return [
                'binance_pay' => 'Binance Pay',
                'crypto_wallet' => 'Crypto Wallet',
                'credit_card' => 'Credit/Debit Card'
            ];
        }
    }
    
    public function testConnection(): array
    {
        try {
            // Test API connectivity
            $testUrl = $this->config['gateway_type'] === 'binance_p2p' 
                ? self::P2P_API_URL . '/friendly/c2c/portal/config'
                : self::B2B_API_URL . '/sapi/v1/capital/config/getall';
                
            $response = $this->makeRequest('GET', $testUrl);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Binance API connection successful',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to connect to Binance API',
                    'details' => $response
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
    
    private function generateQRCode(Payment $payment): string
    {
        // In real implementation, this would generate a QR code
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }
    
    private function getBankDetails(): array
    {
        return [
            'bank_name' => 'Test Bank',
            'account_name' => 'IMEI Bot Platform',
            'account_number' => '1234567890',
            'routing_number' => '987654321'
        ];
    }
    
    private function getP2PInstructions(): array
    {
        return [
            'step1' => 'Copy the payment details',
            'step2' => 'Login to your Binance P2P account',
            'step3' => 'Search for the seller and initiate payment',
            'step4' => 'Upload payment proof',
            'step5' => 'Wait for seller confirmation'
        ];
    }
}
