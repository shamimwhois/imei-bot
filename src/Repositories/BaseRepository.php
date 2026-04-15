<?php

declare(strict_types=1);

namespace IMEIBot\Repositories;

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

abstract class BaseRepository
{
    protected Database $db;
    protected Logger $logger;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(Database $db, Logger $logger, string $table)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->table = $table;
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id AND deleted_at IS NULL";
            $result = $this->db->fetch($sql, [':id' => $id]);
            
            if ($result) {
                $this->logger->debug("Record found", ['table' => $this->table, 'id' => $id]);
                return $result;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find record", [
                'table' => $this->table,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Find a record by UUID
     */
    public function findByUuid(string $uuid): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE uuid = :uuid AND deleted_at IS NULL";
            $result = $this->db->fetch($sql, [':uuid' => $uuid]);
            
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find record by UUID", [
                'table' => $this->table,
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all records with pagination
     */
    public function paginate(int $limit = 20, int $offset = 0, array $filters = [], string $search = '', string $sort = 'created_at', string $order = 'desc'): array
    {
        try {
            $where = ['deleted_at IS NULL'];
            $params = [];

            // Apply filters
            foreach ($filters as $field => $value) {
                if ($value !== '') {
                    $where[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }

            // Apply search
            if ($search !== '') {
                $searchFields = $this->getSearchableFields();
                if (!empty($searchFields)) {
                    $searchConditions = [];
                    foreach ($searchFields as $field) {
                        $searchConditions[] = "$field LIKE :search";
                    }
                    $where[] = '(' . implode(' OR ', $searchConditions) . ')';
                    $params[':search'] = '%' . $search . '%';
                }
            }

            $whereClause = implode(' AND ', $where);
            
            // Validate sort field
            $allowedSortFields = $this->getSortableFields();
            if (!in_array($sort, $allowedSortFields)) {
                $sort = 'created_at';
            }
            
            // Validate order
            $order = in_array(strtolower($order), ['asc', 'desc']) ? strtolower($order) : 'desc';

            // Get total count
            $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE $whereClause";
            $total = (int) $this->db->fetchColumn($countSql, $params);

            // Get records
            $sql = "SELECT * FROM {$this->table} WHERE $whereClause ORDER BY $sort $order LIMIT :limit OFFSET :offset";
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
            $this->logger->error("Failed to paginate records", [
                'table' => $this->table,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new record
     */
    public function create(array $data): int
    {
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $fields = array_keys($data);
            $placeholders = array_map(fn($field) => ":$field", $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $params = array_combine(array_map(fn($field) => ":$field", $fields), array_values($data));
            
            $this->db->execute($sql, $params);
            $id = (int) $this->db->lastInsertId();
            
            $this->logger->info("Record created", [
                'table' => $this->table,
                'id' => $id,
                'data' => $data
            ]);
            
            return $id;
        } catch (\Exception $e) {
            $this->logger->error("Failed to create record", [
                'table' => $this->table,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');

            $fields = array_keys($data);
            $setClause = implode(', ', array_map(fn($field) => "$field = :$field", $fields));
            
            $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id AND deleted_at IS NULL";
            
            $params = array_combine(array_map(fn($field) => ":$field", $fields), array_values($data));
            $params[':id'] = $id;
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                $this->logger->info("Record updated", [
                    'table' => $this->table,
                    'id' => $id,
                    'data' => $data
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to update record", [
                'table' => $this->table,
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Soft delete a record
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET deleted_at = NOW(), updated_at = NOW() WHERE {$this->primaryKey} = :id AND deleted_at IS NULL";
            $result = $this->db->execute($sql, [':id' => $id]);
            
            if ($result) {
                $this->logger->info("Record deleted", [
                    'table' => $this->table,
                    'id' => $id
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete record", [
                'table' => $this->table,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Restore a soft-deleted record
     */
    public function restore(int $id): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET deleted_at = NULL, updated_at = NOW() WHERE {$this->primaryKey} = :id AND deleted_at IS NOT NULL";
            $result = $this->db->execute($sql, [':id' => $id]);
            
            if ($result) {
                $this->logger->info("Record restored", [
                    'table' => $this->table,
                    'id' => $id
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to restore record", [
                'table' => $this->table,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Toggle a boolean field
     */
    public function toggle(int $id, string $field): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET $field = NOT $field, updated_at = NOW() WHERE {$this->primaryKey} = :id AND deleted_at IS NULL";
            $result = $this->db->execute($sql, [':id' => $id]);
            
            if ($result) {
                $this->logger->info("Field toggled", [
                    'table' => $this->table,
                    'id' => $id,
                    'field' => $field
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to toggle field", [
                'table' => $this->table,
                'id' => $id,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get count of records
     */
    public function count(array $filters = []): int
    {
        try {
            $where = ['deleted_at IS NULL'];
            $params = [];

            foreach ($filters as $field => $value) {
                if ($value !== '') {
                    $where[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }

            $whereClause = implode(' AND ', $where);
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $whereClause";
            
            return (int) $this->db->fetchColumn($sql, $params);
        } catch (\Exception $e) {
            $this->logger->error("Failed to count records", [
                'table' => $this->table,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get searchable fields (override in child classes)
     */
    protected function getSearchableFields(): array
    {
        return ['name'];
    }

    /**
     * Get sortable fields (override in child classes)
     */
    protected function getSortableFields(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }
}
