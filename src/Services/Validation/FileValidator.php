<?php

declare(strict_types=1);

namespace IMEIBot\Services\Validation;

use IMEIBot\Core\Logger;
use IMEIBot\Core\Database;

class FileValidator extends BaseValidator
{
    private Database $db;

    public function __construct(Logger $logger, Database $db)
    {
        parent::__construct($logger);
        $this->db = $db;
        $this->rules = $this->getRules();
    }

    /**
     * Get validation rules
     */
    protected function getRules(): array
    {
        return [
            'name' => [
                'required' => true,
                'min' => 1,
                'max' => 255
            ],
            'display_name' => [
                'required' => true,
                'min' => 1,
                'max' => 255
            ],
            'description' => [
                'max' => 1000
            ],
            'category' => [
                'required' => true,
                'in' => 'firmware,qcn,frp,modem,unlock,tools,other'
            ],
            'subcategory' => [
                'max' => 100
            ],
            'version' => [
                'max' => 50
            ],
            'build_number' => [
                'max' => 50
            ],
            'android_version' => [
                'max' => 20
            ],
            'security_patch' => [
                'max' => 20
            ],
            'region' => [
                'max' => 50
            ],
            'carrier' => [
                'max' => 100
            ],
            'network_lock' => [
                'in' => 'unlocked,carrier,region'
            ],
            'file_type' => [
                'required' => true,
                'in' => 'zip,rar,7z,tar,gz,img,bin,cert,exe,apk,ipsw,other'
            ],
            'file_size_mb' => [
                'required' => true,
                'numeric' => true,
                'min_value' => 0,
                'max_value' => 10240 // 10GB max
            ],
            'file_hash_md5' => [
                'max' => 32
            ],
            'file_hash_sha256' => [
                'max' => 64
            ],
            'file_path' => [
                'required' => true,
                'max' => 500
            ],
            'file_url' => [
                'url' => true,
                'max' => 500
            ],
            'cdn_url' => [
                'url' => true,
                'max' => 500
            ],
            'rating' => [
                'numeric' => true,
                'min_value' => 0,
                'max_value' => 5
            ],
            'is_featured' => [
                'in' => '0,1'
            ],
            'is_premium' => [
                'in' => '0,1'
            ],
            'is_verified' => [
                'in' => '0,1'
            ],
            'is_active' => [
                'in' => '0,1'
            ],
            'brand_id' => [
                'integer' => true,
                'min_value' => 1
            ],
            'model_id' => [
                'integer' => true,
                'min_value' => 1
            ]
        ];
    }

    /**
     * Validate brand exists
     */
    protected function validateBrand_id(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!is_numeric($value) || $value < 1) {
                $this->addError($field, "The $field must be a positive integer");
                return false;
            }

