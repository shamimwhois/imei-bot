<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\BotManager;
use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;
use IMEIBot\Core\Database;
use IMEIBot\Core\Cache;
use IMEIBot\Core\Logger;

class App
{
    private BotManager $botManager;
    private Logger $logger;
    
    public function __construct()
    {
        $this->initializeServices();
        $this->botManager = new BotManager(
            $this->getConfig(),
            $this->getIMEIGenerator(),
            $this->getFileManager(),
            $this->getSearchService(),
            $this->getLogger(),
            $this->getCache()
        );
        
        $this->logger = $this->getLogger();
    }
    
    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            $this->logger->info('Application starting', [
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
            ]);
            
            // Handle the request
            $this->handleRequest();
            
        } catch (\Exception $e) {
            $this->logger->error('Application error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->handleError($e);
        }
    }
    
    /**
     * Handle incoming requests
     */
    private function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        $this->logger->info('Request received', [
            'method' => $method,
            'path' => $path,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        ]);
        
        // Route the request
        switch ($path) {
            case '/webhook/telegram':
                $this->handleTelegramWebhook();
                break;
                
            case '/webhook/whatsapp':
                $this->handleWhatsAppWebhook();
                break;
                
            case '/health':
                $this->handleHealthCheck();
                break;
                
            case '/status':
                $this->handleStatusCheck();
                break;
                
            case '/api/bots':
                $this->handleBotAPI($method);
                break;
                
            case '/':
            case '/index':
                $this->handleWebInterface();
                break;
                
            default:
                $this->handleNotFound();
                break;
        }
    }
    
    /**
     * Handle Telegram webhook
     */
    private function handleTelegramWebhook(): void
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            $this->logger->warning('Empty Telegram webhook received');
            $this->sendJsonResponse(['error' => 'Empty input'], 400);
            return;
        }
        
        try {
            $this->botManager->handleWebhook('telegram', $input);
            $this->sendJsonResponse(['status' => 'success']);
            
        } catch (\Exception $e) {
            $this->logger->error('Telegram webhook error', [
                'error' => $e->getMessage(),
            ]);
            
            $this->sendJsonResponse(['error' => 'Webhook processing failed'], 500);
        }
    }
    
    /**
     * Handle WhatsApp webhook
     */
    private function handleWhatsAppWebhook(): void
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            $this->logger->warning('Empty WhatsApp webhook received');
            $this->sendJsonResponse(['error' => 'Empty input'], 400);
            return;
        }
        
        // Handle WhatsApp webhook verification
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
            $verifyToken = $this->getConfig()['whatsapp']['webhook_verify_token'] ?? '';
            if ($_GET['hub_verify_token'] === $verifyToken) {
                echo $_GET['hub_challenge'];
                exit;
            }
            $this->sendJsonResponse(['error' => 'Invalid verification token'], 403);
            return;
        }
        
        try {
            $this->botManager->handleWebhook('whatsapp', $input);
            $this->sendJsonResponse(['status' => 'success']);
            
        } catch (\Exception $e) {
            $this->logger->error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
            ]);
            
            $this->sendJsonResponse(['error' => 'Webhook processing failed'], 500);
        }
    }
    
    /**
     * Handle health check
     */
    private function handleHealthCheck(): void
    {
        $health = $this->botManager->healthCheck();
        
        $httpStatus = $health['status'] === 'healthy' ? 200 : 503;
        $this->sendJsonResponse($health, $httpStatus);
    }
    
    /**
     * Handle status check
     */
    private function handleStatusCheck(): void
    {
        $status = [
            'app' => 'IMEI Bot Platform',
            'version' => '1.0.0',
            'status' => 'running',
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => $this->getUptime(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'bots' => $this->botManager->getBotStatus(),
        ];
        
        $this->sendJsonResponse($status);
    }
    
    /**
     * Handle Bot API requests
     */
    private function handleBotAPI(string $method): void
    {
        switch ($method) {
            case 'GET':
                $this->handleBotAPIGet();
                break;
                
            case 'POST':
                $this->handleBotAPIPost();
                break;
                
            case 'PUT':
                $this->handleBotAPIPut();
                break;
                
            case 'DELETE':
                $this->handleBotAPIDelete();
                break;
                
            default:
                $this->sendJsonResponse(['error' => 'Method not allowed'], 405);
                break;
        }
    }
    
    /**
     * Handle Bot API GET requests
     */
    private function handleBotAPIGet(): void
    {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'status':
                $this->sendJsonResponse($this->botManager->getBotStatus());
                break;
                
            case 'statistics':
                $this->sendJsonResponse($this->botManager->getPlatformStatistics());
                break;
                
            case 'health':
                $this->sendJsonResponse($this->botManager->healthCheck());
                break;
                
            default:
                $this->sendJsonResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }
    
    /**
     * Handle Bot API POST requests
     */
    private function handleBotAPIPost(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'broadcast':
                $platform = $input['platform'] ?? '';
                $message = $input['message'] ?? '';
                $options = $input['options'] ?? [];
                
                if (empty($platform) || empty($message)) {
                    $this->sendJsonResponse(['error' => 'Platform and message are required'], 400);
                    return;
                }
                
                $result = $this->botManager->broadcastMessage($platform, $message, $options);
                $this->sendJsonResponse($result);
                break;
                
            case 'send':
                $platform = $input['platform'] ?? '';
                $recipient = $input['recipient'] ?? '';
                $message = $input['message'] ?? '';
                $options = $input['options'] ?? [];
                
                if (empty($platform) || empty($recipient) || empty($message)) {
                    $this->sendJsonResponse(['error' => 'Platform, recipient, and message are required'], 400);
                    return;
                }
                
                $success = $this->botManager->sendMessage($platform, $recipient, $message, $options);
                $this->sendJsonResponse(['success' => $success]);
                break;
                
            case 'restart':
                $platform = $input['platform'] ?? '';
                
                if (empty($platform)) {
                    $this->sendJsonResponse(['error' => 'Platform is required'], 400);
                    return;
                }
                
                $success = $this->botManager->restartPlatform($platform);
                $this->sendJsonResponse(['success' => $success]);
                break;
                
            default:
                $this->sendJsonResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }
    
    /**
     * Handle Bot API PUT requests
     */
    private function handleBotAPIPut(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'toggle':
                $platform = $input['platform'] ?? '';
                $enabled = $input['enabled'] ?? false;
                
                if (empty($platform)) {
                    $this->sendJsonResponse(['error' => 'Platform is required'], 400);
                    return;
                }
                
                $success = $this->botManager->togglePlatform($platform, $enabled);
                $this->sendJsonResponse(['success' => $success]);
                break;
                
            case 'config':
                $config = $input['config'] ?? [];
                
                if (empty($config)) {
                    $this->sendJsonResponse(['error' => 'Config is required'], 400);
                    return;
                }
                
                $this->botManager->updateConfig($config);
                $this->sendJsonResponse(['success' => true]);
                break;
                
            default:
                $this->sendJsonResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }
    
    /**
     * Handle Bot API DELETE requests
     */
    private function handleBotAPIDelete(): void
    {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'cache':
                $this->getCache()->flush();
                $this->sendJsonResponse(['success' => true]);
                break;
                
            default:
                $this->sendJsonResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }
    
    /**
     * Handle web interface
     */
    private function handleWebInterface(): void
    {
        // Serve the web interface
        $webPath = __DIR__ . '/../web/index.php';
        
        if (file_exists($webPath)) {
            include $webPath;
        } else {
            $this->sendJsonResponse(['error' => 'Web interface not found'], 404);
        }
    }
    
    /**
     * Handle 404 errors
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        echo json_encode([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Handle application errors
     */
    private function handleError(\Exception $e): void
    {
        http_response_code(500);
        
        if ($this->isDebugMode()) {
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } else {
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => 'An error occurred while processing your request',
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        }
    }
    
    /**
     * Send JSON response
     */
    private function sendJsonResponse(array $data, int $httpStatus = 200): void
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get application configuration
     */
    private function getConfig(): array
    {
        return [
            'app' => require __DIR__ . '/../config/app.php',
            'database' => require __DIR__ . '/../config/database.php',
            'telegram' => require __DIR__ . '/../config/telegram.php',
            'whatsapp' => require __DIR__ . '/../config/whatsapp.php',
            'monitoring' => require __DIR__ . '/../config/monitoring.php',
            'cache' => require __DIR__ . '/../config/cache.php',
        ];
    }
    
    /**
     * Get IMEI Generator service
     */
    private function getIMEIGenerator(): IMEIGenerator
    {
        static $generator = null;
        
        if ($generator === null) {
            $generator = new IMEIGenerator(
                $this->getDatabase(),
                $this->getCache(),
                $this->getLogger()
            );
        }
        
        return $generator;
    }
    
    /**
     * Get File Manager service
     */
    private function getFileManager(): FileManager
    {
        static $manager = null;
        
        if ($manager === null) {
            $manager = new FileManager(
                $this->getDatabase(),
                $this->getCache(),
                $this->getLogger()
            );
        }
        
        return $manager;
    }
    
    /**
     * Get Search Service
     */
    private function getSearchService(): SearchService
    {
        static $service = null;
        
        if ($service === null) {
            $service = new SearchService(
                $this->getDatabase(),
                $this->getCache(),
                $this->getLogger()
            );
        }
        
        return $service;
    }
    
    /**
     * Get Database instance
     */
    private function getDatabase(): Database
    {
        static $db = null;
        
        if ($db === null) {
            $db = Database::getInstance(
                $this->getConfig()['database'],
                $this->getLogger()
            );
        }
        
        return $db;
    }
    
    /**
     * Get Cache instance
     */
    private function getCache(): Cache
    {
        static $cache = null;
        
        if ($cache === null) {
            $cache = new Cache(
                $this->getConfig()['cache'],
                $this->getLogger()
            );
        }
        
        return $cache;
    }
    
    /**
     * Get Logger instance
     */
    private function getLogger(): Logger
    {
        static $logger = null;
        
        if ($logger === null) {
            $logger = new Logger($this->getConfig()['monitoring']);
        }
        
        return $logger;
    }
    
    /**
     * Initialize services
     */
    private function initializeServices(): void
    {
        // Load environment variables
        $envFile = __DIR__ . '/../.env';
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
    
    /**
     * Get application uptime
     */
    private function getUptime(): string
    {
        $startTime = $_ENV['APP_START_TIME'] ?? time();
        $uptime = time() - $startTime;
        
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        $seconds = $uptime % 60;
        
        return sprintf('%dd %dh %dm %ds', $days, $hours, $minutes, $seconds);
    }
    
    /**
     * Check if debug mode is enabled
     */
    private function isDebugMode(): bool
    {
        return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    }
}

// Run the application
$app = new App();
$app->run();
