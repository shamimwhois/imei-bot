<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use IMEIBot\Core\Logger;
use IMEIBot\Repositories\FileRepository;
use IMEIBot\Services\Validation\FileValidator;
use IMEIBot\Services\FileManager;

class FileService extends BaseService
{
    private FileManager $fileManager;

    public function __construct(Logger $logger, FileRepository $repository, FileValidator $validator, FileManager $fileManager)
    {
        parent::__construct($logger, $repository, $validator);
        $this->fileManager = $fileManager;
    }

    /**
     * Get paginated files with relations
     */
    public function getWithRelations(int $limit = 20, int $offset = 0, array $filters = [], string $search = '', string $sort = 'created_at', string $order = 'desc'): array
    {
        try {
            $this->logger->info("Fetching files with relations", [
                'limit' => $limit,
                'offset' => $offset,
                'filters' => $filters,
                'search' => $search,
                'sort' => $sort,
                'order' => $order
            ]);

            $result = $this->repository->getWithRelations($limit, $offset, $filters, $search, $sort, $order);
            
            $this->logger->info("Files with relations fetched", [
                'total' => $result['total'],
                'page' => $result['page']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch files with relations", [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to fetch files: " . $e->getMessage());
        }
    }

    /**
     * Find file by ID with relations
     */
    public function findWithRelations(int $id): ?array
    {
        try {
            $this->logger->debug("Finding file with relations", ['id' => $id]);
            
            $file = $this->repository->findWithRelations($id);
            
            if ($file) {
                $this->logger->debug("File with relations found", ['id' => $id]);
            } else {
                $this->logger->warning("File with relations not found", ['id' => $id]);
            }

            return $file;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find file with relations", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to find file: " . $e->getMessage());
        }
    }

