<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Checking Test Users ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/check.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Check all test users
    $testUsers = $db->fetchAll("SELECT id, email, username, role, status, created_at FROM users WHERE email LIKE '%@imei-bot.com' ORDER BY created_at DESC");
    
    echo "Found " . count($testUsers) . " test users:\n\n";
    
    foreach ($testUsers as $user) {
        echo "User ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Status: " . $user['status'] . "\n";
        echo "Created: " . $user['created_at'] . "\n";
        echo "---\n";
    }
    
    // Test password verification for premium user
    echo "\n=== Testing Premium User Login ===\n";
    
    $premiumUser = $db->fetchOne("SELECT email, username, password_hash FROM users WHERE email = 'premium@imei-bot.com'");
    
    if ($premiumUser) {
        echo "Premium user found in database\n";
        echo "Email: " . $premiumUser['email'] . "\n";
        echo "Username: " . $premiumUser['username'] . "\n";
        
        // Test password verification
        $testPassword = 'PremiumPassword123!';
        $passwordValid = password_verify($testPassword, $premiumUser['password_hash']);
        
        echo "Password verification: " . ($passwordValid ? "VALID" : "INVALID") . "\n";
        
        if (!$passwordValid) {
            echo "Password hash in database: " . $premiumUser['password_hash'] . "\n";
            echo "Test password: " . $testPassword . "\n";
            
            // Create new password hash
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "New hash would be: " . $newHash . "\n";
            
            // Update the password
            $db->query("UPDATE users SET password_hash = ? WHERE email = ?", [$newHash, 'premium@imei-bot.com']);
            echo "Password updated in database\n";
        }
    } else {
        echo "Premium user NOT found in database\n";
        
        // Create the premium user
        $premiumUuid = bin2hex(random_bytes(16));
        $premiumPasswordHash = password_hash('PremiumPassword123!', PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO users (uuid, email, username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$premiumUuid, 'premium@imei-bot.com', 'premiumuser', $premiumPasswordHash, 'premium', 'active']
        );
        echo "Premium user created\n";
    }
    
    // Test admin user too
    echo "\n=== Testing Admin User Login ===\n";
    
    $adminUser = $db->fetchOne("SELECT email, username, password_hash FROM users WHERE email = 'admin@imei-bot.com'");
    
    if ($adminUser) {
        echo "Admin user found in database\n";
        echo "Email: " . $adminUser['email'] . "\n";
        echo "Username: " . $adminUser['username'] . "\n";
        
        // Test password verification
        $testPassword = 'Admin123!';
        $passwordValid = password_verify($testPassword, $adminUser['password_hash']);
        
        echo "Password verification: " . ($passwordValid ? "VALID" : "INVALID") . "\n";
        
        if (!$passwordValid) {
            echo "Updating admin password\n";
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password_hash = ? WHERE email = ?", [$newHash, 'admin@imei-bot.com']);
            echo "Admin password updated\n";
        }
    } else {
        echo "Admin user NOT found in database\n";
        
        // Create the admin user
        $adminUuid = bin2hex(random_bytes(16));
        $adminPasswordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO users (uuid, email, username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$adminUuid, 'admin@imei-bot.com', 'admin', $adminPasswordHash, 'super_admin', 'active']
        );
        echo "Admin user created\n";
    }
    
    // Test regular user too
    echo "\n=== Testing Regular User Login ===\n";
    
    $regularUser = $db->fetchOne("SELECT email, username, password_hash FROM users WHERE email = 'user@imei-bot.com'");
    
    if ($regularUser) {
        echo "Regular user found in database\n";
        echo "Email: " . $regularUser['email'] . "\n";
        echo "Username: " . $regularUser['username'] . "\n";
        
        // Test password verification
        $testPassword = 'TestPassword123!';
        $passwordValid = password_verify($testPassword, $regularUser['password_hash']);
        
        echo "Password verification: " . ($passwordValid ? "VALID" : "INVALID") . "\n";
        
        if (!$passwordValid) {
            echo "Updating regular user password\n";
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password_hash = ? WHERE email = ?", [$newHash, 'user@imei-bot.com']);
            echo "Regular user password updated\n";
        }
    } else {
        echo "Regular user NOT found in database\n";
        
        // Create the regular user
        $userUuid = bin2hex(random_bytes(16));
        $userPasswordHash = password_hash('TestPassword123!', PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO users (uuid, email, username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$userUuid, 'user@imei-bot.com', 'testuser', $userPasswordHash, 'user', 'active']
        );
        echo "Regular user created\n";
    }
    
    echo "\n=== FINAL CREDENTIALS ===\n\n";
    echo "ADMIN LOGIN:\n";
    echo "Email: admin@imei-bot.com\n";
    echo "Password: Admin123!\n\n";
    
    echo "PREMIUM USER LOGIN:\n";
    echo "Email: premium@imei-bot.com\n";
    echo "Password: PremiumPassword123!\n\n";
    
    echo "REGULAR USER LOGIN:\n";
    echo "Email: user@imei-bot.com\n";
    echo "Password: TestPassword123!\n\n";
    
    echo "All credentials have been verified and updated!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Complete ===\n";
?>
