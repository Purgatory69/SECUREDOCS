@extends('layouts.admin')

@section('content')
    {{-- Header --}}
    <header class="col-span-2 flex items-center px-4 bg-[#0D0E2F] border-b border-[#4A4D6A] z-10">
        <div class="flex items-center mr-10">
            <div class="w-8 h-8 bg-red-600 rounded-lg mr-3 flex items-center justify-center text-white font-bold text-lg">A</div> {{-- Admin Indicator --}}
            <div class="text-xl font-medium text-white">Admin Panel - Securedocs</div>
            
        </div>

        {{-- Spacer --}}
        <div class="flex-grow"></div>

        {{-- Admin Profile/Logout --}}
        <div class="flex items-center ml-auto gap-4">
            <div class="relative inline-block">
                <div id="userProfileBtn" class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center text-base cursor-pointer hover:bg-red-600 transition-colors">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div id="profileDropdown" class="absolute top-full right-0 mt-2 w-[280px] bg-[#1F2235] rounded-lg shadow-xl border border-[#4A4D6A] z-50 overflow-hidden transition-all duration-200 opacity-0 invisible transform translate-y-[-10px] scale-95">
                    <div class="p-4 border-b border-[#4A4D6A] flex items-center">
                        <div class="w-12 h-12 rounded-full bg-red-500 text-white flex items-center justify-center text-xl mr-4">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <div class="text-base font-medium mb-1 text-white">{{ Auth::user()->name }} (Admin)</div>
                            <div class="text-sm text-gray-300">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <ul class="list-none">
                        <li class="p-3 flex items-center cursor-pointer hover:bg-[#3C3F58]">
                            <span class="mr-4 text-lg w-6 text-center">👤</span>
                            <a href="{{ route('profile.show') }}" class="text-sm text-gray-200">Profile Settings</a>
                        </li>
                        {{-- Add other relevant admin links if needed --}}
                    </ul>
                    <div class="p-3 border-t border-[#4A4D6A] text-center">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="py-2 px-4 bg-[#3C3F58] border border-[#4A4D6A] rounded text-sm cursor-pointer hover:bg-[#4A4D6A] text-white">Sign Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Sidebar --}}
    <div id = "adminSidebar" class="bg-[#1F2235] border-r border-[#4A4D6A] py-4 overflow-y-auto">
        <div class="px-6 py-3 mb-4">
            <h2 class="text-lg font-semibold text-white">Admin Menu</h2>
        </div>
        <ul class="mt-2">
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 {{ request()->routeIs('admin.dashboard') ? 'bg-[#3C3F58] text-white' : 'hover:bg-[#3C3F58] text-gray-300' }}">
                <span class="mr-4 text-lg w-6 text-center">📊</span>
                <a href="{{ route('admin.dashboard') }}" class="w-full {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-gray-300' }}">Dashboard</a>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 {{ request()->routeIs('admin.users') ? 'bg-[#3C3F58] text-white' : 'hover:bg-[#3C3F58] text-gray-300' }}">
                <span class="mr-4 text-lg w-6 text-center">👥</span>
                <a href="{{ route('admin.users') }}" class="w-full {{ request()->routeIs('admin.users') ? 'text-white' : 'text-gray-300' }}">Manage Users</a>
            </li>
        </ul>
    </div>

    {{-- Main Content --}}
    <main class="bg-[#0D0E2F] p-6 overflow-y-auto">
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
            <h2 class="text-xl font-semibold text-white mb-4">Dashboard</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">Total Users</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="totalUsersCount">{{ number_format($totalUsers ?? 0) }}</div>
                </div>
                <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">Premium Users</div>
                    <div class="text-3xl font-bold mt-1 text-blue-600" id="premiumUsersCount">{{ number_format($premiumUsers ?? 0) }}</div>
                </div>
                <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-5 shadow-sm">
                    <div class="text-sm text-gray-300">Standard Users</div>
                    <div class="text-3xl font-bold mt-1 text-white" id="standardUsersCount">{{ number_format($standardUsers ?? 0) }}</div>
                </div>
            </div>
        </section>

        {{-- Line Chart: New Users --}}
        <section class="mb-6">
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-5 shadow-sm">
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
                <div class="relative" style="height: 280px;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </section>

        {{-- Recent Signups --}}
        <section class="mb-8">
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-lg p-5 shadow-sm">
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

        {{-- Users table moved to Admin → Manage Users page --}}
    </main>
    <script>
        // Profile dropdown toggle
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('userProfileBtn');
            const menu = document.getElementById('profileDropdown');
            if (!btn || !menu) return;

            const open = () => {
                menu.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]', 'scale-95');
                menu.classList.add('opacity-100', 'visible', 'translate-y-0', 'scale-100');
            };
            const close = () => {
                menu.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]', 'scale-95');
                menu.classList.remove('opacity-100', 'visible', 'translate-y-0', 'scale-100');
            };

            let isOpen = false;
            const toggle = () => { isOpen ? close() : open(); isOpen = !isOpen; };

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggle();
            });

            document.addEventListener('click', (e) => {
                if (!isOpen) return;
                if (!menu.contains(e.target) && !btn.contains(e.target)) {
                    close(); isOpen = false;
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && isOpen) { close(); isOpen = false; }
            });
        });

        // Chart.js for user growth
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('userGrowthChart');
            if (!canvas) return;
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
                const ctx = canvas.getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Total', data: total, borderColor: '#ffffff', backgroundColor: 'rgba(255,255,255,0.1)', tension: 0.25 },
                            { label: 'Premium', data: premium, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.1)', tension: 0.25 },
                            { label: 'Standard', data: standard, borderColor: '#6b7280', backgroundColor: 'rgba(107,114,128,0.1)', tension: 0.25 },
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
                                labels: { color: '#ffffff' }
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
        });
        
        // Users predictive search removed from dashboard (available on Manage Users page)
    </script>
@endsection