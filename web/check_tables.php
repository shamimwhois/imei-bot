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
    
    echo "Checking database tables...\n";
    
    $tables = $db->fetchAll("SHOW TABLES");
    echo "Tables found:\n";
    
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "- $tableName\n";
    }
    
    if (empty($tables)) {
        echo "No tables found. Running migrations...\n";
        
        // Run the migrations
        $migrationFiles = glob(__DIR__ . '/../migrations/*.sql');
        sort($migrationFiles);
        
        foreach ($migrationFiles as $migrationFile) {
            $filename = basename($migrationFile);
            echo "Running migration: $filename\n";
            
            $sql = file_get_contents($migrationFile);
            
            if (!empty($sql)) {
                $db->exec($sql);
                echo "Migration completed: $filename\n";
            }
        }
        
        echo "All migrations completed!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
