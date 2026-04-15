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

class BackCommand
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
            // For now, always go back to main menu
            // In a more complex implementation, we could track navigation history
            $this->showMainMenu($chatId, $user);
            
            $this->logger->info('Back command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle back command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to go back. Please try again.');
        }
    }
    
    private function showMainMenu(int $chatId, User $user): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        
        $message = "
<b> IMEI Bot Platform - Main Menu </b>

<b> Welcome back, {$firstName}! </b>

<b> Choose an option below: </b>
        ";
        
        $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
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
