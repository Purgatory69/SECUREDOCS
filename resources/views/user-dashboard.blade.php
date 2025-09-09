@extends('layouts.app')

@section('content')
{{-- CSRF meta is already included in the base layout --}}
<script>
    window.currentUserId = {{ Auth::id() }};
</script>
<header class="col-span-2 flex items-center px-4 bg-[#141326] border-b border-border-color z-10 pb-5 pt-5">
    <div class="mt-2 ml-4 mb-2 flex items-center space-x-3 py-8 mr-10">
        <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-8 h-8">
        <div class="text-white text-l font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></div>
    </div>

    <div class="flex-grow max-w-[720px] relative pb-10 pl-10">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-text-secondary">🔍</span>
        <input type="text" id="mainSearchInput" placeholder="Search files..."
            class="w-full py-3 pl-12 pr-4 rounded-lg border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md placeholder-[#FFFFFF]">
    </div>

    <div class="flex items-center ml-auto gap-4">
        <!-- Notifications Bell -->
        <div class="relative">
            <div id="notificationBell" class="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer hover:bg-bg-light relative">
                🔔
                <span id="notificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
            </div>
            <!-- Notification Dropdown -->
            <div id="notificationDropdown" class="absolute top-[54px] right-0 w-[350px] bg-white rounded-lg shadow-lg z-10 overflow-hidden transition-all duration-300 opacity-0 invisible translate-y-[-10px]">
                <div class="p-4 border-b border-border-color flex items-center justify-between">
                    <h3 class="text-base font-medium text-gray-900">Notifications</h3>
                    <button id="markAllRead" class="text-sm text-blue-600 hover:text-blue-800">Mark all read</button>
                </div>
                <div id="notificationsList" class="max-h-96 overflow-y-auto">
                    <div class="p-4 text-center text-gray-500">No notifications</div>
                </div>
                <div class="p-3 border-t border-border-color text-center">
                    <button id="viewAllNotifications" class="text-sm text-blue-600 hover:text-blue-800">View all notifications</button>
                </div>
            </div>
        </div>
        <div class="relative inline-block">
            <div id="userProfileBtn"
                class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center text-base cursor-pointer z-20">
                U</div>
            <div id="profileDropdown"
                class="absolute top-[54px] right-0 w-[280px] bg-white rounded-lg shadow-lg z-10 overflow-hidden transition-all duration-300 opacity-0 invisible translate-y-[-10px]">
                <div class="p-4 border-b border-border-color flex items-center">
                    <div
                        class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center text-xl mr-4">
                        U</div>
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
                        <span class="mr-4 text-lg w-6 text-center">👆</span>
                        <a href="{{ route('webauthn.index') }}" class="text-sm">Biometric Login</a>
                    </li>
                    <li class="h-px bg-border-color my-1"></li>
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
                        <button type="submit"
                            class="py-2 px-4 bg-bg-light border border-border-color rounded text-sm cursor-pointer hover:bg-gray-200">Sign
                            Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<div id="overlay" class="fixed inset-0 bg-transparent z-[5] hidden"></div>

