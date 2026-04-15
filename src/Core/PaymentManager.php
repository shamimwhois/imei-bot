<?php

declare(strict_types=1);

namespace IMEIBot\Core;

use IMEIBot\Core\Logger;
use IMEIBot\Core\Cache;
use IMEIBot\Core\Database;
use IMEIBot\Services\PaymentGateways\BinanceGateway;
use IMEIBot\Services\PaymentGateways\BkashGateway;
use IMEIBot\Services\PaymentGateways\NagadGateway;
use IMEIBot\Services\PaymentGateways\BankCardGateway;
use IMEIBot\Services\PaymentGateways\PayPalGateway;
use IMEIBot\Models\Payment;
use IMEIBot\Models\PaymentGateway;

final class PaymentManager
{
    private array $config;
    private Database $db;
    private Logger $logger;
    private Cache $cache;
    private array $gateways = [];
    
    public function __construct(array $config, Database $db, Logger $logger, Cache $cache)
    {
        $this->config = $config;
        $this->db = $db;
        $this->logger = $logger;
        $this->cache = $cache;
        
        $this->initializeGateways();
    }
    
    private function initializeGateways(): void
    {
        $gatewayConfigs = $this->db->fetchAll("SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY display_order");
        
        foreach ($gatewayConfigs as $config) {
            $gateway = $this->createGateway($config);
            if ($gateway) {
                $this->gateways[$config['gateway_type']] = $gateway;
                $this->logger->info('Payment gateway initialized', [
                    'gateway' => $config['gateway_type'],
                    'name' => $config['name']
                ]);
            }
        }
    }
    
    private function createGateway(array $gatewayConfig): PaymentGatewayInterface
    {
        $type = $gatewayConfig['gateway_type'];
        
        return match ($type) {
            'binance_p2p' => new BinanceGateway($gatewayConfig, $this->logger),
            'binance_b2b' => new BinanceGateway($gatewayConfig, $this->logger),
            'bkash' => new BkashGateway($gatewayConfig, $this->logger),
            'nagad' => new NagadGateway($gatewayConfig, $this->logger),
            'bangladeshi_banks' => new BangladeshiBankGateway($gatewayConfig, $this->logger),
            'bank_card' => new BankCardGateway($gatewayConfig, $this->logger),
            'paypal' => new PayPalGateway($gatewayConfig, $this->logger),
            default => throw new \InvalidArgumentException("Unsupported gateway type: {$type}")
        };
    }
    
