@extends('layouts.app')

@section('content')
<div class = "hidden" data-page="user-dashboard"></div>


<header class="col-span-2 flex items-center px-4 bg-[#141326] z-10 h-18">
    <div style="margin-bottom: 13px;" class="ml-4 flex items-center space-x-3 mr-10">
        <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-8 h-8" style="margin-top:20px;">
        <div style="padding-right: 30px;" class="flex flex-col relative">
            <div class="text-white text-l font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></div>
            <div class="absolute top-full text-xs text-gray-400">
                {{ auth()->user()->is_premium ? __('auth.db_premium') : __('auth.db_standard') }}
            </div>
        </div>
    </div>

    <div style="margin-left: -5px; outline: none;" class="flex-grow max-w-[720px] relative pl-6 flex items-center gap-2">
        <div class="relative flex-1">
            <img src="{{ asset('magnifying-glass.png') }}" alt="Search" class="absolute top-1/2 -translate-y-1/2 w-4 h-4" style="left: 18px;">
            <input type="text" id="mainSearchInput" placeholder="{{ __('auth.db_search') }}"
                class="w-full py-3 pl-12 pr-12 mt-4 mb-4 rounded-full border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md"
                style="color: white; outline: none; padding-right: 20px;"
                onfocus="this.style.setProperty('--placeholder-opacity', '0.5');"
                onblur="this.style.setProperty('--placeholder-opacity', '0.5');">
        </div>
        <button id="advanced-search-button" 
                class="mt-4 mb-4 px-4 py-3 bg-[#3C3F58] hover:bg-[#55597C] text-white rounded-full text-sm font-medium transition-colors flex items-center gap-2"
                title="Advanced Search">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
            <span>Advanced</span>
        </button>
    </div>

    <div class="flex items-center ml-auto gap-4">
        <!-- Bundlr Wallet Widget (Premium Only) -->
        @if(auth()->user()->is_premium)
        <div class="relative">
            <button id="bundlrWalletBtn" class="flex items-center gap-2 px-3 py-2 bg-[#3C3F58] hover:bg-[#55597C] rounded-lg transition-colors text-white text-sm" title="Bundlr Wallet">
                <div class="w-6 h-6 rounded-full bg-orange-500 flex items-center justify-center text-xs font-bold">
                    B
                </div>
                <div class="hidden md:block">
                    <div class="text-xs text-gray-400">Bundlr</div>
                    <div id="walletBalance" class="text-xs font-medium">Click to Initialize</div>
                </div>
            </button>
            
            <!-- Bundlr Wallet Dropdown -->
            <div id="bundlrWalletDropdown" class="hidden absolute right-0 mt-3 w-80 bg-[#1F2235] text-gray-100 rounded-lg shadow-xl border border-[#4A4D6A] z-50">
                <div class="px-4 py-3 border-b border-[#4A4D6A]">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium">Bundlr Wallet</div>
                        <button id="initializeBundlrBtn" class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 rounded text-sm transition-colors">
                            Initialize
                        </button>
                    </div>
                    <div class="text-xs text-gray-400" id="walletStatus">Click initialize to connect</div>
                </div>
                <div class="p-4 space-y-3">
                    <!-- Balance Display -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm">Balance:</span>
                        <span id="walletBalanceDetail" class="text-sm font-medium text-green-400">-- MATIC</span>
                    </div>
                    
                    <!-- Actions -->
                    <div class="space-y-2">
                        <div class="text-xs text-gray-400 mb-2">
                            Need MATIC in your wallet to fund Bundlr
                        </div>
                        <div class="flex gap-2">
                            <select id="fundAmountSelect" class="flex-1 px-3 py-2 bg-[#3C3F58] rounded text-sm">
                                <option value="0.01">0.01 MATIC</option>
                                <option value="0.05">0.05 MATIC</option>
                                <option value="0.1">0.1 MATIC</option>
                            </select>
                            <button id="fundBundlrBtn" class="px-3 py-2 bg-green-600 hover:bg-green-700 rounded text-sm transition-colors" disabled>
                                Fund
                            </button>
                        </div>
                        <button id="refreshBalanceBtn" class="w-full px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded text-sm transition-colors" disabled>
                            Refresh Balance
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <div class="relative">
            <button id="notificationBell" class="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer hover:bg-[#3C3F58] transition-colors" title="Notifications" aria-label="Notifications">
            <img src="{{ asset('notifications.png') }}" alt="Notifications" class="w-6 h-6 object-contain">
            </button>
            <span id="notificationBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none px-1.5 py-0.5 rounded-full">0</span>
            
            <!-- Notification Dropdown -->
            <div id="notificationDropdown" class="hidden absolute right-0 mt-3 w-80 bg-[#1F2235] text-gray-100 rounded-lg shadow-xl border border-[#4A4D6A] z-50">
                <div class="px-4 py-3 border-b border-[#4A4D6A] flex items-center justify-between">
                    <div class="text-sm font-medium">Notifications</div>
                    <button id="markAllRead" class="text-xs px-2 py-1 rounded bg-[#2A2D47] hover:bg-[#3C3F58]">Mark all read</button>
                </div>
                <div id="notificationsList" class="max-h-80 overflow-auto">
                    <div class="p-4 text-center text-gray-400">Loading...</div>
                </div>
                <div class="px-4 py-2 border-t border-[#4A4D6A] flex items-center justify-between">
                    <button id="deleteAllNotifications" class="flex items-center gap-1 text-xs px-2 py-1 rounded bg-red-600 hover:bg-red-700 text-white transition-colors" title="Delete all notifications">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete all
                    </button>
                    <a id="viewAllNotifications" href="#" class="text-xs text-blue-400 hover:text-blue-300">View all</a>
                </div>
            </div>
        </div>
        
        <div class="relative inline-block mr-2">
            <div id="userProfileBtn"
                class="w-10 h-10 rounded-full flex items-center justify-center text-xl mr-2 cursor-pointer transition"
                style="background-color: #3C3F58;"
                onmouseover="this.style.filter='brightness(1.1)';"
                onmouseout="this.style.filter='';">
                <img src="{{ asset('user-shape.png') }}" alt="Profile" class="w-6 h-6 object-contain">
            </div>
            <div id="profileDropdown" 
                class="absolute top-[54px] right-0 w-[280px] bg-[#3C3F58] text-white rounded-lg shadow-lg z-50 overflow-hidden opacity-0 invisible translate-y-[-10px] scale-95 transition-all duration-200">
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
                        <span class="text-sm">{{ __('auth.db_profile_settings') }}</span>
                        </a>
                    </li>


                    <li>
                        <a href="{{ route('webauthn.index') }}"
                        class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/fingerprint.png" class="mr-4 w-4 h-4 ml-1" alt="Biometric Login">
                        <span class="text-sm">{{ __('auth.db_biometrics') }}</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('profile.sessions') }}"
                        class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/shield.png" class="mr-4 w-4 h-4 ml-1" alt="Account Security">
                        <span class="text-sm">Account Security</span>
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
                                <span class="text-sm">{{ __('auth.db_language') }}</span> 
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
                    <li>
                        <a href="{{ route('profile.faq') }} " 
                        class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/info.png" class="mr-4 w-4 h-4 ml-1" alt="Security & Privacy">
                        <span class="text-sm">{{ __('auth.db_help_support') }}</span>
                        </a>
                    </li>
                    <!--
                    <li class="p-4 flex items-center cursor-pointer"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/pencil.png" class="mr-4 w-4 h-4 ml-1" alt="Security & Privacy">
                        <span class="text-sm">{{ __('auth.db_send_feedback') }}</span>
                    </li>
                    <li class="h-px bg-gray-600 my-1 ml-4 mr-4"></li>
                    -->
                    <li class="flex items-center px-4 py-2 hover:bg-[#3C3F58] cursor-pointer rounded-lg mx-2 transition-colors duration-200"
                        onclick="window.location.href='{{ route('premium.upgrade') }}'"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <img src="/crown.png" class="mr-4 w-4 h-4 ml-1" alt="Premium Upgrade">
                        <span class="text-sm">{{ __('auth.db_buy_premium') }}</span>
                    </li>
                    <li class="h-px bg-gray-600 my-1 ml-4 mr-4"></li>
                </ul>
                <div class="pt-2 mt-2 border-border-color text-center">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="bg-[#f89c00] px-8 text-black font-bold py-2 rounded-full cursor-pointer hover:brightness-110 transition">{{ __('auth.db_logout') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<body>



<div id="overlay" class="fixed inset-0 bg-transparent z-[5] hidden"></div>

<div id="uploadModal" class="fixed inset-0 z-50 flex  items-center justify-center hidden text-white z-[1001]">
    <div style="background-color: #141326; opacity: 0.8;" class="fixed inset-0 transition-opacity" id="modalBackdrop"></div>
    <div style="background-color: #24243B;"
    class="rounded-lg shadow-xl w-full max-w-md p-6 relative z-10 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-medium text-white text-text-main">Upload New File</h3>
            <button id="closeModalBtn" class="close-button text-text-secondary hover:text-white text-2xl focus:outline-none">
                <img src="/close.png" class="mr-2 w-3 h-3" alt="Close">
            </button>
        </div>

        <div class="space-y-6">
            <div id="dropZone" style="border-width: 3px;"
                class="dropzone-border border-dashed rounded-lg p-8 text-center cursor-pointer">
                <div id="dropZoneContent" class="flex flex-col items-center">
                    <div class="dropzone-img text-3xl mb-4">
                        <img src="/file.png" alt="File" class="opacity-50 w-12 h-12">
                    </div>
                    <p class="dropzone-text text-sm mb-1">Drag and drop files here or click to browse</p>
                    <p class="dropzone-text text-xs"> Maximum file size: 100MB</p>
                </div>
                <input type="file" id="fileInput" class="hidden" multiple>
            </div>

            

            <div id="fileList"></div>
            <!-- Processing Options (Standard / Premium) -->
            <div id="processingOptions" class="space-y-4" style="display: none;">
                <div class="text-sm font-medium">Processing Options</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Standard -->
                    <label class="cursor-pointer block">
                        <div class="rounded-lg border border-[#3C3F58] bg-[#1F2235] p-3 flex items-start gap-3">
                            <input id="standardUpload" type="radio" name="processingType" value="standard" class="mt-1" checked>
                            <div>
                                <div class="text-sm text-white font-medium">Standard Upload</div>
                                <div class="text-xs text-gray-400">Store file in Supabase storage</div>
                            </div>
                        </div>
                    </label>


                    <!-- Vectorize (Premium) -->
                    <label class="cursor-pointer block" data-premium-option="true">
                        <div class="rounded-lg border border-[#3C3F58] bg-[#1F2235] p-3 flex items-start gap-3 @if(!auth()->user()->is_premium) opacity-60 cursor-not-allowed @else hover:border-[#f89c00] @endif" 
                             @if(!auth()->user()->is_premium) onclick="showPremiumUpgradeModal('ai')" @endif>
                            <input id="vectorizeUpload" type="radio" name="processingType" value="vectorize" class="mt-1" @if(!auth()->user()->is_premium) disabled @endif>
                            <div>
                                <div class="text-sm text-white font-medium flex items-center gap-2">
                                    AI Vectorize
                                    @if(!auth()->user()->is_premium)
                                        <span id="badgeVectorize" class="text-[10px] px-2 py-0.5 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-white">PREMIUM</span>
                                    @endif
                                </div>
                                <div id="descVectorize" class="text-xs text-gray-400">
                                    @if(auth()->user()->is_premium)
                                        Process with AI for advanced search capabilities
                                    @else
                                        Process with AI for advanced search (Premium required) - Click to upgrade
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>

                </div>
                <div id="processingValidation" class="hidden mt-2"></div>
            </div>
            <div id="uploadProgress" class="hidden">
                <div class="flex justify-between text-sm mb-1">
                    <span>Uploading...</span>
                    <span id="progressPercentage">0%</span>
                </div>
                <div class="w-full rounded-full h-2">
                    <div id="progressBar" class="bg-primary h-2 rounded-full" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <button id="cancelUploadBtn"
            class="cancel-button py-2 px-4 rounded text-sm">Cancel</button>
            <button id="uploadBtn"
            class="confirm-button py-2 px-4 rounded text-sm" disabled>Upload</button>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div id="createFolderModal" class="fixed inset-0 z-50 flex items-center justify-center hidden text-white">
    <div style="background-color: #141326; opacity: 0.8;" class="fixed inset-0 transition-opacity"></div>
    <div style="background-color: #24243B;"
    class="rounded-lg shadow-xl w-full max-w-md p-6 relative z-10 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-medium text-white text-text-main">Create New Folder</h3>
            <button id="closeCreateFolderModalBtn" class="close-button text-text-secondary hover:text-white text-2xl focus:outline-none">
                <img src="/close.png" class="mr-2 w-3 h-3" alt="Close">
            </button>
        </div>
        <form id="createFolderForm">
            <div class="mb-4">
                <label for="newFolderNameInput" class="block text-sm font-medium mb-2" style="color: #9CA3AF;">Folder Name</label>
                <input type="text" id="newFolderNameInput" name="newFolderName"
                    class="w-full py-2 px-3 rounded-lg border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md placeholder-gray-400"
                    placeholder="Enter here" required>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="cancelCreateFolderBtn"
                class="cancel-button py-2 px-4 rounded text-sm">Cancel</button>
                <button type="submit"
                class="confirm-button py-2 px-4 rounded text-sm">Create Folder</button>
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
     style="background-color: #3c3f58;">
        <img src="{{ asset('add.png') }}" alt="Add" class="mr-3 w-3 h-3">
        <span class="text-sm text-white font-medium">{{ __('auth.db_upload') }}</span>
        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="ml-auto w-2 h-2" id="uploadIcon">
    </div>

    <!-- Dropdown Menu - Simplified Structure -->
    <div id="newDropdown" 
     class="absolute left-0 right-0 top-full mt-2 rounded-lg z-50 bg-[#55597C] hidden opacity-0 invisible translate-y-[-10px] transition-all duration-200"
     style="background-color: #55597C;">
        <div>
            <div id="uploadFileOption"
                class="flex items-center px-5 py-4 text-sm transition-colors text-white"
                onmouseover="this.style.cssText = 'background-color: #55597C; border-radius: 0.5rem 0.5rem 0 0;';"
                onmouseout="this.style.cssText = 'border-radius: 0.5rem 0.5rem 0 0;';">
                <img src="{{ asset('file.png') }}" alt="File" class="mr-4 w-4 h-4">
                <span class="font-medium">{{ __('auth.db_new_file') }}</span>
            </div>
            
            @if(auth()->user()->is_premium)
            <div id="openClientArweaveBtn"
                class="flex items-center px-5 py-4 text-sm transition-colors text-white cursor-pointer"
                onclick="openClientArweaveModal()"
                onmouseover="this.style.cssText = 'background-color: #55597C;';"
                onmouseout="this.style.cssText = '';">
                <span class="mr-4 text-lg">🚀</span>
                <div class="flex-1">
                    <div class="font-medium">Arweave Storage </div>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gradient-to-r from-blue-500 to-green-500 text-white">NEW</span>
            </div>
            @endif
            
            <div id="createFolderOption"
                class="flex items-center px-5 py-4 text-sm transition-colors text-white"
                onmouseover="this.style.cssText = 'background-color: #55597C; border-radius: 0 0 0.5rem 0.5rem;';"
                onmouseout="this.style.cssText = 'border-radius: 0 0 0.5rem 0.5rem;';">
                <img src="{{ asset('folder-closed-black-shape.png') }}" alt="File" class="mr-4 w-4 h-4">
                <span class="font-medium">{{ __('auth.db_new_folder') }}</span>
            </div>
        </div>
    </div>
</div>

     <ul id="sidebar" class="mt-4">
        <li id="my-documents-link" 
            class="py-3 px-8 flex items-center cursor-pointer rounded-r-2xl mr-4 bg-primary">
            <img src="{{ asset('folder-white-shape.png') }}" alt="Documents" class="mr-4 w-5 h-5">
            <span class="text-sm">{{ __('auth.db_my_documents') }}</span>
        </li>


        <li id="trash-link" 
            class="py-3 px-8 flex items-center cursor-pointer rounded-r-2xl mr-4 
            bg-[#141326] text-white hover:brightness-110 active:bg-[#2B2C61]">
            <img src="{{ asset('delete.png') }}" alt="Trash" class="mr-4 w-5 h-5">
            <span class="text-sm">{{ __('auth.db_trash') }}</span>
        </li>
        <li id="blockchain-storage-link" class="py-3 px-8 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light @if(!auth()->user()->is_premium) opacity-60 @endif"
            @if(!auth()->user()->is_premium) onclick="showPremiumUpgradeModal('blockchain')" @endif>
            <img src="{{ asset('link-symbol.png') }}" alt="Blockchain" class="mr-4 w-5 h-5">
            <span class="text-white text-sm">{{ __('auth.db_blockchain_storage') }}</span>
            
            @if(!auth()->user()->is_premium)
                <span class="ml-auto px-2 py-1 text-xs bg-gradient-to-r from-purple-500 to-pink-500 rounded-full text-white font-medium">PREMIUM</span>
            @endif
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


    </ul>

    <!-- Storage Usage Display -->
    <div id="storageUsageContainer" class="mt-8 px-6">
        <div class="w-full h-2 bg-gray-600 rounded overflow-hidden">
            <div id="storageProgressBar" class="h-full transition-all duration-300 rounded" style="width: 0%; background-color: #3C3F58;"></div>
        </div>
        <div id="storageUsageText" class="text-xs text-gray-300 mt-2">Loading storage usage...</div>
        <div id="upgradePrompt" class="hidden mt-2 p-2 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg text-xs">
            <div class="flex items-center justify-between">
                <span class="text-white font-medium">Storage limit reached!</span>
                <button id="upgradeBtn" class="bg-white text-purple-600 px-2 py-1 rounded text-xs font-bold hover:bg-gray-100 transition-colors">
                    Upgrade to Premium
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<main style="background-color: #24243B; border-top-left-radius: 32px; margin-left: 13px;" class="p-6 overflow-y-auto">
    <input type="hidden" id="currentFolderId" value="">
    <div id="breadcrumbsContainer"class="mt-2 mb-8 text-sm text-white flex items-center justify-between">
        
        <!-- Breadcrumbs will be populated by JavaScript -->
        <div id="breadcrumbsDropdown" class="relative hidden">
            <button id="breadcrumbsMenuBtn" class="flex items-center justify-center w-12 h-12 rounded-full hover:bg-gray-100 transition-colors mr-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"></path>
                </svg>
            </button>
            <div id="breadcrumbsDropdownMenu" class="absolute top-full left-0 mt-1 bg-[#1F2235] border border-[#4A4D6A] rounded-lg shadow-lg z-50 min-w-[200px] hidden">
                <!-- Hidden breadcrumb items will be populated here -->
            </div>
        </div>

        <div id="breadcrumbsPath" class="flex items-center">
            <!-- Visible breadcrumb path will be populated here -->
        </div>

        <!-- Move view toggle buttons here -->
        <div id="viewToggleBtns" class="flex gap-2 ml-auto">
            <button id="btnGridLayout" data-view="grid" title="Grid view" aria-label="Grid view"
            class="view-toggle-btn active py-2 px-4 border rounded text-sm">
                <span><img src="{{ asset('grid.png') }}" alt="Documents" class="w-4 h-4"></span>
            </button>
            <button id="btnListLayout" data-view="list" title="List view" aria-label="List view"
            class="view-toggle-btn py-2 px-4 border rounded text-sm">
                <span><img src="{{ asset('list.png') }}" alt="Documents" class="w-4 h-4"></span>
            </button>
        </div>

    </div>
    <!-- <h1 id="header-title" class="text-2xl text-white font-bold mb-6">My Documents</h1> -->

    <!-- JS Localization -->
    <div id="db-js-localization-data" class="hidden" data-my-documents="{{ __('auth.db_my_documents') }}"></div>
    <!-- Hidden file input for uploading new versions -->
    <input type="file" id="newVersionInput" class="hidden" />
    <script>
        window.I18N = window.I18N || {};
        // Read the localized text from the hidden HTML element
        const localData = document.getElementById('db-js-localization-data');
        if (localData) {
            window.I18N.dbMyDocuments = localData.getAttribute('data-my-documents');
        }
    </script>

    <style>
    /* Search placeholder text - set for lower opacity */
    #mainSearchInput::placeholder, #newFolderNameInput::placeholder {
        color: rgba(255, 255, 255, 0.5);
        opacity: 1;
    }

    /* Grid View/List View button */
    .view-toggle-btn {
        background-color: #3C3F58 !important;
        border-color: #55597C !important;
        /* color: #9CA3AF; */
        transition: filter 0.2s ease;
    }
    .view-toggle-btn:hover {
        filter: brightness(1.1);
    }
    .view-toggle-btn.active {
        background-color: #55597C !important;
        border-color: #6B7280 !important;
        color: #FFFFFF !important;
    }

    /* /close.png */
    .close-button img, .dropzone-img img {
        filter: brightness(0) saturate(100%) invert(30%) sepia(10%) saturate(1200%) hue-rotate(210deg) brightness(120%);
        transition: filter 0.2s ease;
    }
    .close-button:hover img, .dropzone-border:hover .dropzone-img img {
        filter: brightness(0) saturate(100%) invert(30%) sepia(10%) saturate(1200%) hue-rotate(210deg) brightness(150%); 
    }

    /* Upload dropzone */
    .dropzone-border, .dropzone-text {
        border-color: #605a80; color: #605a80; font-weight: 500;
        transition: border-color 0.2s ease, color 0.2s ease;
    }
    .dropzone-border:hover, .dropzone-border:hover .dropzone-text {
        border-color: #776f9e; color: #776f9e; font-weight: 500;
    }

    /* Confirm button - New Modals */
    .confirm-button {
        background-color: #f89c00; color: black; font-weight: 600;
        transition: filter 0.2s ease;
    }
    .confirm-button:hover {
        filter: brightness(110%);
    }

    /* Cancel button - New Modals */
    .cancel-button {
        background-color: #3C3F58; color: rgba(255, 255, 255, 0.5);; font-weight: 400;
        transition: background-color 0.2s ease;
    }
    .cancel-button:hover {
        background-color: #55597C;
    }
    </style>
<!--
    <script>
        // View toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const gridBtn = document.getElementById('btnGridLayout');
            const listBtn = document.getElementById('btnListLayout');
            
            function setActiveButton(activeBtn, inactiveBtn) {
                // Remove active from both first
                gridBtn.classList.remove('active');
                listBtn.classList.remove('active');
                // Then add to the active one
                activeBtn.classList.add('active');
            }
            
            function showGridView() {
                setActiveButton(gridBtn, listBtn);
                // Add your grid view logic here
                const filesContainer = document.getElementById('filesContainer');
                if (filesContainer) {
                    filesContainer.className = 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4';
                }
            }
            
            function showListView() {
                setActiveButton(listBtn, gridBtn);
                // Add your list view logic here
                const filesContainer = document.getElementById('filesContainer');
                if (filesContainer) {
                    filesContainer.className = 'space-y-2';
                }
            }
            
            // Grid button click handler
            if (gridBtn) {
                gridBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showGridView();
                });
            }
            
            // List button click handler
            if (listBtn) {
                listBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showListView();
                });
            }
            
            // Initialize with grid view active (default)
            showGridView();
        });
    </script>
