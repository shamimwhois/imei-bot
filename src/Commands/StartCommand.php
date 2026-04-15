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

class StartCommand
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
        
        $welcomeMessage = $this->getWelcomeMessage($user);
        $keyboard = $this->getWelcomeKeyboard();
        
        try {
            $this->bot->sendMessage($chatId, $welcomeMessage, 'HTML', false, null, $keyboard);
            
            $this->logger->info('Start command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send start message', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function getWelcomeMessage(User $user): string
    {
        $firstName = $user->getFirstName() ?: 'User';
        $isNewUser = $user->getCreatedAt() > date('Y-m-d H:i:s', strtotime('-5 minutes'));
        
        if ($isNewUser) {
            return "
<b> Welcome to IMEI Bot Platform! </b> {$firstName}! 

<b> I'm your advanced IMEI generation and file download assistant. </b>

<b> What I can do for you: </b>
<b> Generate IMEI numbers </b> with validation
<b> Download firmware, QCN, NVRAM, and solution files </b>
<b> Search and browse files by brand and model </b>
<b> Provide analytics and statistics </b>

<b> Quick Start: </b>
<b> /random </b> - Generate a random IMEI
<b> /firmware </b> - Browse firmware files
<b> /help </b> - See all commands

<b> Premium Features Available! </b>
<b> Unlimited IMEI generation </b>
<b> Priority file downloads </b>
<b> Advanced search filters </b>

<b> Type /menu to see all available options, or use any command below. </b>
            ";
        } else {
            return "
<b> Welcome back, {$firstName}! </b>

<b> IMEI Bot Platform </b> - Your trusted assistant for IMEI generation and mobile file downloads.

<b> Quick Commands: </b>
<b> /random </b> - Generate random IMEI
<b> /firmware </b> - Browse firmware
<b> /profile </b> - View your profile
<b> /menu </b> - Main menu

<b> Need help? </b> Use /help to see all available commands.
            ";
        }
    }
    
    private function getWelcomeKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                ['text' => 'Generate IMEI', 'callback_data' => 'imei:random'],
                ['text' => 'Browse Files', 'callback_data' => 'menu:files'],
            ],
            [
                ['text' => 'My Profile', 'callback_data' => 'menu:profile'],
                ['text' => 'Help', 'callback_data' => 'menu:help'],
            ],
            [
                ['text' => 'Premium Features', 'callback_data' => 'menu:premium'],
                ['text' => 'Statistics', 'callback_data' => 'menu:stats'],
            ],
        ]);
    }
}
