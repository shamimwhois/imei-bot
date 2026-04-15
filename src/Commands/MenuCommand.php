<?php

declare(strict_types=1);

namespace IMEIBot\Commands;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;
use IMEIBot\Models\User;
use IMEIBot\Core\Logger;
use IMEIBot\Core\Cache;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class MenuCommand
{
    private BotApi $bot;
    private IMEIGenerator $imeiGenerator;
    private FileManager $fileManager;
    private SearchService $searchService;
    private Logger $logger;
    private Cache $cache;
    
    public function __construct(
        BotApi $bot,
        IMEIGenerator $imeiGenerator,
        FileManager $fileManager,
        SearchService $searchService,
        Logger $logger,
        Cache $cache
    ) {
        $this->bot = $bot;
        $this->imeiGenerator = $imeiGenerator;
        $this->fileManager = $fileManager;
        $this->searchService = $searchService;
        $this->logger = $logger;
        $this->cache = $cache;
    }
    
    public function handle(Message $message, User $user, array $args): void
    {
        $chatId = $message->getChat()->getId();
        
        try {
            $menuType = $args[0] ?? 'main';
            
            switch ($menuType) {
                case 'imei':
                    $this->showIMEIMenu($chatId, $user);
                    break;
                case 'files':
                    $this->showFilesMenu($chatId, $user);
                    break;
                case 'profile':
                    $this->showProfileMenu($chatId, $user);
                    break;
                case 'help':
                    $this->showHelpMenu($chatId, $user);
                    break;
                case 'premium':
                    $this->showPremiumMenu($chatId, $user);
                    break;
                case 'stats':
                    $this->showStatsMenu($chatId, $user);
                    break;
                case 'settings':
                    $this->showSettingsMenu($chatId, $user);
                    break;
                default:
                    $this->showMainMenu($chatId, $user);
                    break;
            }
            
            $this->logger->info('Menu command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'menu_type' => $menuType,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle menu command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to display menu. Please try again.');
        }
    }
    
    private function showMainMenu(int $chatId, User $user): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        
        $message = "
<b> IMEI Bot Platform - Main Menu </b>

<b> Welcome, {$firstName}! </b>

<b> Choose an option below: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'IMEI Services', 'callback_data' => 'menu:imei'],
                ['text' => 'File Downloads', 'callback_data' => 'menu:files'],
            ],
            [
                ['text' => 'My Profile', 'callback_data' => 'menu:profile'],
                ['text' => 'Help & Support', 'callback_data' => 'menu:help'],
            ],
            [
                ['text' => 'Premium Features', 'callback_data' => 'menu:premium'],
                ['text' => 'Statistics', 'callback_data' => 'menu:stats'],
            ],
            [
                ['text' => 'Settings', 'callback_data' => 'menu:settings'],
                ['text' => 'Send Feedback', 'callback_data' => 'menu:feedback'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show main menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showIMEIMenu(int $chatId, User $user): void
    {
        $message = "
<b> IMEI Services Menu </b>

<b> Generate and validate IMEI numbers: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Random IMEI', 'callback_data' => 'imei:random'],
                ['text' => 'Single IMEI', 'callback_data' => 'imei:single'],
            ],
            [
                ['text' => 'Dual IMEI', 'callback_data' => 'imei:dual'],
                ['text' => 'Multiple IMEIs', 'callback_data' => 'imei:multi'],
            ],
            [
                ['text' => 'Validate IMEI', 'callback_data' => 'imei:validate'],
                ['text' => 'View History', 'callback_data' => 'imei:history'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show IMEI menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showFilesMenu(int $chatId, User $user): void
    {
        $message = "
<b> File Downloads Menu </b>

<b> Browse and download mobile files: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Firmware', 'callback_data' => 'file:firmware'],
                ['text' => 'Engineering ROM', 'callback_data' => 'file:engrom'],
            ],
            [
                ['text' => 'QCN/NVRAM', 'callback_data' => 'file:qcnnvram'],
                ['text' => 'Solutions', 'callback_data' => 'file:solution'],
            ],
            [
                ['text' => 'Search Files', 'callback_data' => 'file:search'],
                ['text' => 'Recent Files', 'callback_data' => 'file:recent'],
            ],
            [
                ['text' => 'Popular Files', 'callback_data' => 'file:popular'],
                ['text' => 'My Downloads', 'callback_data' => 'file:downloads'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show files menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showProfileMenu(int $chatId, User $user): void
    {
        $message = "
<b> Profile Menu </b>

<b> Manage your account and view statistics: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'View Profile', 'callback_data' => 'profile:view'],
                ['text' => 'Statistics', 'callback_data' => 'profile:stats'],
            ],
            [
                ['text' => 'IMEI History', 'callback_data' => 'profile:history'],
                ['text' => 'Download History', 'callback_data' => 'profile:downloads'],
            ],
            [
                ['text' => 'Account Settings', 'callback_data' => 'profile:settings'],
                ['text' => 'Notifications', 'callback_data' => 'profile:notifications'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show profile menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showHelpMenu(int $chatId, User $user): void
    {
        $message = "
<b> Help & Support Menu </b>

<b> Get help and support: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Command Help', 'callback_data' => 'help:commands'],
                ['text' => 'FAQ', 'callback_data' => 'help:faq'],
            ],
            [
                ['text' => 'Contact Support', 'callback_data' => 'help:support'],
                ['text' => 'Send Feedback', 'callback_data' => 'help:feedback'],
            ],
            [
                ['text' => 'Report Bug', 'callback_data' => 'help:bug'],
                ['text' => 'Feature Request', 'callback_data' => 'help:feature'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'About', 'callback_data' => 'help:about'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show help menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showPremiumMenu(int $chatId, User $user): void
    {
        $isPremium = $user->getRole() === 'premium';
        
        if ($isPremium) {
            $message = "
<b> Premium Features Menu </b>

<b> You have premium access! </b>
            ";
        } else {
            $message = "
<b> Premium Features Menu </b>

<b> Upgrade to premium for unlimited access: </b>
            ";
        }
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Premium Benefits', 'callback_data' => 'premium:benefits'],
                ['text' => 'Pricing Plans', 'callback_data' => 'premium:pricing'],
            ],
            [
                ['text' => 'Upgrade Now', 'callback_data' => 'premium:upgrade'],
                ['text' => 'Manage Subscription', 'callback_data' => 'premium:manage'],
            ],
            [
                ['text' => 'Premium Statistics', 'callback_data' => 'premium:stats'],
                ['text' => 'Priority Support', 'callback_data' => 'premium:support'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show premium menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showStatsMenu(int $chatId, User $user): void
    {
        $message = "
<b> Statistics Menu </b>

<b> View platform and personal statistics: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'My Statistics', 'callback_data' => 'stats:personal'],
                ['text' => 'Platform Stats', 'callback_data' => 'stats:platform'],
            ],
            [
                ['text' => 'IMEI Statistics', 'callback_data' => 'stats:imei'],
                ['text' => 'File Statistics', 'callback_data' => 'stats:files'],
            ],
            [
                ['text' => 'Usage Analytics', 'callback_data' => 'stats:usage'],
                ['text' => 'Growth Metrics', 'callback_data' => 'stats:growth'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show stats menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showSettingsMenu(int $chatId, User $user): void
    {
        $message = "
<b> Settings Menu </b>

<b> Configure your preferences: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Language', 'callback_data' => 'settings:language'],
                ['text' => 'Notifications', 'callback_data' => 'settings:notifications'],
            ],
            [
                ['text' => 'Privacy', 'callback_data' => 'settings:privacy'],
                ['text' => 'Security', 'callback_data' => 'settings:security'],
            ],
            [
                ['text' => 'Display Settings', 'callback_data' => 'settings:display'],
                ['text' => 'Reset Settings', 'callback_data' => 'settings:reset'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show settings menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function sendMessage(int $chatId, string $text, string $parseMode = null, $replyMarkup = null): void
    {
        try {
            $this->bot->sendMessage($chatId, $text, $parseMode, false, null, $replyMarkup);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
