<?php

declare(strict_types=1);

namespace IMEIBot\Core;

use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;
use IMEIBot\Models\User;
use IMEIBot\Core\Logger;
use IMEIBot\Core\Cache;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardRemove;

final class TelegramBot
{
    private BotApi $bot;
    private IMEIGenerator $imeiGenerator;
    private FileManager $fileManager;
    private SearchService $searchService;
    private Logger $logger;
    private Cache $cache;
    private array $config;
    private array $commands = [];
    
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
        
        $this->initializeBot();
        $this->loadCommands();
    }
    
    private function initializeBot(): void
    {
        $this->bot = new BotApi($this->config['bot_token']);
        
        $this->logger->info('Telegram bot initialized', [
            'webhook_url' => $this->config['webhook_url'] ?? null,
        ]);
    }
    
    private function loadCommands(): void
    {
        $this->commands = $this->config['commands'] ?? [];
        
        $this->logger->info('Commands loaded', [
            'count' => count($this->commands),
        ]);
    }
    
    public function handleWebhook(string $input): void
    {
        try {
            $update = json_decode($input, true);
            
            if (!$update) {
                $this->logger->error('Invalid webhook input', ['input' => $input]);
                return;
            }
            
            $update = Update::fromResponse($update);
            $this->processUpdate($update);
            
        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'input' => $input,
            ]);
        }
    }
    
    public function getUpdates(): void
    {
        try {
            $updates = $this->bot->getUpdates();
            
            foreach ($updates as $update) {
                $this->processUpdate($update);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Get updates failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function processUpdate(Update $update): void
    {
        $message = $update->getMessage();
        $callbackQuery = $update->getCallbackQuery();
        $inlineQuery = $update->getInlineQuery();
        
        if ($message) {
            $this->processMessage($message);
        } elseif ($callbackQuery) {
            $this->processCallbackQuery($callbackQuery);
        } elseif ($inlineQuery) {
            $this->processInlineQuery($inlineQuery);
        }
    }
    
    private function processMessage(Message $message): void
    {
        $user = $message->getFrom();
        $chat = $message->getChat();
        $text = $message->getText();
        
        $this->logger->logBotInteraction('telegram', $user->getId(), $text ?? 'no_text', [
            'chat_id' => $chat->getId(),
            'message_id' => $message->getMessageId(),
        ]);
        
        // Handle commands
        if ($text && str_starts_with($text, '/')) {
            $this->handleCommand($message);
            return;
        }
        
        // Handle regular messages
        $this->handleRegularMessage($message);
    }
    
    private function handleCommand(Message $message): void
    {
        $text = $message->getText();
        $parts = explode(' ', $text);
        $command = $parts[0];
        $args = array_slice($parts, 1);
        
        $user = $this->getOrCreateUser($message->getFrom());
        
        // Check rate limiting
        if (!$this->checkRateLimit($user->getId(), 'command')) {
            $this->sendMessage($message->getChat()->getId(), 'Rate limit exceeded. Please try again later.');
            return;
        }
        
        // Find and execute command
        foreach ($this->commands as $category => $commandList) {
            foreach ($commandList as $commandConfig) {
                if ($command === '/' . $commandConfig['command']) {
                    $this->executeCommand($commandConfig, $message, $user, $args);
                    return;
                }
            }
        }
        
        // Command not found
        $this->sendMessage($message->getChat()->getId(), 'Command not found. Use /help to see available commands.');
    }
    
    private function executeCommand(array $commandConfig, Message $message, User $user, array $args): void
    {
        $handlerClass = $commandConfig['handler'];
        
        if (!class_exists($handlerClass)) {
            $this->logger->error('Command handler class not found', [
                'handler' => $handlerClass,
                'command' => $commandConfig['command'],
            ]);
            
            $this->sendMessage($message->getChat()->getId(), 'Command not available.');
            return;
        }
        
        try {
            $handler = new $handlerClass(
                $this->bot,
                $this->imeiGenerator,
                $this->fileManager,
                $this->searchService,
                $this->logger,
                $this->cache
            );
            
            // Check admin-only commands
            if ($commandConfig['admin_only'] ?? false && $user->getRole() !== 'admin') {
                $this->sendMessage($message->getChat()->getId(), 'This command is only available to administrators.');
                return;
            }
            
            // Check premium-only commands
            if ($commandConfig['premium_only'] ?? false && $user->getRole() !== 'premium') {
                $this->sendMessage($message->getChat()->getId(), 'This command is only available to premium users.');
                return;
            }
            
            $handler->handle($message, $user, $args);
            
        } catch (\Exception $e) {
            $this->logger->error('Command execution failed', [
                'command' => $commandConfig['command'],
                'handler' => $handlerClass,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($message->getChat()->getId(), 'An error occurred while executing the command.');
        }
    }
    
    private function handleRegularMessage(Message $message): void
    {
        // Handle regular text messages, photos, documents, etc.
        $chatId = $message->getChat()->getId();
        
        // For now, just send a help message
        $this->sendMessage($chatId, 'Please use a command. Type /help to see available commands.');
    }
    
    private function processCallbackQuery($callbackQuery): void
    {
        $data = $callbackQuery->getData();
        $user = $callbackQuery->getFrom();
        $message = $callbackQuery->getMessage();
        
        $this->logger->logBotInteraction('telegram', $user->getId(), 'callback_query', [
            'callback_data' => $data,
            'message_id' => $message->getMessageId(),
        ]);
        
        // Handle callback data
        $this->handleCallbackData($callbackQuery);
    }
    
    private function handleCallbackData($callbackQuery): void
    {
        $data = $callbackQuery->getData();
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        
        // Parse callback data (format: command:action:parameters)
        $parts = explode(':', $data);
        $command = $parts[0] ?? '';
        $action = $parts[1] ?? '';
        $parameters = array_slice($parts, 2);
        
        switch ($command) {
            case 'imei':
                $this->handleIMEICallback($callbackQuery, $action, $parameters);
                break;
            case 'file':
                $this->handleFileCallback($callbackQuery, $action, $parameters);
                break;
            case 'menu':
                $this->handleMenuCallback($callbackQuery, $action, $parameters);
                break;
            default:
                $this->answerCallbackQuery($callbackQuery->getId(), 'Unknown action');
                break;
        }
    }
    
    private function handleIMEICallback($callbackQuery, string $action, array $parameters): void
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $user = $this->getOrCreateUser($callbackQuery->getFrom());
        
        switch ($action) {
            case 'random':
                $imei = $this->imeiGenerator->generateRandom();
                $this->sendMessage($chatId, "Generated IMEI: <code>{$imei}</code>", 'HTML');
                $this->imeiGenerator->logGeneration($user->getId(), 'telegram', [$imei], 'random');
                break;
            case 'single':
                $this->showPrefixSelection($chatId, 'single');
                break;
            case 'dual':
                $imeis = $this->imeiGenerator->generateDual();
                $this->sendMessage($chatId, "Generated IMEIs:\n<code>{$imeis[0]}</code>\n<code>{$imeis[1]}</code>", 'HTML');
                $this->imeiGenerator->logGeneration($user->getId(), 'telegram', $imeis, 'dual');
                break;
            case 'multi':
                $this->askForQuantity($chatId);
                break;
            default:
                $this->answerCallbackQuery($callbackQuery->getId(), 'Unknown IMEI action');
                break;
        }
        
        $this->answerCallbackQuery($callbackQuery->getId());
    }
    
    private function handleFileCallback($callbackQuery, string $action, array $parameters): void
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        
        switch ($action) {
            case 'firmware':
                $this->showBrandSelection($chatId, 'firmware');
                break;
            case 'engrom':
                $this->showBrandSelection($chatId, 'engrom');
                break;
            case 'qcnnvram':
                $this->showBrandSelection($chatId, 'qcnnvram');
                break;
            case 'solution':
                $this->showBrandSelection($chatId, 'solution');
                break;
            default:
                $this->answerCallbackQuery($callbackQuery->getId(), 'Unknown file action');
                break;
        }
        
        $this->answerCallbackQuery($callbackQuery->getId());
    }
    
    private function handleMenuCallback($callbackQuery, string $action, array $parameters): void
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        
        switch ($action) {
            case 'main':
                $this->showMainMenu($chatId);
                break;
            case 'back':
                $this->showMainMenu($chatId);
                break;
            default:
                $this->answerCallbackQuery($callbackQuery->getId(), 'Unknown menu action');
                break;
        }
        
        $this->answerCallbackQuery($callbackQuery->getId());
    }
    
    private function processInlineQuery($inlineQuery): void
    {
        // Handle inline queries for future implementation
        $this->logger->info('Inline query received', [
            'query_id' => $inlineQuery->getId(),
            'query' => $inlineQuery->getQuery(),
        ]);
    }
    
    private function getOrCreateUser($from): User
    {
        $telegramId = $from->getId();
        
        // Try to find existing user
        $user = User::findByTelegramId($telegramId);
        
        if (!$user) {
            // Create new user
            $user = new User([
                'telegram_id' => $telegramId,
                'username' => $from->getUsername(),
                'first_name' => $from->getFirstName(),
                'last_name' => $from->getLastName(),
                'role' => 'user',
                'status' => 'active',
                'language' => $from->getLanguageCode() ?? 'en',
            ]);
            
            $user->save();
            
            $this->logger->info('New user created', [
                'telegram_id' => $telegramId,
                'username' => $from->getUsername(),
            ]);
        }
        
        return $user;
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
    
    private function answerCallbackQuery(string $callbackQueryId, string $text = null, bool $showAlert = false): void
    {
        try {
            $this->bot->answerCallbackQuery($callbackQueryId, $text, $showAlert);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to answer callback query', [
                'callback_query_id' => $callbackQueryId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showMainMenu(int $chatId): void
    {
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'IMEI Services', 'callback_data' => 'menu:imei'],
                ['text' => 'File Downloads', 'callback_data' => 'menu:files'],
            ],
            [
                ['text' => 'My Profile', 'callback_data' => 'menu:profile'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
        ]);
        
        $this->sendMessage($chatId, 'Main Menu:', null, $keyboard);
    }
    
    private function showPrefixSelection(int $chatId, string $command): void
    {
        $prefixes = $this->imeiGenerator->getSupportedPrefixes();
        $keyboard = [];
        
        foreach ($prefixes as $prefix) {
            $keyboard[] = [['text' => "Prefix {$prefix}", 'callback_data' => "imei:{$command}:{$prefix}"]];
        }
        
        $keyboard[] = [['text' => '« Back', 'callback_data' => 'menu:back']];
        
        $replyMarkup = new InlineKeyboardMarkup($keyboard);
        $this->sendMessage($chatId, 'Select IMEI prefix:', null, $replyMarkup);
    }
    
    private function showBrandSelection(int $chatId, string $category): void
    {
        $brands = $this->fileManager->getPopularBrands();
        $keyboard = [];
        
        foreach ($brands as $brand) {
            $keyboard[] = [['text' => $brand['name'], 'callback_data' => "file:{$category}:brand:{$brand['id']}"]];
        }
        
        $keyboard[] = [['text' => '« Back', 'callback_data' => 'menu:back']];
        
        $replyMarkup = new InlineKeyboardMarkup($keyboard);
        $this->sendMessage($chatId, 'Select brand:', null, $replyMarkup);
    }
    
    private function askForQuantity(int $chatId): void
    {
        $this->sendMessage($chatId, 'Please enter the quantity (1-50):');
    }
    
    private function checkRateLimit(int $userId, string $action): bool
    {
        $key = "rate_limit:telegram:{$userId}:{$action}";
        $current = $this->cache->get($key, 0);
        $limit = $this->config['rate_limit']['requests_per_minute'] ?? 30;
        
        if ($current >= $limit) {
            return false;
        }
        
        $this->cache->increment($key);
        $this->cache->expire($key, 60); // 1 minute
        
        return true;
    }
    
    public function setWebhook(): bool
    {
        try {
            $webhookUrl = $this->config['webhook_url'];
            
            if ($webhookUrl) {
                $this->bot->setWebhook($webhookUrl);
                
                $this->logger->info('Webhook set', [
                    'url' => $webhookUrl,
                ]);
                
                return true;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to set webhook', [
                'error' => $e->getMessage(),
                'url' => $webhookUrl ?? null,
            ]);
        }
        
        return false;
    }
    
    public function getWebhookInfo(): array
    {
        try {
            $info = $this->bot->getWebhookInfo();
            
            return [
                'url' => $info->getUrl(),
                'has_custom_certificate' => $info->getHasCustomCertificate(),
                'pending_update_count' => $info->getPendingUpdateCount(),
                'last_error_date' => $info->getLastErrorDate(),
                'last_error_message' => $info->getLastErrorMessage(),
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get webhook info', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    public function getBotInfo(): array
    {
        try {
            $bot = $this->bot->getMe();
            
            return [
                'id' => $bot->getId(),
                'username' => $bot->getUsername(),
                'first_name' => $bot->getFirstName(),
                'is_bot' => $bot->isBot(),
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get bot info', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}
