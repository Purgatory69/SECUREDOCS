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
            <h2 class="text-lg font-semibold text-white">Admin Menu</h2>
        </div>
        <ul id="sidebar" class="mt-2">
        <a href="{{ route('admin.dashboard') }}"
            class="py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4">
                <img src="{{ asset('graph-bar.png') }}" alt="Dashboard" class="mr-4 w-5 h-5">
                <span class="text-sm">Dashboard</span>
            </a>
            <a class="activeTab py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4 {{ request()->routeIs('admin.users') ? 'activeTab' : '' }}">
                <img src="{{ asset('people.png') }}" alt="Manage Users" class="mr-4 w-5 h-5">
                <span class="text-sm">Manage Users</span>
            </a>

            <style>
                /* Sidebar styles */
                #sidebar>a{background:#141326!important;color:#fff!important;transition:filter .15s;}
                #sidebar>a:hover{filter:brightness(1.5)!important;}
                #sidebar>a.activeTab{background:#2B2C61!important;color:#fff!important;}
                #sidebar>a.activeTab:hover{filter:none!important;}
                #sidebar>a *{color:inherit!important;}
            </style>
        </ul>
    </div>

    {{-- Main Content --}}
<main style="background-color: #24243B; border-top-left-radius: 32px; margin-left: 13px;" class="p-6">
    <h1 class="text-2xl font-semibold text-white mb-6">All Users</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Search bar for users --}}
    <form method="GET" action="{{ route('admin.users') }}" class="mb-4" id="adminUserSearchForm">
        <div class="flex items-center gap-2">
            <input type="text" id="adminUserSearch" name="q" value="{{ request('q') }}" placeholder="Search users by name or email" class="w-full max-w-md rounded-md border border-[#4A4D6A] bg-[#1F2235] text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Search</button>
            @if(request('q'))
                <a href="{{ route('admin.users') }}" class="px-3 py-2 text-sm text-gray-300 hover:text-white">Clear</a>
            @endif
        </div>
    </form>

    <div class="rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr style="background-color: #3C3F58; border-radius: 8px 8px 0 0;">
                        <th class="table-header" style="border-radius: 8px 0 0 0; width: 15%;">Name</th>
                        <th class="table-header" style="width: 20%;">Email</th>
                        <th class="table-header" style="width: 10%;">Role</th>
                        <th class="table-header" style="width: 10%;">Approved</th>
                        <th class="table-header" style="width: 12%;">Plan</th>
                        <th class="table-header" style="width: 13%;">Actions</th>
                        <th class="table-header" style="border-radius: 0 8px 0 0; width: 20%;">Manage</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid #3C3F58;">
                    @forelse ($users as $user)
                        <tr class="user-table-row" style="border-bottom: 1px solid #3C3F58;">
                            <td class="px-6 py-4 text-sm" style="color: #ffffff; width: 15%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ Str::limit($user->name, 20) }}</td>
                            <td class="px-6 py-4 text-sm" style="color: #ffffff; width: 20%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ Str::limit($user->email, 25) }}</td>
                            <td class="px-6 py-4 text-sm" style="color: #ffffff; width: 10%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $user->role }}</td>
                            <td class="px-6 py-4 text-sm font-bold" style="width: 10%; @if($user->is_approved) color: #10B981; @else color: #EF4444; @endif">
                                @if($user->is_approved)
                                    Yes
                                @else
                                    No
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-bold" style="width: 12%; @if($user->is_premium) color: #f89c00; @else color: #2563eb; @endif">
                                @if($user->is_premium)
                                    Premium
                                @else
                                    Standard
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm" style="width: 13%;">
                                @if (!$user->is_approved)
                                    <form method="POST" action="{{ route('admin.approve', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-indigo-400 hover:text-indigo-300 transition-colors">Approve</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.revoke', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-400 hover:text-red-300 transition-colors">Revoke</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm" style="width: 20%;">
    <div class="relative inline-block text-left">
        <!-- Toggle Button -->
        <button type="button" 
                class="inline-flex items-center justify-center w-full px-3 py-1 text-xs font-medium text-white bg-[#3C3F58] rounded-md hover:bg-[#55597C] focus:outline-none transition-colors"
                id="manageAccounts-menu-{{ $user->id }}"
                aria-expanded="false"
                aria-haspopup="true">
            <img src="{{ asset('garage.png') }}" alt="Manage Accounts" class="w-4 h-4 mr-2">
            <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="w-2 h-2 ml-2 transition-transform duration-200" id="manageAccounts-caret-{{ $user->id }}">
        </button>

        <!-- Dropdown Menu -->
        <div class="absolute right-0 mt-2 w-48 bg-[#3C3F58] text-white rounded-lg shadow-lg z-50 overflow-hidden opacity-0 invisible translate-y-[-10px] transition-all duration-200"
             id="manageAccounts-dropdown-{{ $user->id }}"
             role="menu"
             aria-orientation="vertical"
             aria-labelledby="manageAccounts-menu-{{ $user->id }}">
            <div class="py-1" role="none">
                <!-- Toggle Premium Button -->
                <button onclick="togglePremium({{ $user->id }}, '{{ $user->name }}', {{ $user->is_premium ? 'true' : 'false' }})" 
                        class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-[#55597C] transition-colors"
                        role="menuitem">
                    {{ $user->is_premium ? 'Remove Premium' : 'Grant Premium' }}
                </button>
                
                <!-- Reset Premium Button (Only show for premium users) -->
                @if($user->is_premium)
                <button onclick="resetPremium({{ $user->id }}, '{{ $user->name }}')" 
                        class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-[#55597C] transition-colors"
                        role="menuitem">
                    Reset All Data
                </button>
                @endif
                
                <!-- View Details Button -->
                <button onclick="viewPremiumDetails({{ $user->id }})" 
                        class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-[#55597C] transition-colors"
                        role="menuitem">
                    View Details
                </button>
            </div>
        </div>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm" style="color: #ffffff;">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4" id="usersPagination">
            {{ $users->links() }}
        </div>
    </div>
</main>

<style>
    .user-table-row {
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .user-table-row:hover {
        background-color: #676C98 !important;
    }
    .user-table-row:hover td {
        color: #FFFFFF !important;
    }
    
    /* Table Header */
    .table-header {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
        padding-top: 1rem;
        padding-bottom: 1rem;
        text-align: left;
        font-size: 0.75rem;
        color: #ffffff;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>

<script>
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
                        <td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900\">${escapeHtml(u.name)}</td>
                        <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">${escapeHtml(u.email)}</td>
                        <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize\">${escapeHtml(u.role || '')}</td>
                        <td class=\"px-6 py-4 whitespace-nowrap\">${approvedBadge}</td>
                        <td class=\"px-6 py-4 whitespace-nowrap text-sm\">${planBadge}</td>
                        <td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium\">${approveForm}</td>
                        <td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium\">${premiumForm}</td>
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
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
