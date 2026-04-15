<?php

declare(strict_types=1);

/**
 * Main Layout Component
 * Combines header, sidebar, and footer components
 */

use IMEIBot\Models\User;

// Layout configuration
$title = $title ?? 'IMEI Bot Platform';
$description = $description ?? 'Professional IMEI generation and mobile file download platform';
$showSidebar = $showSidebar ?? true;
$isAdminPanel = $isAdminPanel ?? false;
$pageClass = $pageClass ?? '';

// Determine if this is admin panel
$isAdminPanel = $isAdminPanel || (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);

// Get user data if available
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = User::findByIdAsArray($_SESSION['user_id']);
}

// Additional CSS and JS
$additionalCSS = $additionalCSS ?? '';
$additionalJS = $additionalJS ?? '';

?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - IMEI Bot Platform</title>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="IMEI generator, mobile files, firmware download, QCN, NVRAM, Android, iOS, mobile repair">
    <meta name="author" content="IMEI Bot Platform">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js (for analytics pages) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Dark mode styles */
        .dark {
            color-scheme: dark;
        }
        .dark body {
            background-color: #1f2937;
            color: #f3f4f6;
        }
        .dark .bg-white {
            background-color: #374151;
        }
        .dark .bg-gray-50 {
            background-color: #1f2937;
        }
        .dark .text-gray-900 {
            color: #f3f4f6;
        }
        .dark .text-gray-600 {
            color: #d1d5db;
        }
        .dark .text-gray-500 {
            color: #9ca3af;
        }
        .dark .border-gray-200 {
            border-color: #4b5563;
        }
        .dark .hover\:bg-gray-50:hover {
            background-color: #374151;
        }
        
        /* Loading animations */
        .loading {
            position: relative;
            overflow: hidden;
        }
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: loading 1.5s infinite;
        }
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Smooth transitions */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
        }
        
        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Focus styles */
        .focus-ring:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        
        <?= $additionalCSS ?>
    </style>
