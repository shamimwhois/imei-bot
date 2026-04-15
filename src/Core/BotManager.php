<?php

declare(strict_types=1);

namespace IMEIBot\Core;

use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;
use IMEIBot\Core\Logger;
use IMEIBot\Core\Cache;

final class BotManager
{
    private array $config;
    private IMEIGenerator $imeiGenerator;
    private FileManager $fileManager;
    private SearchService $searchService;
    private Logger $logger;
    private Cache $cache;
    private ?TelegramBot $telegramBot = null;
    private ?WhatsAppBot $whatsappBot = null;
    
    public function __construct(
        array $config,
        IMEIGenerator $imeiGenerator,
        FileManager $fileManager,
        SearchService $searchService,
        Logger $logger,
        Cache $cache
    ) {
        $this->config = $config;
        $this->imeiGenerator = $imeiGenerator;
        $this->fileManager = $fileManager;
        $this->searchService = $searchService;
        $this->logger = $logger;
        $this->cache = $cache;
        
        $this->initializeBots();
    }
    
    private function initializeBots(): void
    {
        // Initialize Telegram Bot if enabled
        if ($this->config['telegram']['enabled'] ?? false) {
            $this->telegramBot = new TelegramBot(
                $this->config['telegram'],
                $this->imeiGenerator,
                $this->fileManager,
                $this->searchService,
                $this->logger,
                $this->cache
            );
        }
        
        // Initialize WhatsApp Bot if enabled
        if ($this->config['whatsapp']['enabled'] ?? false) {
            $this->whatsappBot = new WhatsAppBot(
                $this->config['whatsapp'],
                $this->imeiGenerator,
                $this->fileManager,
                $this->searchService,
                $this->logger,
                $this->cache
            );
        }
        
        $this->logger->info('Bot Manager initialized', [
            'telegram_enabled' => $this->telegramBot !== null,
            'whatsapp_enabled' => $this->whatsappBot !== null,
        ]);
    }
    
