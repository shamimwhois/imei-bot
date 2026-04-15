<?php

declare(strict_types=1);

namespace IMEIBot\Models;

use IMEIBot\Core\Database;
use Ramsey\Uuid\Uuid;

class Model
{
    private array $attributes = [];
    private Database $db;
    
    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge([
            'uuid' => Uuid::uuid4()->toString(),
            'brand_id' => null,
            'name' => '',
            'slug' => '',
            'display_name' => '',
            'model_number' => null,
            'codename' => null,
            'description' => null,
            'image_url' => null,
            'release_date' => null,
            'device_type' => 'smartphone',
            'os_type' => 'android',
            'network_technology' => null,
            'sim_type' => null,
            'processor' => null,
            'ram_mb' => null,
            'storage_gb' => null,
            'display_size_inches' => null,
            'display_resolution' => null,
            'camera_megapixels' => null,
            'battery_mah' => null,
            'weight_grams' => null,
            'dimensions_mm' => null,
            'colors' => null,
            'price_range' => null,
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
            INSERT INTO models (
                uuid, brand_id, name, slug, display_name, model_number, codename,
                description, image_url, release_date, device_type, os_type,
                network_technology, sim_type, processor, ram_mb, storage_gb,
                display_size_inches, display_resolution, camera_megapixels, battery_mah,
                weight_grams, dimensions_mm, colors, price_range, is_popular, is_active,
                sort_order, meta_title, meta_description, meta_keywords,
                created_at, updated_at, deleted_at
            ) VALUES (
                :uuid, :brand_id, :name, :slug, :display_name, :model_number, :codename,
                :description, :image_url, :release_date, :device_type, :os_type,
                :network_technology, :sim_type, :processor, :ram_mb, :storage_gb,
                :display_size_inches, :display_resolution, :camera_megapixels, :battery_mah,
                :weight_grams, :dimensions_mm, :colors, :price_range, :is_popular, :is_active,
                :sort_order, :meta_title, :meta_description, :meta_keywords,
                :created_at, :updated_at, :deleted_at
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':uuid' => $this->attributes['uuid'],
                ':brand_id' => $this->attributes['brand_id'],
                ':name' => $this->attributes['name'],
                ':slug' => $this->attributes['slug'],
                ':display_name' => $this->attributes['display_name'],
                ':model_number' => $this->attributes['model_number'],
                ':codename' => $this->attributes['codename'],
                ':description' => $this->attributes['description'],
                ':image_url' => $this->attributes['image_url'],
                ':release_date' => $this->attributes['release_date'],
                ':device_type' => $this->attributes['device_type'],
                ':os_type' => $this->attributes['os_type'],
                ':network_technology' => $this->attributes['network_technology'],
                ':sim_type' => $this->attributes['sim_type'],
                ':processor' => $this->attributes['processor'],
                ':ram_mb' => $this->attributes['ram_mb'],
                ':storage_gb' => $this->attributes['storage_gb'],
                ':display_size_inches' => $this->attributes['display_size_inches'],
                ':display_resolution' => $this->attributes['display_resolution'],
                ':camera_megapixels' => $this->attributes['camera_megapixels'],
                ':battery_mah' => $this->attributes['battery_mah'],
                ':weight_grams' => $this->attributes['weight_grams'],
                ':dimensions_mm' => $this->attributes['dimensions_mm'],
                ':colors' => $this->attributes['colors'] ? json_encode($this->attributes['colors']) : null,
                ':price_range' => $this->attributes['price_range'],
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
            error_log("Failed to insert model: " . $e->getMessage());
            return false;
        }
    }
    
    private function update(): bool
    {
        $sql = "
            UPDATE models SET
                brand_id = :brand_id,
                name = :name,
                slug = :slug,
                display_name = :display_name,
                model_number = :model_number,
                codename = :codename,
                description = :description,
                image_url = :image_url,
                release_date = :release_date,
                device_type = :device_type,
                os_type = :os_type,
                network_technology = :network_technology,
                sim_type = :sim_type,
                processor = :processor,
                ram_mb = :ram_mb,
                storage_gb = :storage_gb,
                display_size_inches = :display_size_inches,
                display_resolution = :display_resolution,
                camera_megapixels = :camera_megapixels,
                battery_mah = :battery_mah,
                weight_grams = :weight_grams,
                dimensions_mm = :dimensions_mm,
                colors = :colors,
                price_range = :price_range,
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
                ':brand_id' => $this->attributes['brand_id'],
                ':name' => $this->attributes['name'],
                ':slug' => $this->attributes['slug'],
                ':display_name' => $this->attributes['display_name'],
                ':model_number' => $this->attributes['model_number'],
                ':codename' => $this->attributes['codename'],
                ':description' => $this->attributes['description'],
                ':image_url' => $this->attributes['image_url'],
                ':release_date' => $this->attributes['release_date'],
                ':device_type' => $this->attributes['device_type'],
                ':os_type' => $this->attributes['os_type'],
                ':network_technology' => $this->attributes['network_technology'],
                ':sim_type' => $this->attributes['sim_type'],
                ':processor' => $this->attributes['processor'],
                ':ram_mb' => $this->attributes['ram_mb'],
                ':storage_gb' => $this->attributes['storage_gb'],
                ':display_size_inches' => $this->attributes['display_size_inches'],
                ':display_resolution' => $this->attributes['display_resolution'],
                ':camera_megapixels' => $this->attributes['camera_megapixels'],
                ':battery_mah' => $this->attributes['battery_mah'],
                ':weight_grams' => $this->attributes['weight_grams'],
                ':dimensions_mm' => $this->attributes['dimensions_mm'],
                ':colors' => $this->attributes['colors'] ? json_encode($this->attributes['colors']) : null,
                ':price_range' => $this->attributes['price_range'],
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
            error_log("Failed to update model: " . $e->getMessage());
            return false;
        }
    }
    
