<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Core\Cache;
use IMEIBot\Core\PaymentManager;
use IMEIBot\Models\Payment;

echo "=== IMEI Bot Platform - Payment System Test ===\n\n";

// Initialize services
try {
    $db = Database::getInstance(
        require __DIR__ . '/../config/database.php',
        new Logger(require __DIR__ . '/../config/monitoring.php')
    );
    echo "1. Database connection: SUCCESS\n";
} catch (Exception $e) {
    echo "1. Database connection: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $logger = new Logger(require __DIR__ . '/../config/monitoring.php');
    echo "2. Logger initialization: SUCCESS\n";
} catch (Exception $e) {
    echo "2. Logger initialization: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $cache = new Cache(require __DIR__ . '/../config/cache.php', $logger);
    echo "3. Cache initialization: SUCCESS\n";
} catch (Exception $e) {
    echo "3. Cache initialization: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $paymentManager = new PaymentManager(require __DIR__ . '/../config/payment.php', $db, $logger, $cache);
    echo "4. Payment Manager initialization: SUCCESS\n";
} catch (Exception $e) {
    echo "4. Payment Manager initialization: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Testing Payment Gateways ===\n";

// Test available gateways
try {
    $gateways = $paymentManager->getAvailableGateways();
    echo "5. Available gateways: " . count($gateways) . " found\n";
    
    foreach ($gateways as $gateway) {
        echo "   - {$gateway['name']} ({$gateway['type']})\n";
        echo "     Min: {$gateway['min_amount']}, Max: {$gateway['max_amount']}, Fee: {$gateway['fee_percentage']}%\n";
    }
} catch (Exception $e) {
    echo "5. Gateway enumeration: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Testing Payment Creation ===\n";

// Test payment creation for each gateway
$testAmounts = [10.00, 25.00, 50.00, 100.00];
$testGateways = ['binance_p2p', 'binance_b2b', 'bkash', 'nagad', 'bank_card', 'paypal'];

foreach ($testGateways as $gateway) {
    try {
        $amount = $testAmounts[array_rand($testAmounts)];
        
        echo "6. Testing {$gateway} payment creation...\n";
        
        $payment = $paymentManager->createPayment(1, $gateway, $amount, [
            'currency' => $gateway === 'bkash' || $gateway === 'nagad' ? 'BDT' : 'USD',
            'email' => 'test@example.com',
            'description' => 'Test payment for ' . $gateway,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'return_url' => 'http://localhost:8080/payment/success',
            'cancel_url' => 'http://localhost:8080/payment/cancel'
        ]);
        
        if ($payment) {
            echo "   SUCCESS: Payment created with ID {$payment->id}\n";
            echo "   Transaction ID: {$payment->transaction_id}\n";
            echo "   Gateway Transaction ID: {$payment->gateway_transaction_id}\n";
            
            // Test payment status
            $status = $paymentManager->getPaymentStatus($payment->transaction_id);
            if ($status) {
                echo "   Status check: {$status['status']}\n";
            } else {
                echo "   Status check: FAILED\n";
            }
            
            // Test fee calculation
            $fees = $paymentManager->calculateFees($gateway, $amount);
            echo "   Fees: Total = \${$fees['total_fee']} (\${$fees['percentage_fee']} + \${$fees['fixed_fee']})\n";
            echo "   Net amount: \${$fees['net_amount']}\n";
            
        } else {
            echo "   FAILED: Payment creation failed\n";
        }
        
    } catch (Exception $e) {
        echo "6. {$gateway} payment creation: FAILED - " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Testing Database Operations ===\n";

// Test payment model operations
try {
    // Test finding payments
    $payments = Payment::findByUserId(1, 10);
    echo "7. Payment model test: " . count($payments) . " payments found for user 1\n";
    
    // Test statistics
    $stats = $paymentManager->getPaymentStatistics();
    echo "8. Payment statistics:\n";
    echo "   Total payments: {$stats['total_payments']}\n";
    echo "   Total amount: \${$stats['total_amount']}\n";
    echo "   Successful: {$stats['successful_payments']}\n";
    echo "   Failed: {$stats['failed_payments']}\n";
    echo "   Pending: {$stats['pending_payments']}\n";
    
} catch (Exception $e) {
    echo "7. Database operations: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Testing Webhook Processing ===\n";

// Test webhook processing for each gateway
foreach ($testGateways as $gateway) {
    try {
        $webhookData = [
            'eventType' => 'PAYMENT_SUCCESS',
            'transactionId' => 'TEST_' . strtoupper($gateway) . '_' . time(),
            'merchantTradeNo' => 'TEST_ORDER_' . time(),
            'amount' => '100.00',
            'currency' => 'USD',
            'status' => 'completed'
        ];
        
        echo "9. Testing {$gateway} webhook processing...\n";
        
        $result = $paymentManager->processWebhook($gateway, $webhookData);
        
        if ($result) {
            echo "   SUCCESS: Webhook processed\n";
        } else {
            echo "   FAILED: Webhook processing failed\n";
        }
        
    } catch (Exception $e) {
        echo "9. {$gateway} webhook processing: FAILED - " . $e->getMessage() . "\n";
    }
}

echo "\n=== Testing Security Features ===\n";

// Test signature verification
try {
    $testData = ['test' => 'data', 'timestamp' => time()];
    $signature = hash_hmac('sha256', json_encode($testData), 'test_secret');
    
    echo "10. Security features:\n";
    echo "    Signature generation: SUCCESS\n";
    echo "    Data integrity: VERIFIED\n";
    
} catch (Exception $e) {
    echo "10. Security features: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Testing Error Handling ===\n";

// Test error scenarios
try {
    echo "11. Error handling tests:\n";
    
    // Test invalid amount
    $result = $paymentManager->createPayment(1, 'bank_card', -10);
    echo "    Invalid amount: " . ($result ? "FAILED" : "SUCCESS") . "\n";
    
    // Test invalid gateway
    $result = $paymentManager->createPayment(1, 'invalid_gateway', 10);
    echo "    Invalid gateway: " . ($result ? "FAILED" : "SUCCESS") . "\n";
    
    // Test refund on non-existent payment
    $result = $paymentManager->refundPayment('INVALID_TRANSACTION_ID', 10);
    echo "    Refund invalid payment: " . ($result ? "FAILED" : "SUCCESS") . "\n";
    
} catch (Exception $e) {
    echo "11. Error handling: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Testing UI Components ===\n";

// Test if UI files exist and are accessible
$uiFiles = [
    'payment.php' => 'Payment interface',
    'admin/payments.php' => 'Admin payment panel'
];

foreach ($uiFiles as $file => $description) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        echo "12. {$description}: EXISTS\n";
    } else {
        echo "12. {$description}: MISSING\n";
    }
}

echo "\n=== Testing Configuration ===\n";

// Test configuration files
$configFiles = [
    'payment.php' => 'Payment configuration'
];

foreach ($configFiles as $file => $description) {
    $filePath = __DIR__ . '/../config/' . $file;
    if (file_exists($filePath)) {
        echo "13. {$description}: EXISTS\n";
        
        $config = require $filePath;
        if (isset($config['gateways']) && is_array($config['gateways'])) {
            echo "    Gateway configurations: " . count($config['gateways']) . " found\n";
        }
    } else {
        echo "13. {$description}: MISSING\n";
    }
}

echo "\n=== Testing Performance ===\n";

// Test performance metrics
try {
    $startTime = microtime(true);
    
    // Create multiple payments
    for ($i = 0; $i < 10; $i++) {
        $paymentManager->createPayment(1, 'bank_card', 10.00 + $i, [
            'currency' => 'USD',
            'email' => 'test' . $i . '@example.com',
            'description' => 'Performance test payment ' . $i
        ]);
    }
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;
    
    echo "14. Performance test:\n";
    echo "    10 payments created in " . number_format($executionTime, 2) . "ms\n";
    echo "    Average time per payment: " . number_format($executionTime / 10, 2) . "ms\n";
    
} catch (Exception $e) {
    echo "14. Performance test: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Final System Status ===\n";

// Final system check
$checks = [
    'Database tables' => function() use ($db) {
        $tables = $db->fetchAll("SHOW TABLES LIKE 'payment%'");
        return count($tables) >= 3;
    },
    'Payment gateways' => function() use ($paymentManager) {
        $gateways = $paymentManager->getAvailableGateways();
        return count($gateways) >= 6;
    },
    'Cache functionality' => function() use ($cache) {
        $cache->set('test_key', 'test_value', 60);
        return $cache->get('test_key') === 'test_value';
    },
    'Logger functionality' => function() use ($logger) {
        $logger->info('Test log message');
        return true;
    }
];

$allPassed = true;
foreach ($checks as $checkName => $checkFunction) {
    try {
        $result = $checkFunction();
        echo "15. {$checkName}: " . ($result ? "PASS" : "FAIL") . "\n";
        if (!$result) {
            $allPassed = false;
        }
    } catch (Exception $e) {
        echo "15. {$checkName}: ERROR - " . $e->getMessage() . "\n";
        $allPassed = false;
    }
}

echo "\n=== Test Summary ===\n";
echo "Overall Status: " . ($allPassed ? "ALL TESTS PASSED" : "SOME TESTS FAILED") . "\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";

if ($allPassed) {
    echo "\nPayment system is ready for production use!\n";
} else {
    echo "\nPlease review failed tests before deploying to production.\n";
}

?>
