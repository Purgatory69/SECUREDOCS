@extends('layouts.app')

@section('content')
<<<<<<< HEAD
{{-- CSRF meta is already included in the base layout --}}
<script>
    window.currentUserId = {{ Auth::id() }};
</script>
<header class="col-span-2 flex items-center px-4 bg-[#141326] border-b border-border-color z-10 pb-5 pt-5">
    <div class="mt-2 ml-4 mb-2 flex items-center space-x-3 py-8 mr-10">
        <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-8 h-8">
        <div class="text-white text-l font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></div>
=======

<header class="col-span-2 flex items-center px-4 bg-[#141326] z-10 h-18">
    <div style="margin-bottom: 13px;" class="ml-4 flex items-center space-x-3 mr-10">
        <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-8 h-8" style="margin-top:20px;">
        <div style="padding-right: 30px;" class="flex flex-col relative">
            <div class="text-white text-l font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></div>
            <div class="absolute top-full text-xs text-gray-400">
                {{ auth()->user()->is_premium ? __('auth.premium') : __('auth.basic-subscription') }}
            </div>
        </div>
>>>>>>> origin/language-feature
    </div>

    <div style="margin-left: -5px; outline: none;" class="flex-grow max-w-[720px] relative pl-6">
        <img src="{{ asset('magnifying-glass.png') }}" alt="Search" class="absolute top-1/2 -translate-y-1/2 w-4 h-4" style="left: 42px;">
        <input type="text" id="mainSearchInput" placeholder="Search in SECUREDOCS"
            class="w-full py-3 pl-12 pr-12 mt-4 mb-4 rounded-full border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md"
            style="color: white; outline: none; padding-right: 20px;"
            onfocus="this.style.setProperty('--placeholder-opacity', '0.5');"
            onblur="this.style.setProperty('--placeholder-opacity', '0.5');">
    </div>

    <style>
    #mainSearchInput::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }
    </style>

    <div class="flex items-center ml-auto gap-4">
<<<<<<< HEAD
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
=======
        <div class="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer hover:bg-[#3C3F58]">🔔</div>
        
        <div class="relative inline-block mr-2">
