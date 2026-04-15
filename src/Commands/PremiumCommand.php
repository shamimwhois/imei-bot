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

class PremiumCommand
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
            $isPremium = $user->getRole() === 'premium';
            
            if ($isPremium) {
                $this->sendPremiumDashboard($chatId, $user);
            } else {
                $this->sendPremiumOffer($chatId, $user);
            }
            
            $this->logger->info('Premium command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'is_premium' => $isPremium,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle premium command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to process premium request. Please try again.');
        }
    }
    
    private function sendPremiumDashboard(int $chatId, User $user): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        $premiumExpiry = $user->getPremiumExpiresAt() ? date('M j, Y', strtotime($user->getPremiumExpiresAt())) : 'Lifetime';
        
        $message = "
<b> Premium Dashboard </b>

<b> Welcome back, {$firstName}! </b>

<b> Your Premium Status: </b>
<b> Plan: </b> Premium
<b> Expires: </b> {$premiumExpiry}
<b> Member Since: </b> " . date('M j, Y', strtotime($user->getCreatedAt())) . "

<b> Premium Benefits: </b>
<b> Unlimited IMEI generation </b>
<b> Priority file downloads </b>
<b> Advanced search filters </b>
<b> No daily limits </b>
<b> Priority support </b>
<b> Access to exclusive files </b>

<b> Quick Actions: </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Generate Unlimited IMEI', 'callback_data' => 'imei:multi:50'],
                ['text' => 'Priority File Access', 'callback_data' => 'file:premium'],
            ],
            [
                ['text' => 'Advanced Search', 'callback_data' => 'search:advanced'],
                ['text' => 'Exclusive Files', 'callback_data' => 'file:exclusive'],
            ],
            [
                ['text' => 'Premium Statistics', 'callback_data' => 'premium:stats'],
                ['text' => 'Priority Support', 'callback_data' => 'premium:support'],
            ],
            [
                ['text' => 'Manage Subscription', 'callback_data' => 'premium:manage'],
                ['text' => 'Refer a Friend', 'callback_data' => 'premium:refer'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send premium dashboard', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function sendPremiumOffer(int $chatId, User $user): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        $currentUsage = $this->getCurrentUsage($user->getId());
        
        $message = "
<b> Upgrade to Premium! </b>

<b> Hello, {$firstName}! </b>

<b> Your Current Usage: </b>
<b> IMEIs Generated Today: </b> {$currentUsage['today']}/{$user->getDailyImeiLimit()}
<b> Files Downloaded Today: </b> {$currentUsage['downloads']}/{$user->getDailyDownloadLimit()}
<b> Storage Used: </b> {$user->getStorageUsedMb()}MB/{$user->getStorageQuotaMb()}MB

<b> Premium Benefits: </b>
<b> Unlimited IMEI generation </b>
<b> Unlimited file downloads </b>
<b> Priority download speeds </b>
<b> Advanced search filters </b>
<b> Access to exclusive files </b>
<b> Priority customer support </b>
<b> No advertisements </b>
<b> Export functionality </b>

<b> Pricing Plans: </b>
<b> Monthly: </b> $9.99/month
<b> Quarterly: </b> $24.99/quarter (Save 17%)
<b> Yearly: </b> $79.99/year (Save 33%)
<b> Lifetime: </b> $199.99 (One-time payment)

<b> Special Offer: </b>
<b> Get 50% off your first month! </b>
Use code: WELCOME50

<b> Ready to upgrade? </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Upgrade Monthly - $9.99', 'callback_data' => 'premium:upgrade:monthly'],
                ['text' => 'Upgrade Quarterly - $24.99', 'callback_data' => 'premium:upgrade:quarterly'],
            ],
            [
                ['text' => 'Upgrade Yearly - $79.99', 'callback_data' => 'premium:upgrade:yearly'],
                ['text' => 'Upgrade Lifetime - $199.99', 'callback_data' => 'premium:upgrade:lifetime'],
            ],
            [
                ['text' => 'Compare Plans', 'callback_data' => 'premium:compare'],
                ['text' => 'Free Trial', 'callback_data' => 'premium:trial'],
            ],
            [
                ['text' => 'Learn More', 'callback_data' => 'premium:info'],
                ['text' => 'Contact Support', 'callback_data' => 'premium:support'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send premium offer', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function getCurrentUsage(int $userId): array
    {
        $today = date('Y-m-d');
        $cacheKey = "imei_daily_limit_{$userId}_{$today}";
        $todayUsage = $this->cache->get($cacheKey, 0);
        
        // For downloads, we would need to implement a similar tracking system
        $downloadsCacheKey = "downloads_daily_limit_{$userId}_{$today}";
        $todayDownloads = $this->cache->get($downloadsCacheKey, 0);
        
        return [
            'today' => $todayUsage,
            'downloads' => $todayDownloads,
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