<div id="uploadModal" class="fixed inset-0 z-50 flex items-start md:items-center justify-center hidden text-white overflow-y-auto py-6">
    <div class="fixed inset-0 bg-[#0D0E2F] bg-opacity-50 transition-opacity" id="modalBackdrop"></div>
    <div class="bg-[#0D0E2F] rounded-xl shadow-2xl w-full max-w-[92vw] sm:max-w-lg md:max-w-2xl mx-auto p-4 sm:p-6 md:p-8 relative z-10 transform transition-all ring-1 ring-white/10 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-medium text-white text-text-main">Upload New File</h3>
            <button id="closeModalBtn" class="text-text-secondary hover:text-white text-2xl focus:outline-none">
                &times;
            </button>
        </div>

        <div class="space-y-6">
            <div id="dropZone"
                class="rounded-xl border-2 border-dashed border-border-color/60 bg-[#14183A] hover:bg-[#1B1F4A] p-8 sm:p-10 text-center cursor-pointer transition-colors duration-200">
                <div id="dropZoneContent" class="flex flex-col items-center">
                    <div class="text-4xl mb-3">📄</div>
                    <p class="mb-1 text-sm">Drag and drop files here or click to browse</p>
                    <p class="text-xs text-text-secondary">Maximum file size: 100MB</p>
                </div>
                <input type="file" id="fileInput" class="hidden" multiple>
            </div>

            <div id="fileList"></div>

            <!-- Processing Options -->
            <div class="space-y-4 border-t border-border-color pt-4" id="processingOptions" style="display: none;">
                <div class="text-sm font-medium text-white">Processing Options</div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="block cursor-pointer group">
                        <input type="radio" id="standardUpload" name="processingType" value="standard" class="peer sr-only" checked>
                        <div class="rounded-lg border border-border-color bg-[#171A3A] p-4 transition-colors group-hover:border-primary/60 peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/40">
                            <div class="flex items-start gap-3">
                                <div class="text-xl">📦</div>
                                <div>
                                    <div class="text-sm font-medium">Standard Upload</div>
                                    <p class="text-xs text-gray-400 mt-1">Upload file to your personal storage</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="block cursor-pointer group" data-premium-option="true">
                        <input type="radio" id="blockchainUpload" name="processingType" value="blockchain" class="peer sr-only">
                        <div class="rounded-lg border border-border-color bg-[#171A3A] p-4 transition-colors group-hover:border-primary/60 peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/40">
                            <div class="flex items-start gap-3">
                                <div class="text-xl">🔗</div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-medium">Blockchain Storage</div>
                                        <span id="badgeBlockchain" class="px-2 py-0.5 text-[10px] bg-gradient-to-r from-purple-500 to-pink-500 rounded-full text-white">PREMIUM</span>
                                    </div>
                                    <p id="descBlockchain" class="text-xs text-gray-400 mt-1">Store on IPFS via Pinata (Premium required)</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="block cursor-pointer group" data-premium-option="true">
                        <input type="radio" id="vectorizeUpload" name="processingType" value="vectorize" class="peer sr-only">
                        <div class="rounded-lg border border-border-color bg-[#171A3A] p-4 transition-colors group-hover:border-primary/60 peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/40">
                            <div class="flex items-start gap-3">
                                <div class="text-xl">🧠</div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-medium">AI Vectorization</div>
                                        <span id="badgeVectorize" class="px-2 py-0.5 text-[10px] bg-gradient-to-r from-purple-500 to-pink-500 rounded-full text-white">PREMIUM</span>
                                    </div>
                                    <p id="descVectorize" class="text-xs text-gray-400 mt-1">Process with AI for advanced search (Premium required)</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="block cursor-pointer group" data-premium-option="true">
                        <input type="radio" id="hybridUpload" name="processingType" value="hybrid" class="peer sr-only">
                        <div class="rounded-lg border border-border-color bg-[#171A3A] p-4 transition-colors group-hover:border-primary/60 peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/40">
                            <div class="flex items-start gap-3">
                                <div class="text-xl">🧠🔗</div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-medium">Blockchain + AI</div>
                                        <span id="badgeHybrid" class="px-2 py-0.5 text-[10px] bg-gradient-to-r from-purple-500 to-pink-500 rounded-full text-white">PREMIUM</span>
                                    </div>
                                    <p id="descHybrid" class="text-xs text-gray-400 mt-1">Store on IPFS and vectorize (Premium required)</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                <div id="processingValidation" class="hidden p-3 rounded border text-sm"></div>
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
            <button id="cancelUploadBtn"
                class="py-2 px-4 border border-border-color rounded text-sm bg-[#2B2C61] hover:bg-bg-light transition-colors">
                Cancel
            </button>
            <button id="uploadBtn"
                class="py-2 px-4 bg-[#3C3F58] text-white rounded text-sm hover:bg-primary-dark transition-colors"
                disabled>
                Upload
            </button>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div id="createFolderModal" class="fixed inset-0 z-50 flex items-center justify-center hidden text-white">
    <div class="fixed inset-0 bg-[#0D0E2F] bg-opacity-50 transition-opacity"></div>
    <div class="bg-[#0D0E2F] rounded-lg shadow-xl w-full max-w-md p-6 relative z-10 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-medium text-text-main">Create New Folder</h3>
            <button id="closeCreateFolderModalBtn"
                class="text-text-secondary hover:text-white text-2xl focus:outline-none">&times;</button>
        </div>
        <form id="createFolderForm">
            <div class="mb-4">
                <label for="newFolderNameInput" class="block text-sm font-medium text-gray-300 mb-1">Folder Name</label>
                <input type="text" id="newFolderNameInput" name="newFolderName"
                    class="w-full py-2 px-3 rounded-lg border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md placeholder-gray-400"
                    placeholder="Enter folder name" required>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" id="cancelCreateFolderBtn"
                    class="py-2 px-4 border border-border-color rounded text-sm bg-[#2B2C61] hover:bg-bg-light transition-colors">Cancel</button>
                <button type="submit"
                    class="py-2 px-4 bg-[#3C3F58] text-white rounded text-sm hover:bg-primary-dark transition-colors">Create
                    Folder</button>
            </div>
        </form>
    </div>
