<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

try {
    $db = Database::getInstance(
        require __DIR__ . '/../config/database.php',
        new Logger(require __DIR__ . '/../config/monitoring.php')
    );
    
    echo "Running missing migrations...\n";
    
    // Check what tables exist
    $existingTables = $db->fetchAll("SHOW TABLES");
    $existingTableNames = array_map(fn($table) => array_values($table)[0], $existingTables);
    
    echo "Existing tables: " . implode(', ', $existingTableNames) . "\n";
    
    // Required tables
    $requiredTables = [
        'users',
        'brands', 
        'models',
        'files',
        'commands',
        'settings',
        'analytics',
        'notifications',
        'sessions',
        'downloads',
        'imei_logs',
        'api_keys',
        'rate_limits',
        'audit_logs',
        'backups'
    ];
    
    $missingTables = array_diff($requiredTables, $existingTableNames);
    
    if (empty($missingTables)) {
        echo "All required tables exist.\n";
        exit;
    }
    
    echo "Missing tables: " . implode(', ', $missingTables) . "\n";
    
    // Run missing migrations
    $migrations = [
        'users' => '001_create_users_table.sql',
        'files' => '004_create_files_table.sql',
        'commands' => '005_create_commands_table.sql',
        'settings' => '006_create_settings_table.sql',
        'analytics' => '007_create_analytics_table.sql',
        'notifications' => '008_create_notifications_table.sql',
        'sessions' => '009_create_sessions_table.sql',
        'downloads' => '010_create_downloads_table.sql',
        'imei_logs' => '011_create_imei_logs_table.sql',
        'api_keys' => '012_create_api_keys_table.sql',
        'audit_logs' => '014_create_audit_logs_table.sql',
        'backups' => '015_create_backups_table.sql'
    ];
    
    foreach ($missingTables as $table) {
        if (isset($migrations[$table])) {
            $migrationFile = $migrations[$table];
            $filePath = __DIR__ . '/../migrations/' . $migrationFile;
            
            if (file_exists($filePath)) {
                echo "Running migration for $table: $migrationFile\n";
                
                $sql = file_get_contents($filePath);
                
                if (!empty($sql)) {
                    try {
                        $db->exec($sql);
                        echo "Migration completed: $migrationFile\n";
                    } catch (Exception $e) {
                        echo "Migration failed: $migrationFile - " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
    
    echo "Migration process completed!\n";
    
    // Verify tables were created
    $newTables = $db->fetchAll("SHOW TABLES");
    $newTableNames = array_map(fn($table) => array_values($table)[0], $newTables);
    
    echo "\nFinal table list:\n";
    foreach ($newTableNames as $tableName) {
        echo "- $tableName\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
