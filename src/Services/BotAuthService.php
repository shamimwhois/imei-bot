<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use IMEIBot\Services\JWTService;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;

class BotAuthService
{
    private JWTService $jwtService;
    private Logger $logger;

    public function __construct(JWTService $jwtService, Logger $logger)
    {
        $this->jwtService = $jwtService;
        $this->logger = $logger;
    }

    public function authenticateTelegramUser(array $telegramUser): array
    {
        $telegramId = $telegramUser['id'] ?? null;
        $username = $telegramUser['username'] ?? null;
        $firstName = $telegramUser['first_name'] ?? null;
        $lastName = $telegramUser['last_name'] ?? null;
        $languageCode = $telegramUser['language_code'] ?? 'en';

        if (!$telegramId) {
            throw new \InvalidArgumentException('Telegram ID is required');
        }

        // Find existing user by Telegram ID
        $user = User::findByTelegramId($telegramId);

        if (!$user) {
            // Try to find by username if available
            if ($username) {
                $user = User::findByUsername($username);
                if ($user && !$user['telegram_id']) {
                    // Link existing user to this Telegram account
                    User::updateTelegramId($user['id'], $telegramId);
                    $user = User::findByTelegramId($telegramId);
                }
            }
        }

        if (!$user) {
            // Create new user
            $userData = [
                'telegram_id' => $telegramId,
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'language' => $this->mapTelegramLanguage($languageCode),
                'role' => 'user',
                'status' => 'active',
                'preferences' => json_encode([
                    'telegram_verified' => true,
                    'notification_enabled' => true
                ])
            ];

            $user = User::create($userData);
            
            if (!$user) {
                throw new \RuntimeException('Failed to create Telegram user');
            }

            $this->logger->info('New Telegram user created', [
                'telegram_id' => $telegramId,
                'username' => $username,
                'user_id' => $user['id']
            ]);
        } else {
            // Update user info
            User::updateTelegramUserInfo($user['id'], [
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'language' => $this->mapTelegramLanguage($languageCode)
            ]);

            // Update last activity
            User::updateLastActivity($user['id']);
        }

        // Generate JWT token for bot API access
        $tokens = $this->jwtService->generateToken([
            'user_id' => $user['id'],
            'telegram_id' => $telegramId,
            'role' => $user['role'],
            'platform' => 'telegram'
        ]);

        // Update API token
        User::updateApiToken($user['id'], $tokens['access_token']);

        $this->logger->info('Telegram user authenticated', [
            'user_id' => $user['id'],
            'telegram_id' => $telegramId,
            'username' => $username
        ]);

        return [
            'user' => $user,
            'tokens' => $tokens,
            'platform' => 'telegram'
        ];
    }

    public function authenticateWhatsAppUser(string $phoneNumber, array $profileData = []): array
    {
        // Normalize phone number
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if (!$phoneNumber) {
            throw new \InvalidArgumentException('Valid phone number is required');
        }

        // Find existing user by WhatsApp phone
        $user = User::findByWhatsAppPhone($phoneNumber);

        if (!$user) {
            // Try to find by regular phone number
            $user = User::findByPhone($phoneNumber);
            if ($user && !$user['whatsapp_phone']) {
                // Link existing user to this WhatsApp account
                User::updateWhatsAppPhone($user['id'], $phoneNumber);
                $user = User::findByWhatsAppPhone($phoneNumber);
            }
        }

        if (!$user) {
            // Create new user
            $userData = [
                'whatsapp_phone' => $phoneNumber,
                'phone' => $phoneNumber,
                'first_name' => $profileData['first_name'] ?? null,
                'last_name' => $profileData['last_name'] ?? null,
                'language' => $profileData['language'] ?? 'en',
                'role' => 'user',
                'status' => 'active',
                'preferences' => json_encode([
                    'whatsapp_verified' => true,
                    'notification_enabled' => true
                ])
            ];

            $user = User::create($userData);
            
            if (!$user) {
                throw new \RuntimeException('Failed to create WhatsApp user');
            }

            $this->logger->info('New WhatsApp user created', [
                'phone' => $phoneNumber,
                'user_id' => $user['id']
            ]);
        } else {
            // Update user info
            User::updateWhatsAppUserInfo($user['id'], [
                'first_name' => $profileData['first_name'] ?? null,
                'last_name' => $profileData['last_name'] ?? null,
                'language' => $profileData['language'] ?? 'en'
            ]);

            // Update last activity
            User::updateLastActivity($user['id']);
        }

        // Generate JWT token for bot API access
        $tokens = $this->jwtService->generateToken([
            'user_id' => $user['id'],
            'whatsapp_phone' => $phoneNumber,
            'role' => $user['role'],
            'platform' => 'whatsapp'
        ]);

        // Update API token
        User::updateApiToken($user['id'], $tokens['access_token']);

        $this->logger->info('WhatsApp user authenticated', [
            'user_id' => $user['id'],
            'phone' => $phoneNumber
        ]);

        return [
            'user' => $user,
            'tokens' => $tokens,
            'platform' => 'whatsapp'
        ];
    }

