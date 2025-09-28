@extends('layouts.admin')

@section('content')
    {{-- Header --}}
    <header class="col-span-2 flex items-center px-4 bg-[#0D0E2F] border-b border-[#4A4D6A] z-10">
        <div class="flex items-center mr-10">
            <div class="w-8 h-8 bg-red-600 rounded-lg mr-3 flex items-center justify-center text-white font-bold text-lg">A</div>
            <div class="text-xl font-medium text-white">Admin Panel - Users</div>
        </div>
        <div class="flex-grow"></div>
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
    <div id="adminSidebar" class="bg-[#1F2235] border-r border-[#4A4D6A] py-4 overflow-y-auto">
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

        <div class="bg-[#1F2235] shadow-md rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200" id="allUsersTable">
                <thead class="bg-[#3C3F58]">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Approved</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Premium Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Manage Premium</th>
                    </tr>
                </thead>
                <tbody class="bg-[#1F2235] divide-y divide-[#4A4D6A]">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">{{ $user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 capitalize">{{ $user->role }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($user->is_approved)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">No</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if ($user->is_premium)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Premium</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Standard</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if (!$user->is_approved)
                                    <form method="POST" action="{{ route('admin.approve', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900">Approve</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.revoke', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900">Revoke</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-col space-y-2">
                                    <!-- Toggle Premium Button -->
                                    <button onclick="togglePremium({{ $user->id }}, '{{ $user->name }}', {{ $user->is_premium ? 'true' : 'false' }})" 
                                            class="px-3 py-1 text-xs rounded transition-colors {{ $user->is_premium ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white' }}">
                                        {{ $user->is_premium ? 'Remove Premium' : 'Grant Premium' }}
                                    </button>
                                    
                                    <!-- Reset Premium Button -->
                                    @if($user->is_premium)
                                    <button onclick="resetPremium({{ $user->id }}, '{{ $user->name }}')" 
                                            class="px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white text-xs rounded transition-colors">
                                        Reset All Data
                                    </button>
                                    @endif
                                    
                                    <!-- View Details Button -->
                                    <button onclick="viewPremiumDetails({{ $user->id }})" 
                                            class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded transition-colors">
                                        View Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4" id="usersPagination">
                {{ $users->links() }}
            </div>
        </div>
    </main>

    <script>
        // Profile dropdown toggle
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('userProfileBtn');
            const menu = document.getElementById('profileDropdown');
            if (!btn || !menu) return;
            const open = () => { menu.classList.remove('opacity-0','invisible','translate-y-[-10px]','scale-95'); menu.classList.add('opacity-100','visible','translate-y-0','scale-100'); };
            const close = () => { menu.classList.add('opacity-0','invisible','translate-y-[-10px]','scale-95'); menu.classList.remove('opacity-100','visible','translate-y-0','scale-100'); };
            let isOpen = false;
            btn.addEventListener('click', (e)=>{ e.stopPropagation(); isOpen?close():open(); isOpen=!isOpen; });
            document.addEventListener('click', (e)=>{ if(isOpen && !menu.contains(e.target) && !btn.contains(e.target)){ close(); isOpen=false; }});
            document.addEventListener('keydown',(e)=>{ if(e.key==='Escape' && isOpen){ close(); isOpen=false; }});
        });

        // Predictive search for All Users (AJAX)
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('adminUserSearch');
            const tableBody = document.querySelector('#allUsersTable tbody');
            const pagination = document.getElementById('usersPagination');
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            if (!input || !tableBody) return;
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
        });

        // Premium Management Functions
        function togglePremium(userId, userName, isPremium) {
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
    </script>
@endsection
