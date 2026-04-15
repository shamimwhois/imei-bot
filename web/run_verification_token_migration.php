<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Running Verification Token Migration ===\n\n";

try {
    $logger = new Logger(['level' => 'info', 'format' => 'json', 'channels' => ['default' => 'logs/migration.log']]);
    $db = Database::getInstance(
        ['host' => 'localhost', 'port' => '3306', 'database' => 'imei_bot', 'username' => 'root', 'password' => ''],
        $logger
    );
    
    // Read and execute migration
    $migrationSql = file_get_contents(__DIR__ . '/../migrations/022_add_verification_token_to_users.sql');
    
    echo "Executing SQL:\n";
    echo $migrationSql . "\n\n";
    
    // Execute migration
    $db->query($migrationSql);
    
    echo "✅ Verification token column added to users table!\n";
    
    // Verify column exists
    $columnExists = $db->fetchOne("SHOW COLUMNS FROM users LIKE 'verification_token'");
    if ($columnExists) {
        echo "✅ Column verification: PASSED\n";
    } else {
        echo "❌ Column verification: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "\n=== Migration Complete ===\n";
