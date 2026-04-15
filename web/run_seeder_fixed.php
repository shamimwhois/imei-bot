<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Running Database Seeder ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/seeder.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Include the seeder class directly
    require_once __DIR__ . '/../database/seeders/UserSeeder_clean.php';
    
    // Create seeder instance
    $seeder = new UserSeeder($db, $logger);
    
    // Run seeder
    $seeder->run();
    
    echo "Database seeding completed successfully!\n";
    
    // Verify seeded data
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE email LIKE '%@imei-bot.com'");
    $fileCount = $db->fetchOne("SELECT COUNT(*) as count FROM files WHERE filename LIKE '%v%'");
    
    echo "Seeding Results:\n";
    echo "   Users created: $userCount[count]\n";
    echo "   Files created: $fileCount[count]\n";
    
    if ($userCount['count'] > 0 && $fileCount['count'] > 0) {
        echo "Seeding verification: PASSED\n";
    } else {
        echo "Seeding verification: FAILED\n";
    }
    
    // Display test credentials
    echo "\n=== Test Credentials ===\n";
    echo "Admin Login:\n";
    echo "   Email: admin@imei-bot.com\n";
    echo "   Password: Admin123!\n";
    echo "   Role: Super Admin\n\n";
    
    echo "Premium User Login:\n";
    echo "   Email: premium@imei-bot.com\n";
    echo "   Password: PremiumPassword123!\n";
    echo "   Role: Premium\n\n";
    
    echo "Regular User Login:\n";
    echo "   Email: user@imei-bot.com\n";
    echo "   Password: TestPassword123!\n";
    echo "   Role: User\n\n";
    
} catch (Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
}

echo "\n=== Seeding Complete ===\n";
?>
