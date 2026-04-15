<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use IMEIBot\Core\Database;
use IMEIBot\Core\Logger;
use IMEIBot\Models\File;
use IMEIBot\Models\Brand;
use IMEIBot\Models\Model;

// Initialize services
$db = Database::getInstance(
    require __DIR__ . '/../../config/database.php',
    new Logger(require __DIR__ . '/../../config/monitoring.php')
);
$logger = new Logger(require __DIR__ . '/../../config/monitoring.php');

// Check authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['name', 'display_name', 'category', 'file_type'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new \InvalidArgumentException("Field '$field' is required");
            }
        }

        // Handle file upload
        $fileData = null;
        $uploadMethod = $_POST['upload_method'] ?? 'local';

        if ($uploadMethod === 'local') {
            // Local file upload
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new \InvalidArgumentException("Please select a file to upload");
            }

            $uploadedFile = $_FILES['file'];
            
            // Validate file
            $allowedTypes = [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip',
                'application/octet-stream',
                'application/img',
                'application/x-img',
                'application/bin',
                'application/x-bin',
                'application/x-certificate',
                'application/x-msdownload',
                'application/vnd.android.package-archive',
                'application/x-apple-diskimage'
            ];

            $allowedExtensions = ['zip', 'rar', '7z', 'tar', 'gz', 'img', 'bin', 'cert', 'exe', 'apk', 'ipsw'];
            
            if (!in_array($uploadedFile['type'], $allowedTypes)) {
                throw new \InvalidArgumentException("Invalid file type");
            }

            $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                throw new \InvalidArgumentException("Invalid file extension");
            }

            if ($uploadedFile['size'] > 104857600) { // 100MB
                throw new \InvalidArgumentException("File size too large (max 100MB)");
            }

            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../uploads/files/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $filename = uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($uploadedFile['tmp_name'], $filepath)) {
                throw new \RuntimeException("Failed to move uploaded file");
            }

            // Generate file hashes
            $md5Hash = md5_file($filepath);
            $sha256Hash = hash_file('sha256', $filepath);

            $fileData = [
                'file_name' => $filename,
                'original_name' => $uploadedFile['name'],
                'file_size_bytes' => $uploadedFile['size'],
                'file_size_mb' => round($uploadedFile['size'] / 1024 / 1024, 2),
                'file_type' => $uploadedFile['type'],
                'file_path' => $filepath,
                'file_hash_md5' => $md5Hash,
                'file_hash_sha256' => $sha256Hash
            ];

        } elseif ($uploadMethod === 'google_drive') {
            // Google Drive upload
            $googleDriveData = json_decode($_POST['google_drive_data'] ?? '{}', true);
            
            if (empty($googleDriveData['id']) || empty($googleDriveData['name'])) {
                throw new \InvalidArgumentException("Invalid Google Drive data");
            }

            $fileData = [
                'file_name' => $googleDriveData['id'],
                'original_name' => $googleDriveData['name'],
                'file_size_bytes' => $googleDriveData['size'] ?? 0,
                'file_size_mb' => round(($googleDriveData['size'] ?? 0) / 1024 / 1024, 2),
                'file_type' => $googleDriveData['mimeType'] ?? 'application/octet-stream',
                'file_path' => $googleDriveData['webViewLink'] ?? '',
                'file_url' => $googleDriveData['webViewLink'] ?? '',
                'file_hash_md5' => $googleDriveData['md5Checksum'] ?? null,
                'file_hash_sha256' => null
            ];
        }

        // Prepare file record data
        $fileRecord = [
            'uuid' => Ramsey\Uuid\Uuid::uuid4()->toString(),
            'brand_id' => !empty($_POST['brand_id']) ? (int) $_POST['brand_id'] : null,
            'model_id' => !empty($_POST['model_id']) ? (int) $_POST['model_id'] : null,
            'category' => $_POST['category'],
            'subcategory' => $_POST['subcategory'] ?? null,
            'name' => $_POST['name'],
            'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name']))),
            'display_name' => $_POST['display_name'],
            'description' => $_POST['description'] ?? null,
            'version' => $_POST['version'] ?? null,
            'build_number' => $_POST['build_number'] ?? null,
            'android_version' => $_POST['android_version'] ?? null,
            'security_patch' => $_POST['security_patch'] ?? null,
            'region' => $_POST['region'] ?? null,
            'carrier' => $_POST['carrier'] ?? null,
            'network_lock' => $_POST['network_lock'] ?? 'unlocked',
            'is_featured' => isset($_POST['is_featured']),
            'is_premium' => isset($_POST['is_premium']),
            'is_verified' => isset($_POST['is_verified']),
            'is_active' => isset($_POST['is_active']),
            'is_virus_scanned' => false,
            'download_count' => 0,
            'view_count' => 0,
            'rating' => null,
            'rating_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Merge file data
        $fileRecord = array_merge($fileRecord, $fileData);

        // Create file record
        $file = new File($fileRecord);
        $file->save();

        $_SESSION['success'] = 'File uploaded successfully';
        header('Location: files.php');
        exit;

    } catch (\Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: file-upload.php');
        exit;
    }
}

