<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

echo "=== Testing Logout URL ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/logout_test.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    $auth = new AuthMiddleware($db, $logger);
    
    // Test login first
    echo "Step 1: Login as admin user\n";
    $loginResult = $auth->login('admin@imei-bot.com', 'Admin123!');
    
    if ($loginResult) {
        echo "   Login: SUCCESS\n";
        echo "   Session user_id: " . $_SESSION['user_id'] . "\n";
        
        // Check if logout file exists
        echo "\nStep 2: Check logout file exists\n";
        $logoutFile = __DIR__ . '/auth/logout.php';
        if (file_exists($logoutFile)) {
            echo "   Logout file exists: YES\n";
            echo "   File path: " . $logoutFile . "\n";
            echo "   File size: " . filesize($logoutFile) . " bytes\n";
        } else {
            echo "   Logout file exists: NO\n";
        }
        
        // Test logout functionality
        echo "\nStep 3: Test logout functionality\n";
        $auth->logout();
        echo "   Logout: SUCCESS\n";
        echo "   Session after logout: " . (isset($_SESSION['user_id']) ? "STILL LOGGED IN" : "CLEARED") . "\n";
        
    } else {
        echo "   Login: FAILED\n";
    }
    
    echo "\n=== URL Test Results ===\n";
    echo "Logout URL should work: http://localhost:8080/auth/logout\n";
    echo "Direct file path: " . __DIR__ . "/auth/logout.php\n";
    echo "File exists: " . (file_exists(__DIR__ . '/auth/logout.php') ? "YES" : "NO") . "\n";
    
    echo "\n=== Troubleshooting ===\n";
    echo "If logout URL still returns 404:\n";
    echo "1. Check if Apache mod_rewrite is enabled\n";
    echo "2. Check if .htaccess file is being processed\n";
    echo "3. Check if web server has permission to access auth directory\n";
    echo "4. Try accessing logout.php directly: http://localhost:8080/auth/logout.php\n";
    echo "5. Check Apache error logs for specific error messages\n";
    
    echo "\n=== Alternative Solutions ===\n";
    echo "If .htaccess routing doesn't work, try:\n";
    echo "1. Use direct .php extension: http://localhost:8080/auth/logout.php\n";
    echo "2. Create a symbolic link or copy logout.php to root\n";
    echo "3. Update web server configuration to allow directory access\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Complete ===\n";
?>
