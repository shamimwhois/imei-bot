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

// Test authentication functionality
echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Auth Test</title>\n</head>\n<body>\n";
echo "<h1>🔐 Authentication System Test</h1>\n";

// Test 1: Check if login page exists
echo "<h2>Test 1: Login Page</h2>\n";
if (file_exists(__DIR__ . '/auth/login.php')) {
    echo "<p>✅ Login page exists at /auth/login.php</p>\n";
} else {
    echo "<p>❌ Login page not found</p>\n";
}

// Test 2: Check if registration page exists
echo "<h2>Test 2: Registration Page</h2>\n";
if (file_exists(__DIR__ . '/auth/register.php')) {
    echo "<p>✅ Registration page exists at /auth/register.php</p>\n";
} else {
    echo "<p>❌ Registration page not found</p>\n";
}

// Test 3: Check if forgot password page exists
echo "<h2>Test 3: Forgot Password Page</h2>\n";
if (file_exists(__DIR__ . '/auth/forgot-password.php')) {
    echo "<p>✅ Forgot password page exists at /auth/forgot-password.php</p>\n";
} else {
    echo "<p>❌ Forgot password page not found</p>\n";
}

// Test 4: Check if email verification page exists
echo "<h2>Test 4: Email Verification Page</h2>\n";
if (file_exists(__DIR__ . '/auth/verify.php')) {
    echo "<p>✅ Email verification page exists at /auth/verify.php</p>\n";
} else {
    echo "<p>❌ Email verification page not found</p>\n";
}

// Test 5: Check if password reset page exists
echo "<h2>Test 5: Password Reset Page</h2>\n";
if (file_exists(__DIR__ . '/auth/reset-password.php')) {
    echo "<p>✅ Password reset page exists at /auth/reset-password.php</p>\n";
} else {
    echo "<p>❌ Password reset page not found</p>\n";
}

// Test 6: Check if database tables exist
echo "<h2>Test 6: Database Tables</h2>\n";
$requiredTables = ['users', 'remember_tokens', 'password_resets'];
foreach ($requiredTables as $table) {
    $exists = $db->fetchOne("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "<p>✅ Table '$table' exists</p>\n";
    } else {
        echo "<p>❌ Table '$table' missing</p>\n";
    }
}

// Test 7: Check AuthMiddleware functionality
echo "<h2>Test 7: AuthMiddleware</h2>\n";
$csrfToken = $auth->generateCsrfToken();
echo "<p>✅ CSRF token generation: $csrfToken</p>\n";

echo "<h2>Access Links</h2>\n";
echo "<ul>\n";
echo "<li><a href='/auth/login.php' target='_blank'>Login Page</a></li>\n";
echo "<li><a href='/auth/register.php' target='_blank'>Registration Page</a></li>\n";
echo "<li><a href='/auth/forgot-password.php' target='_blank'>Forgot Password</a></li>\n";
echo "<li><a href='/auth/verify.php' target='_blank'>Email Verification</a></li>\n";
echo "<li><a href='/auth/reset-password.php' target='_blank'>Password Reset</a></li>\n";
echo "</ul>\n";

echo "</body>\n</html>\n";
?>
