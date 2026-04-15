<?php

declare(strict_types=1);

/**
 * Sidebar Component
 * Collapsible sidebar with admin and user variants
 */

use IMEIBot\Models\User;

// Get current page for active state highlighting
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPath = str_replace('/admin/', '', $currentPath);

// Determine if this is admin panel
$isAdminPanel = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

// Get user data if available
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = User::findByIdAsArray($_SESSION['user_id']);
}

// Sidebar items based on panel type
$sidebarSections = $isAdminPanel ? [
    [
        'title' => 'Main',
        'items' => [
            ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => '/admin/dashboard.php', 'active' => $currentPath === 'dashboard.php' || $currentPath === ''],
            ['name' => 'Analytics', 'icon' => 'fas fa-chart-bar', 'url' => '/admin/analytics.php', 'active' => $currentPath === 'analytics.php'],
        ]
    ],
    [
        'title' => 'Management',
        'items' => [
            ['name' => 'Users', 'icon' => 'fas fa-users', 'url' => '/admin/users.php', 'active' => $currentPath === 'users.php'],
            ['name' => 'Files', 'icon' => 'fas fa-folder', 'url' => '/admin/files.php', 'active' => $currentPath === 'files.php'],
            ['name' => 'Payments', 'icon' => 'fas fa-credit-card', 'url' => '/admin/payments.php', 'active' => $currentPath === 'payments.php'],
        ]
    ],
    [
        'title' => 'System',
        'items' => [
            ['name' => 'Settings', 'icon' => 'fas fa-cog', 'url' => '/admin/settings.php', 'active' => $currentPath === 'settings.php'],
            ['name' => 'Logs', 'icon' => 'fas fa-file-alt', 'url' => '/admin/logs.php', 'active' => $currentPath === 'logs.php'],
            ['name' => 'Backup', 'icon' => 'fas fa-database', 'url' => '/admin/backup.php', 'active' => $currentPath === 'backup.php'],
        ]
    ]
] : [
    [
        'title' => 'Main',
        'items' => [
            ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => '/dashboard.php', 'active' => $currentPath === 'dashboard.php' || $currentPath === ''],
            ['name' => 'IMEI Generator', 'icon' => 'fas fa-mobile-alt', 'url' => '/imei-generator.php', 'active' => $currentPath === 'imei-generator.php'],
            ['name' => 'File Downloads', 'icon' => 'fas fa-download', 'url' => '/files.php', 'active' => $currentPath === 'files.php'],
        ]
    ],
    [
        'title' => 'Tools',
        'items' => [
            ['name' => 'IMEI Validator', 'icon' => 'fas fa-check-circle', 'url' => '/imei-validator.php', 'active' => $currentPath === 'imei-validator.php'],
            ['name' => 'IMEI History', 'icon' => 'fas fa-history', 'url' => '/imei-history.php', 'active' => $currentPath === 'imei-history.php'],
            ['name' => 'Analytics', 'icon' => 'fas fa-chart-line', 'url' => '/analytics.php', 'active' => $currentPath === 'analytics.php'],
        ]
    ],
    [
        'title' => 'Account',
        'items' => [
            ['name' => 'Profile', 'icon' => 'fas fa-user', 'url' => '/profile.php', 'active' => $currentPath === 'profile.php'],
            ['name' => 'Settings', 'icon' => 'fas fa-cog', 'url' => '/settings.php', 'active' => $currentPath === 'settings.php'],
            ['name' => 'Billing', 'icon' => 'fas fa-credit-card', 'url' => '/billing.php', 'active' => $currentPath === 'billing.php'],
        ]
    ]
];

// Get user statistics for display
$userStats = [];
if ($user) {
    $userStats = [
        'total_imei_generated' => $user['total_imei_generated'],
        'total_files_downloaded' => $user['total_files_downloaded'],
        'daily_imei_limit' => $user['daily_imei_limit'],
        'daily_download_limit' => $user['daily_download_limit'],
        'storage_quota_mb' => $user['storage_quota_mb'],
        'storage_used_mb' => $user['storage_used_mb'],
        'is_premium' => $user['role'] === 'premium' && ($user['premium_expires_at'] === null || new DateTime($user['premium_expires_at']) > new DateTime())
    ];
    
    $userStats['imei_percentage'] = min(100, round(($userStats['total_imei_generated'] / max(1, $userStats['daily_imei_limit'])) * 100));
    $userStats['download_percentage'] = min(100, round(($userStats['total_files_downloaded'] / max(1, $userStats['daily_download_limit'])) * 100));
    $userStats['storage_percentage'] = min(100, round(($userStats['storage_used_mb'] / max(1, $userStats['storage_quota_mb'])) * 100));
}

