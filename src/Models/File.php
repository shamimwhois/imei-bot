<?php

declare(strict_types=1);

namespace IMEIBot\Models;

use IMEIBot\Core\Database;
use Ramsey\Uuid\Uuid;

class File
{
    private array $attributes = [];
    private Database $db;
    
    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge([
            'uuid' => Uuid::uuid4()->toString(),
            'brand_id' => null,
            'model_id' => null,
            'category' => 'firmware',
            'subcategory' => null,
            'name' => '',
            'slug' => '',
            'display_name' => '',
            'description' => null,
            'version' => null,
            'build_number' => null,
            'android_version' => null,
            'security_patch' => null,
            'region' => null,
            'carrier' => null,
            'network_lock' => 'unlocked',
            'file_type' => 'zip',
            'file_size_bytes' => 0,
            'file_size_mb' => 0,
            'file_hash_md5' => null,
            'file_hash_sha256' => null,
            'file_path' => '',
            'file_url' => null,
            'cdn_url' => null,
            'download_count' => 0,
            'view_count' => 0,
            'rating' => null,
            'rating_count' => 0,
            'is_featured' => false,
            'is_premium' => false,
            'is_verified' => false,
            'is_active' => true,
            'is_virus_scanned' => false,
            'virus_scan_result' => null,
            'upload_source' => 'admin',
            'uploaded_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
            'expires_at' => null,
            'access_level' => 'public',
            'download_limit' => null,
            'download_limit_period' => null,
            'tags' => null,
            'compatibility' => null,
            'requirements' => null,
            'changelog' => null,
            'installation_notes' => null,
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
            INSERT INTO files (
                uuid, brand_id, model_id, category, subcategory, name, slug, display_name,
                description, version, build_number, android_version, security_patch, region,
                carrier, network_lock, file_type, file_size_bytes, file_size_mb, file_hash_md5,
                file_hash_sha256, file_path, file_url, cdn_url, download_count, view_count,
                rating, rating_count, is_featured, is_premium, is_verified, is_active,
                is_virus_scanned, virus_scan_result, upload_source, uploaded_by, approved_by,
                approved_at, published_at, expires_at, access_level, download_limit,
                download_limit_period, tags, compatibility, requirements, changelog,
                installation_notes, meta_title, meta_description, meta_keywords,
                created_at, updated_at, deleted_at
            ) VALUES (
                :uuid, :brand_id, :model_id, :category, :subcategory, :name, :slug, :display_name,
                :description, :version, :build_number, :android_version, :security_patch, :region,
                :carrier, :network_lock, :file_type, :file_size_bytes, :file_size_mb, :file_hash_md5,
                :file_hash_sha256, :file_path, :file_url, :cdn_url, :download_count, :view_count,
                :rating, :rating_count, :is_featured, :is_premium, :is_verified, :is_active,
                :is_virus_scanned, :virus_scan_result, :upload_source, :uploaded_by, :approved_by,
                :approved_at, :published_at, :expires_at, :access_level, :download_limit,
                :download_limit_period, :tags, :compatibility, :requirements, :changelog,
                :installation_notes, :meta_title, :meta_description, :meta_keywords,
                :created_at, :updated_at, :deleted_at
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':uuid' => $this->attributes['uuid'],
                ':brand_id' => $this->attributes['brand_id'],
                ':model_id' => $this->attributes['model_id'],
                ':category' => $this->attributes['category'],
                ':subcategory' => $this->attributes['subcategory'],
                ':name' => $this->attributes['name'],
                ':slug' => $this->attributes['slug'],
                ':display_name' => $this->attributes['display_name'],
                ':description' => $this->attributes['description'],
                ':version' => $this->attributes['version'],
                ':build_number' => $this->attributes['build_number'],
                ':android_version' => $this->attributes['android_version'],
                ':security_patch' => $this->attributes['security_patch'],
                ':region' => $this->attributes['region'],
                ':carrier' => $this->attributes['carrier'],
                ':network_lock' => $this->attributes['network_lock'],
                ':file_type' => $this->attributes['file_type'],
                ':file_size_bytes' => $this->attributes['file_size_bytes'],
                ':file_size_mb' => $this->attributes['file_size_mb'],
                ':file_hash_md5' => $this->attributes['file_hash_md5'],
                ':file_hash_sha256' => $this->attributes['file_hash_sha256'],
                ':file_path' => $this->attributes['file_path'],
                ':file_url' => $this->attributes['file_url'],
                ':cdn_url' => $this->attributes['cdn_url'],
                ':download_count' => $this->attributes['download_count'],
                ':view_count' => $this->attributes['view_count'],
                ':rating' => $this->attributes['rating'],
                ':rating_count' => $this->attributes['rating_count'],
                ':is_featured' => $this->attributes['is_featured'],
                ':is_premium' => $this->attributes['is_premium'],
                ':is_verified' => $this->attributes['is_verified'],
                ':is_active' => $this->attributes['is_active'],
                ':is_virus_scanned' => $this->attributes['is_virus_scanned'],
                ':virus_scan_result' => $this->attributes['virus_scan_result'] ? json_encode($this->attributes['virus_scan_result']) : null,
                ':upload_source' => $this->attributes['upload_source'],
                ':uploaded_by' => $this->attributes['uploaded_by'],
                ':approved_by' => $this->attributes['approved_by'],
                ':approved_at' => $this->attributes['approved_at'],
                ':published_at' => $this->attributes['published_at'],
                ':expires_at' => $this->attributes['expires_at'],
                ':access_level' => $this->attributes['access_level'],
                ':download_limit' => $this->attributes['download_limit'],
                ':download_limit_period' => $this->attributes['download_limit_period'],
                ':tags' => $this->attributes['tags'] ? json_encode($this->attributes['tags']) : null,
                ':compatibility' => $this->attributes['compatibility'] ? json_encode($this->attributes['compatibility']) : null,
                ':requirements' => $this->attributes['requirements'] ? json_encode($this->attributes['requirements']) : null,
                ':changelog' => $this->attributes['changelog'],
                ':installation_notes' => $this->attributes['installation_notes'],
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
            error_log("Failed to insert file: " . $e->getMessage());
            return false;
        }
    }
    
    private function update(): bool
    {
        $sql = "
            UPDATE files SET
                brand_id = :brand_id,
                model_id = :model_id,
                category = :category,
                subcategory = :subcategory,
                name = :name,
                slug = :slug,
                display_name = :display_name,
                description = :description,
                version = :version,
                build_number = :build_number,
                android_version = :android_version,
                security_patch = :security_patch,
                region = :region,
                carrier = :carrier,
                network_lock = :network_lock,
                file_type = :file_type,
                file_size_bytes = :file_size_bytes,
                file_size_mb = :file_size_mb,
                file_hash_md5 = :file_hash_md5,
                file_hash_sha256 = :file_hash_sha256,
                file_path = :file_path,
                file_url = :file_url,
                cdn_url = :cdn_url,
                download_count = :download_count,
                view_count = :view_count,
                rating = :rating,
                rating_count = :rating_count,
                is_featured = :is_featured,
                is_premium = :is_premium,
                is_verified = :is_verified,
                is_active = :is_active,
                is_virus_scanned = :is_virus_scanned,
                virus_scan_result = :virus_scan_result,
                upload_source = :upload_source,
                uploaded_by = :uploaded_by,
                approved_by = :approved_by,
                approved_at = :approved_at,
                published_at = :published_at,
                expires_at = :expires_at,
                access_level = :access_level,
                download_limit = :download_limit,
                download_limit_period = :download_limit_period,
                tags = :tags,
                compatibility = :compatibility,
                requirements = :requirements,
                changelog = :changelog,
                installation_notes = :installation_notes,
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
                ':model_id' => $this->attributes['model_id'],
                ':category' => $this->attributes['category'],
                ':subcategory' => $this->attributes['subcategory'],
                ':name' => $this->attributes['name'],
                ':slug' => $this->attributes['slug'],
                ':display_name' => $this->attributes['display_name'],
                ':description' => $this->attributes['description'],
                ':version' => $this->attributes['version'],
                ':build_number' => $this->attributes['build_number'],
                ':android_version' => $this->attributes['android_version'],
                ':security_patch' => $this->attributes['security_patch'],
                ':region' => $this->attributes['region'],
                ':carrier' => $this->attributes['carrier'],
                ':network_lock' => $this->attributes['network_lock'],
                ':file_type' => $this->attributes['file_type'],
                ':file_size_bytes' => $this->attributes['file_size_bytes'],
                ':file_size_mb' => $this->attributes['file_size_mb'],
                ':file_hash_md5' => $this->attributes['file_hash_md5'],
                ':file_hash_sha256' => $this->attributes['file_hash_sha256'],
                ':file_path' => $this->attributes['file_path'],
                ':file_url' => $this->attributes['file_url'],
                ':cdn_url' => $this->attributes['cdn_url'],
                ':download_count' => $this->attributes['download_count'],
                ':view_count' => $this->attributes['view_count'],
                ':rating' => $this->attributes['rating'],
                ':rating_count' => $this->attributes['rating_count'],
                ':is_featured' => $this->attributes['is_featured'],
                ':is_premium' => $this->attributes['is_premium'],
                ':is_verified' => $this->attributes['is_verified'],
                ':is_active' => $this->attributes['is_active'],
                ':is_virus_scanned' => $this->attributes['is_virus_scanned'],
                ':virus_scan_result' => $this->attributes['virus_scan_result'] ? json_encode($this->attributes['virus_scan_result']) : null,
                ':upload_source' => $this->attributes['upload_source'],
                ':uploaded_by' => $this->attributes['uploaded_by'],
                ':approved_by' => $this->attributes['approved_by'],
                ':approved_at' => $this->attributes['approved_at'],
                ':published_at' => $this->attributes['published_at'],
                ':expires_at' => $this->attributes['expires_at'],
                ':access_level' => $this->attributes['access_level'],
                ':download_limit' => $this->attributes['download_limit'],
                ':download_limit_period' => $this->attributes['download_limit_period'],
                ':tags' => $this->attributes['tags'] ? json_encode($this->attributes['tags']) : null,
                ':compatibility' => $this->attributes['compatibility'] ? json_encode($this->attributes['compatibility']) : null,
                ':requirements' => $this->attributes['requirements'] ? json_encode($this->attributes['requirements']) : null,
                ':changelog' => $this->attributes['changelog'],
                ':installation_notes' => $this->attributes['installation_notes'],
                ':meta_title' => $this->attributes['meta_title'],
                ':meta_description' => $this->attributes['meta_description'],
                ':meta_keywords' => $this->attributes['meta_keywords'],
                ':updated_at' => $this->attributes['updated_at'],
                ':deleted_at' => $this->attributes['deleted_at'],
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to update file: " . $e->getMessage());
            return false;
        }
    }
    
