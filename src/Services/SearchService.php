<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use IMEIBot\Core\Database;
use IMEIBot\Core\Cache;
use IMEIBot\Core\Logger;

final class SearchService
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
            'search_limit' => 50,
            'cache_ttl' => 600, // 10 minutes
        ], $config);
    }
    
    /**
     * Advanced search for files with multiple filters
     */
    public function searchFiles(array $filters): array
    {
        $cacheKey = 'search_' . md5(json_encode($filters));
        
        return $this->cache->remember($cacheKey, $this->config['cache_ttl'], function () use ($filters) {
            $sql = "
                SELECT f.id, f.uuid, f.name, f.slug, f.display_name, f.description,
                       f.version, f.android_version, f.region, f.carrier, f.file_type,
                       f.file_size_mb, f.download_count, f.rating, f.is_featured,
                       f.is_premium, f.is_verified, f.published_at,
                       b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.is_active = 1
            ";
            
            $params = [];
            
            // Add search query
            if (!empty($filters['query'])) {
                $sql .= " AND (MATCH(f.name, f.description, f.tags) AGAINST(:query IN NATURAL LANGUAGE MODE)
                          OR f.name LIKE :query_like
                          OR f.description LIKE :query_like)";
                $params[':query'] = $filters['query'];
                $params[':query_like'] = "%{$filters['query']}%";
            }
            
            // Add category filter
            if (!empty($filters['category'])) {
                $sql .= " AND f.category = :category";
                $params[':category'] = $filters['category'];
            }
            
            // Add brand filter
            if (!empty($filters['brand_id'])) {
                $sql .= " AND f.brand_id = :brand_id";
                $params[':brand_id'] = $filters['brand_id'];
            }
            
            // Add model filter
            if (!empty($filters['model_id'])) {
                $sql .= " AND f.model_id = :model_id";
                $params[':model_id'] = $filters['model_id'];
            }
            
            // Add file type filter
            if (!empty($filters['file_type'])) {
                $sql .= " AND f.file_type = :file_type";
                $params[':file_type'] = $filters['file_type'];
            }
            
            // Add Android version filter
            if (!empty($filters['android_version'])) {
                $sql .= " AND f.android_version = :android_version";
                $params[':android_version'] = $filters['android_version'];
            }
            
            // Add region filter
            if (!empty($filters['region'])) {
                $sql .= " AND f.region = :region";
                $params[':region'] = $filters['region'];
            }
            
            // Add carrier filter
            if (!empty($filters['carrier'])) {
                $sql .= " AND f.carrier = :carrier";
                $params[':carrier'] = $filters['carrier'];
            }
            
            // Add premium filter
            if (isset($filters['is_premium'])) {
                $sql .= " AND f.is_premium = :is_premium";
                $params[':is_premium'] = $filters['is_premium'];
            }
            
            // Add verified filter
            if (isset($filters['is_verified'])) {
                $sql .= " AND f.is_verified = :is_verified";
                $params[':is_verified'] = $filters['is_verified'];
            }
            
            // Add featured filter
            if (isset($filters['is_featured'])) {
                $sql .= " AND f.is_featured = :is_featured";
                $params[':is_featured'] = $filters['is_featured'];
            }
            
            // Add date range filter
            if (!empty($filters['date_from'])) {
                $sql .= " AND f.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND f.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            // Add size range filter
            if (!empty($filters['size_min'])) {
                $sql .= " AND f.file_size_mb >= :size_min";
                $params[':size_min'] = $filters['size_min'];
            }
            
            if (!empty($filters['size_max'])) {
                $sql .= " AND f.file_size_mb <= :size_max";
                $params[':size_max'] = $filters['size_max'];
            }
            
            // Add rating filter
            if (!empty($filters['min_rating'])) {
                $sql .= " AND f.rating >= :min_rating";
                $params[':min_rating'] = $filters['min_rating'];
            }
            
            // Add download count filter
            if (!empty($filters['min_downloads'])) {
                $sql .= " AND f.download_count >= :min_downloads";
                $params[':min_downloads'] = $filters['min_downloads'];
            }
            
            // Add sorting
            $sortBy = $filters['sort_by'] ?? 'relevance';
            $sortOrder = $filters['sort_order'] ?? 'DESC';
            
            switch ($sortBy) {
                case 'name':
                    $sql .= " ORDER BY f.name {$sortOrder}";
                    break;
                case 'date':
                    $sql .= " ORDER BY f.created_at {$sortOrder}";
                    break;
                case 'size':
                    $sql .= " ORDER BY f.file_size_mb {$sortOrder}";
                    break;
                case 'downloads':
                    $sql .= " ORDER BY f.download_count {$sortOrder}";
                    break;
                case 'rating':
                    $sql .= " ORDER BY f.rating {$sortOrder}";
                    break;
                case 'relevance':
                default:
                    if (!empty($filters['query'])) {
                        $sql .= " ORDER BY (CASE WHEN MATCH(f.name, f.description, f.tags) AGAINST(:query IN NATURAL LANGUAGE MODE) THEN 1 ELSE 0 END) DESC, f.download_count DESC";
                    } else {
                        $sql .= " ORDER BY f.is_featured DESC, f.download_count DESC, f.created_at DESC";
                    }
                    break;
            }
            
            // Add pagination
            $limit = $filters['limit'] ?? $this->config['search_limit'];
            $offset = $filters['offset'] ?? 0;
            
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Format results
            foreach ($files as &$file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
                $file['metadata'] = $file['metadata'] ? json_decode($file['metadata'], true) : [];
                $file['compatibility'] = $file['compatibility'] ? json_decode($file['compatibility'], true) : [];
            }
            
            return $files;
        });
    }
    
    /**
     * Get search suggestions
     */
    public function getSearchSuggestions(string $query, string $type = 'files'): array
    {
        $cacheKey = "suggestions_{$type}_" . md5($query);
        
        return $this->cache->remember($cacheKey, 300, function () use ($query, $type) {
            switch ($type) {
                case 'brands':
                    return $this->getBrandSuggestions($query);
                case 'models':
                    return $this->getModelSuggestions($query);
                case 'files':
                default:
                    return $this->getFileSuggestions($query);
            }
        });
    }
    
    /**
     * Get file suggestions
     */
    private function getFileSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }
        
        $sql = "
            SELECT DISTINCT name, display_name, category, file_type
            FROM files
            WHERE is_active = 1
            AND (name LIKE :query_like OR display_name LIKE :query_like)
            ORDER BY download_count DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':query_like' => "%{$query}%"]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get brand suggestions
     */
    private function getBrandSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }
        
        $sql = "
            SELECT DISTINCT id, name, display_name, country
            FROM brands
            WHERE is_active = 1
            AND (name LIKE :query_like OR display_name LIKE :query_like)
            ORDER BY is_popular DESC, name ASC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':query_like' => "%{$query}%"]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get model suggestions
     */
    private function getModelSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }
        
        $sql = "
            SELECT DISTINCT m.id, m.name, m.display_name, m.model_number, b.name as brand_name
            FROM models m
            LEFT JOIN brands b ON m.brand_id = b.id
            WHERE m.is_active = 1
            AND (m.name LIKE :query_like OR m.display_name LIKE :query_like OR m.model_number LIKE :query_like)
            ORDER BY m.is_popular DESC, m.name ASC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':query_like' => "%{$query}%"]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get popular searches
     */
    public function getPopularSearches(int $limit = 20): array
    {
        $cacheKey = "popular_searches_{$limit}";
        
        return $this->cache->remember($cacheKey, 1800, function () use ($limit) {
            // This would typically track actual search queries from analytics
            // For now, return some popular search terms
            return [
                ['query' => 'samsung firmware', 'count' => 1250],
                ['query' => 'xiaomi rom', 'count' => 980],
                ['query' => 'huawei qcn', 'count' => 750],
                ['query' => 'iphone firmware', 'count' => 650],
                ['query' => 'oppo flash file', 'count' => 520],
                ['query' => 'vivo nvram', 'count' => 480],
                ['query' => 'realme firmware', 'count' => 420],
                ['query' => 'nokia flash file', 'count' => 380],
                ['query' => 'motorola firmware', 'count' => 350],
                ['query' => 'lg firmware', 'count' => 320],
            ];
        });
    }
    
    /**
     * Get related files
     */
    public function getRelatedFiles(int $fileId, int $limit = 10): array
    {
        $cacheKey = "related_files_{$fileId}_{$limit}";
        
        return $this->cache->remember($cacheKey, 3600, function () use ($fileId, $limit) {
            // Get the file to find related ones
            $fileSql = "SELECT brand_id, model_id, category, android_version FROM files WHERE id = :id";
            $fileStmt = $this->db->prepare($fileSql);
            $fileStmt->execute([':id' => $fileId]);
            $file = $fileStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$file) {
                return [];
            }
            
            $sql = "
                SELECT f.id, f.uuid, f.name, f.slug, f.display_name, f.description,
                       f.version, f.android_version, f.file_type, f.file_size_mb,
                       f.download_count, f.rating, f.published_at,
                       b.name as brand_name, m.name as model_name
                FROM files f
                LEFT JOIN brands b ON f.brand_id = b.id
                LEFT JOIN models m ON f.model_id = m.id
                WHERE f.is_active = 1 AND f.id != :file_id
                AND (
                    (f.brand_id = :brand_id AND f.model_id = :model_id) OR
                    (f.brand_id = :brand_id AND f.category = :category) OR
                    (f.category = :category AND f.android_version = :android_version)
                )
                ORDER BY f.download_count DESC, f.rating DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':file_id' => $fileId,
                ':brand_id' => $file['brand_id'],
                ':model_id' => $file['model_id'],
                ':category' => $file['category'],
                ':android_version' => $file['android_version'],
                ':limit' => $limit,
            ]);
            
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($files as &$file) {
                $file['file_size_formatted'] = $this->formatFileSize($file['file_size_mb'] * 1024 * 1024);
            }
            
            return $files;
        });
    }
    
    /**
     * Get search analytics
     */
    public function getSearchAnalytics(int $days = 30): array
    {
        $cacheKey = "search_analytics_{$days}";
        
        return $this->cache->remember($cacheKey, 300, function () use ($days) {
            $sql = "
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_searches,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(result_count) as avg_results
                FROM search_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':days' => $days]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Log search query
     */
    public function logSearch(int $userId, string $query, array $filters, int $resultCount): void
    {
        $sql = "
            INSERT INTO search_logs (user_id, query, filters, result_count, created_at)
            VALUES (:user_id, :query, :filters, :result_count, NOW())
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':query' => $query,
            ':filters' => json_encode($filters),
            ':result_count' => $resultCount,
        ]);
        
        $this->logger->info('Search logged', [
            'user_id' => $userId,
            'query' => $query,
            'result_count' => $resultCount,
        ]);
    }
    
    /**
     * Get trending searches
     */
    public function getTrendingSearches(int $hours = 24, int $limit = 10): array
    {
        $cacheKey = "trending_searches_{$hours}_{$limit}";
        
        return $this->cache->remember($cacheKey, 600, function () use ($hours, $limit) {
            $sql = "
                SELECT query, COUNT(*) as search_count
                FROM search_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                GROUP BY query
                HAVING search_count > 1
                ORDER BY search_count DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':hours' => $hours, ':limit' => $limit]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Get search filters
     */
    public function getAvailableFilters(): array
    {
        $cacheKey = 'available_filters';
        
        return $this->cache->remember($cacheKey, 3600, function () {
            $filters = [];
            
            // Get categories
            $categorySql = "SELECT DISTINCT category FROM files WHERE is_active = 1 ORDER BY category";
            $categoryStmt = $this->db->query($categorySql);
            $filters['categories'] = $categoryStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Get file types
            $typeSql = "SELECT DISTINCT file_type FROM files WHERE is_active = 1 ORDER BY file_type";
            $typeStmt = $this->db->query($typeSql);
            $filters['file_types'] = $typeStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Get Android versions
            $androidSql = "SELECT DISTINCT android_version FROM files WHERE is_active = 1 AND android_version IS NOT NULL ORDER BY android_version";
            $androidStmt = $this->db->query($androidSql);
            $filters['android_versions'] = $androidStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Get regions
            $regionSql = "SELECT DISTINCT region FROM files WHERE is_active = 1 AND region IS NOT NULL ORDER BY region";
            $regionStmt = $this->db->query($regionSql);
            $filters['regions'] = $regionStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Get carriers
            $carrierSql = "SELECT DISTINCT carrier FROM files WHERE is_active = 1 AND carrier IS NOT NULL ORDER BY carrier";
            $carrierStmt = $this->db->query($carrierSql);
            $filters['carriers'] = $carrierStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            return $filters;
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
     * Clear search cache
     */
    public function clearSearchCache(): void
    {
        $patterns = [
            'search_*',
            'suggestions_*',
            'related_files_*',
            'popular_searches_*',
            'trending_searches_*',
        ];
        
        foreach ($patterns as $pattern) {
            $keys = $this->cache->keys($pattern);
            foreach ($keys as $key) {
                $this->cache->delete($key);
            }
        }
        
        $this->logger->info('Search cache cleared');
    }
}
