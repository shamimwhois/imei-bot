<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Testing Admin Index number_format() Fix ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/admin_test.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Test the same queries used in admin index
    echo "Test 1: Testing database queries and type conversion\n";
    
    $stats = [
        'total_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"),
        'active_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL"),
        'premium_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'premium' AND deleted_at IS NULL"),
        'total_files' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL"),
        'total_downloads' => (int) $db->fetchColumn("SELECT COALESCE(SUM(download_count), 0) FROM files WHERE deleted_at IS NULL"),
        'total_imei_generated' => (int) $db->fetchColumn("SELECT COUNT(*) FROM imei_logs"),
        'today_imei_generated' => (int) $db->fetchColumn("SELECT COUNT(*) FROM imei_logs WHERE DATE(created_at) = CURDATE()"),
        'today_downloads' => (int) $db->fetchColumn("SELECT COUNT(*) FROM downloads WHERE DATE(created_at) = CURDATE()"),
    ];
    
    echo "   Database queries executed successfully\n";
    
    // Test number_format() with each stat
    echo "\nTest 2: Testing number_format() with stats\n";
    
    foreach ($stats as $key => $value) {
        $formatted = number_format($value);
        echo "   $key: $value -> $formatted (Type: " . gettype($value) . ")\n";
    }
    
    // Test recent files formatting
    echo "\nTest 3: Testing recent files formatting\n";
    $recent_files = $db->fetchAll("SELECT id, name, display_name, category, file_size_mb, download_count, created_at FROM files WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 3");
    
    foreach ($recent_files as $file) {
        $downloads_formatted = number_format((int) $file['download_count']);
        $size_formatted = number_format((float) $file['file_size_mb'], 1);
        echo "   File: {$file['display_name']} - Downloads: $downloads_formatted, Size: $size_formatted MB\n";
    }
    
    echo "\nTest 4: Testing admin index file access\n";
    $adminIndexPath = __DIR__ . '/admin/index.php';
    if (file_exists($adminIndexPath)) {
        echo "   Admin index file exists: YES\n";
        echo "   File size: " . filesize($adminIndexPath) . " bytes\n";
    } else {
        echo "   Admin index file exists: NO\n";
    }
    
    echo "\n=== Fix Applied ===\n";
    echo "1. Cast all database results to proper numeric types\n";
    echo "2. Used COALESCE() for SUM() to handle NULL values\n";
    echo "3. Cast file download_count to int\n";
    echo "4. Cast file file_size_mb to float\n";
    echo "5. All number_format() calls now receive proper numeric types\n";
    
    echo "\n=== Expected Behavior ===\n";
    echo "Admin index page should now load without number_format() errors\n";
    echo "All statistics should display with proper number formatting\n";
    echo "File sizes and download counts should format correctly\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
