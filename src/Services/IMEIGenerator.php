<?php

declare(strict_types=1);

namespace IMEIBot\Services;

use IMEIBot\Core\Cache;
use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\IMEILog;
use Ramsey\Uuid\Uuid;

final class IMEIGenerator
{
    private const IMEI_LENGTH = 15;
    private const DEFAULT_PREFIXES = ['35', '86', '89', '45'];
    private const CACHE_TTL = 86400; // 24 hours
    
    private array $prefixes;
    private Database $db;
    private Cache $cache;
    private Logger $logger;
    
    public function __construct(
        Database $db,
        Cache $cache,
        Logger $logger,
        ?array $prefixes = null
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->prefixes = $prefixes ?? self::DEFAULT_PREFIXES;
    }
    
    /**
     * Generate a single random IMEI with Luhn validation
     */
    public function generateRandom(?string $prefix = null): string
    {
        $prefix = $prefix ?? $this->getRandomPrefix();
        $this->validatePrefix($prefix);
        
        $imei = $this->generateIMEIWithPrefix($prefix);
        
        $this->logger->info('IMEI generated', [
            'type' => 'random',
            'imei' => $imei,
            'prefix' => $prefix,
        ]);
        
        return $imei;
    }
    
    /**
     * Generate a single IMEI with specified prefix
     */
    public function generateSingle(string $prefix): string
    {
        $this->validatePrefix($prefix);
        $imei = $this->generateIMEIWithPrefix($prefix);
        
        $this->logger->info('IMEI generated', [
            'type' => 'single',
            'imei' => $imei,
            'prefix' => $prefix,
        ]);
        
        return $imei;
    }
    
    /**
     * Generate a dual IMEI pair with matching patterns
     */
    public function generateDual(?string $prefix = null): array
    {
        $prefix = $prefix ?? $this->getRandomPrefix();
        $this->validatePrefix($prefix);
        
        $imei1 = $this->generateIMEIWithPrefix($prefix);
        $imei2 = $this->generateIMEIWithPrefix($prefix);
        
        $this->logger->info('Dual IMEI generated', [
            'type' => 'dual',
            'imei1' => $imei1,
            'imei2' => $imei2,
            'prefix' => $prefix,
        ]);
        
        return [$imei1, $imei2];
    }
    
    /**
     * Generate multiple IMEIs
     */
    public function generateMultiple(int $quantity, ?string $prefix = null): array
    {
        if ($quantity <= 0 || $quantity > 50) {
            throw new \InvalidArgumentException('Quantity must be between 1 and 50');
        }
        
        $prefix = $prefix ?? $this->getRandomPrefix();
        $this->validatePrefix($prefix);
        
        $imeis = [];
        for ($i = 0; $i < $quantity; $i++) {
            $imeis[] = $this->generateIMEIWithPrefix($prefix);
        }
        
        $this->logger->info('Multiple IMEIs generated', [
            'type' => 'multi',
            'quantity' => $quantity,
            'prefix' => $prefix,
            'imeis' => $imeis,
        ]);
        
        return $imeis;
    }
    
    /**
     * Validate an existing IMEI number
     */
    public function validate(string $imei): array
    {
        $this->validateIMEIFormat($imei);
        
        $isValid = $this->isValidIMEI($imei);
        $prefix = substr($imei, 0, 2);
        
        $result = [
            'imei' => $imei,
            'is_valid' => $isValid,
            'prefix' => $prefix,
            'prefix_info' => $this->getPrefixInfo($prefix),
            'validation_method' => 'luhn',
        ];
        
        $this->logger->info('IMEI validated', $result);
        
        return $result;
    }
    
    /**
     * Get IMEI generation history for a user
     */
    public function getHistory(int $userId, int $limit = 50): array
    {
        $cacheKey = "imei_history_{$userId}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($userId, $limit) {
            $sql = "
                SELECT imei, prefix, type, quantity, is_valid, validation_method, created_at
                FROM imei_logs
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':limit' => $limit,
            ]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Log IMEI generation to database
     */
    public function logGeneration(
        int $userId,
        string $platform,
        array $imeis,
        string $type,
        ?string $batchUuid = null
    ): void {
        foreach ($imeis as $imei) {
            $log = new IMEILog([
                'uuid' => Uuid::uuid4()->toString(),
                'user_id' => $userId,
                'platform' => $platform,
                'imei' => $imei,
                'prefix' => substr($imei, 0, 2),
                'type' => $type,
                'quantity' => count($imeis),
                'batch_uuid' => $batchUuid,
                'is_valid' => $this->isValidIMEI($imei),
                'validation_method' => 'luhn',
                'generation_time_ms' => null, // Could be measured
            ]);
            
            $log->save();
        }
        
        // Clear user history cache
        $this->cache->delete("imei_history_{$userId}");
    }
    
