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

class FeedbackCommand
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
            $feedbackType = $args[0] ?? 'menu';
            
            switch ($feedbackType) {
                case 'bug':
                    $this->showBugReport($chatId, $user);
                    break;
                case 'feature':
                    $this->showFeatureRequest($chatId, $user);
                    break;
                case 'general':
                    $this->showGeneralFeedback($chatId, $user);
                    break;
                case 'complaint':
                    $this->showComplaintForm($chatId, $user);
                    break;
                default:
                    $this->showFeedbackMenu($chatId, $user);
                    break;
            }
            
            $this->logger->info('Feedback command executed', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'feedback_type' => $feedbackType,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle feedback command', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to process feedback request. Please try again.');
        }
    }
    
    private function showFeedbackMenu(int $chatId, User $user): void
    {
        $firstName = $user->getFirstName() ?: 'User';
        
        $message = "
<b> Feedback & Support </b>

<b> We value your feedback, {$firstName}! </b>

<b> How can we help you today? </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Report a Bug', 'callback_data' => 'feedback:bug'],
                ['text' => 'Request Feature', 'callback_data' => 'feedback:feature'],
            ],
            [
                ['text' => 'General Feedback', 'callback_data' => 'feedback:general'],
                ['text' => 'File a Complaint', 'callback_data' => 'feedback:complaint'],
            ],
            [
                ['text' => 'Contact Support', 'callback_data' => 'feedback:support'],
                ['text' => 'FAQ', 'callback_data' => 'feedback:faq'],
            ],
            [
                ['text' => 'Rate Us', 'callback_data' => 'feedback:rate'],
                ['text' => '« Back', 'callback_data' => 'menu:main'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show feedback menu', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showBugReport(int $chatId, User $user): void
    {
        $message = "
<b> Report a Bug </b>

<b> Help us improve by reporting issues: </b>

<b> Please describe: </b>
<b> 1. What happened? </b>
<b> 2. What did you expect to happen? </b>
<b> 3. Steps to reproduce the issue </b>
<b> 4. Any error messages you saw </b>
<b> 5. Your device/app information </b>

<b> Type your bug report below: </b>

<b> Example: </b>
<b> When I tried to generate an IMEI with /random command, </b>
<b> I got an error message saying 'Failed to generate IMEI'. </b>
<b> I expected to receive a valid IMEI number. </b>
<b> Steps: Used /random command, waited 10 seconds, got error. </b>
<b> Error: 'Failed to generate IMEI. Please try again later.' </b>
<b> Device: iPhone 12, iOS 15.5, Telegram app v9.2.5 </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML');
            
            // Store feedback state
            $this->cache->set("feedback_state_{$user->getId()}", [
                'type' => 'bug',
                'chat_id' => $chatId,
                'timestamp' => time(),
            ], 300); // 5 minutes
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show bug report form', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showFeatureRequest(int $chatId, User $user): void
    {
        $message = "
<b> Request a Feature </b>

<b> We'd love to hear your ideas! </b>

<b> Please describe: </b>
<b> 1. What feature would you like? </b>
<b> 2. Why would this be useful? </b>
<b> 3. How would you like it to work? </b>
<b> 4. Any specific requirements or preferences </b>

<b> Type your feature request below: </b>

<b> Examples: </b>
<b> 'I'd like to be able to generate IMEIs for specific countries' </b>
<b> 'Add a way to export IMEI history to CSV' </b>
<b> 'Create a dark mode for the interface' </b>
<b> 'Add voice commands for IMEI generation' </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML');
            
            // Store feedback state
            $this->cache->set("feedback_state_{$user->getId()}", [
                'type' => 'feature',
                'chat_id' => $chatId,
                'timestamp' => time(),
            ], 300); // 5 minutes
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show feature request form', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showGeneralFeedback(int $chatId, User $user): void
    {
        $message = "
<b> General Feedback </b>

<b> Share your thoughts and suggestions: </b>

<b> Tell us about: </b>
<b> What you like about the bot </b>
<b> What could be improved </b>
<b> Your overall experience </b>
<b> Any suggestions for new features </b>
<b> Comments on performance or usability </b>

<b> Type your feedback below: </b>

<b> We appreciate all feedback and use it to improve our service! </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML');
            
            // Store feedback state
            $this->cache->set("feedback_state_{$user->getId()}", [
                'type' => 'general',
                'chat_id' => $chatId,
                'timestamp' => time(),
            ], 300); // 5 minutes
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show general feedback form', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function showComplaintForm(int $chatId, User $user): void
    {
        $message = "
<b> File a Complaint </b>

<b> We take complaints seriously and will address them promptly. </b>

<b> Please provide: </b>
<b> 1. Nature of your complaint </b>
<b> 2. When and where it occurred </b>
<b> 3. Who was involved (if applicable) </b>
<b> 4. What resolution you're seeking </b>
<b> 5. Any supporting evidence or details </b>

<b> Type your complaint below: </b>

<b> Your complaint will be reviewed by our support team </b>
<b> within 24 hours. We'll respond via Telegram. </b>

<b> For urgent matters, contact support directly: </b>
<b> @support_username or email support@imei-bot.com </b>
        ";
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML');
            
            // Store feedback state
            $this->cache->set("feedback_state_{$user->getId()}", [
                'type' => 'complaint',
                'chat_id' => $chatId,
                'timestamp' => time(),
            ], 300); // 5 minutes
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to show complaint form', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    public function processFeedback(Message $message, User $user): void
    {
        $chatId = $message->getChat()->getId();
        $feedbackText = $message->getText();
        
        // Get feedback state
        $feedbackState = $this->cache->get("feedback_state_{$user->getId()}");
        
        if (!$feedbackState) {
            $this->sendMessage($chatId, 'Feedback session expired. Please start again with /feedback');
            return;
        }
        
        try {
            // Process the feedback
            $this->submitFeedback($user, $feedbackState['type'], $feedbackText);
            
            // Send confirmation
            $this->sendFeedbackConfirmation($chatId, $user, $feedbackState['type']);
            
            // Clear feedback state
            $this->cache->delete("feedback_state_{$user->getId()}");
            
            $this->logger->info('Feedback submitted', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'type' => $feedbackState['type'],
                'length' => strlen($feedbackText),
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process feedback', [
                'user_id' => $user->getId(),
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            $this->sendMessage($chatId, 'Failed to submit feedback. Please try again.');
        }
    }
    
    private function submitFeedback(User $user, string $type, string $text): void
    {
        // In a real implementation, this would save to database
        // and possibly send notification to admin
        
        $feedbackData = [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'first_name' => $user->getFirstName(),
            'type' => $type,
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s'),
            'platform' => 'telegram',
        ];
        
        // Log the feedback
        $this->logger->info('Feedback received', $feedbackData);
        
        // In a real implementation, you would:
        // 1. Save to database
        // 2. Send notification to admin
        // 3. Create support ticket if needed
        // 4. Send auto-response if configured
    }
    
    private function sendFeedbackConfirmation(int $chatId, User $user, string $type): void
    {
        $typeNames = [
            'bug' => 'bug report',
            'feature' => 'feature request',
            'general' => 'feedback',
            'complaint' => 'complaint',
        ];
        
        $typeName = $typeNames[$type] ?? 'feedback';
        
        $message = "
<b> Thank You! </b>

<b> Your {$typeName} has been submitted successfully. </b>

<b> What happens next: </b>
<b> We'll review your {$typeName} within 24 hours </b>
<b> You'll receive a response via Telegram </b>
<b> We may contact you for additional information </b>

<b> Reference ID: </b> #" . strtoupper(substr(md5($user->getId() . time()), 0, 8)) . "

<b> Need immediate assistance? </b>
<b> Contact support: @support_username </b>
<b> Email: support@imei-bot.com </b>

<b> Thank you for helping us improve! </b>
        ";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Submit More Feedback', 'callback_data' => 'feedback:menu'],
                ['text' => 'Contact Support', 'callback_data' => 'feedback:support'],
            ],
            [
                ['text' => '« Back to Main', 'callback_data' => 'menu:main'],
                ['text' => 'Rate Our Service', 'callback_data' => 'feedback:rate'],
            ],
        ]);
        
        try {
            $this->bot->sendMessage($chatId, $message, 'HTML', false, null, $keyboard);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send feedback confirmation', [
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
