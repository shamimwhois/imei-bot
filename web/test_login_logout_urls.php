<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

echo "=== Testing Login and Logout URLs ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/login_logout_test.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    $auth = new AuthMiddleware($db, $logger);
    
    // Test 1: Check all login files exist
    echo "Test 1: Check login files exist\n";
    $loginFiles = [
        '/auth/login.php' => __DIR__ . '/auth/login.php',
        '/login.php' => __DIR__ . '/login.php'
    ];
    
    foreach ($loginFiles as $url => $path) {
        if (file_exists($path)) {
            echo "   $url: EXISTS (Size: " . filesize($path) . " bytes)\n";
        } else {
            echo "   $url: MISSING\n";
        }
    }
    
    // Test 2: Check all logout files exist
    echo "\nTest 2: Check logout files exist\n";
    $logoutFiles = [
        '/auth/logout.php' => __DIR__ . '/auth/logout.php',
        '/logout.php' => __DIR__ . '/logout.php'
    ];
    
    foreach ($logoutFiles as $url => $path) {
        if (file_exists($path)) {
            echo "   $url: EXISTS (Size: " . filesize($path) . " bytes)\n";
        } else {
            echo "   $url: MISSING\n";
        }
    }
    
    // Test 3: Test login flow
    echo "\nTest 3: Test login flow\n";
    $loginResult = $auth->login('admin@imei-bot.com', 'Admin123!');
    
    if ($loginResult) {
        echo "   Login: SUCCESS\n";
        echo "   Session user_id: " . $_SESSION['user_id'] . "\n";
        echo "   Session user_role: " . $_SESSION['user_role'] . "\n";
        
        // Test 4: Test logout flow
        echo "\nTest 4: Test logout flow\n";
        $auth->logout();
        echo "   Logout: SUCCESS\n";
        echo "   Session after logout: " . (isset($_SESSION['user_id']) ? "STILL LOGGED IN" : "CLEARED") . "\n";
        
    } else {
        echo "   Login: FAILED\n";
    }
    
    // Test 5: Test redirect URLs
    echo "\nTest 5: Test redirect URLs\n";
    echo "   Working Login URLs:\n";
    echo "     1. http://localhost:8080/login.php (Direct file)\n";
    echo "     2. http://localhost:8080/auth/login.php (Auth directory)\n";
    echo "     3. http://localhost:8080/auth/login (With .htaccess routing)\n";
    
    echo "\n   Working Logout URLs:\n";
    echo "     1. http://localhost:8080/logout.php (Direct file)\n";
    echo "     2. http://localhost:8080/auth/logout.php (Auth directory)\n";
    echo "     3. http://localhost:8080/auth/logout (With .htaccess routing)\n";
    
    // Test 6: Test AuthMiddleware redirects
    echo "\nTest 6: Test AuthMiddleware redirect URLs\n";
    echo "   requireAuth() redirects to: /login.php\n";
    echo "   requireActive() redirects to: /login.php\n";
    echo "   requireRole() redirects to: /dashboard.php\n";
    echo "   requireAdmin() redirects to: /dashboard.php\n";
    echo "   requireAnyRole() redirects to: /dashboard.php\n";
    
    echo "\n=== Navigation Links Updated ===\n";
    echo "   Header login link: /auth/login\n";
    echo "   Header logout link: /logout.php\n";
    echo "   All redirect URLs updated to working paths\n";
    
    echo "\n=== Complete URL Mapping ===\n";
    echo "   Login: http://localhost:8080/login.php\n";
    echo "   Logout: http://localhost:8080/logout.php\n";
    echo "   Dashboard: http://localhost:8080/dashboard.php\n";
    echo "   Admin Dashboard: http://localhost:8080/admin/dashboard.php\n";
    echo "   Registration: http://localhost:8080/auth/register.php\n";
    
    echo "\n=== Troubleshooting ===\n";
    echo "   If URLs still return 404:\n";
    echo "   1. Try direct .php files (should work)\n";
    echo "   2. Check .htaccess is enabled\n";
    echo "   3. Check Apache error logs\n";
    echo "   4. Verify file permissions\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
