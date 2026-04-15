<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use IMEIBot\Core\Database;
use IMEIBot\Core\Cache;
use IMEIBot\Core\Logger;
use IMEIBot\Models\File;
use IMEIBot\Models\Brand;
use IMEIBot\Models\Model;
use Ramsey\Uuid\Uuid;

final class FileManager
{
    private Database $db;
    private Cache $cache;
    private Logger $logger;
    private array $config;
    
    public function __construct(
        Database $db,
        Cache $cache,
        Logger $logger,
        array $config = []
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = array_merge([
            'upload_path' => 'storage/uploads',
            'max_file_size' => 100 * 1024 * 1024, // 100MB
            'allowed_types' => ['zip', '7z', 'rar', 'tar', 'gz', 'bin', 'img', 'cert', 'qcn', 'nvram'],
            'scan_files' => true,
        ], $config);
    }
    
    /**
     * Get popular brands for display
     */
    public function getPopularBrands(int $limit = 20): array
    {
        $cacheKey = 'popular_brands';
        
        return $this->cache->remember($cacheKey, 3600, function () use ($limit) {
            $sql = "
                SELECT id, name, slug, display_name, logo_url, is_popular
                FROM brands
                WHERE is_active = 1 AND is_popular = 1
                ORDER BY sort_order ASC, name ASC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':limit' => $limit]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Get brands by category
     */
    public function getBrandsByCategory(string $category = null): array
    {
        $cacheKey = "brands_by_category_{$category}";
        
        return $this->cache->remember($cacheKey, 3600, function () use ($category) {
            $sql = "
                SELECT id, name, slug, display_name, logo_url, is_popular, country
                FROM brands
                WHERE is_active = 1
            ";
            
            $params = [];
            
            if ($category) {
                $sql .= " AND category = :category";
                $params[':category'] = $category;
            }
            
            $sql .= " ORDER BY sort_order ASC, name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Get models by brand
     */
    public function getModelsByBrand(int $brandId): array
    {
        $cacheKey = "models_by_brand_{$brandId}";
        
        return $this->cache->remember($cacheKey, 3600, function () use ($brandId) {
            $sql = "
                SELECT id, name, slug, display_name, model_number, device_type, os_type,
                       release_date, is_popular, image_url
                FROM models
                WHERE brand_id = :brand_id AND is_active = 1
                ORDER BY sort_order ASC, name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':brand_id' => $brandId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Get files by category, brand, and model
     */
    public function getFiles(string $category = null, int $brandId = null, int $modelId = null, int $limit = 50, int $offset = 0): array
    {
        $cacheKey = "files_{$category}_{$brandId}_{$modelId}_{$limit}_{$offset}";
        
        return $this->cache->remember($cacheKey, 1800, function () use ($category, $brandId, $modelId, $limit, $offset) {
            $sql = "
                SELECT f.id, f.uuid, f.name, f.slug, f.display_name, f.description, f.version,
                       f.android_version, f.region, f.carrier, f.file_type, f.file_size_mb,
                       f.download_count, f.rating, f.is_featured, f.is_premium, f.is_verified,
                       f.published_at, b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.is_active = 1
            ";
            
            $params = [];
            
            if ($category) {
                $sql .= " AND f.category = :category";
                $params[':category'] = $category;
            }
            
            if ($brandId) {
                $sql .= " AND f.brand_id = :brand_id";
                $params[':brand_id'] = $brandId;
            }
            
            if ($modelId) {
                $sql .= " AND f.model_id = :model_id";
                $params[':model_id'] = $modelId;
            }
            
            $sql .= " ORDER BY f.is_featured DESC, f.download_count DESC, f.created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Format file sizes
            foreach ($files as &$file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
            }
            
            return $files;
        });
    }
    
    /**
     * Get recent files
     */
    public function getRecentFiles(int $limit = 20): array
    {
        $cacheKey = "recent_files_{$limit}";
        
        return $this->cache->remember($cacheKey, 600, function () use ($limit) {
            $sql = "
                SELECT f.id, f.uuid, f.name, f.slug, f.display_name, f.description,
                       f.version, f.android_version, f.file_type, f.file_size_mb,
                       f.download_count, f.published_at, b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.is_active = 1 AND f.published_at IS NOT NULL
                ORDER BY f.published_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':limit' => $limit]);
            
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($files as &$file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
            }
            
            return $files;
        });
    }
    
    /**
     * Get popular files
     */
    public function getPopularFiles(int $limit = 20): array
    {
        $cacheKey = "popular_files_{$limit}";
        
        return $this->cache->remember($cacheKey, 1800, function () use ($limit) {
            $sql = "
                SELECT f.id, f.uuid, f.name, f.slug, f.display_name, f.description,
                       f.version, f.android_version, f.file_type, f.file_size_mb,
                       f.download_count, f.rating, f.published_at, b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.is_active = 1 AND f.published_at IS NOT NULL
                ORDER BY f.download_count DESC, f.rating DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':limit' => $limit]);
            
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($files as &$file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
            }
            
            return $files;
        });
    }
    
    /**
     * Search files
     */
    public function searchFiles(string $query, string $category = null, int $brandId = null, int $limit = 50): array
    {
        $cacheKey = "search_" . md5($query . $category . $brandId . $limit);
        
        return $this->cache->remember($cacheKey, 600, function () use ($query, $category, $brandId, $limit) {
            $sql = "
                SELECT f.id, f.uuid, f.name, f.slug, f.display_name, f.description,
                       f.version, f.android_version, f.region, f.file_type, f.file_size_mb,
                       f.download_count, f.rating, f.published_at, b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.is_active = 1 AND f.published_at IS NOT NULL
                AND (MATCH(f.name, f.description, f.tags) AGAINST(:query IN NATURAL LANGUAGE MODE)
                     OR f.name LIKE :query_like
                     OR f.description LIKE :query_like)
            ";
            
            $params = [
                ':query' => $query,
                ':query_like' => "%{$query}%",
            ];
            
            if ($category) {
                $sql .= " AND f.category = :category";
                $params[':category'] = $category;
            }
            
            if ($brandId) {
                $sql .= " AND f.brand_id = :brand_id";
                $params[':brand_id'] = $brandId;
            }
            
            $sql .= " ORDER BY f.download_count DESC, f.rating DESC
                     LIMIT :limit";
            
            $params[':limit'] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($files as &$file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
            }
            
            return $files;
        });
    }
    
    /**
     * Get file by ID
     */
    public function getFile(int $id): ?array
    {
        $cacheKey = "file_{$id}";
        
        return $this->cache->remember($cacheKey, 3600, function () use ($id) {
            $sql = "
                SELECT f.*, b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.id = :id AND f.is_active = 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $file = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
                $file['metadata'] = $file['metadata'] ? json_decode($file['metadata'], true) : [];
                $file['compatibility'] = $file['compatibility'] ? json_decode($file['compatibility'], true) : [];
            }
            
            return $file ?: null;
        });
    }
    
    /**
     * Get file by UUID
     */
    public function getFileByUuid(string $uuid): ?array
    {
        $cacheKey = "file_uuid_{$uuid}";
        
        return $this->cache->remember($cacheKey, 3600, function () use ($uuid) {
            $sql = "
                SELECT f.*, b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.uuid = :uuid AND f.is_active = 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uuid' => $uuid]);
            
            $file = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
                $file['metadata'] = $file['metadata'] ? json_decode($file['metadata'], true) : [];
                $file['compatibility'] = $file['compatibility'] ? json_decode($file['compatibility'], true) : [];
            }
            
            return $file ?: null;
        });
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount(int $fileId): bool
    {
        try {
            $sql = "UPDATE files SET download_count = download_count + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $fileId]);
            
            // Clear cache
            $this->cache->delete("file_{$fileId}");
            $this->cache->delete('popular_files_20');
            $this->cache->delete('popular_files_50');
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to increment download count', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Get file statistics
     */
    public function getFileStatistics(): array
    {
        $cacheKey = 'file_statistics';
        
        return $this->cache->remember($cacheKey, 300, function () {
            $sql = "
                SELECT 
                    COUNT(*) as total_files,
                    COUNT(DISTINCT brand_id) as unique_brands,
                    COUNT(DISTINCT model_id) as unique_models,
                    SUM(download_count) as total_downloads,
                    AVG(rating) as avg_rating,
                    SUM(file_size_mb) as total_size_mb
                FROM files
                WHERE is_active = 1
            ";
            
            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get category distribution
            $categorySql = "
                SELECT category, COUNT(*) as count, SUM(download_count) as downloads
                FROM files
                WHERE is_active = 1
                GROUP BY category
                ORDER BY count DESC
            ";
            
            $categoryStmt = $this->db->query($categorySql);
            $stats['category_distribution'] = $categoryStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Get file type distribution
            $typeSql = "
                SELECT file_type, COUNT(*) as count, SUM(download_count) as downloads
                FROM files
                WHERE is_active = 1
                GROUP BY file_type
                ORDER BY count DESC
            ";
            
            $typeStmt = $this->db->query($typeSql);
            $stats['type_distribution'] = $typeStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $stats;
        });
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload(array $fileData): array
    {
        $errors = [];
        
        // Check file size
        if ($fileData['size'] > $this->config['max_file_size']) {
            $errors[] = 'File size exceeds maximum limit of ' . $this->formatFileSize($this->config['max_file_size']);
        }
        
        // Check file type
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['allowed_types'])) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $this->config['allowed_types']);
        }
        
        // Check for viruses if enabled
        if ($this->config['scan_files']) {
            // This would integrate with a virus scanning service
            // For now, just log that scanning would happen
            $this->logger->info('File scan would be performed', [
                'file_name' => $fileData['name'],
                'file_size' => $fileData['size'],
            ]);
        }
        
        return $errors;
    }
    
    /**
     * Get download URL for file
     */
    public function getDownloadUrl(string $fileUuid, int $userId = null): string
    {
        $baseUrl = $this->config['base_url'] ?? 'https://your-domain.com';
        
        // In a real implementation, this would generate a secure, time-limited download token
        $token = $this->generateDownloadToken($fileUuid, $userId);
        
        return "{$baseUrl}/download/{$fileUuid}?token={$token}";
    }
    
    /**
     * Generate download token
     */
    private function generateDownloadToken(string $fileUuid, int $userId = null): string
    {
        $payload = [
            'file_uuid' => $fileUuid,
            'user_id' => $userId,
            'expires_at' => time() + 3600, // 1 hour expiry
            'random' => bin2hex(random_bytes(16)),
        ];
        
        return base64_encode(json_encode($payload));
    }
    
    /**
     * Validate download token
     */
    public function validateDownloadToken(string $token): ?array
    {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || !isset($payload['file_uuid']) || !isset($payload['expires_at'])) {
                return null;
            }
            
            if ($payload['expires_at'] < time()) {
                return null;
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to validate download token', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Get files by category for admin
     */
    public function getFilesByCategoryForAdmin(string $category, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT f.*, b.name as brand_name, m.name as model_name
            FROM files f
            LEFT JOIN brands b ON f.brand_id = b.id
            LEFT JOIN models m ON f.model_id = m.id
            WHERE f.category = :category
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category' => $category,
            ':limit' => $limit,
            ':offset' => $offset,
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get premium files
     */
    public function getPremiumFiles(int $limit = 20): array
    {
        $cacheKey = "premium_files_{$limit}";
        
        return $this->cache->remember($cacheKey, 1800, function () use ($limit) {
            $sql = "
                SELECT f.id, f.uuid, f.name, f.slug, f.display_name, f.description,
                       f.version, f.android_version, f.file_type, f.file_size_mb,
                       f.download_count, f.rating, f.published_at, b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.is_active = 1 AND f.is_premium = 1 AND f.published_at IS NOT NULL
                ORDER BY f.download_count DESC, f.rating DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':limit' => $limit]);
            
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($files as &$file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
            }
            
            return $files;
        });
    }
}
