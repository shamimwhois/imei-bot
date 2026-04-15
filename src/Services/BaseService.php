<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use IMEIBot\Core\Logger;
use IMEIBot\Repositories\BaseRepository;
use IMEIBot\Services\Validation\BaseValidator;

abstract class BaseService
{
    protected Logger $logger;
    protected BaseRepository $repository;
    protected BaseValidator $validator;

    public function __construct(Logger $logger, BaseRepository $repository, BaseValidator $validator)
    {
        $this->logger = $logger;
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $limit = 20, int $offset = 0, array $filters = [], string $search = '', string $sort = 'created_at', string $order = 'desc'): array
    {
        try {
            $this->logger->info("Fetching paginated results", [
                'limit' => $limit,
                'offset' => $offset,
                'filters' => $filters,
                'search' => $search,
                'sort' => $sort,
                'order' => $order
            ]);

            $result = $this->repository->paginate($limit, $offset, $filters, $search, $sort, $order);
            
            $this->logger->info("Paginated results fetched", [
                'total' => $result['total'],
                'page' => $result['page']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch paginated results", [
                'error' => $e->getMessage(),
                'limit' => $limit,
                'offset' => $offset
            ]);
            throw new \RuntimeException("Failed to fetch records: " . $e->getMessage());
        }
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?array
    {
        try {
            $this->logger->debug("Finding record", ['id' => $id]);
            
            $record = $this->repository->find($id);
            
            if ($record) {
                $this->logger->debug("Record found", ['id' => $id]);
            } else {
                $this->logger->warning("Record not found", ['id' => $id]);
            }

            return $record;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find record", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to find record: " . $e->getMessage());
        }
    }