>>>>>>> origin/language-feature
            <div id="userProfileBtn"
                class="w-10 h-10 rounded-full flex items-center justify-center text-xl mr-2 cursor-pointer transition"
                style="background-color: #3C3F58;"
                onmouseover="this.style.filter='brightness(1.1)';"
                onmouseout="this.style.filter='';">
                <img src="{{ asset('user-shape.png') }}" alt="Profile" class="w-6 h-6 object-contain">
            </div>
            <div id="profileDropdown" 
                class="absolute top-[54px] right-0 w-[280px] bg-[#3C3F58] text-white rounded-lg shadow-lg z-10 overflow-hidden transition-all duration-300 opacity-0 invisible translate-y-[-10px]">
                <div class="p-4 border-border-color flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl mr-4 cursor-pointer transition"
                        style="background-color: #55597C;">
                        <img src="{{ asset('user-shape.png') }}" alt="Profile" class="w-6 h-6 object-contain">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-base font-medium mb-1">{{ Str::limit(Auth::user()->name, 20) }}</div>
                        <div style="color: #B6B6B6; font-size: 12px;" class="text-sm text-text-secondary">{{ Str::limit(Auth::user()->email, 25) }}</div>
                    </div>
                </div>
                <ul class="list-none">
                    <li class="h-px bg-gray-600 my-1 ml-4 mr-4"></li>
                    <li>
                        <a href="{{ route('profile.show') }}"
                        class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/user-shape.png" class="mr-4 w-4 h-4 ml-1" alt="Profile Settings">
                        <span class="text-sm">Profile Settings</span>
                        </a>
                    </li>

                    <li>
                        <a href=""
                        class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/padlock.png" class="mr-4 w-4 h-4 ml-1" alt="Security & Privacy">
                        <span class="text-sm">Security & Privacy</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('webauthn.index') }}"
                        class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/fingerprint.png" class="mr-4 w-4 h-4 ml-1" alt="Biometric Login">
                        <span class="text-sm">Biometric Login</span>
                        </a>
                    </li>
                    <li class="h-px bg-gray-600 my-1 ml-4 mr-4"></li>
                    <!-- <li class="h-px bg-border-color my-1"></li> -->
                    <!-- ======================================= -->
                    <!-- OPTION 2: DROPDOWN TO THE LEFT SIDE -->
                    <li class="relative"> 
                        <div id="headerLanguageToggle2" 
                        class="p-4 flex items-center justify-between cursor-pointer" 
                        style="transition: background-color 0.2s;" 
                        onmouseover="this.style.backgroundColor='#55597C';" 
                        onmouseout="this.style.backgroundColor='';"> 
                            <div class="flex items-center"> 
                                <svg class="mr-4 w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                                </svg>
                                <span class="text-sm">Language</span> 
                            </div> 
                            <!-- Image arrow positioned at the right -->
                            <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="w-2 h-2 mr-2 transition-transform duration-200" id="langCaret"> 
                        </div> 
                        
                        <!-- Updated submenu with conditional hover effects -->
                        <div id="headerLanguageSubmenu2" style="background-color: #3c3f58; border: 3px solid #1F1F33" class="absolute right-0 mr-4 top-full mt-2 w-[140px] rounded-lg shadow-xl overflow-hidden transition-all duration-200 opacity-0 invisible pointer-events-none translate-y-[-10px] z-40"> 
                            <a href="{{ route('language.switch', 'en') }}"  
                            class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'en' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                            @if(app()->getLocale() != 'en')
                                onmouseover="this.style.backgroundColor='#55597C';"
                                onmouseout="this.style.backgroundColor='';"
                            @endif> 
                                <span class="mr-2">🇺🇸</span> 
                                English 
                            </a> 
                            <a href="{{ route('language.switch', 'fil') }}"  
                            class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'fil' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                            @if(app()->getLocale() != 'fil')
                                onmouseover="this.style.backgroundColor='#55597C';"
                                onmouseout="this.style.backgroundColor='';"
                            @endif> 
                                <span class="mr-2">🇵🇭</span> 
                                Filipino 
                            </a> 
                        </div>
                    </li>
                    <li class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/info.png" class="mr-4 w-4 h-4 ml-1" alt="Security & Privacy">
                        <span class="text-sm">Help & Support</span>
                    </li>
                    <li class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/pencil.png" class="mr-4 w-4 h-4 ml-1" alt="Security & Privacy">
                        <span class="text-sm">Send Feedback</span>
                    </li>
                    <li class="h-px bg-gray-600 my-1 ml-4 mr-4"></li>
                </ul>
                <div class="pt-2 mt-2 border-border-color text-center">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="bg-[#f89c00] px-8 text-black font-bold py-2 rounded-full cursor-pointer hover:brightness-110 transition">Sign Out</button>
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