    /**
     * Handle incoming webhook requests
     */
    public function handleWebhook(string $platform, string $input): void
    {
        try {
            $this->logger->info('Webhook received', [
                'platform' => $platform,
                'input_length' => strlen($input),
            ]);
            
            switch ($platform) {
                case 'telegram':
                    if ($this->telegramBot) {
                        $this->telegramBot->handleWebhook($input);
                    } else {
                        $this->logger->warning('Telegram webhook received but bot not enabled');
                    }
                    break;
                    
                case 'whatsapp':
                    if ($this->whatsappBot) {
                        $this->whatsappBot->handleWebhook($input);
                    } else {
                        $this->logger->warning('WhatsApp webhook received but bot not enabled');
                    }
                    break;
                    
                default:
                    $this->logger->error('Unknown platform webhook', ['platform' => $platform]);
                    break;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Webhook handling failed', [
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw if needed for proper HTTP response
            throw $e;
        }
    }
    
    /**
     * Get bot status and health information
     */
    public function getBotStatus(): array
    {
        $status = [
            'telegram' => $this->telegramBot ? [
                'enabled' => true,
                'status' => 'active',
                'last_check' => date('Y-m-d H:i:s'),
                'webhook_url' => $this->config['telegram']['webhook_url'] ?? null,
                'bot_info' => $this->telegramBot->getBotInfo(),
                'webhook_info' => $this->telegramBot->getWebhookInfo(),
            ] : ['enabled' => false, 'status' => 'disabled'],
            
            'whatsapp' => $this->whatsappBot ? [
                'enabled' => true,
                'status' => 'active',
                'last_check' => date('Y-m-d H:i:s'),
                'webhook_url' => $this->config['whatsapp']['webhook_url'] ?? null,
                'phone_number_id' => $this->config['whatsapp']['phone_number_id'] ?? null,
            ] : ['enabled' => false, 'status' => 'disabled'],
        ];
        
        return $status;
    }
    
    /**
     * Send message to specific platform
     */
    public function sendMessage(string $platform, string $recipient, string $message, array $options = []): bool
    {
        try {
            switch ($platform) {
                case 'telegram':
                    if ($this->telegramBot) {
                        // Implementation would depend on TelegramBot's send method
                        $this->logger->info('Sending Telegram message', [
                            'recipient' => $recipient,
                            'message_length' => strlen($message),
                        ]);
                        return true;
                    }
                    break;
                    
                case 'whatsapp':
                    if ($this->whatsappBot) {
                        // Implementation would depend on WhatsAppBot's send method
                        $this->logger->info('Sending WhatsApp message', [
                            'recipient' => $recipient,
                            'message_length' => strlen($message),
                        ]);
                        return true;
                    }
                    break;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send message', [
                'platform' => $platform,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Broadcast message to all active users on a platform
     */
    public function broadcastMessage(string $platform, string $message, array $options = []): array
    {
        $results = [
            'total_sent' => 0,
            'total_failed' => 0,
            'errors' => [],
        ];
        
        try {
            // Get all active users for the platform
            $users = $this->getActiveUsersByPlatform($platform);
            
            foreach ($users as $user) {
                $recipient = $this->getRecipientByPlatform($platform, $user);
                
                if ($this->sendMessage($platform, $recipient, $message, $options)) {
                    $results['total_sent']++;
                } else {
                    $results['total_failed']++;
                }
            }
            
            $this->logger->info('Broadcast completed', [
                'platform' => $platform,
                'total_sent' => $results['total_sent'],
                'total_failed' => $results['total_failed'],
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Broadcast failed', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
            
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Get platform statistics
     */
    public function getPlatformStatistics(): array
    {
        $stats = [
            'telegram' => [
                'total_users' => 0,
                'active_users_today' => 0,
                'messages_today' => 0,
                'imei_generated_today' => 0,
            ],
            'whatsapp' => [
                'total_users' => 0,
                'active_users_today' => 0,
                'messages_today' => 0,
                'imei_generated_today' => 0,
            ],
        ];
        
        try {
            // Get Telegram statistics
            if ($this->telegramBot) {
                $stats['telegram'] = $this->getTelegramStatistics();
            }
            
            // Get WhatsApp statistics
            if ($this->whatsappBot) {
                $stats['whatsapp'] = $this->getWhatsAppStatistics();
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get platform statistics', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return $stats;
    }
    
    /**
     * Enable or disable a bot platform
     */
    public function togglePlatform(string $platform, bool $enabled): bool
    {
        try {
            $configKey = "{$platform}_enabled";
            
            // Update configuration
            $this->config[$platform]['enabled'] = $enabled;
            
            // Re-initialize bots
            $this->initializeBots();
            
            $this->logger->info('Platform toggled', [
                'platform' => $platform,
                'enabled' => $enabled,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to toggle platform', [
                'platform' => $platform,
                'enabled' => $enabled,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Restart a bot platform
     */
    public function restartPlatform(string $platform): bool
    {
        try {
            $this->logger->info('Restarting platform', ['platform' => $platform]);
            
            // Disable and re-enable the platform
            $this->togglePlatform($platform, false);
            sleep(1);
            $this->togglePlatform($platform, true);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to restart platform', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Get active users by platform
     */
    private function getActiveUsersByPlatform(string $platform): array
    {
        // This would query the database for active users on the platform
        // Implementation depends on your database structure
        
        $users = [];
        
        try {
            $db = $this->getDatabase();
            
            switch ($platform) {
                case 'telegram':
                    $sql = "SELECT * FROM users WHERE telegram_id IS NOT NULL AND status = 'active' AND deleted_at IS NULL";
                    break;
                    
                case 'whatsapp':
                    $sql = "SELECT * FROM users WHERE whatsapp_phone IS NOT NULL AND status = 'active' AND deleted_at IS NULL";
                    break;
                    
                default:
                    return [];
            }
            
            $stmt = $db->query($sql);
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get active users', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $users;
    }
    
    /**
     * Get recipient identifier by platform
     */
    private function getRecipientByPlatform(string $platform, array $user): string
    {
        switch ($platform) {
            case 'telegram':
                return (string) $user['telegram_id'];
                
            case 'whatsapp':
                return $user['whatsapp_phone'];
                
            default:
                return '';
        }
    }
    
    /**
     * Get Telegram statistics
     */
    private function getTelegramStatistics(): array
    {
        $today = date('Y-m-d');
        
        return [
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL AND deleted_at IS NULL"),
            'active_users_today' => $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL AND last_activity_at >= DATE(NOW()) AND deleted_at IS NULL"),
            'messages_today' => $this->db->fetchColumn("SELECT COUNT(*) FROM imei_logs WHERE platform = 'telegram' AND DATE(created_at) = :today", [':today' => $today]),
            'imei_generated_today' => $this->db->fetchColumn("SELECT COUNT(*) FROM imei_logs WHERE platform = 'telegram' AND DATE(created_at) = :today", [':today' => $today]),
        ];
    }
    
    /**
     * Get WhatsApp statistics
     */
    private function getWhatsAppStatistics(): array
    {
        $today = date('Y-m-d');
        
        return [
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE whatsapp_phone IS NOT NULL AND deleted_at IS NULL"),
            'active_users_today' => $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE whatsapp_phone IS NOT NULL AND last_activity_at >= DATE(NOW()) AND deleted_at IS NULL"),
            'messages_today' => $this->db->fetchColumn("SELECT COUNT(*) FROM imei_logs WHERE platform = 'whatsapp' AND DATE(created_at) = :today", [':today' => $today]),
            'imei_generated_today' => $this->db->fetchColumn("SELECT COUNT(*) FROM imei_logs WHERE platform = 'whatsapp' AND DATE(created_at) = :today", [':today' => $today]),
        ];
    }
    
    /**
     * Get database instance
     */
    private function getDatabase(): \IMEIBot\Core\Database
    {
        static $db = null;
        
        if ($db === null) {
            $db = \IMEIBot\Core\Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
        }
        
        return $db;
    }
    
    /**
     * Get bot instance by platform
     */
    public function getBot(string $platform): ?object
    {
        switch ($platform) {
            case 'telegram':
                return $this->telegramBot;
                
            case 'whatsapp':
                return $this->whatsappBot;
                
            default:
                return null;
        }
    }
    
    /**
     * Check if platform is available
     */
    public function isPlatformAvailable(string $platform): bool
    {
        switch ($platform) {
            case 'telegram':
                return $this->telegramBot !== null;
                
            case 'whatsapp':
                return $this->whatsappBot !== null;
                
            default:
                return false;
        }
    }
    
    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Update configuration
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initializeBots();
    }
    
    /**
     * Health check for all platforms
     */
    public function healthCheck(): array
    {
        $health = [
            'status' => 'healthy',
            'platforms' => [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        $platforms = ['telegram', 'whatsapp'];
        
        foreach ($platforms as $platform) {
            $platformHealth = [
                'platform' => $platform,
                'enabled' => $this->isPlatformAvailable($platform),
                'status' => 'healthy',
                'last_check' => date('Y-m-d H:i:s'),
                'details' => [],
            ];
            
            if (!$platformHealth['enabled']) {
                $platformHealth['status'] = 'disabled';
            } else {
                // Perform platform-specific health checks
                try {
                    $bot = $this->getBot($platform);
                    if ($bot && method_exists($bot, 'healthCheck')) {
                        $platformHealth['details'] = $bot->healthCheck();
                    }
                } catch (\Exception $e) {
                    $platformHealth['status'] = 'unhealthy';
                    $platformHealth['details']['error'] = $e->getMessage();
                    $health['status'] = 'degraded';
                }
            }
            
            $health['platforms'][] = $platformHealth;
        }
        
        return $health;
    }
}