    public function createPayment(int $userId, string $gateway, float $amount, array $options = []): ?Payment
    {
        try {
            if (!isset($this->gateways[$gateway])) {
                throw new \InvalidArgumentException("Gateway '$gateway' is not available");
            }
            
            $gatewayInstance = $this->gateways[$gateway];
            
            // Validate amount
            $gatewayConfig = $this->getGatewayConfig($gateway);
            if ($amount < $gatewayConfig['min_amount'] || $amount > $gatewayConfig['max_amount']) {
                throw new \InvalidArgumentException("Amount must be between {$gatewayConfig['min_amount']} and {$gatewayConfig['max_amount']}");
            }
            
            // Create payment record
            $payment = new Payment();
            $payment->user_id = $userId;
            $payment->transaction_id = $this->generateTransactionId();
            $payment->gateway = $gateway;
            $payment->amount = $amount;
            $payment->currency = $options['currency'] ?? 'USD';
            $payment->status = 'pending';
            $payment->payment_details = $options['payment_details'] ?? null;
            $payment->ip_address = $options['ip_address'] ?? null;
            $payment->user_agent = $options['user_agent'] ?? null;
            $payment->expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $payment->metadata = $options['metadata'] ?? null;
            
            $payment->save();
            
            // Initiate payment with gateway
            $paymentResponse = $gatewayInstance->createPayment($payment, $options);
            
            if ($paymentResponse['success']) {
                $payment->gateway_transaction_id = $paymentResponse['gateway_transaction_id'] ?? null;
                $payment->payment_details = array_merge($payment->payment_details ?? [], $paymentResponse['payment_details'] ?? []);
                $payment->save();
                
                $this->logger->info('Payment created successfully', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'gateway' => $gateway,
                    'amount' => $amount
                ]);
                
                return $payment;
            } else {
                $payment->status = 'failed';
                $payment->failure_reason = $paymentResponse['error'] ?? 'Gateway error';
                $payment->failed_at = date('Y-m-d H:i:s');
                $payment->save();
                
                throw new \RuntimeException($paymentResponse['error'] ?? 'Payment creation failed');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Payment creation failed', [
                'user_id' => $userId,
                'gateway' => $gateway,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    public function processWebhook(string $gateway, array $webhookData): bool
    {
        try {
            if (!isset($this->gateways[$gateway])) {
                $this->logger->warning('Webhook for unknown gateway', ['gateway' => $gateway]);
                return false;
            }
            
            $gatewayInstance = $this->gateways[$gateway];
            
            // Verify webhook signature
            if (!$gatewayInstance->verifyWebhook($webhookData)) {
                $this->logger->warning('Webhook verification failed', ['gateway' => $gateway]);
                return false;
            }
            
            // Process webhook
            $result = $gatewayInstance->processWebhook($webhookData);
            
            if ($result['success']) {
                $payment = Payment::findByGatewayTransactionId($result['gateway_transaction_id'], $gateway);
                
                if ($payment) {
                    $this->updatePaymentStatus($payment, $result['status'], $result['details'] ?? []);
                    
                    $this->logger->info('Webhook processed successfully', [
                        'gateway' => $gateway,
                        'payment_id' => $payment->id,
                        'status' => $result['status']
                    ]);
                }
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function getPaymentStatus(string $transactionId): ?array
    {
        try {
            $payment = Payment::findByTransactionId($transactionId);
            
            if (!$payment) {
                return null;
            }
            
            // Get real-time status from gateway if payment is pending
            if ($payment->status === 'pending' && isset($this->gateways[$payment->gateway])) {
                $gatewayInstance = $this->gateways[$payment->gateway];
                $gatewayStatus = $gatewayInstance->getPaymentStatus($payment->gateway_transaction_id);
                
                if ($gatewayStatus['success']) {
                    $this->updatePaymentStatus($payment, $gatewayStatus['status'], $gatewayStatus['details'] ?? []);
                }
            }
            
            return [
                'transaction_id' => $payment->transaction_id,
                'gateway' => $payment->gateway,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'created_at' => $payment->created_at,
                'completed_at' => $payment->completed_at,
                'failure_reason' => $payment->failure_reason,
                'payment_details' => $payment->payment_details
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment status', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    public function refundPayment(string $transactionId, float $amount = null, string $reason = ''): bool
    {
        try {
            $payment = Payment::findByTransactionId($transactionId);
            
            if (!$payment || $payment->status !== 'completed') {
                throw new \InvalidArgumentException('Payment not found or not completed');
            }
            
            if (!isset($this->gateways[$payment->gateway])) {
                throw new \RuntimeException('Gateway not available');
            }
            
            $gatewayInstance = $this->gateways[$payment->gateway];
            $refundAmount = $amount ?? $payment->amount;
            
            $refundResult = $gatewayInstance->refundPayment($payment->gateway_transaction_id, $refundAmount, $reason);
            
            if ($refundResult['success']) {
                $payment->status = 'refunded';
                $payment->refund_id = $refundResult['refund_id'];
                $payment->refund_amount = $refundAmount;
                $payment->refund_reason = $reason;
                $payment->refunded_at = date('Y-m-d H:i:s');
                $payment->save();
                
                $this->logger->info('Payment refunded successfully', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transactionId,
                    'refund_amount' => $refundAmount
                ]);
                
                return true;
            }
            
            throw new \RuntimeException($refundResult['error'] ?? 'Refund failed');
            
        } catch (\Exception $e) {
            $this->logger->error('Payment refund failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function getAvailableGateways(): array
    {
        $gateways = [];
        
        foreach ($this->gateways as $type => $gateway) {
            $config = $this->getGatewayConfig($type);
            
            $gateways[] = [
                'type' => $type,
                'name' => $config['name'],
                'display_name' => $gateway->getDisplayName(),
                'description' => $gateway->getDescription(),
                'logo_url' => $config['logo_url'],
                'supported_currencies' => $config['supported_currencies'] ?? ['USD'],
                'min_amount' => $config['min_amount'],
                'max_amount' => $config['max_amount'],
                'fee_percentage' => $config['fee_percentage'],
                'fixed_fee' => $config['fixed_fee'],
                'payment_methods' => $gateway->getSupportedPaymentMethods()
            ];
        }
        
        return $gateways;
    }
    
    public function calculateFees(string $gateway, float $amount): array
    {
        $config = $this->getGatewayConfig($gateway);
        
        $percentageFee = ($amount * $config['fee_percentage']) / 100;
        $fixedFee = $config['fixed_fee'];
        $totalFee = $percentageFee + $fixedFee;
        $netAmount = $amount - $totalFee;
        
        return [
            'amount' => $amount,
            'percentage_fee' => $percentageFee,
            'fixed_fee' => $fixedFee,
            'total_fee' => $totalFee,
            'net_amount' => $netAmount,
            'currency' => 'USD'
        ];
    }
    
    private function updatePaymentStatus(Payment $payment, string $status, array $details = []): void
    {
        $oldStatus = $payment->status;
        $payment->status = $status;
        $payment->payment_details = array_merge($payment->payment_details ?? [], $details);
        
        switch ($status) {
            case 'processing':
                $payment->processed_at = date('Y-m-d H:i:s');
                break;
                
            case 'completed':
                $payment->completed_at = date('Y-m-d H:i:s');
                break;
                
            case 'failed':
                $payment->failure_reason = $details['failure_reason'] ?? 'Payment failed';
                $payment->failed_at = date('Y-m-d H:i:s');
                break;
        }
        
        $payment->save();
        
        if ($oldStatus !== $status) {
            $this->logger->info('Payment status updated', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'old_status' => $oldStatus,
                'new_status' => $status
            ]);
        }
    }
    
    private function getGatewayConfig(string $gateway): array
    {
        $cacheKey = "gateway_config_{$gateway}";
        
        $config = $this->cache->get($cacheKey);
        if ($config === null) {
            $config = $this->db->fetchOne("SELECT * FROM payment_gateways WHERE gateway_type = ?", [$gateway]);
            $this->cache->set($cacheKey, $config, 3600);
        }
        
        return $config;
    }
    
    private function generateTransactionId(): string
    {
        return 'PAY' . strtoupper(uniqid()) . time();
    }
    
    public function getPaymentStatistics(): array
    {
        $stats = [
            'total_payments' => 0,
            'total_amount' => 0,
            'successful_payments' => 0,
            'failed_payments' => 0,
            'pending_payments' => 0,
            'refunded_payments' => 0,
            'gateway_stats' => [],
            'daily_stats' => []
        ];
        
        // Overall statistics
        $overall = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_payments,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded_payments
            FROM payments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $stats = array_merge($stats, $overall);
        
        // Gateway statistics
        $gatewayStats = $this->db->fetchAll("
            SELECT gateway, COUNT(*) as count, SUM(amount) as total
            FROM payments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY gateway
        ");
        
        $stats['gateway_stats'] = $gatewayStats;
        
        // Daily statistics
        $dailyStats = $this->db->fetchAll("
            SELECT DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total
            FROM payments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        $stats['daily_stats'] = $dailyStats;
        
        return $stats;
    }
}
