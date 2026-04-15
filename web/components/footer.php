<?php

declare(strict_types=1);

/**
 * Footer Component
 * Reusable footer component for both admin and user panels
 */

use IMEIBot\Models\User;

// Determine if this is admin panel
$isAdminPanel = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;

// Get current year
$currentYear = date('Y');

// Get user data if available
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = User::findByIdAsArray($_SESSION['user_id']);
}

// Footer links based on panel type
$footerLinks = $isAdminPanel ? [
    'Platform' => [
        ['name' => 'Admin Dashboard', 'url' => '/admin/dashboard.php'],
        ['name' => 'System Settings', 'url' => '/admin/settings.php'],
        ['name' => 'User Management', 'url' => '/admin/users.php'],
        ['name' => 'File Management', 'url' => '/admin/files.php'],
    ],
    'Resources' => [
        ['name' => 'Documentation', 'url' => '/docs'],
        ['name' => 'API Reference', 'url' => '/api/docs'],
        ['name' => 'System Logs', 'url' => '/admin/logs.php'],
        ['name' => 'Backup & Restore', 'url' => '/admin/backup.php'],
    ],
    'Support' => [
        ['name' => 'Help Center', 'url' => '/help'],
        ['name' => 'Contact Support', 'url' => '/support'],
        ['name' => 'System Status', 'url' => '/status'],
        ['name' => 'Report Issue', 'url' => '/report'],
    ],
] : [
    'Platform' => [
        ['name' => 'Dashboard', 'url' => '/dashboard.php'],
        ['name' => 'IMEI Generator', 'url' => '/imei-generator.php'],
        ['name' => 'File Downloads', 'url' => '/files.php'],
        ['name' => 'Analytics', 'url' => '/analytics.php'],
    ],
    'Services' => [
        ['name' => 'IMEI Validation', 'url' => '/imei-validator.php'],
        ['name' => 'Premium Features', 'url' => '/premium'],
        ['name' => 'API Access', 'url' => '/api'],
        ['name' => 'Mobile Apps', 'url' => '/mobile'],
    ],
    'Support' => [
        ['name' => 'Help Center', 'url' => '/help'],
        ['name' => 'FAQ', 'url' => '/faq'],
        ['name' => 'Contact Support', 'url' => '/support'],
        ['name' => 'Community', 'url' => '/community'],
    ],
    'Legal' => [
        ['name' => 'Terms of Service', 'url' => '/terms'],
        ['name' => 'Privacy Policy', 'url' => '/privacy'],
        ['name' => 'Cookie Policy', 'url' => '/cookies'],
        ['name' => 'Refund Policy', 'url' => '/refund'],
    ],
];

// Social media links
$socialLinks = [
    ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'url' => '#'],
    ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'url' => '#'],
    ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'url' => '#'],
    ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'url' => '#'],
    ['name' => 'GitHub', 'icon' => 'fab fa-github', 'url' => '#'],
];

// System information for admin panel
$systemInfo = [];
if ($isAdminPanel) {
    $systemInfo = [
        'version' => '1.0.0',
        'php_version' => PHP_VERSION,
        'database' => 'MySQL',
        'cache' => 'Redis',
        'uptime' => '99.9%',
    ];
}

