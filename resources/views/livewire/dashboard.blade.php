@extends('layouts.app')

@section('content')
<div>
    {{-- HEADER COMPONENT --}}
    <header class="col-span-2 grid-area-[header] flex items-center px-4 bg-white border-b border-border-color z-10">
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
                            <a href="/user/profile" class="text-sm">Profile Settings</a>
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

    {{-- SIDEBAR COMPONENT --}}
    <aside class="grid-area-[sidebar] bg-white border-r border-border-color py-4 overflow-y-auto">
        <div id="overlay" class="fixed inset-0 bg-transparent z-[5] hidden"></div>
        <div id="uploadModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" id="modalBackdrop"></div>
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative z-10 transform transition-all">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-medium text-text-main">Upload New File</h3>
                    <button id="closeModalBtn" class="text-text-secondary hover:text-gray-700 text-2xl focus:outline-none">&times;</button>
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
                    <button id="cancelUploadBtn" class="py-2 px-4 border border-border-color rounded text-sm bg-white hover:bg-bg-light transition-colors">Cancel</button>
                    <button id="uploadBtn" class="py-2 px-4 bg-primary text-white rounded text-sm hover:bg-primary-dark transition-colors" disabled>Upload</button>
                </div>
            </div>
        </div>
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
    </aside>

    {{-- MAIN CONTENT COMPONENT --}}
    <main class="grid-area-[main] bg-white p-6 overflow-y-auto">
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

    {{-- CHAT INTEGRATION COMPONENT --}}
    <div id="n8n-chat-container" style="position:fixed;bottom:24px;right:24px;z-index:9999;"></div>
    <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';
        const currentUserEmail = window.userEmail;
        const currentUserId = window.userId;
        const currentUsername = window.username;
        createChat({
            webhookUrl: 'https://fool1.app.n8n.cloud/webhook/0a216509-e55c-4a43-8d4a-581dffe09d18/chat',
            webhookConfig: { method: 'POST', headers: {} },
            target: '#n8n-chat-container',
            mode: 'window',
            chatInputKey: 'chatInput',
            chatSessionKey: 'sessionId',
            metadata: { userId: currentUserId, userEmail: currentUserEmail, userName: currentUsername },
            showWelcomeScreen: false,
            defaultLanguage: 'en',
            initialMessages: [ 'Hello!', 'My Name is Tubby. How can I assist you today?' ],
            i18n: { en: { title: 'Welcome!', subtitle: "Ask me anything.", getStarted: 'Start Chatting', inputPlaceholder: 'Enter your message here...' } },
            theme: { colors: { primary: '#4285f4' } },
        });
    </script>
</div>
@endsection

{{--
    TODO: 
    - Move header, sidebar, upload modal, file list, and chat into separate components as needed.
    - Integrate Supabase logic via Vite or inline script.
--}}
