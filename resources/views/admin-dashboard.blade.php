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
    <div id = "adminSidebar" style="background-color: #141326;" class="py-4 overflow-y-auto">
        <div class="px-6 py-3 mb-4">
            <h2 class="text-lg font-semibold text-white">Admin Menu</h2>
        </div>
        <ul id="sidebar" class="mt-4">
            <a class="activeTab py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4">
                <img src="{{ asset('graph-bar.png') }}" alt="Dashboard" class="mr-4 w-5 h-5">
                <span class="text-sm">Dashboard</span>
            </a>
            <a href="{{ route('admin.users') }}"
            class="py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4 {{ request()->routeIs('admin.users') ? 'activeTab' : '' }}">
                <img src="{{ asset('people.png') }}" alt="Manage Users" class="mr-4 w-5 h-5">
                <span class="text-sm">Manage Users</span>
            </a>
        </ul>
    </div>

    {{-- Main Content --}}
    <main style="background-color: #24243B; border-top-left-radius: 32px; margin-left: 13px;" class="p-6 overflow-y-auto">
        <h1 class="text-2xl font-semibold text-white mb-6">Dashboard</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        {{-- Users search removed from dashboard (now on Manage Users page) --}}

        {{-- Dashboard KPIs --}}
        <section class="mb-6">
            <!-- <h3 class="text-lg font-semibold text-white mb-4">User Count</h3> -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="tot-div user-count-txt rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">Total Users</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="totalUsersCount">{{ number_format($totalUsers ?? 0) }}</div>
                </div>
                <div class="prem-div user-count-txt rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">Premium Users</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="premiumUsersCount">{{ number_format($premiumUsers ?? 0) }}</div>
                </div>
                <div class="stan-div user-count-txt rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">Standard Users</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="standardUsersCount">{{ number_format($standardUsers ?? 0) }}</div>
                </div>
            </div>
        </section>

        {{-- Line Chart: New Users --}}
        <section class="mb-6">
            <div class="section-border rounded-lg p-5">
                <div class="flex items-center justify-between mb-3 gap-4 flex-wrap">
                    <h3 class="text-lg font-semibold text-white">New Users</h3>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-300">Range</label>
                        <select id="chartRange" class="border border-[#4A4D6A] bg-[#3C3F58] text-white rounded px-2 py-1 text-sm">
                            <option value="7d">7 days</option>
                            <option value="30d" selected>30 days</option>
                            <option value="90d">90 days</option>
                            <option value="1y">1 year (daily)</option>
                            <option value="12m">12 months (monthly)</option>
                        </select>
                        <label class="text-sm text-gray-300">Group</label>
                        <select id="chartGroup" class="border border-[#4A4D6A] bg-[#3C3F58] text-white rounded px-2 py-1 text-sm">
                            <option value="day" selected>Day</option>
                            <option value="month">Month</option>
                        </select>
                    </div>
                </div>
                <div class="relative" style="height: 320px;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </section>

        {{-- Recent Signups --}}
        <section class="mb-8">
            <div class="section-border rounded-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-white">Recent Signups</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-[#3C3F58]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Created</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[#1F2235] divide-y divide-[#4A4D6A]" id="usersTableBody">
                            @forelse(($recentUsers ?? []) as $ru)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-white">{{ $ru->name }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-300">{{ $ru->email }}</td>
                                    <td class="px-6 py-3 text-sm">
                                        @if($ru->is_premium)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Premium</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Standard</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-300">{{ optional($ru->created_at)->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-300">No recent signups.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <style>
            /* Sidebar styles */
            #sidebar>a{background:#141326!important;color:#fff!important;transition:filter .15s;}
            #sidebar>a:hover{filter:brightness(1.5)!important;}
            #sidebar>a.activeTab{background:#2B2C61!important;color:#fff!important;}
            #sidebar>a.activeTab:hover{filter:none!important;}
            #sidebar>a *{color:inherit!important;}

            .section-border {
                background-color: #24243B !important;
                border-style: solid !important;
                border-width: 2px !important;
                border-color: #3C3F58 !important;
            }

            .tot-div, .prem-div, .stan-div {
                background-color: #3C3F58;
                transition: background-color 0.2s ease, color 0.2s ease;
            }
            .tot-div:hover {background-color: #676C98;}
            .prem-div:hover {background-color: #f89c00;}
            .stan-div:hover {background-color: #2563eb;}
            .user-count-txt:hover * {color: #000000 !important;}
        </style>

        {{-- Users table moved to Admin → Manage Users page --}}
    </main>

    <!--
        PLS PLS PLS AWAY NI IMALHIN SA LAING JS.
        IT'S WORKING AS INTENDED. I AINT FIXING IT AGAIN.
        FOR THE LOVE OF GOD PLS DON'T CHANGE IT. MALUOY INTAWN.
        IT TAKES ME FOREVERRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRR
    -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // =========================================================================
        // 1. Chart.js Logic (Existing Code)
        // =========================================================================
        const canvas = document.getElementById('userGrowthChart');
        if (canvas) {
            const labels = @json($labels ?? []);
            const total = @json($seriesTotal ?? []);
            const premium = @json($seriesPremium ?? []);
            const standard = @json($seriesStandard ?? []);
            const rangeSel = document.getElementById('chartRange');
            const groupSel = document.getElementById('chartGroup');
            const totalEl = document.getElementById('totalUsersCount');
            const premiumEl = document.getElementById('premiumUsersCount');
            const standardEl = document.getElementById('standardUsersCount');
            
            const ensureChartJs = () => new Promise((resolve) => {
                if (window.Chart) return resolve();
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                s.onload = () => resolve();
                document.head.appendChild(s);
            });

            ensureChartJs().then(() => {
                document.head.appendChild(s);
            });
            ensureChartJs().then(() => {
                Chart.defaults.font.family = 'Poppins, sans-serif'; 

                const ctx = canvas.getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            // Total dataset: Updated to use #676C98 and its 10% opacity equivalent
                            { label: 'Total', data: total, borderColor: '#676C98', backgroundColor: 'rgba(103,108,152,0.1)', tension: 0.25 },
                            // Premium dataset: Updated to use #f89c00 and its 10% opacity equivalent
                            { label: 'Premium', data: premium, borderColor: '#f89c00', backgroundColor: 'rgba(248,156,0,0.1)', tension: 0.25 },
                            // Standard dataset: Updated to use #2563eb and its 10% opacity equivalent
                            { label: 'Standard', data: standard, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.1)', tension: 0.25 },
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                ticks: { precision: 0, color: '#ffffff' },
                                grid: { color: '#4A4D6A' }
                            },
                            x: {
                                ticks: { color: '#ffffff' },
                                grid: { color: '#4A4D6A' }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'rect',    // square boxes
                                    boxWidth: 18,
                                    boxHeight: 18,
                                    padding: 20,           // extra space between legend items
                                    color: function(context) {
                                        const chart = context.chart;
                                        const datasetIndex = context.datasetIndex;
                                        const meta = chart.getDatasetMeta(datasetIndex);
                                        return meta.hidden ? 'rgba(255,255,255,0.6)' : '#ffffff'; // dim text if hidden
                                    },
                                    generateLabels: function(chart) {
                                        const datasets = chart.data.datasets;
                                        return datasets.map((ds, i) => {
                                            const meta = chart.getDatasetMeta(i);
                                            return {
                                                text: ds.label,
                                                fillStyle: ds.borderColor,
                                                strokeStyle: ds.borderColor,
                                                hidden: meta.hidden,
                                                datasetIndex: i,
                                                pointStyle: 'rect',
                                                textDecoration: 'none' // attempt to remove strikethrough
                                            };
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: '#1F1F33',
                                titleColor: '#f89c00',
                                bodyColor: '#ffffff',
                                borderColor: '#f89c00',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 6
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: '#ffffff' },
                                grid: { color: '#4A4D6A' }
                            },
                            x: {
                                ticks: { color: '#FFAA00' }, // change chart date color here
                                grid: { color: '#4A4D6A' }
                            }
                        }
                    }

                });

                const fetchMetrics = async () => {
                    try {
                        const r = rangeSel ? rangeSel.value : '30d';
                        let g = groupSel ? groupSel.value : 'day';
                        if (r === '12m') g = 'month';
                        const res = await fetch(`{{ route('admin.metrics.users') }}?range=${encodeURIComponent(r)}&group=${encodeURIComponent(g)}`, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        chart.data.labels = data.labels || [];
                        if (chart.data.datasets[0]) chart.data.datasets[0].data = data.total || [];
                        if (chart.data.datasets[1]) chart.data.datasets[1].data = data.premium || [];
                        if (chart.data.datasets[2]) chart.data.datasets[2].data = data.standard || [];
                        chart.update();
                        if (data.totals) {
                            if (totalEl) totalEl.textContent = new Intl.NumberFormat().format(data.totals.total_users || 0);
                            if (premiumEl) premiumEl.textContent = new Intl.NumberFormat().format(data.totals.premium_users || 0);
                            if (standardEl) standardEl.textContent = new Intl.NumberFormat().format(data.totals.standard_users || 0);
                        }
                    } catch (e) { /* noop */ }
                };

                rangeSel && rangeSel.addEventListener('change', fetchMetrics);
                groupSel && groupSel.addEventListener('change', fetchMetrics);
            });
        }

        // =========================================================================
        // 2. Dropdown Logic (FIXED for animation and z-index/overflow)
        // =========================================================================

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
                // Removed 'scale-95' for a simple top-to-bottom slide animation
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
                    } 
                    // If language is clicked, we do nothing here, as closing the parent profileDropdown is wrong.

                    // 2. Toggle the target dropdown
                    if (isHidden) {
                        // Open: Remove transform and visibility classes
                        // Removed 'scale-95' for a simple top-to-bottom slide animation
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

        // Global listener to close dropdowns when clicking anywhere outside
        document.addEventListener('click', function(e) {
            // IDs of all elements that belong to a dropdown set
            const profileToggle = document.getElementById('userProfileBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const languageToggle = document.getElementById('headerLanguageToggle2');
            const languageDropdown = document.getElementById('headerLanguageSubmenu2');
            
            const clickedInsideAnyDropdown = 
                (profileToggle && profileToggle.contains(e.target)) ||
                (profileDropdown && profileDropdown.contains(e.target));
                
            if (!clickedInsideAnyDropdown) {
                closeDropdown('profileDropdown');
            }
            
            // This handles cases where the click is outside the language dropdown itself but inside the profile menu
            if (languageDropdown && !languageDropdown.contains(e.target) && !languageToggle.contains(e.target)) {
                // Close language submenu if click is outside it, but only if profileDropdown is still open
                if (profileDropdown && !profileDropdown.classList.contains('invisible')) {
                    closeDropdown('headerLanguageSubmenu2');
                }
            }
        });

    });
    </script>
@endsection