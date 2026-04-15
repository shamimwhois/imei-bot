<?php

declare(strict_types=1);

/**
 * Helper functions for the IMEI Bot Platform
 */

if (!function_exists('config')) {
    /**
     * Get configuration value
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;
        
        if ($config === null) {
            $config = require __DIR__ . '/../config/app.php';
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            'empty' => '',
            default => $value,
        };
    }
}

if (!function_exists('base_path')) {
    /**
     * Get base path
     */
    function base_path(string $path = ''): string
    {
        return __DIR__ . '/..' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get storage path
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get public path
     */
    function public_path(string $path = ''): string
    {
        return base_path('web' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('config_path')) {
    /**
     * Get config path
     */
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('logs_path')) {
    /**
     * Get logs path
     */
    function logs_path(string $path = ''): string
    {
        return base_path('logs' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('str_slug')) {
    /**
     * Convert string to slug
     */
    function str_slug(string $string): string
    {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9]+/', '-', $string);
        return trim($string, '-');
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limit string length
     */
    function str_limit(string $string, int $limit = 100, string $suffix = '...'): string
    {
        if (strlen($string) <= $limit) {
            return $string;
        }
        
        return substr($string, 0, $limit - strlen($suffix)) . $suffix;
    }
}

if (!function_exists('bytes_to_human')) {
    /**
     * Convert bytes to human readable format
     */
    function bytes_to_human(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

if (!function_exists('generate_uuid')) {
    /**
     * Generate UUID
     */
    function generate_uuid(): string
    {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
}

if (!function_exists('validate_imei')) {
    /**
     * Validate IMEI using Luhn algorithm
     */
    function validate_imei(string $imei): bool
    {
        // Remove any non-digit characters
        $imei = preg_replace('/\D/', '', $imei);
        
        // IMEI must be 15 digits
        if (strlen($imei) !== 15) {
            return false;
        }
        
        // Luhn algorithm
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $digit = (int) $imei[$i];
            
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = 1 + ($digit % 10);
                }
            }
            
            $sum += $digit;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $checkDigit === (int) $imei[14];
    }
}

if (!function_exists('is_valid_prefix')) {
    /**
     * Check if IMEI prefix is valid
     */
    function is_valid_prefix(string $prefix): bool
    {
        $validPrefixes = ['35', '86', '89', '45'];
        return in_array($prefix, $validPrefixes);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date
     */
    function format_date(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        $dateTime = new \DateTime($date);
        return $dateTime->format($format);
    }
}

if (!function_exists('time_ago')) {
    /**
     * Get time ago string
     */
    function time_ago(string $date): string
    {
        $time = strtotime($date);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' days ago';
        } else {
            return format_date($date, 'M j, Y');
        }
    }
}

if (!function_exists('sanitize_input')) {
    /**
     * Sanitize input
     */
    function sanitize_input(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_json')) {
    /**
     * Check if string is valid JSON
     */
    function is_json(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('array_get')) {
    /**
     * Get value from array using dot notation
     */
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('array_set')) {
    /**
     * Set value in array using dot notation
     */
    function array_set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $k) {
            if (!is_array($current)) {
                $current = [];
            }
            
            if (!array_key_exists($k, $current)) {
                $current[$k] = [];
            }
            
            $current = &$current[$k];
        }
        
        $current = $value;
    }
}
