<?php

declare(strict_types=1);

namespace IMEIBot\Controllers;

use IMEIBot\Services\JWTService;
use IMEIBot\Services\EmailService;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class AuthController
{
    private JWTService $jwtService;
    private Logger $logger;
    private EmailService $emailService;

    public function __construct(JWTService $jwtService, Logger $logger, EmailService $emailService)
    {
        $this->jwtService = $jwtService;
        $this->logger = $logger;
        $this->emailService = $emailService;
    }

    public function register(): void
    {
        header('Content-Type: application/json');

        try {
            $input = $this->getJsonInput();
            
            // Validate input
            $this->validateRegistrationInput($input);

            // Check if user already exists
            if (User::findByEmail($input['email'])) {
                $this->sendErrorResponse('Email already registered', 409);
                return;
            }

            if (!empty($input['username']) && User::findByUsername($input['username'])) {
                $this->sendErrorResponse('Username already taken', 409);
                return;
            }

            // Create user
            $userData = [
                'email' => $input['email'],
                'username' => $input['username'] ?? null,
                'first_name' => $input['first_name'] ?? null,
                'last_name' => $input['last_name'] ?? null,
                'phone' => $input['phone'] ?? null,
                'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
                'role' => 'user',
                'status' => 'active',
                'language' => $input['language'] ?? 'en',
                'timezone' => $input['timezone'] ?? 'UTC',
                'preferences' => json_encode($input['preferences'] ?? []),
                'referral_code' => $this->generateReferralCode(),
                'referred_by' => $input['referral_code'] ?? null
            ];

            $user = User::create($userData);

            if (!$user) {
                $this->sendErrorResponse('Failed to create user', 500);
                return;
            }

            // Generate tokens
            $tokens = $this->jwtService->generateToken([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'username' => $user['username']
            ]);

            // Update user with API token
            User::updateApiToken($user['id'], $tokens['access_token']);

            // Send welcome email
            try {
                $this->emailService->sendWelcomeEmail($user['email'], $user['first_name'] ?? $user['username']);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to send welcome email', [
                    'user_id' => $user['id'],
                    'error' => $e->getMessage()
                ]);
            }

            // Handle referral
            if (!empty($userData['referred_by'])) {
                $this->handleReferral($userData['referred_by'], $user['id']);
            }

            $this->logger->info('User registered successfully', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $this->sendSuccessResponse([
                'user' => $this->sanitizeUser($user),
                'tokens' => $tokens
            ], 'User registered successfully', 201);

        } catch (\Exception $e) {
            $this->logger->error('Registration failed', [
                'error' => $e->getMessage(),
                'input' => $this->sanitizeInput($input ?? [])
            ]);
            $this->sendErrorResponse('Registration failed: ' . $e->getMessage());
        }
    }

    public function login(): void
    {
        header('Content-Type: application/json');

        try {
            $input = $this->getJsonInput();
            
            // Validate input
            $this->validateLoginInput($input);

            // Find user by email or username
            $user = User::findByEmail($input['identifier']);
            if (!$user) {
                $user = User::findByUsername($input['identifier']);
            }

            if (!$user) {
                $this->sendErrorResponse('Invalid credentials', 401);
                return;
            }

            // Check password
            if (!password_verify($input['password'], $user['password_hash'])) {
                $this->logger->warning('Invalid login attempt', [
                    'identifier' => $input['identifier'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                $this->sendErrorResponse('Invalid credentials', 401);
                return;
            }

            // Check account status
            if ($user['status'] !== 'active') {
                $this->sendErrorResponse('Account is not active', 403);
                return;
            }

            // Generate tokens
            $tokens = $this->jwtService->generateToken([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'username' => $user['username']
            ]);

            // Update API token and last login
            User::updateApiToken($user['id'], $tokens['access_token']);
            User::updateLastLogin($user['id']);

            $this->logger->info('User logged in successfully', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $this->sendSuccessResponse([
                'user' => $this->sanitizeUser($user),
                'tokens' => $tokens
            ], 'Login successful');

        } catch (\Exception $e) {
            $this->logger->error('Login failed', [
                'error' => $e->getMessage(),
                'input' => $this->sanitizeInput($input ?? [])
            ]);
            $this->sendErrorResponse('Login failed: ' . $e->getMessage());
        }
    }

    public function refreshToken(): void
    {
        header('Content-Type: application/json');

        try {
            $input = $this->getJsonInput();
            
            if (empty($input['refresh_token'])) {
                $this->sendErrorResponse('Refresh token is required', 400);
                return;
            }

            $tokens = $this->jwtService->refreshToken($input['refresh_token']);

            // Get user from token and update API token
            $payload = $this->jwtService->validateToken($tokens['access_token']);
            User::updateApiToken($payload['user_id'], $tokens['access_token']);

            $this->logger->info('Token refreshed successfully', [
                'user_id' => $payload['user_id']
            ]);

            $this->sendSuccessResponse([
                'tokens' => $tokens
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            $this->logger->error('Token refresh failed', [
                'error' => $e->getMessage()
            ]);
            $this->sendErrorResponse('Token refresh failed: ' . $e->getMessage(), 401);
        }
    }

    public function logout(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $this->jwtService->extractTokenFromHeader();
            
            if ($token) {
                $payload = $this->jwtService->validateToken($token);
                User::updateApiToken($payload['user_id'], null);
                
                $this->logger->info('User logged out successfully', [
                    'user_id' => $payload['user_id']
                ]);
            }

            $this->sendSuccessResponse([], 'Logout successful');

        } catch (\Exception $e) {
            $this->logger->error('Logout failed', [
                'error' => $e->getMessage()
            ]);
            $this->sendErrorResponse('Logout failed: ' . $e->getMessage());
        }
    }

    public function me(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $this->jwtService->extractTokenFromHeader();
            
            if (!$token) {
                $this->sendErrorResponse('No token provided', 401);
                return;
            }

            $payload = $this->jwtService->validateToken($token);
            $user = User::findById($payload['user_id']);

            if (!$user || $user['status'] !== 'active') {
                $this->sendErrorResponse('User not found or inactive', 404);
                return;
            }

            $this->sendSuccessResponse([
                'user' => $this->sanitizeUser($user)
            ], 'User data retrieved');

        } catch (\Exception $e) {
            $this->logger->error('Get user data failed', [
                'error' => $e->getMessage()
            ]);
            $this->sendErrorResponse('Failed to get user data: ' . $e->getMessage(), 401);
        }
    }

    private function validateRegistrationInput(array $input): void
    {
        $validator = v::key('email', v::email()->notEmpty())
            ->key('password', v::stringType()->length(8, 255))
            ->key('first_name', v::optional(v::stringType()->length(1, 100)))
            ->key('last_name', v::optional(v::stringType()->length(1, 100)))
            ->key('username', v::optional(v::alnum()->length(3, 50)))
            ->key('phone', v::optional(v::phone()))
            ->key('language', v::optional(v::in(['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ar', 'bn'])))
            ->key('timezone', v::optional(v::stringType()));

        try {
            $validator->assert($input);
        } catch (NestedValidationException $e) {
            $errors = [];
            foreach ($e->getMessages() as $message) {
                $errors[] = $message;
            }
            $this->sendErrorResponse('Validation failed: ' . implode(', ', $errors), 400);
        }
    }

    private function validateLoginInput(array $input): void
    {
        $validator = v::key('identifier', v::stringType()->notEmpty())
            ->key('password', v::stringType()->notEmpty());

        try {
            $validator->assert($input);
        } catch (NestedValidationException $e) {
            $errors = [];
            foreach ($e->getMessages() as $message) {
                $errors[] = $message;
            }
            $this->sendErrorResponse('Validation failed: ' . implode(', ', $errors), 400);
        }
    }

    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (User::findByReferralCode($code));

        return $code;
    }

    private function handleReferral(string $referralCode, int $newUserId): void
    {
        $referrer = User::findByReferralCode($referralCode);
        
        if ($referrer) {
            User::incrementReferralCount($referrer['id']);
            
            // Award referral bonus
            User::incrementDailyLimits($referrer['id'], [
                'imei_limit' => 5,
                'download_limit' => 2
            ]);

            $this->logger->info('Referral processed', [
                'referrer_id' => $referrer['id'],
                'new_user_id' => $newUserId,
                'referral_code' => $referralCode
            ]);
        }
    }

    private function sanitizeUser(array $user): array
    {
        unset($user['password_hash'], $user['api_token']);
        return $user;
    }

    private function sanitizeInput(array $input): array
    {
        $sensitive = ['password', 'password_hash', 'api_token'];
        return array_diff_key($input, array_flip($sensitive));
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
}
