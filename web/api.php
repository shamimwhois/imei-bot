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

// Parse the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$requestUri = strtok($requestUri, '?');

// Route the request
switch ($requestUri) {
    case '/api/telegram/webhook':
        if ($requestMethod === 'POST') {
            handleTelegramWebhook();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/api/whatsapp/webhook':
        if ($requestMethod === 'POST') {
            handleWhatsAppWebhook();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case '/api/payment/webhook':
        if ($requestMethod === 'POST') {
            handlePaymentWebhook();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        break;
}

function handleTelegramWebhook(): void
{
    try {
        // Get the webhook data
        $input = file_get_contents('php://input');
        $webhookData = json_decode($input, true);

        if (!$webhookData) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        // Initialize services
        $logger = new Logger(require __DIR__ . '/../config/monitoring.php');
        $database = Database::getInstance(
            require __DIR__ . '/../config/database.php',
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
}

function handleWhatsAppWebhook(): void
{
    try {
        // Get the webhook data
        $input = file_get_contents('php://input');
        $webhookData = json_decode($input, true);

        if (!$webhookData) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        // Initialize services
        $logger = new Logger(require __DIR__ . '/../config/monitoring.php');
        $database = Database::getInstance(
            require __DIR__ . '/../config/database.php',
            $logger
        );
        
        $botManager = new BotManager($database, $logger);
        
        // Log the webhook
        $logger->info('WhatsApp webhook received', [
            'data' => $webhookData
        ]);
        
        // Process the webhook
        $result = $botManager->handleWhatsAppWebhook($webhookData);
        
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
}

function handlePaymentWebhook(): void
{
    try {
        // Get the webhook data
        $input = file_get_contents('php://input');
        $webhookData = json_decode($input, true);

        if (!$webhookData) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        // Initialize services
        $logger = new Logger(require __DIR__ . '/../config/monitoring.php');
        $database = Database::getInstance(
            require __DIR__ . '/../config/database.php',
            $logger
        );
        
        // Log the webhook
        $logger->info('Payment webhook received', [
            'gateway' => $webhookData['gateway'] ?? 'unknown',
            'transaction_id' => $webhookData['transaction_id'] ?? null
        ]);
        
        // Process the webhook (implementation would go here)
        echo json_encode(['status' => 'success']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