-->
    <div id="filesContainer" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <div class="p-4 text-center text-text-secondary col-span-full">Loading files...</div>
    </div>

    <style>
    .file-card, .file-row {
        background-color: #24243B !important;
        border-style: solid !important;
        border-width: 2px !important;
        border-color: #3C3F58 !important;
    }
    .file-card.bg-white, .file-card[class*="bg-"], .file-card.bg-white, .file-card[class*="bg-"] {
        background-color: #24243B !important;
    }
    .file-card:hover, .file-row:hover {
        background-color: #3C3F58 !important;
        border-style: solid !important;
        border-width: 2px !important;
        border-color: #55597C !important;
    }
    </style>
    <!--  Reserved colors: 3C3F58, 24243B
      .file-card:hover {background-color: #24243B !important;}
    -->

</main>

<!-- N8N Chat floating in bottom-right -->
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
            
            <!-- Search Options -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-[#1F2235] rounded-lg">
                <div>
                    <label class="block text-sm font-medium mb-2">Match Type</label>
                    <select id="searchMatchType" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white">
                        <option value="contains">Contains (default)</option>
                        <option value="exact">Exact Match</option>
                        <option value="starts_with">Starts With</option>
                        <option value="ends_with">Ends With</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Case Sensitivity</label>
                    <select id="searchCaseSensitive" class="w-full py-2 px-3 rounded bg-[#3C3F58] border-none text-white">
                        <option value="insensitive">Case Insensitive (Aa = aa)</option>
                        <option value="sensitive">Case Sensitive (Aa ≠ aa)</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="searchWholeWord" class="w-4 h-4 rounded bg-[#3C3F58] border-gray-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm">Match Whole Words Only</span>
                    </label>
                </div>
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
    </div>
</div>

<!-- Version History Modal - REMOVED -->

<!-- Premium Upgrade Modal -->
<div id="premiumUpgradeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6 max-w-md mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-gradient-to-r from-[#f89c00] to-[#ff8c00] rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">👑</span>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Premium Feature</h3>
            <p id="premiumModalText" class="text-gray-400 mb-6">
                This feature requires a Premium subscription to access advanced capabilities.
            </p>
            <div class="space-y-3">
                <button onclick="window.location.href='{{ route('premium.upgrade') }}'" 
                        class="w-full bg-[#f89c00] hover:bg-[#e88900] text-white py-3 px-6 rounded-lg font-bold transition-colors">
                    Upgrade to Premium
                </button>
                <button onclick="window.closePremiumModal()" 
                        class="w-full bg-[#3C3F58] hover:bg-[#4A4D6A] text-white py-3 px-6 rounded-lg transition-colors">
                    Maybe Later
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Pass user premium status to JavaScript
    window.userIsPremium = {{ auth()->user()->is_premium ? 'true' : 'false' }};
    
    // Pass user data to JavaScript
    window.authUser = {
        id: {{ auth()->user()->id }},
        name: "{{ auth()->user()->name }}",
        email: "{{ auth()->user()->email }}",
        is_premium: {{ auth()->user()->is_premium ? 'true' : 'false' }}
    };
</script>


<!-- Include Client-Side Arweave Modal -->
@include('modals.client-arweave-modal')

@endsection

@push('scripts')
    @vite(['resources/js/dashboard.js'])
@endpush

</body>

<!-- Inline scripts moved to modules/ui.js for better maintainability -->

</html>