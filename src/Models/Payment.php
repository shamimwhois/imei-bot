<?php

declare(strict_types=1);

namespace IMEIBot\Models;

use IMEIBot\Core\Database;

final class Payment
{
    public ?int $id = null;
    public ?string $uuid = null;
    public ?int $user_id = null;
    public ?string $transaction_id = null;
    public ?string $gateway = null;
    public ?string $gateway_transaction_id = null;
    public ?float $amount = null;
    public ?string $currency = null;
    public ?string $status = null;
    public ?string $payment_method = null;
    public ?array $payment_details = null;
    public ?array $webhook_data = null;
    public ?string $failure_reason = null;
    public ?string $refund_id = null;
    public ?float $refund_amount = null;
    public ?string $refund_reason = null;
    public ?string $ip_address = null;
    public ?string $user_agent = null;
    public ?string $device_fingerprint = null;
    public ?string $processed_at = null;
    public ?string $completed_at = null;
    public ?string $failed_at = null;
    public ?string $refunded_at = null;
    public ?string $expires_at = null;
    public ?array $metadata = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    
    private Database $db;
    
    public function __construct(Database $db = null)
    {
        $this->db = $db ?? Database::getInstance(
            require __DIR__ . '/../../config/database.php',
            new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
        );
    }
    
    public function save(): bool
    {
        try {
            $data = [
                'uuid' => $this->uuid ?? $this->generateUuid(),
                'user_id' => $this->user_id,
                'transaction_id' => $this->transaction_id,
                'gateway' => $this->gateway,
                'gateway_transaction_id' => $this->gateway_transaction_id,
                'amount' => $this->amount,
                'currency' => $this->currency ?? 'USD',
                'status' => $this->status ?? 'pending',
                'payment_method' => $this->payment_method,
                'payment_details' => $this->payment_details ? json_encode($this->payment_details) : null,
                'webhook_data' => $this->webhook_data ? json_encode($this->webhook_data) : null,
                'failure_reason' => $this->failure_reason,
                'refund_id' => $this->refund_id,
                'refund_amount' => $this->refund_amount,
                'refund_reason' => $this->refund_reason,
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
                'device_fingerprint' => $this->device_fingerprint,
                'processed_at' => $this->processed_at,
                'completed_at' => $this->completed_at,
                'failed_at' => $this->failed_at,
                'refunded_at' => $this->refunded_at,
                'expires_at' => $this->expires_at,
                'metadata' => $this->metadata ? json_encode($this->metadata) : null
            ];
            
            if ($this->id) {
                // Update existing payment
                $this->db->update('payments', $data, ['id' => $this->id]);
            } else {
                // Insert new payment
                $this->id = (int) $this->db->insert('payments', $data);
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function delete(): bool
    {
        try {
            if ($this->id) {
                $this->db->delete('payments', 'id = ?', [$this->id]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function find(int $id): ?self
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchOne("SELECT * FROM payments WHERE id = ?", [$id]);
            
            if ($data) {
                return self::hydrate($data, $db);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function findByUuid(string $uuid): ?self
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchOne("SELECT * FROM payments WHERE uuid = ?", [$uuid]);
            
            if ($data) {
                return self::hydrate($data, $db);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function findByTransactionId(string $transactionId): ?self
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchOne("SELECT * FROM payments WHERE transaction_id = ?", [$transactionId]);
            
            if ($data) {
                return self::hydrate($data, $db);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function findByGatewayTransactionId(string $gatewayTransactionId, string $gateway): ?self
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchOne(
                "SELECT * FROM payments WHERE gateway_transaction_id = ? AND gateway = ?", 
                [$gatewayTransactionId, $gateway]
            );
            
            if ($data) {
                return self::hydrate($data, $db);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function findByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchAll(
                "SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?", 
                [$userId, $limit, $offset]
            );
            
            $payments = [];
            foreach ($data as $row) {
                $payments[] = self::hydrate($row, $db);
            }
            
            return $payments;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public static function findByStatus(string $status, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchAll(
                "SELECT * FROM payments WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?", 
                [$status, $limit, $offset]
            );
            
            $payments = [];
            foreach ($data as $row) {
                $payments[] = self::hydrate($row, $db);
            }
            
            return $payments;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public static function findByGateway(string $gateway, int $limit = 50, int $offset = 0): array
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchAll(
                "SELECT * FROM payments WHERE gateway = ? ORDER BY created_at DESC LIMIT ? OFFSET ?", 
                [$gateway, $limit, $offset]
            );
            
            $payments = [];
            foreach ($data as $row) {
                $payments[] = self::hydrate($row, $db);
            }
            
            return $payments;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public static function getExpiredPayments(): array
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $data = $db->fetchAll(
                "SELECT * FROM payments WHERE status = 'pending' AND expires_at < NOW() ORDER BY expires_at ASC"
            );
            
            $payments = [];
            foreach ($data as $row) {
                $payments[] = self::hydrate($row, $db);
            }
            
            return $payments;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public static function getTotalAmountByStatus(string $status, string $dateRange = '30 days'): float
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = ?";
            $params = [$status];
            
            if ($dateRange !== 'all') {
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL ?)";
                $params[] = $dateRange;
            }
            
            $result = $db->fetchOne($sql, $params);
            
            return (float) ($result['total'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    public static function getCountByStatus(string $status, string $dateRange = '30 days'): int
    {
        try {
            $db = Database::getInstance(
                require __DIR__ . '/../../config/database.php',
                new \IMEIBot\Core\Logger(require __DIR__ . '/../../config/monitoring.php')
            );
            
            $sql = "SELECT COUNT(*) as count FROM payments WHERE status = ?";
            $params = [$status];
            
            if ($dateRange !== 'all') {
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL ?)";
                $params[] = $dateRange;
            }
            
            $result = $db->fetchOne($sql, $params);
            
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private static function hydrate(array $data, Database $db): self
    {
        $payment = new self($db);
        
        foreach ($data as $key => $value) {
            if (property_exists($payment, $key)) {
                if (in_array($key, ['payment_details', 'webhook_data', 'metadata'])) {
                    $payment->$key = $value ? json_decode($value, true) : null;
                } elseif (in_array($key, ['amount', 'refund_amount'])) {
                    $payment->$key = $value ? (float) $value : null;
                } elseif (in_array($key, ['id', 'user_id'])) {
                    $payment->$key = $value ? (int) $value : null;
                } else {
                    $payment->$key = $value;
                }
            }
        }
        
        return $payment;
    }
    
    private function generateUuid(): string
    {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
    
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
    
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
    
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
    
    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at && strtotime($this->expires_at) < time();
    }
    
    public function canBeRefunded(): bool
    {
        return $this->status === 'completed' && !$this->refund_id;
    }
    
    public function getFormattedAmount(): string
    {
        return number_format($this->amount ?? 0, 2) . ' ' . ($this->currency ?? 'USD');
    }
    
    public function getStatusBadge(): string
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'processing' => '<span class="badge badge-info">Processing</span>',
            'completed' => '<span class="badge badge-success">Completed</span>',
            'failed' => '<span class="badge badge-danger">Failed</span>',
            'cancelled' => '<span class="badge badge-secondary">Cancelled</span>',
            'refunded' => '<span class="badge badge-warning">Refunded</span>',
            'disputed' => '<span class="badge badge-danger">Disputed</span>'
        ];
        
        return $badges[$this->status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
            'gateway' => $this->gateway,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_details' => $this->payment_details,
            'failure_reason' => $this->failure_reason,
            'refund_id' => $this->refund_id,
            'refund_amount' => $this->refund_amount,
            'refund_reason' => $this->refund_reason,
            'ip_address' => $this->ip_address,
            'processed_at' => $this->processed_at,
            'completed_at' => $this->completed_at,
            'failed_at' => $this->failed_at,
            'refunded_at' => $this->refunded_at,
            'expires_at' => $this->expires_at,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_amount' => $this->getFormattedAmount(),
            'status_badge' => $this->getStatusBadge()
        ];
    }
}
