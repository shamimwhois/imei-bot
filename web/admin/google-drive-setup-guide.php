<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Drive API Setup Guide - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">IMEI Bot Platform</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="file-upload.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Upload
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Google Drive API Setup Guide</h3>
                    
                    <div class="space-y-8">
                        <!-- Overview -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <i class="fab fa-google-drive mr-2"></i>Overview
                            </h4>
                            <p class="text-sm text-gray-600 mb-4">
                                To enable Google Drive file picker functionality, you need to configure the Google Drive API with proper credentials. This guide will walk you through the setup process.
                            </p>
                        </section>

                        <!-- Step 1: Google Cloud Console -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-xs rounded-full mr-2">1</span>
                                Create Google Cloud Project
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-600 hover:text-blue-800">Google Cloud Console</a></li>
                                    <li>Sign in with your Google account</li>
                                    <li>Click on the project dropdown and select "NEW PROJECT"</li>
                                    <li>Enter a project name (e.g., "IMEI Bot Platform")</li>
                                    <li>Click "CREATE" to create the project</li>
                                </ol>
                            </div>
                        </section>

                        <!-- Step 2: Enable APIs -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-xs rounded-full mr-2">2</span>
                                Enable Required APIs
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                                    <li>In your project, go to "APIs & Services" > "Library"</li>
                                    <li>Search for "Google Drive API" and click on it</li>
                                    <li>Click "ENABLE" to enable the Google Drive API</li>
                                    <li>Search for "Google Picker API" and click on it</li>
                                    <li>Click "ENABLE" to enable the Google Picker API</li>
                                </ol>
                            </div>
                        </section>

                        <!-- Step 3: Create OAuth Credentials -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-xs rounded-full mr-2">3</span>
                                Create OAuth 2.0 Credentials
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                                    <li>Go to "APIs & Services" > "Credentials"</li>
                                    <li>Click "+ CREATE CREDENTIALS" and select "OAuth 2.0 Client IDs"</li>
                                    <li>Configure the OAuth consent screen if prompted:
                                        <ul class="list-disc list-inside ml-6 mt-2">
                                            <li>Choose "External" for user type</li>
                                            <li>Fill in required fields (app name, user support email)</li>
                                            <li>Add developer contact information</li>
                                            <li>Add scopes: <code>https://www.googleapis.com/auth/drive.readonly</code></li>
                                            <li>Add test users (your Google account)</li>
                                            <li>Go through verification steps (can use test mode initially)</li>
                                        </ul>
                                    </li>
                                    <li>Create OAuth 2.0 Client ID:
                                        <ul class="list-disc list-inside ml-6 mt-2">
                                            <li>Select "Web application" as application type</li>
                                            <li>Add authorized JavaScript origins: <code>http://localhost:8000</code>, <code>http://your-domain.com</code></li>
                                            <li>Add authorized redirect URIs (if needed)</li>
                                            <li>Click "CREATE"</li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>
                        </section>

                        <!-- Step 4: Create API Key -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-xs rounded-full mr-2">4</span>
                                Create API Key
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                                    <li>In the Credentials page, click "+ CREATE CREDENTIALS"</li>
                                    <li>Select "API Key"</li>
                                    <li>Copy the generated API key</li>
                                    <li>Optionally, restrict the API key for security:
                                        <ul class="list-disc list-inside ml-6 mt-2">
                                            <li>Click on the API key to edit it</li>
                                            <li>Under "Application restrictions", select "HTTP referrers"</li>
                                            <li>Add your website domains</li>
                                            <li>Under "API restrictions", select "Restrict key"</li>
                                            <li>Select "Google Drive API" and "Google Picker API"</li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>
                        </section>

                        <!-- Step 5: Update Configuration -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-xs rounded-full mr-2">5</span>
                                Update File Upload Configuration
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <p class="text-sm text-gray-700 mb-3">Update the following constants in <code>web/admin/file-upload.php</code>:</p>
                                <div class="bg-gray-900 text-gray-100 p-4 rounded-md font-mono text-sm">
                                    <pre>const API_KEY = 'YOUR_GOOGLE_API_KEY';
