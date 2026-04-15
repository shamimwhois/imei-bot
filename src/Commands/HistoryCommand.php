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

class HistoryCommand
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
            // Get user's IMEI generation history
            $history = $this->imeiGenerator->getHistory($user->getId(), 20);
            
            if (empty($history)) {
                $this->sendEmptyHistoryMessage($chatId, $user);
                return;
            }
            
            // Get user statistics
            $statistics = $this->getUserStatistics($user->getId());
            
            // Send history response
            $this->sendHistoryResponse($chatId, $user, $history, $statistics);
            
            $this->logger->info('History command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'history_count' => count($history),
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle history command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to retrieve history. Please try again.');
        }
    }
    
    private function sendEmptyHistoryMessage(int $chatId, User $user): void
    {
        $message = "
<b> IMEI Generation History </b>

<b> You haven't generated any IMEIs yet! </b>

<b> Get Started: </b>
<b> /random </b> - Generate a random IMEI
<b> /single </b> - Generate IMEI with prefix
<b> /dual </b> - Generate dual IMEI pair
<b> /multi </b> - Generate multiple IMEIs

<b> Your Statistics: </b>
<b> Total Generated: </b> 0
<b> Daily Limit Used: </b> 0/{$user->getDailyImeiLimit()}
<b> Account Type: </b> " . ucfirst($user->getRole()) . "

<b> Start generating IMEIs to see your history here! </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Generate Random IMEI', 'callback_data' => 'imei:random'],
                ['text' => 'Generate Single IMEI', 'callback_data' => 'imei:single'],
            ],
            [
                ['text' => 'Generate Dual IMEI', 'callback_data' => 'imei:dual'],
                ['text' => 'Generate Multiple', 'callback_data' => 'imei:multi'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send empty history message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function sendHistoryResponse(int $chatId, User $user, array $history, array $statistics): void
    {
        // Send statistics header
        $statsMessage = $this->formatStatisticsMessage($statistics, $user);
        
        try {
            $this->bot->sendMessage($chatId, $statsMessage, 'HTML');
            
            // Send history items
            $this->sendHistoryItems($chatId, $history);
            
            // Send footer with actions
            $this->sendHistoryFooter($chatId);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send history response', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function formatStatisticsMessage(array $statistics, User $user): string
    {
        $totalGenerated = $statistics['total_generated'] ?? 0;
        $validCount = $statistics['valid_count'] ?? 0;
        $uniquePrefixes = $statistics['unique_prefixes'] ?? 0;
        $avgGenerationTime = $statistics['avg_generation_time'] ?? 0;
        
        // Get today's usage
        $today = date('Y-m-d');
        $cacheKey = "imei_daily_limit_{$user->getId()}_{$today}";
        $todayUsage = $this->cache->get($cacheKey, 0);
        $dailyLimit = $user->getDailyImeiLimit();
        $remainingLimit = max(0, $dailyLimit - $todayUsage);
        
        return "
<b> IMEI Generation Statistics </b>

<b> Overall Stats: </b>
<b> Total Generated: </b> {$totalGenerated}
<b> Valid IMEIs: </b> {$validCount}
<b> Success Rate: </b> " . ($totalGenerated > 0 ? round(($validCount / $totalGenerated) * 100, 1) : 0) . "%
<b> Unique Prefixes Used: </b> {$uniquePrefixes}
<b> Avg Generation Time: </b> " . round($avgGenerationTime, 2) . "ms

<b> Today's Usage: </b>
<b> Generated Today: </b> {$todayUsage}
<b> Daily Limit: </b> {$dailyLimit}
<b> Remaining: </b> {$remainingLimit}
<b> Account Type: </b> " . ucfirst($user->getRole()) . "

<b> Recent History: </b>
        ";
    }
    
    private function sendHistoryItems(int $chatId, array $history): void
    {
        $chunks = array_chunk($history, 5);
        
        foreach ($chunks as $index => $chunk) {
            $chunkMessage = "";
            $startNum = $index * 5 + 1;
            $endNum = min(($index + 1) * 5, count($history));
            
            foreach ($chunk as $item) {
                $date = date('M j, Y H:i', strtotime($item['created_at']));
                $status = $item['is_valid'] ? 'Valid' : 'Invalid';
                $statusEmoji = $item['is_valid'] ? 'Valid' : 'Invalid';
                
                $chunkMessage .= "
<b> {$date} </b>
<b> IMEI: </b> <code>{$item['imei']}</code>
<b> Type: </b> " . ucfirst($item['type']) . "
<b> Prefix: </b> {$item['prefix']} ({$statusEmoji} {$status})
";
            }
            
            $chunkMessage .= "\n<b> Items {$startNum}-{$endNum} </b>";
            
            try {
                $this->bot->sendMessage($chatId, $chunkMessage, 'HTML');
            } catch (\Exception $e) {
                $this->logger->error('Failed to send history chunk', [
                    'chat_id' => $chatId,
                    'chunk_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    
    private function sendHistoryFooter(int $chatId): void
    {
        $message = "
<b> History Complete! </b>

<b> Actions: </b>
<b> Generate More IMEIs </b>
<b> Export History </b> (coming soon)
<b> Clear History </b> (admin only)

<b> Quick Actions: </b>
<b> /random </b> - Generate random IMEI
<b> /single </b> - Generate with prefix
<b> /validate </b> - Validate IMEI
<b> /profile </b> - View profile
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Generate Random IMEI', 'callback_data' => 'imei:random'],
                ['text' => 'Generate Single IMEI', 'callback_data' => 'imei:single'],
            ],
            [
                ['text' => 'Generate Dual IMEI', 'callback_data' => 'imei:dual'],
                ['text' => 'Generate Multiple', 'callback_data' => 'imei:multi'],
            ],
            [
                ['text' => 'Validate IMEI', 'callback_data' => 'imei:validate'],
                ['text' => 'View Profile', 'callback_data' => 'menu:profile'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send history footer', [
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
