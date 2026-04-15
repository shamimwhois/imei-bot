<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Models\User;

// Mock user session for testing
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'user';

// Mock user data for testing
$user = [
    'id' => 1,
    'username' => 'testuser',
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@example.com',
    'role' => 'user',
    'status' => 'active',
    'total_imei_generated' => 25,
    'total_files_downloaded' => 15,
    'daily_imei_limit' => 100,
    'daily_download_limit' => 50,
    'storage_quota_mb' => 1000,
    'storage_used_mb' => 250,
    'referral_count' => 5,
    'premium_expires_at' => null,
    'created_at' => '2024-01-01 00:00:00'
];

// Layout configuration
$title = 'UI Components Test';
$description = 'Testing the new UI components system';
$isAdminPanel = false;
$showSidebar = true;

// Page header configuration
$pageHeader = [
    'title' => 'UI Components Test',
    'description' => 'Testing all UI components functionality',
    'actions' => [
        [
            'text' => 'Test Action',
            'icon' => 'fas fa-flask',
            'url' => '#',
            'primary' => true
        ]
    ]
];

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/dashboard.php'],
    ['name' => 'UI Test', 'url' => '/test_ui_components.php']
];

// Content for layout
ob_start();
?>

<div class="space-y-8">
    <!-- Test Section -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">UI Components Test</h2>
        <p class="text-gray-600 mb-6">This page tests all the new UI components including header, sidebar, footer, and layout system.</p>
        
        <!-- Test Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <button onclick="addFlashMessage('success', 'Success message test!')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-check mr-2"></i> Test Success Message
            </button>
            <button onclick="addFlashMessage('error', 'Error message test!')" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                <i class="fas fa-times mr-2"></i> Test Error Message
            </button>
            <button onclick="showConfirmModal('Test Confirm', 'Are you sure you want to test this?', function() { alert('Confirmed!'); closeConfirmModal(); })" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-question mr-2"></i> Test Confirmation
            </button>
        </div>
        
        <!-- Test Features -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Header Features</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ Responsive navigation</li>
                    <li>✅ User dropdown menu</li>
                    <li>✅ Search functionality</li>
                    <li>✅ Dark mode toggle</li>
                    <li>✅ Notifications</li>
                </ul>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Sidebar Features</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ Collapsible navigation</li>
                    <li>✅ User stats display</li>
                    <li>✅ Quick actions</li>
                    <li>✅ Mobile responsive</li>
                    <li>✅ Smooth animations</li>
                </ul>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Footer Features</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ Comprehensive links</li>
                    <li>✅ Social media</li>
                    <li>✅ System information</li>
                    <li>✅ Back to top</li>
                    <li>✅ User stats</li>
                </ul>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Layout Features</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✅ Responsive design</li>
                    <li>✅ Breadcrumb navigation</li>
                    <li>✅ Flash messages</li>
                    <li>✅ Loading overlays</li>
                    <li>✅ Accessibility</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Responsive Test -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Responsive Design Test</h2>
        <p class="text-gray-600 mb-4">Resize your browser to test responsive behavior:</p>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-blue-100 p-4 rounded text-center">
                <i class="fas fa-mobile-alt text-blue-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium">Mobile</p>
            </div>
            <div class="bg-green-100 p-4 rounded text-center">
                <i class="fas fa-tablet-alt text-green-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium">Tablet</p>
            </div>
            <div class="bg-purple-100 p-4 rounded text-center">
                <i class="fas fa-laptop text-purple-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium">Laptop</p>
            </div>
            <div class="bg-orange-100 p-4 rounded text-center">
                <i class="fas fa-desktop text-orange-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium">Desktop</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include layout
require_once __DIR__ . '/components/layout.php';
