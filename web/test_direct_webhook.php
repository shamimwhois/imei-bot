<?php

declare(strict_types=1);

echo "=== Direct Webhook Test ===\n\n";

// Test webhook endpoint
$webhookUrl = 'http://localhost:8080/bot.php';

// Create test webhook data
$testData = [
    'update_id' => 123456789,
    'message' => [
        'message_id' => 1,
        'from' => [
            'id' => 931774029,
            'is_bot' => false,
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'language_code' => 'en'
        ],
        'chat' => [
            'id' => 931774029,
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'type' => 'private'
        ],
        'date' => time(),
        'text' => '/start'
    ]
];

echo "Testing direct webhook endpoint: $webhookUrl\n";
echo "Test data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}
echo "Response: $response\n\n";

if ($httpCode === 200) {
    echo "Direct webhook endpoint is working! \n";
} else {
    echo "Direct webhook endpoint test failed.\n";
}

echo "\n=== Test Complete ===\n";
