<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Core\BotManager;

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the webhook data
$input = file_get_contents('php://input');
$webhookData = json_decode($input, true);

if (!$webhookData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    // Initialize services
    $logger = new Logger(require __DIR__ . '/../config/monitoring.php');
    $database = Database::getInstance(
        require __DIR__ . '/../config/database.php',
        $logger
    );
    
    // Get bot configuration
    $config = require __DIR__ . '/../config/bot.php';
    $telegramToken = $config['telegram']['token'];
    
    // Log the webhook
    $logger->info('Telegram webhook received', [
        'update_id' => $webhookData['update_id'] ?? null,
        'message_id' => $webhookData['message']['message_id'] ?? null,
        'chat_id' => $webhookData['message']['chat']['id'] ?? null,
        'text' => $webhookData['message']['text'] ?? null
    ]);
    
    // Process the message
    $message = $webhookData['message'] ?? null;
    if ($message) {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'];
        
        // Handle commands
        $response = handleCommand($text, $chatId, $userId, $telegramToken, $database, $logger);
        
        if ($response) {
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process command']);
        }
    } else {
        echo json_encode(['status' => 'success']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleCommand($text, $chatId, $userId, $token, $database, $logger): bool
{
    try {
        $response = '';
        
        switch (strtolower($text)) {
            case '/start':
                $response = "Hello! Welcome to IMEI Bot Platform! 

I can help you with:
/random - Generate random IMEI
/single - Generate single IMEI  
/dual - Generate dual IMEI
/validate - Validate IMEI
/profile - View your profile
/help - Show help

Reply with /start to begin!";
                break;
                
            case '/help':
                $response = "Available commands:
/start - Start using the bot
/help - Show this help message
/random - Generate a random IMEI number
/single - Generate a single IMEI with prefix selection
/dual - Generate a dual IMEI pair
/validate - Validate an existing IMEI number
/profile - View your profile and statistics
/premium - Upgrade to premium features
/settings - Personal settings
/feedback - Send feedback to admin
/menu - Show main menu";
                break;
                
            case '/random':
                $imei = generateRandomIMEI();
                $response = "Random IMEI generated: `$imei`";
                break;
                
            case '/profile':
                $response = "Your Profile:
User ID: $userId
Chat ID: $chatId
Status: Active
Premium: Basic

Use /premium to upgrade!";
                break;
                
            default:
                if (strpos($text, '/') === 0) {
                    $response = "Unknown command. Use /help to see available commands.";
                }
                break;
        }
        
        if ($response) {
            return sendMessage($token, $chatId, $response);
        }
        
        return true;
        
    } catch (Exception $e) {
        $logger->error('Error handling command', [
            'command' => $text,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

function generateRandomIMEI(): string
{
    $prefixes = ['35', '86', '89', '45'];
    $prefix = $prefixes[array_rand($prefixes)];
    
    $imei = $prefix;
    for ($i = 0; $i < 13; $i++) {
        $imei .= random_int(0, 9);
    }
    
    return $imei;
}

function sendMessage($token, $chatId, $text): bool
{
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}
