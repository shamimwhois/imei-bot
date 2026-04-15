<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

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
    $logger = new Logger(require __DIR__ . '/../../config/monitoring.php');
    $database = Database::getInstance(
        require __DIR__ . '/../../config/database.php',
        $logger
    );
    
    $botManager = new BotManager($database, $logger);
    
    // Log the webhook
    $logger->info('Telegram webhook received', [
        'update_id' => $webhookData['update_id'] ?? null,
        'message_id' => $webhookData['message']['message_id'] ?? null,
        'chat_id' => $webhookData['message']['chat']['id'] ?? null,
        'text' => $webhookData['message']['text'] ?? null
    ]);
    
    // Process the webhook
    $result = $botManager->handleTelegramWebhook($webhookData);
    
    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process webhook']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
