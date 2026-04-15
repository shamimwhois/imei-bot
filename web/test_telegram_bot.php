<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Telegram Bot Test ===\n\n";

// Load configuration
$config = require __DIR__ . '/../config/telegram.php';
$telegramToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '6056134016:AAExCQ12HYybTdUBT99pFQp4jrIBNBVklZw';
$adminChatId = $_ENV['TELEGRAM_ADMIN_CHAT_ID'] ?? '931774029';

echo "Bot Token: " . substr($telegramToken, 0, 10) . "...\n";
echo "Admin Chat ID: $adminChatId\n\n";

// Test 1: Get bot information
echo "1. Testing bot information...\n";
$botInfoUrl = "https://api.telegram.org/bot{$telegramToken}/getMe";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $botInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        $bot = $data['result'];
        echo "   Bot Name: {$bot['first_name']}\n";
        echo "   Bot Username: @{$bot['username']}\n";
        echo "   Bot ID: {$bot['id']}\n";
        echo "   Status: Connected successfully\n";
    } else {
        echo "   Error: " . ($data['description'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   HTTP Error: $httpCode\n";
    echo "   Response: $response\n";
}

echo "\n";

// Test 2: Send test message to admin
echo "2. Sending test message to admin...\n";
$messageUrl = "https://api.telegram.org/bot{$telegramToken}/sendMessage";

$messageData = [
    'chat_id' => $adminChatId,
    'text' => "Hello! I'm your IMEI Bot Platform test bot. I'm now connected and ready to use! 

Commands available:
/start - Start using the bot
/help - Show help
/random - Generate random IMEI
/single - Generate single IMEI
/dual - Generate dual IMEI
/validate - Validate IMEI
/profile - View your profile

Reply with /start to begin!",
    'parse_mode' => 'HTML'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $messageUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        echo "   Message sent successfully!\n";
        echo "   Message ID: {$data['result']['message_id']}\n";
    } else {
        echo "   Error: " . ($data['description'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   HTTP Error: $httpCode\n";
    echo "   Response: $response\n";
}

echo "\n";

// Test 3: Set webhook
echo "3. Setting webhook...\n";
$webhookUrl = $_ENV['TELEGRAM_WEBHOOK_URL'] ?? 'http://localhost:8080/api/telegram/webhook';
$webhookData = [
    'url' => $webhookUrl,
    'allowed_updates' => ['message', 'callback_query', 'inline_query']
];

$webhookSetUrl = "https://api.telegram.org/bot{$telegramToken}/setWebhook";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookSetUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        echo "   Webhook set successfully!\n";
        echo "   Webhook URL: {$data['result']['url']}\n";
        echo "   Allowed updates: " . implode(', ', $data['result']['allowed_updates']) . "\n";
    } else {
        echo "   Error: " . ($data['description'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   HTTP Error: $httpCode\n";
    echo "   Response: $response\n";
}

echo "\n";

// Test 4: Check webhook info
echo "4. Checking webhook info...\n";
$webhookInfoUrl = "https://api.telegram.org/bot{$telegramToken}/getWebhookInfo";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        $webhook = $data['result'];
        echo "   Webhook URL: {$webhook['url']}\n";
        echo "   Custom Certificate: " . ($webhook['has_custom_certificate'] ? 'Yes' : 'No') . "\n";
        echo "   Pending Updates: {$webhook['pending_update_count']}\n";
        echo "   Last Error: " . ($webhook['last_error_message'] ?? 'None') . "\n";
    } else {
        echo "   Error: " . ($data['description'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   HTTP Error: $httpCode\n";
    echo "   Response: $response\n";
}

echo "\n=== Test Complete ===\n";
