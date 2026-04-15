<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

echo "=== Direct Login Test ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/direct_test.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    $auth = new AuthMiddleware($db, $logger);
    
    // Test premium user login
    echo "Testing Premium User Login:\n";
    echo "Email: premium@imei-bot.com\n";
    echo "Password: PremiumPassword123!\n\n";
    
    $loginResult = $auth->login('premium@imei-bot.com', 'PremiumPassword123!');
    
    if ($loginResult) {
        echo "LOGIN SUCCESS! \n";
        echo "User is now authenticated\n";
        echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
        echo "Session user_role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";
        
        // Get current user
        $currentUser = $auth->getCurrentUser();
        if ($currentUser) {
            echo "Current user data:\n";
            echo "  ID: " . $currentUser['id'] . "\n";
            echo "  Email: " . $currentUser['email'] . "\n";
            echo "  Username: " . $currentUser['username'] . "\n";
            echo "  Role: " . $currentUser['role'] . "\n";
            echo "  Status: " . $currentUser['status'] . "\n";
        }
        
        // Logout to clean up
        $auth->logout();
        
    } else {
        echo "LOGIN FAILED!\n";
        echo "Check the database logs for more details.\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Test admin user login
    echo "Testing Admin User Login:\n";
    echo "Email: admin@imei-bot.com\n";
    echo "Password: Admin123!\n\n";
    
    $loginResult = $auth->login('admin@imei-bot.com', 'Admin123!');
    
    if ($loginResult) {
        echo "LOGIN SUCCESS! \n";
        echo "User is now authenticated\n";
        echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
        echo "Session user_role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";
        
        // Get current user
        $currentUser = $auth->getCurrentUser();
        if ($currentUser) {
            echo "Current user data:\n";
            echo "  ID: " . $currentUser['id'] . "\n";
            echo "  Email: " . $currentUser['email'] . "\n";
            echo "  Username: " . $currentUser['username'] . "\n";
            echo "  Role: " . $currentUser['role'] . "\n";
            echo "  Status: " . $currentUser['status'] . "\n";
        }
        
        // Logout to clean up
        $auth->logout();
        
    } else {
        echo "LOGIN FAILED!\n";
        echo "Check the database logs for more details.\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Test regular user login
    echo "Testing Regular User Login:\n";
    echo "Email: user@imei-bot.com\n";
    echo "Password: TestPassword123!\n\n";
    
    $loginResult = $auth->login('user@imei-bot.com', 'TestPassword123!');
    
    if ($loginResult) {
        echo "LOGIN SUCCESS! \n";
        echo "User is now authenticated\n";
        echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
        echo "Session user_role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";
        
        // Get current user
        $currentUser = $auth->getCurrentUser();
        if ($currentUser) {
            echo "Current user data:\n";
            echo "  ID: " . $currentUser['id'] . "\n";
            echo "  Email: " . $currentUser['email'] . "\n";
            echo "  Username: " . $currentUser['username'] . "\n";
            echo "  Role: " . $currentUser['role'] . "\n";
            echo "  Status: " . $currentUser['status'] . "\n";
        }
        
        // Logout to clean up
        $auth->logout();
        
    } else {
        echo "LOGIN FAILED!\n";
        echo "Check the database logs for more details.\n";
    }
    
    echo "\n=== FINAL WORKING CREDENTIALS ===\n\n";
    echo "PREMIUM USER:\n";
    echo "  Email: premium@imei-bot.com\n";
    echo "  Password: PremiumPassword123!\n";
    echo "  Role: premium\n\n";
    
    echo "ADMIN USER:\n";
    echo "  Email: admin@imei-bot.com\n";
    echo "  Password: Admin123!\n";
    echo "  Role: super_admin\n\n";
    
    echo "REGULAR USER:\n";
    echo "  Email: user@imei-bot.com\n";
    echo "  Password: TestPassword123!\n";
    echo "  Role: user\n\n";
    
    echo "If login is still failing in the browser, check:\n";
    echo "1. Make sure there are no extra spaces in the login form\n";
    echo "2. Make sure you're using the exact email address\n";
    echo "3. Check browser console for JavaScript errors\n";
    echo "4. Check if the login form is submitting correctly\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Complete ===\n";
?>
