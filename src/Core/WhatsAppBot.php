<?php

declare(strict_types=1);

namespace IMEIBot\Core;

use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;
use IMEIBot\Models\User;
use IMEIBot\Core\Logger;
use IMEIBot\Core\Cache;

final class WhatsAppBot
{
    private array $config;
    private IMEIGenerator $imeiGenerator;
    private FileManager $fileManager;
    private SearchService $searchService;
    private Logger $logger;
    private Cache $cache;
    private string $accessToken;
    private string $phoneNumberId;
    private string $apiVersion;
    
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
        
        $this->accessToken = $config['access_token'];
        $this->phoneNumberId = $config['phone_number_id'];
        $this->apiVersion = $config['api_version'] ?? 'v18.0';
        
        $this->logger->info('WhatsApp bot initialized', [
            'phone_number_id' => $this->phoneNumberId,
            'api_version' => $this->apiVersion,
        ]);
    }
    
    public function handleWebhook(string $input): void
    {
        try {
            $data = json_decode($input, true);
            
            if (!$data) {
                $this->logger->error('Invalid webhook input', ['input' => $input]);
                return;
            }
            
            // Handle different webhook events
            if (isset($data['object']) && $data['object'] === 'whatsapp_business_account') {
                $this->processWebhookEvent($data);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('WhatsApp webhook processing failed', [
                'error' => $e->getMessage(),
                'input' => $input,
            ]);
        }
    }
    
    private function processWebhookEvent(array $data): void
    {
        $entry = $data['entry'][0] ?? null;
        
        if (!$entry) {
            return;
        }
        
        $changes = $entry['changes'] ?? [];
        
        foreach ($changes as $change) {
            $field = $change['field'] ?? '';
            
            switch ($field) {
                case 'messages':
                    $this->processMessage($change['value']['messages'][0] ?? []);
                    break;
                case 'message_reactions':
                    $this->processMessageReaction($change['value']['message_reactions'][0] ?? []);
                    break;
                case 'message_status':
                    $this->processMessageStatus($change['value']['statuses'][0] ?? []);
                    break;
                default:
                    $this->logger->info('Unhandled webhook field', ['field' => $field]);
                    break;
            }
        }
    }
    
    private function processMessage(array $message): void
    {
        $from = $message['from'] ?? '';
        $messageType = $message['type'] ?? '';
        $timestamp = $message['timestamp'] ?? '';
        
        $user = $this->getOrCreateUser($from);
        
        $this->logger->logBotInteraction('whatsapp', $user->getId(), $messageType, [
            'from' => $from,
            'timestamp' => $timestamp,
        ]);
        
        switch ($messageType) {
            case 'text':
                $this->processTextMessage($message, $user);
                break;
            case 'interactive':
                $this->processInteractiveMessage($message, $user);
                break;
            case 'button':
                $this->processButtonMessage($message, $user);
                break;
            case 'location':
                $this->processLocationMessage($message, $user);
                break;
            case 'image':
            case 'document':
            case 'audio':
            case 'video':
                $this->processMediaMessage($message, $user);
                break;
            default:
                $this->logger->info('Unhandled message type', ['type' => $messageType]);
                break;
        }
    }
    
    private function processTextMessage(array $message, User $user): void
    {
        $text = $message['text']['body'] ?? '';
        $from = $message['from'];
        
        // Check if it's a command
        if (str_starts_with($text, '/')) {
            $this->handleCommand($text, $from, $user);
        } else {
            $this->handleRegularMessage($text, $from, $user);
        }
    }
    
    private function processInteractiveMessage(array $message, User $user): void
    {
        $interactive = $message['interactive'] ?? [];
        $type = $interactive['type'] ?? '';
        $from = $message['from'];
        
        switch ($type) {
            case 'button_reply':
                $this->handleButtonReply($interactive['button_reply'], $from, $user);
                break;
            case 'list_reply':
                $this->handleListReply($interactive['list_reply'], $from, $user);
                break;
            default:
                $this->logger->info('Unhandled interactive type', ['type' => $type]);
                break;
        }
    }
    
    private function processButtonMessage(array $message, User $user): void
    {
        $button = $message['button'] ?? [];
        $text = $button['text'] ?? '';
        $from = $message['from'];
        
        $this->handleButtonReply(['text' => $text], $from, $user);
    }
    
    private function processLocationMessage(array $message, User $user): void
    {
        $location = $message['location'] ?? [];
        $latitude = $location['latitude'] ?? 0;
        $longitude = $location['longitude'] ?? 0;
        $from = $message['from'];
        
        $this->logger->info('Location received', [
            'user_id' => $user->getId(),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        
        // For now, send a thank you message
        $this->sendMessage($from, 'Thank you for sharing your location!');
    }
    
    private function processMediaMessage(array $message, User $user): void
    {
        $messageType = $message['type'] ?? '';
        $mediaId = $message[$messageType]['id'] ?? '';
        $from = $message['from'];
        
        $this->logger->info('Media received', [
            'user_id' => $user->getId(),
            'type' => $messageType,
            'media_id' => $mediaId,
        ]);
        
        // For now, send a thank you message
        $this->sendMessage($from, "Thank you for sharing the {$messageType}!");
    }
    
    private function handleCommand(string $command, string $from, User $user): void
    {
        $parts = explode(' ', $command);
        $commandName = $parts[0];
        $args = array_slice($parts, 1);
        
        // Check rate limiting
        if (!$this->checkRateLimit($user->getId(), 'command')) {
            $this->sendMessage($from, 'Rate limit exceeded. Please try again later.');
            return;
        }
        
        // Handle WhatsApp-specific commands
        switch ($commandName) {
            case '/start':
                $this->sendWelcomeMessage($from, $user);
                break;
            case '/help':
                $this->sendHelpMessage($from, $user);
                break;
            case '/random':
                $this->handleRandomIMEI($from, $user);
                break;
            case '/single':
                $this->handleSingleIMEI($from, $user, $args);
                break;
            case '/dual':
                $this->handleDualIMEI($from, $user, $args);
                break;
            case '/multi':
                $this->handleMultiIMEI($from, $user, $args);
                break;
            case '/validate':
                $this->handleValidateIMEI($from, $user, $args);
                break;
            case '/firmware':
                $this->handleFirmwareSearch($from, $user);
                break;
            case '/menu':
                $this->sendMainMenu($from, $user);
                break;
            default:
                $this->sendMessage($from, 'Unknown command. Type /help for available commands.');
                break;
        }
    }
    
    private function handleRegularMessage(string $text, string $from, User $user): void
    {
        // For now, send a help message
        $this->sendMessage($from, 'Please use a command. Type /help to see available commands.');
    }
    
    private function handleButtonReply(array $buttonReply, string $from, User $user): void
    {
        $buttonId = $buttonReply['id'] ?? '';
        $buttonText = $buttonReply['title'] ?? '';
        
        $this->logger->info('Button reply received', [
            'user_id' => $user->getId(),
            'button_id' => $buttonId,
            'button_text' => $buttonText,
        ]);
        
        // Handle button responses
        if (str_starts_with($buttonId, 'imei_')) {
            $this->handleIMEIButton($buttonId, $from, $user);
        } elseif (str_starts_with($buttonId, 'file_')) {
            $this->handleFileButton($buttonId, $from, $user);
        } elseif (str_starts_with($buttonId, 'menu_')) {
            $this->handleMenuButton($buttonId, $from, $user);
        }
    }
    
    private function handleListReply(array $listReply, string $from, User $user): void
    {
        $listId = $listReply['id'] ?? '';
        $listTitle = $listReply['title'] ?? '';
        
        $this->logger->info('List reply received', [
            'user_id' => $user->getId(),
            'list_id' => $listId,
            'list_title' => $listTitle,
        ]);
        
        // Handle list responses
        if (str_starts_with($listId, 'imei_')) {
            $this->handleIMEIList($listId, $from, $user);
        } elseif (str_starts_with($listId, 'file_')) {
            $this->handleFileList($listId, $from, $user);
        }
    }
    
    private function sendWelcomeMessage(string $to, User $user): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        
        $message = "
Welcome to IMEI Bot Platform! {$firstName}

I'm your advanced IMEI generation and file download assistant.

What I can do for you:
Generate IMEI numbers with validation
Download firmware, QCN, NVRAM, and solution files
Search and browse files by brand and model
Provide analytics and statistics

Quick Start:
/random - Generate a random IMEI
/firmware - Browse firmware files
/help - See all commands

Type /menu to see all available options.
        ";
        
        $keyboard = $this->getWelcomeKeyboard();
        
        $this->sendMessage($to, $message, null, $keyboard);
    }
    
    private function sendHelpMessage(string $to, User $user): void
    {
        $message = "
IMEI Bot Platform - Help

IMEI Generation Commands:
/random - Generate a random IMEI number
/single - Generate IMEI with prefix selection
/dual - Generate dual IMEI pair
/multi - Generate multiple IMEIs
/validate - Validate an existing IMEI

File Download Commands:
/firmware - Browse firmware files
/engrom - Browse engineering ROM files
/qcnnvram - Browse QCN and NVRAM files
/solution - Browse solution files

User Management:
/profile - View your profile and statistics
/settings - Personal preferences
/feedback - Send feedback to admin

Navigation:
/start - Welcome message
/menu - Main menu
/back - Go back

Tips:
Use interactive buttons for easier navigation
Check file descriptions before downloading
Validate IMEIs before using them
Contact support for any issues
        ";
        
        $keyboard = $this->getHelpKeyboard();
        
        $this->sendMessage($to, $message, null, $keyboard);
    }
    
    private function sendMainMenu(string $to, User $user): void
    {
        $message = "Main Menu - Choose an option below:";
        
        $keyboard = $this->getMainMenuKeyboard();
        
        $this->sendMessage($to, $message, null, $keyboard);
    }
    
    private function handleRandomIMEI(string $to, User $user): void
    {
        try {
            // Check user rate limit
            if (!$this->checkUserLimit($user)) {
                $this->sendMessage($to, "You've reached your daily IMEI generation limit.\nUpgrade to premium for unlimited access.");
                return;
            }
            
            $imei = $this->imeiGenerator->generateRandom();
            
            // Log the generation
            $this->imeiGenerator->logGeneration($user->getId(), 'whatsapp', [$imei], 'random');
            
            $message = "Generated IMEI: {$imei}\n\nThis IMEI is generated for testing purposes only.";
            
            $keyboard = $this->getIMEIKeyboard();
            
            $this->sendMessage($to, $message, null, $keyboard);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate random IMEI', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($to, 'Failed to generate IMEI. Please try again later.');
        }
    }
    
    private function handleSingleIMEI(string $to, User $user, array $args): void
    {
        $prefix = $args[0] ?? null;
        
        if ($prefix) {
            $this->generateIMEIWithPrefix($to, $user, $prefix);
        } else {
            $this->showPrefixSelection($to, $user, 'single');
        }
    }
    
    private function handleDualIMEI(string $to, User $user, array $args): void
    {
        $prefix = $args[0] ?? null;
        
        if ($prefix) {
            $this->generateDualIMEIWithPrefix($to, $user, $prefix);
        } else {
            $this->showPrefixSelection($to, $user, 'dual');
        }
    }
    
    private function handleMultiIMEI(string $to, User $user, array $args): void
    {
        $quantity = $args[0] ?? null;
        
        if ($quantity && is_numeric($quantity)) {
            $quantity = (int) $quantity;
            
            if ($quantity < 1 || $quantity > 50) {
                $this->sendMessage($to, "Invalid quantity. Please provide a number between 1 and 50.\nUsage: /multi [quantity]");
                return;
            }
            
            $prefix = $args[1] ?? null;
            
            if ($prefix) {
                $this->generateMultipleIMEIWithPrefix($to, $user, $quantity, $prefix);
            } else {
                $this->showPrefixSelection($to, $user, 'multi', $quantity);
            }
        } else {
            $this->sendMessage($to, "Please provide the quantity:\nUsage: /multi [quantity] [prefix]\nExample: /multi 5 86");
        }
    }
    
    private function handleValidateIMEI(string $to, User $user, array $args): void
    {
        $imei = $args[0] ?? null;
        
        if ($imei) {
            $this->validateIMEI($to, $user, $imei);
        } else {
            $this->sendMessage($to, "Please provide the IMEI to validate:\nUsage: /validate [15-digit IMEI]\nExample: /validate 351234567890123");
        }
    }
    
    private function handleFirmwareSearch(string $to, User $user): void
    {
        $this->showBrandSelection($to, $user, 'firmware');
    }
    
    private function getOrCreateUser(string $phoneNumber): User
    {
        // For WhatsApp, we use phone number as identifier
        $user = User::findByWhatsAppPhone($phoneNumber);
        
        if (!$user) {
            // Create new user
            $user = new User([
                'whatsapp_phone' => $phoneNumber,
                'role' => 'user',
                'status' => 'active',
                'language' => 'en',
            ]);
            
            $user->save();
            
            $this->logger->info('New WhatsApp user created', [
                'phone_number' => $phoneNumber,
            ]);
        }
        
        return $user;
    }
    
    private function sendMessage(string $to, string $text, string $parseMode = null, $keyboard = null): void
    {
        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
            
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $text,
                ],
            ];
            
            if ($keyboard) {
                $payload['text'] = [
                    'body' => $text,
                ];
                $payload['interactive'] = $this->formatKeyboard($keyboard);
            }
            
            $this->makeApiCall('POST', $url, $payload);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function makeApiCall(string $method, string $url, array $data = []): array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("API call failed: {$error}");
        }
        
        if ($httpCode >= 400) {
            $responseData = json_decode($response, true);
            $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
            throw new \RuntimeException("API error ({$httpCode}): {$errorMessage}");
        }
        
        return json_decode($response, true);
    }
    
    private function checkRateLimit(int $userId, string $action): bool
    {
        $key = "rate_limit:whatsapp:{$userId}:{$action}";
        $current = $this->cache->get($key, 0);
        $limit = $this->config['rate_limits']['messages_per_minute'] ?? 30;
        
        if ($current >= $limit) {
            return false;
        }
        
        $this->cache->increment($key);
        $this->cache->expire($key, 60); // 1 minute
        
        return true;
    }
    
    private function checkUserLimit(User $user, int $count = 1): bool
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
    
    // Placeholder methods for keyboard formatting and other functionality
    private function getWelcomeKeyboard(): array
    {
        return [
            'type' => 'button',
            'body' => 'Choose an option:',
            'buttons' => [
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'imei_random', 'title' => 'Generate IMEI'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'file_firmware', 'title' => 'Browse Files'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'menu_main', 'title' => 'Main Menu'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'help', 'title' => 'Help'],
                ],
            ],
        ];
    }
    
    private function getHelpKeyboard(): array
    {
        return [
            'type' => 'button',
            'body' => 'Choose an option:',
            'buttons' => [
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'imei_services', 'title' => 'IMEI Services'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'file_downloads', 'title' => 'File Downloads'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'menu_profile', 'title' => 'My Profile'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'menu_main', 'title' => 'Main Menu'],
                ],
            ],
        ];
    }
    
    private function getMainMenuKeyboard(): array
    {
        return [
            'type' => 'button',
            'body' => 'Main Menu:',
            'buttons' => [
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'imei_services', 'title' => 'IMEI Services'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'file_downloads', 'title' => 'File Downloads'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'menu_profile', 'title' => 'My Profile'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'help', 'title' => 'Help'],
                ],
            ],
        ];
    }
    
    private function getIMEIKeyboard(): array
    {
        return [
            'type' => 'button',
            'body' => 'Choose an option:',
            'buttons' => [
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'imei_random', 'title' => 'Generate Another'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'imei_single', 'title' => 'Generate Single'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'imei_dual', 'title' => 'Generate Dual'],
                ],
                [
                    'type' => 'reply',
                    'reply' => ['id' => 'menu_main', 'title' => 'Main Menu'],
                ],
            ],
        ];
    }
    
    private function formatKeyboard(array $keyboard): array
    {
        // Convert internal keyboard format to WhatsApp format
        return $keyboard;
    }
    
    // Placeholder methods for IMEI and file handling
    private function showPrefixSelection(string $to, User $user, string $type, int $quantity = 1): void
    {
        $prefixes = $this->imeiGenerator->getSupportedPrefixes();
        $buttons = [];
        
        foreach ($prefixes as $prefix) {
            $buttons[] = [
                'type' => 'reply',
                'reply' => ['id' => "imei_{$type}_{$prefix}", 'title' => "Prefix {$prefix}"],
            ];
        }
        
        $buttons[] = [
            'type' => 'reply',
            'reply' => ['id' => 'menu_back', 'title' => '« Back'],
        ];
        
        $keyboard = [
            'type' => 'button',
            'body' => 'Select IMEI prefix:',
            'buttons' => $buttons,
        ];
        
        $this->sendMessage($to, '', null, $keyboard);
    }
    
    private function showBrandSelection(string $to, User $user, string $category): void
    {
        $brands = $this->fileManager->getPopularBrands();
        $buttons = [];
        
        foreach ($brands as $brand) {
            $buttons[] = [
                'type' => 'reply',
                'reply' => ['id' => "file_{$category}_{$brand['id']}", 'title' => $brand['name']],
            ];
        }
        
        $buttons[] = [
            'type' => 'reply',
            'reply' => ['id' => 'menu_back', 'title' => '« Back'],
        ];
        
        $keyboard = [
            'type' => 'button',
            'body' => 'Select brand:',
            'buttons' => $buttons,
        ];
        
        $this->sendMessage($to, '', null, $keyboard);
    }
    
    // Placeholder methods for IMEI generation
    private function generateIMEIWithPrefix(string $to, User $user, string $prefix): void
    {
        // Implementation similar to Telegram bot
        $this->sendMessage($to, "Generating IMEI with prefix {$prefix}...");
    }
    
    private function generateDualIMEIWithPrefix(string $to, User $user, string $prefix): void
    {
        // Implementation similar to Telegram bot
        $this->sendMessage($to, "Generating dual IMEI with prefix {$prefix}...");
    }
    
    private function generateMultipleIMEIWithPrefix(string $to, User $user, int $quantity, string $prefix): void
    {
        // Implementation similar to Telegram bot
        $this->sendMessage($to, "Generating {$quantity} IMEIs with prefix {$prefix}...");
    }
    
    private function validateIMEI(string $to, User $user, string $imei): void
    {
        // Implementation similar to Telegram bot
        $this->sendMessage($to, "Validating IMEI {$imei}...");
    }
    
    // Placeholder methods for button handling
    private function handleIMEIButton(string $buttonId, string $from, User $user): void
    {
        // Handle IMEI-related button clicks
        $this->sendMessage($from, "IMEI button clicked: {$buttonId}");
    }
    
    private function handleFileButton(string $buttonId, string $from, User $user): void
    {
        // Handle file-related button clicks
        $this->sendMessage($from, "File button clicked: {$buttonId}");
    }
    
    private function handleMenuButton(string $buttonId, string $from, User $user): void
    {
        // Handle menu-related button clicks
        $this->sendMessage($from, "Menu button clicked: {$buttonId}");
    }
    
    private function handleIMEIList(string $listId, string $from, User $user): void
    {
        // Handle IMEI-related list selections
        $this->sendMessage($from, "IMEI list selected: {$listId}");
    }
    
    private function handleFileList(string $listId, string $from, User $user): void
    {
        // Handle file-related list selections
        $this->sendMessage($from, "File list selected: {$listId}");
    }
    
    private function processMessageReaction(array $reaction): void
    {
        // Handle message reactions
        $this->logger->info('Message reaction received', ['reaction' => $reaction]);
    }
    
    private function processMessageStatus(array $status): void
    {
        // Handle message status updates
        $this->logger->info('Message status received', ['status' => $status]);
    }
    
    public function verifyWebhook(string $verifyToken): bool
    {
        return $verifyToken === $this->config['webhook_verify_token'];
    }
}
