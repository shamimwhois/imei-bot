<?php

declare(strict_types=1);

namespace IMEIBot\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;

final class Logger
{
    private MonologLogger $logger;
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setupLogger();
    }
    
    private function setupLogger(): void
    {
        $this->logger = new MonologLogger('imei_bot');
        
        // Add handlers based on configuration
        $this->addFileHandlers();
        $this->addErrorLogHandler();
        
        // Set formatter based on environment
        $formatter = ($this->config['format'] ?? 'json') === 'json' 
            ? new JsonFormatter() 
            : new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s',
                true,
                true
            );
        
        foreach ($this->logger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }
    
    private function addFileHandlers(): void
    {
        $channels = $this->config['channels'] ?? [
            'performance' => 'logs/performance.log',
            'errors' => 'logs/errors.log',
            'health_checks' => 'logs/health_checks.log',
            'metrics' => 'logs/metrics.log',
            'alerts' => 'logs/alerts.log',
        ];
        
        foreach ($channels as $channel => $filename) {
            $handler = new RotatingFileHandler(
                __DIR__ . '/../../storage/' . $filename,
                $this->config['max_files'] ?? 30,
                $this->getLogLevel(),
                true,
                null,
                true
            );
            
            $this->logger->pushHandler($handler);
        }
    }
    
    private function addErrorLogHandler(): void
    {
        if ($this->config['error_log'] ?? true) {
            $handler = new ErrorLogHandler(
                ErrorLogHandler::OPERATING_SYSTEM,
                $this->getLogLevel()
            );
            
            $this->logger->pushHandler($handler);
        }
    }
    
    private function getLogLevel(): int
    {
        $level = $this->config['level'] ?? 'info';
        
        return match (strtolower($level)) {
            'debug' => MonologLogger::DEBUG,
            'info' => MonologLogger::INFO,
            'notice' => MonologLogger::NOTICE,
            'warning' => MonologLogger::WARNING,
            'error' => MonologLogger::ERROR,
            'critical' => MonologLogger::CRITICAL,
            'alert' => MonologLogger::ALERT,
            'emergency' => MonologLogger::EMERGENCY,
            default => MonologLogger::INFO,
        };
    }
    
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
    
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }
    
    public function notice(string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }
    
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }
    
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
    
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }
    
    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }
    
    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }
    
    public function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
    
    public function withContext(array $context): self
    {
        $newLogger = clone $this;
        $newLogger->logger->withContext($context);
        
        return $newLogger;
    }
    
    public function getLogger(): MonologLogger
    {
        return $this->logger;
    }
    
    public function addPerformanceMetric(string $operation, float $duration, array $context = []): void
    {
        $this->info('Performance metric', array_merge([
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logUserAction(int $userId, string $action, array $context = []): void
    {
        $this->info('User action', array_merge([
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logBotInteraction(string $platform, int $userId, string $command, array $context = []): void
    {
        $this->info('Bot interaction', array_merge([
            'platform' => $platform,
            'user_id' => $userId,
            'command' => $command,
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logError(\Throwable $exception, array $context = []): void
    {
        $this->error('Exception occurred', array_merge([
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logDatabaseQuery(string $sql, array $params, float $duration, array $context = []): void
    {
        $this->debug('Database query', array_merge([
            'sql' => $sql,
            'params' => $params,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logCacheOperation(string $operation, string $key, $value = null, array $context = []): void
    {
        $this->debug('Cache operation', array_merge([
            'operation' => $operation,
            'key' => $key,
            'value_type' => is_object($value) ? get_class($value) : gettype($value),
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logApiRequest(string $method, string $endpoint, int $statusCode, float $duration, array $context = []): void
    {
        $this->info('API request', array_merge([
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->warning('Security event', array_merge([
            'event' => $event,
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function logSystemEvent(string $event, array $context = []): void
    {
        $this->info('System event', array_merge([
            'event' => $event,
            'timestamp' => microtime(true),
        ], $context));
    }
    
    public function createTimer(): callable
    {
        $startTime = microtime(true);
        
        return function (string $operation) use ($startTime): void {
            $duration = microtime(true) - $startTime;
            $this->addPerformanceMetric($operation, $duration);
        };
    }
    
    public function healthCheck(): array
    {
        $startTime = microtime(true);
        
        try {
            // Test logging
            $this->debug('Health check test');
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'handlers_count' => count($this->logger->getHandlers()),
                'level' => $this->config['level'] ?? 'info',
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time_ms' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
