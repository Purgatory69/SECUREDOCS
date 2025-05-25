<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Securedocs</title>
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js"></script>
    <script>
        // These are already correctly exposed by your Laravel blade template
        window.userId = @json(auth()->id());
        window.userEmail = @json(Auth::user()->email); // Add this line to expose user email
        window.username = @json(Auth::user()->name); // Add this line to expose user email

        window.SUPABASE_URL = "{{ config('services.supabase.url') }}";
        window.SUPABASE_KEY = "{{ config('services.supabase.key') }}";
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Add custom styles that might be missing */
        .grid-areas-layout {
            display: grid;
        }
        .text-text-main {
            color: #202124;
        }
        .text-text-secondary {
            color: #5f6368;
        }
        .bg-bg-light {
            background-color: #f8f9fa;
        }
        .border-border-color {
            border-color: #dadce0;
        }
        .bg-primary {
            background-color: #4285f4;
        }
        .text-primary {
            color: #4285f4;
        }
        .hover\:bg-primary-dark:hover {
            background-color: #3367d6;
        }
    </style>
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
                                <a href="{{ route('profile.show') }}" class="text-sm">Profile Settings</a>
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

        <div id="uploadModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" id="modalBackdrop"></div>
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative z-10 transform transition-all">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-medium text-text-main">Upload New File</h3>
                    <button id="closeModalBtn" class="text-text-secondary hover:text-gray-700 text-2xl focus:outline-none">
                        &times;
                    </button>
                </div>

                <div class="space-y-6">
                    <div id="dropZone" class="border-2 border-dashed border-border-color rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors">
                        <div class="flex flex-col items-center">
                            <div class="text-3xl mb-3">📄</div>
                            <p class="mb-2 text-sm">Drag and drop files here or click to browse</p>
                            <p class="text-xs text-text-secondary">Maximum file size: 100MB</p>
                        </div>
                        <input type="file" id="fileInput" class="hidden" multiple>
                    </div>

                    <div id="fileList" class="space-y-2 max-h-40 overflow-y-auto hidden">
                        <div class="text-sm font-medium">Selected Files:</div>
                    </div>

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

            <div id="filesContainer" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <div class="p-4 text-center text-text-secondary col-span-full">Loading files...</div>
            </div>
        </main>
        <div id="n8n-chat-container" style="position:fixed;bottom:24px;right:24px;z-index:9999;"></div>
    </div>

    @push('scripts')
    <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

        // Retrieve user data
        const currentUserEmail = "{{ Auth::user()->email }}";
        const currentUserId = "{{ Auth::user()->id }}";
        const currentUsername = "{{ Auth::user()->name }}";

        createChat({
            webhookUrl: 'https://fool1.app.n8n.cloud/webhook/0a216509-e55c-4a43-8d4a-581dffe09d18/chat', // Replace with your actual webhook URL
            webhookConfig: {
                method: 'POST',
                headers: {}
            },
            target: '#n8n-chat-container',
            mode: 'window',
            chatInputKey: 'chatInput',
            chatSessionKey: 'sessionId',
            // Pass user metadata here
            metadata: {
                userId: currentUserId,
                userEmail: currentUserEmail,
                userName: currentUsername
            },
            showWelcomeScreen: false,
            defaultLanguage: 'en',
            initialMessages: [
                'Hello!',
                'My Name is Tubby. How can I assist you today?'
            ],
            i18n: {
                en: {
                    title: 'Welcome!',
                    subtitle: "Ask me anything.",
                    getStarted: 'Start Chatting',
                    inputPlaceholder: 'Enter your message here...',
                },
            },
            theme: {
                colors: {
                    primary: '#4285f4',
                },
            },
        });

        // User profile dropdown functionality
        const userProfileBtn = document.getElementById('userProfileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const overlay = document.getElementById('overlay');

        userProfileBtn.addEventListener('click', () => {
            profileDropdown.classList.toggle('opacity-0');
            profileDropdown.classList.toggle('invisible');
            profileDropdown.classList.toggle('translate-y-[-10px]');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', () => {
            profileDropdown.classList.add('opacity-0');
            profileDropdown.classList.add('invisible');
            profileDropdown.classList.add('translate-y-[-10px]');
            overlay.classList.add('hidden');
        });

        // Upload modal functionality
        const newBtn = document.getElementById('newBtn');
        const uploadModal = document.getElementById('uploadModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const cancelUploadBtn = document.getElementById('cancelUploadBtn');
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadBtn = document.getElementById('uploadBtn');

        newBtn.addEventListener('click', () => {
            uploadModal.classList.remove('hidden');
        });

        [closeModalBtn, modalBackdrop, cancelUploadBtn].forEach(element => {
            element.addEventListener('click', () => {
                uploadModal.classList.add('hidden');
            });
        });

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-primary');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-primary');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files);
        });

        function handleFiles(files) {
            if (files.length > 0) {
                fileList.classList.remove('hidden');
                fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
                uploadBtn.disabled = false;
                
                Array.from(files).forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between text-sm py-1';
                    fileItem.innerHTML = `
                        <div class="flex items-center">
                            <span class="mr-2">📄</span>
                            <span class="truncate max-w-[250px]">${file.name}</span>
                        </div>
                        <span class="text-xs text-text-secondary">${formatFileSize(file.size)}</span>
                    `;
                    fileList.appendChild(fileItem);
                });
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Simulate loading files
        setTimeout(() => {
            const filesContainer = document.getElementById('filesContainer');
            filesContainer.innerHTML = '';
            
            // This is just a placeholder. In a real app, you would fetch files from your backend
            const demoFiles = [
                { name: 'Document1.pdf', type: 'pdf', modified: 'May 23, 2025' },
                { name: 'Spreadsheet.xlsx', type: 'spreadsheet', modified: 'May 22, 2025' },
                { name: 'Report.docx', type: 'word', modified: 'May 19, 2025' }
            ];
            
            if (demoFiles.length === 0) {
                filesContainer.innerHTML = '<div class="p-4 text-center text-text-secondary col-span-full">No files found</div>';
            } else {
                demoFiles.forEach(file => {
                    const fileCard = document.createElement('div');
                    fileCard.className = 'bg-white border border-border-color rounded-lg overflow-hidden hover:shadow-md transition-shadow';
                    
                    let icon = '📄';
                    if (file.type === 'pdf') icon = '📕';
                    if (file.type === 'word') icon = '📘';
                    if (file.type === 'spreadsheet') icon = '📊';
                    if (file.type === 'image') icon = '🖼️';
                    
                    fileCard.innerHTML = `
                        <div class="p-4 flex flex-col items-center">
                            <div class="text-4xl mb-3">${icon}</div>
                            <div class="text-sm font-medium text-center truncate w-full">${file.name}</div>
                            <div class="text-xs text-text-secondary mt-1">Modified: ${file.modified}</div>
                        </div>
                    `;
                    filesContainer.appendChild(fileCard);
                });
            }
        }, 1500);
    </script>
</body>
</html>
