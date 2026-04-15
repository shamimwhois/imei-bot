<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Testing Login Process ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/login_test.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Test premium user login process
    echo "Testing Premium User Login Process:\n";
    
    $email = 'premium@imei-bot.com';
    $password = 'PremiumPassword123!';
    
    echo "Email: $email\n";
    echo "Password: $password\n\n";
    
    // Step 1: Check if user exists
    $user = $db->fetchOne(
        "SELECT id, email, username, password_hash, role, status FROM users WHERE email = ? AND deleted_at IS NULL",
        [$email]
    );
    
    if ($user) {
        echo "Step 1: User found in database - PASS\n";
        echo "User ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Status: " . $user['status'] . "\n\n";
        
        // Step 2: Verify password
        if (password_verify($password, $user['password_hash'])) {
            echo "Step 2: Password verification - PASS\n\n";
            
            // Step 3: Check user status
            if ($user['status'] === 'active') {
                echo "Step 3: User status check - PASS (Active)\n\n";
                
                // Step 4: Simulate successful login
                echo "Login should be successful!\n";
                echo "User can access the system.\n\n";
                
                // Show what should happen in login.php
                echo "Expected behavior in login.php:\n";
                echo "- Session should be created\n";
                echo "- User should be redirected based on role\n";
                echo "- Premium user should go to user dashboard\n\n";
                
            } else {
                echo "Step 3: User status check - FAIL (Status: " . $user['status'] . ")\n";
                echo "User account is not active.\n\n";
            }
        } else {
            echo "Step 2: Password verification - FAIL\n";
            echo "Password does not match hash in database.\n\n";
        }
    } else {
        echo "Step 1: User found in database - FAIL\n";
        echo "User not found in database.\n\n";
    }
    
    // Test with username as well
    echo "Testing with Username instead of Email:\n";
    $username = 'premiumuser';
    
    $userByUsername = $db->fetchOne(
        "SELECT id, email, username, password_hash, role, status FROM users WHERE username = ? AND deleted_at IS NULL",
        [$username]
    );
    
    if ($userByUsername) {
        echo "User found by username: " . $userByUsername['email'] . "\n";
        
        if (password_verify($password, $userByUsername['password_hash'])) {
            echo "Password verification with username: PASS\n";
        } else {
            echo "Password verification with username: FAIL\n";
        }
    } else {
        echo "User not found by username\n";
    }
    
    echo "\n=== Troubleshooting Tips ===\n\n";
    echo "If login is still failing, check:\n";
    echo "1. Make sure you're using the exact email: premium@imei-bot.com\n";
    echo "2. Make sure you're using the exact password: PremiumPassword123!\n";
    echo "3. Check if there are any extra spaces in the login form\n";
    echo "4. Check if the login form is using email or username field\n";
    echo "5. Verify the login.php file is processing the correct field names\n\n";
    
    echo "Current working credentials:\n";
    echo "Email: premium@imei-bot.com\n";
    echo "Username: premiumuser\n";
    echo "Password: PremiumPassword123!\n";
    echo "Role: premium\n";
    echo "Status: active\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Complete ===\n";
?>
