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
    
    echo "Running payment migrations...\n";
    
    // Run payments table migration
    echo "Creating payments table...\n";
    $sql = file_get_contents(__DIR__ . '/../migrations/016_create_payments_table.sql');
    $db->exec($sql);
    echo "Payments table created successfully\n";
    
    // Run payment_gateways table migration
    echo "Creating payment_gateways table...\n";
    $sql = file_get_contents(__DIR__ . '/../migrations/017_create_payment_gateways_table.sql');
    $db->exec($sql);
    echo "Payment gateways table created successfully\n";
    
    // Run payment_subscriptions table migration
    echo "Creating payment_subscriptions table...\n";
    $sql = file_get_contents(__DIR__ . '/../migrations/018_create_payment_subscriptions_table.sql');
    $db->exec($sql);
    echo "Payment subscriptions table created successfully\n";
    
    // Insert sample payment gateway configurations
    echo "Inserting sample payment gateway configurations...\n";
    
    $gateways = [
        [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Binance P2P',
            'gateway_type' => 'binance_p2p',
            'is_active' => true,
            'is_test_mode' => true,
            'config' => json_encode([
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
                'test_api_url' => 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/portal',
                'api_url' => 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/portal'
            ]),
            'supported_currencies' => json_encode(['USD', 'USDT', 'BTC', 'ETH']),
            'min_amount' => 1.00,
            'max_amount' => 10000.00,
            'fee_percentage' => 0.5,
            'fixed_fee' => 0.00,
            'settlement_time' => 'instant',
            'description' => 'Binance P2P trading platform',
            'display_order' => 1
        ],
        [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Binance Pay',
            'gateway_type' => 'binance_b2b',
            'is_active' => true,
            'is_test_mode' => true,
            'config' => json_encode([
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
                'merchant_id' => 'test_merchant',
                'test_api_url' => 'https://api.binance.com',
                'api_url' => 'https://api.binance.com'
            ]),
            'supported_currencies' => json_encode(['USD', 'USDT', 'BTC', 'ETH']),
            'min_amount' => 1.00,
            'max_amount' => 10000.00,
            'fee_percentage' => 1.0,
            'fixed_fee' => 0.00,
            'settlement_time' => 'instant',
            'description' => 'Binance Pay cryptocurrency payment',
            'display_order' => 2
        ],
        [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'bKash',
            'gateway_type' => 'bkash',
            'is_active' => true,
            'is_test_mode' => true,
            'config' => json_encode([
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
                'merchant_id' => 'test_merchant',
                'test_api_url' => 'https://checkout.sandbox.bka.sh/v1.2.0-beta',
                'api_url' => 'https://checkout.pay.bka.sh/v1.2.0-beta'
            ]),
            'supported_currencies' => json_encode(['BDT']),
            'min_amount' => 10.00,
            'max_amount' => 50000.00,
            'fee_percentage' => 1.5,
            'fixed_fee' => 0.00,
            'settlement_time' => '24_hours',
            'description' => 'bKash mobile banking',
            'display_order' => 3
        ],
        [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Nagad',
            'gateway_type' => 'nagad',
            'is_active' => true,
            'is_test_mode' => true,
            'config' => json_encode([
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
                'merchant_id' => 'test_merchant',
                'merchant_number' => '01800000000',
                'private_key' => 'test_private_key',
                'public_key' => 'test_public_key',
                'test_api_url' => 'https://sandbox.mynagad.com',
                'api_url' => 'https://api.mynagad.com'
            ]),
            'supported_currencies' => json_encode(['BDT']),
            'min_amount' => 10.00,
            'max_amount' => 50000.00,
            'fee_percentage' => 1.5,
            'fixed_fee' => 0.00,
            'settlement_time' => '24_hours',
            'description' => 'Nagad mobile banking',
            'display_order' => 4
        ],
        [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Bank Card',
            'gateway_type' => 'bank_card',
            'is_active' => true,
            'is_test_mode' => true,
            'config' => json_encode([
                'publishable_key' => 'pk_test_1234567890',
                'secret_key' => 'sk_test_1234567890',
                'test_api_url' => 'https://api.sandbox.stripe.com',
                'api_url' => 'https://api.stripe.com'
            ]),
            'supported_currencies' => json_encode(['USD', 'EUR', 'GBP', 'BDT']),
            'min_amount' => 1.00,
            'max_amount' => 10000.00,
            'fee_percentage' => 2.9,
            'fixed_fee' => 0.30,
            'settlement_time' => '3_days',
            'description' => 'Credit/Debit card payments via Stripe',
            'display_order' => 5
        ],
        [
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'PayPal',
            'gateway_type' => 'paypal',
            'is_active' => true,
            'is_test_mode' => true,
            'config' => json_encode([
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'test_api_url' => 'https://api-m.sandbox.paypal.com',
                'api_url' => 'https://api-m.paypal.com'
            ]),
            'supported_currencies' => json_encode(['USD', 'EUR', 'GBP']),
            'min_amount' => 1.00,
            'max_amount' => 10000.00,
            'fee_percentage' => 2.9,
            'fixed_fee' => 0.30,
            'settlement_time' => 'instant',
            'description' => 'PayPal digital wallet',
            'display_order' => 6
        ]
    ];
    
    foreach ($gateways as $gateway) {
        $columns = array_keys($gateway);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO payment_gateways (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($gateway));
    }
    
    echo "Sample payment gateway configurations inserted successfully\n";
    
    echo "\nPayment migrations completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
