<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IMEIBot\Services\JWTService;
use IMEIBot\Core\Logger;
use IMEIBot\Models\User;

session_start();

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = User::findByIdAsArray($_SESSION['user_id']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-robot text-blue-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">IMEI Bot Platform</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Home
                        </a>
                        <a href="/docs" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            API Docs
                        </a>
                        <?php if ($user): ?>
                            <a href="/dashboard" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <?php if (in_array($user['role'], ['admin', 'super_admin'])): ?>
                                <a href="/admin/dashboard" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Admin
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="/auth/login" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Login
                            </a>
                            <a href="/auth/register" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Register
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center">
                    <?php if ($user): ?>
                        <div class="relative">
                            <button onclick="toggleUserMenu()" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                                    <span class="text-white font-medium">
                                        <?php echo strtoupper(substr($user['first_name'] ?: $user['username'] ?: $user['email'], 0, 1)); ?>
                                    </span>
                                </div>
                                <span class="ml-2 text-gray-700 font-medium"><?php echo htmlspecialchars($user['first_name'] ?: $user['username'] ?: $user['email']); ?></span>
                                <i class="fas fa-chevron-down text-gray-400 ml-2"></i>
                            </button>
                            
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    <a href="/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-home mr-2"></i> Dashboard
                                    </a>
                                    <a href="/auth/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-book mr-3 text-blue-600"></i>
                    API Documentation
                </h1>
                <p class="text-lg text-gray-600">
                    Complete API reference for the IMEI Bot Platform. Integrate our services into your applications.
                </p>
            </div>

            <!-- Table of Contents -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="#authentication" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-lock text-blue-600 mr-2"></i>
                            Authentication
                        </a>
                        <a href="#user-management" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-users text-blue-600 mr-2"></i>
                            User Management
                        </a>
                        <a href="#imei-generator" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>
                            IMEI Generator
                        </a>
                        <a href="#file-downloads" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-download text-blue-600 mr-2"></i>
                            File Downloads
                        </a>
                        <a href="#bot-integration" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-robot text-blue-600 mr-2"></i>
                            Bot Integration
                        </a>
                        <a href="#admin-api" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-cog text-blue-600 mr-2"></i>
                            Admin API
                        </a>
                    </div>
                </div>
            </div>

            <!-- Authentication Section -->
            <section id="authentication" class="mb-12">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-lock text-blue-600 mr-2"></i>
                            Authentication
                        </h2>
                        
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Overview</h3>
                            <p class="text-gray-600 mb-4">
                                The IMEI Bot Platform uses JWT (JSON Web Token) authentication. All API endpoints (except authentication endpoints) require a valid JWT token in the Authorization header.
                            </p>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Base URL</h3>
                            <div class="bg-gray-100 p-3 rounded-lg font-mono text-sm">
                                https://api.imei-bot.com/v1
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Authorization Header</h3>
                            <div class="bg-gray-100 p-3 rounded-lg font-mono text-sm">
                                Authorization: Bearer &lt;your_jwt_token&gt;
                            </div>
                        </div>

                        <!-- Register Endpoint -->
                        <div class="mb-8 border-l-4 border-blue-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Register User</h4>
                            <p class="text-gray-600 mb-3">Create a new user account.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/auth/register</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Request Body:</h5>
                                <pre><code class="language-json">{
  "email": "user@example.com",
  "password": "securepassword123",
  "username": "username",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "language": "en",
  "timezone": "UTC",
  "referral_code": "REFERRAL123"
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 123,
      "email": "user@example.com",
      "username": "username",
      "role": "user",
      "status": "active"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>

                        <!-- Login Endpoint -->
                        <div class="mb-8 border-l-4 border-green-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Login User</h4>
                            <p class="text-gray-600 mb-3">Authenticate a user and get access tokens.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/auth/login</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Request Body:</h5>
                                <pre><code class="language-json">{
  "identifier": "user@example.com",
  "password": "securepassword123"
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 123,
      "email": "user@example.com",
      "username": "username",
      "role": "user",
      "status": "active"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>

                        <!-- Refresh Token Endpoint -->
                        <div class="mb-8 border-l-4 border-yellow-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Refresh Token</h4>
                            <p class="text-gray-600 mb-3">Get new access tokens using a refresh token.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/auth/refresh</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Request Body:</h5>
                                <pre><code class="language-json">{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- User Management Section -->
            <section id="user-management" class="mb-12">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-users text-blue-600 mr-2"></i>
                            User Management
                        </h2>

                        <!-- Get Current User -->
                        <div class="mb-8 border-l-4 border-blue-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Get Current User</h4>
                            <p class="text-gray-600 mb-3">Get information about the currently authenticated user.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">GET</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/auth/me</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "User data retrieved",
  "data": {
    "user": {
      "id": 123,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "user@example.com",
      "username": "username",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+1234567890",
      "role": "user",
      "status": "active",
      "language": "en",
      "timezone": "UTC",
      "daily_imei_limit": 10,
      "daily_download_limit": 5,
      "total_imei_generated": 15,
      "total_files_downloaded": 8,
      "storage_quota_mb": 100,
      "storage_used_mb": 45,
      "referral_code": "USER123",
      "referral_count": 3,
      "created_at": "2023-01-01T12:00:00Z",
      "last_login_at": "2023-12-01T10:30:00Z"
    }
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>

                        <!-- Logout -->
                        <div class="mb-8 border-l-4 border-red-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Logout</h4>
                            <p class="text-gray-600 mb-3">Logout the current user and invalidate tokens.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/auth/logout</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "Logout successful",
  "data": {},
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- IMEI Generator Section -->
            <section id="imei-generator" class="mb-12">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>
                            IMEI Generator
                        </h2>

                        <!-- Generate IMEI -->
                        <div class="mb-8 border-l-4 border-green-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Generate IMEI</h4>
                            <p class="text-gray-600 mb-3">Generate a valid IMEI number for testing purposes.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/imei/generate</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Request Body (Optional):</h5>
                                <pre><code class="language-json">{
  "brand": "Apple",
  "model": "iPhone 14",
  "tac": "013599000"
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "IMEI generated successfully",
  "data": {
    "imei": "3512345678901234",
    "tac": "013599000",
    "serial": "12345678",
    "checksum": "4",
    "brand": "Apple",
    "model": "iPhone 14",
    "generated_at": "2023-12-01T10:30:00Z"
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>

                        <!-- Validate IMEI -->
                        <div class="mb-8 border-l-4 border-blue-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Validate IMEI</h4>
                            <p class="text-gray-600 mb-3">Validate an IMEI number using Luhn algorithm.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/imei/validate</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Request Body:</h5>
                                <pre><code class="language-json">{
  "imei": "3512345678901234"
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "IMEI validation completed",
  "data": {
    "imei": "3512345678901234",
    "valid": true,
    "tac": "013599000",
    "serial": "12345678",
    "checksum": "4"
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bot Integration Section -->
            <section id="bot-integration" class="mb-12">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-robot text-blue-600 mr-2"></i>
                            Bot Integration
                        </h2>

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Overview</h3>
                            <p class="text-gray-600 mb-4">
                                Our platform supports integration with Telegram and WhatsApp bots. Bot authentication uses a special token that includes platform-specific identifiers.
                            </p>
                        </div>

                        <!-- Telegram Bot Auth -->
                        <div class="mb-8 border-l-4 border-blue-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Telegram Bot Authentication</h4>
                            <p class="text-gray-600 mb-3">Authenticate users from Telegram bot.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/bot/auth/telegram</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Request Body:</h5>
                                <pre><code class="language-json">{
  "telegram_user": {
    "id": 123456789,
    "username": "telegram_user",
    "first_name": "John",
    "last_name": "Doe",
    "language_code": "en"
  }
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "Telegram user authenticated",
  "data": {
    "user": {
      "id": 123,
      "telegram_id": 123456789,
      "username": "telegram_user",
      "role": "user"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    },
    "platform": "telegram"
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>

                        <!-- WhatsApp Bot Auth -->
                        <div class="mb-8 border-l-4 border-green-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">WhatsApp Bot Authentication</h4>
                            <p class="text-gray-600 mb-3">Authenticate users from WhatsApp bot.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">POST</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/bot/auth/whatsapp</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Request Body:</h5>
                                <pre><code class="language-json">{
  "phone_number": "+1234567890",
  "profile_data": {
    "first_name": "John",
    "last_name": "Doe",
    "language": "en"
  }
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "WhatsApp user authenticated",
  "data": {
    "user": {
      "id": 124,
      "whatsapp_phone": "+1234567890",
      "role": "user"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    },
    "platform": "whatsapp"
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Admin API Section -->
            <section id="admin-api" class="mb-12">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-cog text-blue-600 mr-2"></i>
                            Admin API
                        </h2>

                        <div class="mb-6">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Admin endpoints require admin or super_admin role.</strong> Make sure your user has the appropriate permissions.
                                </p>
                            </div>
                        </div>

                        <!-- Admin Dashboard -->
                        <div class="mb-8 border-l-4 border-purple-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Admin Dashboard</h4>
                            <p class="text-gray-600 mb-3">Get admin dashboard statistics.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded">GET</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/admin/dashboard</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "Admin dashboard data retrieved",
  "data": {
    "stats": {
      "total_users": 1000,
      "users_by_role": {
        "user": 800,
        "premium": 150,
        "admin": 45,
        "super_admin": 5
      },
      "users_by_status": {
        "active": 950,
        "inactive": 30,
        "banned": 15,
        "pending": 5
      },
      "registrations_24h": 25,
      "active_users_24h": 180,
      "premium_users": 150
    }
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>

                        <!-- Manage Users -->
                        <div class="mb-8 border-l-4 border-red-500 pl-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-3">Manage Users</h4>
                            <p class="text-gray-600 mb-3">List and manage platform users.</p>
                            
                            <div class="mb-4">
                                <span class="inline-block bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">GET</span>
                                <code class="ml-2 bg-gray-100 px-2 py-1 rounded text-sm">/admin/users</code>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Query Parameters:</h5>
                                <ul class="list-disc list-inside text-gray-600 space-y-1">
                                    <li><code>page</code> (int): Page number (default: 1)</li>
                                    <li><code>limit</code> (int): Results per page (default: 20)</li>
                                    <li><code>search</code> (string): Search users by email, username, or name</li>
                                    <li><code>role</code> (string): Filter by role (user, premium, admin, super_admin)</li>
                                    <li><code>status</code> (string): Filter by status (active, inactive, banned, pending)</li>
                                    <li><code>sort_by</code> (string): Sort field (created_at, last_login_at, email)</li>
                                    <li><code>sort_order</code> (string): Sort order (asc, desc)</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h5 class="font-semibold text-gray-700 mb-2">Response:</h5>
                                <pre><code class="language-json">{
  "success": true,
  "message": "Users retrieved successfully",
  "data": {
    "users": [
      {
        "id": 123,
        "email": "user@example.com",
        "username": "username",
        "role": "user",
        "status": "active",
        "created_at": "2023-01-01T12:00:00Z",
        "last_login_at": "2023-12-01T10:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 1000,
      "last_page": 50
    }
  },
  "timestamp": 1640995200
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Error Handling -->
            <section id="error-handling" class="mb-12">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-exclamation-triangle text-blue-600 mr-2"></i>
                            Error Handling
                        </h2>

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Standard Error Response</h3>
                            <p class="text-gray-600 mb-4">All API errors follow a consistent format:</p>
                            <pre><code class="language-json">{
  "success": false,
  "error": "Error message description",
  "timestamp": 1640995200
}</code></pre>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Common HTTP Status Codes</h3>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <span class="inline-block bg-gray-200 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded mr-3">400</span>
                                    <span class="text-gray-600">Bad Request - Invalid request parameters</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="inline-block bg-gray-200 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded mr-3">401</span>
                                    <span class="text-gray-600">Unauthorized - Invalid or missing authentication</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="inline-block bg-gray-200 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded mr-3">403</span>
                                    <span class="text-gray-600">Forbidden - Insufficient permissions</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="inline-block bg-gray-200 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded mr-3">404</span>
                                    <span class="text-gray-600">Not Found - Resource not found</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="inline-block bg-gray-200 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded mr-3">429</span>
                                    <span class="text-gray-600">Too Many Requests - Rate limit exceeded</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="inline-block bg-gray-200 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded mr-3">500</span>
                                    <span class="text-gray-600">Internal Server Error - Server error</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Rate Limiting</h3>
                            <p class="text-gray-600 mb-4">
                                API requests are rate limited based on user subscription tier. Rate limit headers are included in responses:
                            </p>
                            <div class="bg-gray-100 p-3 rounded-lg font-mono text-sm space-y-1">
                                <div>X-RateLimit-Limit: 1000</div>
                                <div>X-RateLimit-Remaining: 999</div>
                                <div>X-RateLimit-Reset: 1640995800</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SDKs and Libraries -->
            <section id="sdks" class="mb-12">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-code text-blue-600 mr-2"></i>
                            SDKs and Libraries
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                    <i class="fab fa-php text-purple-600 mr-2"></i>
                                    PHP SDK
                                </h3>
                                <p class="text-gray-600 mb-3">Official PHP SDK for IMEI Bot Platform API</p>
                                <div class="flex space-x-2">
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">Documentation</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">GitHub</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">Composer</a>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                    <i class="fab fa-js text-yellow-600 mr-2"></i>
                                    JavaScript SDK
                                </h3>
                                <p class="text-gray-600 mb-3">JavaScript/Node.js SDK for web applications</p>
                                <div class="flex space-x-2">
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">Documentation</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">GitHub</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">NPM</a>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                    <i class="fab fa-python text-blue-600 mr-2"></i>
                                    Python SDK
                                </h3>
                                <p class="text-gray-600 mb-3">Python SDK for backend integration</p>
                                <div class="flex space-x-2">
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">Documentation</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">GitHub</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">PyPI</a>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-mobile-alt text-green-600 mr-2"></i>
                                    Mobile SDKs
                                </h3>
                                <p class="text-gray-600 mb-3">iOS and Android SDKs for mobile apps</p>
                                <div class="flex space-x-2">
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">iOS SDK</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">Android SDK</a>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">React Native</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-gray-500 text-sm">
                <p>&copy; 2023 IMEI Bot Platform. All rights reserved.</p>
                <div class="mt-2 space-x-4">
                    <a href="/terms" class="hover:text-gray-700">Terms of Service</a>
                    <a href="/privacy" class="hover:text-gray-700">Privacy Policy</a>
                    <a href="/support" class="hover:text-gray-700">Support</a>
                    <a href="mailto:api@imei-bot.com" class="hover:text-gray-700">api@imei-bot.com</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleUserMenu') === -1) {
                menu.classList.add('hidden');
            }
        });

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

        // Highlight current section in navigation
        function highlightCurrentSection() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('a[href^="#"]');
            
            let currentSection = '';
            sections.forEach(section => {
                const rect = section.getBoundingClientRect();
                if (rect.top <= 100 && rect.bottom >= 100) {
                    currentSection = section.id;
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('bg-blue-50', 'border-blue-500');
                if (link.getAttribute('href') === '#' + currentSection) {
                    link.classList.add('bg-blue-50', 'border-blue-500');
                }
            });
        }

        window.addEventListener('scroll', highlightCurrentSection);
        highlightCurrentSection(); // Initial call
    </script>
</body>
</html>
