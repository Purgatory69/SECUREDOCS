@extends('layouts.app')

@section('content')
    <header class="col-span-2 flex items-center px-4 bg-[#141326] border-b border-border-color z-10 pb-5 pt-5">
        <div class="mt-2 ml-4 mb-2 flex items-center space-x-3 py-8 mr-10">
        <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-8 h-8">
        <div class="text-white text-l font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></div>
        </div>

        <div class="flex-grow max-w-[720px] relative pb-10 pl-10">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-text-secondary">🔍</span>
            <input type="text" id="searchInput" placeholder="Search files..." class="w-full py-3 pl-12 pr-4 rounded-lg border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md placeholder-[#FFFFFF]">
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
                                <button type="submit" class="py-2 px-4 bg-bg-light border border-border-color rounded text-sm cursor-pointer hover:bg-gray-200">Sign Out</button>
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
                    <div id="dropZone" class="border-2 border-dashed border-border-color rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors">
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
                    <button id="cancelUploadBtn" class="py-2 px-4 border border-border-color rounded text-sm bg-[#2B2C61] hover:bg-bg-light transition-colors">
                        Cancel
                    </button>
                    <button id="uploadBtn" class="py-2 px-4 bg-[#3C3F58] text-white rounded text-sm hover:bg-primary-dark transition-colors" disabled>
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
                    <button id="closeCreateFolderModalBtn" class="text-text-secondary hover:text-white text-2xl focus:outline-none">&times;</button>
                </div>
                <form id="createFolderForm">
                    <div class="mb-4">
                        <label for="newFolderNameInput" class="block text-sm font-medium text-gray-300 mb-1">Folder Name</label>
                        <input type="text" id="newFolderNameInput" name="newFolderName" class="w-full py-2 px-3 rounded-lg border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md placeholder-gray-400" placeholder="Enter folder name" required>
                    </div>
                    <div class="mt-8 flex justify-end gap-3">
                        <button type="button" id="cancelCreateFolderBtn" class="py-2 px-4 border border-border-color rounded text-sm bg-[#2B2C61] hover:bg-bg-light transition-colors">Cancel</button>
                        <button type="submit" class="py-2 px-4 bg-[#3C3F58] text-white rounded text-sm hover:bg-primary-dark transition-colors">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-[#141326] border-r border-border-color py-4 overflow-y-auto">
            <div id="newBtn" class="flex items-center mx-4 my-2 py-3 px-6 bg-[#3C3F58] border border-border-color rounded-3xl shadow-sm cursor-pointer transition-shadow hover:shadow-md">
                <span class="mr-3 text-2xl text-primary">+</span>
                <span class="text-base text-white font-medium">New File</span>
            </div>
            <div id="createFolderBtn" class="flex items-center mx-4 my-2 py-3 px-6 bg-[#3C3F58] border border-border-color rounded-3xl shadow-sm cursor-pointer transition-shadow hover:shadow-md mt-2">
                <span class="mr-3 text-2xl text-primary">📁</span> <!-- Folder Icon -->
                <span class="text-base text-white font-medium">New Folder</span>
            </div>


            <ul class="mt-4">
                <li class="bg-[#A9A4FF] py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4  text-white text-primary">
                    <span class=" bg-[#A9A4FF] mr-4 text-lg w-6 text-center">📄</span>
                    <span>My Documents</span>
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
            <div id="breadcrumbsContainer" class="mb-4 text-sm text-gray-400">
                <!-- Breadcrumbs will be populated by JavaScript -->
            </div>
            <h1 class="text-2xl text-white font-normal mb-6">My Documents</h1>

            <div class="flex items-center mb-6 gap-4 flex-wrap">
                <button class="bg-[#3C3F58] py-2 px-4 border border-border-color rounded-3xl text-sm text-primary border-[#c6dafc]">All Documents</button>
                <button class="bg-[#3C3F58] py-2 px-4 border border-border-color rounded-3xl text-sm text-white text-text-secondary hover:bg-bg-light">PDF Files</button>
                <button class="bg-[#3C3F58] py-2 px-4 border border-border-color rounded-3xl text-sm text-white text-text-secondary hover:bg-bg-light">Word Documents</button>
                <button class="bg-[#3C3F58] py-2 px-4 border border-border-color rounded-3xl text-sm text-white text-text-secondary hover:bg-bg-light">Images</button>
                <button class="bg-[#3C3F58] py-2 px-4 border border-border-color rounded-3xl text-sm text-white text-text-secondary hover:bg-bg-light">Spreadsheets</button>

                <div class="ml-auto flex gap-2">
                    <button class="py-2 px-4 border border-border-color rounded text-sm bg-[#3C3F58] text-text-secondary hover:bg-bg-light">
                        <span>📊</span>
                    </button>
                    <button class="py-2 px-4 border border-border-color rounded text-sm bg-[#3C3F58] text-primary ">
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
@endsection

@push('scripts')
    @vite(['resources/js/dashboard.js'])
@endpush

</body>
</html>