<div class="bg-[#141326] py-4">
<!-- New Button container -->
<div class="relative mx-4 my-2">
    <!-- Toggle button -->
    <div id="newBtn"
     class="flex items-center py-4 px-6 rounded-full shadow-sm cursor-pointer transition-all duration-200 hover:bg-[#55597C]"
     style="background-color: #3c3f58; :hover">
        <img src="{{ asset('add.png') }}" alt="Add" class="mr-3 w-3 h-3">
        <span class="text-sm text-white font-medium">New</span>
        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="ml-auto w-2 h-2" id="uploadIcon">
    </div>

    <!-- Dropdown Menu - Simplified Structure -->
    <div id="newDropdown" 
     class="absolute left-0 right-0 top-full mt-2 rounded-lg z-50 bg-[#55597C]"
     style="background-color: #55597C;">
        <div>
            <div id="uploadFileOption"
                class="flex items-center px-5 py-4 text-sm transition-colors text-white"
                onmouseover="this.style.cssText = 'background-color: #55597C; border-radius: 0.5rem 0.5rem 0 0;';"
                onmouseout="this.style.cssText = 'border-radius: 0.5rem 0.5rem 0 0;';">
                <img src="{{ asset('file.png') }}" alt="File" class="mr-4 w-4 h-4">
                <span class="font-medium">Upload File</span>
            </div>
            <div id="createFolderOption"
                class="flex items-center px-5 py-4 cursor-pointer"
                onmouseover="this.style.cssText = 'background-color: #55597C; border-radius: 0 0 0.5rem 0.5rem;';"
                onmouseout="this.style.cssText = 'border-radius: 0 0 0.5rem 0.5rem;';">
                <img src="{{ asset('folder-closed-black-shape.png') }}" alt="File" class="mr-4 w-4 h-4">
                <span class="text-white text-sm font-medium">New Folder</span>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar list starts here -->
     <ul id="sidebar" class="mt-4">
        <li id="my-documents-link" 
        class="py-3 px-8 flex items-center cursor-pointer rounded-r-2xl mr-4 
        bg-[#2B2C61] text-white">
        <img src="{{ asset('folder-white-shape.png') }}" alt="Documents" class="mr-4 w-5 h-5">
        <span class="text-sm">My Documents</span>
    </li>

        <li id="trash-link" 
            class="py-3 px-8 flex items-center cursor-pointer rounded-r-2xl mr-4 
            bg-[#141326] text-white hover:brightness-110 active:bg-[#2B2C61]">
            <img src="{{ asset('delete.png') }}" alt="Trash" class="mr-4 w-5 h-5">
            <span class="text-sm">Trash</span>
        </li>
        <li id="security-link" class="py-3 px-8 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
            <img src="{{ asset('shield.png') }}" alt="Security" class="mr-4 w-5 h-5">
            <span class="text-white text-sm">Security</span>
        </li>
        <li id="blockchain-storage-link" class="py-3 px-8 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
            <img src="{{ asset('link-symbol.png') }}" alt="Blockchain" class="mr-4 w-5 h-5">
            <span class="text-white text-sm">Blockchain Storage</span>
            <span class="ml-auto px-2 py-1 text-xs bg-gradient-to-r from-purple-500 to-pink-500 rounded-full text-white font-medium">PREMIUM</span>
        </li>

        <style>
            /* Sidebar styles */
            #sidebar>li{background:#141326!important;color:#fff!important;transition:filter .15s;}
            #sidebar>li:hover{filter:brightness(1.5)!important;}
            #sidebar>li.bg-primary{background:#2B2C61!important;color:#fff!important;}
            #sidebar>li.bg-primary:hover{filter:none!important;}
            #sidebar>li *{color:inherit!important;}

            /* New button + dropdown */
            #newBtn{background:#3c3f58!important;}
            #newBtn:hover{background:#55597C!important;}
            #newDropdown{background:#3c3f58!important;}
        </style>

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
</div>

<main style="background-color: #24243B; border-top-left-radius: 32px; margin-left: 13px;" class="p-6 overflow-y-auto">
    <input type="hidden" id="currentFolderId" value="">
    <div id="breadcrumbsContainer" style="color: #FFFFFF;" class="mb-4 text-sm text-gray-400 ">
        <!-- Breadcrumbs will be populated by JavaScript -->
    </div>
    <h1 id="header-title" class="text-2xl text-white font-bold mb-6">My Documents</h1>

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
<<<<<<< HEAD
@endpush
=======
@endpush

</body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Global dropdown state management
    let activeDropdown = null;
    
    // Function to close specific dropdowns
    function closeDropdown(dropdownName) {
        if (dropdownName === 'language' || dropdownName === 'all') {
            const langDropdown = document.getElementById('headerLanguageSubmenu2');
            const langArrow = document.getElementById('langCaret');
            if (langDropdown) {
                langDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
                langDropdown.style.pointerEvents = 'none';
                if (langArrow) {
                    langArrow.style.transform = 'rotate(0deg)';
                }
            }
        }
        
        if (dropdownName === 'new' || dropdownName === 'all') {
            const newDropdown = document.getElementById('newDropdown');
            const newArrow = document.getElementById('uploadIcon');
            if (newDropdown) {
                newDropdown.style.opacity = '0';
                newDropdown.style.visibility = 'hidden';
                newDropdown.style.transform = 'translateY(-10px)';
                newDropdown.style.pointerEvents = 'none';
                if (newArrow) {
                    newArrow.style.transform = 'rotate(0deg)';
                }
            }
        }
        
        if (dropdownName === 'profile' || dropdownName === 'all') {
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown) {
                profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            }
        }
        
        if (dropdownName === 'all') {
            activeDropdown = null;
        }
    }
    
    // Language Dropdown Function
    function initializeLanguageDropdown() {
        const toggle = document.getElementById('headerLanguageToggle2');
        const dropdown = document.getElementById('headerLanguageSubmenu2');
        const arrow = document.getElementById('langCaret');
        
        if (toggle && dropdown) {
            dropdown.style.pointerEvents = 'none';
            
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const isCurrentlyActive = activeDropdown === 'language';
                
                // Only close the new dropdown when language toggle is clicked
                closeDropdown('new');
                
                // Toggle the language dropdown
                if (isCurrentlyActive) {
                    closeDropdown('language');
                    activeDropdown = null;
                } else {
                    dropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]');
                    dropdown.style.pointerEvents = 'auto';
                    if (arrow) {
                        arrow.style.transform = 'rotate(180deg)';
                    }
                    activeDropdown = 'language';
                }
            });
        }
    }
    
    // New Button Dropdown Function
    function initializeNewDropdown() {
        const toggle = document.getElementById('newBtn');
        const dropdown = document.getElementById('newDropdown');
        const arrow = document.getElementById('uploadIcon');
        
        if (toggle && dropdown) {
            // Force reset and set initial state
            dropdown.style.cssText = '';
            dropdown.className = 'bg-[#2B2C61] absolute left-0 right-0 top-full mt-2 rounded-lg z-50';
            
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
            dropdown.style.transform = 'translateY(-10px)';
            dropdown.style.pointerEvents = 'none';
            dropdown.style.display = 'block';
            dropdown.style.transition = 'all 0.2s ease';
            
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isCurrentlyActive = activeDropdown === 'new';
                
                // Close both language and profile dropdowns when new button is clicked
                closeDropdown('language');
                closeDropdown('profile');
                
                // Toggle the new dropdown
                if (isCurrentlyActive) {
                    closeDropdown('new');
                    activeDropdown = null;
                } else {
                    dropdown.style.opacity = '1';
                    dropdown.style.visibility = 'visible';
                    dropdown.style.transform = 'translateY(0px)';
                    dropdown.style.pointerEvents = 'auto';
                    if (arrow) {
                        arrow.style.transform = 'rotate(180deg)';
                        arrow.style.transition = 'transform 0.2s ease';
                    }
                    activeDropdown = 'new';
                }
            });
            
            // Handle dropdown option clicks
            const uploadOption = document.getElementById('uploadFileOption');
            const folderOption = document.getElementById('createFolderOption');
            
            if (uploadOption) {
                uploadOption.addEventListener('click', function(e) {
                    closeDropdown('all');
                });
            }
            
            if (folderOption) {
                folderOption.addEventListener('click', function(e) {
                    closeDropdown('all');
                });
            }
        }
    }
    
    // Profile Dropdown Function
    function initializeProfileDropdown() {
        const toggle = document.getElementById('userProfileBtn');
        const dropdown = document.getElementById('profileDropdown');
        
        if (toggle && dropdown) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const isCurrentlyActive = activeDropdown === 'profile';
                
                // Close new dropdown when profile is clicked, but not language
                closeDropdown('new');
                
                // Toggle the profile dropdown
                if (isCurrentlyActive) {
                    closeDropdown('profile');
                    activeDropdown = null;
                } else {
                    dropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]');
                    activeDropdown = 'profile';
                }
            });
        }
    }
    
    // Global click handler to close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const toggle1 = document.getElementById('headerLanguageToggle2');
        const dropdown1 = document.getElementById('headerLanguageSubmenu2');
        const toggle2 = document.getElementById('newBtn');
        const dropdown2 = document.getElementById('newDropdown');
        const toggle3 = document.getElementById('userProfileBtn');
        const dropdown3 = document.getElementById('profileDropdown');
        
        const clickedInsideAnyDropdown = 
            (toggle1 && toggle1.contains(e.target)) ||
            (dropdown1 && dropdown1.contains(e.target)) ||
            (toggle2 && toggle2.contains(e.target)) ||
            (dropdown2 && dropdown2.contains(e.target)) ||
            (toggle3 && toggle3.contains(e.target)) ||
            (dropdown3 && dropdown3.contains(e.target));
        
        if (!clickedInsideAnyDropdown) {
            closeDropdown('all');
        }
    });
    
    // Initialize all dropdowns
    setTimeout(() => {
        initializeLanguageDropdown();
        initializeNewDropdown();
        initializeProfileDropdown();
    }, 100);
});
</script>

</html>
>>>>>>> origin/language-feature
