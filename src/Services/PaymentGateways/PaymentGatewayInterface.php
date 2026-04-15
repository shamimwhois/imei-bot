<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

interface PaymentGatewayInterface
{
    public function __construct(array $config, \IMEIBot\Core\Logger $logger);
    
    /**
     * Create a new payment
     */
    public function createPayment(\IMEIBot\Models\Payment $payment, array $options = []): array;
    
    /**
     * Get payment status from gateway
     */
    public function getPaymentStatus(string $gatewayTransactionId): array;
    
    /**
     * Refund a payment
     */
    public function refundPayment(string $gatewayTransactionId, float $amount, string $reason = ''): array;
    
    /**
     * Process webhook from gateway
     */
    public function processWebhook(array $webhookData): array;
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhook(array $webhookData): bool;
    
    /**
     * Get gateway display name
     */
    public function getDisplayName(): string;
    
    /**
     * Get gateway description
     */
    public function getDescription(): string;
    
    /**
     * Get supported payment methods
     */
    public function getSupportedPaymentMethods(): array;
    
    /**
     * Test gateway connection
     */
    public function testConnection(): array;
}
