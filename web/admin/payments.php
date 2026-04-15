<?php

declare(strict_types=1);

require_once __DIR__ . '/../index.php';

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

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'toggle_gateway':
            $gateway = $_POST['gateway'] ?? '';
            $enabled = isset($_POST['enabled']);
            
            if ($paymentManager->togglePlatform($gateway, $enabled)) {
                $message = "Gateway status updated successfully";
            } else {
                $error = "Failed to update gateway status";
            }
            break;
            
        case 'test_gateway':
            $gateway = $_POST['gateway'] ?? '';
            $result = $paymentManager->testGateway($gateway);
            
            if ($result['success']) {
                $message = "Gateway test successful: " . $result['message'];
            } else {
                $error = "Gateway test failed: " . $result['error'];
            }
            break;
            
        case 'refund_payment':
            $transactionId = $_POST['transaction_id'] ?? '';
            $amount = (float) ($_POST['amount'] ?? 0);
            $reason = $_POST['reason'] ?? '';
            
            if ($paymentManager->refundPayment($transactionId, $amount, $reason)) {
                $message = "Payment refunded successfully";
            } else {
                $error = "Failed to refund payment";
            }
            break;
    }
}

// Get data
$gateways = $paymentManager->getAvailableGateways();
$payments = $db->fetchAll("SELECT * FROM payments ORDER BY created_at DESC LIMIT 50");
$statistics = $paymentManager->getPaymentStatistics();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .payment-card {
            transition: all 0.3s ease;
        }
        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        .gateway-card {
            transition: all 0.3s ease;
        }
        .gateway-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">Payment Management</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Payments</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($statistics['total_payments']) ?></p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-credit-card text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Amount</p>
                        <p class="text-2xl font-bold text-gray-900">$<?= number_format($statistics['total_amount'], 2) ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Successful</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($statistics['successful_payments']) ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Failed</p>
                        <p class="text-2xl font-bold text-red-600"><?= number_format($statistics['failed_payments']) ?></p>
                    </div>
                    <div class="bg-red-100 rounded-full p-3">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Gateways -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Payment Gateways</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($gateways as $gateway): ?>
                        <div class="gateway-card border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-gray-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($gateway['name']) ?></h3>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($gateway['type']) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="status-badge <?= $gateway['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $gateway['enabled'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Min Amount:</span>
                                    <span class="font-medium">$<?= number_format($gateway['min_amount'], 2) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Max Amount:</span>
                                    <span class="font-medium">$<?= number_format($gateway['max_amount'], 2) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Fee:</span>
                                    <span class="font-medium"><?= $gateway['fee_percentage'] ?>%</span>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex space-x-2">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="toggle_gateway">
                                    <input type="hidden" name="gateway" value="<?= htmlspecialchars($gateway['type']) ?>">
                                    <input type="hidden" name="enabled" value="<?= $gateway['enabled'] ? '0' : '1' ?>">
                                    <button type="submit" class="w-full px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                        <?= $gateway['enabled'] ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="action" value="test_gateway">
                                    <input type="hidden" name="gateway" value="<?= htmlspecialchars($gateway['type']) ?>">
                                    <button type="submit" class="w-full px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                                        Test
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Recent Payments</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($payment['transaction_id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="capitalize"><?= htmlspecialchars($payment['gateway']) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    $<?= number_format($payment['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                        'refunded' => 'bg-orange-100 text-orange-800'
                                    ];
                                    $color = $statusColors[$payment['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="status-badge <?= $color ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y H:i', strtotime($payment['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($payment['status'] === 'completed'): ?>
                                        <button onclick="showRefundModal('<?= htmlspecialchars($payment['transaction_id']) ?>', <?= $payment['amount'] ?>)" 
                                                class="text-orange-600 hover:text-orange-900 mr-3">
                                            <i class="fas fa-undo"></i> Refund
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="showPaymentDetails('<?= htmlspecialchars($payment['transaction_id']) ?>')" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Refund Modal -->
    <div id="refundModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Refund Payment</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="refund_payment">
                    <input type="hidden" id="refundTransactionId" name="transaction_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount to Refund</label>
                        <input type="number" id="refundAmount" name="amount" step="0.01" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <textarea name="reason" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRefundModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                            Process Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showRefundModal(transactionId, amount) {
            document.getElementById('refundTransactionId').value = transactionId;
            document.getElementById('refundAmount').value = amount;
            document.getElementById('refundModal').classList.remove('hidden');
        }
        
        function closeRefundModal() {
            document.getElementById('refundModal').classList.add('hidden');
        }
        
        function showPaymentDetails(transactionId) {
            // Implementation for showing payment details
            alert('Payment details for: ' + transactionId);
        }
    </script>
</body>
</html>
