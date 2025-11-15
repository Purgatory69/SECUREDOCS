<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="share-token" content="{{ $share->share_token }}">
    @if(isset($share->expires_at) && $share->expires_at)
    <meta name="share-expires-at" content="{{ $share->expires_at->toIso8601String() }}">
    @endif
    <meta name="share-is-one-time" content="{{ $share->is_one_time ? 'true' : 'false' }}">
    <meta name="share-download-count" content="{{ $share->download_count ?? 0 }}">
    <title>{{ $file->file_name }} - SecureDocs</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css'])
    <style>
        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .share-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .file-icon {
            width: 48px;
            height: 48px;
            background: #f89c00;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: black;
            font-size: 20px;
            font-weight: bold;
        }
        .download-btn {
            background: #667eea;
            transition: all 0.3s ease;
        }
        .download-btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }
        .save-btn {
            background: #22c55e;
            transition: all 0.3s ease;
        }
        .save-btn:hover {
            background: #16a34a;
        }
        .folder-table {
            background: #1f2937;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            border: 1px solid #374151;
        }
        .folder-row:hover {
            background-color: #374151;
        }
        
        /* Breadcrumb styles */
        .breadcrumbs_container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .breadcrumb {
            text-decoration: none;
            padding: 2px 6px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        
        .breadcrumb:hover {
            background-color: rgba(249, 115, 22, 0.1);
        }
        
        .breadCrumbsOptions {
            font-size: 12px;
        }
        
        #myfilesCurrentFolderName {
            font-weight: 500;
        }
    </style>