const CLIENT_ID = 'YOUR_GOOGLE_CLIENT_ID';
const SCOPE = 'https://www.googleapis.com/auth/drive.readonly';</pre>
                                </div>
                                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <p class="text-sm text-yellow-800">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <strong>Security Note:</strong> Store these credentials securely and never commit them to version control.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Step 6: Test Integration -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-xs rounded-full mr-2">6</span>
                                Test the Integration
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                                    <li>Navigate to the file upload page</li>
                                    <li>Select "Google Drive" as the upload method</li>
                                    <li>Click "Open Google Drive Picker"</li>
                                    <li>Sign in with your Google account (if prompted)</li>
                                    <li>Select a file from your Google Drive</li>
                                    <li>Verify that file information is populated correctly</li>
                                    <li>Complete the upload process</li>
                                </ol>
                            </div>
                        </section>

                        <!-- Troubleshooting -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <i class="fas fa-wrench mr-2"></i>Troubleshooting
                            </h4>
                            <div class="space-y-4">
                                <div class="bg-red-50 border border-red-200 p-4 rounded-md">
                                    <h5 class="text-sm font-medium text-red-800 mb-2">Common Issues</h5>
                                    <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                                        <li><strong>API not enabled:</strong> Ensure both Google Drive API and Google Picker API are enabled</li>
                                        <li><strong>OAuth consent screen:</strong> Make sure the consent screen is configured and published</li>
                                        <li><strong>Authorized origins:</strong> Add your domain to authorized JavaScript origins</li>
                                        <li><strong>API key restrictions:</strong> Check if API key has proper restrictions</li>
                                        <li><strong>CORS issues:</strong> Verify that your domain is properly configured</li>
                                    </ul>
                                </div>
                                
                                <div class="bg-blue-50 border border-blue-200 p-4 rounded-md">
                                    <h5 class="text-sm font-medium text-blue-800 mb-2">Debug Tips</h5>
                                    <ul class="list-disc list-inside space-y-1 text-sm text-blue-700">
                                        <li>Check browser console for JavaScript errors</li>
                                        <li>Verify API key and client ID are correctly set</li>
                                        <li>Test with incognito mode to avoid cache issues</li>
                                        <li>Use Google's OAuth 2.0 Playground for testing</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <!-- Security Considerations -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <i class="fas fa-shield-alt mr-2"></i>Security Considerations
                            </h4>
                            <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-md">
                                <ul class="list-disc list-inside space-y-1 text-sm text-yellow-800">
                                    <li>Never expose API keys or client secrets in client-side code</li>
                                    <li>Use environment variables for sensitive credentials</li>
                                    <li>Restrict API keys to specific domains and APIs</li>
                                    <li>Implement proper error handling for API failures</li>
                                    <li>Regularly rotate API keys and client secrets</li>
                                    <li>Monitor API usage for unusual activity</li>
                                </ul>
                            </div>
                        </section>

                        <!-- Alternative Solutions -->
                        <section>
                            <h4 class="text-md font-medium text-gray-900 mb-3">
                                <i class="fas fa-lightbulb mr-2"></i>Alternative Solutions
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <p class="text-sm text-gray-700 mb-3">If Google Drive integration is complex, consider these alternatives:</p>
                                <ul class="list-disc list-inside space-y-1 text-sm text-gray-700">
                                    <li><strong>Dropbox API:</strong> Similar file picker functionality</li>
                                    <li><strong>OneDrive API:</strong> Microsoft's cloud storage solution</li>
                                    <li><strong>Direct URL upload:</strong> Allow users to paste file URLs</li>
                                    <li><strong>Enhanced local upload:</strong> Improve local file upload experience</li>
                                </ul>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