    /**
     * Find a record by UUID
     */
    public function findByUuid(string $uuid): ?array
    {
        try {
            $this->logger->debug("Finding record by UUID", ['uuid' => $uuid]);
            
            $record = $this->repository->findByUuid($uuid);
            
            if ($record) {
                $this->logger->debug("Record found by UUID", ['uuid' => $uuid]);
            } else {
                $this->logger->warning("Record not found by UUID", ['uuid' => $uuid]);
            }

            return $record;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find record by UUID", [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to find record: " . $e->getMessage());
        }
    }

    /**
     * Create a new record
     */
    public function create(array $data): array
    {
        try {
            $this->logger->info("Creating new record", ['data' => $this->sanitizeData($data)]);

            // Validate data
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                $this->logger->warning("Validation failed", ['errors' => $errors]);
                throw new \InvalidArgumentException("Validation failed: " . $this->validator->getFirstError());
            }

            // Sanitize data
            $sanitizedData = $this->validator->sanitize($data);

            // Create record
            $id = $this->repository->create($sanitizedData);
            
            // Get the created record
            $record = $this->repository->find($id);
            
            if (!$record) {
                throw new \RuntimeException("Failed to retrieve created record");
            }

            $this->logger->info("Record created successfully", ['id' => $id]);
            
            return $record;
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (\Exception $e) {
            $this->logger->error("Failed to create record", [
                'data' => $this->sanitizeData($data),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to create record: " . $e->getMessage());
        }
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): array
    {
        try {
            $this->logger->info("Updating record", ['id' => $id, 'data' => $this->sanitizeData($data)]);

            // Check if record exists
            $existingRecord = $this->repository->find($id);
            if (!$existingRecord) {
                $this->logger->warning("Record not found for update", ['id' => $id]);
                throw new \InvalidArgumentException("Record not found");
            }

            // Validate data
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                $this->logger->warning("Validation failed for update", ['id' => $id, 'errors' => $errors]);
                throw new \InvalidArgumentException("Validation failed: " . $this->validator->getFirstError());
            }

            // Sanitize data
            $sanitizedData = $this->validator->sanitize($data);

            // Update record
            $success = $this->repository->update($id, $sanitizedData);
            
            if (!$success) {
                throw new \RuntimeException("Failed to update record");
            }

            // Get the updated record
            $record = $this->repository->find($id);
            
            $this->logger->info("Record updated successfully", ['id' => $id]);
            
            return $record;
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (\Exception $e) {
            $this->logger->error("Failed to update record", [
                'id' => $id,
                'data' => $this->sanitizeData($data),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to update record: " . $e->getMessage());
        }
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        try {
            $this->logger->info("Deleting record", ['id' => $id]);

            // Check if record exists
            $existingRecord = $this->repository->find($id);
            if (!$existingRecord) {
                $this->logger->warning("Record not found for deletion", ['id' => $id]);
                throw new \InvalidArgumentException("Record not found");
            }

            // Check if can be deleted (override in child classes for business rules)
            if (!$this->canDelete($existingRecord)) {
                $this->logger->warning("Record cannot be deleted", ['id' => $id]);
                throw new \InvalidArgumentException("Record cannot be deleted");
            }

            $success = $this->repository->delete($id);
            
            if ($success) {
                $this->logger->info("Record deleted successfully", ['id' => $id]);
            } else {
                $this->logger->error("Failed to delete record", ['id' => $id]);
            }

            return $success;
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete record", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to delete record: " . $e->getMessage());
        }
    }

    /**
     * Toggle a boolean field
     */
    public function toggle(int $id, string $field): array
    {
        try {
            $this->logger->info("Toggling field", ['id' => $id, 'field' => $field]);

            // Check if record exists
            $existingRecord = $this->repository->find($id);
            if (!$existingRecord) {
                $this->logger->warning("Record not found for toggle", ['id' => $id]);
                throw new \InvalidArgumentException("Record not found");
            }

            // Check if field can be toggled
            if (!$this->canToggle($existingRecord, $field)) {
                $this->logger->warning("Field cannot be toggled", ['id' => $id, 'field' => $field]);
                throw new \InvalidArgumentException("Field cannot be toggled");
            }

            $success = $this->repository->toggle($id, $field);
            
            if (!$success) {
                throw new \RuntimeException("Failed to toggle field");
            }

            // Get the updated record
            $record = $this->repository->find($id);
            
            $this->logger->info("Field toggled successfully", ['id' => $id, 'field' => $field]);
            
            return $record;
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (\Exception $e) {
            $this->logger->error("Failed to toggle field", [
                'id' => $id,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to toggle field: " . $e->getMessage());
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        try {
            $this->logger->debug("Fetching statistics");
            
            $stats = $this->getRepositoryStatistics();
            
            $this->logger->debug("Statistics fetched", ['stats' => $stats]);
            
            return $stats;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch statistics", ['error' => $e->getMessage()]);
            throw new \RuntimeException("Failed to fetch statistics: " . $e->getMessage());
        }
    }

    /**
     * Check if record can be deleted (override in child classes)
     */
    protected function canDelete(array $record): bool
    {
        return true;
    }

    /**
     * Check if field can be toggled (override in child classes)
     */
    protected function canToggle(array $record, string $field): bool
    {
        return true;
    }

    /**
     * Get repository statistics (override in child classes)
     */
    protected function getRepositoryStatistics(): array
    {
        return [
            'total' => $this->repository->count(),
            'active' => $this->repository->count(['status' => 'active']),
            'created_today' => $this->repository->count(['created_at' => date('Y-m-d')])
        ];
    }

    /**
     * Sanitize data for logging
     */
    protected function sanitizeData(array $data): array
    {
        return array_map(function($value) {
            if (is_string($value)) {
                // Mask sensitive fields
                if (in_array(strtolower($value), ['password', 'token', 'secret', 'key'])) {
                    return '***MASKED***';
                }
                return strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
            }
            return $value;
        }, $data);
    }

    /**
     * Validate business rules (override in child classes)
     */
    protected function validateBusinessRules(array $data, ?array $existingRecord = null): void
    {
        // Override in child classes to implement business-specific validation
    }
}