</head>
<body>
    @if($file->is_folder)
        <!-- Folder View - Dark Theme -->
        <div class="min-h-screen bg-gray-900">
            <!-- Header -->
            <div class="bg-gray-800 text-white p-4">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-orange-500 rounded flex items-center justify-center">
                            <span class="text-white font-bold text-sm">📁</span>
                        </div>
                        <div>
                            <h1 class="text-lg font-semibold">{{ $file->file_name }}</h1>
                            <p class="text-gray-300 text-sm">shared by "{{ trim(($share->user->firstname ?? '') . ' ' . ($share->user->lastname ?? '')) }}"</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="shareFolder()" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-3 py-2 rounded transition-colors" title="Share">
                            📤
                        </button>
                        <button onclick="downloadFolder()" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-3 py-2 rounded transition-colors" title="Download">
                            ⬇️
                        </button>
                        <a href="{{ route('register') }}" class="bg-orange-500 text-white px-4 py-2 rounded text-sm font-medium hover:bg-orange-600 transition-colors">
                            SIGN UP
                        </a>
                        <a href="{{ route('login') }}" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded text-sm transition-colors text-white">
                            LOG IN
                        </a>
                    </div>
                </div>
            </div>

            <!-- Breadcrumb Navigation (SecureDocs Style) -->
            <div id="breadcrumbsContainer" class="mt-2 mb-8 text-sm text-white flex items-center justify-between bg-gray-800 border-b border-gray-700 px-4 py-3">
                
                <!-- Breadcrumbs Dropdown (for collapsed paths) -->
                <div id="breadcrumbsDropdown" class="relative hidden">
                    <button id="breadcrumbsMenuBtn" class="flex items-center justify-center w-12 h-12 rounded-full hover:bg-gray-700 transition-colors mr-2">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"></path>
                        </svg>
                    </button>
                    <div id="breadcrumbsDropdownMenu" class="absolute top-full left-0 mt-1 bg-[#1F2235] border border-[#4A4D6A] rounded-lg shadow-lg z-50 min-w-[200px] hidden">
                        <!-- Dropdown items will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Breadcrumbs Path -->
                <div id="breadcrumbsPath" class="flex items-center">
                    <!-- Breadcrumb items will be populated by JavaScript -->
                </div>

                <!-- View Toggle Buttons -->
                <div id="viewToggleBtns" class="flex gap-2 ml-auto">
                    <button id="btnGridLayout" data-view="grid" title="Grid view" aria-label="Grid view" 
                            class="view-toggle-btn py-2 px-4 border border-gray-600 rounded text-sm text-gray-400 hover:text-orange-400 transition-colors cursor-pointer" aria-pressed="false">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </button>
                    <button id="btnListLayout" data-view="list" title="List view" aria-label="List view" 
                            class="view-toggle-btn active py-2 px-4 border border-gray-600 rounded text-sm text-orange-400 bg-gray-700 hover:text-orange-400 transition-colors cursor-pointer" aria-pressed="true">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- OTP Files Warning -->
            @if(isset($otpInfo) && $otpInfo['has_otp_files'])
            <div class="max-w-7xl mx-auto px-4 mb-4">
                <div class="bg-yellow-900/50 border border-yellow-600 rounded-lg p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-yellow-400 font-medium text-sm mb-1">
                            Some Files Are Not Available
                        </h3>
                        <p class="text-yellow-200 text-sm">
                            This folder contains {{ $otpInfo['otp_count'] }} {{ $otpInfo['otp_count'] === 1 ? 'file' : 'files' }} marked as one-time access that cannot be shared publicly.
                            @if(count($otpInfo['otp_files']) <= 3)
                                <span class="block mt-1 text-yellow-300">
                                    Hidden {{ $otpInfo['otp_count'] === 1 ? 'file' : 'files' }}: {{ implode(', ', $otpInfo['otp_files']) }}
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto p-4">
                <!-- Folder Table -->
                <div class="folder-table bg-gray-800 border border-gray-700">
                    <!-- Table Header -->
                    <div class="bg-gray-700 border-b border-gray-600 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" class="rounded bg-gray-600 border-gray-500">
                                <span class="text-sm font-medium text-gray-200">NAME</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div class="text-sm font-medium text-gray-200">MODIFIED</div>
                        </div>
                    </div>

                    <!-- Files List -->
                    <div class="divide-y divide-gray-600">
                        @forelse($folderFiles as $folderFile)
                            <div class="folder-row px-4 py-3 flex items-center justify-between hover:bg-gray-700 cursor-pointer transition-colors" onclick="openFile('{{ $folderFile->id }}', '{{ $folderFile->file_name }}', {{ $folderFile->is_folder ? 'true' : 'false' }})">
                                <div class="flex items-center space-x-3 flex-1">
                                    <input type="checkbox" class="rounded bg-gray-600 border-gray-500" onclick="event.stopPropagation()">
                                    
                                    <!-- File Icon with Type Badge -->
                                    <div class="relative">
                                        @if($folderFile->is_folder)
                                            <div class="w-8 h-8 flex items-center justify-center">
                                                <span class="text-2xl">📁</span>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 flex items-center justify-center">
                                                <svg viewBox="0 0 35 40" height="35" width="30">
                                                    <path d="M34.28 12.14V37.86C34.28 38.141 34.2246 38.4193 34.1171 38.6789C34.0096 38.9386 33.8519 39.1745 33.6532 39.3732C33.4545 39.5719 33.2186 39.7296 32.9589 39.8371C32.6993 39.9446 32.421 40 32.14 40H2.14C1.85897 40 1.58069 39.9446 1.32106 39.8371C1.06142 39.7296 0.825509 39.5719 0.626791 39.3732C0.428074 39.1745 0.270443 38.9386 0.162898 38.6789C0.0553525 38.4193 0 38.141 0 37.86V2.14C0 1.57244 0.225464 1.02812 0.626791 0.626791C1.02812 0.225464 1.57244 0 2.14 0H22.14C23.4969 0.0774993 24.7874 0.613415 25.8 1.52L32.8 8.52C33.6838 9.52751 34.2048 10.8019 34.28 12.14ZM31.42 14.28H22.14C21.5724 14.28 21.0281 14.0545 20.6268 13.6532C20.2255 13.2519 20 12.7076 20 12.14V2.86H2.85V37.14H31.43V14.29L31.42 14.28ZM22.85 11.42H31.24C31.1355 11.0855 30.9693 10.7734 30.75 10.5L23.75 3.5C23.4825 3.28063 23.1776 3.11126 22.85 3V11.39V11.42Z" fill="#9ca3af"></path>
                                                </svg>
                                            </div>
                                            <!-- File Type Badge -->
                                            <div class="absolute -bottom-1 -right-1 bg-orange-500 text-white text-xs px-1 rounded">
                                                {{ strtoupper($folderFile->file_type ?? 'FILE') }}
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- File Details -->
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-orange-400 hover:text-orange-300 truncate">
                                            {{ $folderFile->file_name }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ $folderFile->download_count ?? 0 }} downloads, {{ $folderFile->file_size ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-2">
                                    <!-- Copy Link Button -->
                                    <button onclick="event.stopPropagation(); copyFileLink('{{ $folderFile->id }}')" 
                                            class="p-2 text-gray-400 hover:text-orange-400 transition-colors" 
                                            title="Copy share link">
                                        <svg viewBox="0 0 20 8" width="14" height="12">
                                            <path d="M1.9 5C1.9 3.29 3.29 1.9 5 1.9H9V0H5C2.24 0 0 2.24 0 5C0 7.76 2.24 10 5 10H9V8.1H5C3.29 8.1 1.9 6.71 1.9 5ZM6 6H14V4H6V6ZM15 0H11V1.9H15C16.71 1.9 18.1 3.29 18.1 5C18.1 6.71 16.71 8.1 15 8.1H11V10H15C17.76 10 20 7.76 20 5C20 2.24 17.76 0 15 0Z" fill="currentColor"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Modified Date -->
                                    <div class="text-sm text-gray-400 min-w-0">
                                        {{ $folderFile->updated_at->format('Y-m-d H:i') }}
                                    </div>
                                    
                                    <!-- Context Menu Button -->
                                    <button onclick="event.stopPropagation(); showFileContextMenu(event, '{{ $folderFile->id }}', '{{ $folderFile->file_name }}', {{ $folderFile->is_folder ? 'true' : 'false' }})" 
                                            class="p-2 text-gray-400 hover:text-orange-400 transition-colors" 
                                            title="More options">
                                        <svg viewBox="0 0 6 16" width="8" height="14">
                                            <path d="M2 4C3.1 4 4 3.1 4 2C4 0.9 3.1 0 2 0C0.9 0 0 0.9 0 2C0 3.1 0.9 4 2 4ZM2 6C0.9 6 0 6.9 0 8C0 9.1 0.9 10 2 10C3.1 10 4 9.1 4 8C4 6.9 3.1 6 2 6ZM2 12C0.9 12 0 12.9 0 14C0 15.1 0.9 16 2 16C3.1 16 4 15.1 4 14C4 12.9 3.1 12 2 12Z" fill="currentColor"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-gray-400">
                                <div class="text-4xl mb-2">📁</div>
                                <p>This folder is empty</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Download Actions -->
                <div class="mt-6 flex justify-center">
                    <a href="{{ route('public.share.download', $share->share_token) }}" 
                       class="bg-orange-500 hover:bg-orange-600 px-8 py-3 text-white font-semibold rounded-lg text-center transition-colors">
                        📥 DOWNLOAD FOLDER (ZIP)
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- File View - SecureDocs Style -->
        <div style="background-color: #1D1D2F;" class="min-h-screen text-white flex flex-col">
            <div class="bg-[#141326] px-6 py-6">
                <div class="flex items-center justify-between w-full">
                    <button id="back-button" style="margin-left: 10px;"
                        class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                        <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
                    </button>
                    <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                        <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                        <h2 class="font-bold text-xl text-[#f89c00] font-['Poppins']">File Sharing</h2>
                    </div>
                    <div class="flex items-center gap-6">
                        <a href="{{ route('login') }}" class="text-sm font-medium transition-all duration-200 hover:text-[#ff9c00]">LOG IN</a>
                        <a href="{{ route('register') }}" class="bg-[#ff9c00] text-black px-4 py-2 rounded-full font-bold transition-all duration-200 hover:brightness-110">SIGN UP</a>
                    </div>
                </div>
            </div>

            <div class="fixed bottom-6 right-6 z-50">
                <div class="relative">
                    <button id="language-toggle" class="bg-[#3c3f58] text-white p-3 rounded-full shadow-lg transition"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                    </button>

                    <div id="language-dropdown" style="background-color: #3c3f58; border: 3px solid #1F1F33" class="absolute bottom-full right-0 mb-2 hidden bg-[#3c3f58] rounded-lg shadow-xl overflow-hidden min-w-[140px]">
                        <a href="{{ route('language.switch', 'en') }}"
                            class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'en' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                            @if(app()->getLocale() != 'en')
                                style="transition: background-color 0.2s;"
                                onmouseover="this.style.backgroundColor='#55597C';"
                                onmouseout="this.style.backgroundColor='';"
                            @endif>
                            <span class="mr-2">🇺🇸</span>
                            English
                        </a>
                        <a href="{{ route('language.switch', 'fil') }}"
                            class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'fil' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                            @if(app()->getLocale() != 'fil')
                                style="transition: background-color 0.2s;"
                                onmouseover="this.style.backgroundColor='#55597C';"
                                onmouseout="this.style.backgroundColor='';"
                            @endif>
                            <span class="mr-2">🇵🇭</span>
                            Filipino
                        </a>
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-6 py-8 flex-1 flex items-center justify-center">
                <div class="bg-[#3C3F58] w-full max-w-lg p-8 mb-4 md:p-12 rounded-2xl">
                    
                    <div class="flex justify-center mb-6">
                        <img src="{{ asset('file.png') }}" alt="File Icon" class="w-12 h-12">
                    </div>

                    <h2 class="text-xl font-semibold text-white text-center mb-8 truncate" title="{{ $file->file_name }}">
                        {{ $file->file_name }}
                    </h2>

                    <div class="space-y-2 text-sm text-gray-300 mb-10">
                        <p>
                            <span class="font-medium text-gray-100">Shared By :</span>
                            {{ $share->user->name }}
                        </p>
                        <p>
                            <span class="font-medium text-gray-100">Share Link Expires in :</span>
                            {{ $share->expires_at ? $share->expires_at->format('M j, Y g:i A') : 'Never' }}
                        </p>
                        @if($share->password_protected)
                            <p>
                                <span class="font-medium text-gray-100">Protection :</span>
                                Password protected
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        
                        <button onclick="saveToMyFiles()" 
                            class="w-full py-3 px-4 bg-[#55597C] hover:brightness-110 text-white font-medium rounded-lg text-center transition-all duration-200">
                            Save to My Files
                        </button>
                        
                        <a href="{{ route('public.share.download', $share->share_token) }}" 
                           class="w-full py-3 px-4 bg-[#f89c00] hover:brightness-110 text-black font-semibold rounded-lg text-center block transition-all duration-200">
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- JavaScript for functionality -->
    <script>
        // Check if share has expired
        function isShareExpired() {
            const expiresAtMeta = document.querySelector('meta[name="share-expires-at"]');
            if (!expiresAtMeta || !expiresAtMeta.content) {
                return false; // No expiration set
            }
            
            const expiryDate = new Date(expiresAtMeta.content);
            return expiryDate < new Date();
        }

        // Check if share is one-time and already used
        function isShareUsed() {
            const isOneTimeMeta = document.querySelector('meta[name="share-is-one-time"]');
            const downloadCountMeta = document.querySelector('meta[name="share-download-count"]');
            
            if (!isOneTimeMeta || isOneTimeMeta.content !== 'true') {
                return false; // Not a one-time link
            }
            
            const downloadCount = parseInt(downloadCountMeta?.content || '0');
            return downloadCount > 0;
        }

        // Redirect to expired page
        function redirectToExpired() {
            const shareToken = document.querySelector('meta[name="share-token"]')?.content;
            window.location.href = `/s/${shareToken}`;
        }

        // Check share validity on page load
        function checkShareValidity() {
            if (isShareExpired()) {
                console.log('Share has expired, redirecting...');
                redirectToExpired();
                return false;
            }
            
            if (isShareUsed()) {
                console.log('One-time share already used, redirecting...');
                redirectToExpired();
                return false;
            }
            
            return true;
        }

        // Intercept all download button clicks
        function interceptDownloadButtons() {
            // Get all download buttons
            const downloadButtons = document.querySelectorAll('a[href*="/download"], button[onclick*="download"]');
            
            downloadButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!checkShareValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }, true); // Use capture phase to intercept early
            });
        }

        // Check validity on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkShareValidity();
            interceptDownloadButtons();
            
            // Check every 30 seconds if still on page
            setInterval(checkShareValidity, 30000);
        });

        // Save to My Files functionality
        async function saveToMyFiles() {
            try {
                const response = await fetch('{{ route("public.share.save", $share->share_token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('File saved to your account successfully!');
                } else {
                    alert(data.message || 'Failed to save file');
                }
            } catch (error) {
                alert('An error occurred while saving the file');
            }
        }

        // Folder header button functions
        function showUpgradeModal() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-8 max-w-md w-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">⬆️</span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Upgrade to Premium</h2>
                        <p class="text-gray-600 mb-6">Get unlimited storage, password protection, and advanced sharing features.</p>
                        <div class="space-y-3">
                            <a href="{{ route('register') }}" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-medium block transition-colors">
                                Sign Up for Premium
                            </a>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 px-6 py-2 rounded-lg">
                                Maybe Later
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function shareFolder() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-8 max-w-md w-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">📤</span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Share This Folder</h2>
                        <p class="text-gray-600 mb-6">Create your own account to share folders and files with others.</p>
                        <div class="space-y-3">
                            <a href="{{ route('register') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium block transition-colors">
                                Sign Up to Share
                            </a>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 px-6 py-2 rounded-lg">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function downloadFolder() {
            window.location.href = '{{ route("public.share.download", $share->share_token) }}';
        }

        let currentView = 'list';
        
        function toggleListView() {
            if (currentView === 'list') return;
            
            currentView = 'list';
            const table = document.querySelector('.folder-table');
            const listBtn = document.getElementById('btnListLayout');
            const gridBtn = document.getElementById('btnGridLayout');
            
            // Update button states
            listBtn.classList.add('active', 'text-orange-400', 'bg-gray-700');
            listBtn.classList.remove('text-gray-400');
            listBtn.setAttribute('aria-pressed', 'true');
            
            gridBtn.classList.remove('active', 'text-orange-400', 'bg-gray-700');
            gridBtn.classList.add('text-gray-400');
            gridBtn.setAttribute('aria-pressed', 'false');
            
            // Show table view
            table.style.display = 'block';
            
            // Hide grid view if it exists
            const gridView = document.querySelector('.folder-grid');
            if (gridView) gridView.style.display = 'none';
        }

        function toggleGridView() {
            if (currentView === 'grid') return;
            
            currentView = 'grid';
            const table = document.querySelector('.folder-table');
            const listBtn = document.getElementById('btnListLayout');
            const gridBtn = document.getElementById('btnGridLayout');
            
            // Update button states
            gridBtn.classList.add('active', 'text-orange-400', 'bg-gray-700');
            gridBtn.classList.remove('text-gray-400');
            gridBtn.setAttribute('aria-pressed', 'true');
            
            listBtn.classList.remove('active', 'text-orange-400', 'bg-gray-700');
            listBtn.classList.add('text-gray-400');
            listBtn.setAttribute('aria-pressed', 'false');
            
            // Create grid view if it doesn't exist
            let gridView = document.querySelector('.folder-grid');
            if (!gridView) {
                gridView = createGridView();
                table.parentNode.appendChild(gridView);
            }
            
            // Toggle views
            table.style.display = 'none';
            gridView.style.display = 'grid';
        }

        function createGridView() {
            const gridView = document.createElement('div');
            gridView.className = 'folder-grid grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-4';
            
            @if(isset($folderFiles) && $folderFiles->count() > 0)
                @foreach($folderFiles as $index => $folderFile)
                    const fileCard{{ $index }} = document.createElement('div');
                    fileCard{{ $index }}.className = 'bg-gray-800 border border-gray-700 rounded-lg p-4 hover:bg-gray-700 transition-colors cursor-pointer';
                    fileCard{{ $index }}.onclick = () => openFile('{{ $folderFile->id }}', '{{ $folderFile->file_name }}', {{ $folderFile->is_folder ? 'true' : 'false' }});
                    fileCard{{ $index }}.innerHTML = `
                        <div class="text-center">
                            <div class="text-4xl mb-2">{{ $folderFile->is_folder ? '📁' : '📄' }}</div>
                            <div class="text-sm font-medium text-orange-400 truncate">{{ $folderFile->file_name }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $folderFile->file_size ?? 'N/A' }}</div>
                        </div>
                    `;
                    gridView.appendChild(fileCard{{ $index }});
                @endforeach
            @else
                gridView.innerHTML = `
                    <div class="col-span-full text-center py-8 text-gray-500">
                        <div class="text-4xl mb-2">📁</div>
                        <p>This folder is empty</p>
                    </div>
                `;
            @endif
            
            return gridView;
        }

        // Initialize list view as active
        document.addEventListener('DOMContentLoaded', function() {
            toggleListView();
            initializeBreadcrumbs();
            
            // Wire up view toggle buttons
            const gridBtn = document.getElementById('btnGridLayout');
            const listBtn = document.getElementById('btnListLayout');
            
            if (gridBtn) {
                gridBtn.addEventListener('click', toggleGridView);
            }
            
            if (listBtn) {
                listBtn.addEventListener('click', toggleListView);
            }
        });

        // File interaction functions
        async function openFile(fileId, fileName, isFolder) {
            try {
                // Get or create individual share token for this file/folder
                const response = await fetch('{{ route("api.get-or-create-share-token") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        file_id: fileId,
                        parent_token: '{{ $share->share_token }}'
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to get share token');
                }

                const data = await response.json();
                
                if (data.success) {
                    // Use the individual share token to access the file/folder
                    const shareUrl = '{{ route("public.share.show", "SHARE_TOKEN") }}'.replace('SHARE_TOKEN', data.share_token);
                    
                    if (isFolder) {
                        // Navigate to the folder
                        window.location.href = shareUrl;
                    } else {
                        // Open file in new tab
                        window.open(shareUrl, '_blank');
                    }
                } else {
                    console.error('Failed to create share token:', data.message);
                    alert('Unable to access this item. Please try again.');
                }
            } catch (error) {
                console.error('Error opening file:', error);
                // Fallback to old method
                if (isFolder) {
                    const folderUrl = '{{ route("public.share.folder.show", [$share->share_token, "FOLDER_ID"]) }}'.replace('FOLDER_ID', fileId);
                    window.location.href = folderUrl;
                } else {
                    const fileUrl = '{{ route("public.share.file.show", [$share->share_token, "FILE_ID"]) }}'.replace('FILE_ID', fileId);
                    window.open(fileUrl, '_blank');
                }
            }
        }

        function showFileModal(fileId, fileName) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-8 max-w-md w-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">📄</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">${fileName}</h2>
                        <p class="text-gray-600 mb-6">What would you like to do with this file?</p>
                        <div class="space-y-3">
                            <button onclick="downloadFile('${fileId}')" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium w-full transition-colors">
                                📥 Download File
                            </button>
                            <button onclick="previewFile('${fileId}', '${fileName}')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium w-full transition-colors">
                                👁️ Preview File
                            </button>
                            <button onclick="saveFileToMyFiles('${fileId}')" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-medium w-full transition-colors">
                                💾 Save to My Files
                            </button>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700 px-6 py-2 rounded-lg">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function showInfoModal(title, message) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-8 max-w-md w-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">ℹ️</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 mb-4">${title}</h2>
                        <p class="text-gray-600 mb-6">${message}</p>
                        <button onclick="this.closest('.fixed').remove()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium">
                            OK
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        async function copyFileLink(fileId) {
            try {
                // Get or create individual share token for this item
                const response = await fetch('/api/get-or-create-share-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        file_id: fileId,
                        parent_token: '{{ $share->share_token }}'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Use the individual share token
                    const shareUrl = `${window.location.origin}/s/${data.share_token}`;
                    
                    navigator.clipboard.writeText(shareUrl).then(() => {
                        showToast('Individual share link copied to clipboard!');
                    }).catch(() => {
                        showToast('Failed to copy link');
                    });
                } else {
                    showToast('Failed to generate share link');
                }
            } catch (error) {
                console.error('Error generating share link:', error);
                showToast('Failed to generate share link');
            }
        }

        function showFileContextMenu(event, fileId, fileName, isFolder) {
            event.preventDefault();
            
            // Remove existing context menu
            const existingMenu = document.querySelector('.context-menu');
            if (existingMenu) existingMenu.remove();
            
            const menu = document.createElement('div');
            menu.className = 'context-menu fixed bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50';
            menu.style.left = event.pageX + 'px';
            menu.style.top = event.pageY + 'px';
            
            menu.innerHTML = `
                <button onclick="downloadFile('${fileId}'); removeContextMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2">
                    <span>📥</span><span>Download</span>
                </button>
                <button onclick="copyFileLink('${fileId}'); removeContextMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2">
                    <span>🔗</span><span>Copy Link</span>
                </button>
                ${!isFolder ? `
                <button onclick="previewFile('${fileId}', '${fileName}'); removeContextMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2">
                    <span>👁️</span><span>Preview</span>
                </button>
                ` : ''}
                <button onclick="saveFileToMyFiles('${fileId}'); removeContextMenu()" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2">
                    <span>💾</span><span>Save to My Files</span>
                </button>
            `;
            
            document.body.appendChild(menu);
            
            // Close menu when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeMenu() {
                    removeContextMenu();
                    document.removeEventListener('click', closeMenu);
                });
            }, 10);
        }

        function removeContextMenu() {
            const menu = document.querySelector('.context-menu');
            if (menu) menu.remove();
        }

        function downloadFile(fileId) {
            // Use individual file download route
            const downloadUrl = '{{ route("public.share.file.download", [$share->share_token, "FILE_ID"]) }}'.replace('FILE_ID', fileId);
            window.location.href = downloadUrl;
        }

        function previewFile(fileId, fileName) {
            // Open file in new tab for preview
            const fileUrl = '{{ route("public.share.file.show", [$share->share_token, "FILE_ID"]) }}'.replace('FILE_ID', fileId);
            window.open(fileUrl, '_blank');
        }

        async function saveFileToMyFiles(fileId) {
            try {
                const response = await fetch('{{ route("public.share.file.save", [$share->share_token, "FILE_ID"]) }}'.replace('FILE_ID', fileId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    showToast('File saved to your account successfully!');
                } else {
                    showToast(data.message || 'Failed to save file');
                }
            } catch (error) {
                showToast('An error occurred while saving the file');
            }
        }

        function shareFolder() {
            // Copy current folder URL to clipboard
            const folderUrl = window.location.href;
            navigator.clipboard.writeText(folderUrl).then(() => {
                showToast('Folder share link copied to clipboard!');
            }).catch(() => {
                showToast('Failed to copy folder link');
            });
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Slide in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Slide out after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // Breadcrumb Navigation System (from SecureDocs)
        function initializeBreadcrumbs() {
            // Build breadcrumbs from PHP data
            const breadcrumbs = @json($breadcrumbs ?? []);
            const shareUserName = "{{ $share->user->name }}";
            
            updateBreadcrumbsDisplay(breadcrumbs, shareUserName);
            
            // Add dropdown toggle functionality
            const menuBtn = document.getElementById('breadcrumbsMenuBtn');
            const dropdownMenu = document.getElementById('breadcrumbsDropdownMenu');
            
            if (menuBtn && dropdownMenu) {
                menuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', () => {
                    dropdownMenu.classList.add('hidden');
                });
            }
            
            // Add breadcrumb click handlers
            const breadcrumbsContainer = document.getElementById('breadcrumbsContainer');
            breadcrumbsContainer?.addEventListener('click', (e) => {
                if (e.target.tagName === 'A' && e.target.dataset.folderId) {
                    e.preventDefault();
                    const folderId = e.target.dataset.folderId;
                    
                    // Handle root navigation
                    if (folderId === 'root') {
                        window.location.href = '{{ route("public.share.show", $share->share_token) }}';
                        return;
                    }
                    
                    // Use the URL from the breadcrumb data if available
                    if (e.target.dataset.url) {
                        window.location.href = e.target.dataset.url;
                    }
                }
            });
        }
        
        function updateBreadcrumbsDisplay(breadcrumbs, shareUserName) {
            const dropdown = document.getElementById('breadcrumbsDropdown');
            const dropdownMenu = document.getElementById('breadcrumbsDropdownMenu');
            const pathContainer = document.getElementById('breadcrumbsPath');
            
            if (!dropdown || !dropdownMenu || !pathContainer) return;
            
            // Clear existing content
            dropdownMenu.innerHTML = '';
            pathContainer.innerHTML = '';
            
            // Create base breadcrumbs (shared root)
            let allBreadcrumbs = [{
                id: 'root',
                name: `"${shareUserName}" Shared Files`,
                url: '{{ route("public.share.show", $share->share_token) }}'
            }];
            
            // Add folder breadcrumbs
            if (breadcrumbs && breadcrumbs.length > 0) {
                breadcrumbs.forEach(crumb => {
                    if (!crumb.is_root) { // Skip root as we already have it
                        allBreadcrumbs.push({
                            id: crumb.id,
                            name: crumb.name,
                            url: crumb.url || '{{ route("public.share.folder.show", [$share->share_token, "FOLDER_ID"]) }}'.replace('FOLDER_ID', crumb.id)
                        });
                    }
                });
            }
            
            // Google Drive-style logic: show dropdown when path is long
            const shouldCollapse = allBreadcrumbs.length > 3;
            
            if (shouldCollapse) {
                // Show dropdown button
                dropdown.classList.remove('hidden');
                
                // Add hidden breadcrumbs to dropdown (all except last 2)
                const hiddenCrumbs = allBreadcrumbs.slice(0, -2);
                hiddenCrumbs.forEach(crumb => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'block px-3 py-2 text-sm text-gray-300 hover:bg-[#2A2D47] rounded';
                    item.textContent = truncateText(crumb.name, 30);
                    item.title = crumb.name;
                    item.dataset.folderId = crumb.id;
                    item.dataset.url = crumb.url;
                    dropdownMenu.appendChild(item);
                });
                
                // Show only last 2 breadcrumbs in main path
                const visibleCrumbs = allBreadcrumbs.slice(-2);
                renderBreadcrumbPath(visibleCrumbs, pathContainer);
            } else {
                // Show all breadcrumbs normally
                dropdown.classList.add('hidden');
                renderBreadcrumbPath(allBreadcrumbs, pathContainer);
            }
        }
        
        function renderBreadcrumbPath(breadcrumbs, container) {
            breadcrumbs.forEach((crumb, index) => {
                if (index > 0) {
                    // Add separator
                    const separator = document.createElement('span');
                    separator.innerHTML = `
                        <svg class="w-3 h-3 mx-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    `;
                    container.appendChild(separator);
                }
                
                // Create breadcrumb link
                const link = document.createElement('a');
                link.href = '#';
                link.dataset.folderId = crumb.id;
                link.dataset.url = crumb.url;
                link.title = crumb.name;
                
                // Style current (last) breadcrumb differently
                if (index === breadcrumbs.length - 1) {
                    link.className = 'px-2 py-1 rounded text-sm transition-colors max-w-[200px] truncate inline-block text-white font-medium bg-[#3C3F58]';
                } else {
                    link.className = 'px-2 py-1 rounded text-sm transition-colors max-w-[200px] truncate inline-block text-gray-400 hover:bg-[#2A2D47]';
                }
                
                link.textContent = truncateText(crumb.name, 25);
                container.appendChild(link);
            });
        }
        
        function navigateToFolder(folderId, folderName) {
            if (folderId === 'root') {
                // Navigate to root
                window.location.href = '{{ route("public.share.show", $share->share_token) }}';
            } else {
                // Navigate to specific folder
                const folderUrl = '{{ route("public.share.folder.show", [$share->share_token, "FOLDER_ID"]) }}'.replace('FOLDER_ID', folderId);
                window.location.href = folderUrl;
            }
        }
        
        function truncateText(text, maxLength) {
            text.substring(0, maxLength) + '...' : text;
        }

        // --- Back Button Script (from new design) ---
        document.addEventListener('DOMContentLoaded', function() {
            const backButton = document.getElementById('back-button');
            if (backButton) {
                backButton.addEventListener('click', function() {
                    // Hide button if there's no history to go back to
                    if (window.history.length <= 1) {
                        backButton.style.display = 'none';
                    }
                    // Check if there's a previous page in history
                    if (document.referrer && document.referrer !== window.location.href) {
                        window.history.back();
                    } else {
                        // Fallback to a safe URL (e.g., home) if no referrer
                        window.location.href = "{{ url('/') }}";
                    }
                });
            }
            
            // --- Language Dropdown Toggle (from new design) ---
            const toggleButton = document.getElementById('language-toggle');
            const dropdown = document.getElementById('language-dropdown');

            if (toggleButton && dropdown) {
                toggleButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdown.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!toggleButton.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>