    public static function find(int $id): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM models WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['colors']) {
            $data['colors'] = json_decode($data['colors'], true);
        }
        
        return new self($data);
    }
    
    public static function findByUuid(string $uuid): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM models WHERE uuid = :uuid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['colors']) {
            $data['colors'] = json_decode($data['colors'], true);
        }
        
        return new self($data);
    }
    
    public static function findBySlug(string $slug): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM models WHERE slug = :slug AND is_active = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['colors']) {
            $data['colors'] = json_decode($data['colors'], true);
        }
        
        return new self($data);
    }
    
    public static function getByBrand(int $brandId): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM models WHERE brand_id = :brand_id AND is_active = 1 ORDER BY sort_order ASC, name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':brand_id' => $brandId]);
        
        $models = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($data['colors']) {
                $data['colors'] = json_decode($data['colors'], true);
            }
            $models[] = new self($data);
        }
        
        return $models;
    }
    
    public static function getPopular(int $limit = 20): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM models WHERE is_active = 1 AND is_popular = 1 ORDER BY sort_order ASC, name ASC LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->execute([':limit' => $limit]);
        
        $models = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($data['colors']) {
                $data['colors'] = json_decode($data['colors'], true);
            }
            $models[] = new self($data);
        }
        
        return $models;
    }
    
    public static function getByDeviceType(string $deviceType): array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM models WHERE device_type = :device_type AND is_active = 1 ORDER BY sort_order ASC, name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':device_type' => $deviceType]);
        
        $models = [];
        
        while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($data['colors']) {
                $data['colors'] = json_decode($data['colors'], true);
            }
            $models[] = new self($data);
        }
        
        return $models;
    }
    
    public function getBrand(): ?Brand
    {
        if (!$this->attributes['brand_id']) {
            return null;
        }
        
        return Brand::find($this->attributes['brand_id']);
    }
    
    public function getFileCount(): int
    {
        $sql = "SELECT COUNT(*) FROM files WHERE model_id = :model_id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':model_id' => $this->attributes['id']]);
        
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
        
        $sql = "DELETE FROM models WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $this->attributes['id']]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete model: " . $e->getMessage());
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
    
    public function getFormattedSpecs(): array
    {
        return [
            'processor' => $this->attributes['processor'],
            'ram' => $this->attributes['ram_mb'] ? $this->attributes['ram_mb'] . ' MB' : null,
            'storage' => $this->attributes['storage_gb'] ? $this->attributes['storage_gb'] . ' GB' : null,
            'display' => $this->attributes['display_size_inches'] ? $this->attributes['display_size_inches'] . '"' : null,
            'resolution' => $this->attributes['display_resolution'],
            'camera' => $this->attributes['camera_megapixels'] ? $this->attributes['camera_megapixels'] . ' MP' : null,
            'battery' => $this->attributes['battery_mah'] ? $this->attributes['battery_mah'] . ' mAh' : null,
            'weight' => $this->attributes['weight_grams'] ? $this->attributes['weight_grams'] . ' g' : null,
            'dimensions' => $this->attributes['dimensions_mm'],
            'colors' => $this->attributes['colors'],
            'release_date' => $this->attributes['release_date'],
            'price_range' => $this->attributes['price_range'],
        ];
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
