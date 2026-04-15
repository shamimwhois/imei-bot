<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;

echo "=== Deep Scan for TypeError Issues ===\n\n";

// Function to scan a file for number_format() usage
function scanFileForNumberFormat(string $filePath): array
{
    $issues = [];
    if (!file_exists($filePath)) {
        return $issues;
    }
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'number_format(') !== false) {
            // Check if there are type casts
            $hasIntCast = strpos($line, '(int)') !== false;
            $hasFloatCast = strpos($line, '(float)') !== false;
            $hasDoubleCast = strpos($line, '(double)') !== false;
            
            // Extract the variable being passed to number_format
            if (preg_match('/number_format\(\s*\$([^,\)]+)/', $line, $matches)) {
                $variable = $matches[1];
                $issues[] = [
                    'line' => $lineNum + 1,
                    'content' => trim($line),
                    'variable' => $variable,
                    'has_cast' => $hasIntCast || $hasFloatCast || $hasDoubleCast,
                    'needs_fix' => !$hasIntCast && !$hasFloatCast && !$hasDoubleCast
                ];
            }
        }
    }
    
    return $issues;
}

// Function to scan for database fetchColumn usage
function scanFileForFetchColumn(string $filePath): array
{
    $issues = [];
    if (!file_exists($filePath)) {
        return $issues;
    }
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'fetchColumn(') !== false) {
            // Check if there are type casts
            $hasIntCast = strpos($line, '(int)') !== false;
            $hasFloatCast = strpos($line, '(float)') !== false;
            $hasDoubleCast = strpos($line, '(double)') !== false;
            
            $issues[] = [
                'line' => $lineNum + 1,
                'content' => trim($line),
                'has_cast' => $hasIntCast || $hasFloatCast || $hasDoubleCast,
                'needs_fix' => !$hasIntCast && !$hasFloatCast && !$hasDoubleCast
            ];
        }
    }
    
    return $issues;
}

// Scan specific files
$filesToScan = [
    'web/index.php',
    'web/dashboard.php',
    'web/dashboard_old.php',
    'web/payment.php',
    'web/admin/index.php',
    'web/admin/files.php',
    'web/admin/users.php',
    'web/admin/dashboard.php',
    'web/admin/dashboard_old.php',
    'web/admin/settings.php',
    'web/admin/payments.php',
    'web/admin/analytics.php'
];

$totalIssues = 0;
$filesWithIssues = [];

foreach ($filesToScan as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    
    echo "Scanning: $file\n";
    
    // Scan for number_format() issues
    $numberFormatIssues = scanFileForNumberFormat($fullPath);
    
    // Scan for fetchColumn() issues
    $fetchColumnIssues = scanFileForFetchColumn($fullPath);
    
    if (!empty($numberFormatIssues) || !empty($fetchColumnIssues)) {
        $filesWithIssues[$file] = [
            'number_format' => $numberFormatIssues,
            'fetch_column' => $fetchColumnIssues
        ];
        
        echo "  Found issues:\n";
        
        if (!empty($numberFormatIssues)) {
            echo "    number_format() issues:\n";
            foreach ($numberFormatIssues as $issue) {
                echo "      Line {$issue['line']}: {$issue['content']}\n";
                echo "        Variable: \${$issue['variable']}\n";
                echo "        Has cast: " . ($issue['has_cast'] ? 'YES' : 'NO') . "\n";
                echo "        Needs fix: " . ($issue['needs_fix'] ? 'YES' : 'NO') . "\n";
                $totalIssues++;
            }
        }
        
        if (!empty($fetchColumnIssues)) {
            echo "    fetchColumn() issues:\n";
            foreach ($fetchColumnIssues as $issue) {
                echo "      Line {$issue['line']}: {$issue['content']}\n";
                echo "        Has cast: " . ($issue['has_cast'] ? 'YES' : 'NO') . "\n";
                echo "        Needs fix: " . ($issue['needs_fix'] ? 'YES' : 'NO') . "\n";
                $totalIssues++;
            }
        }
    } else {
        echo "  No issues found\n";
    }
    
    echo "\n";
}

echo "=== Scan Summary ===\n";
echo "Total files scanned: " . count($filesToScan) . "\n";
echo "Files with issues: " . count($filesWithIssues) . "\n";
echo "Total issues found: $totalIssues\n\n";

if (!empty($filesWithIssues)) {
    echo "=== Files Requiring Fixes ===\n";
    foreach ($filesWithIssues as $file => $issues) {
        echo "$file:\n";
        echo "  number_format() issues: " . count($issues['number_format']) . "\n";
        echo "  fetchColumn() issues: " . count($issues['fetch_column']) . "\n";
    }
    
    echo "\n=== Next Steps ===\n";
    echo "1. Fix all number_format() calls without proper type casting\n";
    echo "2. Fix all fetchColumn() calls without proper type casting\n";
    echo "3. Test all fixed files to ensure no more TypeError issues\n";
    echo "4. Update todo list with completed fixes\n";
} else {
    echo "All files are clean! No type errors found.\n";
}

echo "\n=== Scan Complete ===\n";
?>
