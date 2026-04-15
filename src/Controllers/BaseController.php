<?php

declare(strict_types=1);

namespace IMEIBot\Controllers;

use IMEIBot\Core\Logger;
use IMEIBot\Middleware\AuthMiddleware;

abstract class BaseController
{
    protected Logger $logger;
    protected AuthMiddleware $authMiddleware;

    public function __construct(Logger $logger, AuthMiddleware $authMiddleware)
    {
        $this->logger = $logger;
        $this->authMiddleware = $authMiddleware;
    }

    /**
     * Send success response
     */
    protected function sendSuccessResponse(array $data = [], string $message = 'Success'): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Send error response
     */
    protected function sendErrorResponse(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => null
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Validate request method
     */
    protected function requireRequestMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            $this->sendErrorResponse("Method not allowed", 405);
        }
    }

    /**
     * Get JSON request data
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        if ($input === false) {
            return [];
        }

        try {
            return json_decode($input, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException $e) {
            $this->logger->error('Invalid JSON input', ['error' => $e->getMessage()]);
            $this->sendErrorResponse('Invalid JSON input');
        }
    }

    /**
     * Get pagination parameters
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Get search and filter parameters
     */
    protected function getFilterParams(): array
    {
        return [
            'search' => $_GET['search'] ?? '',
            'sort' => $_GET['sort'] ?? 'created_at',
            'order' => strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
            'filters' => array_filter($_GET, function($key) {
                return !in_array($key, ['page', 'limit', 'search', 'sort', 'order']);
            }, ARRAY_FILTER_USE_KEY)
        ];
    }

    /**
     * Validate required fields
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $this->sendErrorResponse("Missing required fields: " . implode(', ', $missing));
        }
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $data): array
    {
        return array_map(function($value) {
            if (is_string($value)) {
                return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            }
            return $value;
        }, $data);
    }

    /**
     * Handle file upload
     */
    protected function handleFileUpload(string $fieldName, array $allowedTypes = [], int $maxSize = 10485760): array
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'No file uploaded or upload error'];
        }

        $file = $_FILES[$fieldName];
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File too large'];
        }

        // Check file type
        if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        
        return [
            'success' => true,
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'tmp_name' => $file['tmp_name']
        ];
    }

    /**
     * Log CRUD operation
     */
    protected function logCrudOperation(string $action, string $entity, array $data = []): void
    {
        $this->logger->info("CRUD Operation: $action", [
            'action' => $action,
            'entity' => $entity,
            'data' => $data,
            'user_id' => $_SESSION['admin_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Standard CRUD index method
     */
    abstract public function index(): void;

    /**
     * Standard CRUD show method
     */
    abstract public function show(int $id): void;

    /**
     * Standard CRUD create method
     */
    abstract public function create(): void;

    /**
     * Standard CRUD update method
     */
    abstract public function update(int $id): void;

    /**
     * Standard CRUD delete method
     */
    abstract public function delete(int $id): void;
}
