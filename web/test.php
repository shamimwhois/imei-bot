<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;

try {
    echo "Testing database connection...\n";
    
    // Initialize services
    $db = Database::getInstance(
        require __DIR__ . '/../config/database.php',
        new Logger(require __DIR__ . '/../config/monitoring.php')
    );
    echo "Database connected successfully\n";
    
    $logger = new Logger(require __DIR__ . '/../config/monitoring.php');
    echo "Logger initialized successfully\n";
    
    $cache = new \IMEIBot\Core\Cache(require __DIR__ . '/../config/cache.php', $logger);
    echo "Cache initialized successfully\n";
    
    $imeiGenerator = new IMEIGenerator($db, $cache, $logger);
    echo "IMEIGenerator initialized successfully\n";
    
    $fileManager = new FileManager($db, $cache, $logger);
    echo "FileManager initialized successfully\n";
    
    $searchService = new SearchService($db, $cache, $logger);
    echo "SearchService initialized successfully\n";
    
    // Test database query
    $totalFiles = $db->fetchColumn("SELECT COUNT(*) FROM files WHERE is_active = 1");
    echo "Total files: $totalFiles\n";
    
    // Test FileManager methods
    $popularFiles = $fileManager->getPopularFiles(6);
    echo "Popular files count: " . count($popularFiles) . "\n";
    
    $recentFiles = $fileManager->getRecentFiles(6);
    echo "Recent files count: " . count($recentFiles) . "\n";
    
    echo "All tests passed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
