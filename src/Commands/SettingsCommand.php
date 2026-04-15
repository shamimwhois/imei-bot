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

class SettingsCommand
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
            $settingType = $args[0] ?? 'main';
            
            switch ($settingType) {
                case 'language':
                    $this->showLanguageSettings($chatId, $user);
                    break;
                case 'notifications':
                    $this->showNotificationSettings($chatId, $user);
                    break;
                case 'privacy':
                    $this->showPrivacySettings($chatId, $user);
                    break;
                case 'security':
                    $this->showSecuritySettings($chatId, $user);
                    break;
                case 'display':
                    $this->showDisplaySettings($chatId, $user);
                    break;
                default:
                    $this->showMainSettings($chatId, $user);
                    break;
            }
            
            $this->logger->info('Settings command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'setting_type' => $settingType,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle settings command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to display settings. Please try again.');
        }
    }
    
    private function showMainSettings(int $chatId, User $user): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        
        $message = "
<b> Settings </b>

<b> Configure your preferences, {$firstName}: </b>
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
                ['text' => 'Display', 'callback_data' => 'settings:display'],
                ['text' => 'Account', 'callback_data' => 'settings:account'],
            ],
            [
                ['text' => 'Reset Settings', 'callback_data' => 'settings:reset'],
                ['text' => '« Back', 'callback_data' => 'menu:main'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show main settings', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showLanguageSettings(int $chatId, User $user): void
    {
        $currentLanguage = $user->getLanguage() ?: 'en';
        
        $message = "
<b> Language Settings </b>

<b> Current Language: </b> " . strtoupper($currentLanguage) . "

<b> Available Languages: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'English', 'callback_data' => 'settings:language:en'],
                ['text' => 'Spanish', 'callback_data' => 'settings:language:es'],
            ],
            [
                ['text' => 'French', 'callback_data' => 'settings:language:fr'],
                ['text' => 'German', 'callback_data' => 'settings:language:de'],
            ],
            [
                ['text' => 'Italian', 'callback_data' => 'settings:language:it'],
                ['text' => 'Portuguese', 'callback_data' => 'settings:language:pt'],
            ],
            [
                ['text' => 'Russian', 'callback_data' => 'settings:language:ru'],
                ['text' => 'Chinese', 'callback_data' => 'settings:language:zh'],
            ],
            [
                ['text' => 'Arabic', 'callback_data' => 'settings:language:ar'],
                ['text' => '« Back', 'callback_data' => 'settings:main'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show language settings', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showNotificationSettings(int $chatId, User $user): void
    {
        $preferences = $user->getPreferences() ?? [];
        $notifications = $preferences['notifications'] ?? [];
        
        $message = "
<b> Notification Settings </b>

<b> Choose which notifications you receive: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'IMEI Generation: ' . ($notifications['imei_generated'] ?? 'On'), 'callback_data' => 'settings:notifications:imei_generated'],
                ['text' => 'File Updates: ' . ($notifications['file_updates'] ?? 'On'), 'callback_data' => 'settings:notifications:file_updates'],
            ],
            [
                ['text' => 'System Updates: ' . ($notifications['system_updates'] ?? 'On'), 'callback_data' => 'settings:notifications:system_updates'],
                ['text' => 'Promotions: ' . ($notifications['promotions'] ?? 'Off'), 'callback_data' => 'settings:notifications:promotions'],
            ],
            [
                ['text' => 'Daily Summary: ' . ($notifications['daily_summary'] ?? 'Off'), 'callback_data' => 'settings:notifications:daily_summary'],
                ['text' => 'Weekly Report: ' . ($notifications['weekly_report'] ?? 'Off'), 'callback_data' => 'settings:notifications:weekly_report'],
            ],
            [
                ['text' => 'Enable All', 'callback_data' => 'settings:notifications:enable_all'],
                ['text' => 'Disable All', 'callback_data' => 'settings:notifications:disable_all'],
            ],
            [
                ['text' => '« Back', 'callback_data' => 'settings:main'],
                ['text' => 'Save Settings', 'callback_data' => 'settings:save'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show notification settings', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showPrivacySettings(int $chatId, User $user): void
    {
        $preferences = $user->getPreferences() ?? [];
        $privacy = $preferences['privacy'] ?? [];
        
        $message = "
<b> Privacy Settings </b>

<b> Control your privacy preferences: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Profile Visibility: ' . ($privacy['profile_visibility'] ?? 'Public'), 'callback_data' => 'settings:privacy:profile_visibility'],
                ['text' => 'Activity Status: ' . ($privacy['activity_status'] ?? 'On'), 'callback_data' => 'settings:privacy:activity_status'],
            ],
            [
                ['text' => 'Statistics Sharing: ' . ($privacy['stats_sharing'] ?? 'On'), 'callback_data' => 'settings:privacy:stats_sharing'],
                ['text' => 'Data Collection: ' . ($privacy['data_collection'] ?? 'Minimal'), 'callback_data' => 'settings:privacy:data_collection'],
            ],
            [
                ['text' => 'Search History: ' . ($privacy['search_history'] ?? 'On'), 'callback_data' => 'settings:privacy:search_history'],
                ['text' => 'Download History: ' . ($privacy['download_history'] ?? 'On'), 'callback_data' => 'settings:privacy:download_history'],
            ],
            [
                ['text' => 'Clear History', 'callback_data' => 'settings:privacy:clear_history'],
                ['text' => 'Export Data', 'callback_data' => 'settings:privacy:export_data'],
            ],
            [
                ['text' => '« Back', 'callback_data' => 'settings:main'],
                ['text' => 'Save Settings', 'callback_data' => 'settings:save'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show privacy settings', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showSecuritySettings(int $chatId, User $user): void
    {
        $message = "
<b> Security Settings </b>

<b> Manage your account security: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Change Password', 'callback_data' => 'settings:security:password'],
                ['text' => 'Two-Factor Auth', 'callback_data' => 'settings:security:2fa'],
            ],
            [
                ['text' => 'Active Sessions', 'callback_data' => 'settings:security:sessions'],
                ['text' => 'API Keys', 'callback_data' => 'settings:security:api_keys'],
            ],
            [
                ['text' => 'Login Alerts', 'callback_data' => 'settings:security:login_alerts'],
                ['text' => 'Backup Codes', 'callback_data' => 'settings:security:backup_codes'],
            ],
            [
                ['text' => 'Security Log', 'callback_data' => 'settings:security:log'],
                ['text' => 'Security Tips', 'callback_data' => 'settings:security:tips'],
            ],
            [
                ['text' => '« Back', 'callback_data' => 'settings:main'],
                ['text' => 'Save Settings', 'callback_data' => 'settings:save'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show security settings', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showDisplaySettings(int $chatId, User $user): void
    {
        $preferences = $user->getPreferences() ?? [];
        $display = $preferences['display'] ?? [];
        
        $message = "
<b> Display Settings </b>

<b> Customize your display preferences: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Theme: ' . ($display['theme'] ?? 'Light'), 'callback_data' => 'settings:display:theme'],
                ['text' => 'Font Size: ' . ($display['font_size'] ?? 'Medium'), 'callback_data' => 'settings:display:font_size'],
            ],
            [
                ['text' => 'Message Format: ' . ($display['message_format'] ?? 'HTML'), 'callback_data' => 'settings:display:message_format'],
                ['text' => 'Keyboard Style: ' . ($display['keyboard_style'] ?? 'Inline'), 'callback_data' => 'settings:display:keyboard_style'],
            ],
            [
                ['text' => 'Show Timestamps: ' . ($display['show_timestamps'] ?? 'On'), 'callback_data' => 'settings:display:show_timestamps'],
                ['text' => 'Compact Mode: ' . ($display['compact_mode'] ?? 'Off'), 'callback_data' => 'settings:display:compact_mode'],
            ],
            [
                ['text' => 'Preview Settings', 'callback_data' => 'settings:display:preview'],
                ['text' => 'Reset to Default', 'callback_data' => 'settings:display:reset'],
            ],
            [
                ['text' => '« Back', 'callback_data' => 'settings:main'],
                ['text' => 'Save Settings', 'callback_data' => 'settings:save'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show display settings', [
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
