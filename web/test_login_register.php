<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;
use IMEIBot\Middleware\AuthMiddleware;

session_start();

// Initialize services
$logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/auth.log']]);
$db = Database::getInstance(
    ['host' => $_ENV['DB_HOST'] ?? 'localhost', 'port' => $_ENV['DB_PORT'] ?? '3306', 'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot', 'username' => $_ENV['DB_USERNAME'] ?? 'root', 'password' => $_ENV['DB_PASSWORD'] ?? ''],
    $logger
);
$auth = new AuthMiddleware($db, $logger);

// Test login functionality
echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Login & Register Test</title>\n</head>\n<body>\n";
echo "<h1>🔐 Login & Register Test</h1>\n";

// Test 1: Test registration
echo "<h2>Test 1: User Registration</h2>\n";
$testEmail = 'testuser' . time() . '@example.com';
$testUsername = 'testuser' . time();
$testFirstName = 'Test';
$testLastName = 'User';
$testPassword = 'TestPassword123!';

echo "<p>Testing registration with:</p>\n";
echo "<ul>\n";
echo "<li>Email: $testEmail</li>\n";
echo "<li>Username: $testUsername</li>\n";
echo "<li>First Name: $testFirstName</li>\n";
echo "<li>Last Name: $testLastName</li>\n";
echo "<li>Password: $testPassword</li>\n";
echo "</ul>\n";

// Simulate registration POST
$_POST = [
    'csrf_token' => $auth->generateCsrfToken(),
    'first_name' => $testFirstName,
    'last_name' => $testLastName,
    'email' => $testEmail,
    'username' => $testUsername,
    'password' => $testPassword,
    'confirm_password' => $testPassword,
    'agree_terms' => true
];

// Include registration page
include __DIR__ . '/register.php';

// Test 2: Test login with created user
echo "<h2>Test 2: User Login</h2>\n";
echo "<p>Testing login with created user:</p>\n";
echo "<ul>\n";
echo "<li>Email: $testEmail</li>\n";
echo "<li>Password: $testPassword</li>\n";
echo "</ul>\n";

// Clear POST data
$_POST = [
    'csrf_token' => $auth->generateCsrfToken(),
    'identifier' => $testEmail,
    'password' => $testPassword,
    'remember' => false
];

// Include login page
include __DIR__ . '/login.php';

echo "</body>\n</html>\n";
?>
