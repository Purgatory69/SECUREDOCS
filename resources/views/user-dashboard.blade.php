@extends('layouts.app')

@section('content')
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
        <div class="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer hover:bg-bg-light">🔔</div>
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

<div id="uploadModal" class="fixed inset-0 z-50 flex  items-center justify-center hidden text-white">
    <div class="fixed inset-0 bg-[#0D0E2F] bg-opacity-50 transition-opacity" id="modalBackdrop"></div>
    <div class="bg-[#0D0E2F] rounded-lg shadow-xl w-full max-w-md p-6 relative z-10 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-medium text-white text-text-main">Upload New File</h3>
            <button id="closeModalBtn" class="text-text-secondary hover:text-white text-2xl focus:outline-none">
                &times;
            </button>
        </div>

        <div class="space-y-6">
            <div id="dropZone"
                class="border-2 border-dashed border-border-color rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors">
                <div class="flex flex-col items-center">
                    <div class="text-3xl mb-3">📄</div>
                    <p class="mb-2 text-sm">Drag and drop files here or click to browse</p>
                    <p class="text-xs text-text-secondary text-white"> Maximum file size: 100MB</p>
                </div>
                <input type="file" id="fileInput" class="hidden" multiple>
            </div>

            <div id="fileList"></div>

            <!-- <div class="space-y-3">
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
-->
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
                    </select>
                </div>
                
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium mb-2">Date From</label>
                    <input id="searchDateFrom" type="date" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Date To</label>
                    <input id="searchDateTo" type="date" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white" />
                </div>
                
                <!-- File Size Range -->
                <div>
                    <label class="block text-sm font-medium mb-2">Min Size (MB)</label>
                    <input id="searchSizeMin" type="number" min="0" step="0.1" placeholder="0" 
                           class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white placeholder-gray-400" />
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Max Size (MB)</label>
                    <input id="searchSizeMax" type="number" min="0" step="0.1" placeholder="1000" 
                           class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white placeholder-gray-400" />
                </div>
                
                
                <!-- Sort Options -->
                <div>
                    <label class="block text-sm font-medium mb-2">Sort By</label>
                    <select id="searchSortBy" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white">
                        <option value="updated_at">Last Modified</option>
                        <option value="created_at">Date Created</option>
                        <option value="file_name">Name</option>
                        <option value="file_size">Size</option>
                        <option value="file_type">Type</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Sort Order</label>
                    <select id="searchSortOrder" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white">
                        <option value="desc">Newest First</option>
                        <option value="asc">Oldest First</option>
                    </select>
                </div>
            </div>
            
            <!-- Search Actions -->
            <div class="flex justify-between items-center pt-6 border-t border-border-color">
                <div class="flex space-x-2">
                    <button type="button" id="clearSearchFilters" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded text-sm">
                        Clear Filters
                    </button>
                    <button type="button" id="saveSearchBtn" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded text-sm">
                        Save Search
                    </button>
                </div>
                <div class="flex space-x-2">
                    <button type="button" id="cancelAdvancedSearch" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded text-sm">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                        Search
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Saved Searches Section -->
        <div class="mt-8 pt-6 border-t border-border-color">
            <h4 class="text-lg font-semibold mb-4">Saved Searches</h4>
            <div id="savedSearchesList" class="space-y-2">
                <!-- Saved searches will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Version History Modal -->
<div id="versionHistoryModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="fixed inset-0 bg-black bg-opacity-50"></div>
    <div class="relative bg-[#0D0E2F] text-white rounded-lg shadow-xl w-full max-w-5xl p-6 z-10 max-h-screen overflow-hidden">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold">
                Version History - <span id="versionHistoryFileName"></span>
            </h3>
            <button id="versionHistoryCloseBtn" class="text-2xl leading-none">&times;</button>
        </div>
        
        <div class="flex gap-6 h-96">
            <!-- Versions List -->
            <div class="w-1/2 border-r border-border-color pr-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-medium">Versions</h4>
                    <div class="flex gap-2">
                        <input type="file" id="newVersionInput" class="hidden" accept="*/*" />
                        <button id="uploadNewVersionBtn" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                            Upload New Version
                        </button>
                    </div>
                </div>
                
                <div id="versionsList" class="space-y-2 overflow-y-auto max-h-72">
                    <!-- Versions will be populated by JavaScript -->
                    <div class="text-center text-text-secondary py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
                        Loading versions...
                    </div>
                </div>
            </div>
            
            <!-- Activity Timeline -->
            <div class="w-1/2 pl-6">
                <h4 class="text-lg font-medium mb-4">Activity Timeline</h4>
                <div id="activityTimeline" class="space-y-3 overflow-y-auto max-h-72">
                    <!-- Activity will be populated by JavaScript -->
                    <div class="text-center text-text-secondary py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
                        Loading activity...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Version Upload Modal -->
        <div id="versionUploadForm" class="hidden mt-6 pt-6 border-t border-border-color">
            <h5 class="text-md font-medium mb-4">Upload New Version</h5>
            <form id="newVersionForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Selected File:</label>
                    <div id="selectedFileName" class="text-text-secondary text-sm mb-2">No file selected</div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Version Comment (Optional):</label>
                    <textarea id="versionComment" placeholder="Describe what changed in this version..." 
                             class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white placeholder-gray-400 h-20 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancelVersionUpload" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded text-sm">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                        Upload Version
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



