<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings Tabs Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Settings Tabs Test</h1>
        
        <!-- Settings Tabs -->
        <div class="bg-white rounded-lg shadow">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex">
                    <button onclick="showTab('general')" class="tab-btn active py-2 px-4 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                        General
                    </button>
                    <button onclick="showTab('telegram')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Telegram Bot
                    </button>
                    <button onclick="showTab('whatsapp')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        WhatsApp Bot
                    </button>
                    <button onclick="showTab('limits')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Rate Limits
                    </button>
                    <button onclick="showTab('notifications')" class="tab-btn py-2 px-4 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Notifications
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- General Settings -->
                <div id="general-tab" class="tab-content">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">General Settings</h3>
                    <p class="text-gray-600">Configure general application settings including name, URL, and default language.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Application Name</label>
                            <input type="text" value="IMEI Bot Platform" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Application URL</label>
                            <input type="url" value="https://your-domain.com" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Telegram Bot Settings -->
                <div id="telegram-tab" class="tab-content" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Telegram Bot Settings</h3>
                    <p class="text-gray-600">Configure Telegram bot integration including API token and webhook settings.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bot Token</label>
                            <input type="text" placeholder="Enter Telegram bot token" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Webhook URL</label>
                            <input type="url" placeholder="https://your-domain.com/webhook/telegram" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Bot Settings -->
                <div id="whatsapp-tab" class="tab-content" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">WhatsApp Bot Settings</h3>
                    <p class="text-gray-600">Configure WhatsApp Business API integration settings.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number ID</label>
                            <input type="text" placeholder="Enter WhatsApp phone number ID" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Access Token</label>
                            <input type="text" placeholder="Enter WhatsApp access token" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Rate Limits Settings -->
                <div id="limits-tab" class="tab-content" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Rate Limits Settings</h3>
                    <p class="text-gray-600">Configure rate limits for IMEI generation and file downloads.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Daily IMEI Limit</label>
                            <input type="number" value="10" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Daily Download Limit</label>
                            <input type="number" value="50" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Notifications Settings -->
                <div id="notifications-tab" class="tab-content" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Notifications Settings</h3>
                    <p class="text-gray-600">Configure email notification preferences for various events.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" checked class="mr-2">
                                <span class="text-sm font-medium text-gray-700">Notify on new user registration</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" checked class="mr-2">
                                <span class="text-sm font-medium text-gray-700">Notify on premium upgrades</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" checked class="mr-2">
                                <span class="text-sm font-medium text-gray-700">Notify on system errors</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="mt-6 bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Tab Test Results</h3>
            <div id="test-results" class="text-sm text-gray-600">
                <p>Click on each tab above to test functionality. Results will appear here.</p>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab content
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600', 'active');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.style.display = 'block';
            }
            
            // Add active class to selected tab button
            const selectedBtn = document.querySelector(`[onclick="showTab('${tabName}')"]`);
            if (selectedBtn) {
                selectedBtn.classList.remove('border-transparent', 'text-gray-500');
                selectedBtn.classList.add('border-blue-500', 'text-blue-600', 'active');
            }
            
            // Update test results
            updateTestResults(tabName);
        }
        
        function updateTestResults(tabName) {
            const resultsDiv = document.getElementById('test-results');
            const timestamp = new Date().toLocaleTimeString();
            
            let results = '<div class="space-y-1">';
            results += `<p><strong>Tab Switch Test:</strong></p>`;
            results += `<p>Switched to: <span class="font-medium text-blue-600">${tabName}</span> at ${timestamp}</p>`;
            
            // Check if tab content is visible
            const selectedTab = document.getElementById(tabName + '-tab');
            const isVisible = selectedTab && selectedTab.style.display !== 'none';
            results += `<p>Tab content visible: <span class="${isVisible ? 'text-green-600' : 'text-red-600'}">${isVisible ? 'YES' : 'NO'}</span></p>`;
            
            // Check if tab button is active
            const selectedBtn = document.querySelector(`[onclick="showTab('${tabName}')"]`);
            const isActive = selectedBtn && selectedBtn.classList.contains('border-blue-500');
            results += `<p>Tab button active: <span class="${isActive ? 'text-green-600' : 'text-red-600'}">${isActive ? 'YES' : 'NO'}</span></p>`;
            
            // Check other tabs are hidden
            let otherTabsHidden = true;
            document.querySelectorAll('.tab-content').forEach(tab => {
                if (tab.id !== tabName + '-tab' && tab.style.display !== 'none') {
                    otherTabsHidden = false;
                }
            });
            results += `<p>Other tabs hidden: <span class="${otherTabsHidden ? 'text-green-600' : 'text-red-600'}">${otherTabsHidden ? 'YES' : 'NO'}</span></p>`;
            
            results += '</div>';
            resultsDiv.innerHTML = results;
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Hide all tabs initially
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Show only the general tab by default
            const generalTab = document.getElementById('general-tab');
            if (generalTab) {
                generalTab.style.display = 'block';
            }
            
            // Set general tab button as active
            const generalBtn = document.querySelector('[onclick="showTab(\'general\')"]');
            if (generalBtn) {
                generalBtn.classList.add('border-blue-500', 'text-blue-600', 'active');
                generalBtn.classList.remove('border-transparent', 'text-gray-500');
            }
            
            // Initial test results
            updateTestResults('general');
        });
    </script>
</body>
</html>
