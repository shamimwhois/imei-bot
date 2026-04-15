<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Services\IMEIGenerator;
use IMEIBot\Services\FileManager;
use IMEIBot\Services\SearchService;

// Initialize services
$db = Database::getInstance(
    require __DIR__ . '/../config/database.php',
    new Logger(require __DIR__ . '/../config/monitoring.php')
);
$logger = new Logger(require __DIR__ . '/../config/monitoring.php');
$imeiGenerator = new IMEIGenerator($db, new \IMEIBot\Core\Cache(require __DIR__ . '/../config/cache.php', $logger), $logger);
$fileManager = new FileManager($db, new \IMEIBot\Core\Cache(require __DIR__ . '/../config/cache.php', $logger), $logger);
$searchService = new SearchService($db, new \IMEIBot\Core\Cache(require __DIR__ . '/../config/cache.php', $logger), $logger);

// Get statistics for display
$stats = [
    'total_files' => (int) $db->fetchColumn("SELECT COUNT(*) FROM files WHERE is_active = 1"),
    'total_downloads' => (int) $db->fetchColumn("SELECT COALESCE(SUM(download_count), 0) FROM files WHERE is_active = 1"),
    'total_users' => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"),
    'popular_files' => $fileManager->getPopularFiles(6),
    'recent_files' => $fileManager->getRecentFiles(6),
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMEI Bot Platform - Advanced IMEI Generation & File Downloads</title>
    <meta name="description" content="Professional IMEI generation and mobile file download platform. Generate valid IMEI numbers, download firmware, QCN, NVRAM files for all mobile brands.">
    <meta name="keywords" content="IMEI generator, mobile files, firmware download, QCN, NVRAM, Android, iOS, mobile repair">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hover-scale {
            transition: transform 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .text-shadow {
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-robot text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-xl font-bold text-gray-900">IMEI Bot Platform</h1>
                    </div>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#features" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Features</a>
                        <a href="#files" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Files</a>
                        <a href="#pricing" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Pricing</a>
                        <a href="#contact" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Contact</a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="#telegram" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fab fa-telegram mr-2"></i>Telegram
                    </a>
                    <a href="#whatsapp" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="animate-float inline-block mb-6">
                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-4xl"></i>
                    </div>
                </div>
                <h2 class="text-4xl md:text-6xl font-bold mb-6 text-shadow">
                    Advanced IMEI Generation & File Downloads
                </h2>
                <p class="text-xl md:text-2xl mb-8 text-gray-100 max-w-3xl mx-auto">
                    Professional-grade IMEI generation with Luhn validation and comprehensive mobile file downloads
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#telegram" class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors hover-scale">
                        <i class="fab fa-telegram mr-2"></i>Start with Telegram
                    </a>
                    <a href="#whatsapp" class="bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transition-colors hover-scale">
                        <i class="fab fa-whatsapp mr-2"></i>Use WhatsApp
                    </a>
                    <a href="#features" class="glass-effect text-white px-8 py-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors hover-scale">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h3 class="text-3xl font-bold text-gray-900">Platform Statistics</h3>
                <p class="text-gray-600 mt-2">Trusted by thousands of users worldwide</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900"><?php echo number_format((int) $stats['total_users']); ?>+</div>
                    <div class="text-gray-600">Active Users</div>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file text-green-600 text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900"><?php echo number_format((int) $stats['total_files']); ?>+</div>
                    <div class="text-gray-600">Files Available</div>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-download text-purple-600 text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900"><?php echo number_format((int) $stats['total_downloads']); ?>+</div>
                    <div class="text-gray-600">Downloads</div>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-mobile-alt text-orange-600 text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900">99.9%</div>
                    <div class="text-gray-600">Success Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-bold text-gray-900">Powerful Features</h3>
                <p class="text-gray-600 mt-2">Everything you need for IMEI generation and mobile file downloads</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-hashtag text-blue-600 text-xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-3">IMEI Generation</h4>
                    <p class="text-gray-600 mb-4">Generate valid IMEI numbers with Luhn algorithm validation. Support for multiple prefixes (35, 86, 89, 45).</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Random IMEI generation</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Single, dual, and multi-IMEI</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Prefix selection</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Validation checking</li>
                    </ul>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-download text-green-600 text-xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-3">File Downloads</h4>
                    <p class="text-gray-600 mb-4">Access comprehensive mobile files including firmware, QCN, NVRAM, and solutions.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Firmware files</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Engineering ROMs</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>QCN/NVRAM files</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Solution files</li>
                    </ul>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-search text-purple-600 text-xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-3">Advanced Search</h4>
                    <p class="text-gray-600 mb-4">Powerful search functionality with filters for brand, model, and file type.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Brand filtering</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Model selection</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Category search</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Version filtering</li>
                    </ul>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-crown text-orange-600 text-xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-3">Premium Features</h4>
                    <p class="text-gray-600 mb-4">Unlock unlimited access with our premium subscription plans.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Unlimited IMEI generation</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Priority downloads</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Advanced search</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Priority support</li>
                    </ul>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-3">Security & Privacy</h4>
                    <p class="text-gray-600 mb-4">Your data is protected with enterprise-grade security measures.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Secure connections</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Data encryption</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Privacy protection</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>GDPR compliant</li>
                    </ul>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-indigo-600 text-xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-900 mb-3">24/7 Support</h4>
                    <p class="text-gray-600 mb-4">Get help whenever you need it with our dedicated support team.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Live chat support</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Email support</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Documentation</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Video tutorials</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Files Section -->
    <section id="files" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-bold text-gray-900">Popular Files</h3>
                <p class="text-gray-600 mt-2">Most downloaded files from our collection</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($stats['popular_files'] as $file): ?>
                    <div class="bg-gray-50 rounded-lg p-6 hover-scale">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($file['display_name'] ?: $file['name']); ?></h4>
                                <p class="text-sm text-gray-600"><?php echo ucfirst($file['category']); ?></p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                <?php echo $file['file_size_formatted']; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <span><i class="fas fa-download mr-1"></i><?php echo number_format((int) $file['download_count']); ?> downloads</span>
                            <span><i class="fas fa-star mr-1"></i><?php echo $file['rating'] ?: 'N/A'; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="#pricing" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    Browse All Files
                </a>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-bold text-gray-900">Choose Your Plan</h3>
                <p class="text-gray-600 mt-2">Flexible pricing options for every need</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-8">
                        <h4 class="text-2xl font-bold text-gray-900 mb-2">Free</h4>
                        <p class="text-gray-600 mb-6">Perfect for getting started</p>
                        <div class="text-4xl font-bold text-gray-900 mb-6">$0<span class="text-lg font-medium text-gray-600">/month</span></div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>10 IMEI per day
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>5 file downloads per day
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>Basic search
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>Community support
                            </li>
                        </ul>
                        <button class="w-full bg-gray-200 text-gray-800 px-6 py-3 rounded-lg font-medium">
                            Current Plan
                        </button>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border-2 border-blue-500">
                    <div class="bg-blue-500 text-white p-8">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-2xl font-bold">Premium</h4>
                            <span class="bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-sm font-medium">Popular</span>
                        </div>
                        <p class="text-blue-100 mb-6">For power users and professionals</p>
                        <div class="text-4xl font-bold text-white mb-6">$9.99<span class="text-lg font-medium text-blue-100">/month</span></div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center text-white">
                                <i class="fas fa-check text-green-400 mr-3"></i>Unlimited IMEI generation
                            </li>
                            <li class="flex items-center text-white">
                                <i class="fas fa-check text-green-400 mr-3"></i>Unlimited downloads
                            </li>
                            <li class="flex items-center text-white">
                                <i class="fas fa-check text-green-400 mr-3"></i>Advanced search
                            </li>
                            <li class="flex items-center text-white">
                                <i class="fas fa-check text-green-400 mr-3"></i>Priority support
                            </li>
                        </ul>
                        <button class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            Upgrade to Premium
                        </button>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-8">
                        <h4 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h4>
                        <p class="text-gray-600 mb-6">For teams and organizations</p>
                        <div class="text-4xl font-bold text-gray-900 mb-6">$29.99<span class="text-lg font-medium text-gray-600">/month</span></div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>Everything in Premium
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>API access
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>Custom branding
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3"></i>Dedicated support
                            </li>
                        </ul>
                        <button class="w-full bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 transition-colors">
                            Contact Sales
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bot Access Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-bold text-gray-900">Start Using Our Bots</h3>
                <p class="text-gray-600 mt-2">Choose your preferred platform and start generating IMEIs instantly</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="text-center">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fab fa-telegram text-blue-600 text-4xl"></i>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-900 mb-4">Telegram Bot</h4>
                    <p class="text-gray-600 mb-6">Fast and reliable IMEI generation directly in Telegram</p>
                    <div class="space-y-3">
                        <a href="https://t.me/your_bot_username" target="_blank" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors inline-block">
                            <i class="fab fa-telegram mr-2"></i>Open in Telegram
                        </a>
                        <div class="text-sm text-gray-500">
                            <p>Username: @imei_bot_platform</p>
                            <p>Click "Start" to begin</p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fab fa-whatsapp text-green-600 text-4xl"></i>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-900 mb-4">WhatsApp Bot</h4>
                    <p class="text-gray-600 mb-6">Professional IMEI generation with WhatsApp Business API</p>
                    <div class="space-y-3">
                        <a href="#" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors inline-block">
                            <i class="fab fa-whatsapp mr-2"></i>Start Conversation
                        </a>
                        <div class="text-sm text-gray-500">
                            <p>Save: +1 234 567 8900</p>
                            <p>Send "Start" to begin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-robot text-white text-sm"></i>
                        </div>
                        <span class="ml-3 text-xl font-bold">IMEI Bot Platform</span>
                    </div>
                    <p class="text-gray-400">Professional IMEI generation and mobile file download platform</p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Features</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">IMEI Generation</a></li>
                        <li><a href="#" class="hover:text-white">File Downloads</a></li>
                        <li><a href="#" class="hover:text-white">Search & Filter</a></li>
                        <li><a href="#" class="hover:text-white">Premium Plans</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Documentation</a></li>
                        <li><a href="#" class="hover:text-white">Contact Support</a></li>
                        <li><a href="#" class="hover:text-white">FAQ</a></li>
                        <li><a href="#" class="hover:text-white">Status Page</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white">Cookie Policy</a></li>
                        <li><a href="#" class="hover:text-white">Disclaimer</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 IMEI Bot Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(section);
        });
    </script>
</body>
</html>
