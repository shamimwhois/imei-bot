<?php

declare(strict_types=1);

namespace IMEIBot\Models;

use IMEIBot\Core\Database;
use Ramsey\Uuid\Uuid;

class User
{
    private array $attributes = [];
    private Database $db;
    
    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge([
            'uuid' => Uuid::uuid4()->toString(),
            'telegram_id' => null,
            'whatsapp_phone' => null,
            'email' => null,
            'username' => null,
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'password_hash' => null,
            'role' => 'user',
            'status' => 'active',
            'language' => 'en',
            'timezone' => 'UTC',
            'preferences' => null,
            'last_login_at' => null,
            'last_activity_at' => null,
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'premium_expires_at' => null,
            'api_token' => null,
            'api_token_expires_at' => null,
            'rate_limit_tier' => 'free',
            'daily_imei_limit' => 10,
            'daily_download_limit' => 5,
            'total_imei_generated' => 0,
            'total_files_downloaded' => 0,
            'storage_quota_mb' => 100,
            'storage_used_mb' => 0,
            'referral_code' => null,
            'referred_by' => null,
            'referral_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $attributes);
        
        $this->db = Database::getInstance(
            $this->getConfig('database'),
            $this->getLogger()
        );
    }
    
    public function save(): bool
    {
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($this->attributes['id'])) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }
    
    private function insert(): bool
    {
        $sql = "
            INSERT INTO users (
                uuid, telegram_id, whatsapp_phone, email, username, first_name, last_name,
                phone, password_hash, role, status, language, timezone, preferences,
                last_login_at, last_activity_at, email_verified_at, phone_verified_at,
                premium_expires_at, api_token, api_token_expires_at, rate_limit_tier,
                daily_imei_limit, daily_download_limit, total_imei_generated, total_files_downloaded,
                storage_quota_mb, storage_used_mb, referral_code, referred_by, referral_count,
                created_at, updated_at, deleted_at
            ) VALUES (
                :uuid, :telegram_id, :whatsapp_phone, :email, :username, :first_name, :last_name,
                :phone, :password_hash, :role, :status, :language, :timezone, :preferences,
                :last_login_at, :last_activity_at, :email_verified_at, :phone_verified_at,
                :premium_expires_at, :api_token, :api_token_expires_at, :rate_limit_tier,
                :daily_imei_limit, :daily_download_limit, :total_imei_generated, :total_files_downloaded,
                :storage_quota_mb, :storage_used_mb, :referral_code, :referred_by, :referral_count,
                :created_at, :updated_at, :deleted_at
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':uuid' => $this->attributes['uuid'],
                ':telegram_id' => $this->attributes['telegram_id'],
                ':whatsapp_phone' => $this->attributes['whatsapp_phone'],
                ':email' => $this->attributes['email'],
                ':username' => $this->attributes['username'],
                ':first_name' => $this->attributes['first_name'],
                ':last_name' => $this->attributes['last_name'],
                ':phone' => $this->attributes['phone'],
                ':password_hash' => $this->attributes['password_hash'],
                ':role' => $this->attributes['role'],
                ':status' => $this->attributes['status'],
                ':language' => $this->attributes['language'],
                ':timezone' => $this->attributes['timezone'],
                ':preferences' => $this->attributes['preferences'] ? json_encode($this->attributes['preferences']) : null,
                ':last_login_at' => $this->attributes['last_login_at'],
                ':last_activity_at' => $this->attributes['last_activity_at'],
                ':email_verified_at' => $this->attributes['email_verified_at'],
                ':phone_verified_at' => $this->attributes['phone_verified_at'],
                ':premium_expires_at' => $this->attributes['premium_expires_at'],
                ':api_token' => $this->attributes['api_token'],
                ':api_token_expires_at' => $this->attributes['api_token_expires_at'],
                ':rate_limit_tier' => $this->attributes['rate_limit_tier'],
                ':daily_imei_limit' => $this->attributes['daily_imei_limit'],
                ':daily_download_limit' => $this->attributes['daily_download_limit'],
                ':total_imei_generated' => $this->attributes['total_imei_generated'],
                ':total_files_downloaded' => $this->attributes['total_files_downloaded'],
                ':storage_quota_mb' => $this->attributes['storage_quota_mb'],
                ':storage_used_mb' => $this->attributes['storage_used_mb'],
                ':referral_code' => $this->attributes['referral_code'],
                ':referred_by' => $this->attributes['referred_by'],
                ':referral_count' => $this->attributes['referral_count'],
                ':created_at' => $this->attributes['created_at'],
                ':updated_at' => $this->attributes['updated_at'],
                ':deleted_at' => $this->attributes['deleted_at'],
            ]);
            