</div>

<div class="bg-[#141326] border-r border-border-color py-4 overflow-y-auto">
    <div class="relative mx-4 my-2">
        <div id="newBtn"
            class="flex items-center py-3 px-6 bg-[#3C3F58] border border-border-color rounded-3xl shadow-sm cursor-pointer transition-all duration-200 hover:shadow-md hover:bg-[#434563]">
            <span class="mr-3 text-2xl text-primary">+</span>
            <span class="text-base text-white font-medium">New</span>
            <svg class="ml-auto w-4 h-4 text-gray-400 transition-transform duration-200" id="chevronIcon" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>

        <!-- Dropdown Menu -->
        <div id="newDropdown"
            class="absolute top-full left-0 right-0 mt-2 bg-[#2B2C61] border border-border-color rounded-lg shadow-xl z-50 overflow-hidden opacity-0 invisible transform translate-y-[-10px] transition-all duration-200 hidden">
            <div class="py-2">
                <div id="uploadFileOption"
                    class="flex items-center px-4 py-3 hover:bg-[#3C3F58] cursor-pointer transition-colors duration-150">
                    <span class="mr-3 text-lg">📄</span>
                    <span class="text-white text-sm font-medium">Upload File</span>
                </div>
                <div id="createFolderOption"
                    class="flex items-center px-4 py-3 hover:bg-[#3C3F58] cursor-pointer transition-colors duration-150">
                    <span class="mr-3 text-lg">📁</span>
                    <span class="text-white text-sm font-medium">New Folder</span>
                </div>
            </div>
        </div>
    </div>



    <ul class="mt-4">
        <li id="my-documents-link" class="bg-[#A9A4FF] py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4 text-white text-primary">
            <span class="mr-4 text-lg w-6 text-center">📄</span>
            <span>My Documents</span>
        </li>
        <li id="trash-link" class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
            <span class="mr-4 text-lg w-6 text-center">🗑️</span>
            <span class="text-white">Trash</span>
        </li>
        <li id="security-link" class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
            <span class="mr-4 text-lg w-6 text-center">🛡️</span>
            <span class="text-white">Security</span>
        </li>
        <li id="blockchain-storage-link" class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
            <span class="mr-4 text-lg w-6 text-center">🔗</span>
            <span class="text-white">Blockchain Storage</span>
            <span class="ml-auto px-2 py-1 text-xs bg-gradient-to-r from-purple-500 to-pink-500 rounded-full text-white font-medium">PREMIUM</span>
        </li>
        <!-- <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                    <span class="mr-4 text-lg w-6 text-center">🔄</span>
                    <span>Shared with Me</span>
                </li> -->
        <!-- <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                    <span class="mr-4 text-lg w-6 text-center">⭐</span>
                    <span>Starred</span>
                </li> -->
        <!-- <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                    <span class="mr-4 text-lg w-6 text-center">🔒</span>
                    <span>Secure Vault</span>
                </li> -->
        <!-- <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                    <span class="mr-4 text-lg w-6 text-center">⏱️</span>
                    <span>Recent</span>
                </li> -->
        <!-- <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                    <span class="mr-4 text-lg w-6 text-center">🔗</span>
                    <span>Blockchain Verified</span>
                </li> -->
        <!-- <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                    <span class="mr-4 text-lg w-6 text-center">🗑️</span>
                    <span>Trash</span>
                </li> -->
    </ul>

    <!-- <div class="mt-8 px-6">
                <div class="w-full h-1 bg-gray-200 rounded overflow-hidden">
                    <div class="h-full w-[35%] bg-primary"></div>
                </div>
                <div class="text-xs text-text-secondary mt-2">3.5 GB of 10 GB used</div>
            </div> -->
