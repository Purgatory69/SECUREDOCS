<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Securedocs</title>
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4285f4',
                        'primary-dark': '#3367d6',
                        secondary: '#34a853',
                        accent: '#fbbc05',
                        danger: '#ea4335',
                        'text-main': '#202124',
                        'text-secondary': '#5f6368',
                        'bg-light': '#f8f9fa',
                        'border-color': '#dadce0',
                    }
                }
            }
        }
    </script>
    
</head>
<body class="h-screen grid grid-rows-[64px_1fr] grid-cols-[260px_1fr] grid-areas-layout text-text-main">
    
    <script>
        window.userId = @json(auth()->id()); 
        window.supabaseUrl = "{{ config('services.supabase.url') }}";
        window.supabaseKey = "{{ config('services.supabase.key') }}";
    </script>
    
    <header class="col-span-2 flex items-center px-4 bg-white border-b border-border-color z-10">
        <div class="flex items-center mr-10">
            <div class="w-8 h-8 bg-primary rounded-lg mr-3 flex items-center justify-center text-white font-bold text-lg">S</div>
            <div class="text-xl font-medium text-text-main">Securedocs</div>
        </div>
        
        <div class="flex-grow max-w-[720px] relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-text-secondary">🔍</span>
            <input type="text" placeholder="Search with AI-powered search" class="w-full py-3 pl-12 pr-4 rounded-lg border-none bg-bg-light text-base focus:outline-none focus:shadow-md">
        </div>
        
        <div class="flex items-center ml-auto gap-4">
            <div class="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer hover:bg-bg-light">🔔</div>
            <div class="relative inline-block">
                <div id="userProfileBtn" class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center text-base cursor-pointer z-20">U</div>
                <div id="profileDropdown" class="absolute top-[54px] right-0 w-[280px] bg-white rounded-lg shadow-lg z-10 overflow-hidden transition-all duration-300 opacity-0 invisible translate-y-[-10px]">
                    <div class="p-4 border-b border-border-color flex items-center">
                        <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center text-xl mr-4">U</div>
                        <div class="flex-1">
                            <div class="text-base font-medium mb-1">{{ Auth::user()->name }}</div>
                            <div class="text-sm text-text-secondary">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <ul class="list-none">
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">👤</span>
                            <span class="text-sm">Profile Settings</span>
                        </li>
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">🔒</span>
                            <span class="text-sm">Security & Privacy</span>
                        </li>
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">🔑</span>
                            <span class="text-sm">Encryption Keys</span>
                        </li>
                        <li class="h-px bg-border-color my-1"></li>
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">⚙️</span>
                            <span class="text-sm">Preferences</span>
                        </li>
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">🌙</span>
                            <span class="text-sm">Dark Mode</span>
                        </li>
                        <li class="h-px bg-border-color my-1"></li>
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">❓</span>
                            <span class="text-sm">Help & Support</span>
                        </li>
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">📝</span>
                            <span class="text-sm">Send Feedback</span>
                        </li>
                    </ul>
                    <div class="p-3 border-t border-border-color text-center">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="py-2 px-4 bg-bg-light border border-border-color rounded text-sm cursor-pointer hover:bg-gray-200">Sign Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div id="overlay" class="fixed inset-0 bg-transparent z-[5] hidden"></div>
    
    <!-- Upload File Modal -->
    <div id="uploadModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" id="modalBackdrop"></div>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative z-10 transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-medium text-text-main">Upload New File</h3>
                <button id="closeModalBtn" class="text-text-secondary hover:text-gray-700 text-2xl focus:outline-none">
                    &times;
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="space-y-6">
                <!-- Upload Area -->
                <div id="dropZone" class="border-2 border-dashed border-border-color rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors">
                    <div class="flex flex-col items-center">
                        <div class="text-3xl mb-3">📄</div>
                        <p class="mb-2 text-sm">Drag and drop files here or click to browse</p>
                        <p class="text-xs text-text-secondary">Maximum file size: 100MB</p>
                    </div>
                    <input type="file" id="fileInput" class="hidden" multiple>
                </div>
                
                <!-- Selected Files List -->
                <div id="fileList" class="space-y-2 max-h-40 overflow-y-auto hidden">
                    <div class="text-sm font-medium">Selected Files:</div>
                </div>
                
                <!-- Security Options -->
                <div class="space-y-3">
                    <div class="text-sm font-medium">Security Options:</div>
                    <div class="flex items-center">
                        <input type="checkbox" id="encryptFiles" class="mr-2">
                        <label for="encryptFiles" class="text-sm">Encrypt files</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="blockchainVerify" class="mr-2">
                        <label for="blockchainVerify" class="text-sm">Add blockchain verification</label>
                    </div>
                </div>
                
                <!-- Progress Bar (Initially Hidden) -->
                <div id="uploadProgress" class="hidden">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Uploading...</span>
                        <span id="progressPercentage">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progressBar" class="bg-primary h-2 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="mt-8 flex justify-end gap-3">
                <button id="cancelUploadBtn" class="py-2 px-4 border border-border-color rounded text-sm bg-white hover:bg-bg-light transition-colors">
                    Cancel
                </button>
                <button id="uploadBtn" class="py-2 px-4 bg-primary text-white rounded text-sm hover:bg-primary-dark transition-colors" disabled>
                    Upload
                </button>
            </div>
        </div>
    </div>
    
    <div class="bg-white border-r border-border-color py-4 overflow-y-auto">
        <div id="newBtn" class="flex items-center mx-4 my-2 mb-4 py-3 px-6 bg-white border border-border-color rounded-3xl shadow-sm cursor-pointer transition-shadow hover:shadow-md">
            <span class="mr-3 text-2xl text-primary">+</span>
            <span class="text-base font-medium">New</span>
        </div>
        
        <ul class="mt-4">
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 bg-[#e8f0fe] text-primary">
                <span class="mr-4 text-lg w-6 text-center">📄</span>
                <span>My Documents</span>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                <span class="mr-4 text-lg w-6 text-center">🔄</span>
                <span>Shared with Me</span>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                <span class="mr-4 text-lg w-6 text-center">⭐</span>
                <span>Starred</span>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                <span class="mr-4 text-lg w-6 text-center">🔒</span>
                <span>Secure Vault</span>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                <span class="mr-4 text-lg w-6 text-center">⏱️</span>
                <span>Recent</span>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                <span class="mr-4 text-lg w-6 text-center">🔗</span>
                <span>Blockchain Verified</span>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                <span class="mr-4 text-lg w-6 text-center">🗑️</span>
                <span>Trash</span>
            </li>
        </ul>
        
        <div class="mt-8 px-6">
            <div class="w-full h-1 bg-gray-200 rounded overflow-hidden">
                <div class="h-full w-[35%] bg-primary"></div>
            </div>
            <div class="text-xs text-text-secondary mt-2">3.5 GB of 10 GB used</div>
        </div>
    </div>

    <main class="bg-white p-6 overflow-y-auto">
        <h1 class="text-2xl font-normal mb-6">My Documents</h1>
        
        <div class="flex items-center mb-6 gap-4 flex-wrap">
            <button class="py-2 px-4 border border-border-color rounded-3xl text-sm bg-[#e8f0fe] text-primary border-[#c6dafc]">All Documents</button>
            <button class="py-2 px-4 border border-border-color rounded-3xl text-sm bg-white text-text-secondary hover:bg-bg-light">PDF Files</button>
            <button class="py-2 px-4 border border-border-color rounded-3xl text-sm bg-white text-text-secondary hover:bg-bg-light">Word Documents</button>
            <button class="py-2 px-4 border border-border-color rounded-3xl text-sm bg-white text-text-secondary hover:bg-bg-light">Images</button>
            <button class="py-2 px-4 border border-border-color rounded-3xl text-sm bg-white text-text-secondary hover:bg-bg-light">Spreadsheets</button>
            
            <div class="ml-auto flex gap-2">
                <button class="py-2 px-4 border border-border-color rounded text-sm bg-white text-text-secondary hover:bg-bg-light">
                    <span>📊</span>
                </button>
                <button class="py-2 px-4 border border-border-color rounded text-sm bg-[#e8f0fe] text-primary border-[#c6dafc]">
                    <span>📑</span>
                </button>
            </div>
        </div>
        
        <!-- User's Files Container -->
        <div id="filesContainer" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <!-- Files will be loaded here dynamically -->
            <div class="p-4 text-center text-text-secondary col-span-full">Loading files...</div>
        </div>
    </main>
    
    @vite(['resources/js/app.js'])

    <script>
        // Function to load user's files from the database
        function loadUserFiles() {
            console.log('Fetching files from /files endpoint...');
            fetch('/files')
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    const filesContainer = document.getElementById('filesContainer');
                    
                    if (!filesContainer) {
                        console.error('Files container not found');
                        return;
                    }
                    
                    // Clear existing files
                    filesContainer.innerHTML = '';
                    
                    const files = data.files || [];
                    console.log('Files to display:', files);
                    
                    if (files.length === 0) {
                        filesContainer.innerHTML = '<div class="p-4 text-center text-text-secondary col-span-full">No files uploaded yet.</div>';
                        return;
                    }
                    
                    // Add each file to the container as a card
                    files.forEach(file => {
                        const fileElement = document.createElement('div');
                        fileElement.className = 'border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md';
                        
                        const fileIcon = getFileIcon(file.file_name);
                        const fileDate = new Date(file.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        const fileType = file.file_type ? file.file_type.toUpperCase() : '';
                        
                        // Determine if we should show a special badge based on file type
                        let badgeHtml = '';
                        if (['pdf', 'docx', 'xlsx', 'pptx'].includes(file.file_type?.toLowerCase())) {
                            badgeHtml = `<div class="absolute top-2 right-2 bg-[#e8f0fe] text-primary px-1.5 py-0.5 rounded text-xs font-medium">${fileType}</div>`;
                        }
                        
                        fileElement.innerHTML = `
                            <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                                <span class="text-3xl">${fileIcon}</span>
                                ${badgeHtml}
                            </div>
                            <div class="p-3 relative">
                                <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">${file.file_name}</div>
                                <div class="text-xs text-text-secondary">Modified: ${fileDate}</div>
                                <button class="delete-file-btn absolute top-3 right-3 text-text-secondary hover:text-danger" data-file-id="${file.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        `;
                        
                        filesContainer.appendChild(fileElement);
                    });
                    
                    // Add event listeners for delete buttons
                    document.querySelectorAll('.delete-file-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const fileId = this.getAttribute('data-file-id');
                            if (confirm('Are you sure you want to delete this file?')) {
                                deleteFile(fileId);
                            }
                        });
                    });
                    
                    // Add event listeners for file cards (for future preview functionality)
                    document.querySelectorAll('.border.border-border-color.rounded-lg').forEach(card => {
                        card.addEventListener('click', function(e) {
                            // Prevent clicking if the delete button was clicked
                            if (e.target.closest('.delete-file-btn')) return;
                            
                            // Future functionality: preview or download file
                            console.log('File card clicked');
                        });
                    });
                })
                .catch(error => {
                    console.error('Error loading files:', error);
                });
        }
        
        // Function to delete a file
        function deleteFile(fileId) {
            // First get the file details to know the path in storage
            fetch(`/files/${fileId}`)
                .then(response => response.json())
                .then(async fileData => {
                    console.log('File to delete:', fileData);
                    
                    try {
                        // First delete from Supabase storage
                        console.log('Deleting from Supabase storage:', fileData.file_path);
                        const { error } = await supabase.storage
                            .from('files')
                            .remove([fileData.file_path]);
                            
                        if (error) {
                            console.error('Error deleting from storage:', error);
                            alert('Error deleting file from storage');
                            return;
                        }
                        
                        console.log('File deleted from storage successfully');
                        
                        // Then delete from database
                        return fetch(`/files/${fileId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                    } catch (error) {
                        console.error('Error in deletion process:', error);
                        throw error;
                    }
                })
                .then(response => response ? response.json() : null)
                .then(result => {
                    if (result) {
                        console.log('File record deleted:', result);
                        loadUserFiles(); // Refresh the file list
                    }
                })
                .catch(error => {
                    console.error('Error deleting file:', error);
                    alert('Error deleting file. Please try again.');
                });
        }
        
        // Load files when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadUserFiles();
        });
    </script>
    <script>
        // Initialize Supabase client
        console.log('Initializing Supabase with:', {
            url: window.supabaseUrl,
            key: window.supabaseKey
        });
        // Use the global Supabase object from CDN
        const supabase = window.supabase.createClient(window.supabaseUrl, window.supabaseKey);
        
        // User Profile Dropdown Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userProfileBtn = document.getElementById('userProfileBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const overlay = document.getElementById('overlay');
            
            if (userProfileBtn && profileDropdown && overlay) {
                console.log('Profile dropdown elements found');
                
                userProfileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    profileDropdown.classList.toggle('opacity-0');
                    profileDropdown.classList.toggle('invisible');
                    profileDropdown.classList.toggle('translate-y-[-10px]');
                    overlay.classList.toggle('hidden');
                    console.log('Profile button clicked');
                });

                overlay.addEventListener('click', function() {
                    profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
                    overlay.classList.add('hidden');
                });
            } else {
                console.error('Profile dropdown elements not found:', { 
                    userProfileBtn: !!userProfileBtn, 
                    profileDropdown: !!profileDropdown, 
                    overlay: !!overlay 
                });
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInside = userProfileBtn.contains(event.target) || 
                                 profileDropdown.contains(event.target);
            
            if (!isClickInside && !profileDropdown.classList.contains('invisible')) {
                profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
                overlay.classList.add('hidden');
            }
        });

        // Upload Modal Functionality
        const newBtn = document.getElementById('newBtn');
        const uploadModal = document.getElementById('uploadModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelUploadBtn = document.getElementById('cancelUploadBtn');
        const modalBackdrop = document.getElementById('modalBackdrop');

        // Modal control functions
        function showUploadModal() {
            uploadModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function hideUploadModal() {
            uploadModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            fileInput.value = '';
            uploadProgress.classList.add('hidden');
            uploadBtn.disabled = false;
        }

        // Event listeners
        newBtn.addEventListener('click', showUploadModal);
        closeModalBtn.addEventListener('click', hideUploadModal);
        cancelUploadBtn.addEventListener('click', hideUploadModal);
        modalBackdrop.addEventListener('click', hideUploadModal);

        // Prevent modal closure when clicking inside content
        uploadModal.querySelector('.bg-white').addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // File input change handler
        const fileInput = document.getElementById('fileInput');
        
        // Define the missing handleFileSelect function
        function handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                const fileName = document.getElementById('fileName');
                if (fileName) {
                    fileName.textContent = files[0].name;
                }
                console.log('File selected:', files[0].name);
            }
        }
        
        fileInput.addEventListener('change', handleFileSelect);

        // Modal show/hide functions
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadBtn = document.getElementById('uploadBtn');

        // New button click handler
        newBtn.addEventListener('click', () => {
            uploadModal.classList.remove('hidden');
        });

        // Close modal on backdrop click
        modalBackdrop.addEventListener('click', hideUploadModal);

        // Handle file selection
        const dropZone = document.getElementById('dropZone');
        dropZone.addEventListener('click', function() {
            fileInput.click();
        });

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-primary');
        });

        dropZone.addEventListener('dragleave', function() {
            this.classList.remove('border-primary');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-primary');
            
            if (e.dataTransfer.files.length > 0) {
                handleFiles(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFiles(this.files);
            }
        });

        function handleFiles(files) {
            if (files.length === 0) return;
            
            const fileList = document.getElementById('fileList');
            fileList.classList.remove('hidden');
            fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
            uploadBtn.disabled = false;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2); // in MB
                
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between text-sm p-2 bg-bg-light rounded';
                fileItem.innerHTML = `
                    <div class="flex items-center">
                        <span class="mr-2">${getFileIcon(file.name)}</span>
                        <span class="truncate max-w-[200px]">${file.name}</span>
                    </div>
                    <span class="text-text-secondary text-xs">${fileSize} MB</span>
                `;
                
                fileList.appendChild(fileItem);
            }
        }

        function getFileIcon(fileName) {
            const extension = fileName.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) return '🖼️';
            if (['pdf'].includes(extension)) return '📄';
            if (['doc', 'docx'].includes(extension)) return '📝';
            if (['xls', 'xlsx', 'csv'].includes(extension)) return '📊';
            if (['ppt', 'pptx'].includes(extension)) return '🎬';
            if (['zip', 'rar', '7z'].includes(extension)) return '📦';
            return '📄';
        }

        // Upload button functionality - REAL UPLOAD
        uploadBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            if (fileInput.files.length === 0) {
                alert('Please select a file first');
                return;
            }

            const file = fileInput.files[0];
            const userId = window.userId;
            
            uploadProgress.classList.remove('hidden');
            uploadBtn.disabled = true;
            
            try {
                console.log('Uploading to Supabase storage...');
                
                // First upload to Supabase storage
                const { data, error } = await supabase.storage
                    .from('files')
                    .upload(`user_${userId}/${file.name}`, file, {
                        cacheControl: '3600',
                        upsert: false,
                        onUploadProgress: (event) => {
                            const percent = (event.loaded / event.total) * 100;
                            if (progressBar) progressBar.style.width = `${percent}%`;
                            if (progressPercentage) progressPercentage.textContent = `${Math.round(percent)}%`;
                        }
                    });
                
                if (error) {
                    console.error('Supabase upload error:', error);
                    alert(`Upload failed: ${error.message}`);
                    return;
                }
                
                console.log('Supabase upload successful:', data);
                
                // Then save metadata to Laravel database
                try {
                    const metadataPayload = {
                        file_name: file.name,
                        file_path: `user_${userId}/${file.name}`,
                        file_size: file.size,
                        file_type: file.type,
                        mime_type: file.type
                    };
                    
                    console.log('Saving metadata to Laravel...', metadataPayload);
                    console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').content);
                    
                    const response = await fetch('/files', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(metadataPayload)
                    });
                    
                    const result = await response.json();
                    console.log('Metadata save response:', result);
                    
                    if (!response.ok) throw new Error('Failed to save file metadata');
                    
                    alert('File uploaded successfully!');
                    loadUserFiles();
                } catch (error) {
                    console.error('Metadata save error:', error);
                    alert(`Failed to save file metadata: ${error.message}`);
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert(`An unexpected error occurred: ${error.message}`);
            } finally {
                hideUploadModal();
                uploadProgress.classList.add('hidden');
                uploadBtn.disabled = false;
                fileInput.value = '';
            }
        });
    </script>
    <script>
        // Profile dropdown toggle
        (function() {
            const profileBtn = document.getElementById('userProfileBtn');
            const dropdown = document.getElementById('profileDropdown');
            if (profileBtn && dropdown) {
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isOpen = dropdown.classList.contains('opacity-100');
                    if (isOpen) {
                        dropdown.classList.remove('opacity-100', 'visible', 'translate-y-0');
                        dropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
                    } else {
                        dropdown.classList.remove('opacity-0', 'invisible', '-translate-y-2');
                        dropdown.classList.add('opacity-100', 'visible', 'translate-y-0');
                    }
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target) && !profileBtn.contains(e.target)) {
                        dropdown.classList.remove('opacity-100', 'visible', 'translate-y-0');
                        dropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
                    }
                });
            }
        })();
    </script>

    <!-- <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

        createChat({
            webhookUrl: 'http://localhost:5678/webhook/0a216509-e55c-4a43-8d4a-581dffe09d18/chat',
            container: '#n8n-chat-container' // Specify the container to render the chat in
        });
    </script> -->
</body>
</html>