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

class SingleIMEICommand
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
            // Check if user provided a prefix as argument
            $prefix = $args[0] ?? null;
            
            if ($prefix) {
                // Generate IMEI with provided prefix
                $this->generateIMEIWithPrefix($chatId, $user, $prefix);
            } else {
                // Show prefix selection
                $this->showPrefixSelection($chatId, $user);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle single IMEI command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to process command. Please try again.');
        }
    }
    
    private function generateIMEIWithPrefix(int $chatId, User $user, string $prefix): void
    {
        // Check user rate limit
        if (!$this->checkUserLimit($user)) {
            $this->sendMessage($chatId, 
                "You've reached your daily IMEI generation limit.\n" .
                "Upgrade to premium for unlimited access: /premium"
            );
            return;
        }
        
        try {
            $startTime = microtime(true);
            $imei = $this->imeiGenerator->generateSingle($prefix);
            $generationTime = (microtime(true) - $startTime) * 1000;
            
            // Log the generation
            $this->imeiGenerator->logGeneration($user->getId(), 'telegram', [$imei], 'single');
            
            // Send response
            $validation = $this->imeiGenerator->validate($imei);
            $prefixInfo = $this->getPrefixInfo($validation['prefix']);
            
            $responseMessage = $this->formatIMEIResponse($imei, $validation, $prefixInfo, $generationTime);
            $keyboard = $this->getIMEIKeyboard();
            
            $this->bot->sendMessage($chatId, $responseMessage, 'HTML', false, null, $keyboard);
            
            $this->logger->info('Single IMEI generated', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'imei' => $imei,
                'prefix' => $prefix,
                'generation_time_ms' => $generationTime,
            ]);
            
        } catch (\InvalidArgumentException $e) {
            $this->sendMessage($chatId, "Invalid prefix: {$prefix}\nSupported prefixes: " . implode(', ', $this->imeiGenerator->getSupportedPrefixes()));
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate single IMEI', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to generate IMEI. Please try again.');
        }
    }
    
    private function showPrefixSelection(int $chatId, User $user): void
    {
        $prefixes = $this->imeiGenerator->getSupportedPrefixes();
        $keyboard = [];
        
        foreach ($prefixes as $prefix) {
            $prefixInfo = $this->getPrefixInfo($prefix);
            $keyboard[] = [
                [
                    'text' => "Prefix {$prefix} ({$prefixInfo['country']})", 
                    'callback_data' => "imei:single:{$prefix}"
                ]
            ];
        }
        
        $keyboard[] = [
            ['text' => 'Random Prefix', 'callback_data' => 'imei:random'],
            ['text' => '« Back', 'callback_data' => 'menu:back']
        ];
        
        $replyMarkup = new InlineKeyboardMarkup($keyboard);
        
        $message = "
<b> Select IMEI Prefix </b>

<b> Choose a prefix for your IMEI: </b>

<b> Available prefixes: </b>
<b> 35 </b> - Reserved (Telia)
<b> 86 </b> - China (China Mobile)
<b> 89 </b> - Reserved (Various)
<b> 45 </b> - Denmark (TDC)

<b> Or select random prefix for automatic selection. </b>

<b> Note: </b> Different prefixes may be associated with different regions or carriers.
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $replyMarkup);
            
            $this->logger->info('Prefix selection shown', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show prefix selection', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
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
<b> Generate Another: /single </b>
<b> Different Prefix: /single [prefix] </b>
<b> Generate Dual: /dual </b>
<b> Generate Multiple: /multi </b>

<b> Note: </b> This IMEI is generated for testing purposes only.
        ";
    }
    
    private function getIMEIKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                ['text' => 'Generate Another', 'callback_data' => 'imei:single'],
                ['text' => 'Random IMEI', 'callback_data' => 'imei:random'],
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