    public function validateBotToken(string $token): ?array
    {
        try {
            $payload = $this->jwtService->validateToken($token);
            
            if (!isset($payload['platform']) || !in_array($payload['platform'], ['telegram', 'whatsapp'])) {
                throw new \RuntimeException('Invalid platform token');
            }

            $user = User::findByIdAsArray($payload['user_id']);
            
            if (!$user || $user['status'] !== 'active') {
                throw new \RuntimeException('User not found or inactive');
            }

            // Verify platform-specific identifier matches
            if ($payload['platform'] === 'telegram' && $user['telegram_id'] != $payload['telegram_id']) {
                throw new \RuntimeException('Telegram ID mismatch');
            }

            if ($payload['platform'] === 'whatsapp' && $user['whatsapp_phone'] !== $payload['whatsapp_phone']) {
                throw new \RuntimeException('WhatsApp phone mismatch');
            }

            return [
                'user' => $user,
                'platform' => $payload['platform']
            ];

        } catch (\Exception $e) {
            $this->logger->warning('Bot token validation failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            return null;
        }
    }

    public function linkBotToWebUser(int $userId, string $platform, string $identifier): bool
    {
        $user = User::findByIdAsArray($userId);
        
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $success = false;

        switch ($platform) {
            case 'telegram':
                if (!$user['telegram_id']) {
                    $success = User::updateTelegramId($userId, (int) $identifier);
                }
                break;
                
            case 'whatsapp':
                $phoneNumber = $this->normalizePhoneNumber($identifier);
                if (!$user['whatsapp_phone'] && $phoneNumber) {
                    $success = User::updateWhatsAppPhone($userId, $phoneNumber);
                }
                break;
                
            default:
                throw new \InvalidArgumentException('Unsupported platform: ' . $platform);
        }

        if ($success) {
            $this->logger->info('Bot linked to web user', [
                'user_id' => $userId,
                'platform' => $platform,
                'identifier' => $identifier
            ]);
        }

        return $success;
    }

    public function unlinkBotFromWebUser(int $userId, string $platform): bool
    {
        $user = User::findByIdAsArray($userId);
        
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $success = false;

        switch ($platform) {
            case 'telegram':
                if ($user['telegram_id']) {
                    $success = User::updateTelegramId($userId, null);
                }
                break;
                
            case 'whatsapp':
                if ($user['whatsapp_phone']) {
                    $success = User::updateWhatsAppPhone($userId, null);
                }
                break;
                
            default:
                throw new \InvalidArgumentException('Unsupported platform: ' . $platform);
        }

        if ($success) {
            $this->logger->info('Bot unlinked from web user', [
                'user_id' => $userId,
                'platform' => $platform
            ]);
        }

        return $success;
    }

    public function generateBotAuthLink(int $userId, string $platform): string
    {
        $user = User::findByIdAsArray($userId);
        
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        // Generate a temporary auth token
        $authToken = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Store auth token (you might want to use Redis for this)
        $_SESSION['bot_auth_tokens'][$authToken] = [
            'user_id' => $userId,
            'platform' => $platform,
            'expires_at' => $expiresAt
        ];

        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
        
        switch ($platform) {
            case 'telegram':
                $botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'imei_bot';
                return "https://t.me/{$botUsername}?start={$authToken}";
                
            case 'whatsapp':
                return "{$baseUrl}/auth/whatsapp?token={$authToken}";
                
            default:
                throw new \InvalidArgumentException('Unsupported platform: ' . $platform);
        }
    }

    public function consumeBotAuthToken(string $authToken): ?array
    {
        if (!isset($_SESSION['bot_auth_tokens'][$authToken])) {
            return null;
        }

        $tokenData = $_SESSION['bot_auth_tokens'][$authToken];
        
        // Check if token has expired
        if (strtotime($tokenData['expires_at']) < time()) {
            unset($_SESSION['bot_auth_tokens'][$authToken]);
            return null;
        }

        // Remove consumed token
        unset($_SESSION['bot_auth_tokens'][$authToken]);

        return $tokenData;
    }

    private function mapTelegramLanguage(string $languageCode): string
    {
        $languageMap = [
            'en' => 'en',
            'es' => 'es',
            'fr' => 'fr',
            'de' => 'de',
            'it' => 'it',
            'pt' => 'pt',
            'ru' => 'ru',
            'zh' => 'zh',
            'ja' => 'ja',
            'ar' => 'ar',
            'bn' => 'bn'
        ];

        return $languageMap[$languageCode] ?? 'en';
    }

    private function normalizePhoneNumber(string $phoneNumber): ?string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Remove leading + if present
        if (strpos($phoneNumber, '+') === 0) {
            $phoneNumber = substr($phoneNumber, 1);
        }
        
        // Basic validation - should be 10-15 digits
        if (strlen($phoneNumber) < 10 || strlen($phoneNumber) > 15) {
            return null;
        }
        
        return '+' . $phoneNumber;
    }
}