</head>
<body class="h-full bg-gray-50 <?= $pageClass ?>">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded">
        Skip to main content
    </a>

    <!-- Header Component -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Layout Container -->
    <div class="flex h-screen pt-16">
        <!-- Sidebar Component -->
        <?php if ($showSidebar): ?>
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>

        <!-- Main Content Area -->
        <main id="main-content" class="flex-1 overflow-y-auto <?= $showSidebar ? 'lg:ml-0' : '' ?>">
            <!-- Page Content -->
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Breadcrumb Navigation -->
                    <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                        <nav class="flex mb-6" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                <li class="inline-flex items-center">
                                    <a href="/dashboard" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                        <i class="fas fa-home mr-2"></i>
                                        Home
                                    </a>
                                </li>
                                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                                    <li>
                                        <div class="flex items-center">
                                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                            <?php if ($index < count($breadcrumbs) - 1): ?>
                                                <a href="<?= htmlspecialchars($crumb['url'] ?? '#') ?>" 
                                                   class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">
                                                    <?= htmlspecialchars($crumb['name']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                                                    <?= htmlspecialchars($crumb['name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    <?php endif; ?>

                    <!-- Page Header -->
                    <?php if (isset($pageHeader)): ?>
                        <div class="mb-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900">
                                        <?= htmlspecialchars($pageHeader['title'] ?? '') ?>
                                    </h1>
                                    <?php if (isset($pageHeader['description'])): ?>
                                        <p class="mt-2 text-gray-600">
                                            <?= htmlspecialchars($pageHeader['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($pageHeader['actions'])): ?>
                                    <div class="flex space-x-3">
                                        <?php foreach ($pageHeader['actions'] as $action): ?>
                                            <a href="<?= htmlspecialchars($action['url'] ?? '#') ?>" 
                                               class="<?= $action['primary'] ?? false ? 'bg-blue-600 hover:bg-blue-700' : 'bg-white hover:bg-gray-50' ?> 
                                               text-<?= $action['primary'] ?? false ? 'white' : 'gray-700' ?> 
                                               px-4 py-2 rounded-md text-sm font-medium border border-gray-300 transition-colors duration-200">
                                                <?php if (isset($action['icon'])): ?>
                                                    <i class="<?= htmlspecialchars($action['icon']) ?> mr-2"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($action['text']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Flash Messages -->
                    <?php if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])): ?>
                        <div class="mb-6 space-y-4">
                            <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="<?= match($type) {
                                        'success' => 'bg-green-50 border-green-200 text-green-800',
                                        'error' => 'bg-red-50 border-red-200 text-red-800',
                                        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                                        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
                                        default => 'bg-gray-50 border-gray-200 text-gray-800'
                                    } ?> border rounded-md p-4" role="alert">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <i class="<?= match($type) {
                                                    'success' => 'fas fa-check-circle',
                                                    'error' => 'fas fa-exclamation-circle',
                                                    'warning' => 'fas fa-exclamation-triangle',
                                                    'info' => 'fas fa-info-circle',
                                                    default => 'fas fa-info-circle'
                                                } ?>"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium">
                                                    <?= htmlspecialchars($message) ?>
                                                </p>
                                            </div>
                                            <div class="ml-auto pl-3">
                                                <div class="-mx-1.5 -my-1.5">
                                                    <button onclick="this.closest('.border').remove()" 
                                                            class="inline-flex rounded-md p-1.5 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                        <span class="sr-only">Dismiss</span>
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php unset($_SESSION['flash_messages']); ?>
                    <?php endif; ?>

                    <!-- Page Content (Dynamic) -->
                    <?= $content ?? '' ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer Component -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 flex flex-col items-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Loading...</p>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-2" id="confirm-title">Confirm Action</h3>
            <p class="text-gray-600 mb-4" id="confirm-message">Are you sure you want to proceed?</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeConfirmModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Cancel
                </button>
                <button id="confirm-button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Global functions
        function showLoading() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }

        function showConfirmModal(title, message, onConfirm) {
            document.getElementById('confirm-title').textContent = title;
            document.getElementById('confirm-message').textContent = message;
            document.getElementById('confirm-button').onclick = onConfirm;
            document.getElementById('confirm-modal').classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('confirm-modal').classList.add('hidden');
        }

        // Flash message handling
        function addFlashMessage(type, message) {
            const flashContainer = document.querySelector('.max-w-7xl .px-4');
            if (flashContainer) {
                const messageHtml = `
                    <div class="${type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 
                                 type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 
                                 type === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 
                                 'bg-blue-50 border-blue-200 text-blue-800'} border rounded-md p-4 mb-6" role="alert">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="${type === 'success' ? 'fas fa-check-circle' : 
                                          type === 'error' ? 'fas fa-exclamation-circle' : 
                                          type === 'warning' ? 'fas fa-exclamation-triangle' : 
                                          'fas fa-info-circle'}"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">${message}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button onclick="this.closest('.border').remove()" class="inline-flex rounded-md p-1.5 hover:bg-gray-100">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                flashContainer.insertAdjacentHTML('afterbegin', messageHtml);
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide flash messages after 5 seconds
            const flashMessages = document.querySelectorAll('[role="alert"]');
            flashMessages.forEach(msg => {
                setTimeout(() => {
                    msg.remove();
                }, 5000);
            });

            // Initialize tooltips if needed
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });

        // Handle AJAX errors globally
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            addFlashMessage('error', 'An unexpected error occurred. Please try again.');
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // Ctrl+K for search
            if (event.ctrlKey && event.key === 'k') {
                event.preventDefault();
                toggleSearch();
            }
            
            // Escape to close modals
            if (event.key === 'Escape') {
                closeConfirmModal();
                const searchOverlay = document.getElementById('search-overlay');
                if (searchOverlay && !searchOverlay.classList.contains('hidden')) {
                    toggleSearch();
                }
            }
        });

        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', function() {
                const perfData = performance.getEntriesByType('navigation')[0];
                console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
            });
        }

        <?= $additionalJS ?>
    </script>
</body>
</html>
