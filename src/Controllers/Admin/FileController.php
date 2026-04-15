<?php

declare(strict_types=1);

namespace IMEIBot\Controllers\Admin;

use IMEIBot\Controllers\BaseController;
use IMEIBot\Services\FileService;
use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

class FileController extends BaseController
{
    private FileService $fileService;

    public function __construct(Logger $logger, AuthMiddleware $authMiddleware, FileService $fileService)
    {
        parent::__construct($logger, $authMiddleware);
        $this->fileService = $fileService;
    }

    /**
     * Get paginated list of files
     */
    public function index(): void
    {
        try {
            $this->requireAdmin();

            $pagination = $this->getPaginationParams();
            $filters = $this->getFilterParams();

            $result = $this->fileService->getWithRelations(
                $pagination['limit'],
                $pagination['offset'],
                $filters['filters'],
                $filters['search'],
                $filters['sort'],
                $filters['order']
            );

            $this->sendSuccessResponse($result, 'Files retrieved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to get files', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to retrieve files');
        }
    }

    /**
     * Get a specific file
     */
    public function show(int $id): void
    {
        try {
            $this->requireAdmin();

            $file = $this->fileService->findWithRelations($id);
            
            if (!$file) {
                $this->sendErrorResponse('File not found', 404);
                return;
            }

            $this->sendSuccessResponse($file, 'File retrieved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to get file', ['id' => $id, 'error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to retrieve file');
        }
    }

    /**
     * Create a new file
     */
    public function create(): void
    {
        try {
            $this->requireAdmin();
            $this->requireRequestMethod('POST');

            $data = $this->getJsonInput();
            
            // Handle file upload
            $fileUpload = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $fileUpload = $_FILES['file'];
            }

            $file = $this->fileService->createWithUpload($data, $fileUpload);
            
            $this->logCrudOperation('create', 'file', ['id' => $file['id'], 'name' => $file['name']]);
            
            $this->sendSuccessResponse($file, 'File created successfully');
        } catch (\InvalidArgumentException $e) {
            $this->sendErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Failed to create file', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to create file');
        }
    }

