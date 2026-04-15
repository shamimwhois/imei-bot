<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Running Password Resets Migration ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/migration.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Read and execute migration
    $migrationSql = file_get_contents(__DIR__ . '/../migrations/021_create_password_resets_table.sql');
    
    echo "Executing SQL:\n";
    echo $migrationSql . "\n\n";
    
    // Execute migration
    $db->query($migrationSql);
    
    echo "✅ Password resets table created successfully!\n";
    
    // Verify table exists
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'password_resets'");
    if ($tableExists) {
        echo "✅ Table verification: PASSED\n";
    } else {
        echo "❌ Table verification: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "\n=== Migration Complete ===\n";