            // Check if brand exists
            try {
                $exists = $this->db->fetchColumn("SELECT COUNT(*) FROM brands WHERE id = :id AND deleted_at IS NULL", [':id' => (int) $value]);
                if (!$exists) {
                    $this->addError($field, "The selected brand does not exist");
                    return false;
                }
            } catch (\Exception $e) {
                $this->logger->error("Failed to validate brand existence", [
                    'brand_id' => $value,
                    'error' => $e->getMessage()
                ]);
                $this->addError($field, "Unable to validate brand selection");
                return false;
            }
        }
        return true;
    }

    /**
     * Validate model exists
     */
    protected function validateModel_id(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!is_numeric($value) || $value < 1) {
                $this->addError($field, "The $field must be a positive integer");
                return false;
            }

            // Check if model exists
            try {
                $exists = $this->db->fetchColumn("SELECT COUNT(*) FROM models WHERE id = :id AND deleted_at IS NULL", [':id' => (int) $value]);
                if (!$exists) {
                    $this->addError($field, "The selected model does not exist");
                    return false;
                }
            } catch (\Exception $e) {
                $this->logger->error("Failed to validate model existence", [
                    'model_id' => $value,
                    'error' => $e->getMessage()
                ]);
                $this->addError($field, "Unable to validate model selection");
                return false;
            }
        }
        return true;
    }

    /**
     * Validate file hash format
     */
    protected function validateFile_hash_md5(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!preg_match('/^[a-f0-9]{32}$/i', $value)) {
                $this->addError($field, "The MD5 hash must be a valid 32-character hexadecimal string");
                return false;
            }
        }
        return true;
    }

    /**
     * Validate SHA256 hash format
     */
    protected function validateFile_hash_sha256(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!preg_match('/^[a-f0-9]{64}$/i', $value)) {
                $this->addError($field, "The SHA256 hash must be a valid 64-character hexadecimal string");
                return false;
            }
        }
        return true;
    }

    /**
     * Validate file path exists
     */
    protected function validateFile_path(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            // Check if file exists (for local files)
            if (strpos($value, 'http') !== 0 && !file_exists($value)) {
                $this->addError($field, "The file path does not exist");
                return false;
            }
        }
        return true;
    }

    /**
     * Validate unique name within brand/model
     */
    protected function validateUnique(string $field, $value, $params): bool
    {
        if ($field === 'name' || $field === 'display_name') {
            $brandId = $this->getCurrentValue('brand_id');
            $modelId = $this->getCurrentValue('model_id');
            $currentId = $this->getCurrentValue('id');

            try {
                $sql = "SELECT COUNT(*) FROM files WHERE $field = :value AND deleted_at IS NULL";
                $params = [':value' => $value];

                if ($brandId) {
                    $sql .= " AND brand_id = :brand_id";
                    $params[':brand_id'] = $brandId;
                }

                if ($modelId) {
                    $sql .= " AND model_id = :model_id";
                    $params[':model_id'] = $modelId;
                }

                if ($currentId) {
                    $sql .= " AND id != :current_id";
                    $params[':current_id'] = $currentId;
                }

                $count = (int) $this->db->fetchColumn($sql, $params);
                
                if ($count > 0) {
                    $this->addError($field, "The $field must be unique within the selected brand/model");
                    return false;
                }
            } catch (\Exception $e) {
                $this->logger->error("Failed to validate uniqueness", [
                    'field' => $field,
                    'value' => $value,
                    'error' => $e->getMessage()
                ]);
                return false; // Don't block validation on database errors
            }
        }
        return true;
    }

    /**
     * Get current value being validated (for uniqueness checks)
     */
    private function getCurrentValue(string $field): mixed
    {
        // This would need to be set during validation
        // For now, return null to skip uniqueness checks
        return null;
    }

    /**
     * Validate file upload
     */
    protected function validateFile(string $field, $value, $params): bool
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            if (in_array('required', $params)) {
                $this->addError($field, "The $field file is required");
                return false;
            }
            return true; // Not required and not uploaded
        }

        $file = $_FILES[$field];
        $allowedTypes = $params['allowed_types'] ?? [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
            'application/octet-stream',
            'application/img',
            'application/x-img',
            'application/bin',
            'application/x-bin',
            'application/x-certificate',
            'application/x-msdownload',
            'application/vnd.android.package-archive',
            'application/x-apple-diskimage'
        ];
        $maxSize = $params['max_size'] ?? 104857600; // 100MB default

        // Check file size
        if ($file['size'] > $maxSize) {
            $this->addError($field, "The $field file size must not exceed " . $this->formatBytes($maxSize));
            return false;
        }

        // Check file extension
        $allowedExtensions = $params['allowed_extensions'] ?? ['zip', 'rar', '7z', 'tar', 'gz', 'img', 'bin', 'cert', 'exe', 'apk', 'ipsw'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $this->addError($field, "The $field file type is not allowed");
            return false;
        }

        // Check file content type
        if (!in_array($file['type'], $allowedTypes)) {
            $this->addError($field, "The $field file type is not allowed");
            return false;
        }

        return true;
    }

    /**
     * Validate download count
     */
    protected function validateDownload_count(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!is_numeric($value) || $value < 0) {
                $this->addError($field, "The $field must be a non-negative number");
                return false;
            }
        }
        return true;
    }

    /**
     * Validate rating count
     */
    protected function validateRating_count(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!is_numeric($value) || $value < 0) {
                $this->addError($field, "The $field must be a non-negative number");
                return false;
            }
        }
        return true;
    }

    /**
     * Set current values for validation (for uniqueness checks)
     */
    public function setCurrentValues(array $values): void
    {
        $this->currentValues = $values;
    }

    private array $currentValues = [];
}
