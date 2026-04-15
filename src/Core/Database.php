<?php

declare(strict_types=1);

namespace IMEIBot\Core;

use PDO;
use PDOException;
use IMEIBot\Core\Logger;

final class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    private array $config;
    private Logger $logger;
    
    private function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connect();
    }
    
    public static function getInstance(array $config, Logger $logger): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config, $logger);
        }
        
        return self::$instance;
    }
    
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'] ?? 'localhost',
                $this->config['port'] ?? '3306',
                $this->config['database'] ?? 'imei_bot',
                $this->config['charset'] ?? 'utf8mb4'
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => $this->config['persistent'] ?? false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
            
            // Add SSL options if configured
            if (!empty($this->config['ssl_ca'])) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $this->config['ssl_ca'];
            }
            
            $this->pdo = new PDO($dsn, $this->config['username'] ?? 'root', $this->config['password'] ?? '', $options);
            
            $this->logger->info('Database connection established', [
                'host' => $this->config['host'] ?? 'localhost',
                'database' => $this->config['database'] ?? 'imei_bot',
            ]);
            
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'host' => $this->config['host'] ?? 'localhost',
                'database' => $this->config['database'] ?? 'imei_bot',
            ]);
            
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
    
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
    
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            if ($executionTime > 1000) { // Log slow queries
                $this->logger->warning('Slow query detected', [
                    'sql' => $sql,
                    'params' => $params,
                    'execution_time_ms' => $executionTime,
                ]);
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logger->error('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Database query failed: ' . $e->getMessage(), 0, $e);
        }
    }
    
    public function prepare(string $sql): \PDOStatement
    {
        return $this->pdo->prepare($sql);
    }
    
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
    
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
    
    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        return $this->query($sql, $params)->fetchColumn($column);
    }
    
    public function fetchOne(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetch() ?: [];
    }
    
    public function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, $data);
        
        return $this->lastInsertId();
    }
    
    public function update(string $table, array $data, array $where): int
    {
        $setParts = array_map(fn($col) => "{$col} = :{$col}", array_keys($data));
        $whereParts = array_map(fn($col) => "{$col} = :where_{$col}", array_keys($where));
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );
        
        $params = array_merge($data, array_combine(
            array_map(fn($col) => "where_{$col}", array_keys($where)),
            $where
        ));
        
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    public function delete(string $table, array $where): int
    {
        $whereParts = array_map(fn($col) => "{$col} = :{$col}", array_keys($where));
        
        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereParts)
        );
        
        $stmt = $this->query($sql, $where);
        
        return $stmt->rowCount();
    }
    
    public function exists(string $table, array $where): bool
    {
        $whereParts = array_map(fn($col) => "{$col} = :{$col}", array_keys($where));
        
        $sql = sprintf(
            "SELECT 1 FROM %s WHERE %s LIMIT 1",
            $table,
            implode(' AND ', $whereParts)
        );
        
        return $this->fetchColumn($sql, $where) !== false;
    }
    
    public function count(string $table, array $where = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$table}";
        $params = [];
        
        if (!empty($where)) {
            $whereParts = array_map(fn($col) => "{$col} = :{$col}", array_keys($where));
            $sql .= " WHERE " . implode(' AND ', $whereParts);
            $params = $where;
        }
        
        return (int) $this->fetchColumn($sql, $params);
    }
    
    public function getTableInfo(string $table): array
    {
        $sql = "DESCRIBE {$table}";
        return $this->fetchAll($sql);
    }
    
    public function getTables(): array
    {
        $sql = "SHOW TABLES";
        $result = $this->fetchAll($sql);
        return array_column($result, array_key_first($result[0] ?? []));
    }
    
    public function healthCheck(): array
    {
        $startTime = microtime(true);
        
        try {
            $result = $this->fetch('SELECT 1 as test');
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'connection' => 'active',
                'database' => $this->config['database'] ?? 'imei_bot',
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time_ms' => null,
                'connection' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function exec(string $sql): int|false
    {
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            $this->logger->error('Database exec failed', [
                'sql' => $sql,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    public function close(): void
    {
        $this->pdo = null;
        self::$instance = null;
    }
    
    public function __destruct()
    {
        $this->close();
    }
    
    public function __clone()
    {
        throw new \RuntimeException('Cloning Database instance is not allowed');
    }
    
    public function __wakeup()
    {
        throw new \RuntimeException('Unserializing Database instance is not allowed');
    }
}
