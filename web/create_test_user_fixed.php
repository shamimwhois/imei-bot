<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

try {
    $db = Database::getInstance(
        require __DIR__ . '/../config/database.php',
        new Logger(require __DIR__ . '/../config/monitoring.php')
    );
    
    echo "Creating test user...\n";
    
    // Check if test user already exists
    $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", ['test@example.com']);
    
    if ($existingUser) {
        echo "Test user already exists with ID: {$existingUser['id']}\n";
    } else {
        // Create test user with correct column names
        $userData = [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'telegram_id' => 123456789,
            'whatsapp_phone' => '+8801234567890',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '+8801234567890',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active',
            'language' => 'en',
            'timezone' => 'UTC',
            'preferences' => json_encode(['theme' => 'light', 'notifications' => true]),
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_activity_at' => date('Y-m-d H:i:s'),
            'email_verified_at' => date('Y-m-d H:i:s'),
            'phone_verified_at' => date('Y-m-d H:i:s'),
            'premium_expires_at' => null,
            'api_token' => bin2hex(random_bytes(32)),
            'api_token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'rate_limit_tier' => 'basic',
            'daily_imei_limit' => 10,
            'daily_download_limit' => 50,
            'total_imei_generated' => 0,
            'total_files_downloaded' => 0,
            'storage_quota_mb' => 1000,
            'storage_used_mb' => 0,
            'referral_code' => 'TEST123',
            'referred_by' => null,
            'referral_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $db->insert('users', $userData);
        echo "Test user created with ID: {$userId}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
