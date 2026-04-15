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

class ValidateIMEICommand
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
            // Check if user provided IMEI as argument
            $imei = $args[0] ?? null;
            
            if ($imei) {
                // Validate provided IMEI
                $this->validateIMEI($chatId, $user, $imei);
            } else {
                // Ask for IMEI
                $this->askForIMEI($chatId, $user);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle validate IMEI command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to process command. Please try again.');
        }
    }
    
    private function askForIMEI(int $chatId, User $user): void
    {
        $message = "
<b> IMEI Validation </b>

<b> Please provide the IMEI number you want to validate: </b>

<b> Usage: </b>
<b> /validate [IMEI] </b>

<b> Example: </b>
<b> /validate 351234567890123 </b>

<b> IMEI Format: </b>
<b> Must be exactly 15 digits </b>
<b> Can start with prefixes: 35, 86, 89, 45 </b>
<b> Validated using Luhn algorithm </b>

<b> Type the IMEI number or use the command format: </b>
<b> /validate [15-digit IMEI] </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML');
            
            $this->logger->info('IMEI validation request shown', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show IMEI validation request', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function validateIMEI(int $chatId, User $user, string $imei): void
    {
        try {
            $startTime = microtime(true);
            $validation = $this->imeiGenerator->validate($imei);
            $validationTime = (microtime(true) - $startTime) * 1000;
            
            // Get prefix information
            $prefixInfo = $this->getPrefixInfo($validation['prefix']);
            
            // Check if IMEI is in cache
            $isCached = $this->imeiGenerator->isCached($imei);
            
            // Cache validation result
            $this->imeiGenerator->cacheValidation($imei, $validation['is_valid']);
            
            // Send response
            $responseMessage = $this->formatValidationResponse($imei, $validation, $prefixInfo, $validationTime, $isCached);
            $keyboard = $this->getValidationKeyboard();
            
            $this->bot->sendMessage($chatId, $responseMessage, 'HTML', false, null, $keyboard);
            
            $this->logger->info('IMEI validated', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'imei' => $imei,
                'is_valid' => $validation['is_valid'],
                'prefix' => $validation['prefix'],
                'validation_time_ms' => $validationTime,
                'is_cached' => $isCached,
            ]);
            
        } catch (\InvalidArgumentException $e) {
            $this->sendMessage($chatId, "Invalid IMEI format: {$e->getMessage()}\nPlease provide a 15-digit IMEI number.");
        } catch (\Exception $e) {
            $this->logger->error('Failed to validate IMEI', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'imei' => $imei,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to validate IMEI. Please try again.');
        }
    }
    
    private function formatValidationResponse(string $imei, array $validation, array $prefixInfo, float $validationTime, bool $isCached): string
    {
        $status = $validation['is_valid'] ? 'Valid' : 'Invalid';
        $statusEmoji = $validation['is_valid'] ? 'Valid' : 'Invalid';
        $statusColor = $validation['is_valid'] ? 'Valid' : 'Invalid';
        
        $cacheInfo = $isCached ? ' (from cache)' : ' (fresh validation)';
        
        return "
<b> IMEI Validation Results </b>

<b> IMEI: </b> <code>{$imei}</code>
<b> Status: </b> {$statusEmoji} {$status}
<b> Prefix: </b> {$validation['prefix']} ({$prefixInfo['country']})
<b> Validation Method: </b> {$validation['validation_method']}
<b> Validation Time: </b> " . round($validationTime, 2) . "ms{$cacheInfo}

<b> Prefix Information: </b>
<b> Country: </b> {$prefixInfo['country']}
<b> Organization: </b> {$prefixInfo['organization']}
<b> Type: </b> {$prefixInfo['type']}

<b> Validation Details: </b>
<b> Length: </b> " . strlen($imei) . " digits
<b> Algorithm: </b> Luhn checksum
<b> Check Digit: </b> " . substr($imei, -1) . "
<b> Base Number: </b> " . substr($imei, 0, -1) . "

<b> Actions: </b>
<b> Validate Another: /validate [IMEI] </b>
<b> Generate New IMEI: /random </b>
<b> Generate with Prefix: /single [prefix] </b>
<b> View History: /history </b>

<b> Note: </b> This validation is for informational purposes only.
        ";
    }
    
    private function getValidationKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                ['text' => 'Validate Another IMEI', 'callback_data' => 'imei:validate'],
                ['text' => 'Generate New IMEI', 'callback_data' => 'imei:random'],
            ],
            [
                ['text' => 'Generate Single IMEI', 'callback_data' => 'imei:single'],
                ['text' => 'Generate Dual IMEI', 'callback_data' => 'imei:dual'],
            ],
            [
                ['text' => 'Generate Multiple', 'callback_data' => 'imei:multi'],
                ['text' => 'View History', 'callback_data' => 'imei:history'],
            ],
        ]);
    }
    
    private function getPrefixInfo(string $prefix): array
    {
        $prefixes = [
            '35' => [
                'country' => 'Reserved',
                'organization' => 'Telia',
                'type' => 'Mobile Equipment',
                'description' => 'Reserved for future use by Telia'
            ],
            '86' => [
                'country' => 'China',
                'organization' => 'China Mobile',
                'type' => 'Mobile Equipment',
                'description' => 'China Mobile network equipment'
            ],
            '89' => [
                'country' => 'Reserved',
                'organization' => 'Various',
                'type' => 'Mobile Equipment',
                'description' => 'Reserved for future use'
            ],
            '45' => [
                'country' => 'Denmark',
                'organization' => 'TDC',
                'type' => 'Mobile Equipment',
                'description' => 'TDC Denmark network equipment'
            ],
        ];
        
        return $prefixes[$prefix] ?? [
            'country' => 'Unknown',
            'organization' => 'Unknown',
            'type' => 'Mobile Equipment',
            'description' => 'Unknown prefix'
        ];
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
