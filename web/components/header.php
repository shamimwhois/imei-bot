<?php

declare(strict_types=1);

/**
 * Header/Navigation Component
 * Reusable header component for both admin and user panels
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

// Navigation items based on panel type
$navItems = $isAdminPanel ? [
    ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => '/admin/dashboard.php', 'active' => $currentPath === 'dashboard.php' || $currentPath === ''],
    ['name' => 'Users', 'icon' => 'fas fa-users', 'url' => '/admin/users.php', 'active' => $currentPath === 'users.php'],
    ['name' => 'Files', 'icon' => 'fas fa-folder', 'url' => '/admin/files.php', 'active' => $currentPath === 'files.php'],
    ['name' => 'Payments', 'icon' => 'fas fa-credit-card', 'url' => '/admin/payments.php', 'active' => $currentPath === 'payments.php'],
    ['name' => 'Analytics', 'icon' => 'fas fa-chart-bar', 'url' => '/admin/analytics.php', 'active' => $currentPath === 'analytics.php'],
    ['name' => 'Settings', 'icon' => 'fas fa-cog', 'url' => '/admin/settings.php', 'active' => $currentPath === 'settings.php'],
] : [
    ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => '/dashboard.php', 'active' => $currentPath === 'dashboard.php' || $currentPath === ''],
    ['name' => 'IMEI Generator', 'icon' => 'fas fa-mobile-alt', 'url' => '/imei-generator.php', 'active' => $currentPath === 'imei-generator.php'],
    ['name' => 'File Downloads', 'icon' => 'fas fa-download', 'url' => '/files.php', 'active' => $currentPath === 'files.php'],
    ['name' => 'Analytics', 'icon' => 'fas fa-chart-line', 'url' => '/analytics.php', 'active' => $currentPath === 'analytics.php'],
    ['name' => 'Profile', 'icon' => 'fas fa-user', 'url' => '/profile.php', 'active' => $currentPath === 'profile.php'],
    ['name' => 'Settings', 'icon' => 'fas fa-cog', 'url' => '/settings.php', 'active' => $currentPath === 'settings.php'],
];

?>
<!-- Header Navigation -->
<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Brand -->
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 lg:hidden">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex-shrink-0 flex items-center ml-2 lg:ml-0">
                    <i class="fas fa-robot text-blue-600 text-2xl mr-3"></i>
                    <span class="text-xl font-bold text-gray-900">IMEI Bot Platform</span>
                    <?php if ($isAdminPanel): ?>
                        <span class="ml-3 bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">ADMIN</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex lg:items-center lg:space-x-4">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" 
                       class="<?= $item['active'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' ?> 
                       px-3 py-2 rounded-md text-sm font-medium border-transparent border-2 transition-colors duration-200 flex items-center">
                        <i class="<?= htmlspecialchars($item['icon']) ?> mr-2"></i>
                        <?= htmlspecialchars($item['name']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Right side items -->
            <div class="flex items-center space-x-4">
                <!-- Search Button (Desktop) -->
                <button onclick="toggleSearch()" class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 hidden lg:block">
                    <i class="fas fa-search"></i>
                </button>

                <!-- Notifications -->
                <button onclick="toggleNotifications()" class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 relative">
                    <i class="fas fa-bell"></i>
                    <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-500"></span>
                </button>

                <!-- Dark Mode Toggle -->
                <button onclick="toggleDarkMode()" class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 hidden lg:block">
                    <i class="fas fa-moon" id="darkModeIcon"></i>
                </button>

                <!-- User Menu -->
                <?php if ($user): ?>
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                                <span class="text-white font-medium text-sm">
                                    <?= strtoupper(substr($user['first_name'] ?: $user['username'] ?: $user['email'], 0, 1)) ?>
                                </span>
                            </div>
                            <div class="hidden lg:block">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($user['first_name'] ?: $user['username']) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </button>

                        <!-- User Dropdown Menu -->
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </p>
                                </div>
                                <a href="<?= $isAdminPanel ? '/admin/profile.php' : '/profile.php' ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i> Profile
                                </a>
                                <a href="<?= $isAdminPanel ? '/admin/settings.php' : '/settings.php' ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i> Settings
                                </a>
                                <?php if (!$isAdminPanel && $user['role'] !== 'premium'): ?>
                                    <a href="/upgrade.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-crown mr-2"></i> Upgrade to Premium
                                    </a>
                                <?php endif; ?>
                                <?php if ($user['role'] === 'admin' || $user['role'] === 'super_admin'): ?>
                                    <a href="/admin/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-shield-alt mr-2"></i> Admin Panel
                                    </a>
                                <?php endif; ?>
                                <hr class="my-1">
                                <a href="/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/auth/login" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                        Sign In
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 lg:hidden">
                    <i class="fas fa-ellipsis-v text-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobile-menu" class="hidden lg:hidden border-t border-gray-200">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" 
                   class="<?= $item['active'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' ?> 
                   block px-3 py-2 rounded-md text-base font-medium border-transparent border-2 flex items-center">
                    <i class="<?= htmlspecialchars($item['icon']) ?> mr-3"></i>
                    <?= htmlspecialchars($item['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search Bar (Hidden by default) -->
    <div id="search-bar" class="hidden border-t border-gray-200">
        <div class="px-4 py-3">
            <div class="relative">
                <input type="text" 
                       placeholder="Search..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       onkeyup="handleSearch(event)">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <button onclick="toggleSearch()" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Notifications Dropdown (Hidden by default) -->
<div id="notifications-dropdown" class="hidden fixed top-16 right-4 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
    <div class="p-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
    </div>
    <div class="max-h-96 overflow-y-auto">
        <div class="p-4 text-center text-gray-500">
            <i class="fas fa-bell text-4xl text-gray-300 mb-2"></i>
            <p>No new notifications</p>
        </div>
    </div>
</div>

<script>
// Toggle functions
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('lg:block');
    }
}

function toggleUserMenu() {
    const menu = document.getElementById('user-menu');
    menu.classList.toggle('hidden');
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
}

function toggleSearch() {
    const searchBar = document.getElementById('search-bar');
    searchBar.classList.toggle('hidden');
    if (!searchBar.classList.contains('hidden')) {
        searchBar.querySelector('input').focus();
    }
}

function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    dropdown.classList.toggle('hidden');
}

function toggleDarkMode() {
    document.body.classList.toggle('dark');
    const icon = document.getElementById('darkModeIcon');
    if (document.body.classList.contains('dark')) {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
        localStorage.setItem('darkMode', 'true');
    } else {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        localStorage.setItem('darkMode', 'false');
    }
}

function handleSearch(event) {
    if (event.key === 'Enter') {
        const query = event.target.value.trim();
        if (query) {
            window.location.href = '/search.php?q=' + encodeURIComponent(query);
        }
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.getElementById('user-menu');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    
    if (!event.target.closest('.relative') && userMenu) {
        userMenu.classList.add('hidden');
    }
    
    if (!event.target.closest('#notifications-dropdown') && !event.target.closest('[onclick="toggleNotifications()"]')) {
        notificationsDropdown.classList.add('hidden');
    }
});

// Initialize dark mode from localStorage
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark');
    const icon = document.getElementById('darkModeIcon');
    if (icon) {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
}
</script>
