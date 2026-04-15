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
    
    echo "Users table columns:\n";
    $columns = $db->fetchAll('DESCRIBE users');
    
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