?>
<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 hidden">
    <div class="flex flex-col h-full">
        <!-- Sidebar Header -->
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
            <div class="flex items-center">
                <i class="fas fa-robot text-blue-600 text-xl mr-2"></i>
                <span class="text-lg font-semibold text-gray-900">Menu</span>
            </div>
            <button onclick="toggleSidebar()" class="p-1 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 lg:hidden">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Sidebar Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- User Info Card (User Panel Only) -->
            <?php if (!$isAdminPanel && $user): ?>
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                            <span class="text-white font-medium">
                                <?= strtoupper(substr($user['first_name'] ?: $user['username'] ?: $user['email'], 0, 1)) ?>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?= htmlspecialchars($user['first_name'] ?: $user['username']) ?>
                            </p>
                            <p class="text-xs text-gray-500 truncate">
                                <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                <?php if ($userStats['is_premium']): ?>
                                    <i class="fas fa-crown text-yellow-500"></i>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">IMEI Today</span>
                            <span class="font-medium"><?= $userStats['total_imei_generated'] ?>/<?= $userStats['daily_imei_limit'] === 999999 ? 'Unlimited' : $userStats['daily_imei_limit'] ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: <?= $userStats['imei_percentage'] ?>%"></div>
                        </div>
                        
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Downloads</span>
                            <span class="font-medium"><?= $userStats['total_files_downloaded'] ?>/<?= $userStats['daily_download_limit'] === 999999 ? 'Unlimited' : $userStats['daily_download_limit'] ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-green-600 h-1.5 rounded-full" style="width: <?= $userStats['download_percentage'] ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation Sections -->
            <nav class="px-4 py-4 space-y-6">
                <?php foreach ($sidebarSections as $section): ?>
                    <div>
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <?= htmlspecialchars($section['title']) ?>
                        </h3>
                        <div class="mt-3 space-y-1">
                            <?php foreach ($section['items'] as $item): ?>
                                <a href="<?= htmlspecialchars($item['url']) ?>" 
                                   class="<?= $item['active'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' ?> 
                                   group flex items-center px-3 py-2 text-sm font-medium rounded-md border-transparent border-2 transition-colors duration-200">
                                    <i class="<?= htmlspecialchars($item['icon']) ?> mr-3 <?= $item['active'] ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' ?>"></i>
                                    <?= htmlspecialchars($item['name']) ?>
                                    <?php if ($item['active']): ?>
                                        <i class="fas fa-chevron-right ml-auto text-blue-500"></i>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>

            <!-- Quick Actions (User Panel) -->
            <?php if (!$isAdminPanel): ?>
                <div class="px-4 py-4 border-t border-gray-200">
                    <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Quick Actions
                    </h3>
                    <div class="space-y-2">
                        <button onclick="quickAction('imei')" class="w-full text-left px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md flex items-center">
                            <i class="fas fa-plus mr-3 text-gray-400"></i>
                            Generate IMEI
                        </button>
                        <button onclick="quickAction('download')" class="w-full text-left px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md flex items-center">
                            <i class="fas fa-download mr-3 text-gray-400"></i>
                            Browse Files
                        </button>
                        <?php if (!$userStats['is_premium']): ?>
                            <button onclick="quickAction('upgrade')" class="w-full text-left px-3 py-2 text-sm text-yellow-600 hover:text-yellow-700 hover:bg-yellow-50 rounded-md flex items-center">
                                <i class="fas fa-crown mr-3 text-yellow-400"></i>
                                Upgrade to Premium
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-gray-200">
            <?php if ($isAdminPanel): ?>
                <div class="text-xs text-gray-500">
                    <div class="flex items-center justify-between mb-2">
                        <span>System Status</span>
                        <span class="flex items-center">
                            <span class="h-2 w-2 bg-green-500 rounded-full mr-1"></span>
                            Online
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Last Backup</span>
                        <span>2 hours ago</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-2">Need help?</p>
                    <a href="/support" class="text-xs text-blue-600 hover:text-blue-700">
                        <i class="fas fa-headset mr-1"></i> Contact Support
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

<script>
// Quick action handlers
function quickAction(action) {
    switch(action) {
        case 'imei':
            window.location.href = '/imei-generator.php';
            break;
        case 'download':
            window.location.href = '/files.php';
            break;
        case 'upgrade':
            window.location.href = '/upgrade.php';
            break;
    }
}

// Enhanced sidebar toggle for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar.classList.contains('hidden')) {
        sidebar.classList.remove('hidden');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        sidebar.classList.add('hidden');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Close sidebar on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (!sidebar.classList.contains('hidden')) {
            sidebar.classList.add('hidden');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
});

// Auto-hide sidebar on window resize (desktop)
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        sidebar.classList.remove('hidden');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
});
</script>
