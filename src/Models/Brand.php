<?php

declare(strict_types=1);

namespace IMEIBot\Models;

use IMEIBot\Core\Database;
use Ramsey\Uuid\Uuid;

class Brand
{
    private array $attributes = [];
    private Database $db;
    
    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge([
            'uuid' => Uuid::uuid4()->toString(),
            'name' => '',
            'slug' => '',
            'display_name' => '',
            'logo_url' => null,
            'description' => null,
            'website' => null,
            'country' => null,
            'founded_year' => null,
            'parent_company' => null,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 0,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
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
            INSERT INTO brands (
                uuid, name, slug, display_name, logo_url, description, website,
                country, founded_year, parent_company, is_popular, is_active,
                sort_order, meta_title, meta_description, meta_keywords,
                created_at, updated_at, deleted_at
            ) VALUES (
                :uuid, :name, :slug, :display_name, :logo_url, :description, :website,
                :country, :founded_year, :parent_company, :is_popular, :is_active,
                :sort_order, :meta_title, :meta_description, :meta_keywords,
                :created_at, :updated_at, :deleted_at
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':uuid' => $this->attributes['uuid'],
                ':name' => $this->attributes['name'],
                ':slug' => $this->attributes['slug'],
                ':display_name' => $this->attributes['display_name'],
                ':logo_url' => $this->attributes['logo_url'],
                ':description' => $this->attributes['description'],
                ':website' => $this->attributes['website'],
                ':country' => $this->attributes['country'],
                ':founded_year' => $this->attributes['founded_year'],
                ':parent_company' => $this->attributes['parent_company'],
                ':is_popular' => $this->attributes['is_popular'],
                ':is_active' => $this->attributes['is_active'],
                ':sort_order' => $this->attributes['sort_order'],
                ':meta_title' => $this->attributes['meta_title'],
                ':meta_description' => $this->attributes['meta_description'],
                ':meta_keywords' => $this->attributes['meta_keywords'],
                ':created_at' => $this->attributes['created_at'],
                ':updated_at' => $this->attributes['updated_at'],
                ':deleted_at' => $this->attributes['deleted_at'],
            ]);
            
            $this->attributes['id'] = $this->db->lastInsertId();
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Failed to insert brand: " . $e->getMessage());
            return false;
        }
    }
    
    private function update(): bool
    {
        $sql = "
            UPDATE brands SET
                name = :name,
                slug = :slug,
                display_name = :display_name,
                logo_url = :logo_url,
                description = :description,
                website = :website,
                country = :country,
                founded_year = :founded_year,
                parent_company = :parent_company,
                is_popular = :is_popular,
                is_active = :is_active,
                sort_order = :sort_order,
                meta_title = :meta_title,
                meta_description = :meta_description,
                meta_keywords = :meta_keywords,
                updated_at = :updated_at,
                deleted_at = :deleted_at
            WHERE id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([
                ':id' => $this->attributes['id'],
                ':name' => $this->attributes['name'],
                ':slug' => $this->attributes['slug'],
                ':display_name' => $this->attributes['display_name'],
                ':logo_url' => $this->attributes['logo_url'],
                ':description' => $this->attributes['description'],
                ':website' => $this->attributes['website'],
                ':country' => $this->attributes['country'],
                ':founded_year' => $this->attributes['founded_year'],
                ':parent_company' => $this->attributes['parent_company'],
                ':is_popular' => $this->attributes['is_popular'],
                ':is_active' => $this->attributes['is_active'],
                ':sort_order' => $this->attributes['sort_order'],
                ':meta_title' => $this->attributes['meta_title'],
                ':meta_description' => $this->attributes['meta_description'],
                ':meta_keywords' => $this->attributes['meta_keywords'],
                ':updated_at' => $this->attributes['updated_at'],
                ':deleted_at' => $this->attributes['deleted_at'],
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to update brand: " . $e->getMessage());
            return false;
        }
    }
    
    public static function find(int $id): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM brands WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return new self($data);
    }
    
    public static function findByUuid(string $uuid): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM brands WHERE uuid = :uuid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return new self($data);
    }
    
    public static function findBySlug(string $slug): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM brands WHERE slug = :slug AND is_active = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return new self($data);
    }
    
    public static function getAll(int $limit = null, int $offset = 0): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM brands WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
        $params = [];
        
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $brands = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $brands[] = new self($data);
        }
        
        return $brands;
    }
    
    public static function getPopular(int $limit = 20): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM brands WHERE is_active = 1 AND is_popular = 1 ORDER BY sort_order ASC, name ASC LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->execute([':limit' => $limit]);
        
        $brands = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $brands[] = new self($data);
        }
        
        return $brands;
    }
    
    public static function getByCountry(string $country): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM brands WHERE country = :country AND is_active = 1 ORDER BY sort_order ASC, name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':country' => $country]);
        
        $brands = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $brands[] = new self($data);
        }
        
        return $brands;
    }
    
    public function getModels(): array
    {
        $sql = "SELECT * FROM models WHERE brand_id = :brand_id AND is_active = 1 ORDER BY sort_order ASC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':brand_id' => $this->attributes['id']]);
        
        $models = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $models[] = new Model($data);
        }
        
        return $models;
    }
    
    public function getFileCount(): int
    {
        $sql = "SELECT COUNT(*) FROM files WHERE brand_id = :brand_id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':brand_id' => $this->attributes['id']]);
        
        return (int) $stmt->fetchColumn();
    }
    
    public function setAsPopular(bool $popular): bool
    {
        $this->attributes['is_popular'] = $popular;
        
        return $this->save();
    }
    
    public function setActive(bool $active): bool
    {
        $this->attributes['is_active'] = $active;
        
        return $this->save();
    }
    
    public function delete(): bool
    {
        if (!isset($this->attributes['id'])) {
            return false;
        }
        
        $sql = "DELETE FROM brands WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $this->attributes['id']]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete brand: " . $e->getMessage());
            return false;
        }
    }
    
    public function softDelete(): bool
    {
        $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
        $this->attributes['is_active'] = false;
        
        return $this->save();
    }
    
    public function restore(): bool
    {
        $this->attributes['deleted_at'] = null;
        $this->attributes['is_active'] = true;
        
        return $this->save();
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