    /**
     * Create a new file with upload
     */
    public function createWithUpload(array $data, array $fileUpload = null): array
    {
        try {
            $this->logger->info("Creating new file with upload", ['data' => $this->sanitizeData($data)]);

            // Handle file upload if provided
            if ($fileUpload) {
                $uploadResult = $this->fileManager->handleUpload($fileUpload);
                if (!$uploadResult['success']) {
                    throw new \InvalidArgumentException("File upload failed: " . $uploadResult['error']);
                }

                // Add file information to data
                $data['file_name'] = $uploadResult['filename'];
                $data['original_name'] = $uploadResult['original_name'];
                $data['file_size_bytes'] = $uploadResult['size'];
                $data['file_size_mb'] = round($uploadResult['size'] / 1024 / 1024, 2);
                $data['file_type'] = $uploadResult['type'];
                $data['file_path'] = $uploadResult['path'];

                // Generate file hashes
                $hashes = $this->fileManager->generateHashes($uploadResult['path']);
                $data['file_hash_md5'] = $hashes['md5'];
                $data['file_hash_sha256'] = $hashes['sha256'];
            }

            // Validate data
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                $this->logger->warning("Validation failed", ['errors' => $errors]);
                throw new \InvalidArgumentException("Validation failed: " . $this->validator->getFirstError());
            }

            // Sanitize data
            $sanitizedData = $this->validator->sanitize($data);

            // Validate business rules
            $this->validateBusinessRules($sanitizedData);

            // Create record
            $id = $this->repository->create($sanitizedData);
            
            // Get the created record
            $record = $this->repository->findWithRelations($id);
            
            if (!$record) {
                throw new \RuntimeException("Failed to retrieve created file");
            }

            $this->logger->info("File created successfully", ['id' => $id]);
            
            return $record;
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (\Exception $e) {
            $this->logger->error("Failed to create file", [
                'data' => $this->sanitizeData($data),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to create file: " . $e->getMessage());
        }
    }

    /**
     * Update file with optional new upload
     */
    public function updateWithUpload(int $id, array $data, array $fileUpload = null): array
    {
        try {
            $this->logger->info("Updating file with upload", ['id' => $id, 'data' => $this->sanitizeData($data)]);

            // Check if file exists
            $existingFile = $this->repository->findWithRelations($id);
            if (!$existingFile) {
                $this->logger->warning("File not found for update", ['id' => $id]);
                throw new \InvalidArgumentException("File not found");
            }

            // Handle file upload if provided
            if ($fileUpload) {
                // Delete old file if exists
                if ($existingFile['file_path']) {
                    $this->fileManager->deleteFile($existingFile['file_path']);
                }

                $uploadResult = $this->fileManager->handleUpload($fileUpload);
                if (!$uploadResult['success']) {
                    throw new \InvalidArgumentException("File upload failed: " . $uploadResult['error']);
                }

                // Add file information to data
                $data['file_name'] = $uploadResult['filename'];
                $data['original_name'] = $uploadResult['original_name'];
                $data['file_size_bytes'] = $uploadResult['size'];
                $data['file_size_mb'] = round($uploadResult['size'] / 1024 / 1024, 2);
                $data['file_type'] = $uploadResult['type'];
                $data['file_path'] = $uploadResult['path'];

                // Generate file hashes
                $hashes = $this->fileManager->generateHashes($uploadResult['path']);
                $data['file_hash_md5'] = $hashes['md5'];
                $data['file_hash_sha256'] = $hashes['sha256'];
            }

            // Validate data
            if (!$this->validator->validate($data)) {
                $errors = $this->validator->getErrors();
                $this->logger->warning("Validation failed for update", ['id' => $id, 'errors' => $errors]);
                throw new \InvalidArgumentException("Validation failed: " . $this->validator->getFirstError());
            }

            // Sanitize data
            $sanitizedData = $this->validator->sanitize($data);

            // Validate business rules
            $this->validateBusinessRules($sanitizedData, $existingFile);

            // Update record
            $success = $this->repository->update($id, $sanitizedData);
            
            if (!$success) {
                throw new \RuntimeException("Failed to update file");
            }

            // Get the updated record
            $record = $this->repository->findWithRelations($id);
            
            $this->logger->info("File updated successfully", ['id' => $id]);
            
            return $record;
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (\Exception $e) {
            $this->logger->error("Failed to update file", [
                'id' => $id,
                'data' => $this->sanitizeData($data),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to update file: " . $e->getMessage());
        }
    }

    /**
     * Delete file and associated file storage
     */
    public function deleteWithFile(int $id): bool
    {
        try {
            $this->logger->info("Deleting file with storage", ['id' => $id]);

            // Check if file exists
            $existingFile = $this->repository->find($id);
            if (!$existingFile) {
                $this->logger->warning("File not found for deletion", ['id' => $id]);
                throw new \InvalidArgumentException("File not found");
            }

            // Check if can be deleted
            if (!$this->canDelete($existingFile)) {
                $this->logger->warning("File cannot be deleted", ['id' => $id]);
                throw new \InvalidArgumentException("File cannot be deleted");
            }

            // Soft delete record first
            $success = $this->repository->delete($id);
            
            if ($success) {
                // Delete physical file (optional - keep for audit)
                if ($existingFile['file_path']) {
                    $this->fileManager->deleteFile($existingFile['file_path']);
                }
                
                $this->logger->info("File deleted successfully", ['id' => $id]);
            } else {
                $this->logger->error("Failed to delete file", ['id' => $id]);
            }

            return $success;
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete file", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to delete file: " . $e->getMessage());
        }
    }

    /**
     * Toggle file status
     */
    public function toggleStatus(int $id, string $status): array
    {
        try {
            $this->logger->info("Toggling file status", ['id' => $id, 'status' => $status]);

            // Check if file exists
            $existingFile = $this->repository->find($id);
            if (!$existingFile) {
                throw new \InvalidArgumentException("File not found");
            }

            // Validate status
            $allowedStatuses = ['is_active', 'is_featured', 'is_premium', 'is_verified'];
            if (!in_array($status, $allowedStatuses)) {
                throw new \InvalidArgumentException("Invalid status field");
            }

            // Toggle status
            $success = $this->repository->toggle($id, $status);
            
            if (!$success) {
                throw new \RuntimeException("Failed to toggle status");
            }

            // Get the updated record
            $record = $this->repository->findWithRelations($id);
            
            $this->logger->info("File status toggled successfully", ['id' => $id, 'status' => $status]);
            
            return $record;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to toggle file status", [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to toggle status: " . $e->getMessage());
        }
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(int $id): array
    {
        try {
            $this->logger->info("Incrementing download count", ['id' => $id]);

            // Check if file exists and is active
            $file = $this->repository->find($id);
            if (!$file) {
                throw new \InvalidArgumentException("File not found");
            }

            if (!$file['is_active']) {
                throw new \InvalidArgumentException("File is not active");
            }

            // Increment count
            $success = $this->repository->incrementDownloadCount($id);
            
            if (!$success) {
                throw new \RuntimeException("Failed to increment download count");
            }

            // Get updated file
            $record = $this->repository->findWithRelations($id);
            
            $this->logger->info("Download count incremented", ['id' => $id]);
            
            return $record;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to increment download count", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to increment download count: " . $e->getMessage());
        }
    }

    /**
     * Get popular files
     */
    public function getPopularFiles(int $limit = 10): array
    {
        try {
            $this->logger->debug("Fetching popular files", ['limit' => $limit]);
            
            $files = $this->repository->getPopularFiles($limit);
            
            $this->logger->debug("Popular files fetched", ['count' => count($files)]);
            
            return $files;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch popular files", [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to fetch popular files: " . $e->getMessage());
        }
    }

    /**
     * Get recent files
     */
    public function getRecentFiles(int $limit = 10): array
    {
        try {
            $this->logger->debug("Fetching recent files", ['limit' => $limit]);
            
            $files = $this->repository->getRecentFiles($limit);
            
            $this->logger->debug("Recent files fetched", ['count' => count($files)]);
            
            return $files;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch recent files", [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to fetch recent files: " . $e->getMessage());
        }
    }

    /**
     * Get files by category
     */
    public function getByCategory(string $category, int $limit = 20): array
    {
        try {
            $this->logger->debug("Fetching files by category", ['category' => $category, 'limit' => $limit]);
            
            $files = $this->repository->getByCategory($category, $limit);
            
            $this->logger->debug("Files by category fetched", ['category' => $category, 'count' => count($files)]);
            
            return $files;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch files by category", [
                'category' => $category,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to fetch files by category: " . $e->getMessage());
        }
    }

    /**
     * Check if file can be deleted
     */
    protected function canDelete(array $file): bool
    {
        // Business rules for file deletion
        // For example, don't allow deletion if file has downloads in last 30 days
        if ($file['download_count'] > 0) {
            // Allow deletion but log warning
            $this->logger->warning("Deleting file with downloads", [
                'id' => $file['id'],
                'download_count' => $file['download_count']
            ]);
        }
        
        return true;
    }

    /**
     * Get repository statistics
     */
    protected function getRepositoryStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?array $existingFile = null): void
    {
        // Check if file size is reasonable for category
        if (isset($data['category']) && isset($data['file_size_mb'])) {
            $categoryLimits = [
                'firmware' => 5000, // 5GB
                'qcn' => 10, // 10MB
                'frp' => 100, // 100MB
                'modem' => 50, // 50MB
                'unlock' => 100, // 100MB
                'tools' => 500, // 500MB
                'other' => 1000 // 1GB
            ];

            $limit = $categoryLimits[$data['category']] ?? 1000;
            if ($data['file_size_mb'] > $limit) {
                throw new \InvalidArgumentException("File size exceeds limit for category {$data['category']} (max: {$limit}MB)");
            }
        }

        // Check if premium files should have verification
        if (isset($data['is_premium']) && $data['is_premium'] == 1) {
            if (!($data['is_verified'] ?? false)) {
                $this->logger->warning("Premium file without verification", ['data' => $data]);
            }
        }
    }
}