            $this->attributes['id'] = $this->db->lastInsertId();
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Failed to insert user: " . $e->getMessage());
            return false;
        }
    }
    
    private function update(): bool
    {
        $sql = "
            UPDATE users SET
                telegram_id = :telegram_id,
                whatsapp_phone = :whatsapp_phone,
                email = :email,
                username = :username,
                first_name = :first_name,
                last_name = :last_name,
                phone = :phone,
                password_hash = :password_hash,
                role = :role,
                status = :status,
                language = :language,
                timezone = :timezone,
                preferences = :preferences,
                last_login_at = :last_login_at,
                last_activity_at = :last_activity_at,
                email_verified_at = :email_verified_at,
                phone_verified_at = :phone_verified_at,
                premium_expires_at = :premium_expires_at,
                api_token = :api_token,
                api_token_expires_at = :api_token_expires_at,
                rate_limit_tier = :rate_limit_tier,
                daily_imei_limit = :daily_imei_limit,
                daily_download_limit = :daily_download_limit,
                total_imei_generated = :total_imei_generated,
                total_files_downloaded = :total_files_downloaded,
                storage_quota_mb = :storage_quota_mb,
                storage_used_mb = :storage_used_mb,
                referral_code = :referral_code,
                referred_by = :referred_by,
                referral_count = :referral_count,
                updated_at = :updated_at,
                deleted_at = :deleted_at
            WHERE id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([
                ':id' => $this->attributes['id'],
                ':telegram_id' => $this->attributes['telegram_id'],
                ':whatsapp_phone' => $this->attributes['whatsapp_phone'],
                ':email' => $this->attributes['email'],
                ':username' => $this->attributes['username'],
                ':first_name' => $this->attributes['first_name'],
                ':last_name' => $this->attributes['last_name'],
                ':phone' => $this->attributes['phone'],
                ':password_hash' => $this->attributes['password_hash'],
                ':role' => $this->attributes['role'],
                ':status' => $this->attributes['status'],
                ':language' => $this->attributes['language'],
                ':timezone' => $this->attributes['timezone'],
                ':preferences' => $this->attributes['preferences'] ? json_encode($this->attributes['preferences']) : null,
                ':last_login_at' => $this->attributes['last_login_at'],
                ':last_activity_at' => $this->attributes['last_activity_at'],
                ':email_verified_at' => $this->attributes['email_verified_at'],
                ':phone_verified_at' => $this->attributes['phone_verified_at'],
                ':premium_expires_at' => $this->attributes['premium_expires_at'],
                ':api_token' => $this->attributes['api_token'],
                ':api_token_expires_at' => $this->attributes['api_token_expires_at'],
                ':rate_limit_tier' => $this->attributes['rate_limit_tier'],
                ':daily_imei_limit' => $this->attributes['daily_imei_limit'],
                ':daily_download_limit' => $this->attributes['daily_download_limit'],
                ':total_imei_generated' => $this->attributes['total_imei_generated'],
                ':total_files_downloaded' => $this->attributes['total_files_downloaded'],
                ':storage_quota_mb' => $this->attributes['storage_quota_mb'],
                ':storage_used_mb' => $this->attributes['storage_used_mb'],
                ':referral_code' => $this->attributes['referral_code'],
                ':referred_by' => $this->attributes['referred_by'],
                ':referral_count' => $this->attributes['referral_count'],
                ':updated_at' => $this->attributes['updated_at'],
                ':deleted_at' => $this->attributes['deleted_at'],
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to update user: " . $e->getMessage());
            return false;
        }
    }
    
    public static function findById(int $id): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
    public static function findByIdAsArray(int $id): ?array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return $data;
    }
    
    public static function create(array $userData): ?array
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $userData['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        $sql = "
            INSERT INTO users (
                uuid, telegram_id, whatsapp_phone, email, username, first_name, last_name,
                phone, password_hash, role, status, language, timezone, preferences,
                last_login_at, last_activity_at, email_verified_at, phone_verified_at,
                premium_expires_at, api_token, api_token_expires_at, rate_limit_tier,
                daily_imei_limit, daily_download_limit, total_imei_generated, total_files_downloaded,
                storage_quota_mb, storage_used_mb, referral_code, referred_by, referral_count,
                created_at, updated_at, deleted_at
            ) VALUES (
                :uuid, :telegram_id, :whatsapp_phone, :email, :username, :first_name, :last_name,
                :phone, :password_hash, :role, :status, :language, :timezone, :preferences,
                :last_login_at, :last_activity_at, :email_verified_at, :phone_verified_at,
                :premium_expires_at, :api_token, :api_token_expires_at, :rate_limit_tier,
                :daily_imei_limit, :daily_download_limit, :total_imei_generated, :total_files_downloaded,
                :storage_quota_mb, :storage_used_mb, :referral_code, :referred_by, :referral_count,
                :created_at, :updated_at, :deleted_at
            )
        ";
        
        $stmt = $db->prepare($sql);
        
        try {
            $stmt->execute([
                ':uuid' => $userData['uuid'],
                ':telegram_id' => $userData['telegram_id'] ?? null,
                ':whatsapp_phone' => $userData['whatsapp_phone'] ?? null,
                ':email' => $userData['email'] ?? null,
                ':username' => $userData['username'] ?? null,
                ':first_name' => $userData['first_name'] ?? null,
                ':last_name' => $userData['last_name'] ?? null,
                ':phone' => $userData['phone'] ?? null,
                ':password_hash' => $userData['password_hash'] ?? null,
                ':role' => $userData['role'] ?? 'user',
                ':status' => $userData['status'] ?? 'active',
                ':language' => $userData['language'] ?? 'en',
                ':timezone' => $userData['timezone'] ?? 'UTC',
                ':preferences' => isset($userData['preferences']) ? json_encode($userData['preferences']) : null,
                ':last_login_at' => $userData['last_login_at'] ?? null,
                ':last_activity_at' => $userData['last_activity_at'] ?? null,
                ':email_verified_at' => $userData['email_verified_at'] ?? null,
                ':phone_verified_at' => $userData['phone_verified_at'] ?? null,
                ':premium_expires_at' => $userData['premium_expires_at'] ?? null,
                ':api_token' => $userData['api_token'] ?? null,
                ':api_token_expires_at' => $userData['api_token_expires_at'] ?? null,
                ':rate_limit_tier' => $userData['rate_limit_tier'] ?? 'free',
                ':daily_imei_limit' => $userData['daily_imei_limit'] ?? 10,
                ':daily_download_limit' => $userData['daily_download_limit'] ?? 5,
                ':total_imei_generated' => $userData['total_imei_generated'] ?? 0,
                ':total_files_downloaded' => $userData['total_files_downloaded'] ?? 0,
                ':storage_quota_mb' => $userData['storage_quota_mb'] ?? 100,
                ':storage_used_mb' => $userData['storage_used_mb'] ?? 0,
                ':referral_code' => $userData['referral_code'] ?? null,
                ':referred_by' => $userData['referred_by'] ?? null,
                ':referral_count' => $userData['referral_count'] ?? 0,
                ':created_at' => $userData['created_at'],
                ':updated_at' => $userData['updated_at'],
                ':deleted_at' => $userData['deleted_at'] ?? null,
            ]);
            
            $userData['id'] = $db->lastInsertId();
            return $userData;
            
        } catch (\PDOException $e) {
            error_log("Failed to create user: " . $e->getMessage());
            return null;
        }
    }
    
    public static function updateApiToken(int $userId, ?string $token): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "UPDATE users SET api_token = :api_token, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute([
                ':id' => $userId,
                ':api_token' => $token,
            ]);
        } catch (\PDOException $e) {
            error_log("Failed to update API token: " . $e->getMessage());
            return false;
        }
    }
    
    public static function updateLastLogin(int $userId): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "UPDATE users SET last_login_at = NOW(), last_activity_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Failed to update last login: " . $e->getMessage());
            return false;
        }
    }
    
    public static function updateLastActivity(int $userId): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "UPDATE users SET last_activity_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Failed to update last activity: " . $e->getMessage());
            return false;
        }
    }
    
    public static function incrementReferralCount(int $referrerId): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "UPDATE users SET referral_count = referral_count + 1 WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $referrerId]);
        } catch (\PDOException $e) {
            error_log("Failed to increment referral count: " . $e->getMessage());
            return false;
        }
    }
    
    public static function incrementDailyLimits(int $userId, array $increments): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $updates = [];
        $params = [':id' => $userId];
        
        if (isset($increments['imei_limit'])) {
            $updates[] = "daily_imei_limit = daily_imei_limit + :imei_limit";
            $params[':imei_limit'] = $increments['imei_limit'];
        }
        
        if (isset($increments['download_limit'])) {
            $updates[] = "daily_download_limit = daily_download_limit + :download_limit";
            $params[':download_limit'] = $increments['download_limit'];
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Failed to increment daily limits: " . $e->getMessage());
            return false;
        }
    }
    
    public static function updateTelegramId(int $userId, ?int $telegramId): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "UPDATE users SET telegram_id = :telegram_id, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute([
                ':id' => $userId,
                ':telegram_id' => $telegramId,
            ]);
        } catch (\PDOException $e) {
            error_log("Failed to update Telegram ID: " . $e->getMessage());
            return false;
        }
    }
    
    public static function updateWhatsAppPhone(int $userId, ?string $whatsappPhone): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "UPDATE users SET whatsapp_phone = :whatsapp_phone, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute([
                ':id' => $userId,
                ':whatsapp_phone' => $whatsappPhone,
            ]);
        } catch (\PDOException $e) {
            error_log("Failed to update WhatsApp phone: " . $e->getMessage());
            return false;
        }
    }
    
    public static function updateTelegramUserInfo(int $userId, array $userInfo): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $updates = [];
        $params = [':id' => $userId];
        
        if (isset($userInfo['username'])) {
            $updates[] = "username = :username";
            $params[':username'] = $userInfo['username'];
        }
        
        if (isset($userInfo['first_name'])) {
            $updates[] = "first_name = :first_name";
            $params[':first_name'] = $userInfo['first_name'];
        }
        
        if (isset($userInfo['last_name'])) {
            $updates[] = "last_name = :last_name";
            $params[':last_name'] = $userInfo['last_name'];
        }
        
        if (isset($userInfo['language'])) {
            $updates[] = "language = :language";
            $params[':language'] = $userInfo['language'];
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Failed to update Telegram user info: " . $e->getMessage());
            return false;
        }
    }
    
    public static function updateWhatsAppUserInfo(int $userId, array $userInfo): bool
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $updates = [];
        $params = [':id' => $userId];
        
        if (isset($userInfo['first_name'])) {
            $updates[] = "first_name = :first_name";
            $params[':first_name'] = $userInfo['first_name'];
        }
        
        if (isset($userInfo['last_name'])) {
            $updates[] = "last_name = :last_name";
            $params[':last_name'] = $userInfo['last_name'];
        }
        
        if (isset($userInfo['language'])) {
            $updates[] = "language = :language";
            $params[':language'] = $userInfo['language'];
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        try {
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Failed to update WhatsApp user info: " . $e->getMessage());
            return false;
        }
    }
    
    public static function findByPhone(string $phone): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE phone = :phone";
        $stmt = $db->prepare($sql);
        $stmt->execute([':phone' => $phone]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
    public static function findByUuid(string $uuid): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE uuid = :uuid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
    public static function findByTelegramId(int $telegramId): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE telegram_id = :telegram_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':telegram_id' => $telegramId]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
    public static function findByWhatsAppPhone(string $phone): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE whatsapp_phone = :phone";
        $stmt = $db->prepare($sql);
        $stmt->execute([':phone' => $phone]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
    public static function findByEmail(string $email): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
    public static function findByUsername(string $username): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
    public static function findByReferralCode(string $referralCode): ?self
    {
        $db = Database::getInstance(
            self::getConfig('database'),
            self::getLogger()
        );
        
        $sql = "SELECT * FROM users WHERE referral_code = :referral_code";
        $stmt = $db->prepare($sql);
        $stmt->execute([':referral_code' => $referralCode]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        // Decode JSON fields
        if ($data['preferences']) {
            $data['preferences'] = json_decode($data['preferences'], true);
        }
        
        return new self($data);
    }
    
        
    public function upgradeToPremium(\DateTime $expiresAt): bool
    {
        $this->attributes['role'] = 'premium';
        $this->attributes['premium_expires_at'] = $expiresAt->format('Y-m-d H:i:s');
        $this->attributes['daily_imei_limit'] = 999999; // Unlimited
        $this->attributes['daily_download_limit'] = 999999; // Unlimited
        $this->attributes['storage_quota_mb'] = 10000; // 10GB
        
        return $this->save();
    }
    
    public function downgradeToFree(): bool
    {
        $this->attributes['role'] = 'user';
        $this->attributes['premium_expires_at'] = null;
        $this->attributes['daily_imei_limit'] = 10;
        $this->attributes['daily_download_limit'] = 5;
        $this->attributes['storage_quota_mb'] = 100;
        
        return $this->save();
    }
    
    public function isPremium(): bool
    {
        return $this->attributes['role'] === 'premium' && 
               ($this->attributes['premium_expires_at'] === null || 
                new \DateTime($this->attributes['premium_expires_at']) > new \DateTime());
    }
    
    public function isAdmin(): bool
    {
        return in_array($this->attributes['role'], ['admin', 'super_admin']);
    }
    
    public function generateApiToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->attributes['api_token'] = hash('sha256', $token);
        $this->attributes['api_token_expires_at'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        if ($this->save()) {
            return $token;
        }
        
        return '';
    }
    
    public function revokeApiToken(): bool
    {
        $this->attributes['api_token'] = null;
        $this->attributes['api_token_expires_at'] = null;
        
        return $this->save();
    }
    
    public function isApiTokenValid(): bool
    {
        if (!$this->attributes['api_token'] || !$this->attributes['api_token_expires_at']) {
            return false;
        }
        
        return new \DateTime($this->attributes['api_token_expires_at']) > new \DateTime();
    }
    
    public function incrementImeiCount(): bool
    {
        $sql = "UPDATE users SET total_imei_generated = total_imei_generated + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute([':id' => $this->attributes['id']]);
            
            if ($result) {
                $this->attributes['total_imei_generated']++;
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Failed to increment IMEI count: " . $e->getMessage());
            return false;
        }
    }
    
    public function incrementDownloadCount(): bool
    {
        $sql = "UPDATE users SET total_files_downloaded = total_files_downloaded + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute([':id' => $this->attributes['id']]);
            
            if ($result) {
                $this->attributes['total_files_downloaded']++;
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Failed to increment download count: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete(): bool
    {
        if (!isset($this->attributes['id'])) {
            return false;
        }
        
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        try {
            return $stmt->execute([':id' => $this->attributes['id']]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete user: " . $e->getMessage());
            return false;
        }
    }
    
    // Magic methods for attribute access
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
    
    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }
    
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
    
    public function toArray(): array
    {
        return $this->attributes;
    }
    
    private static function getConfig(string $key): array
    {
        // This would normally load from config files
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'imei_bot',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ];
    }
    
    private static function getLogger(): \IMEIBot\Core\Logger
    {
        // This would normally create a logger instance
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
