@extends('layouts.admin')

@section('content')
    {{-- Header --}}
    <header style="background-color: #141326;" class="col-span-2 flex items-center px-4 z-10">
        <div style="margin-bottom: 13px;" class="ml-4 flex items-center space-x-3 mr-10">
            <img src="{{ asset('logo-white.png') }}" alt="SecureDocs logo" class="w-8 h-8" style="margin-top:20px;">
            <div style="padding-right: 30px;" class="flex flex-col relative">
                <div class="text-white text-l font-bold">SECURE<span class="text-[#f89c00]">DOCS</span></div>
                <div class="absolute top-full text-xs text-gray-400">Administrator</div>
            </div>
        </div>

        {{-- Search Bar --}}
        <div style="margin-left: -5px; outline: none;" class="flex-grow max-w-[720px] relative pl-6">
            <div class="flex items-center">
                <div class="relative flex-1">
                    <img src="{{ asset('magnifying-glass.png') }}" alt="Search" class="absolute top-1/2 -translate-y-1/2 w-4 h-4" style="left: 18px;">
                    <form method="GET" action="{{ route('admin.users') }}" id="adminUserSearchForm" class="w-full">
                        <input type="text" id="adminUserSearch" name="q" value="{{ request('q') }}" placeholder="{{ __('auth.au_searchholder') }}"
                            class="w-full py-3 pl-12 pr-12 mt-4 mb-4 rounded-full border-none bg-[#3C3F58] text-base text-white focus:outline-none focus:shadow-md"
                            style="color: white; outline: none; padding-right: 20px;"
                            onfocus="this.style.setProperty('--placeholder-opacity', '0.5');"
                            onblur="this.style.setProperty('--placeholder-opacity', '0.5');">
                    </form>
                </div>
                <button type="button" 
                        id="smartClearButton"
                        class="clear-search-link ml-2 mt-4 mb-4 px-4 py-3 text-sm text-white rounded-full transition-all duration-100 ease-in whitespace-nowrap"
                        @if(!request('q')) disabled @endif>
                    {{ __('auth.au_clear') }}
                </button>
            </div>
        </div>

        {{-- Spacer --}}
        <div class="flex-grow"></div>

        {{-- Admin Profile/Logout --}}
        <div class="relative inline-block mr-2">
        <div id="userProfileBtn"
            class="w-10 h-10 rounded-full flex items-center justify-center text-xl mr-2 cursor-pointer transition"
            style="background-color: #3C3F58;"
            onmouseover="this.style.filter='brightness(1.1)';"
            onmouseout="this.style.filter='';">
            <img src="{{ asset('user-shape.png') }}" alt="Profile" class="w-6 h-6 object-contain">
        </div>
        <div id="profileDropdown"
        class="absolute top-[54px] right-0 w-[280px] bg-[#3C3F58] text-white rounded-lg shadow-lg z-50 overflow-hidden opacity-0 invisible translate-y-[-10px] transition-all duration-200">
            <div class="p-4 border-border-color flex items-center">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl mr-4 cursor-pointer transition"
                    style="background-color: #55597C;">
                    <img src="{{ asset('user-shape.png') }}" alt="Profile" class="w-6 h-6 object-contain">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-base font-medium mb-1">{{ Str::limit(trim((Auth::user()->firstname ?? '') . ' ' . (Auth::user()->lastname ?? '')), 20) }}</div>
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
                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="w-2 h-2 mr-2 transition-transform duration-200" id="langCaret"> 
                    </div> 
                    
                    <div id="headerLanguageSubmenu2" style="background-color: #3c3f58; border: 3px solid #1F1F33" class="absolute right-0 mr-4 top-full mt-2 w-[140px] rounded-lg shadow-xl overflow-hidden transition-all duration-200 opacity-0 invisible pointer-events-none translate-y-[-10px] z-50"> 
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
                <li class="h-px bg-gray-600 my-1"></li>
            </ul>
            <div class="mb-4 mt-4 border-border-color text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="bg-[#f89c00] px-8 text-black font-bold py-2 rounded-full cursor-pointer hover:brightness-110 transition">{{ __('auth.db_logout') }}</button>
                </form>
            </div>
        </div>
    </div>
    </header>

    {{-- Sidebar --}}
    <div id="adminSidebar" style="background-color: #141326;" class="py-4 overflow-y-auto">
        <div class="px-6 py-3 mb-4">
            <h2 class="text-lg font-semibold text-white">{{ __('auth.db_admin_menu') }}</h2>
        </div>
        <ul id="sidebar" class="mt-2">
            <a href="{{ route('admin.dashboard') }}"
                class="py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4">
                <img src="{{ asset('graph-bar.png') }}" alt="Dashboard" class="mr-4 w-5 h-5">
                <span class="text-sm">{{ __('auth.db_dashboard') }}</span>
            </a>
            <a class="activeTab py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4 {{ request()->routeIs('admin.users') ? 'activeTab' : '' }}">
                <img src="{{ asset('people.png') }}" alt="Manage Users" class="mr-4 w-5 h-5">
                <span class="text-sm">{{ __('auth.db_manage_users') }}</span>
            </a>
        </ul>
    </div>

    {{-- Main Content --}}
<main style="background-color: #24243B; border-top-left-radius: 32px; margin-left: 13px;" class="p-6">
    <h1 class="text-2xl font-semibold text-white mb-6">
        @if(request('q'))
            {{ __('auth.au_users_with') }} <span class="text-[#f89c00]">"{{ Str::limit(request('q'), 20) }}"</span>
        @else
            {{ __('auth.au_all_users') }}
        @endif
    </h1>

    @if (session('success'))
        <div style="background-color: #10B981;" class="text-white px-4 py-3 rounded-lg mb-4" role="alert">
            <strong class="font-bold">{{ __('auth.success_generic') }}</strong>
            <span class="block sm:inline">
                @if(is_array(session('success')))
                    {{ __(session('success')['key'], session('success')['params']) }}
                @else
                    {{ session('success') }}
                @endif
            </span>
        </div>
    @endif

    <!--
    {{-- Search bar for users --}}
    <form method="GET" action="{{ route('admin.users') }}" class="mb-4" id="adminUserSearchForm">
        <div class="flex items-center gap-2">
            <input type="text" id="adminUserSearch" name="q" value="{{ request('q') }}" placeholder="Search users by name or email" class="w-full max-w-md rounded-md border border-[#4A4D6A] bg-[#1F2235] text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Search</button>
            @if(request('q'))
                <a href="{{ route('admin.users') }}" class="px-3 py-2 text-sm text-gray-300 hover:text-white">Clear</a>
            @endif
        </div>
    </form> -->

    <div class="rounded-lg">
        <!-- Fixed header table -->
        <div style="overflow: hidden;">
            <table class="min-w-full" style="table-layout: fixed; width: 100%;">
            <thead>
                <tr style="background-color: #3C3F58; border-radius: 8px 8px 0 0;">
                    <th class="table-header" style="border-radius: 8px 0 0 0; width: 22%;">{{ __('auth.db_name') }}</th>
                    <th class="table-header" style="width: 26%;">{{ __('auth.db_email') }}</th>
                    <th class="table-header" style="width: 9%;">{{ __('auth.au_role') }}</th>
                    <th class="table-header" style="width: 6.5%; text-align: center;">{{ __('auth.au_approved') }}</th>
                    <th class="table-header" style="width: 11%;">{{ __('auth.db_plan') }}</th>
                    <th class="table-header" style="width: 12%; text-align:center; padding-left: 30px;">{{ __('auth.au_actions') }}</th>
                    <th class="table-header" style="border-radius: 0 8px 0 0; width: 10%; text-align: center; padding-right: 40px;">{{ __('auth.au_manage') }}</th>
                </tr>
            </thead>
            </table>
        </div>

        <!-- Scrollable body table -->
        <div class="table-body-container" style="max-height: 450px;  scrollbar-width: none; -ms-overflow-style: none;">
            <table class="min-w-full" style="table-layout: fixed; width: 100%;">
                <tbody style="border-top: 1px solid #3C3F58;">
                    @forelse ($users as $user)
                        <tr class="user-table-row" style="border-bottom: 1px solid #3C3F58;">
                            <td class="px-6 py-4 text-sm" style="color:#ffffff; width:22%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Str::limit(trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')), 28) }}</td>
                            <td class="px-6 py-4 text-sm" style="color:#ffffff; width:26%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Str::limit($user->email, 32) }}</td>
                            <td class="px-6 py-4 text-sm text-center" style="color:#ffffff; width:9%; text-transform:capitalize; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ ucfirst($user->role) }}</td>
                            <td class="px-6 py-4 text-sm text-center" style="width:6.5%; color:#ffffff;">@if($user->is_approved) {{ __('auth.au_yes') }} @else {{ __('auth.au_no') }} @endif</td>
                            <td class="px-6 py-4 text-sm text-center" style="width:11%; color:#ffffff;">@if($user->is_premium) {{ __('auth.db_premium') }} @else {{ __('auth.db_standard') }} @endif</td>
                            <td class="px-6 py-4 text-sm text-center" style="width:12%; padding-left:30px;">
                                @if(!$user->is_approved)
                                    <form method="POST" action="{{ route('admin.approve',$user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="approve-btn transition-all duration-200 ease-in-out">{{ __('auth.au_approve') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.revoke',$user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="revoke-btn transition-all duration-200 ease-in-out">{{ __('auth.au_revoke') }}</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right" style="width:10%; padding-right:40px;">
                                <div class="relative inline-block text-left">
                                    <!-- Toggle Button -->
                                    <button type="button" 
                                        class="manage-btn inline-flex items-center justify-center px-2 py-1 text-xs font-medium text-white rounded-md focus:outline-none transition-colors"
                                        style="min-width: auto;"
                                        id="manageAccounts-menu-{{ $user->id }}"
                                        aria-expanded="false"
                                        aria-haspopup="true">
                                        <img src="{{ asset('garage.png') }}" alt="Manage Accounts" style="margin-left: 10px; margin-right: 12px;" class="w-4 h-4">
                                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" style="margin-right: 12px;" class="w-2 h-2 transition-transform duration-200" id="manageAccounts-caret-{{ $user->id }}">
                                    </button>
                                    <!-- Dropdown Menu - Made narrower -->
                                    <div style="width: 170px; border: 3px solid #24243B;"" class="absolute right-0 mt-2 bg-[#3C3F58] text-white rounded-lg shadow-lg z-50 overflow-hidden opacity-0 invisible translate-y-[-10px] transition-all duration-200"
                                        id="manageAccounts-dropdown-{{ $user->id }}"
                                        role="menu"
                                        aria-orientation="vertical"
                                        aria-labelledby="manageAccounts-menu-{{ $user->id }}">
                                        <div class="py-0" role="none">
                                            <!-- Toggle Premium Button -->
                                            <button onclick="togglePremium({{ $user->id }}, '{{ trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) }}', {{ $user->is_premium ? 'true' : 'false' }})" 
                                                    class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-[#55597C] transition-colors rounded-t-lg"
                                                    style="border-radius: 8px 8px 0 0;"
                                                    role="menuitem">
                                                {{ $user->is_premium ? __('auth.au_remove_premium') : __('auth.au_grant_premium') }}
                                            </button>

                                            <!-- Reset Premium Button (Only show for premium users) -->
                                            @if($user->is_premium)
                                            <button onclick="resetPremium({{ $user->id }}, '{{ trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) }}')" 
                                                    class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-[#55597C] transition-colors rounded-none"
                                                    style="border-radius: 0;"
                                                    role="menuitem">
                                                {{ __('auth.au_reset_all_data') }}
                                            </button>
                                            @endif

                                            <!-- View Details Button -->
                                            <button onclick="viewPremiumDetails({{ $user->id }})" 
                                                    class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-[#55597C] transition-colors rounded-b-lg"
                                                    style="border-radius: 0 0 8px 8px;"
                                                    role="menuitem">
                                                {{ __('auth.au_view_details') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="pt-8 px-6 py-4 text-center text-sm" style="color: #ffffff;">{{ __('auth.au_no_users_found') }}</td>
                            </tr>
                        @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-10 mb-4 flex justify-center" id="usersPagination">
        {{ $users->onEachSide(1)->links('vendor.pagination.custom') }}
    </div>
</main>

<style>
    /* ===== SEARCH & CLEAR ===== */
    #adminUserSearch::placeholder {color: rgba(255, 255, 255, 0.5); opacity: 1;}
    .clear-search-link {background-color: transparent; transition: all 0.1s ease-in;}
    .clear-search-link:hover {background-color: #2B2C61;}
    .clear-search-link:disabled {opacity: 0.3; cursor: not-allowed; color: transparent !important;}
    .clear-search-link:disabled:hover {background-color: transparent !important; filter: none !important; cursor: default !important;}

    /* ===== SIDEBAR STYLES ===== */
    #sidebar>a{background:#141326!important;color:#fff!important;transition:filter .15s;}
    #sidebar>a:hover{filter:brightness(1.5)!important;}
    #sidebar>a.activeTab{background:#2B2C61!important;color:#fff!important;}
    #sidebar>a.activeTab:hover{filter:none!important;}
    #sidebar>a *{color:inherit!important;}

    /* ===== TABLE STYLES ===== */
    .user-table-row {transition: background-color 0.2s ease, color 0.2s ease;}
    .user-table-row:hover {background-color: #55597C !important;}
    .user-table-row:hover td {color: #FFFFFF !important;}
    .user-table-row td {padding-left: 24px;padding-right: 24px;}
    
    /* Fine-tune horizontal spacing for Role–Approved–Plan–Actions–Manage */
    .table-header {padding-left: 24px; padding-right: 24px; padding-top: 1rem; padding-bottom: 1rem; text-align: left;
        font-size: 0.75rem; color: #ffffff; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em;}
    .table-header:nth-child(4), .table-header:nth-child(5), .table-header:nth-child(6), .table-header:nth-child(7),
    .user-table-row td:nth-child(4), .user-table-row td:nth-child(5), .user-table-row td:nth-child(6), .user-table-row td:nth-child(7) {
        padding-left: 10px; padding-right: 10px;}
    .table-header:nth-child(3), .table-header:nth-child(4), .table-header:nth-child(5), .table-header:nth-child(6),
    .user-table-row td:nth-child(3), .user-table-row td:nth-child(4), .user-table-row td:nth-child(5), .user-table-row td:nth-child(6) {
        padding-left: 6px; padding-right: 6px; text-align: center;}
    .table-header:last-child, .user-table-row td:last-child {text-align: right; padding-right: 32px;}
    .table-header:nth-child(5), .user-table-row td:nth-child(5) {padding-right: 18px;}

    /* ===== TABLE CONTAINER ===== */
    .table-body-container::-webkit-scrollbar {display: none;}
    .table-body-container table td, .table-body-container table th {width: inherit;}
    
    /* ===== ACTION BUTTONS ===== */
    .approve-btn, .revoke-btn {font-weight: 600; padding: 6px 14px; border-radius: 8px; background-color: transparent; border: none;
        transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, filter 0.2s ease-in-out;}
    .approve-btn { color: #10B981; }
    .revoke-btn { color: #EF4444; }
    .approve-btn:hover {background-color: #10B981; color: #ffffff;}
    .revoke-btn:hover {background-color: #EF4444; color: #ffffff;}
    .user-table-row:hover .approve-btn {color: #ffffff;}
    .user-table-row:hover .revoke-btn {color: #ffffff;}
    .manage-btn {background-color: #3C3F58 !important;  transition: background-color 0.2s ease-in-out !important;
        padding-left: 8px !important; padding-right: 8px !important; min-width: auto !important;}
    .manage-btn:hover {background-color: #676C98 !important;}

    /* ===== TABLE CONTAINER ===== */
    #usersPagination .flex.items-center {gap: 5px;}
    #usersPagination a, #usersPagination span {display: flex; align-items: center; justify-content: center; min-width: 32px;
        height: 32px; border-radius: 6px; font-size: 14px; font-weight: 500; transition: all 0.2s ease-in-out;}
    #usersPagination a:hover {background-color: #55597C !important;}
    #usersPagination .bg-\[\#f89c00\] {color: #000000 !important; font-weight: 700;}
    #usersPagination .bg-\[\#3C3F58\] {color: #ffffff !important;}
    #usersPagination .text-gray-400 {opacity: 0.4; cursor: not-allowed;}
    #usersPagination img {display: block;}

    /* ===== DROPDOWN STYLES ===== */
    [id^="manageAccounts-dropdown-"] button:first-child {border-top-left-radius: 8px !important;
        border-top-right-radius: 8px !important; border-bottom-left-radius: 0 !important; border-bottom-right-radius: 0 !important;}
    [id^="manageAccounts-dropdown-"] button:last-child { border-bottom-left-radius: 8px !important;
        border-bottom-right-radius: 8px !important; border-top-left-radius: 0 !important; border-top-right-radius: 0 !important;}
    [id^="manageAccounts-dropdown-"] button:not(:first-child):not(:last-child) {border-radius: 0 !important;}
    [id^="manageAccounts-dropdown-"] button:hover {background-color: #55597C !important; width: 100% !important;}
</style>

<script>
    // =========================================================================
    // Smart Clear Button Logic
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        const smartClearButton = document.getElementById('smartClearButton');
        const searchInput = document.getElementById('adminUserSearch');
        const searchForm = document.getElementById('adminUserSearchForm');
        
        // Function to update Clear button state
        function updateClearButtonState() {
            const currentSearchValue = searchInput.value.trim();
            const hasSearchResults = window.location.search.includes('q=');
            
            // Enable Clear button only if there's text in search OR we're viewing search results
            if (currentSearchValue || hasSearchResults) {
                smartClearButton.disabled = false;
            } else {
                smartClearButton.disabled = true;
            }
        }
        
        if (smartClearButton && searchInput) {
            // Initial state check
            updateClearButtonState();
            
            smartClearButton.addEventListener('click', function() {
                // Don't do anything if button is disabled
                if (smartClearButton.disabled) return;
                
                const currentSearchValue = searchInput.value.trim();
                const hasSearchResults = window.location.search.includes('q=');
                
                // Condition 1: If searchbar is empty, do nothing (shouldn't happen due to disabled state)
                if (!currentSearchValue && !hasSearchResults) {
                    return; // Do nothing
                }
                
                // Condition 2: If searchbar has value but not submitted (no search results showing)
                if (currentSearchValue && !hasSearchResults) {
                    searchInput.value = ''; // Just clear the input field
                    searchInput.focus(); // Keep focus on search bar
                    updateClearButtonState(); // Disable the button after clearing
                }
                
                // Condition 3: If searchbar has value AND we're viewing search results
                if (hasSearchResults) {
                    window.location.href = "{{ route('admin.users') }}"; // Original behavior - show all users
                }
            });
            
            // Update button state when user types
            searchInput.addEventListener('input', function() {
                updateClearButtonState();
            });
            
            // Also clear input on Escape key press and update button state
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    updateClearButtonState();
                    // Don't trigger click if button is disabled
                    if (!smartClearButton.disabled) {
                        smartClearButton.click();
                    }
                }
            });
        }
    });

    // =========================================================================
    // 1. Dropdown Logic (FIXED for animation and z-index/overflow)
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        // List of all dropdown menus and their caret IDs
        const ALL_DROPDOWNS = [
            { menu: 'profileDropdown' },
            { menu: 'headerLanguageSubmenu2', caret: 'langCaret' },
        ];
        
        /**
         * Closes a specific dropdown menu by adding Tailwind transition classes.
         * @param {string} menuId - The ID of the menu to close.
         */
        function closeDropdown(menuId) {
            const d = ALL_DROPDOWNS.find(item => item.menu === menuId);
            if (!d) return;

            const menuEl = document.getElementById(menuId);
            const caretEl = d.caret ? document.getElementById(d.caret) : null;
            
            if (menuEl && !menuEl.classList.contains('invisible')) {
                // Apply classes to hide and reset transition state
                menuEl.classList.add('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-[-10px]');
                
                // Re-apply overflow-hidden to the profile dropdown when it closes
                if (menuId === 'profileDropdown') {
                    menuEl.classList.add('overflow-hidden');
                }
                
                // Reset the caret rotation
                if (caretEl) {
                    caretEl.classList.remove('rotate-180');
                }
                
                // If the profile dropdown closes, ensure the nested language submenu closes too
                if (menuId === 'profileDropdown') {
                    closeDropdown('headerLanguageSubmenu2');
                }
            }
        }

        /**
         * Toggles a single dropdown. Crucially, it only closes OTHER top-level menus.
         * @param {string} btnId - The ID of the button/toggle element.
         * @param {string} dropdownId - The ID of the dropdown menu element.
         */
        function toggleDropdown(btnId, dropdownId) {
            const btn = document.getElementById(btnId);
            const dropdown = document.getElementById(dropdownId);
            const d = ALL_DROPDOWNS.find(item => item.menu === dropdownId);
            const caret = d && d.caret ? document.getElementById(d.caret) : null;

            if (btn && dropdown) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isHidden = dropdown.classList.contains('invisible');

                    // 1. Close ALL unrelated menus.
                    if (dropdownId === 'profileDropdown') {
                        closeDropdown('headerLanguageSubmenu2');
                        closeAllManageAccountsDropdowns(); // Close manage accounts dropdowns when profile opens
                    } 
                    // If language is clicked, we do nothing here, as closing the parent profileDropdown is wrong.

                    // 2. Toggle the target dropdown
                    if (isHidden) {
                        // Open: Remove transform and visibility classes
                        dropdown.classList.remove('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-[-10px]');
                        
                        // Crucial fix: Remove overflow-hidden from the parent profileDropdown 
                        // so the nested language submenu can display outside its boundaries
                        if (dropdownId === 'profileDropdown') {
                            dropdown.classList.remove('overflow-hidden');
                        }

                        if (caret) {
                            caret.classList.add('rotate-180');
                        }
                    } else {
                        closeDropdown(dropdownId);
                    }
                });
            }
        }

        // Initialize all required dropdowns
        setTimeout(() => {
            // Profile Dropdown (Top Level)
            toggleDropdown('userProfileBtn', 'profileDropdown');
            
            // Language Toggle (NESTED inside Profile)
            toggleDropdown('headerLanguageToggle2', 'headerLanguageSubmenu2');
        }, 100);

        // =========================================================================
        // 2. Manage Accounts Dropdowns (formerly Premium Management)
        // =========================================================================
        function initializeManageAccountsDropdowns() {
            // Find all manage accounts toggle buttons
            const manageAccountsToggleButtons = document.querySelectorAll('button[id^="manageAccounts-menu-"]');
            
            manageAccountsToggleButtons.forEach(button => {
                const userId = button.id.replace('manageAccounts-menu-', '');
                const dropdown = document.getElementById(`manageAccounts-dropdown-${userId}`);
                const caret = document.getElementById(`manageAccounts-caret-${userId}`);
                
                if (button && dropdown) {
                    button.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isHidden = dropdown.classList.contains('invisible');

                        // Close all other dropdowns first (profile, language, and other manage accounts dropdowns)
                        closeDropdown('profileDropdown');
                        closeDropdown('headerLanguageSubmenu2');
                        closeAllManageAccountsDropdowns();

                        // Toggle current manage accounts dropdown
                        if (isHidden) {
                            // Open dropdown
                            dropdown.classList.remove('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-[-10px]');
                            if (caret) {
                                caret.classList.add('rotate-180');
                            }
                        } else {
                            closeManageAccountsDropdown(userId);
                        }
                    });
                }
            });
        }

        function closeManageAccountsDropdown(userId) {
            const dropdown = document.getElementById(`manageAccounts-dropdown-${userId}`);
            const caret = document.getElementById(`manageAccounts-caret-${userId}`);
            
            if (dropdown) {
                dropdown.classList.add('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-[-10px]');
            }
            
            if (caret) {
                caret.classList.remove('rotate-180');
            }
        }

        function closeAllManageAccountsDropdowns() {
            const manageAccountsDropdowns = document.querySelectorAll('[id^="manageAccounts-dropdown-"]');
            const manageAccountsCarets = document.querySelectorAll('[id^="manageAccounts-caret-"]');
            
            manageAccountsDropdowns.forEach(dropdown => {
                dropdown.classList.add('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-[-10px]');
            });
            
            manageAccountsCarets.forEach(caret => {
                caret.classList.remove('rotate-180');
            });
        }

        // Initialize manage accounts dropdowns
        initializeManageAccountsDropdowns();

        // Global listener to close dropdowns when clicking anywhere outside
        document.addEventListener('click', function(e) {
            // Check if click is inside any dropdown element
            const clickedInsideProfile = document.getElementById('userProfileBtn')?.contains(e.target) || 
                                       document.getElementById('profileDropdown')?.contains(e.target);
            const clickedInsideLanguage = document.getElementById('headerLanguageToggle2')?.contains(e.target) || 
                                        document.getElementById('headerLanguageSubmenu2')?.contains(e.target);
            const clickedInsideManageAccounts = e.target.closest('[id^="manageAccounts-menu-"]') || 
                                       e.target.closest('[id^="manageAccounts-dropdown-"]');

            if (!clickedInsideProfile && !clickedInsideLanguage && !clickedInsideManageAccounts) {
                closeDropdown('profileDropdown');
                closeDropdown('headerLanguageSubmenu2');
                closeAllManageAccountsDropdowns();
            }
        });

        // =========================================================================
        // 3. Predictive search for All Users (AJAX) - Keep this as is
        // =========================================================================
        const input = document.getElementById('adminUserSearch');
        const tableBody = document.querySelector('#allUsersTable tbody');
        const pagination = document.getElementById('usersPagination');
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        if (input && tableBody) {
            let timer = null; let currentPage = 1;

            const fetchUsers = async (page = 1) => {
                const q = input.value.trim();
                const url = new URL(`{{ route('admin.users.json') }}`, window.location.origin);
                url.searchParams.set('q', q); url.searchParams.set('page', page); url.searchParams.set('per_page', 15);
                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) return; const data = await res.json();
                renderRows(data.data || []); renderPagination(data.meta || {}); currentPage = data.meta?.current_page || 1;
            };

            const renderRows = (rows) => {
                if (!Array.isArray(rows) || rows.length === 0) { tableBody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No users found.</td></tr>`; return; }
                const html = rows.map(u => {
                    const approvedBadge = u.is_approved ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">No</span>';
                    const planBadge = u.is_premium ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Premium</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Standard</span>';
                    const approveForm = !u.is_approved ? `<form method=\"POST\" action=\"${u.urls.approve}\" class=\"inline\"><input type=\"hidden\" name=\"_token\" value=\"${csrf}\"><button type=\"submit\" class=\"text-indigo-600 hover:text-indigo-900\">Approve</button></form>` : `<form method=\"POST\" action=\"${u.urls.revoke}\" class=\"inline\"><input type=\"hidden\" name=\"_token\" value=\"${csrf}\"><button type=\"submit\" class=\"text-red-600 hover:text-red-900\">Revoke</button></form>`;
                    const premiumForm = `<form method=\"POST\" action=\"${u.urls.premium}\" class=\"space-y-1\"><input type=\"hidden\" name=\"_token\" value=\"${csrf}\"><div class=\"flex items-center\"><input type=\"checkbox\" name=\"is_premium\" ${u.is_premium ? 'checked' : ''} class=\"mr-1 h-3 w-3 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500\"><label class=\"text-xs\">Premium</label></div><button type=\"submit\" class=\"px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600\">Update</button></form>`;
                    return `<tr>
                        <td class="px-6 py-4 text-sm font-medium text-white" style="width:22%;">${escapeHtml(u.name)}</td>
                        <td class="px-6 py-4 text-sm text-gray-400" style="width:26%;">${escapeHtml(u.email)}</td>
                        <td class="px-6 py-4 text-sm text-center text-gray-400 capitalize" style="width:9%;">${escapeHtml(u.role ? u.role.charAt(0).toUpperCase() + u.role.slice(1) : '')}</td>
                        <td class="px-6 py-4 text-sm text-center" style="width:6.5%;">${approvedBadge}</td>
                        <td class="px-6 py-4 text-sm text-center" style="width:11%;">${planBadge}</td>
                        <td class="px-6 py-4 text-sm text-center font-medium" style="width:12%; padding-left:30px;">${approveForm}</td>
                        <td class="px-6 py-4 text-sm text-right font-medium" style="width:8%; padding-right:40px;">${premiumForm}</td>
                    </tr>`;
                }).join('');
                tableBody.innerHTML = html;
            };

            const renderPagination = (meta) => {
                if (!pagination) return; const current = meta.current_page || 1; const last = meta.last_page || 1;
                const prevDisabled = current <= 1 ? 'opacity-50 pointer-events-none' : '';
                const nextDisabled = current >= last ? 'opacity-50 pointer-events-none' : '';
                pagination.innerHTML = `<div class=\"flex items-center gap-2\"><button class=\"px-3 py-1 border rounded ${prevDisabled}\" data-page=\"${current - 1}\">Prev</button><span class=\"text-sm text-gray-600\">Page ${current} of ${last}</span><button class=\"px-3 py-1 border rounded ${nextDisabled}\" data-page=\"${current + 1}\">Next</button></div>`;
                pagination.querySelectorAll('button[data-page]').forEach(btn => { btn.addEventListener('click', (e) => { e.preventDefault(); const p = parseInt(btn.getAttribute('data-page'), 10); if (!Number.isNaN(p) && p >= 1 && p <= last) fetchUsers(p); }); });
            };

            const escapeHtml = (s) => (s || '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;').replace(/'/g,'&#039;');

            const triggerFetch = () => { fetchUsers(1).catch(()=>{}); };
            input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(triggerFetch, 300); });
    
    // =========================================================================
    // ADDED: Enter key support for the new search design
    // =========================================================================
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(timer); // Clear any pending input timeout
            triggerFetch();
        }
    });
    
    const form = document.getElementById('adminUserSearchForm');
    form && form.addEventListener('submit', (e) => { e.preventDefault(); triggerFetch(); });
        }
    });

    // =========================================================================
    // 4. Premium Management Functions (function names remain the same)
    // =========================================================================
    function togglePremium(userId, userName, isPremium) {
        closeAllManageAccountsDropdowns(); // Close dropdown first
        const action = isPremium ? 'remove premium from' : 'grant premium to';
        if (confirm(`Are you sure you want to ${action} ${userName}?`)) {
            fetch(`/admin/users/${userId}/toggle-premium`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to update the UI
                } else {
                    alert('Error: ' + (data.message || 'Something went wrong'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    }

    function resetPremium(userId, userName) {
        closeAllManageAccountsDropdowns(); // Close dropdown first
        if (confirm(`Are you sure you want to COMPLETELY RESET all premium data for ${userName}? This will delete all their subscriptions and payments. This action cannot be undone.`)) {
            fetch(`/admin/users/${userId}/reset-premium`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to update the UI
                } else {
                    alert('Error: ' + (data.message || 'Something went wrong'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    }

    function viewPremiumDetails(userId) {
        closeAllManageAccountsDropdowns(); // Close dropdown first
        fetch(`/admin/users/${userId}/premium-details`)
            .then(response => response.json())
            .then(data => {
                let details = `Premium Details for ${data.user.name}:\n\n`;
                details += `Status: ${data.user.is_premium ? 'Premium' : 'Standard'}\n\n`;
                
                if (data.subscriptions.length > 0) {
                    details += 'Subscriptions:\n';
                    data.subscriptions.forEach(sub => {
                        details += `- ${sub.plan_name} (${sub.status}) - ${sub.amount}\n`;
                        details += `  From ${sub.starts_at} to ${sub.ends_at}\n`;
                    });
                    details += '\n';
                }
                
                if (data.payments.length > 0) {
                    details += 'Recent Payments:\n';
                    data.payments.slice(0, 5).forEach(payment => {
                        details += `- ${payment.amount} via ${payment.payment_method} (${payment.status}) - ${payment.created_at}\n`;
                    });
                }
                
                if (data.subscriptions.length === 0 && data.payments.length === 0) {
                    details += 'No subscription or payment history found.';
                }
                
                alert(details);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading premium details.');
            });
    }

    // Helper function to close all manage accounts dropdowns
    function closeAllManageAccountsDropdowns() {
        const manageAccountsDropdowns = document.querySelectorAll('[id^="manageAccounts-dropdown-"]');
        const manageAccountsCarets = document.querySelectorAll('[id^="manageAccounts-caret-"]');
        
        manageAccountsDropdowns.forEach(dropdown => {
            dropdown.classList.add('opacity-0', 'invisible', 'pointer-events-none', 'translate-y-[-10px]');
        });
        
        manageAccountsCarets.forEach(caret => {
            caret.classList.remove('rotate-180');
        });
    }
</script>
@endsection
