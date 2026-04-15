<?php

declare(strict_types=1);

namespace IMEIBot\Controllers;

use IMEIBot\Services\JWTService;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;
use IMEIBot\Middleware\AuthMiddleware;

class AdminController
{
    private AuthMiddleware $authMiddleware;
    private Logger $logger;

    public function __construct(AuthMiddleware $authMiddleware, Logger $logger)
    {
        $this->authMiddleware = $authMiddleware;
        $this->logger = $logger;
    }

    public function dashboard(): void
    {
        $user = $this->authMiddleware->requireAdmin();
        
        if (!$user) {
            return;
        }

        header('Content-Type: application/json');

        try {
            // Get admin statistics
            $stats = $this->getAdminStatistics();
            
            $this->sendSuccessResponse([
                'stats' => $stats,
                'user' => $this->sanitizeUser($user)
            ], 'Admin dashboard data retrieved');

        } catch (\Exception $e) {
            $this->logger->error('Admin dashboard failed', [
                'error' => $e->getMessage(),
                'admin_id' => $user['id']
            ]);
            $this->sendErrorResponse('Failed to load dashboard: ' . $e->getMessage());
        }
    }

    public function users(): void
    {
        $user = $this->authMiddleware->requireAdmin();
        
        if (!$user) {
            return;
        }

        header('Content-Type: application/json');

        try {
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 20);
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';
            $sortBy = $_GET['sort_by'] ?? 'created_at';
            $sortOrder = $_GET['sort_order'] ?? 'desc';

            $users = $this->getUsers($page, $limit, $search, $role, $status, $sortBy, $sortOrder);
            $total = $this->getUsersCount($search, $role, $status);

            $this->sendSuccessResponse([
                'users' => array_map([$this, 'sanitizeUser'], $users),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ], 'Users retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error('Admin users list failed', [
                'error' => $e->getMessage(),
                'admin_id' => $user['id']
            ]);
            $this->sendErrorResponse('Failed to retrieve users: ' . $e->getMessage());
        }
    }

    public function updateUser(): void
    {
        $user = $this->authMiddleware->requireAdmin();
        
        if (!$user) {
            return;
        }

        header('Content-Type: application/json');

        try {
            $input = $this->getJsonInput();
            $targetUserId = (int) ($input['user_id'] ?? 0);
            
            if (!$targetUserId) {
                $this->sendErrorResponse('User ID is required', 400);
                return;
            }

            // Prevent admin from modifying super admin unless they are super admin
            $targetUser = User::findByIdAsArray($targetUserId);
            if (!$targetUser) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            if ($targetUser['role'] === 'super_admin' && $user['role'] !== 'super_admin') {
                $this->sendErrorResponse('Cannot modify super admin', 403);
                return;
            }

            // Validate updates
            $allowedUpdates = ['role', 'status', 'daily_imei_limit', 'daily_download_limit', 'storage_quota_mb'];
            $updates = array_intersect_key($input, array_flip($allowedUpdates));

            if (empty($updates)) {
                $this->sendErrorResponse('No valid updates provided', 400);
                return;
            }

            // Role validation
            if (isset($updates['role'])) {
                if ($user['role'] !== 'super_admin' && in_array($updates['role'], ['admin', 'super_admin'])) {
                    $this->sendErrorResponse('Only super admin can assign admin roles', 403);
                    return;
                }
                
                if (!in_array($updates['role'], ['user', 'premium', 'admin', 'super_admin'])) {
                    $this->sendErrorResponse('Invalid role', 400);
                    return;
                }
            }

            // Status validation
            if (isset($updates['status'])) {
                if (!in_array($updates['status'], ['active', 'inactive', 'banned', 'pending'])) {
                    $this->sendErrorResponse('Invalid status', 400);
                    return;
                }
            }

            // Apply updates
            $success = $this->updateUserFields($targetUserId, $updates);
            
            if (!$success) {
                $this->sendErrorResponse('Failed to update user', 500);
                return;
            }

            $updatedUser = User::findByIdAsArray($targetUserId);
            
            $this->logger->info('User updated by admin', [
                'admin_id' => $user['id'],
                'target_user_id' => $targetUserId,
                'updates' => $updates
            ]);

            $this->sendSuccessResponse([
                'user' => $this->sanitizeUser($updatedUser)
            ], 'User updated successfully');

        } catch (\Exception $e) {
            $this->logger->error('Admin user update failed', [
                'error' => $e->getMessage(),
                'admin_id' => $user['id']
            ]);
            $this->sendErrorResponse('Failed to update user: ' . $e->getMessage());
        }
    }

    public function deleteUser(): void
    {
        $user = $this->authMiddleware->requireSuperAdmin();
        
        if (!$user) {
            return;
        }

        header('Content-Type: application/json');

        try {
            $input = $this->getJsonInput();
            $targetUserId = (int) ($input['user_id'] ?? 0);
            
            if (!$targetUserId) {
                $this->sendErrorResponse('User ID is required', 400);
                return;
            }

            if ($targetUserId === $user['id']) {
                $this->sendErrorResponse('Cannot delete your own account', 400);
                return;
            }

            $targetUser = User::findByIdAsArray($targetUserId);
            if (!$targetUser) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $success = User::delete($targetUserId);
            
            if (!$success) {
                $this->sendErrorResponse('Failed to delete user', 500);
                return;
            }

            $this->logger->warning('User deleted by super admin', [
                'admin_id' => $user['id'],
                'deleted_user_id' => $targetUserId,
                'deleted_user_email' => $targetUser['email']
            ]);

            $this->sendSuccessResponse([], 'User deleted successfully');

        } catch (\Exception $e) {
            $this->logger->error('Admin user deletion failed', [
                'error' => $e->getMessage(),
                'admin_id' => $user['id']
            ]);
            $this->sendErrorResponse('Failed to delete user: ' . $e->getMessage());
        }
    }

    public function upgradeToPremium(): void
    {
        $user = $this->authMiddleware->requireAdmin();
        
        if (!$user) {
            return;
        }

        header('Content-Type: application/json');

        try {
            $input = $this->getJsonInput();
            $targetUserId = (int) ($input['user_id'] ?? 0);
            $duration = (int) ($input['duration'] ?? 30); // days
            
            if (!$targetUserId) {
                $this->sendErrorResponse('User ID is required', 400);
                return;
            }

            $targetUser = User::findByIdAsArray($targetUserId);
            if (!$targetUser) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $expiresAt = new \DateTime();
            $expiresAt->add(new \DateInterval("P{$duration}D"));

            $success = $this->upgradeUserToPremium($targetUserId, $expiresAt);
            
            if (!$success) {
                $this->sendErrorResponse('Failed to upgrade user', 500);
                return;
            }

            $this->logger->info('User upgraded to premium by admin', [
                'admin_id' => $user['id'],
                'target_user_id' => $targetUserId,
                'duration_days' => $duration,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s')
            ]);

            $this->sendSuccessResponse([
                'expires_at' => $expiresAt->format('Y-m-d H:i:s')
            ], 'User upgraded to premium successfully');

        } catch (\Exception $e) {
            $this->logger->error('Premium upgrade failed', [
                'error' => $e->getMessage(),
                'admin_id' => $user['id']
            ]);
            $this->sendErrorResponse('Failed to upgrade user: ' . $e->getMessage());
        }
    }

    public function systemLogs(): void
    {
        $user = $this->authMiddleware->requireAdmin();
        
        if (!$user) {
            return;
        }

        header('Content-Type: application/json');

        try {
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 50);
            $level = $_GET['level'] ?? '';
            $search = $_GET['search'] ?? '';

            $logs = $this->getSystemLogs($page, $limit, $level, $search);
            $total = $this->getSystemLogsCount($level, $search);

            $this->sendSuccessResponse([
                'logs' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ], 'System logs retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error('System logs retrieval failed', [
                'error' => $e->getMessage(),
                'admin_id' => $user['id']
            ]);
            $this->sendErrorResponse('Failed to retrieve logs: ' . $e->getMessage());
        }
    }

    private function getAdminStatistics(): array
    {
        $db = Database::getInstance(
            $this->getConfig('database'),
            $this->getLogger()
        );

        $stats = [];

        // Total users
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
        $stats['total_users'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['total'];

        // Users by role
        $stmt = $db->query("
            SELECT role, COUNT(*) as count 
            FROM users 
            WHERE deleted_at IS NULL 
            GROUP BY role
        ");
        $stats['users_by_role'] = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats['users_by_role'][$row['role']] = (int) $row['count'];
        }

        // Users by status
        $stmt = $db->query("
            SELECT status, COUNT(*) as count 
            FROM users 
            WHERE deleted_at IS NULL 
            GROUP BY status
        ");
        $stats['users_by_status'] = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats['users_by_status'][$row['status']] = (int) $row['count'];
        }

        // Recent registrations
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
            AND deleted_at IS NULL
        ");
        $stats['registrations_24h'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // Active users (last 24 hours)
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE last_activity_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
            AND deleted_at IS NULL
        ");
        $stats['active_users_24h'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // Premium users
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE role = 'premium' 
            AND premium_expires_at > NOW() 
            AND deleted_at IS NULL
        ");
        $stats['premium_users'] = (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        return $stats;
    }

    private function getUsers(int $page, int $limit, string $search, string $role, string $status, string $sortBy, string $sortOrder): array
    {
        $db = Database::getInstance(
            $this->getConfig('database'),
            $this->getLogger()
        );

        $offset = ($page - 1) * $limit;
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($search) {
            $where[] = "(email LIKE ? OR username LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if ($role) {
            $where[] = "role = ?";
            $params[] = $role;
        }

        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        $orderClause = "ORDER BY {$sortBy} " . strtoupper($sortOrder);

        $sql = "
            SELECT * FROM users 
            {$whereClause} 
            {$orderClause}
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $users = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['preferences']) {
                $row['preferences'] = json_decode($row['preferences'], true);
            }
            $users[] = $row;
        }

        return $users;
    }

    private function getUsersCount(string $search, string $role, string $status): int
    {
        $db = Database::getInstance(
            $this->getConfig('database'),
            $this->getLogger()
        );

        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($search) {
            $where[] = "(email LIKE ? OR username LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if ($role) {
            $where[] = "role = ?";
            $params[] = $role;
        }

        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT COUNT(*) as count FROM users {$whereClause}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    private function updateUserFields(int $userId, array $updates): bool
    {
        $db = Database::getInstance(
            $this->getConfig('database'),
            $this->getLogger()
        );

        $set = [];
        $params = ['id' => $userId];

        foreach ($updates as $field => $value) {
            $set[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }

        $set[] = "updated_at = NOW()";

        $sql = "UPDATE users SET " . implode(', ', $set) . " WHERE id = :id";

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    private function upgradeUserToPremium(int $userId, \DateTime $expiresAt): bool
    {
        $db = Database::getInstance(
            $this->getConfig('database'),
            $this->getLogger()
        );

        $sql = "
            UPDATE users SET 
                role = 'premium',
                premium_expires_at = ?,
                daily_imei_limit = 999999,
                daily_download_limit = 999999,
                storage_quota_mb = 10000,
                updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $db->prepare($sql);
        return $stmt->execute([$expiresAt->format('Y-m-d H:i:s'), $userId]);
    }

    private function getSystemLogs(int $page, int $limit, string $level, string $search): array
    {
        // This would typically read from your log files or database
        // For now, return empty array as placeholder
        return [];
    }

    private function getSystemLogsCount(string $level, string $search): int
    {
        // This would typically count from your log files or database
        return 0;
    }

    private function sanitizeUser(array $user): array
    {
        unset($user['password_hash'], $user['api_token']);
        return $user;
    }

    private function getJsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendErrorResponse('Invalid JSON input', 400);
        }

        return $input ?? [];
    }

    private function sendSuccessResponse(array $data, string $message = 'Success', int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }

    private function sendErrorResponse(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ]);
    }

    private function getConfig(string $key): array
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ];
    }

    private function getLogger(): \IMEIBot\Core\Logger
    {
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
