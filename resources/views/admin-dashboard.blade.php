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
            <h2 class="text-lg font-semibold text-white">{{ __('auth.db_admin_menu') }}</h2>
        </div>
        <ul id="sidebar" class="mt-4">
            <a class="activeTab py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4">
                <img src="{{ asset('graph-bar.png') }}" alt="Dashboard" class="mr-4 w-5 h-5">
                <span class="text-sm">{{ __('auth.db_dashboard') }}</span>
            </a>
            <a href="{{ route('admin.users') }}"
            class="py-3 px-6 flex items-center cursor-pointer rounded-r-2xl mr-4 {{ request()->routeIs('admin.users') ? 'activeTab' : '' }}">
                <img src="{{ asset('people.png') }}" alt="Manage Users" class="mr-4 w-5 h-5">
                <span class="text-sm">{{ __('auth.db_manage_users') }}</span>
            </a>
        </ul>
    </div>

    {{-- Main Content --}}
    <main style="background-color: #24243B; border-top-left-radius: 32px; margin-left: 13px;" class="p-6 overflow-y-auto">
        <h1 class="text-2xl font-semibold text-white mb-6">{{ __('auth.db_dashboard') }}</h1>

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
                    <div class="text-sm text-gray-300">{{ __('auth.db_total_users') }}</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="totalUsersCount">{{ number_format($totalUsers ?? 0) }}</div>
                </div>
                <div class="prem-div user-count-txt rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">{{ __('auth.db_premium_users') }}</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="premiumUsersCount">{{ number_format($premiumUsers ?? 0) }}</div>
                </div>
                <div class="stan-div user-count-txt rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">{{ __('auth.db_standard_users') }}</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="standardUsersCount">{{ number_format($standardUsers ?? 0) }}</div>
                </div>
            </div>
        </section>

        {{-- Line Chart: New Users --}}
        <section class="mb-6">
            <div class="section-border rounded-lg p-5">
                <div class="flex items-center justify-between mb-3 gap-4 flex-wrap">
                    <h3 class="text-lg font-semibold text-white">{{ __('auth.db_new_users') }}</h3>
                        <div class="flex items-center gap-4">
                            {{-- Range Dropdown --}}
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-300">{{ __('auth.db_range') }}</label>
                                <div class="relative">
                                    <div id="chartRangeToggle" style="width: 250px;"
                                        class="bg-[#3C3F58] border border-[#4A4D6A] text-white rounded px-4 py-2 text-sm cursor-pointer transition-colors duration-200 flex items-center justify-between"
                                        onmouseover="this.style.backgroundColor='#55597C';" 
                                        onmouseout="this.style.backgroundColor='#3C3F58';">
                                        <span id="chartRangeValue" data-current-value="30d">{{ __('auth.db_30_days') }}</span>
                                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="w-2 h-2 ml-2 transition-transform duration-200" id="chartRangeCaret">
                                    </div>
                                    <div id="chartRangeDropdown" style="width: 350px;"
                                        class="absolute top-full left-0 mt-2 bg-[#3C3F58] border border-[#4A4D6A] text-white rounded-lg shadow-xl z-50 overflow-hidden opacity-0 invisible translate-y-[-10px] transition-all duration-200">
                                        <div data-value="7d" class="px-4 py-3 text-sm cursor-pointer transition-colors hover:bg-[#55597C]">{{ __('auth.db_7_days') }}</div>
                                        <div data-value="30d" class="px-4 py-3 text-sm cursor-pointer transition-colors hover:bg-[#55597C]">{{ __('auth.db_30_days') }}</div>
                                        <div data-value="90d" class="px-4 py-3 text-sm cursor-pointer transition-colors hover:bg-[#55597C]">{{ __('auth.db_90_days') }}</div>
                                        <div data-value="1y" class="px-4 py-3 text-sm cursor-pointer transition-colors hover:bg-[#55597C]">{{ __('auth.db_1_year_daily') }}</div>
                                        <div data-value="12m" class="px-4 py-3 text-sm cursor-pointer transition-colors hover:bg-[#55597C]">{{ __('auth.db_12_months_monthly') }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Group Dropdown --}}
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-300">{{ __('auth.db_group') }}</label>
                                <div class="relative">
                                    <div id="chartGroupToggle" 
                                        class="w-56 bg-[#3C3F58] border border-[#4A4D6A] text-white rounded px-4 py-2 text-sm cursor-pointer transition-colors duration-200 flex items-center justify-between"
                                        onmouseover="this.style.backgroundColor='#55597C';" 
                                        onmouseout="this.style.backgroundColor='#3C3F58';">
                                        <span id="chartGroupValue" data-current-value="day">{{ __('auth.db_day') }}</span>                                        <img src="{{ asset('caret-down.png') }}" alt="dropdown arrow" class="w-2 h-2 ml-2 transition-transform duration-200" id="chartGroupCaret">
                                    </div>
                                    <div id="chartGroupDropdown" 
                                        class="absolute top-full left-0 mt-2 w-56 bg-[#3C3F58] border border-[#4A4D6A] text-white rounded-lg shadow-xl z-50 overflow-hidden opacity-0 invisible translate-y-[-10px] transition-all duration-200">
                                        <div data-value="day" class="px-4 py-3 text-sm cursor-pointer transition-colors hover:bg-[#55597C]">{{ __('auth.db_day') }}</div>
                                        <div data-value="month" class="px-4 py-3 text-sm cursor-pointer transition-colors hover:bg-[#55597C]">{{ __('auth.db_month') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="relative" style="height: 340px;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </section>

        {{-- Recent Signups --}}
        <section class="mb-8">
            <div class="rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">{{ __('auth.db_recent_signups') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr style="background-color: #3C3F58; border-radius: 8px 8px 0 0;">
                                <th class="table-header" style="border-radius: 8px 0 0 0; width: 30%;">{{ __('auth.db_name') }}</th>
                                <th class="table-header" style="width: 35%;">{{ __('auth.db_email') }}</th>
                                <th class="table-header" style="width: 15%;">{{ __('auth.db_plan') }}</th>
                                <th class="table-header" style="border-radius: 0 8px 0 0; width: 20%;">{{ __('auth.db_created') }}</th>
                            </tr>
                        </thead>
                        <tbody style="border-top: 1px solid #3C3F58;">
                            @forelse(($recentUsers ?? []) as $ru)
                                <tr class="user-table-row" style="border-bottom: 1px solid #3C3F58;">
                                    <td class="px-6 py-4 text-sm" style="color: #ffffff; width: 30%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ Str::limit($ru->name, 30) }}</td>
                                    <td class="px-6 py-4 text-sm" style="color: #ffffff; width: 35%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ Str::limit($ru->email, 30) }}</td>
                                    <td class="px-6 py-4 text-sm font-bold" style="width: 15%; @if($ru->is_premium) color: #f89c00; @else color: #2563eb; @endif">
                                        @if($ru->is_premium)
                                            {{ __('auth.db_premium') }}
                                        @else
                                            {{ __('auth.db_standard') }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm" style="color: #ffffff; width: 20%;">{{ optional($ru->created_at)->format('Y-m-d  -  g:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm" style="color: #ffffff;">No recent signups.</td>
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

            /* Section Border*/
            .section-border { background-color: #24243B !important; border-style: solid !important; border-width: 2px !important; border-color: #3C3F58 !important;}

            /* Chart Styling */
            .tot-div, .prem-div, .stan-div {background-color: #3C3F58; transition: background-color 0.2s ease, color 0.2s ease;}
            .tot-div:hover {background-color: #676C98;}
            .prem-div:hover {background-color: #f89c00;}
            .stan-div:hover {background-color: #2563eb;}
            .user-count-txt:hover * {color: #000000 !important;}
            .chartjs-render-monitor {cursor: default;}
            #userGrowthChart:hover {cursor: default;}

            /* Chart Dropdown */
            #chartRangeToggle, #chartGroupToggle {transition: background-color 0.2s ease, border-color 0.2s ease;}
            #chartRangeDropdown div, #chartGroupDropdown div {transition: background-color 0.2s ease;}
            #chartRangeToggle, #chartRangeDropdown {width: 200px !important; min-width: 200px !important; max-width: 200px !important;}
            #chartGroupToggle, #chartGroupDropdown {width: 120px !important; min-width: 120px !important; max-width: 120px !important;}

            .user-table-row {transition: background-color 0.2s ease, color 0.2s ease;}
            .user-table-row:hover {background-color: #676C98 !important;}
            .user-table-row:hover td {color: #FFFFFF !important;}
            
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
    </main>

    {{-- Users table moved to Admin → Manage Users page --}}

    <!--
        PLS PLS PLS AWAY NI IMALHIN SA LAING JS.
        IT'S WORKING AS INTENDED. I AINT FIXING IT AGAIN.
        FOR THE LOVE OF GOD PLS DON'T CHANGE IT. MALUOY INTAWN.
        IT TAKES ME FOREVERRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRR
    -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // =========================================================================
    // 0. Translation Variables for JavaScript
    // =========================================================================
    const translations = {
        chart_total: '{{ __("auth.db_chart_total") }}',
        chart_premium: '{{ __("auth.db_chart_premium") }}',
        chart_standard: '{{ __("auth.db_chart_standard") }}',
        range_7_days: '{{ __("auth.db_7_days") }}',
        range_30_days: '{{ __("auth.db_30_days") }}',
        range_90_days: '{{ __("auth.db_90_days") }}',
        range_1_year_daily: '{{ __("auth.db_1_year_daily") }}',
        range_12_months_monthly: '{{ __("auth.db_12_months_monthly") }}',
        group_day: '{{ __("auth.db_day") }}',
        group_month: '{{ __("auth.db_month") }}'
    };
    // =========================================================================
    // 1. Chart.js Logic
    // =========================================================================
    const canvas = document.getElementById('userGrowthChart');
    if (canvas) {
        const labels = @json($labels ?? []);
        const total = @json($seriesTotal ?? []);
        const premium = @json($seriesPremium ?? []);
        const standard = @json($seriesStandard ?? []);
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
            Chart.defaults.font.family = 'Poppins, sans-serif';

            // Legend hover detection
            setTimeout(() => {
                const chartCanvas = document.getElementById('userGrowthChart');
                if (chartCanvas) {
                    let isOverLegend = false;
                    
                    chartCanvas.addEventListener('mousemove', function(e) {
                        const rect = chartCanvas.getBoundingClientRect();
                        const y = e.clientY - rect.top;
                        
                        // Detect if mouse is in bottom 20% of chart (legend area)
                        const newIsOverLegend = y > rect.height * 0.80;
                        
                        if (newIsOverLegend !== isOverLegend) {
                            isOverLegend = newIsOverLegend;
                            chartCanvas.style.cursor = isOverLegend ? 'pointer' : 'default';
                        }
                    });
                    
                    chartCanvas.addEventListener('mouseleave', function() {
                        isOverLegend = false;
                        chartCanvas.style.cursor = 'default';
                    });
                }
            }, 1000);

            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels, // ADDED MISSING COMMA HERE
                    datasets: [
                        { label: translations.chart_total, data: total, borderColor: '#676C98', backgroundColor: 'rgba(103,108,152,0.1)', tension: 0.25 },
                        { label: translations.chart_premium, data: premium, borderColor: '#f89c00', backgroundColor: 'rgba(248,156,0,0.1)', tension: 0.25 },
                        { label: translations.chart_standard, data: standard, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.1)', tension: 0.25 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#ffffff' },
                            grid: { color: '#3C3F58' }
                        },
                        x: {
                            ticks: { color: '#55597C' },
                            grid: { color: '#3C3F58' }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'rect',
                                boxWidth: 20,
                                boxHeight: 20,
                                padding: 25,
                                font: {
                                    family: 'Poppins, sans-serif',
                                    size: 13,
                                    weight: '500'
                                },
                                generateLabels: function(chart) {
                                    const datasets = chart.data.datasets || [];
                                    return datasets.map((ds, i) => {
                                        const meta = chart.getDatasetMeta(i);
                                        const isHidden = meta && meta.hidden;
                                        
                                        // Convert hex to rgba for opacity control
                                        const hexToRgba = (hex, alpha) => {
                                            if (hex.includes('rgba')) return hex;
                                            let hexClean = hex.replace('#', '');
                                            if (hexClean.length === 3) {
                                                hexClean = hexClean[0] + hexClean[0] + hexClean[1] + hexClean[1] + hexClean[2] + hexClean[2];
                                            }
                                            const r = parseInt(hexClean.substr(0, 2), 16);
                                            const g = parseInt(hexClean.substr(2, 2), 16);
                                            const b = parseInt(hexClean.substr(4, 2), 16);
                                            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                                        };

                                        const originalColor = ds.borderColor || '#ffffff';
                                        const displayColor = isHidden ? 
                                            hexToRgba(originalColor, 0.4) : 
                                            hexToRgba(originalColor, 1);
                                        
                                        return {
                                            text: ds.label,
                                            // Fill color with opacity changes - COMPLETELY BORDERLESS
                                            fillStyle: displayColor,
                                            // COMPLETELY REMOVE ALL BORDERS
                                            strokeStyle: 'transparent',
                                            // Remove border width
                                            lineWidth: 0,
                                            // Text color indicator
                                            fontColor: isHidden ? '#55597C' : '#ffffff',
                                            // Strikethrough as backup indicator
                                            textDecoration: isHidden ? 'line-through' : 'none',
                                            hidden: false,
                                            datasetIndex: i,
                                            pointStyle: 'rect'
                                        };
                                    });
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#141326',
                            titleColor: '#f89c00',
                            bodyColor: '#ffffff',
                            borderWidth: 0,
                            padding: 12,
                            cornerRadius: 6,
                            displayColors: true,
                            callbacks: {
                                labelColor: function(context) {
                                    const ds = context.dataset || {};
                                    const color = ds.borderColor || ds.backgroundColor || '#ffffff';
                                    return {
                                        borderColor: 'transparent',
                                        backgroundColor: color,
                                        borderWidth: 0,
                                        borderRadius: 2,
                                        borderDash: [0, 0],
                                    };
                                }
                            }
                        }
                    }
                }
            });

                        // Fetch metrics function - OPTIMIZED VERSION
                        const fetchMetrics = async () => {
                try {
                    // Get current values directly from data attributes - MUCH FASTER
                    const rangeValueEl = document.getElementById('chartRangeValue');
                    const groupValueEl = document.getElementById('chartGroupValue');
                    
                    let r = rangeValueEl ? rangeValueEl.getAttribute('data-current-value') || '30d' : '30d';
                    let g = groupValueEl ? groupValueEl.getAttribute('data-current-value') || 'day' : 'day';
                    
                    // Auto-set group to month if range is 12 months
                    if (r === '12m') g = 'month';
                    
                    const res = await fetch(`{{ route('admin.metrics.users') }}?range=${encodeURIComponent(r)}&group=${encodeURIComponent(g)}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    
                    if (!res.ok) return;
                    const data = await res.json();
                    
                    // Update chart data
                    chart.data.labels = data.labels || [];
                    if (chart.data.datasets[0]) chart.data.datasets[0].data = data.total || [];
                    if (chart.data.datasets[1]) chart.data.datasets[1].data = data.premium || [];
                    if (chart.data.datasets[2]) chart.data.datasets[2].data = data.standard || [];
                    
                    // Update chart
                    chart.update();
                    
                    // Update KPI counts
                    if (data.totals) {
                        if (totalEl) totalEl.textContent = new Intl.NumberFormat().format(data.totals.total_users || 0);
                        if (premiumEl) premiumEl.textContent = new Intl.NumberFormat().format(data.totals.premium_users || 0);
                        if (standardEl) standardEl.textContent = new Intl.NumberFormat().format(data.totals.standard_users || 0);
                    }
                } catch (e) { 
                    console.error('Error fetching metrics:', e);
                }
            };

            const updateChartLabels = () => {
                if (chart && chart.data && chart.data.datasets) {
                    chart.data.datasets[0].label = translations.chart_total;
                    chart.data.datasets[1].label = translations.chart_premium;
                    chart.data.datasets[2].label = translations.chart_standard;
                    chart.update();
                }
            };

            window.updateChartLabels = updateChartLabels;
            window.fetchMetrics = fetchMetrics;
        });
    }

    // =========================================================================
    // 2. Dropdown Logic
    // =========================================================================

    // List of all dropdown menus and their caret IDs
    const ALL_DROPDOWNS = [
        { menu: 'profileDropdown' },
        { menu: 'headerLanguageSubmenu2', caret: 'langCaret' },
        { menu: 'chartRangeDropdown', caret: 'chartRangeCaret' },
        { menu: 'chartGroupDropdown', caret: 'chartGroupCaret' },
    ];
    
    // Language change detection
    const languageLinks = document.querySelectorAll('#headerLanguageSubmenu2 a');
    languageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Wait a bit for the page to reload with new language, then update chart
            setTimeout(() => {
                if (window.updateChartLabels) {
                    window.updateChartLabels();
                }
                // Also update dropdown text if needed
                const rangeValue = document.getElementById('chartRangeValue');
                const groupValue = document.getElementById('chartGroupValue');
                if (rangeValue && groupValue) {
                    // You might want to map data-current-value back to translated text here
                    // For now, we'll rely on the page reload to handle dropdown text
                }
            }, 1000);
        });
    });

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

                // Close other dropdowns when opening one
                if (isHidden) {
                    if (dropdownId === 'chartRangeDropdown') {
                        closeDropdown('chartGroupDropdown');
                        closeDropdown('profileDropdown');
                    } else if (dropdownId === 'chartGroupDropdown') {
                        closeDropdown('chartRangeDropdown');
                        closeDropdown('profileDropdown');
                    } else if (dropdownId === 'profileDropdown') {
                        // Close chart dropdowns when profile dropdown opens
                        closeDropdown('chartRangeDropdown');
                        closeDropdown('chartGroupDropdown');
                    }
                    
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

    // Handle chart dropdown selections
    function setupChartDropdowns() {
        // Range dropdown selection
        const rangeDropdown = document.getElementById('chartRangeDropdown');
        const rangeValue = document.getElementById('chartRangeValue');
        
        // Group dropdown selection  
        const groupDropdown = document.getElementById('chartGroupDropdown');
        const groupValue = document.getElementById('chartGroupValue');
        
        if (rangeDropdown && rangeValue) {
            rangeDropdown.addEventListener('click', function(e) {
                if (e.target.hasAttribute('data-value')) {
                    const text = e.target.textContent;
                    const value = e.target.getAttribute('data-value');
                    
                    // Update displayed value AND data attribute
                    rangeValue.textContent = text;
                    rangeValue.setAttribute('data-current-value', value);
                    
                    // Close dropdown
                    closeDropdown('chartRangeDropdown');
                    
                    // Trigger the chart update
                    if (window.fetchMetrics) {
                        window.fetchMetrics();
                    }
                }
            });
        }
        
        if (groupDropdown && groupValue) {
            groupDropdown.addEventListener('click', function(e) {
                if (e.target.hasAttribute('data-value')) {
                    const text = e.target.textContent;
                    const value = e.target.getAttribute('data-value');
                    
                    // Update displayed value AND data attribute
                    groupValue.textContent = text;
                    groupValue.setAttribute('data-current-value', value);
                    
                    // Close dropdown
                    closeDropdown('chartGroupDropdown');
                    
                    // Trigger the chart update
                    if (window.fetchMetrics) {
                        window.fetchMetrics();
                    }
                }
            });
        }
    }

    // Initialize all dropdowns
    setTimeout(() => {
        // Initialize toggle functionality
        toggleDropdown('userProfileBtn', 'profileDropdown');
        toggleDropdown('headerLanguageToggle2', 'headerLanguageSubmenu2');
        toggleDropdown('chartRangeToggle', 'chartRangeDropdown');
        toggleDropdown('chartGroupToggle', 'chartGroupDropdown');
        
        // Initialize chart dropdown selections
        setupChartDropdowns();
    }, 100);

    // Global listener to close dropdowns when clicking anywhere outside
    document.addEventListener('click', function(e) {
        // Get all dropdown elements
        const profileToggle = document.getElementById('userProfileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const languageToggle = document.getElementById('headerLanguageToggle2'); 
        const languageDropdown = document.getElementById('headerLanguageSubmenu2');
        const rangeToggle = document.getElementById('chartRangeToggle');
        const rangeDropdown = document.getElementById('chartRangeDropdown');
        const groupToggle = document.getElementById('chartGroupToggle');
        const groupDropdown = document.getElementById('chartGroupDropdown');
        
        // Check if click is inside ANY dropdown
        const clickedInsideAnyDropdown = 
            (profileToggle && profileToggle.contains(e.target)) ||
            (profileDropdown && profileDropdown.contains(e.target)) ||
            (languageToggle && languageToggle.contains(e.target)) ||
            (languageDropdown && languageDropdown.contains(e.target)) ||
            (rangeToggle && rangeToggle.contains(e.target)) ||
            (rangeDropdown && rangeDropdown.contains(e.target)) ||
            (groupToggle && groupToggle.contains(e.target)) ||
            (groupDropdown && groupDropdown.contains(e.target));
        
        // If click is outside ALL dropdowns, close them all
        if (!clickedInsideAnyDropdown) {
            closeDropdown('profileDropdown');
            closeDropdown('headerLanguageSubmenu2'); 
            closeDropdown('chartRangeDropdown');
            closeDropdown('chartGroupDropdown');
        }
        
        // Handle language submenu specifically (nested in profile dropdown)
        if (languageDropdown && !languageDropdown.contains(e.target) && !languageToggle.contains(e.target)) {
            if (profileDropdown && !profileDropdown.classList.contains('invisible')) {
                closeDropdown('headerLanguageSubmenu2');
            }
        }
    });
    });
    </script>

@endsection