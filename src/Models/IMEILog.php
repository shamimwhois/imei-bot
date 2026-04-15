<?php

declare(strict_types=1);

namespace IMEIBot\Models;

use IMEIBot\Core\Database;
use Ramsey\Uuid\Uuid;

class IMEILog
{
    private array $attributes = [];
    private Database $db;
    
    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge([
            'uuid' => Uuid::uuid4()->toString(),
            'user_id' => null,
            'platform' => 'web',
            'imei' => '',
            'prefix' => '',
            'type' => 'random',
            'quantity' => 1,
            'batch_uuid' => null,
            'is_valid' => true,
            'validation_method' => 'luhn',
            'generation_time_ms' => null,
            'ip_address' => null,
            'user_agent' => null,
            'metadata' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);
        
        $this->db = Database::getInstance(
            $this->getConfig('database'),
            $this->getLogger()
        );
    }
    
    public function save(): bool
    {
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($this->attributes['id'])) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }
    
    private function insert(): bool
    {
        $sql = "
            INSERT INTO imei_logs (
                uuid, user_id, platform, imei, prefix, type, quantity,
                batch_uuid, is_valid, validation_method, generation_time_ms,
                ip_address, user_agent, metadata, created_at, updated_at
            ) VALUES (
                :uuid, :user_id, :platform, :imei, :prefix, :type, :quantity,
                :batch_uuid, :is_valid, :validation_method, :generation_time_ms,
                :ip_address, :user_agent, :metadata, :created_at, :updated_at
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':uuid' => $this->attributes['uuid'],
                ':user_id' => $this->attributes['user_id'],
                ':platform' => $this->attributes['platform'],
                ':imei' => $this->attributes['imei'],
                ':prefix' => $this->attributes['prefix'],
                ':type' => $this->attributes['type'],
                ':quantity' => $this->attributes['quantity'],
                ':batch_uuid' => $this->attributes['batch_uuid'],
                ':is_valid' => $this->attributes['is_valid'],
                ':validation_method' => $this->attributes['validation_method'],
                ':generation_time_ms' => $this->attributes['generation_time_ms'],
                ':ip_address' => $this->attributes['ip_address'],
                ':user_agent' => $this->attributes['user_agent'],
                ':metadata' => $this->attributes['metadata'] ? json_encode($this->attributes['metadata']) : null,
                ':created_at' => $this->attributes['created_at'],
                ':updated_at' => $this->attributes['updated_at'],
            ]);
            
            $this->attributes['id'] = $this->db->lastInsertId();
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Failed to insert IMEI log: " . $e->getMessage());
            return false;
        }
    }
    
    private function update(): bool
    {
        $sql = "
            UPDATE imei_logs SET
                user_id = :user_id,
                platform = :platform,
                imei = :imei,
                prefix = :prefix,
                type = :type,
                quantity = :quantity,
                batch_uuid = :batch_uuid,
                is_valid = :is_valid,
                validation_method = :validation_method,
                generation_time_ms = :generation_time_ms,
                ip_address = :ip_address,
                user_agent = :user_agent,
                metadata = :metadata,
                updated_at = :updated_at
            WHERE id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([
                ':id' => $this->attributes['id'],
                ':user_id' => $this->attributes['user_id'],
                ':platform' => $this->attributes['platform'],
                ':imei' => $this->attributes['imei'],
                ':prefix' => $this->attributes['prefix'],
                ':type' => $this->attributes['type'],
                ':quantity' => $this->attributes['quantity'],
                ':batch_uuid' => $this->attributes['batch_uuid'],
                ':is_valid' => $this->attributes['is_valid'],
                ':validation_method' => $this->attributes['validation_method'],
                ':generation_time_ms' => $this->attributes['generation_time_ms'],
                ':ip_address' => $this->attributes['ip_address'],
                ':user_agent' => $this->attributes['user_agent'],
                ':metadata' => $this->attributes['metadata'] ? json_encode($this->attributes['metadata']) : null,
                ':updated_at' => $this->attributes['updated_at'],
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to update IMEI log: " . $e->getMessage());
            return false;
        }
    }
    
    public static function find(int $id): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM imei_logs WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['metadata']) {
            $data['metadata'] = json_decode($data['metadata'], true);
        }
        
        return new self($data);
    }
    
    public static function findByUuid(string $uuid): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM imei_logs WHERE uuid = :uuid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['metadata']) {
            $data['metadata'] = json_decode($data['metadata'], true);
        }
        
        return new self($data);
    }
    
    public static function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "
            SELECT * FROM imei_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset,
        ]);
        
        $logs = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($data['metadata']) {
                $data['metadata'] = json_decode($data['metadata'], true);
            }
            
            $logs[] = new self($data);
        }
        
        return $logs;
    }
    
    public static function countByUser(int $userId): int
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT COUNT(*) FROM imei_logs WHERE user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return (int) $stmt->fetchColumn();
    }
    
    public static function getStatistics(int $userId = null): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $whereClause = $userId ? "WHERE user_id = :user_id" : "";
        $params = $userId ? [':user_id' => $userId] : [];
        
        $sql = "
            SELECT 
                COUNT(*) as total_generated,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT prefix) as unique_prefixes,
                SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_count,
                AVG(generation_time_ms) as avg_generation_time,
                SUM(quantity) as total_quantity
            FROM imei_logs
            {$whereClause}
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public static function getPrefixDistribution(int $userId = null): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $whereClause = $userId ? "WHERE user_id = :user_id" : "";
        $params = $userId ? [':user_id' => $userId] : [];
        
        $sql = "
            SELECT prefix, COUNT(*) as count
            FROM imei_logs
            {$whereClause}
            GROUP BY prefix
            ORDER BY count DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function delete(): bool
    {
        if (!isset($this->attributes['id'])) {
            return false;
        }
        
        $sql = "DELETE FROM imei_logs WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $this->attributes['id']]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete IMEI log: " . $e->getMessage());
            return false;
        }
    }
    
    // Magic methods for attribute access
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
    
    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }
    
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
    
    public function toArray(): array
    {
        return $this->attributes;
    }
    
    private static function getConfig(string $key): array
    {
        // This would normally load from config files
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ];
    }
    
    private static function getLogger(): \IMEIBot\Core\Logger
    {
        // This would normally create a logger instance
        static $logger = null;
        
        if ($logger === null) {
            $logger = new \IMEIBot\Core\Logger([
                'level' => 'info',
                'format' => 'json',
                'channels' => [
                    'default' => 'logs/app.log',
                ],
            ]);
        }
        
        return $logger;
    }
}
