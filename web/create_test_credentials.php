<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Creating Test Credentials ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/test.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Clear existing test users
    $db->query("DELETE FROM users WHERE email LIKE '%@imei-bot.com'");
    
    // Create admin user
    $adminUuid = bin2hex(random_bytes(16));
    $adminPasswordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
    $db->query(
        "INSERT INTO users (uuid, email, username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [$adminUuid, 'admin@imei-bot.com', 'admin', $adminPasswordHash, 'super_admin', 'active']
    );
    
    // Create premium user
    $premiumUuid = bin2hex(random_bytes(16));
    $premiumPasswordHash = password_hash('PremiumPassword123!', PASSWORD_DEFAULT);
    $db->query(
        "INSERT INTO users (uuid, email, username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [$premiumUuid, 'premium@imei-bot.com', 'premiumuser', $premiumPasswordHash, 'premium', 'active']
    );
    
    // Create regular user
    $userUuid = bin2hex(random_bytes(16));
    $userPasswordHash = password_hash('TestPassword123!', PASSWORD_DEFAULT);
    $db->query(
        "INSERT INTO users (uuid, email, username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [$userUuid, 'user@imei-bot.com', 'testuser', $userPasswordHash, 'user', 'active']
    );
    
    echo "Test credentials created successfully!\n\n";
    
    // Display test credentials
    echo "=== Test Credentials ===\n\n";
    
    echo "ADMIN LOGIN:\n";
    echo "Email: admin@imei-bot.com\n";
    echo "Password: Admin123!\n";
    echo "Role: Super Admin\n";
    echo "Access: Full admin panel access\n\n";
    
    echo "PREMIUM USER LOGIN:\n";
    echo "Email: premium@imei-bot.com\n";
    echo "Password: PremiumPassword123!\n";
    echo "Role: Premium\n";
    echo "Access: Premium features and higher limits\n\n";
    
    echo "REGULAR USER LOGIN:\n";
    echo "Email: user@imei-bot.com\n";
    echo "Password: TestPassword123!\n";
    echo "Role: User\n";
    echo "Access: Basic features\n\n";
    
    echo "LOGIN URLS:\n";
    echo "Login Page: http://localhost:8080/auth/login.php\n";
    echo "Admin Panel: http://localhost:8080/admin/dashboard.php\n";
    echo "User Dashboard: http://localhost:8080/dashboard.php\n\n";
    
    // Verify users were created
    $adminCheck = $db->fetchOne("SELECT id, email, role FROM users WHERE email = 'admin@imei-bot.com'");
    $premiumCheck = $db->fetchOne("SELECT id, email, role FROM users WHERE email = 'premium@imei-bot.com'");
    $userCheck = $db->fetchOne("SELECT id, email, role FROM users WHERE email = 'user@imei-bot.com'");
    
    echo "VERIFICATION:\n";
    echo "Admin user created: " . ($adminCheck ? "YES" : "NO") . "\n";
    echo "Premium user created: " . ($premiumCheck ? "YES" : "NO") . "\n";
    echo "Regular user created: " . ($userCheck ? "YES" : "NO") . "\n\n";
    
    echo "All test credentials are ready for login testing!\n";
    
} catch (Exception $e) {
    echo "Error creating test credentials: " . $e->getMessage() . "\n";
}

echo "\n=== Complete ===\n";
?>