    /**
     * Update a file
     */
    public function update(int $id): void
    {
        try {
            $this->requireAdmin();
            $this->requireRequestMethod('PUT');

            $data = $this->getJsonInput();
            
            // Handle file upload
            $fileUpload = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $fileUpload = $_FILES['file'];
            }

            $file = $this->fileService->updateWithUpload($id, $data, $fileUpload);
            
            $this->logCrudOperation('update', 'file', ['id' => $id, 'name' => $file['name']]);
            
            $this->sendSuccessResponse($file, 'File updated successfully');
        } catch (\InvalidArgumentException $e) {
            $this->sendErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update file', ['id' => $id, 'error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to update file');
        }
    }

    /**
     * Delete a file
     */
    public function delete(int $id): void
    {
        try {
            $this->requireAdmin();
            $this->requireRequestMethod('DELETE');

            $success = $this->fileService->deleteWithFile($id);
            
            if ($success) {
                $this->logCrudOperation('delete', 'file', ['id' => $id]);
                $this->sendSuccessResponse(['deleted' => true], 'File deleted successfully');
            } else {
                $this->sendErrorResponse('Failed to delete file');
            }
        } catch (\InvalidArgumentException $e) {
            $this->sendErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete file', ['id' => $id, 'error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to delete file');
        }
    }

    /**
     * Toggle file status
     */
    public function toggle(int $id, string $status): void
    {
        try {
            $this->requireAdmin();
            $this->requireRequestMethod('POST');

            $file = $this->fileService->toggleStatus($id, $status);
            
            $this->logCrudOperation('toggle', 'file', ['id' => $id, 'status' => $status]);
            
            $this->sendSuccessResponse($file, "File $status toggled successfully");
        } catch (\InvalidArgumentException $e) {
            $this->sendErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Failed to toggle file status', ['id' => $id, 'status' => $status, 'error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to toggle file status');
        }
    }

    /**
     * Get popular files
     */
    public function popular(): void
    {
        try {
            $this->requireAdmin();

            $limit = max(1, min(50, (int) ($_GET['limit'] ?? 10)));
            $files = $this->fileService->getPopularFiles($limit);
            
            $this->sendSuccessResponse($files, 'Popular files retrieved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to get popular files', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to retrieve popular files');
        }
    }

    /**
     * Get recent files
     */
    public function recent(): void
    {
        try {
            $this->requireAdmin();

            $limit = max(1, min(50, (int) ($_GET['limit'] ?? 10)));
            $files = $this->fileService->getRecentFiles($limit);
            
            $this->sendSuccessResponse($files, 'Recent files retrieved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to get recent files', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to retrieve recent files');
        }
    }

    /**
     * Get files by category
     */
    public function byCategory(string $category): void
    {
        try {
            $this->requireAdmin();

            $limit = max(1, min(50, (int) ($_GET['limit'] ?? 20)));
            $files = $this->fileService->getByCategory($category, $limit);
            
            $this->sendSuccessResponse($files, "Files in category '$category' retrieved successfully");
        } catch (\Exception $e) {
            $this->logger->error('Failed to get files by category', ['category' => $category, 'error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to retrieve files by category');
        }
    }

    /**
     * Get file statistics
     */
    public function statistics(): void
    {
        try {
            $this->requireAdmin();

            $stats = $this->fileService->getStatistics();
            
            $this->sendSuccessResponse($stats, 'File statistics retrieved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to get file statistics', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to retrieve file statistics');
        }
    }

    /**
     * Increment download count
     */
    public function incrementDownload(int $id): void
    {
        try {
            $this->requireAdmin();
            $this->requireRequestMethod('POST');

            $file = $this->fileService->incrementDownloadCount($id);
            
            $this->logCrudOperation('increment_download', 'file', ['id' => $id]);
            
            $this->sendSuccessResponse($file, 'Download count incremented successfully');
        } catch (\InvalidArgumentException $e) {
            $this->sendErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Failed to increment download count', ['id' => $id, 'error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to increment download count');
        }
    }

    /**
     * Bulk operations
     */
    public function bulk(): void
    {
        try {
            $this->requireAdmin();
            $this->requireRequestMethod('POST');

            $data = $this->getJsonInput();
            $action = $data['action'] ?? '';
            $ids = $data['ids'] ?? [];

            if (empty($ids) || !is_array($ids)) {
                $this->sendErrorResponse('Invalid file IDs provided');
                return;
            }

            $results = [];
            $errors = [];

            foreach ($ids as $id) {
                try {
                    switch ($action) {
                        case 'delete':
                            $success = $this->fileService->deleteWithFile($id);
                            $results[$id] = ['success' => $success];
                            break;
                        
                        case 'activate':
                            $file = $this->fileService->toggleStatus($id, 'is_active');
                            $results[$id] = ['success' => true, 'data' => $file];
                            break;
                        
                        case 'deactivate':
                            $file = $this->fileService->toggleStatus($id, 'is_active');
                            $results[$id] = ['success' => true, 'data' => $file];
                            break;
                        
                        case 'feature':
                            $file = $this->fileService->toggleStatus($id, 'is_featured');
                            $results[$id] = ['success' => true, 'data' => $file];
                            break;
                        
                        case 'unfeature':
                            $file = $this->fileService->toggleStatus($id, 'is_featured');
                            $results[$id] = ['success' => true, 'data' => $file];
                            break;
                        
                        default:
                            $errors[$id] = 'Unknown action: ' . $action;
                    }
                } catch (\Exception $e) {
                    $errors[$id] = $e->getMessage();
                }
            }

            $this->logCrudOperation('bulk', 'file', ['action' => $action, 'ids' => $ids]);
            
            $this->sendSuccessResponse([
                'results' => $results,
                'errors' => $errors,
                'processed' => count($ids),
                'successful' => count($results),
                'failed' => count($errors)
            ], "Bulk $action operation completed");
        } catch (\Exception $e) {
            $this->logger->error('Failed to perform bulk operation', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to perform bulk operation');
        }
    }

    /**
     * Search files
     */
    public function search(): void
    {
        try {
            $this->requireAdmin();

            $query = $_GET['q'] ?? '';
            $pagination = $this->getPaginationParams();
            $filters = $this->getFilterParams();

            if (empty($query)) {
                $this->sendErrorResponse('Search query is required');
                return;
            }

            $result = $this->fileService->getWithRelations(
                $pagination['limit'],
                $pagination['offset'],
                $filters['filters'],
                $query,
                $filters['sort'],
                $filters['order']
            );

            $this->sendSuccessResponse($result, 'Search results retrieved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to search files', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Failed to search files');
        }
    }

    /**
     * Require admin authentication
     */
    private function requireAdmin(): void
    {
        $user = $this->authMiddleware->requireAdmin();
        if (!$user) {
            $this->sendErrorResponse('Admin authentication required', 401);
        }
    }
}
