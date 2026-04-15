<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Database\Seeders\UserSeeder_clean;

echo "=== Running Database Seeder ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/seeder.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Create seeder instance
    $seeder = new UserSeeder($db, $logger, null);
    
    // Run seeder
    $seeder->run();
    
    echo "✅ Database seeding completed successfully!\n";
    
    // Verify seeded data
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE email LIKE '%@imei-bot.com'");
    $fileCount = $db->fetchOne("SELECT COUNT(*) as count FROM files WHERE filename LIKE '%v%'");
    
    echo "📊 Seeding Results:\n";
    echo "   Users created: $userCount[count]\n";
    echo "   Files created: $fileCount[count]\n";
    
    if ($userCount['count'] > 0 && $fileCount['count'] > 0) {
        echo "✅ Seeding verification: PASSED\n";
    } else {
        echo "❌ Seeding verification: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
}

echo "\n=== Seeding Complete ===\n";
?>
