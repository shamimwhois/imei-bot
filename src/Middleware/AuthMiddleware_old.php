<?php

declare(strict_types=1);

namespace IMEIBot\Middleware;

use IMEIBot\Services\JWTService;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;

class AuthMiddleware
{
    private Database $db;
    private Logger $logger;

    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function authenticate(): ?array
    {
        // Check if user is authenticated via session
        if (isset($_SESSION['user_id']) {
            return User::findByIdAsArray($_SESSION['user_id']);
        }
        
        return null;
    }

            $payload = $this->jwtService->validateToken($token);
            
            // Verify user exists and is active
            $user = User::findById($payload['user_id'] ?? null);
            
            if (!$user) {
                throw new \RuntimeException('User not found');
            }

            if ($user['status'] !== 'active') {
                throw new \RuntimeException('User account is not active');
            }

            // Update last activity
            User::updateLastActivity($user['id']);

            $this->logger->info('User authenticated successfully', [
                'user_id' => $user['id'],
                'role' => $user['role'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            return $user;

        } catch (\Exception $e) {
            $this->logger->warning('Authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $this->sendUnauthorizedResponse($e->getMessage());
            return null;
        }
    }

    public function requireRole(array $allowedRoles): ?array
    {
        $user = $this->authenticate();
        
        if (!$user) {
            return null;
        }

        if (!in_array($user['role'], $allowedRoles)) {
            $this->sendForbiddenResponse('Insufficient permissions');
            return null;
        }

        return $user;
    }

    public function requireAdmin(): ?array
    {
        return $this->requireRole(['admin', 'super_admin']);
    }

    public function requireSuperAdmin(): ?array
    {
        return $this->requireRole(['super_admin']);
    }

    public function requirePremium(): ?array
    {
        return $this->requireRole(['premium', 'admin', 'super_admin']);
    }

    public function optionalAuth(): ?array
    {
        try {
            $token = $this->jwtService->extractTokenFromHeader();
            
            if (!$token) {
                return null;
            }

            $payload = $this->jwtService->validateToken($token);
            $user = User::findById($payload['user_id'] ?? null);
            
            if ($user && $user['status'] === 'active') {
                User::updateLastActivity($user['id']);
                return $user;
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->debug('Optional authentication failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function rateLimitByUser(?array $user, string $tier = 'free'): bool
    {
        if (!$user) {
            return $this->checkAnonymousRateLimit();
        }

        $limits = $this->getRateLimits($tier);
        $key = "rate_limit:user:{$user['id']}:" . date('Y-m-d-H');
        
        // This would integrate with Redis/cache for rate limiting
        // For now, we'll do a basic check
        return true; // Placeholder - implement actual rate limiting
    }

    private function getRateLimits(string $tier): array
    {
        $limits = [
            'free' => ['requests_per_hour' => 100, 'requests_per_day' => 1000],
            'basic' => ['requests_per_hour' => 500, 'requests_per_day' => 10000],
            'premium' => ['requests_per_hour' => 2000, 'requests_per_day' => 50000],
            'enterprise' => ['requests_per_hour' => 10000, 'requests_per_day' => 1000000]
        ];

        return $limits[$tier] ?? $limits['free'];
    }

    private function checkAnonymousRateLimit(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit:anonymous:{$ip}:" . date('Y-m-d-H');
        
        // Implement anonymous rate limiting
        return true; // Placeholder
    }

    private function sendUnauthorizedResponse(string $message): void
    {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
            'timestamp' => time()
        ]);
        exit;
    }

    private function sendForbiddenResponse(string $message): void
    {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Forbidden',
            'message' => $message,
            'timestamp' => time()
        ]);
        exit;
    }
}
