<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Running Remember Tokens Migration ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/migration.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Read and execute migration
    $migrationSql = file_get_contents(__DIR__ . '/../migrations/020_create_remember_tokens_table.sql');
    
    echo "Executing SQL:\n";
    echo $migrationSql . "\n\n";
    
    // Execute the migration
    $db->query($migrationSql);
    
    echo "✅ Remember tokens table created successfully!\n";
    
    // Verify table exists
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'remember_tokens'");
    if ($tableExists) {
        echo "✅ Table verification: PASSED\n";
    } else {
        echo "❌ Table verification: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "\n=== Migration Complete ===\n";
