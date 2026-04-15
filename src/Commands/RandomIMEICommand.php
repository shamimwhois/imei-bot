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

class RandomIMEICommand
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
            // Check user rate limit
            if (!$this->checkUserLimit($user)) {
                $this->sendMessage($chatId, 
                    "You've reached your daily IMEI generation limit.\n" .
                    "Upgrade to premium for unlimited access: /premium"
                );
                return;
            }
            
            $startTime = microtime(true);
            $imei = $this->imeiGenerator->generateRandom();
            $generationTime = (microtime(true) - $startTime) * 1000;
            
            // Log the generation
            $this->imeiGenerator->logGeneration($user->getId(), 'telegram', [$imei], 'random');
            
            // Send response with validation info
            $validation = $this->imeiGenerator->validate($imei);
            $prefixInfo = $this->getPrefixInfo($validation['prefix']);
            
            $responseMessage = $this->formatIMEIResponse($imei, $validation, $prefixInfo, $generationTime);
            $keyboard = $this->getIMEIKeyboard();
            
            $this->bot->sendMessage($chatId, $responseMessage, 'HTML', false, null, $keyboard);
            
            $this->logger->info('Random IMEI generated', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'imei' => $imei,
                'generation_time_ms' => $generationTime,
                'prefix' => $validation['prefix'],
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate random IMEI', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to generate IMEI. Please try again later.');
        }
    }
    
    private function formatIMEIResponse(string $imei, array $validation, array $prefixInfo, float $generationTime): string
    {
        $status = $validation['is_valid'] ? 'Valid' : 'Invalid';
        $statusEmoji = $validation['is_valid'] ? 'Valid' : 'Invalid';
        
        return "
<b> IMEI Generated Successfully! </b>

<b> IMEI: </b> <code>{$imei}</code>
<b> Status: </b> {$statusEmoji} {$status}
<b> Prefix: </b> {$validation['prefix']} ({$prefixInfo['country']})
<b> Validation: </b> Luhn algorithm
<b> Generation Time: </b> " . round($generationTime, 2) . "ms

<b> Prefix Information: </b>
<b> Country: </b> {$prefixInfo['country']}
<b> Organization: </b> {$prefixInfo['organization']}
<b> Type: </b> {$prefixInfo['type']}

<b> Actions: </b>
<b> Validate another IMEI: /validate </b>
<b> Generate with prefix: /single </b>
<b> Generate dual IMEI: /dual </b>
<b> Generate multiple: /multi </b>

<b> Note: </b> This IMEI is generated for testing purposes only.
        ";
    }
    
    private function getPrefixInfo(string $prefix): array
    {
        $prefixes = [
            '35' => ['country' => 'Reserved', 'organization' => 'Telia', 'type' => 'Mobile'],
            '86' => ['country' => 'China', 'organization' => 'China Mobile', 'type' => 'Mobile'],
            '89' => ['country' => 'Reserved', 'organization' => 'Various', 'type' => 'Mobile'],
            '45' => ['country' => 'Denmark', 'organization' => 'TDC', 'type' => 'Mobile'],
        ];
        
        return $prefixes[$prefix] ?? ['country' => 'Unknown', 'organization' => 'Unknown', 'type' => 'Mobile'];
    }
    
    private function getIMEIKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                ['text' => 'Generate Another', 'callback_data' => 'imei:random'],
                ['text' => 'Generate with Prefix', 'callback_data' => 'imei:single'],
            ],
            [
                ['text' => 'Generate Dual IMEI', 'callback_data' => 'imei:dual'],
                ['text' => 'Generate Multiple', 'callback_data' => 'imei:multi'],
            ],
            [
                ['text' => 'Validate IMEI', 'callback_data' => 'imei:validate'],
                ['text' => 'View History', 'callback_data' => 'imei:history'],
            ],
        ]);
    }
    
    private function checkUserLimit(User $user): bool
    {
        $isPremium = $user->getRole() === 'premium';
        $isAdmin = $user->getRole() === 'admin';
        
        if ($isPremium || $isAdmin) {
            return true;
        }
        
        // Check daily limit for free users
        $today = date('Y-m-d');
        $cacheKey = "imei_daily_limit_{$user->getId()}_{$today}";
        $count = $this->cache->get($cacheKey, 0);
        
        if ($count >= $user->getDailyImeiLimit()) {
            return false;
        }
        
        $this->cache->set($cacheKey, $count + 1, 86400); // 24 hours
        
        return true;
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
