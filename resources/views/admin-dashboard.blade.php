@extends('layouts.app')

@section('content')
    {{-- Header --}}
    <header class="col-span-2 flex items-center px-4 bg-white border-b border-border-color z-10">
        <div class="flex items-center mr-10">
            <div class="w-8 h-8 bg-red-600 rounded-lg mr-3 flex items-center justify-center text-white font-bold text-lg">A</div> {{-- Admin Indicator --}}
            <div class="text-xl font-medium text-text-main">Admin Panel - Securedocs</div>
            
        </div>

        {{-- Spacer --}}
        <div class="flex-grow"></div>

        {{-- Admin Profile/Logout --}}
        <div class="flex items-center ml-auto gap-4">
            <div class="relative inline-block">
                <div id="userProfileBtn" class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center text-base cursor-pointer hover:bg-red-600 transition-colors">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div id="profileDropdown" class="absolute top-full right-0 mt-2 w-[280px] bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden transition-all duration-200 opacity-0 invisible transform translate-y-[-10px] scale-95">
                    <div class="p-4 border-b border-border-color flex items-center">
                        <div class="w-12 h-12 rounded-full bg-red-500 text-white flex items-center justify-center text-xl mr-4">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <div class="text-base font-medium mb-1">{{ Auth::user()->name }} (Admin)</div>
                            <div class="text-sm text-text-secondary">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <ul class="list-none">
                        <li class="p-3 flex items-center cursor-pointer hover:bg-bg-light">
                            <span class="mr-4 text-lg w-6 text-center">👤</span>
                            <a href="{{ route('profile.show') }}" class="text-sm">Profile Settings</a>
                        </li>
                        {{-- Add other relevant admin links if needed --}}
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

    {{-- Sidebar --}}
    <div id = "adminSidebar" class="bg-white border-r border-border-color py-4 overflow-y-auto">
        <div class="px-6 py-3 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Admin Menu</h2>
        </div>
        <ul class="mt-2">
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 {{ request()->routeIs('admin.dashboard') ? 'bg-[#e8f0fe] text-primary' : 'hover:bg-bg-light' }}">
                <span class="mr-4 text-lg w-6 text-center">👥</span> {{-- Users Icon --}}
                <a href="{{ route('admin.dashboard') }}" class="w-full">Manage Users</a>
            </li>
            <li class="py-3 px-6 flex items-center cursor-pointer transition-colors rounded-r-2xl mr-4 hover:bg-bg-light">
                <span class="mr-4 text-lg w-6 text-center">📝</span> {{-- Registrations Icon --}}
                {{-- Link to the same page for now, or a future specific registrations page --}}
                <a href="{{ route('admin.dashboard') }}?filter=pending_registrations" class="w-full">Registrations</a> {{-- Example filter --}}
            </li>
            {{-- Add more admin-specific links here if needed --}}
        </ul>
    </div>

    {{-- Main Content --}}
    <main class="bg-gray-50 p-6 overflow-y-auto">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">User Management</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">All Users</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Premium Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manage Premium</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ $user->role }}</td>
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
                                {{-- Add other actions like Edit, Delete if needed --}}
                            </td>

                            <!-- <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" action="" class="space-y-1">
                                    @csrf
                                    <div class="flex items-center">
                                        <input type="checkbox" name="is_premium" id="is_premium_{{ $user->id }}" value="1" {{ $user->is_premium ? 'checked' : '' }} class="mr-1 h-3 w-3 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        <label for="is_premium_{{ $user->id }}" class="text-xs">Premium</label>
                                    </div>
                                    <button type="submit" class="px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">Update</button>
                                </form>
                            </td> -->
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
    {{-- Profile dropdown handled by resources/js/modules/ui.js --}}
@endsection