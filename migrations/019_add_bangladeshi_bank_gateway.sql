-- Add Bangladeshi Bank Gateway to payment_gateways table

INSERT INTO payment_gateways (
    uuid,
    name,
    gateway_type,
    is_active,
    is_test_mode,
    config,
    supported_currencies,
    min_amount,
    max_amount,
    fee_percentage,
    fixed_fee,
    settlement_time,
    description,
    display_order
) VALUES (
    UUID(),
    'Bangladeshi Bank Cards',
    'bangladeshi_banks',
    true,
    true,
    JSON_OBJECT(
        'store_id', 'test_store_id',
        'store_password', 'test_store_password',
        'test_api_url', 'https://api.sandbox.sslcommerz.com',
        'api_url', 'https://api.sslcommerz.com',
        'webhook_url', 'http://localhost:8080/api/webhooks/sslcommerz',
        'webhook_secret', 'test_webhook_secret'
    ),
    JSON_ARRAY('BDT'),
    10.00,
    100000.00,
    2.0,
    5.00,
    '3_days',
    'Credit/Debit cards from all major Bangladeshi banks via SSLCommerz',
    7
);
