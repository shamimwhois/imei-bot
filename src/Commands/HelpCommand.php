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

class HelpCommand
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
        
        $helpMessage = $this->getHelpMessage($user);
        $keyboard = $this->getHelpKeyboard();
        
        try {
            $this->bot->sendMessage($chatId, $helpMessage, 'HTML', false, null, $keyboard);
            
            $this->logger->info('Help command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send help message', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function getHelpMessage(User $user): string
    {
        $isAdmin = $user->getRole() === 'admin';
        $isPremium = $user->getRole() === 'premium';
        
        $message = "
<b> IMEI Bot Platform - Help </b>

<b> IMEI Generation Commands: </b>
<b> /random </b> - Generate a random IMEI number
<b> /single </b> - Generate IMEI with prefix selection
<b> /dual </b> - Generate dual IMEI pair
<b> /multi </b> - Generate multiple IMEIs
<b> /validate </b> - Validate an existing IMEI
<b> /history </b> - View your IMEI generation history

<b> File Download Commands: </b>
<b> /firmware </b> - Browse firmware files
<b> /engrom </b> - Browse engineering ROM files
<b> /qcnnvram </b> - Browse QCN and NVRAM files
<b> /solution </b> - Browse solution files
<b> /search </b> - Search for files
<b> /recent </b> - Latest uploaded files
<b> /popular </b> - Most downloaded files

<b> User Management: </b>
<b> /profile </b> - View your profile and statistics
<b> /settings </b> - Personal preferences
<b> /feedback </b> - Send feedback to admin
        ";
        
        if ($isPremium) {
            $message .= "
<b> Premium Features: </b>
<b> /premium </b> - Premium dashboard
<b> /unlimited </b> - Unlimited IMEI generation
<b> /priority </b> - Priority file access
            ";
        }
        
        if ($isAdmin) {
            $message .= "
<b> Admin Commands: </b>
<b> /admin </b> - Admin dashboard
<b> /stats </b> - System statistics
<b> /broadcast </b> - Send announcements
<b> /maintenance </b> - System maintenance
<b> /backup </b> - Manual backup
            ";
        }
        
        $message .= "
<b> Navigation: </b>
<b> /start </b> - Welcome message
<b> /menu </b> - Main menu
<b> /back </b> - Go back

<b> Tips: </b>
<b> Use inline keyboards for easier navigation </b>
<b> Check file descriptions before downloading </b>
<b> Validate IMEIs before using them </b>
<b> Contact support for any issues </b>

<b> Support: </b>
<b> Need help? Use /feedback </b>
<b> Premium upgrade: /premium </b>
<b> Report issues: /feedback </b>
        ";
        
        return $message;
    }
    
    private function getHelpKeyboard(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                ['text' => 'IMEI Services', 'callback_data' => 'imei:random'],
                ['text' => 'File Downloads', 'callback_data' => 'menu:files'],
            ],
            [
                ['text' => 'My Profile', 'callback_data' => 'menu:profile'],
                ['text' => 'Settings', 'callback_data' => 'menu:settings'],
            ],
            [
                ['text' => 'Premium Upgrade', 'callback_data' => 'menu:premium'],
                ['text' => 'Send Feedback', 'callback_data' => 'menu:feedback'],
            ],
        ]);
    }
}