    /**
     * Generate IMEI with specific prefix using Luhn algorithm
     */
    private function generateIMEIWithPrefix(string $prefix): string
    {
        $prefixLength = strlen($prefix);
        
        if ($prefixLength >= self::IMEI_LENGTH) {
            throw new \InvalidArgumentException('Prefix too long for IMEI');
        }
        
        // Generate random digits for the middle part
        $middleLength = self::IMEI_LENGTH - $prefixLength - 1; // -1 for check digit
        $middle = '';
        for ($i = 0; $i < $middleLength; $i++) {
            $middle .= random_int(0, 9);
        }
        
        $baseIMEI = $prefix . $middle;
        $checkDigit = $this->calculateLuhnCheckDigit($baseIMEI);
        
        return $baseIMEI . $checkDigit;
    }
    
    /**
     * Calculate Luhn check digit
     */
    private function calculateLuhnCheckDigit(string $number): int
    {
        $sum = 0;
        $length = strlen($number);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];
            
            // Double every second digit from right
            if ($i % 2 === ($length % 2)) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return (10 - ($sum % 10)) % 10;
    }
    
    /**
     * Validate IMEI using Luhn algorithm
     */
    private function isValidIMEI(string $imei): bool
    {
        if (strlen($imei) !== self::IMEI_LENGTH) {
            return false;
        }
        
        if (!ctype_digit($imei)) {
            return false;
        }
        
        $baseIMEI = substr($imei, 0, -1);
        $checkDigit = (int) substr($imei, -1);
        
        return $this->calculateLuhnCheckDigit($baseIMEI) === $checkDigit;
    }
    
    /**
     * Validate IMEI format
     */
    private function validateIMEIFormat(string $imei): void
    {
        if (strlen($imei) !== self::IMEI_LENGTH) {
            throw new \InvalidArgumentException('IMEI must be exactly 15 digits');
        }
        
        if (!ctype_digit($imei)) {
            throw new \InvalidArgumentException('IMEI must contain only digits');
        }
    }
    
    /**
     * Validate prefix
     */
    private function validatePrefix(string $prefix): void
    {
        if (strlen($prefix) < 2 || strlen($prefix) > 3) {
            throw new \InvalidArgumentException('Prefix must be 2 or 3 digits');
        }
        
        if (!ctype_digit($prefix)) {
            throw new \InvalidArgumentException('Prefix must contain only digits');
        }
        
        if (!in_array($prefix, $this->prefixes)) {
            throw new \InvalidArgumentException("Prefix '{$prefix}' is not supported");
        }
    }
    
    /**
     * Get random prefix from supported prefixes
     */
    private function getRandomPrefix(): string
    {
        return $this->prefixes[array_rand($this->prefixes)];
    }
    
    /**
     * Get prefix information
     */
    private function getPrefixInfo(string $prefix): array
    {
        $prefixInfo = [
            '35' => [
                'country' => 'Reserved for future use',
                'organization' => 'Telia',
                'type' => 'Mobile equipment',
            ],
            '86' => [
                'country' => 'China',
                'organization' => 'China Mobile',
                'type' => 'Mobile equipment',
            ],
            '89' => [
                'country' => 'Reserved for future use',
                'organization' => 'Various',
                'type' => 'Mobile equipment',
            ],
            '45' => [
                'country' => 'Denmark',
                'organization' => 'TDC',
                'type' => 'Mobile equipment',
            ],
        ];
        
        return $prefixInfo[$prefix] ?? [
            'country' => 'Unknown',
            'organization' => 'Unknown',
            'type' => 'Mobile equipment',
        ];
    }
    
    /**
     * Get supported prefixes
     */
    public function getSupportedPrefixes(): array
    {
        return $this->prefixes;
    }
    
    /**
     * Check if IMEI is in cache (previously validated)
     */
    public function isCached(string $imei): bool
    {
        $cacheKey = "imei_valid_{$imei}";
        return $this->cache->has($cacheKey);
    }
    
    /**
     * Cache IMEI validation result
     */
    public function cacheValidation(string $imei, bool $isValid): void
    {
        $cacheKey = "imei_valid_{$imei}";
        $this->cache->set($cacheKey, $isValid, self::CACHE_TTL);
    }
    
    /**
     * Get IMEI statistics
     */
    public function getStatistics(): array
    {
        $cacheKey = 'imei_statistics';
        
        return $this->cache->remember($cacheKey, 300, function () {
            $sql = "
                SELECT 
                    COUNT(*) as total_generated,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT prefix) as unique_prefixes,
                    SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_count,
                    AVG(generation_time_ms) as avg_generation_time
                FROM imei_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ";
            
            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get prefix distribution
            $prefixSql = "
                SELECT prefix, COUNT(*) as count
                FROM imei_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY prefix
                ORDER BY count DESC
            ";
            
            $prefixStmt = $this->db->query($prefixSql);
            $stats['prefix_distribution'] = $prefixStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $stats;
        });
    }
}
