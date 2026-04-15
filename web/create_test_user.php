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
        // Create test user
        $userData = [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'telegram_id' => 123456789,
            'whatsapp_phone' => '+8801234567890',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'user',
            'status' => 'active',
            'language' => 'en',
            'timezone' => 'UTC',
            'is_premium' => 0,
            'premium_expires_at' => null,
            'daily_imei_limit' => 10,
            'total_imei_limit' => 1000,
            'total_files_downloaded' => 0,
            'storage_quota_mb' => 1000,
            'storage_used_mb' => 0,
            'referral_code' => 'TEST123',
            'referred_by' => null,
            'last_activity_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $db->insert('users', $userData);
        echo "Test user created with ID: {$userId}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
