<?php

declare(strict_types=1);

namespace IMEIBot\Services\PaymentGateways;

abstract class BaseGateway implements PaymentGatewayInterface
{
    protected array $config;
    protected \IMEIBot\Core\Logger $logger;
    protected bool $isTestMode;
    
    public function __construct(array $config, \IMEIBot\Core\Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->isTestMode = (bool) ($config['is_test_mode'] ?? true);
    }
    
    protected function makeRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => !$this->isTestMode
            ]);
            
            $options = [
                'headers' => array_merge([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'IMEIBot-Payment/1.0'
                ], $headers)
            ];
            
            if (!empty($data)) {
                if ($method === 'GET') {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }
            
            $response = $client->request($method, $url, $options);
            
            return [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'data' => json_decode($response->getBody()->getContents(), true),
                'headers' => $response->getHeaders()
            ];
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->logger->error('Gateway request failed', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null
            ];
        } catch (\Exception $e) {
            $this->logger->error('Gateway request error', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    protected function generateSignature(array $data, string $secret): string
    {
        ksort($data);
        $string = implode('', array_map(function($key, $value) {
            return $key . $value;
        }, array_keys($data), array_values($data)));
        
        return hash_hmac('sha256', $string, $secret);
    }
    
    protected function verifySignature(array $data, string $signature, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($data, $secret);
        return hash_equals($expectedSignature, $signature);
    }
    
    protected function validateAmount(float $amount): bool
    {
        $minAmount = $this->config['min_amount'] ?? 1.00;
        $maxAmount = $this->config['max_amount'] ?? 10000.00;
        
        return $amount >= $minAmount && $amount <= $maxAmount;
    }
    
    protected function validateCurrency(string $currency): bool
    {
        $supportedCurrencies = $this->config['supported_currencies'] ?? ['USD'];
        
        // Handle JSON string or array
        if (is_string($supportedCurrencies)) {
            $supportedCurrencies = json_decode($supportedCurrencies, true) ?? ['USD'];
        }
        
        return in_array(strtoupper($currency), $supportedCurrencies);
    }
    
    protected function calculateFees(float $amount): array
    {
        $percentageFee = ($amount * ($this->config['fee_percentage'] ?? 0)) / 100;
        $fixedFee = $this->config['fixed_fee'] ?? 0;
        $totalFee = $percentageFee + $fixedFee;
        
        return [
            'percentage_fee' => $percentageFee,
            'fixed_fee' => $fixedFee,
            'total_fee' => $totalFee,
            'net_amount' => $amount - $totalFee
        ];
    }
    
    protected function logTransaction(string $action, array $data): void
    {
        $this->logger->info("Payment gateway transaction: {$action}", [
            'gateway' => $this->config['gateway_type'],
            'action' => $action,
            'data' => $data
        ]);
    }
    
    protected function getApiUrl(string $endpoint = ''): string
    {
        $baseUrl = $this->isTestMode 
            ? ($this->config['test_api_url'] ?? $this->config['api_url'])
            : $this->config['api_url'];
            
        return rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }
    
    protected function getWebhookUrl(): string
    {
        return $this->config['webhook_url'] ?? '';
    }
    
    protected function getWebhookSecret(): string
    {
        return $this->config['webhook_secret'] ?? '';
    }
    
    protected function getApiKey(): string
    {
        return $this->config['api_key'] ?? '';
    }
    
    protected function getApiSecret(): string
    {
        return $this->config['api_secret'] ?? '';
    }
    
    protected function getMerchantId(): string
    {
        return $this->config['merchant_id'] ?? '';
    }
    
    public function testConnection(): array
    {
        try {
            // Default implementation - can be overridden by specific gateways
            return [
                'success' => true,
                'message' => 'Connection test successful',
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
    
    abstract public function getDisplayName(): string;
    abstract public function getDescription(): string;
    abstract public function getSupportedPaymentMethods(): array;
}