@push('scripts')
    <!-- Activity Log Modal -->
    <div id="activityModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/5 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Activity & Audit Logs</h3>
                    <button id="closeActivityModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Activity Stats -->
                <div id="activityStats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-blue-600" id="todayCount">-</div>
                        <div class="text-sm text-blue-500">Today</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-green-600" id="weekCount">-</div>
                        <div class="text-sm text-green-500">This Week</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-yellow-600" id="monthCount">-</div>
                        <div class="text-sm text-yellow-500">This Month</div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-purple-600" id="totalCount">-</div>
                        <div class="text-sm text-purple-500">Total</div>
                    </div>
                </div>

                <!-- Filters and Controls -->
                <div class="flex flex-wrap gap-3 mb-4">
                    <select id="activityTypeFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Types</option>
                        <option value="file">File Activities</option>
                        <option value="auth">Authentication</option>
                        <option value="system">System</option>
                    </select>
                    
                    <select id="riskLevelFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Risk Levels</option>
                        <option value="low">Low Risk</option>
                        <option value="medium">Medium Risk</option>
                        <option value="high">High Risk</option>
                        <option value="critical">Critical Risk</option>
                    </select>

                    <input type="date" id="dateFromFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="From Date">
                    <input type="date" id="dateToFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="To Date">
                    
                    <button id="applyActivityFilters" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                        Apply Filters
                    </button>
                    
                    <button id="exportActivities" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                        📊 Export
                    </button>
                </div>

                <!-- Activity Timeline -->
                <div class="mb-4">
                    <h4 class="text-md font-medium text-gray-800 mb-2">Activity Timeline</h4>
                    <div id="activityTimeline" class="h-32 bg-gray-50 rounded-lg p-4 flex items-center justify-center">
                        <span class="text-gray-500">Loading timeline...</span>
                    </div>
                </div>

                <!-- Activity List -->
                <div class="max-h-96 overflow-y-auto">
                    <div id="activityList" class="space-y-3">
                        <!-- Activities will be loaded here -->
                        <div class="text-center py-8 text-gray-500">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                            Loading activities...
                        </div>
                    </div>
                </div>

                <!-- Load More Button -->
                <div class="text-center mt-4">
                    <button id="loadMoreActivities" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm hidden">
                        Load More
                    </button>
                </div>

                <!-- Tabs for Additional Views -->
                <div class="mt-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button class="activity-tab-btn border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600" data-tab="activities">
                                Activities
                            </button>
                            <button class="activity-tab-btn border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="sessions">
                                Sessions
                            </button>
                            <button class="activity-tab-btn border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="security">
                                Security Events
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Contents -->
                    <div id="activitiesTab" class="activity-tab-content mt-4">
                        <!-- Main activity list above -->
                    </div>

                    <div id="sessionsTab" class="activity-tab-content mt-4 hidden">
                        <div id="userSessions" class="space-y-3">
                            <!-- Sessions will be loaded here -->
                        </div>
                    </div>

                    <div id="securityTab" class="activity-tab-content mt-4 hidden">
                        <div id="securityEvents" class="space-y-3">
                            <!-- Security events will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Security Dashboard Modal -->
    <div id="securityModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                            <span class="mr-3 text-3xl">🛡️</span>
                            Security Dashboard
                        </h3>
                        <button id="closeSecurityModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Security Stats Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-red-600">Security Violations</p>
                                    <p class="text-2xl font-bold text-red-900" id="violationsCount">-</p>
                                </div>
                                <div class="text-red-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-red-500 mt-1"><span id="unresolvedViolations">-</span> unresolved</p>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-600">Trusted Devices</p>
                                    <p class="text-2xl font-bold text-blue-900" id="trustedDevicesCount">-</p>
                                </div>
                                <div class="text-blue-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zm0 3a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-blue-500 mt-1"><span id="activeDevices">-</span> active</p>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-yellow-600">Encrypted Files</p>
                                    <p class="text-2xl font-bold text-yellow-900" id="encryptedFilesCount">-</p>
                                </div>
                                <div class="text-yellow-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-yellow-500 mt-1"><span id="needKeyRotation">-</span> need rotation</p>
                        </div>
                        
                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-purple-600">DLP Scans</p>
                                    <p class="text-2xl font-bold text-purple-900" id="dlpScansCount">-</p>
                                </div>
                                <div class="text-purple-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-purple-500 mt-1"><span id="highRiskScans">-</span> high risk</p>
                        </div>
                    </div>

                    <!-- Security Tabs -->
                    <div class="border-b border-gray-200 mb-4">
                        <nav class="-mb-px flex space-x-8">
                            <button class="security-tab-btn active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="violations">
                                Violations
                            </button>
                            <button class="security-tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="devices">
                                Trusted Devices
                            </button>
                            <button class="security-tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="encryption">
                                Encryption
                            </button>
                            <button class="security-tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="dlp">
                                DLP Scans
                            </button>
                            <button class="security-tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="policies">
                                Policies
                            </button>
                        </nav>
                    </div>

                    <!-- Security Violations Tab -->
                    <div id="violationsTab" class="security-tab-content">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Security Violations</h4>
                            <div class="flex space-x-2">
                                <select id="violationsSeverityFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Severities</option>
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                                <select id="violationsStatusFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Status</option>
                                    <option value="open">Open</option>
                                    <option value="investigating">Investigating</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                        </div>
                        <div id="violationsList" class="space-y-3 max-h-96 overflow-y-auto">
                            <!-- Violations will be loaded here -->
                        </div>
                    </div>

                    <!-- Trusted Devices Tab -->
                    <div id="devicesTab" class="security-tab-content hidden">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Trusted Devices</h4>
                            <div class="flex space-x-2">
                                <select id="devicesStatusFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="revoked">Revoked</option>
                                    <option value="expired">Expired</option>
                                </select>
                                <button id="addDeviceBtn" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    Trust Device
                                </button>
                            </div>
                        </div>
                        <div id="devicesList" class="space-y-3 max-h-96 overflow-y-auto">
                            <!-- Devices will be loaded here -->
                        </div>
                    </div>

                    <!-- Encryption Tab -->
                    <div id="encryptionTab" class="security-tab-content hidden">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">File Encryption</h4>
                            <div class="flex space-x-2">
                                <select id="encryptionLevelFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Levels</option>
                                    <option value="public">Public</option>
                                    <option value="internal">Internal</option>
                                    <option value="confidential">Confidential</option>
                                    <option value="restricted">Restricted</option>
                                    <option value="top_secret">Top Secret</option>
                                </select>
                            </div>
                        </div>
                        <div id="encryptionList" class="space-y-3 max-h-96 overflow-y-auto">
                            <!-- Encryption info will be loaded here -->
                        </div>
                    </div>

                    <!-- DLP Scans Tab -->
                    <div id="dlpTab" class="security-tab-content hidden">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">DLP Scan Results</h4>
                            <div class="flex space-x-2">
                                <select id="dlpRiskFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Risk Levels</option>
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                                <select id="dlpStatusFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Status</option>
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="scanning">Scanning</option>
                                </select>
                            </div>
                        </div>
                        <div id="dlpScansList" class="space-y-3 max-h-96 overflow-y-auto">
                            <!-- DLP scans will be loaded here -->
                        </div>
                    </div>

                    <!-- Security Policies Tab -->
                    <div id="policiesTab" class="security-tab-content hidden">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Security Policies</h4>
                            <div class="flex space-x-2">
                                <select id="policiesTypeFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Types</option>
                                    <option value="access_control">Access Control</option>
                                    <option value="dlp">Data Loss Prevention</option>
                                    <option value="encryption">Encryption</option>
                                    <option value="audit">Audit</option>
                                </select>
                                <button id="createPolicyBtn" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    Create Policy
                                </button>
                            </div>
                        </div>
                        <div id="policiesList" class="space-y-3 max-h-96 overflow-y-auto">
                            <!-- Policies will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resolve Violation Modal -->
    <div id="resolveViolationModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Resolve Security Violation</h3>
                        <button id="closeResolveViolationModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form id="resolveViolationForm">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Notes</label>
                            <textarea id="resolutionNotes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Describe how this violation was resolved..." required></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" id="cancelResolveViolation" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Resolve Violation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Trust Device Modal -->
    <div id="trustDeviceModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Trust New Device</h3>
                        <button id="closeTrustDeviceModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form id="trustDeviceForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Device Name</label>
                                <input type="text" id="trustDeviceName" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="My Laptop" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Trust Level</label>
                                <select id="trustDeviceLevel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="standard">Standard</option>
                                    <option value="high">High</option>
                                    <option value="limited">Limited</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expires In (Days)</label>
                                <input type="number" id="trustDeviceExpires" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="90" min="1" max="365" required>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" id="cancelTrustDevice" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Trust Device
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Blockchain Storage Modal -->
    <div id="blockchainStorageModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-3xl mr-3">🔗</span>
                            <div>
                                <h3 class="text-2xl font-bold text-white">Blockchain Storage</h3>
                                <p class="text-purple-100">Decentralized, immutable file storage</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 text-xs bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full text-white font-medium">
                                PREMIUM
                            </span>
                            <button id="closeBlockchainModal" class="text-white hover:text-purple-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white px-6 pt-4 pb-4">
                    <!-- Storage Analytics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-600">Files on IPFS</p>
                                    <p class="text-2xl font-bold text-blue-900" id="ipfsFileCount">-</p>
                                </div>
                                <div class="text-blue-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zm0 3a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-blue-500 mt-1">Immutable storage</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-green-600">Storage Used</p>
                                    <p class="text-2xl font-bold text-green-900" id="blockchainStorageUsed">-</p>
                                </div>
                                <div class="text-green-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zm0 3a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-green-500 mt-1">of <span id="blockchainStorageLimit">1TB</span> limit</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-purple-600">Monthly Cost</p>
                                    <p class="text-2xl font-bold text-purple-900" id="blockchainMonthlyCost">-</p>
                                </div>
                                <div class="text-purple-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-purple-500 mt-1">IPFS + CDN included</p>
                        </div>
                        
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg border border-orange-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-orange-600">Global Nodes</p>
                                    <p class="text-2xl font-bold text-orange-900">22K+</p>
                                </div>
                                <div class="text-orange-500">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-orange-500 mt-1">Worldwide distribution</p>
                        </div>
                    </div>

                    <!-- Blockchain Tabs -->
                    <div class="border-b border-gray-200 mb-4">
                        <nav class="-mb-px flex space-x-8">
                            <button class="blockchain-tab-btn active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="files">
                                My Blockchain Files
                            </button>
                            <button class="blockchain-tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="upload">
                                Upload to IPFS
                            </button>
                            <button class="blockchain-tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="providers">
                                Storage Providers
                            </button>
                            <button class="blockchain-tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="analytics">
                                Analytics
                            </button>
                        </nav>
                    </div>

                    <!-- Blockchain Files Tab -->
                    <div id="filesTab" class="blockchain-tab-content">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Files on Blockchain</h4>
                            <div class="flex space-x-2">
                                <select id="blockchainProviderFilter" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Providers</option>
                                    <option value="pinata">Pinata (IPFS)</option>
                                    <option value="filecoin">Filecoin</option>
                                    <option value="storj">STORJ</option>
                                    <option value="arweave">Arweave</option>
                                </select>
                                <button id="refreshBlockchainFiles" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    Refresh
                                </button>
                            </div>
                        </div>
                        <div id="blockchainFilesList" class="space-y-3 max-h-96 overflow-y-auto">
                            <!-- Blockchain files will be loaded here -->
                        </div>
                    </div>

                    <!-- Upload to IPFS Tab -->
                    <div id="uploadTab" class="blockchain-tab-content hidden">
                        <div class="max-w-2xl mx-auto">
                            <div class="text-center mb-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">Upload to Blockchain</h3>
                                <p class="text-gray-600">Store your files immutably on the decentralized web</p>
                            </div>

                            <!-- Upload Form -->
                            <form id="blockchainUploadForm" class="space-y-6">
                                <!-- File Drop Zone -->
                                <div id="blockchainDropZone" class="border-2 border-dashed border-purple-300 rounded-lg p-8 text-center hover:border-purple-400 transition-colors cursor-pointer">
                                    <svg class="mx-auto h-12 w-12 text-purple-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">
                                        <span class="font-medium text-purple-600 hover:text-purple-500">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PNG, JPG, PDF, DOC up to 100MB</p>
                                </div>
                                <input type="file" id="blockchainFileInput" class="hidden" multiple>

                                <!-- Provider Selection -->
                                <div class="space-y-4">
                                    <label class="block text-sm font-medium text-gray-700">Storage Provider</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="relative">
                                            <input type="radio" id="pinataProvider" name="provider" value="pinata" class="sr-only" checked>
                                            <label for="pinataProvider" class="flex items-center p-4 border-2 border-purple-200 rounded-lg cursor-pointer hover:border-purple-300 transition-colors">
                                                <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-white text-sm font-bold">P</span>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900">Pinata (IPFS)</div>
                                                    <div class="text-sm text-gray-500">$20/month • Best for developers</div>
                                                </div>
                                                <div class="w-4 h-4 border-2 border-purple-600 rounded-full flex items-center justify-center">
                                                    <div class="w-2 h-2 bg-purple-600 rounded-full"></div>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="relative">
                                            <input type="radio" id="filecoinProvider" name="provider" value="filecoin" class="sr-only">
                                            <label for="filecoinProvider" class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-gray-300 transition-colors">
                                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-white text-sm font-bold">F</span>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900">Filecoin</div>
                                                    <div class="text-sm text-gray-500">$0.19/TB • Most cost-effective</div>
                                                </div>
                                                <div class="w-4 h-4 border-2 border-gray-300 rounded-full"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload Options -->
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="encryptBeforeUpload" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <label for="encryptBeforeUpload" class="ml-2 text-sm text-gray-700">Encrypt before uploading to blockchain</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="permanentStorage" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <label for="permanentStorage" class="ml-2 text-sm text-gray-700">Permanent storage (cannot be deleted)</label>
                                    </div>
                                </div>

                                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                    Upload to Blockchain
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Storage Providers Tab -->
                    <div id="providersTab" class="blockchain-tab-content hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Pinata Card -->
                            <div class="border border-purple-200 rounded-lg p-6 bg-gradient-to-br from-purple-50 to-pink-50">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-white text-lg font-bold">P</span>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Pinata (IPFS)</h3>
                                            <p class="text-sm text-gray-500">Professional IPFS service</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">ACTIVE</span>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Storage:</span>
                                        <span class="font-medium">1TB included</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Bandwidth:</span>
                                        <span class="font-medium">Unlimited</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">CDN:</span>
                                        <span class="font-medium">Global</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Price:</span>
                                        <span class="font-medium">$20/month</span>
                                    </div>
                                </div>
                                <button class="w-full mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700">
                                    Manage Settings
                                </button>
                            </div>

                            <!-- Filecoin Card -->
                            <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-white text-lg font-bold">F</span>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Filecoin</h3>
                                            <p class="text-sm text-gray-500">Decentralized storage network</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">AVAILABLE</span>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Storage:</span>
                                        <span class="font-medium">Pay per TB</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Redundancy:</span>
                                        <span class="font-medium">Multi-node</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Duration:</span>
                                        <span class="font-medium">Flexible</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Price:</span>
                                        <span class="font-medium">$0.19/TB/month</span>
                                    </div>
                                </div>
                                <button class="w-full mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                                    Enable Provider
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Tab -->
                    <div id="analyticsTab" class="blockchain-tab-content hidden">
                        <div class="space-y-6">
                            <!-- Storage Usage Chart -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">Storage Usage Over Time</h4>
                                <div id="storageChart" class="h-64 flex items-center justify-center text-gray-500">
                                    <div class="text-center">
                                        <svg class="w-16 h-16 mx-auto mb-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <p>Usage analytics will appear here</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cost Analysis -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-6 border border-green-200">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Monthly Costs</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Pinata Storage:</span>
                                            <span class="font-medium">$20.00</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Bandwidth:</span>
                                            <span class="font-medium">$0.00</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Gateway Requests:</span>
                                            <span class="font-medium">$2.50</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="flex justify-between font-semibold">
                                            <span>Total:</span>
                                            <span>$22.50</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6 border border-purple-200">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Performance Stats</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Avg. Upload Speed:</span>
                                            <span class="font-medium">45 MB/s</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Global Availability:</span>
                                            <span class="font-medium">99.9%</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Active Gateways:</span>
                                            <span class="font-medium">12</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Data Redundancy:</span>
                                            <span class="font-medium">3x</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite(['resources/js/dashboard.js'])
@endpush

</body>

</html>