// Get brands and models for dropdowns
$brands = Brand::getAll();
$models = Model::getPopular(100); // Get up to 100 popular models

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File - IMEI Bot Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://apis.google.com/js/api.js"></script>
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
                        <a href="files.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Files
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Upload New File</h3>
                    
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <!-- Upload Method Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Method</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="upload_method" value="local" checked class="mr-2" onchange="toggleUploadMethod('local')">
                                    <span class="text-sm text-gray-700">Local File</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="upload_method" value="google_drive" class="mr-2" onchange="toggleUploadMethod('google_drive')">
                                    <span class="text-sm text-gray-700">Google Drive</span>
                                </label>
                            </div>
                        </div>

                        <!-- Local File Upload -->
                        <div id="localUpload" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload a file</span>
                                            <input id="file" name="file" type="file" class="sr-only" accept=".zip,.rar,.7z,.tar,.gz,.img,.bin,.cert,.exe,.apk,.ipsw" onchange="handleFileSelect(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">ZIP, RAR, 7Z, TAR, GZ, IMG, BIN, CERT, EXE, APK, IPSW up to 100MB</p>
                                </div>
                                <div id="fileInfo" class="hidden mt-4 p-4 bg-gray-50 rounded-md">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fas fa-file text-blue-500 mr-2"></i>
                                            <span id="fileName" class="text-sm text-gray-700"></span>
                                        </div>
                                        <span id="fileSize" class="text-sm text-gray-500"></span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div id="uploadProgress" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Google Drive Upload -->
                        <div id="googleDriveUpload" class="mb-6 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select from Google Drive</label>
                            <button type="button" onclick="openGoogleDrivePicker()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fab fa-google-drive mr-2"></i>
                                Open Google Drive Picker
                            </button>
                            <div id="googleDriveInfo" class="hidden mt-4 p-4 bg-gray-50 rounded-md">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fab fa-google-drive text-green-500 mr-2"></i>
                                        <span id="googleFileName" class="text-sm text-gray-700"></span>
                                    </div>
                                    <span id="googleFileSize" class="text-sm text-gray-500"></span>
                                </div>
                                <input type="hidden" name="google_drive_data" id="googleDriveData">
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Internal Name *</label>
                                <input type="text" id="name" name="name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">Internal identifier (no spaces)</p>
                            </div>
                            <div>
                                <label for="display_name" class="block text-sm font-medium text-gray-700">Display Name *</label>
                                <input type="text" id="display_name" name="display_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">User-friendly name</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700">Category *</label>
                                <select id="category" name="category" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Select Category</option>
                                    <option value="firmware">Firmware</option>
                                    <option value="qcn">QCN</option>
                                    <option value="frp">FRP</option>
                                    <option value="modem">Modem</option>
                                    <option value="unlock">Unlock</option>
                                    <option value="tools">Tools</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label for="subcategory" class="block text-sm font-medium text-gray-700">Subcategory</label>
                                <input type="text" id="subcategory" name="subcategory" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <!-- Device Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="brand_id" class="block text-sm font-medium text-gray-700">Brand</label>
                                <select id="brand_id" name="brand_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Select Brand</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand->id; ?>"><?php echo htmlspecialchars($brand->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="model_id" class="block text-sm font-medium text-gray-700">Model</label>
                                <select id="model_id" name="model_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">Select Model</option>
                                    <?php foreach ($models as $model): ?>
                                        <option value="<?php echo $model->id; ?>"><?php echo htmlspecialchars($model->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Version Information -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label for="version" class="block text-sm font-medium text-gray-700">Version</label>
                                <input type="text" id="version" name="version" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="build_number" class="block text-sm font-medium text-gray-700">Build Number</label>
                                <input type="text" id="build_number" name="build_number" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="android_version" class="block text-sm font-medium text-gray-700">Android Version</label>
                                <input type="text" id="android_version" name="android_version" placeholder="e.g., 12, 13" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <!-- Regional Information -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label for="region" class="block text-sm font-medium text-gray-700">Region</label>
                                <input type="text" id="region" name="region" placeholder="e.g., Global, EU, US" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="carrier" class="block text-sm font-medium text-gray-700">Carrier</label>
                                <input type="text" id="carrier" name="carrier" placeholder="e.g., Unlocked, AT&T, T-Mobile" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="network_lock" class="block text-sm font-medium text-gray-700">Network Lock</label>
                                <select id="network_lock" name="network_lock" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="unlocked">Unlocked</option>
                                    <option value="carrier">Carrier Locked</option>
                                    <option value="region">Region Locked</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>

                        <!-- File Options -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_featured" class="mr-2">
                                    <span class="text-sm text-gray-700">Featured</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_premium" class="mr-2">
                                    <span class="text-sm text-gray-700">Premium</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_verified" class="mr-2">
                                    <span class="text-sm text-gray-700">Verified</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" checked class="mr-2">
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-upload mr-2"></i>
                                Upload File
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Google Drive API configuration
        const API_KEY = 'YOUR_GOOGLE_API_KEY';
        const CLIENT_ID = 'YOUR_GOOGLE_CLIENT_ID';
        const SCOPE = 'https://www.googleapis.com/auth/drive.readonly';

        let googleDrivePicker;

        function toggleUploadMethod(method) {
            const localUpload = document.getElementById('localUpload');
            const googleDriveUpload = document.getElementById('googleDriveUpload');
            
            if (method === 'local') {
                localUpload.classList.remove('hidden');
                googleDriveUpload.classList.add('hidden');
            } else {
                localUpload.classList.add('hidden');
                googleDriveUpload.classList.remove('hidden');
            }
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (file) {
                const fileInfo = document.getElementById('fileInfo');
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.classList.remove('hidden');
                
                // Auto-fill display name if empty
                const displayName = document.getElementById('display_name');
                if (!displayName.value) {
                    displayName.value = file.name.replace(/\.[^/.]+$/, '');
                }
                
                // Auto-fill internal name if empty
                const internalName = document.getElementById('name');
                if (!internalName.value) {
                    internalName.value = file.name.replace(/\.[^/.]+$/, '').toLowerCase().replace(/[^a-z0-9]/g, '_');
                }
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function openGoogleDrivePicker() {
            // Load Google Drive API
            gapi.load('picker', function() {
                const picker = new google.picker.PickerBuilder()
                    .addView(google.picker.ViewId.DOCS)
                    .setOAuthToken('YOUR_ACCESS_TOKEN') // You'll need to implement OAuth flow
                    .setDeveloperKey(API_KEY)
                    .setCallback(pickerCallback)
                    .build();
                
                picker.setVisible(true);
            });
        }

        function pickerCallback(data) {
            if (data.action === google.picker.Action.PICKED) {
                const doc = data.docs[0];
                const googleDriveInfo = document.getElementById('googleDriveInfo');
                const googleFileName = document.getElementById('googleFileName');
                const googleFileSize = document.getElementById('googleFileSize');
                const googleDriveData = document.getElementById('googleDriveData');
                
                googleFileName.textContent = doc.name;
                googleFileSize.textContent = formatFileSize(doc.sizeBytes || 0);
                googleDriveInfo.classList.remove('hidden');
                
                // Store Google Drive data
                googleDriveData.value = JSON.stringify({
                    id: doc.id,
                    name: doc.name,
                    size: doc.sizeBytes,
                    mimeType: doc.mimeType,
                    webViewLink: doc.url,
                    md5Checksum: doc.md5Checksum
                });
                
                // Auto-fill form fields
                const displayName = document.getElementById('display_name');
                const internalName = document.getElementById('name');
                
                if (!displayName.value) {
                    displayName.value = doc.name.replace(/\.[^/.]+$/, '');
                }
                
                if (!internalName.value) {
                    internalName.value = doc.name.replace(/\.[^/.]+$/, '').toLowerCase().replace(/[^a-z0-9]/g, '_');
                }
            }
        }

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });
        
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('file').files = files;
                handleFileSelect(document.getElementById('file'));
            }
        });

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', (e) => {
            const uploadMethod = document.querySelector('input[name="upload_method"]:checked').value;
            
            if (uploadMethod === 'local') {
                const fileInput = document.getElementById('file');
                if (!fileInput.files || fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Please select a file to upload');
                    return;
                }
            } else if (uploadMethod === 'google_drive') {
                const googleDriveData = document.getElementById('googleDriveData').value;
                if (!googleDriveData) {
                    e.preventDefault();
                    alert('Please select a file from Google Drive');
                    return;
                }
            }
        });
    </script>
</body>
</html>
