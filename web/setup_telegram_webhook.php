<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Telegram Webhook Setup ===\n\n";

// Load configuration
$telegramToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '6056134016:AAExCQ12HYybTdUBT99pFQp4jrIBNBVklZw';
$adminChatId = $_ENV['TELEGRAM_ADMIN_CHAT_ID'] ?? '931774029';

echo "Bot Token: " . substr($telegramToken, 0, 10) . "...\n";
echo "Admin Chat ID: $adminChatId\n\n";

// For local development, we'll use polling instead of webhook
echo "Setting up polling mode for local development...\n";

// Delete existing webhook
$deleteWebhookUrl = "https://api.telegram.org/bot{$telegramToken}/deleteWebhook";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $deleteWebhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        echo "Webhook deleted successfully!\n";
    } else {
        echo "Error deleting webhook: " . ($data['description'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "HTTP Error: $httpCode\n";
}

// Send setup confirmation message
echo "\nSending setup confirmation...\n";
$messageUrl = "https://api.telegram.org/bot{$telegramToken}/sendMessage";

$messageData = [
    'chat_id' => $adminChatId,
    'text' => "Your IMEI Bot Platform is now configured! 

Bot Status: Online
Mode: Polling (for local development)
Chat ID: $adminChatId

Available Commands:
/start - Start using the bot
/help - Show help
/random - Generate random IMEI
/single - Generate single IMEI
/dual - Generate dual IMEI
/validate - Validate IMEI
/profile - View your profile

Try sending /start to test the bot!",
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
        echo "Setup message sent successfully!\n";
        echo "Message ID: {$data['result']['message_id']}\n";
    } else {
        echo "Error: " . ($data['description'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "HTTP Error: $httpCode\n";
}

echo "\n=== Setup Complete ===\n";
echo "Your bot is ready to use! Try sending commands to @Rdxunlockbot\n";
