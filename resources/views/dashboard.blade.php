<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Securedocs</title>
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
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
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <!-- Document Card 1 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">📄</span>
                    <div class="absolute top-2 right-2 bg-[#e8f0fe] text-primary px-1.5 py-0.5 rounded text-xs font-medium">Secured</div>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Financial Report Q1 2025.pdf</div>
                    <div class="text-xs text-text-secondary">Modified: Apr 18, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 2 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">📝</span>
                    <div class="absolute top-2 right-2 bg-[#e8f0fe] text-primary px-1.5 py-0.5 rounded text-xs font-medium">Blockchain</div>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Contract Agreement.docx</div>
                    <div class="text-xs text-text-secondary">Modified: Apr 15, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 3 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">📊</span>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Sales Analysis.xlsx</div>
                    <div class="text-xs text-text-secondary">Modified: Apr 10, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 4 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">🖼️</span>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Project Mockup.png</div>
                    <div class="text-xs text-text-secondary">Modified: Apr 5, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 5 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">📊</span>
                    <div class="absolute top-2 right-2 bg-[#e8f0fe] text-primary px-1.5 py-0.5 rounded text-xs font-medium">AI-Enhanced</div>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Budget 2025.xlsx</div>
                    <div class="text-xs text-text-secondary">Modified: Apr 2, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 6 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">📝</span>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Meeting Notes.docx</div>
                    <div class="text-xs text-text-secondary">Modified: Mar 28, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 7 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">📄</span>
                    <div class="absolute top-2 right-2 bg-[#e8f0fe] text-primary px-1.5 py-0.5 rounded text-xs font-medium">Blockchain</div>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Legal Document.pdf</div>
                    <div class="text-xs text-text-secondary">Modified: Mar 25, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 8 -->
            <div class="border border-border-color rounded-lg overflow-hidden transition-shadow cursor-pointer hover:shadow-md">
                <div class="h-[120px] bg-bg-light flex items-center justify-center border-b border-border-color relative">
                    <span class="text-3xl">🎬</span>
                </div>
                <div class="p-3">
                    <div class="text-sm whitespace-nowrap overflow-hidden text-ellipsis mb-1">Presentation.pptx</div>
                    <div class="text-xs text-text-secondary">Modified: Mar 20, 2025</div>
                </div>
            </div>
        </div>
    </main>
    <script>
        import { createClient } from '@supabase/supabase-js';

        // Initialize Supabase (replace with your actual keys, loaded securely)
        const supabaseUrl = 'https://fywmgiuvdbsjfchfzixc.supabase.co';
        const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZ5d21naXV2ZGJzamZjaGZ6aXhjIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDUxNjExNzAsImV4cCI6MjA2MDczNzE3MH0.wrupHgjfdyERpLDzD5uP9ZsYNnsUOICuwOTunCGmfG4';
        const supabase = createClient(supabaseUrl, supabaseKey);

        // Assuming you have a way to get the user ID from Laravel and pass it to your JavaScript
        const userId = Auth::user()->id /* Get the user ID from Laravel, e.g., via a data attribute or a global variable */;

        // ... (existing code for modal functionality)

        uploadBtn.addEventListener('click', async function() { // Make the function async
            const files = fileInput.files;
            if (files.length === 0) return;

            // Show progress bar
            uploadProgress.classList.remove('hidden');
            uploadBtn.disabled = true;

            const file = files[0]; // Assuming only one file for simplicity

            try {
                const { data, error } = await supabase
                    .storage
                    .from('files') // Replace with your bucket name
                    .upload(`user_${userId}/${file.name}`, file, { // Use userId variable
                        cacheControl: '3600',
                        upsert: false,
                        onProgress: (event) => {
                            const percent = (event.loaded / event.total) * 100;
                            progressBar.style.width = `${percent}%`;
                            progressPercentage.textContent = `${Math.round(percent)}%`;
                        }
                    });

                if (error) {
                    console.error('Upload error:', error.message);
                    alert('Upload failed: ' + error.message);
                } else {
                    console.log('Upload successful:', data);
                    alert('Files uploaded successfully!');
                    // Here you might want to save file metadata (like data.path) to your database
                }
            } catch (error) {
                console.error('Unexpected error during upload:', error);
                alert('An unexpected error occurred during upload.');
            } finally {
                // Hide progress bar and reset modal
                hideUploadModal();
            }
        });
    </script>
    <script>
        // User Profile Dropdown Functionality
        const userProfileBtn = document.getElementById('userProfileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const overlay = document.getElementById('overlay');

        userProfileBtn.addEventListener('click', function() {
            profileDropdown.classList.toggle('opacity-0');
            profileDropdown.classList.toggle('invisible');
            profileDropdown.classList.toggle('translate-y-[-10px]');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', function() {
            profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            overlay.classList.add('hidden');
            hideUploadModal();
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
        const modalBackdrop = document.getElementById('modalBackdrop');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelUploadBtn = document.getElementById('cancelUploadBtn');
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressPercentage = document.getElementById('progressPercentage');

        function showUploadModal() {
            uploadModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function hideUploadModal() {
            uploadModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            
            // Reset modal state
            fileInput.value = '';
            fileList.classList.add('hidden');
            fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
            uploadBtn.disabled = true;
            uploadProgress.classList.add('hidden');
            progressBar.style.width = '0%';
            progressPercentage.textContent = '0%';
        }

        // Open modal
        newBtn.addEventListener('click', showUploadModal);

        // Close modal
        closeModalBtn.addEventListener('click', hideUploadModal);
        cancelUploadBtn.addEventListener('click', hideUploadModal);
        modalBackdrop.addEventListener('click', hideUploadModal);

        // Prevent modal closing when clicking inside the modal
        uploadModal.querySelector('.bg-white').addEventListener('click', function(event) {
            event.stopPropagation();
        });

        // Handle file selection
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

        // Upload button functionality
        uploadBtn.addEventListener('click', function() {
            // Show progress bar
            uploadProgress.classList.remove('hidden');
            
            // Disable upload button
            uploadBtn.disabled = true;
            
            // Simulate upload progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    
                    // Simulate a small delay before completing
                    setTimeout(() => {
                        // Show success message
                        alert('Files uploaded successfully!');
                        
                        // Close the modal
                        hideUploadModal();
                    }, 500);
                }
                
                // Update progress bar
                progressBar.style.width = `${progress}%`;
                progressPercentage.textContent = `${Math.round(progress)}%`;
            }, 300);
        });
    </script>
    
    <div id="n8n-chat-container"></div>
<script type="module">
    import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

    createChat({
        webhookUrl: 'http://localhost:5678/webhook/5dd17da4-fc5c-4f8b-876b-b5718ea0716a/chat',
        container: '#n8n-chat-container' // Specify the container to render the chat in
    });
</script>
</body>
</html>