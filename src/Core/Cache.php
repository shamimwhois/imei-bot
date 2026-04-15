<?php

declare(strict_types=1);

namespace IMEIBot\Core;

use Predis\Client;
use IMEIBot\Core\Logger;

final class Cache
{
    private ?Client $redis = null;
    private Logger $logger;
    private array $config;
    private bool $connected = false;
    
    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connect();
    }
    
    private function connect(): void
    {
        try {
            $this->redis = new Client([
                'scheme' => 'tcp',
                'host' => $this->config['host'] ?? '127.0.0.1',
                'port' => $this->config['port'] ?? 6379,
                'password' => $this->config['password'] ?? null,
                'database' => $this->config['database'] ?? 0,
                'timeout' => 5.0,
                'read_timeout' => 5.0,
                'retry_interval' => 1.0,
            ]);
            
            // Test connection
            $this->redis->ping();
            $this->connected = true;
            
            $this->logger->info('Redis connection established', [
                'host' => $this->config['host'] ?? '127.0.0.1',
                'port' => $this->config['port'] ?? 6379,
                'database' => $this->config['database'] ?? 0,
            ]);
            
        } catch (\Exception $e) {
            $this->connected = false;
            $this->redis = null;
            
            $this->logger->warning('Redis connection failed - caching disabled', [
                'error' => $e->getMessage(),
                'host' => $this->config['host'] ?? '127.0.0.1',
                'port' => $this->config['port'] ?? 6379,
            ]);
            
            // Don't throw exception - allow platform to work without caching
        }
    }
    
    private function ensureConnected(): bool
    {
        return $this->connected && $this->redis !== null;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->ensureConnected()) {
            return $default;
        }
        
        try {
            $value = $this->redis->get($key);
            
            if ($value === null) {
                return $default;
            }
            
            return unserialize($value);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return $default;
        }
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            $serialized = $this->serialize($value);
            
            if ($ttl > 0) {
                return $this->redis->setex($key, $ttl, $serialized);
            } else {
                return $this->redis->set($key, $serialized);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Cache set failed', [
                'key' => $key,
                'ttl' => $ttl,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function has(string $key): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            return (bool) $this->redis->exists($key);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache has check failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function delete(string $key): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            return (bool) $this->redis->del($key);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache delete failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function flush(): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            return $this->redis->flushdb();
            
        } catch (\Exception $e) {
            $this->logger->error('Cache flush failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    public function increment(string $key, int $value = 1): int|false
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            return $this->redis->incrby($key, $value);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache increment failed', [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function decrement(string $key, int $value = 1): int|false
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            return $this->redis->decrby($key, $value);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache decrement failed', [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function expire(string $key, int $ttl): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            return $this->redis->expire($key, $ttl);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache expire failed', [
                'key' => $key,
                'ttl' => $ttl,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function ttl(string $key): int
    {
        if (!$this->ensureConnected()) {
            return -1;
        }
        
        try {
            return $this->redis->ttl($key);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache TTL check failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return -1;
        }
    }
    
    public function keys(string $pattern = '*'): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }
        
        try {
            return $this->redis->keys($pattern);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache keys failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    public function mget(array $keys): array
    {
        if (!$this->ensureConnected()) {
            return array_fill(0, count($keys), null);
        }
        
        try {
            $values = $this->redis->mget($keys);
            
            return array_map(function ($value) {
                return $value !== null ? $this->deserialize($value) : null;
            }, $values);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache mget failed', [
                'keys' => $keys,
                'error' => $e->getMessage(),
            ]);
            
            return array_fill(0, count($keys), null);
        }
    }
    
    public function mset(array $values): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }
        
        try {
            $serialized = array_map([$this, 'serialize'], $values);
            return $this->redis->mset($serialized);
            
        } catch (\Exception $e) {
            $this->logger->error('Cache mset failed', [
                'keys' => array_keys($values),
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function healthCheck(): array
    {
        $startTime = microtime(true);
        
        if (!$this->ensureConnected()) {
            return [
                'status' => 'unhealthy',
                'response_time_ms' => null,
                'connection' => 'failed',
                'error' => 'Redis not connected',
            ];
        }
        
        try {
            $this->redis->ping();
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'connection' => 'active',
                'database' => $this->config['database'] ?? 0,
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time_ms' => null,
                'connection' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function serialize(mixed $value): string
    {
        return serialize($value);
    }
    
    private function deserialize(string $value): mixed
    {
        return unserialize($value);
    }
    
    public function getStats(): array
    {
        if (!$this->ensureConnected()) {
            return [
                'status' => 'unavailable',
                'error' => 'Redis not connected',
            ];
        }
        
        try {
            $info = $this->redis->info();
            
            return [
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory' => $info['used_memory_human'] ?? '0B',
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Cache stats failed', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    public function close(): void
    {
        if ($this->ensureConnected()) {
            try {
                $this->redis->disconnect();
            } catch (\Exception $e) {
                $this->logger->error('Cache disconnect failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    
    public function __destruct()
    {
        $this->close();
    }
}
