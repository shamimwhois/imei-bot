<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

session_start();

// Initialize services
$logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/auth.log']]);
$db = Database::getInstance(
    ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
    $logger
);
$auth = new AuthMiddleware($db, $logger);

// Logout user
$auth->logout();

// Redirect to login page with success message
$_SESSION['flash_messages']['success'][] = 'You have been logged out successfully.';
header('Location: /login.php');
exit;
