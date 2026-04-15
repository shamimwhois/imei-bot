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
    
    echo "Adding Bangladeshi bank gateway...\n";
    
    // Run the migration
    $sql = file_get_contents(__DIR__ . '/../migrations/019_add_bangladeshi_bank_gateway.sql');
    $db->exec($sql);
    
    echo "Bangladeshi bank gateway added successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
