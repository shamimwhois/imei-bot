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

class MultiIMEICommand
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
            // Check if user provided quantity and prefix as arguments
            $quantity = $args[0] ?? null;
            $prefix = $args[1] ?? null;
            
            if ($quantity && is_numeric($quantity)) {
                $quantity = (int) $quantity;
                
                if ($quantity < 1 || $quantity > 50) {
                    $this->sendMessage($chatId, "Invalid quantity. Please provide a number between 1 and 50.\nUsage: /multi [quantity] [prefix]");
                    return;
                }
                
                if ($prefix) {
                    // Generate multiple IMEIs with provided prefix
                    $this->generateMultipleIMEIWithPrefix($chatId, $user, $quantity, $prefix);
                } else {
                    // Show prefix selection
                    $this->showPrefixSelection($chatId, $user, $quantity);
                }
            } else {
                // Ask for quantity
                $this->askForQuantity($chatId, $user);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle multi IMEI command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to process command. Please try again.');
        }
    }
    
    private function askForQuantity(int $chatId, User $user): void
    {
        $message = "
<b> Multiple IMEI Generation </b>

<b> How many IMEIs would you like to generate? </b>

<b> Please enter a number between 1 and 50: </b>

<b> Examples: </b>
<b> /multi 5 </b> - Generate 5 IMEIs
<b> /multi 10 86 </b> - Generate 10 IMEIs with prefix 86
<b> /multi 20 35 </b> - Generate 20 IMEIs with prefix 35

<b> Limits: </b>
<b> Free users: </b> Up to 10 per day
<b> Premium users: </b> Unlimited
<b> Maximum per request: </b> 50 IMEIs

<b> Type the quantity or use the command format: </b>
<b> /multi [quantity] [prefix] </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML');
            
            $this->logger->info('Multi IMEI quantity request shown', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show quantity request', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showPrefixSelection(int $chatId, User $user, int $quantity): void
    {
        $prefixes = $this->imeiGenerator->getSupportedPrefixes();
        $keyboard = [];
        
        foreach ($prefixes as $prefix) {
            $prefixInfo = $this->getPrefixInfo($prefix);
            $keyboard[] = [
                [
                    'text' => "Prefix {$prefix} ({$prefixInfo['country']})", 
                    'callback_data' => "imei:multi:{$quantity}:{$prefix}"
                ]
            ];
        }
        
        $keyboard[] = [
            ['text' => 'Random Prefix', 'callback_data' => "imei:multi:{$quantity}:random"],
            ['text' => '« Back', 'callback_data' => 'menu:back']
        ];
        
        $replyMarkup = new InlineKeyboardMarkup($keyboard);
        
        $message = "
<b> Select IMEI Prefix for {$quantity} IMEIs </b>

<b> Choose a prefix for all IMEIs: </b>

<b> Available prefixes: </b>
<b> 35 </b> - Reserved (Telia)
<b> 86 </b> - China (China Mobile)
<b> 89 </b> - Reserved (Various)
<b> 45 </b> - Denmark (TDC)

<b> Or select random prefix for automatic selection. </b>

<b> Quantity: </b> {$quantity} IMEIs
<b> This will count as {$quantity} generations from your daily limit. </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $replyMarkup);
            
            $this->logger->info('Multi IMEI prefix selection shown', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'quantity' => $quantity,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show multi IMEI prefix selection', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function generateMultipleIMEIWithPrefix(int $chatId, User $user, int $quantity, string $prefix): void
    {
        // Check user rate limit
        if (!$this->checkUserLimit($user, $quantity)) {
            $this->sendMessage($chatId, 
                "You've reached your daily IMEI generation limit.\n" .
                "Upgrade to premium for unlimited access: /premium"
            );
            return;
        }
        
        try {
            $startTime = microtime(true);
            $imeis = $this->imeiGenerator->generateMultiple($quantity, $prefix);
            $generationTime = (microtime(true) - $startTime) * 1000;
            
            // Log the generation
            $this->imeiGenerator->logGeneration($user->getId(), 'telegram', $imeis, 'multi');
            
            // Send response
            $prefixInfo = $this->getPrefixInfo($prefix);
            $responseMessage = $this->formatMultipleIMEIResponse($imeis, $prefixInfo, $generationTime);
            
            // Send in chunks if too many IMEIs
            if (count($imeis) > 10) {
                $this->sendMultipleIMEIChunks($chatId, $imeis, $prefixInfo, $generationTime);
            } else {
                $keyboard = $this->getIMEIKeyboard();
                $this->bot->sendMessage($chatId, $responseMessage, 'HTML', false, null, $keyboard);
            }
            
            $this->logger->info('Multiple IMEIs generated', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'quantity' => $quantity,
                'prefix' => $prefix,
                'generation_time_ms' => $generationTime,
            ]);
            
        } catch (\InvalidArgumentException $e) {
            $this->sendMessage($chatId, "Invalid prefix: {$prefix}\nSupported prefixes: " . implode(', ', $this->imeiGenerator->getSupportedPrefixes()));
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate multiple IMEIs', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'quantity' => $quantity,
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to generate IMEIs. Please try again.');
        }
    }
    
    private function sendMultipleIMEIChunks(int $chatId, array $imeis, array $prefixInfo, float $generationTime): void
    {
        $total = count($imeis);
        $chunks = array_chunk($imeis, 10);
        
        // Send header message
        $headerMessage = "
<b> Multiple IMEIs Generated Successfully! </b>

<b> Total IMEIs: </b> {$total}
<b> Prefix: </b> {$prefixInfo['country']} ({$prefixInfo['organization']})
<b> Generation Time: </b> " . round($generationTime, 2) . "ms

<b> IMEIs: </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $headerMessage, 'HTML');
            
            // Send IMEIs in chunks
            foreach ($chunks as $index => $chunk) {
                $chunkMessage = "";
                $startNum = $index * 10 + 1;
                $endNum = min(($index + 1) * 10, $total);
                
                foreach ($chunk as $i => $imei) {
                    $chunkMessage .= "<code>{$imei}</code>\n";
                }
                
                $chunkMessage .= "\n<b> IMEIs {$startNum}-{$endNum} of {$total} </b>";
                
                $this->bot->sendMessage($chatId, $chunkMessage, 'HTML');
            }
            
            // Send footer with actions
            $footerMessage = "
<b> Generation Complete! </b>

<b> Summary: </b>
<b> Generated: </b> {$total} IMEIs
<b> Prefix: </b> {$prefixInfo['country']}
<b> All IMEIs validated with Luhn algorithm </b>

<b> Actions: </b>
<b> Generate More: /multi </b>
<b> Generate Single: /single </b>
<b> View History: /history </b>

<b> Note: </b> These IMEIs are generated for testing purposes only.
            ";
            
            $keyboard = $this->getIMEIKeyboard();
            $this->bot->sendMessage($chatId, $footerMessage, 'HTML', false, null, $keyboard);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send multi IMEI chunks', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function formatMultipleIMEIResponse(array $imeis, array $prefixInfo, float $generationTime): string
    {
        $total = count($imeis);
        $imeiList = "";
        
        foreach ($imeis as $imei) {
            $imeiList .= "<code>{$imei}</code>\n";
        }
        
        return "
<b> Multiple IMEIs Generated Successfully! </b>

<b> Total IMEIs: </b> {$total}
<b> Prefix: </b> {$prefixInfo['country']} ({$prefixInfo['organization']})
<b> Generation Time: </b> " . round($generationTime, 2) . "ms

<b> IMEIs: </b>
{$imeiList}

<b> Validation: </b> All IMEIs validated with Luhn algorithm
<b> Prefix Information: </b> {$prefixInfo['country']} - {$prefixInfo['organization']}

<b> Actions: </b>
<b> Generate More: /multi </b>
<b> Generate Single: /single </b>
<b> Generate Dual: /dual </b>
<b> View History: /history </b>

<b> Note: </b> These IMEIs are generated for testing purposes only.
        ";
    }
    
    private function getIMEIKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                ['text' => 'Generate More', 'callback_data' => 'imei:multi'],
                ['text' => 'Generate Single', 'callback_data' => 'imei:single'],
            ],
            [
                ['text' => 'Generate Dual IMEI', 'callback_data' => 'imei:dual'],
                ['text' => 'Random IMEI', 'callback_data' => 'imei:random'],
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
    
    private function checkUserLimit(User $user, int $count): bool
    {
        $isPremium = $user->getRole() === 'premium';
        $isAdmin = $user->getRole() === 'admin';
        
        if ($isPremium || $isAdmin) {
            return true;
        }
        
        // Check daily limit for free users
        $today = date('Y-m-d');
        $cacheKey = "imei_daily_limit_{$user->getId()}_{$today}";
        $currentCount = $this->cache->get($cacheKey, 0);
        
        if (($currentCount + $count) > $user->getDailyImeiLimit()) {
            return false;
        }
        
        $this->cache->set($cacheKey, $currentCount + $count, 86400); // 24 hours
        
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
