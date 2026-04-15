<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

class MigrationRunner
{
    private Database $db;
    private Logger $logger;
    private string $migrationsPath;
    
    public function __construct()
    {
        $this->loadEnvironment();
        $this->db = Database::getInstance(
            $this->getDatabaseConfig(),
            new Logger($this->getMonitoringConfig())
        );
        $this->logger = new Logger($this->getMonitoringConfig());
        $this->migrationsPath = __DIR__ . '/migrations';
    }
    
    public function run(): void
    {
        echo "Starting migration process...\n";
        
        try {
            // Create migrations table if it doesn't exist
            $this->createMigrationsTable();
            
            // Get all migration files
            $migrationFiles = $this->getMigrationFiles();
            
            // Get executed migrations
            $executedMigrations = $this->getExecutedMigrations();
            
            // Run pending migrations
            $pendingMigrations = array_diff($migrationFiles, $executedMigrations);
            
            if (empty($pendingMigrations)) {
                echo "No pending migrations.\n";
                return;
            }
            
            echo "Found " . count($pendingMigrations) . " pending migrations.\n";
            
            foreach ($pendingMigrations as $migration) {
                $this->runMigration($migration);
            }
            
            echo "Migration completed successfully!\n";
            
        } catch (\Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->db->exec($sql);
    }
    
    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.sql');
        $migrationFiles = [];
        
        foreach ($files as $file) {
            $migrationFiles[] = basename($file);
        }
        
        sort($migrationFiles);
        return $migrationFiles;
    }
    
    private function getExecutedMigrations(): array
    {
        try {
            $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY migration");
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            // Table might not exist yet
            return [];
        }
    }
    
    private function runMigration(string $migrationFile): void
    {
        echo "Running migration: {$migrationFile}\n";
        
        $filePath = $this->migrationsPath . '/' . $migrationFile;
        
        if (!file_exists($filePath)) {
            throw new \Exception("Migration file not found: {$migrationFile}");
        }
        
        $sql = file_get_contents($filePath);
        
        if (empty($sql)) {
            echo "Skipping empty migration: {$migrationFile}\n";
            return;
        }
        
        try {
            // Execute migration
            $this->db->exec($sql);
            
            // Record migration
            $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
            $stmt->execute([':migration' => $migrationFile]);
            
            echo "Migration completed: {$migrationFile}\n";
            
        } catch (\Exception $e) {
            throw new \Exception("Migration {$migrationFile} failed: " . $e->getMessage());
        }
    }
    
    private function loadEnvironment(): void
    {
        $envFile = __DIR__ . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }
    
    private function getDatabaseConfig(): array
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        ];
    }
    
    private function getMonitoringConfig(): array
    {
        return [
            'level' => $_ENV['APP_DEBUG'] === 'true' ? 'debug' : 'info',
            'format' => 'json',
            'channels' => [
                'default' => 'logs/migrations.log',
            ],
        ];
    }
}

// Run migrations
try {
    $runner = new MigrationRunner();
    $runner->run();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
