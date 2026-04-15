<?php

declare(strict_types=1);

namespace IMEIBot\Repositories;

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

class FileRepository extends BaseRepository
{
    public function __construct(Database $db, Logger $logger)
    {
        parent::__construct($db, $logger, 'files');
    }

    /**
     * Get files with brand and model information
     */
    public function getWithRelations(int $limit = 20, int $offset = 0, array $filters = [], string $search = '', string $sort = 'created_at', string $order = 'desc'): array
    {
        try {
            $where = ['f.deleted_at IS NULL'];
            $params = [];

            // Apply filters
            foreach ($filters as $field => $value) {
                if ($value !== '') {
                    if ($field === 'brand_id' || $field === 'model_id') {
                        $where[] = "f.$field = :$field";
                    } else {
                        $where[] = "$field = :$field";
                    }
                    $params[":$field"] = $value;
                }
            }

            // Apply search
            if ($search !== '') {
                $where[] = "(f.name LIKE :search OR f.display_name LIKE :search OR f.description LIKE :search OR b.name LIKE :search OR m.name LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            $whereClause = implode(' AND ', $where);
            
            // Validate sort field
            $allowedSortFields = ['f.id', 'f.name', 'f.display_name', 'f.download_count', 'f.created_at', 'b.name', 'm.name'];
            if (!in_array($sort, $allowedSortFields)) {
                $sort = 'f.created_at';
            }
            
            $order = in_array(strtolower($order), ['asc', 'desc']) ? strtolower($order) : 'desc';

            // Get total count
            $countSql = "SELECT COUNT(*) FROM files f LEFT JOIN brands b ON f.brand_id = b.id LEFT JOIN models m ON f.model_id = m.id WHERE $whereClause";
            $total = (int) $this->db->fetchColumn($countSql, $params);

            // Get records
            $sql = "SELECT f.*, b.name as brand_name, m.name as model_name 
                    FROM files f 
                    LEFT JOIN brands b ON f.brand_id = b.id 
                    LEFT JOIN models m ON f.model_id = m.id 
                    WHERE $whereClause 
                    ORDER BY $sort $order 
                    LIMIT :limit OFFSET :offset";
            
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $records = $this->db->fetchAll($sql, $params);

            return [
                'data' => $records,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'page' => ($offset / $limit) + 1,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get files with relations", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get file by ID with relations
     */
    public function findWithRelations(int $id): ?array
    {
        try {
            $sql = "SELECT f.*, b.name as brand_name, m.name as model_name 
                    FROM files f 
                    LEFT JOIN brands b ON f.brand_id = b.id 
                    LEFT JOIN models m ON f.model_id = m.id 
                    WHERE f.id = :id AND f.deleted_at IS NULL";
            
            $result = $this->db->fetch($sql, [':id' => $id]);
            
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find file with relations", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get popular files
     */
    public function getPopularFiles(int $limit = 10): array
    {
        try {
            $sql = "SELECT f.*, b.name as brand_name, m.name as model_name 
                    FROM files f 
                    LEFT JOIN brands b ON f.brand_id = b.id 
                    LEFT JOIN models m ON f.model_id = m.id 
                    WHERE f.deleted_at IS NULL AND f.is_active = 1 
                    ORDER BY f.download_count DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, [':limit' => $limit]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get popular files", [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get recent files
     */
    public function getRecentFiles(int $limit = 10): array
    {
        try {
            $sql = "SELECT f.*, b.name as brand_name, m.name as model_name 
                    FROM files f 
                    LEFT JOIN brands b ON f.brand_id = b.id 
                    LEFT JOIN models m ON f.model_id = m.id 
                    WHERE f.deleted_at IS NULL AND f.is_active = 1 
                    ORDER BY f.created_at DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, [':limit' => $limit]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get recent files", [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get files by category
     */
    public function getByCategory(string $category, int $limit = 20): array
    {
        try {
            $sql = "SELECT f.*, b.name as brand_name, m.name as model_name 
                    FROM files f 
                    LEFT JOIN brands b ON f.brand_id = b.id 
                    LEFT JOIN models m ON f.model_id = m.id 
                    WHERE f.deleted_at IS NULL AND f.is_active = 1 AND f.category = :category 
                    ORDER BY f.download_count DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, [':category' => $category, ':limit' => $limit]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get files by category", [
                'category' => $category,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get files by brand
     */
    public function getByBrand(int $brandId, int $limit = 20): array
    {
        try {
            $sql = "SELECT f.*, b.name as brand_name, m.name as model_name 
                    FROM files f 
                    LEFT JOIN brands b ON f.brand_id = b.id 
                    LEFT JOIN models m ON f.model_id = m.id 
                    WHERE f.deleted_at IS NULL AND f.is_active = 1 AND f.brand_id = :brand_id 
                    ORDER BY f.download_count DESC 
                    LIMIT :limit";
            
            return $this->db->fetchAll($sql, [':brand_id' => $brandId, ':limit' => $limit]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get files by brand", [
                'brand_id' => $brandId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(int $id): bool
    {
        try {
            $sql = "UPDATE files SET download_count = download_count + 1, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL";
            $result = $this->db->execute($sql, [':id' => $id]);
            
            if ($result) {
                $this->logger->info("Download count incremented", ['id' => $id]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to increment download count", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get file statistics
     */
    public function getStatistics(): array
    {
        try {
            $stats = [];
            
            // Total files
            $stats['total'] = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL");
            
            // Active files
            $stats['active'] = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL AND is_active = 1");
            
            // Featured files
            $stats['featured'] = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL AND is_featured = 1");
            
            // Premium files
            $stats['premium'] = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL AND is_premium = 1");
            
            // Verified files
            $stats['verified'] = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL AND is_verified = 1");
            
            // Total downloads
            $stats['total_downloads'] = (int) $this->db->fetchColumn("SELECT COALESCE(SUM(download_count), 0) FROM files WHERE deleted_at IS NULL");
            
            // Total size
            $stats['total_size_mb'] = (float) $this->db->fetchColumn("SELECT COALESCE(SUM(file_size_mb), 0) FROM files WHERE deleted_at IS NULL");
            
            // Files by category
            $stats['by_category'] = $this->db->fetchAll("SELECT category, COUNT(*) as count FROM files WHERE deleted_at IS NULL GROUP BY category ORDER BY count DESC");
            
            // Recent uploads
            $stats['recent_uploads'] = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL AND DATE(created_at) = CURDATE()");
            
            return $stats;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get file statistics", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get searchable fields
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'display_name', 'description', 'version', 'category', 'subcategory'];
    }

    /**
     * Get sortable fields
     */
    protected function getSortableFields(): array
    {
        return ['id', 'name', 'display_name', 'category', 'download_count', 'file_size_mb', 'created_at', 'updated_at'];
    }
}