?>
<!-- Footer -->
<footer class="bg-gray-900 text-white mt-auto">
    <!-- Main Footer Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 lg:col-span-1">
                <div class="flex items-center mb-4">
                    <i class="fas fa-robot text-blue-400 text-2xl mr-3"></i>
                    <span class="text-xl font-bold">IMEI Bot Platform</span>
                </div>
                <p class="text-gray-300 text-sm mb-4">
                    Professional IMEI generation and mobile file download platform. 
                    Advanced tools for mobile technicians and enthusiasts.
                </p>
                <div class="flex space-x-4">
                    <?php foreach ($socialLinks as $social): ?>
                        <a href="<?= htmlspecialchars($social['url']) ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200"
                           title="<?= htmlspecialchars($social['name']) ?>">
                            <i class="<?= htmlspecialchars($social['icon']) ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Footer Links -->
            <?php foreach ($footerLinks as $category => $links): ?>
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">
                        <?= htmlspecialchars($category) ?>
                    </h3>
                    <ul class="space-y-2">
                        <?php foreach ($links as $link): ?>
                            <li>
                                <a href="<?= htmlspecialchars($link['url']) ?>" 
                                   class="text-gray-300 hover:text-white text-sm transition-colors duration-200">
                                    <?= htmlspecialchars($link['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Admin System Info -->
        <?php if ($isAdminPanel && !empty($systemInfo)): ?>
            <div class="mt-8 pt-8 border-t border-gray-800">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Version:</span>
                        <span class="text-gray-300"><?= htmlspecialchars($systemInfo['version']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">PHP:</span>
                        <span class="text-gray-300"><?= htmlspecialchars($systemInfo['php_version']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Database:</span>
                        <span class="text-gray-300"><?= htmlspecialchars($systemInfo['database']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Cache:</span>
                        <span class="text-gray-300"><?= htmlspecialchars($systemInfo['cache']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Uptime:</span>
                        <span class="text-green-400"><?= htmlspecialchars($systemInfo['uptime']) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- User Quick Stats -->
        <?php if (!$isAdminPanel && $user): ?>
            <div class="mt-8 pt-8 border-t border-gray-800">
                <div class="bg-gray-800 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-white mb-3">Your Quick Stats</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-400"><?= number_format($user['total_imei_generated']) ?></div>
                            <div class="text-gray-400">IMEI Generated</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-400"><?= number_format($user['total_files_downloaded']) ?></div>
                            <div class="text-gray-400">Files Downloaded</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-400">
                                <?= $user['role'] === 'premium' ? 'Premium' : 'Basic' ?>
                            </div>
                            <div class="text-gray-400">Account Type</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Footer -->
    <div class="border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-sm text-gray-400">
                    © <?= $currentYear ?> IMEI Bot Platform. All rights reserved.
                </div>
                <div class="flex items-center space-x-6 mt-4 md:mt-0">
                    <a href="/privacy" class="text-sm text-gray-400 hover:text-white transition-colors duration-200">
                        Privacy Policy
                    </a>
                    <a href="/terms" class="text-sm text-gray-400 hover:text-white transition-colors duration-200">
                        Terms of Service
                    </a>
                    <a href="/cookies" class="text-sm text-gray-400 hover:text-white transition-colors duration-200">
                        Cookie Policy
                    </a>
                    <?php if ($isAdminPanel): ?>
                        <a href="/admin/logs.php" class="text-sm text-gray-400 hover:text-white transition-colors duration-200">
                            <i class="fas fa-file-alt mr-1"></i> System Logs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button onclick="scrollToTop()" 
            id="backToTop"
            class="fixed bottom-8 right-8 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all duration-200 opacity-0 invisible z-50">
        <i class="fas fa-arrow-up"></i>
    </button>
</footer>

<script>
// Back to top functionality
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide back to top button based on scroll position
window.addEventListener('scroll', function() {
    const backToTopButton = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
        backToTopButton.classList.remove('opacity-0', 'invisible');
        backToTopButton.classList.add('opacity-100', 'visible');
    } else {
        backToTopButton.classList.add('opacity-0', 'invisible');
        backToTopButton.classList.remove('opacity-100', 'visible');
    }
});

// Footer link tracking
document.addEventListener('DOMContentLoaded', function() {
    const footerLinks = document.querySelectorAll('footer a');
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Track footer link clicks (you can add analytics here)
            const linkText = this.textContent.trim();
            const linkUrl = this.href;
            
            // Log to console for debugging (remove in production)
            console.log('Footer link clicked:', { text: linkText, url: linkUrl });
        });
    });
});

// Responsive footer handling
function handleFooterResponsive() {
    const footer = document.querySelector('footer');
    const screenWidth = window.innerWidth;
    
    if (screenWidth < 768) {
        // Mobile footer adjustments
        footer.classList.add('text-center');
    } else {
        // Desktop footer
        footer.classList.remove('text-center');
    }
}

// Handle resize events
window.addEventListener('resize', handleFooterResponsive);
handleFooterResponsive();

// Lazy load footer images if any
function lazyLoadFooterImages() {
    const images = document.querySelectorAll('footer img[data-src]');
    images.forEach(img => {
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    lazyLoadFooterImages();
    handleFooterResponsive();
});
</script>
