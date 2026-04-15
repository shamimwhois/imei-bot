<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

class Server
{
    private Database $db;
    private Logger $logger;
    
    public function __construct()
    {
        $this->loadEnvironment();
        $this->db = Database::getInstance(
            $this->getDatabaseConfig(),
            new Logger($this->getMonitoringConfig())
        );
        $this->logger = new Logger($this->getMonitoringConfig());
    }
    
    public function start(): void
    {
        echo "Starting IMEI Bot Platform...\n";
        
        try {
            // Check database connection
            $this->checkDatabase();
            
            // Check Redis connection
            $this->checkRedis();
            
            // Start built-in PHP server
            $this->startServer();
            
        } catch (\Exception $e) {
            echo "Server startup failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function checkDatabase(): void
    {
        echo "Checking database connection...\n";
        
        try {
            $result = $this->db->query("SELECT 1");
            echo "Database connection: OK\n";
        } catch (\Exception $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function checkRedis(): void
    {
        echo "Checking Redis connection...\n";
        
        try {
            $redis = new \Predis\Client([
                'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
                'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                'password' => !empty($_ENV['REDIS_PASSWORD']) ? null : $_ENV['REDIS_PASSWORD'],
            ]);
            
            $redis->connect();
            $redis->ping();
            echo "Redis connection: OK\n";
        } catch (\Exception $e) {
            echo "Warning: Redis connection failed - " . $e->getMessage() . "\n";
            echo "Continuing without Redis (caching disabled)\n";
        }
    }
    
    private function startServer(): void
    {
        $host = 'localhost';
        $port = 8080;
        $documentRoot = __DIR__ . '/web';
        
        echo "Starting server on http://{$host}:{$port}\n";
        echo "Document root: {$documentRoot}\n";
        echo "Press Ctrl+C to stop the server\n\n";
        
        // Set environment variable for app start time
        $_ENV['APP_START_TIME'] = time();
        
        // Start PHP built-in server
        $command = "php -S {$host}:{$port} -t {$documentRoot} " . __DIR__ . "/src/App.php";
        passthru($command);
    }
    
    private function loadEnvironment(): void
    {
        $envFile = __DIR__ . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }
    
    private function getDatabaseConfig(): array
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        ];
    }
    
    private function getMonitoringConfig(): array
    {
        return [
            'level' => $_ENV['APP_DEBUG'] === 'true' ? 'debug' : 'info',
            'format' => 'json',
            'channels' => [
                'default' => 'logs/app.log',
            ],
        ];
    }
}

// Start server
$server = new Server();
$server->start();