    public static function find(int $id): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM files WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['virus_scan_result']) {
            $data['virus_scan_result'] = json_decode($data['virus_scan_result'], true);
        }
        if ($data['tags']) {
            $data['tags'] = json_decode($data['tags'], true);
        }
        if ($data['compatibility']) {
            $data['compatibility'] = json_decode($data['compatibility'], true);
        }
        if ($data['requirements']) {
            $data['requirements'] = json_decode($data['requirements'], true);
        }
        
        return new self($data);
    }
    
    public static function findByUuid(string $uuid): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM files WHERE uuid = :uuid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['virus_scan_result']) {
            $data['virus_scan_result'] = json_decode($data['virus_scan_result'], true);
        }
        if ($data['tags']) {
            $data['tags'] = json_decode($data['tags'], true);
        }
        if ($data['compatibility']) {
            $data['compatibility'] = json_decode($data['compatibility'], true);
        }
        if ($data['requirements']) {
            $data['requirements'] = json_decode($data['requirements'], true);
        }
        
        return new self($data);
    }
    
    public function publish(): bool
    {
        $this->attributes['published_at'] = date('Y-m-d H:i:s');
        $this->attributes['is_active'] = true;
        
        return $this->save();
    }
    
    public function unpublish(): bool
    {
        $this->attributes['published_at'] = null;
        $this->attributes['is_active'] = false;
        
        return $this->save();
    }
    
    public function setAsFeatured(bool $featured): bool
    {
        $this->attributes['is_featured'] = $featured;
        
        return $this->save();
    }
    
    public function setAsPremium(bool $premium): bool
    {
        $this->attributes['is_premium'] = $premium;
        
        return $this->save();
    }
    
    public function setAsVerified(bool $verified): bool
    {
        $this->attributes['is_verified'] = $verified;
        
        return $this->save();
    }
    
    public function incrementDownloadCount(): bool
    {
        $sql = "UPDATE files SET download_count = download_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute([':id' => $this->attributes['id']]);
            
            if ($result) {
                $this->attributes['download_count']++;
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Failed to increment download count: " . $e->getMessage());
            return false;
        }
    }
    
    public function incrementViewCount(): bool
    {
        $sql = "UPDATE files SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute([':id' => $this->attributes['id']]);
            
            if ($result) {
                $this->attributes['view_count']++;
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Failed to increment view count: " . $e->getMessage());
            return false;
        }
    }
    
    public function addRating(int $rating): bool
    {
        if ($rating < 1 || $rating > 5) {
            return false;
        }
        
        $currentRating = $this->attributes['rating'] ?? 0;
        $currentCount = $this->attributes['rating_count'] ?? 0;
        
        $newRating = (($currentRating * $currentCount) + $rating) / ($currentCount + 1);
        $newCount = $currentCount + 1;
        
        $sql = "UPDATE files SET rating = :rating, rating_count = :rating_count WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute([
                ':id' => $this->attributes['id'],
                ':rating' => $newRating,
                ':rating_count' => $newCount,
            ]);
            
            if ($result) {
                $this->attributes['rating'] = $newRating;
                $this->attributes['rating_count'] = $newCount;
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Failed to add rating: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete(): bool
    {
        if (!isset($this->attributes['id'])) {
            return false;
        }
        
        $sql = "DELETE FROM files WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $this->attributes['id']]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete file: " . $e->getMessage());
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
    
    public function isExpired(): bool
    {
        if (!$this->attributes['expires_at']) {
            return false;
        }
        
        return new \DateTime($this->attributes['expires_at']) < new \DateTime();
    }
    
    public function getFileSizeFormatted(): string
    {
        $bytes = $this->attributes['file_size_bytes'];
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public function getDownloadUrl(): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'https://your-domain.com';
        
        return "{$baseUrl}/download/{$this->attributes['uuid']}";
    }
    
    public function getDirectUrl(): string
    {
        if ($this->attributes['cdn_url']) {
            return $this->attributes['cdn_url'];
        }
        
        if ($this->attributes['file_url']) {
            return $this->attributes['file_url'];
        }
        
        $baseUrl = $_ENV['APP_URL'] ?? 'https://your-domain.com';
        $storagePath = $_ENV['STORAGE_PATH'] ?? 'storage/uploads';
        
        return "{$baseUrl}/{$storagePath}/{$this->attributes['file_path']}";
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
