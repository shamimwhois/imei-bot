<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

/**
 * User Seeder Class
 * Creates sample users for testing and development
 */
class UserSeeder
{
    private Database $db;
    private Logger $logger;
    private $auth;
    
    public function __construct(Database $db, Logger $logger, $auth)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->auth = $auth;
    }

/**
 * User Seeder Class
 * Creates sample users for testing and development
 */
class UserSeeder
{
    private Database $db;
    private Logger $logger;
    private AuthMiddleware $auth;
    
    public function __construct(Database $db, Logger $logger, AuthMiddleware $auth)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->auth = $auth;
    }
    
    /**
     * Seed users table with sample data
     */
    public function seedUsers(): void
    {
        $this->logger->info('Starting user seeding');
        
        try {
            // Sample users for testing
            $users = [
                [
                    'uuid' => '550e8400-e29b-41d4-a716-4466-5540-4250-418a-473a-473a-473a',
                    'telegram_id' => 123456789,
                    'email' => 'admin@imei-bot.com',
                    'username' => 'admin',
                    'first_name' => 'Admin',
                    'last_name' => 'User',
                    'phone' => '+8801234567890',
                    'password_hash' => password_hash('Admin123!', PASSWORD_DEFAULT),
                    'role' => 'super_admin',
                    'status' => 'active',
                    'language' => 'en',
                    'timezone' => 'UTC',
                    'preferences' => json_encode(['theme' => 'dark', 'notifications' => true]),
                    'last_login_at' => date('Y-m-d H:i:s'),
                    'last_activity_at' => date('Y-m-d H:i:s'),
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'phone_verified_at' => date('Y-m-d H:i:s'),
                    'premium_expires_at' => null,
                    'api_token' => 'admin_token_' . bin2hex(random_bytes(16)),
                    'api_token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'rate_limit_tier' => 'enterprise',
                    'daily_imei_limit' => 1000,
                    'daily_download_limit' => 500,
                    'total_imei_generated' => 0,
                    'total_files_downloaded' => 0,
                    'storage_quota_mb' => 10000,
                    'storage_used_mb' => 0,
                    'referral_code' => 'ADMIN2024',
                    'referred_by' => null,
                    'referral_count' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a716-4466-5540-4250-418a-473a',
                    'telegram_id' => 987654321,
                    'email' => 'user@imei-bot.com',
                    'username' => 'testuser',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'phone' => '+880987654321',
                    'password_hash' => password_hash('TestPassword123!', PASSWORD_DEFAULT),
                    'role' => 'user',
                    'status' => 'active',
                    'language' => 'en',
                    'timezone' => 'UTC',
                    'preferences' => json_encode(['theme' => 'light', 'notifications' => true]),
                    'last_login_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
                    'last_activity_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                    'email_verified_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'phone_verified_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'premium_expires_at' => null,
                    'api_token' => 'user_token_' . bin2hex(random_bytes(16)),
                    'api_token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'rate_limit_tier' => 'free',
                    'daily_imei_limit' => 10,
                    'daily_download_limit' => 5,
                    'total_imei_generated' => 0,
                    'total_files_downloaded' => 0,
                    'storage_quota_mb' => 100,
                    'storage_used_mb' => 0,
                    'referral_code' => 'USER2024',
                    'referred_by' => null,
                    'referral_count' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a716-4466-5540-4250-418a-473a',
                    'telegram_id' => 987654322,
                    'email' => 'premium@imei-bot.com',
                    'username' => 'premiumuser',
                    'first_name' => 'Premium',
                    'last_name' => 'User',
                    'phone' => '+880987654322',
                    'password_hash' => password_hash('PremiumPassword123!', PASSWORD_DEFAULT),
                    'role' => 'premium',
                    'status' => 'active',
                    'language' => 'en',
                    'timezone' => 'UTC',
                    'preferences' => json_encode(['theme' => 'light', 'notifications' => false]),
                    'last_login_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
                    'last_activity_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'email_verified_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                    'phone_verified_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                    'premium_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'api_token' => 'premium_token_' . bin2hex(random_bytes(16)),
                    'api_token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'rate_limit_tier' => 'premium',
                    'daily_imei_limit' => 100,
                    'daily_download_limit' => 50,
                    'total_imei_generated' => 0,
                    'total_files_downloaded' => 0,
                    'storage_quota_mb' => 5000,
                    'storage_used_mb' => 0,
                    'referral_code' => 'PREMIUM2024',
                    'referred_by' => null,
                    'referral_count' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ];
            
            // Clear existing users
            $this->db->query("DELETE FROM users WHERE email LIKE '%@imei-bot.com'");
            
            // Insert sample users
            foreach ($users as $user) {
                $this->db->query(
                    "INSERT INTO users (uuid, telegram_id, email, username, first_name, last_name, phone, password_hash, role, status, language, timezone, preferences, last_login_at, last_activity_at, email_verified_at, phone_verified_at, premium_expires_at, api_token, api_token_expires_at, rate_limit_tier, daily_imei_limit, daily_download_limit, total_imei_generated, total_files_downloaded, storage_quota_mb, storage_used_mb, referral_code, referred_by, referral_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $user['uuid'],
                        $user['telegram_id'],
                        $user['email'],
                        $user['username'],
                        $user['first_name'],
                        $user['last_name'],
                        $user['phone'],
                        $user['password_hash'],
                        $user['role'],
                        $user['status'],
                        $user['language'],
                        $user['timezone'],
                        $user['preferences'],
                        $user['last_login_at'],
                        $user['last_activity_at'],
                        $user['email_verified_at'],
                        $user['phone_verified_at'],
                        $user['premium_expires_at'],
                        $user['api_token'],
                        $user['api_token_expires_at'],
                        $user['rate_limit_tier'],
                        $user['daily_imei_limit'],
                        $user['daily_download_limit'],
                        $user['total_imei_generated'],
                        $user['total_files_downloaded'],
                        $user['storage_quota_mb'],
                        $user['storage_used_mb'],
                        $user['referral_code'],
                        $user['referred_by'],
                        $user['referral_count'],
                        $user['created_at'],
                        $user['updated_at']
                    ]
                );
                
                $this->logger->info('User created', [
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]);
            }
            
            $this->logger->info('User seeding completed', [
                'total_users' => count($users)
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('User seeding failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Seed files table with sample data
     */
    public function seedFiles(): void
    {
        $this->logger->info('Starting file seeding');
        
        try {
            // Sample files for testing
            $files = [
                [
                    'user_id' => 1, // Admin user
                    'filename' => 'firmware_v1.0.zip',
                    'original_filename' => 'firmware_v1.0.zip',
                    'file_path' => '/uploads/firmware/firmware_v1.0.zip',
                    'file_size' => 52428800, // 50 MB in bytes
                    'mime_type' => 'application/zip',
                    'download_count' => 0,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'user_id' => 1, // Admin user
                    'filename' => 'tool_v2.1.apk',
                    'original_filename' => 'tool_v2.1.apk',
                    'file_path' => '/uploads/tools/tool_v2.1.apk',
                    'file_size' => 10485760, // 10 MB in bytes
                    'mime_type' => 'application/vnd.android.package-archive',
                    'download_count' => 0,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'user_id' => 2, // Premium user
                    'filename' => 'manual_v3.2.pdf',
                    'original_filename' => 'manual_v3.2.pdf',
                    'file_path' => '/uploads/manuals/manual_v3.2.pdf',
                    'file_size' => 2097152, // 2 MB in bytes
                    'mime_type' => 'application/pdf',
                    'download_count' => 0,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ];
            
            // Clear existing files
            $this->db->query("DELETE FROM files WHERE filename LIKE '%v%'");
            
            // Insert sample files
            foreach ($files as $file) {
                $this->db->query(
                    "INSERT INTO files (user_id, filename, original_filename, file_path, file_size, mime_type, download_count, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $file['user_id'],
                        $file['filename'],
                        $file['original_filename'],
                        $file['file_path'],
                        $file['file_size'],
                        $file['mime_type'],
                        $file['download_count'],
                        $file['status'],
                        $file['created_at'],
                        $file['updated_at']
                    ]
                );
                
                $this->logger->info('File created', [
                    'filename' => $file['filename'],
                    'user_id' => $file['user_id']
                ]);
            }
            
            $this->logger->info('File seeding completed', [
                'total_files' => count($files)
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('File seeding failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Run all seeders
     */
    public function run(): void
    {
        $this->logger->info('Starting database seeding');
        
        try {
            $this->seedUsers();
            $this->seedFiles();
            
            $this->logger->info('Database seeding completed successfully');
            
        } catch (Exception $e) {
            $this->logger->error('Database seeding failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
