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

class ProfileCommand
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
            // Get user statistics
            $statistics = $this->getUserStatistics($user->getId());
            
            // Get today's usage
            $today = date('Y-m-d');
            $cacheKey = "imei_daily_limit_{$user->getId()}_{$today}";
            $todayUsage = $this->cache->get($cacheKey, 0);
            
            // Send profile information
            $this->sendProfileMessage($chatId, $user, $statistics, $todayUsage);
            
            $this->logger->info('Profile command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle profile command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to retrieve profile. Please try again.');
        }
    }
    
    private function sendProfileMessage(int $chatId, User $user, array $statistics, int $todayUsage): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        $username = $user->getUsername() ? "@{$user->getUsername()}" : 'Not set';
        $role = ucfirst($user->getRole());
        $status = ucfirst($user->getStatus());
        $joinedDate = date('M j, Y', strtotime($user->getCreatedAt()));
        $lastActivity = $user->getLastActivityAt() ? date('M j, Y H:i', strtotime($user->getLastActivityAt())) : 'Never';
        
        $totalGenerated = $statistics['total_generated'] ?? 0;
        $validCount = $statistics['valid_count'] ?? 0;
        $successRate = $totalGenerated > 0 ? round(($validCount / $totalGenerated) * 100, 1) : 0;
        $dailyLimit = $user->getDailyImeiLimit();
        $remainingLimit = max(0, $dailyLimit - $todayUsage);
        
        $message = "
<b> User Profile </b>

<b> Account Information: </b>
<b> Name: </b> {$firstName}
<b> Username: </b> {$username}
<b> Role: </b> {$role}
<b> Status: </b> {$status}
<b> Joined: </b> {$joinedDate}
<b> Last Activity: </b> {$lastActivity}

<b> IMEI Generation Stats: </b>
<b> Total Generated: </b> {$totalGenerated}
<b> Valid IMEIs: </b> {$validCount}
<b> Success Rate: </b> {$successRate}%
<b> Today's Usage: </b> {$todayUsage}/{$dailyLimit}
<b> Remaining: </b> {$remainingLimit}

<b> Account Features: </b>
<b> Daily Limit: </b> {$dailyLimit} IMEIs
<b> Download Limit: </b> {$user->getDailyDownloadLimit()} files
<b> Storage Quota: </b> {$user->getStorageQuotaMb()}MB
<b> Storage Used: </b> {$user->getStorageUsedMb()}MB
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'IMEI History', 'callback_data' => 'profile:history'],
                ['text' => 'Download History', 'callback_data' => 'profile:downloads'],
            ],
            [
                ['text' => 'Account Settings', 'callback_data' => 'profile:settings'],
                ['text' => 'Upgrade to Premium', 'callback_data' => 'menu:premium'],
            ],
            [
                ['text' => 'Statistics', 'callback_data' => 'menu:stats'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send profile message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function getUserStatistics(int $userId): array
    {
        try {
            return $this->imeiGenerator->getStatistics();
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user statistics', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_generated' => 0,
                'valid_count' => 0,
                'unique_prefixes' => 0,
                'avg_generation_time' => 0,
            ];
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
