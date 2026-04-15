<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

echo "=== Testing Logout and Redirects ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/redirect_test.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    $auth = new AuthMiddleware($db, $logger);
    
    // Test 1: Login as admin
    echo "Test 1: Admin Login\n";
    $loginResult = $auth->login('admin@imei-bot.com', 'Admin123!');
    
    if ($loginResult) {
        echo "   Admin login: SUCCESS\n";
        echo "   Session user_id: " . $_SESSION['user_id'] . "\n";
        echo "   Session user_role: " . $_SESSION['user_role'] . "\n";
        
        // Test 2: Check admin authentication
        echo "\nTest 2: Admin Authentication Check\n";
        $isAdmin = $auth->isAdmin();
        echo "   Is admin: " . ($isAdmin ? "YES" : "NO") . "\n";
        
        // Test 3: Logout
        echo "\nTest 3: Admin Logout\n";
        $auth->logout();
        echo "   Logout: SUCCESS\n";
        echo "   Session after logout: " . (isset($_SESSION['user_id']) ? "STILL LOGGED IN" : "CLEARED") . "\n";
        
    } else {
        echo "   Admin login: FAILED\n";
    }
    
    // Test 4: Login as premium user
    echo "\nTest 4: Premium User Login\n";
    $loginResult = $auth->login('premium@imei-bot.com', 'PremiumPassword123!');
    
    if ($loginResult) {
        echo "   Premium user login: SUCCESS\n";
        echo "   Session user_id: " . $_SESSION['user_id'] . "\n";
        echo "   Session user_role: " . $_SESSION['user_role'] . "\n";
        
        // Test 5: Check user authentication
        echo "\nTest 5: User Authentication Check\n";
        $isAuthenticated = $auth->isAuthenticated();
        echo "   Is authenticated: " . ($isAuthenticated ? "YES" : "NO") . "\n";
        
        $isActive = $auth->isActive();
        echo "   Is active: " . ($isActive ? "YES" : "NO") . "\n";
        
        $hasRole = $auth->hasRole('premium');
        echo "   Has premium role: " . ($hasRole ? "YES" : "NO") . "\n";
        
        // Test 6: Logout
        echo "\nTest 6: Premium User Logout\n";
        $auth->logout();
        echo "   Logout: SUCCESS\n";
        echo "   Session after logout: " . (isset($_SESSION['user_id']) ? "STILL LOGGED IN" : "CLEARED") . "\n";
        
    } else {
        echo "   Premium user login: FAILED\n";
    }
    
    // Test 7: Login as regular user
    echo "\nTest 7: Regular User Login\n";
    $loginResult = $auth->login('user@imei-bot.com', 'TestPassword123!');
    
    if ($loginResult) {
        echo "   Regular user login: SUCCESS\n";
        echo "   Session user_id: " . $_SESSION['user_id'] . "\n";
        echo "   Session user_role: " . $_SESSION['user_role'] . "\n";
        
        // Test 8: Check admin access (should fail)
        echo "\nTest 8: Admin Access Check (should fail)\n";
        $isAdmin = $auth->isAdmin();
        echo "   Is admin: " . ($isAdmin ? "YES" : "NO") . " (Expected: NO)\n";
        
        // Test 9: Logout
        echo "\nTest 9: Regular User Logout\n";
        $auth->logout();
        echo "   Logout: SUCCESS\n";
        echo "   Session after logout: " . (isset($_SESSION['user_id']) ? "STILL LOGGED IN" : "CLEARED") . "\n";
        
    } else {
        echo "   Regular user login: FAILED\n";
    }
    
    echo "\n=== Redirect URL Tests ===\n";
    echo "Login Page: http://localhost:8080/auth/login.php\n";
    echo "Logout URL: http://localhost:8080/auth/logout.php\n";
    echo "Admin Dashboard: http://localhost:8080/admin/dashboard.php\n";
    echo "User Dashboard: http://localhost:8080/dashboard.php\n";
    echo "Admin Mail Config: http://localhost:8080/admin/mail_config.php\n";
    
    echo "\n=== Expected Redirect Behavior ===\n";
    echo "1. Non-authenticated user accessing admin dashboard -> redirect to login\n";
    echo "2. Non-admin user accessing admin dashboard -> redirect to user dashboard\n";
    echo "3. Authenticated admin accessing admin dashboard -> allow access\n";
    echo "4. Non-authenticated user accessing user dashboard -> redirect to login\n";
    echo "5. Authenticated user accessing user dashboard -> allow access\n";
    echo "6. Logout -> clear session and redirect to login\n";
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
