<?php

declare(strict_types=1);

namespace IMEIBot\Services\Validation;

use IMEIBot\Core\Logger;

abstract class BaseValidator
{
    protected Logger $logger;
    protected array $errors = [];
    protected array $rules = [];

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Validate data against rules
     */
    public function validate(array $data): bool
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule => $params) {
                if (!$this->validateRule($field, $value, $rule, $params)) {
                    break; // Stop at first error for this field
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        return empty($this->errors) ? null : reset($this->errors)[0];
    }

    /**
     * Validate a single rule
     */
    protected function validateRule(string $field, $value, string $rule, $params): bool
    {
        $methodName = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $methodName)) {
            return $this->$methodName($field, $value, $params);
        }
        
        $this->logger->warning("Unknown validation rule: $rule", ['field' => $field, 'rule' => $rule]);
        return true;
    }

    /**
     * Required validation
     */
    protected function validateRequired(string $field, $value, $params): bool
    {
        if ($value === null || $value === '') {
            $this->addError($field, "The $field field is required");
            return false;
        }
        return true;
    }

    /**
     * Email validation
     */
    protected function validateEmail(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "The $field must be a valid email address");
                return false;
            }
        }
        return true;
    }

    /**
     * Min length validation
     */
    protected function validateMin(string $field, $value, $params): bool
    {
        $minLength = (int) $params;
        if ($value !== null && strlen((string) $value) < $minLength) {
            $this->addError($field, "The $field must be at least $minLength characters");
            return false;
        }
        return true;
    }

    /**
     * Max length validation
     */
    protected function validateMax(string $field, $value, $params): bool
    {
        $maxLength = (int) $params;
        if ($value !== null && strlen((string) $value) > $maxLength) {
            $this->addError($field, "The $field must not exceed $maxLength characters");
            return false;
        }
        return true;
    }

    /**
     * Numeric validation
     */
    protected function validateNumeric(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!is_numeric($value)) {
                $this->addError($field, "The $field must be a number");
                return false;
            }
        }
        return true;
    }

    /**
     * Integer validation
     */
    protected function validateInteger(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $this->addError($field, "The $field must be an integer");
                return false;
            }
        }
        return true;
    }

    /**
     * Min value validation
     */
    protected function validateMinValue(string $field, $value, $params): bool
    {
        $minValue = (float) $params;
        if ($value !== null && $value !== '' && is_numeric($value)) {
            if ((float) $value < $minValue) {
                $this->addError($field, "The $field must be at least $minValue");
                return false;
            }
        }
        return true;
    }

    /**
     * Max value validation
     */
    protected function validateMaxValue(string $field, $value, $params): bool
    {
        $maxValue = (float) $params;
        if ($value !== null && $value !== '' && is_numeric($value)) {
            if ((float) $value > $maxValue) {
                $this->addError($field, "The $field must not exceed $maxValue");
                return false;
            }
        }
        return true;
    }

    /**
     * In validation (value must be in array)
     */
    protected function validateIn(string $field, $value, $params): bool
    {
        $allowedValues = is_array($params) ? $params : explode(',', $params);
        
        if ($value !== null && $value !== '') {
            if (!in_array($value, $allowedValues)) {
                $this->addError($field, "The $field must be one of: " . implode(', ', $allowedValues));
                return false;
            }
        }
        return true;
    }

    /**
     * URL validation
     */
    protected function validateUrl(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->addError($field, "The $field must be a valid URL");
                return false;
            }
        }
        return true;
    }

    /**
     * Phone validation
     */
    protected function validatePhone(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            // Basic phone validation - adjust regex as needed
            if (!preg_match('/^[\d\s\-\+\(\)]+$/', $value)) {
                $this->addError($field, "The $field must be a valid phone number");
                return false;
            }
        }
        return true;
    }

    /**
     * Unique validation (for database fields)
     */
    protected function validateUnique(string $field, $value, $params): bool
    {
        // This should be implemented in child classes
        // as it requires database access
        return true;
    }

    /**
     * Regex validation
     */
    protected function validateRegex(string $field, $value, $params): bool
    {
        if ($value !== null && $value !== '') {
            if (!preg_match($params, $value)) {
                $this->addError($field, "The $field format is invalid");
                return false;
            }
        }
        return true;
    }

    /**
     * File validation
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
        $allowedTypes = $params['allowed_types'] ?? [];
        $maxSize = $params['max_size'] ?? 10485760; // 10MB default

        // Check file size
        if ($file['size'] > $maxSize) {
            $this->addError($field, "The $field file size must not exceed " . $this->formatBytes($maxSize));
            return false;
        }

        // Check file type
        if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
            $this->addError($field, "The $field file type is not allowed");
            return false;
        }

        return true;
    }

    /**
     * Add validation error
     */
    protected function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        
        $this->logger->debug("Validation error added", [
            'field' => $field,
            'message' => $message
        ]);
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Sanitize input data
     */
    public function sanitize(array $data): array
    {
        return array_map(function($value) {
            if (is_string($value)) {
                return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            }
            return $value;
        }, $data);
    }

    /**
     * Get validation rules (override in child classes)
     */
    abstract protected function getRules(): array;
}