</div>

<main class="bg-[#141326] p-6 overflow-y-auto">
    <input type="hidden" id="currentFolderId" value="">
    <div id="breadcrumbsContainer" class="mb-4 text-sm text-gray-400">
        <!-- Breadcrumbs will be populated by JavaScript -->
    </div>
    <h1 id="header-title" class="text-2xl text-white font-normal mb-6">My Documents</h1>

    <div id="new-button-container" class="flex items-center mb-6">
        <div class="ml-auto flex gap-2">
            <button id="btnGridLayout" title="Grid view" aria-label="Grid view"
                class="py-2 px-4 border border-border-color rounded text-sm bg-[#3C3F58] text-text-secondary hover:bg-bg-light">
                <span>📊</span>
            </button>
            <button id="btnListLayout" title="List view" aria-label="List view"
                class="py-2 px-4 border border-border-color rounded text-sm bg-[#3C3F58] text-text-secondary hover:bg-bg-light">
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

<!-- Advanced Search Modal -->
<div id="advancedSearchModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="fixed inset-0 bg-black bg-opacity-50"></div>
    <div class="relative bg-[#0D0E2F] text-white rounded-lg shadow-xl w-full max-w-4xl p-6 z-10 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold">Advanced Search</h3>
            <button id="advancedSearchCloseBtn" class="text-2xl leading-none">&times;</button>
        </div>
        
        <form id="advancedSearchForm" class="space-y-6">
            <!-- Search Query -->
            <div>
                <label class="block text-sm font-medium mb-2">Search Terms</label>
                <input id="advancedSearchQuery" type="text" placeholder="Enter search terms..." 
                       class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white placeholder-gray-400" />
            </div>
            
            <!-- Filters Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- File Type Filter -->
                <div>
                    <label class="block text-sm font-medium mb-2">File Type</label>
                    <select id="searchFileType" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white">
                        <option value="">All Types</option>
                        <option value="images">Images</option>
                        <option value="documents">Documents</option>
                        <option value="spreadsheets">Spreadsheets</option>
                        <option value="presentations">Presentations</option>
                        <option value="videos">Videos</option>
                        <option value="audio">Audio</option>
                        <option value="folders">Folders</option>
                        <option value="files">Files Only</option>
                        <option value="videos">Videos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Date From</label>
                    <input id="searchDateFrom" type="date" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white" />
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal is already embedded above -->

    <!-- Existing modals and components -->
    <div id="filePreviewModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <!-- File preview modal content -->
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/dashboard.js'])
@endpush