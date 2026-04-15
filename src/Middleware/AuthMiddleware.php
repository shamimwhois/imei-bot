<?php

declare(strict_types=1);

namespace IMEIBot\Middleware;

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;

/**
 * Authentication Middleware
 * Handles user authentication, session management, and access control
 */
class AuthMiddleware
{
    private Database $db;
    private Logger $logger;
    
    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return User::findByIdAsArray($_SESSION['user_id']);
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        return $user['role'] === $role;
    }
    
    /**
     * Check if user has any of specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        return in_array($user['role'], $roles);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }
    
    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        return $user['status'] === 'active';
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    public function requireAuth(string $redirectUrl = '/login.php'): void
    {
        if (!$this->isAuthenticated()) {
            $currentUrl = $_SERVER['REQUEST_URI'];
            $redirectUrl .= '?redirect=' . urlencode($currentUrl);
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Require specific role - redirect if user doesn't have required role
     */
    public function requireRole(string $role, string $redirectUrl = '/dashboard.php'): void
    {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            $_SESSION['flash_messages']['error'][] = 'You do not have permission to access this page.';
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Require admin role - redirect if user is not admin
     */
    public function requireAdmin(string $redirectUrl = '/dashboard.php'): void
    {
        $this->requireAnyRole(['admin', 'super_admin'], $redirectUrl);
    }
    
    /**
     * Require any of specified roles
     */
    public function requireAnyRole(array $roles, string $redirectUrl = '/dashboard.php'): void
    {
        $this->requireAuth();
        
        if (!$this->hasAnyRole($roles)) {
            $_SESSION['flash_messages']['error'][] = 'You do not have permission to access this page.';
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Require active user status
     */
    public function requireActive(string $redirectUrl = '/login.php'): void
    {
        $this->requireAuth();
        
        if (!$this->isActive()) {
            $_SESSION['flash_messages']['error'][] = 'Your account must be active to access this page.';
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Login user with credentials
     */
    public function login(string $identifier, string $password, bool $remember = false): bool
    {
        try {
            // Find user by email or username
            $user = $this->findUserByIdentifier($identifier);
            
            if (!$user) {
                $this->logger->warning('Login attempt with invalid identifier', [
                    'identifier' => $identifier,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return false;
            }
            
            // Check password
            if (!password_verify($password, $user['password_hash'])) {
                $this->logger->warning('Login attempt with invalid password', [
                    'user_id' => $user['id'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return false;
            }
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                $this->logger->warning('Login attempt by inactive user', [
                    'user_id' => $user['id'],
                    'status' => $user['status'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                return false;
            }
            
            // Update last activity and login attempts
            $this->updateUserLoginData($user['id'], $remember);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            $this->logger->info('User logged in successfully', [
                'user_id' => $user['id'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Login error', [
                'error' => $e->getMessage(),
                'identifier' => $identifier,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return false;
        }
    }
    
    /**
     * Logout user
     */
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Clear session data
        $_SESSION = [];
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        // Clear remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $this->clearRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        if ($userId) {
            $this->logger->info('User logged out', [
                'user_id' => $userId
            ]);
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Find user by email or username
     */
    private function findUserByIdentifier(string $identifier): ?array
    {
        // Try email first
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$identifier]
        );
        
        // Try username if email not found
        if (!$user) {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE username = ? AND deleted_at IS NULL",
                [$identifier]
            );
        }
        
        return $user;
    }
    
    /**
     * Update user login data
     */
    private function updateUserLoginData(int $userId, bool $remember): void
    {
        $now = date('Y-m-d H:i:s');
        
        // Update last activity
        $this->db->query(
            "UPDATE users SET last_activity_at = ? WHERE id = ?",
            [$now, $userId]
        );
        
        // Handle remember me functionality
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Store token in database
            $this->db->query(
                "INSERT INTO remember_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, ?)",
                [$userId, $token, $expires, $now]
            );
            
            // Set cookie
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
        }
    }
    
    /**
     * Clear remember token
     */
    private function clearRememberToken(string $token): void
    {
        $this->db->query(
            "DELETE FROM remember_tokens WHERE token = ? OR expires_at < NOW()",
            [$token]
        );
    }
    
    /**
     * Check remember token
     */
    public function checkRememberToken(string $token): ?array
    {
        $tokenData = $this->db->fetchOne(
            "SELECT rt.*, u.id, u.username, u.email, u.role, u.status 
             FROM remember_tokens rt 
             JOIN users u ON rt.user_id = u.id 
             WHERE rt.token = ? AND rt.expires_at > NOW() AND u.deleted_at IS NULL",
            [$token]
        );
        
        if ($tokenData) {
            // Auto-login user
            $_SESSION['user_id'] = $tokenData['id'];
            $_SESSION['user_role'] = $tokenData['role'];
            $_SESSION['login_time'] = time();
            
            // Update last activity
            $this->db->query(
                "UPDATE users SET last_activity_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $tokenData['id']]
            );
            
            // Clear used token
            $this->db->query(
                "DELETE FROM remember_tokens WHERE token = ?",
                [$token]
            );
        }
        
        return $tokenData;
    }
}
