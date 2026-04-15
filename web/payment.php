<?php

declare(strict_types=1);

require_once __DIR__ . '/index.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Core\PaymentManager;

// Initialize services
$db = Database::getInstance(
    require __DIR__ . '/../config/database.php',
    new Logger(require __DIR__ . '/../config/monitoring.php')
);
$logger = new Logger(require __DIR__ . '/../config/monitoring.php');
$cache = new \IMEIBot\Core\Cache(require __DIR__ . '/../config/cache.php', $logger);
$paymentManager = new PaymentManager(require __DIR__ . '/../config/payment.php', $db, $logger, $cache);

// Handle payment form submission
$message = '';
$error = '';
$selectedGateway = $_GET['gateway'] ?? '';
$amount = (float) ($_GET['amount'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gateway = $_POST['gateway'] ?? '';
    $amount = (float) ($_POST['amount'] ?? 0);
    $currency = $_POST['currency'] ?? 'USD';
    $email = $_POST['email'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($gateway) || $amount <= 0) {
        $error = 'Please select a gateway and enter a valid amount';
    } else {
        // Create payment
        $payment = $paymentManager->createPayment(
            1, // User ID (should come from session)
            $gateway,
            $amount,
            [
                'currency' => $currency,
                'email' => $email,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'return_url' => 'http://localhost:8080/payment/success',
                'cancel_url' => 'http://localhost:8080/payment/cancel'
            ]
        );
        
        if ($payment) {
            // Redirect to payment gateway
            $paymentDetails = $payment->payment_details ?? [];
            $paymentUrl = $paymentDetails['payment_url'] ?? $paymentDetails['checkout_url'] ?? '';
            
            if ($paymentUrl) {
                header('Location: ' . $paymentUrl);
                exit;
            } else {
                // Handle gateways that don't redirect (like card payments)
                $message = 'Payment created successfully! Transaction ID: ' . $payment->transaction_id;
            }
        } else {
            $error = 'Failed to create payment. Please try again.';
        }
    }
}

// Get available gateways
$gateways = $paymentManager->getAvailableGateways();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .payment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .payment-card.selected {
            border-color: #3B82F6;
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        }
        .gateway-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
        }
        .amount-input {
            font-size: 2rem;
            font-weight: 600;
            text-align: center;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        .amount-input:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .quick-amount {
            transition: all 0.2s ease;
        }
        .quick-amount:hover {
            transform: scale(1.05);
        }
        .loading-spinner {
            display: none;
        }
        .loading .loading-spinner {
            display: inline-block;
        }
        .loading .button-text {
            display: none;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-mobile-alt text-2xl text-blue-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">IMEI Bot Platform</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-8">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-8">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Payment Form -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                <h1 class="text-2xl font-bold text-white">Make a Payment</h1>
                <p class="text-blue-100 mt-2">Choose your preferred payment method and complete your transaction securely</p>
            </div>
            
            <form method="POST" class="p-8">
                <!-- Amount Section -->
                <div class="mb-8">
                    <label class="block text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-dollar-sign mr-2"></i>Payment Amount
                    </label>
                    
                    <div class="relative mb-4">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-2xl text-gray-500">$</span>
                        <input type="number" 
                               name="amount" 
                               value="<?= htmlspecialchars($amount) ?>"
                               step="0.01" 
                               min="1" 
                               max="10000"
                               class="amount-input w-full pl-12"
                               placeholder="0.00"
                               required>
                    </div>
                    
                    <!-- Quick Amount Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="setAmount(10)" class="quick-amount px-4 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm font-medium">$10</button>
                        <button type="button" onclick="setAmount(25)" class="quick-amount px-4 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm font-medium">$25</button>
                        <button type="button" onclick="setAmount(50)" class="quick-amount px-4 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm font-medium">$50</button>
                        <button type="button" onclick="setAmount(100)" class="quick-amount px-4 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm font-medium">$100</button>
                        <button type="button" onclick="setAmount(250)" class="quick-amount px-4 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm font-medium">$250</button>
                        <button type="button" onclick="setAmount(500)" class="quick-amount px-4 py-2 bg-gray-100 hover:bg-blue-100 rounded-lg text-sm font-medium">$500</button>
                    </div>
                </div>

                <!-- Payment Gateways -->
                <div class="mb-8">
                    <label class="block text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-credit-card mr-2"></i>Select Payment Method
                    </label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($gateways as $gateway): ?>
                            <?php
                            $iconClass = match($gateway['type']) {
                                'bkash' => 'fa-mobile-alt',
                                'nagad' => 'fa-mobile-alt',
                                'bangladeshi_banks' => 'fa-university',
                                'bank_card' => 'fa-credit-card',
                                'paypal' => 'fa-paypal',
                                'binance_p2p', 'binance_b2b' => 'fa-coins',
                                default => 'fa-credit-card'
                            };
                            
                            $iconBg = match($gateway['type']) {
                                'bkash' => 'bg-pink-100 text-pink-600',
                                'nagad' => 'bg-orange-100 text-orange-600',
                                'bangladeshi_banks' => 'bg-green-100 text-green-600',
                                'bank_card' => 'bg-blue-100 text-blue-600',
                                'paypal' => 'bg-blue-100 text-blue-600',
                                'binance_p2p', 'binance_b2b' => 'bg-yellow-100 text-yellow-600',
                                default => 'bg-gray-100 text-gray-600'
                            };
                            
                            $currency = $gateway['currency'] ?? 'USD';
                            $currencySymbol = $currency === 'BDT' ? 'Tk' : '$';
                            ?>
                            <div class="payment-card border-2 border-gray-200 rounded-xl p-4" 
                                 onclick="selectGateway('<?= htmlspecialchars($gateway['type']) ?>')"
                                 data-gateway="<?= htmlspecialchars($gateway['type']) ?>">
                                <div class="flex items-center">
                                    <div class="gateway-icon <?= $iconBg ?> mr-4">
                                        <i class="fas <?= $iconClass ?>"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($gateway['name']) ?></h3>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($gateway['description']) ?></p>
                                        <div class="flex items-center mt-2 text-xs text-gray-500">
                                            <span class="mr-3">Min: <?= $currencySymbol ?><?= number_format((float) $gateway['min_amount'], 2) ?></span>
                                            <span>Fee: <?= $gateway['fee_percentage'] ?>%</span>
                                        </div>
                                        <?php if ($gateway['type'] === 'bkash' || $gateway['type'] === 'nagad'): ?>
                                            <div class="mt-2 text-xs text-blue-600">
                                                <span class="font-medium">Personal & Merchant accounts available</span>
                                            </div>
                                        <?php elseif ($gateway['type'] === 'bangladeshi_banks'): ?>
                                            <div class="mt-2 text-xs text-green-600">
                                                <span class="font-medium">All major Bangladeshi banks</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <input type="radio" 
                                               name="gateway" 
                                               value="<?= htmlspecialchars($gateway['type']) ?>"
                                               class="w-4 h-4 text-blue-600"
                                               <?= $selectedGateway === $gateway['type'] ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                            <select name="currency" id="currency" onchange="updateCurrency()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="BDT" selected>BDT - Bangladeshi Taka</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" 
                                   name="email" 
                                   placeholder="your@email.com"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <!-- Bangladeshi Payment Options -->
                    <div id="bangladeshiOptions" class="mt-6 p-4 bg-green-50 rounded-lg border border-green-200">
                        <h4 class="font-semibold text-green-800 mb-4">
                            <i class="fas fa-university mr-2"></i>Bangladeshi Payment Options
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                                <select name="account_type" id="accountType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <option value="personal">Personal Account</option>
                                    <option value="merchant">Merchant Account</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Type</label>
                                <select name="bank_type" id="bankType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <option value="all">All Banks</option>
                                    <option value="local">Local Banks Only</option>
                                    <option value="international">International Banks Only</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Card Type</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="card_type" value="all" checked class="mr-2">
                                    <span class="text-sm">All Cards</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="card_type" value="credit" class="mr-2">
                                    <span class="text-sm">Credit Cards</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="card_type" value="debit" class="mr-2">
                                    <span class="text-sm">Debit Cards</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                        <textarea name="description" 
                                  rows="3" 
                                  placeholder="Add a note about this payment..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>

                <!-- Security Badge -->
                <div class="bg-gray-50 rounded-lg p-4 mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-600 text-xl mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-900">Secure Payment</h4>
                            <p class="text-sm text-gray-600">Your payment information is encrypted and secure. We never store your card details.</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" 
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-4 px-8 rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <span class="button-text">
                            <i class="fas fa-lock mr-2"></i>
                            Proceed to Secure Payment
                        </span>
                        <span class="loading-spinner">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Processing...
                        </span>
                    </button>
                    
                    <p class="text-sm text-gray-500 mt-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        By proceeding, you agree to our Terms of Service and Privacy Policy
                    </p>
                </div>
            </form>
        </div>

        <!-- Features Section -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Secure & Encrypted</h3>
                <p class="text-gray-600 text-sm">All transactions are protected with industry-standard encryption</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Instant Processing</h3>
                <p class="text-gray-600 text-sm">Most payments are processed instantly with real-time confirmation</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-purple-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">24/7 Support</h3>
                <p class="text-gray-600 text-sm">Our support team is always here to help with any payment issues</p>
            </div>
        </div>
    </main>

    <script>
        function selectGateway(gatewayType) {
            // Remove selected class from all cards
            document.querySelectorAll('.payment-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            const selectedCard = document.querySelector(`[data-gateway="${gatewayType}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Check the radio button
            const radio = document.querySelector(`input[value="${gatewayType}"]`);
            if (radio) {
                radio.checked = true;
            }
            
            // Show/hide Bangladeshi options based on gateway
            updateBangladeshiOptions(gatewayType);
        }
        
        function setAmount(amount) {
            document.querySelector('input[name="amount"]').value = amount;
        }
        
        function updateCurrency() {
            const currency = document.getElementById('currency').value;
            const amountInput = document.querySelector('input[name="amount"]');
            const currentValue = parseFloat(amountInput.value) || 0;
            
            // Convert amount if needed (simplified conversion rates)
            const conversionRates = {
                'USD': 1,
                'EUR': 0.85,
                'GBP': 0.73,
                'BDT': 110
            };
            
            // Update placeholder and validation
            if (currency === 'BDT') {
                amountInput.placeholder = '0.00 Tk';
                amountInput.min = '10';
            } else {
                amountInput.placeholder = '0.00 $';
                amountInput.min = '1';
            }
        }
        
        function updateBangladeshiOptions(gatewayType) {
            const bangladeshiOptions = document.getElementById('bangladeshiOptions');
            const accountType = document.getElementById('accountType');
            const bankType = document.getElementById('bankType');
            
            if (gatewayType === 'bkash' || gatewayType === 'nagad') {
                bangladeshiOptions.style.display = 'block';
                accountType.disabled = false;
                bankType.disabled = true;
                bankType.style.opacity = '0.5';
                
                // Update labels for mobile banking
                if (gatewayType === 'bkash') {
                    document.querySelector('#bangladeshiOptions h4').innerHTML = '<i class="fas fa-mobile-alt mr-2"></i>bKash Payment Options';
                } else {
                    document.querySelector('#bangladeshiOptions h4').innerHTML = '<i class="fas fa-mobile-alt mr-2"></i>Nagad Payment Options';
                }
            } else if (gatewayType === 'bangladeshi_banks') {
                bangladeshiOptions.style.display = 'block';
                accountType.disabled = true;
                accountType.style.opacity = '0.5';
                bankType.disabled = false;
                
                // Update labels for bank cards
                document.querySelector('#bangladeshiOptions h4').innerHTML = '<i class="fas fa-university mr-2"></i>Bangladeshi Bank Options';
            } else {
                bangladeshiOptions.style.display = 'none';
            }
        }
        
        // Form submission handling
        document.querySelector('form').addEventListener('submit', function(e) {
            const gateway = document.querySelector('input[name="gateway"]:checked');
            const amount = document.querySelector('input[name="amount"]').value;
            
            if (!gateway) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }
            
            if (!amount || amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount');
                return;
            }
            
            // Validate Bangladeshi options if needed
            const gatewayType = gateway.value;
            if (gatewayType === 'bkash' || gatewayType === 'nagad' || gatewayType === 'bangladeshi_banks') {
                const currency = document.getElementById('currency').value;
                if (currency !== 'BDT') {
                    e.preventDefault();
                    alert('Bangladeshi payment methods only support BDT currency');
                    return;
                }
            }
            
            // Add loading state
            this.classList.add('loading');
        });
        
        // Initialize selected gateway if provided in URL
        <?php if ($selectedGateway): ?>
            selectGateway('<?= htmlspecialchars($selectedGateway) ?>');
        <?php else: ?>
            // Default to BDT currency for Bangladeshi users
            updateCurrency();
            updateBangladeshiOptions('bkash');
        <?php endif; ?>
    </script>
</body>
</html>
