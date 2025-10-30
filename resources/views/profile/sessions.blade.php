@extends('layouts.app')

@section('content')
<div style="background-color: #24243B;" class="min-h-screen text-white">
    <!-- Header -->
    <div class="bg-[#141326] px-6 py-6">
        <div class="flex items-center justify-between w-full">
            <a href="{{ route('user.dashboard') }}" style="margin-left: 100px;"
            class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
            </a>
            <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                <h2 class="font-bold text-xl text-[#f89c00] font-['Poppins']">Account Security</h2>
            </div>
            
        </div>
    </div>

    <div class="container mx-auto px-6 py-8" >
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Active Sessions -->
            <div class="bg-[#3C3F58] rounded-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2 text-[#f89c00]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                        </svg>
                        Active Sessions
                    </h2>
                    <button id="terminateAllBtn" class="terminate-button text-white px-4 py-2 rounded-lg text-sm transition-all duration-200">
                        Terminate All Others
                    </button>
                </div>

                <div id="sessionsContainer" class="space-y-4">
                    <!-- Sessions will be loaded here -->
                    <div class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#f89c00]"></div>
                        <span class="ml-3 text-gray-500">Loading sessions...</span>
                    </div>
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="bg-[#3C3F58] rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6 flex items-center">
                    <img src="{{ asset('notifications.png') }}" alt="Notifications" class="w-6 h-6 mr-2">
                    Notification Preferences
                </h2>

                <form id="notificationPreferencesForm" class="space-y-4">
                    <div class="space-y-4">
                        <div style="margin-top: 2px !important;" class="notif-pref-div flex items-center justify-between rounded-lg">
                            <div>
                                <label class="text-white font-medium">Email Notifications</label>
                                <p class="text-gray-400 text-sm">Receive notifications via email</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="email_notifications_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#f89c00]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#f89c00]"></div>
                            </label>
                        </div>
                        
                        <div class="notif-border border-t"></div>

                        <div class="notif-pref-div flex items-center justify-between rounded-lg">
                            <div>
                                <label class="text-white font-medium">New Device Login Alerts</label>
                                <p class="text-gray-400 text-sm">Get notified when you log in from a new device</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="login_notifications_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#f89c00]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#f89c00]"></div>
                            </label>
                        </div>

                        <div class="notif-border border-t"></div>

                        <div class="notif-pref-div flex items-center justify-between rounded-lg">
                            <div>
                                <label class="text-white font-medium">Security Alerts</label>
                                <p class="text-gray-400 text-sm">Important security-related notifications</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="security_notifications_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#f89c00]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#f89c00]"></div>
                            </label>
                        </div>

                        <div class="notif-border border-t"></div>

                        <div class="notif-pref-div flex items-center justify-between rounded-lg">
                            <div>
                                <label class="text-white font-medium">Activity Notifications</label>
                                <p class="text-gray-400 text-sm">File uploads, downloads, and other activities</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="activity_notifications_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#f89c00]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#f89c00]"></div>
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-center">
                    <button type="submit" class="p-4 w-1/2 bg-[#f89c00] text-black font-medium py-3 px-4 rounded-lg transition-all duration-200 hover:filter hover:brightness-110">
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 bg-[#3C3F58] rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-[#f89c00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Recent Activity
            </h2>

            <div id="activityContainer" class="space-y-3 max-h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-[#f89c00] scrollbar-track-[#2A2D47] pr-2">
                <!-- Activity will be loaded here -->
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#f89c00]"></div>
                    <span class="ml-3 text-gray-400">Loading activity...</span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Terminate All Sessions Modal -->
<div id="terminateAllModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[10000] hidden flex items-center justify-center p-4">
    <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl max-w-md w-full shadow-2xl">
        <div class="p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">Terminate All Other Sessions</h3>
                <p class="text-gray-300 mb-6">This will log you out of all other devices. You'll need to log in again on those devices.</p>
                
                <form id="terminateAllForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Confirm with your password:</label>
                        <input type="password" id="confirmPassword" class="w-full px-3 py-2 bg-[#2A2D47] border border-[#4A4D6A] rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#f89c00] focus:border-transparent" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" id="cancelTerminateAll" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="terminate-button flex-1 text-white py-2 px-4 rounded-lg transition-colors">
                            Terminate All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Terminate All button */
.terminate-button {
    background-color: #dc2626 !important;
    color: white !important;
}
.terminate-button:hover:not(:disabled) {
    background-color: #ef4444 !important;
}

/* Notification preferences divs */
.notif-pref-div {
    background-color: #3C3F58 !important;
    padding-top: 4px !important;
    padding-bottom: 4px !important;
    padding-left: 16px !important;
    padding-right: 16px !important;
}

.notif-border {
    border-color: #55597C !important;
    margin-left: 16px !important;
    margin-right: 16px !important;
}

/* Browser Sessions Livewire Component Styling */
.browser-sessions-wrapper {
    /* Override Livewire component backgrounds */
}

.browser-sessions-wrapper .bg-white {
    background-color: #3C3F58 !important;
}

.browser-sessions-wrapper .text-sm.text-gray-600,
.browser-sessions-wrapper .text-xs.text-gray-500 {
    color: rgba(156, 163, 175, 1) !important;
}

.browser-sessions-wrapper .text-gray-600 {
    color: rgba(209, 213, 219, 1) !important;
}

.browser-sessions-wrapper .text-gray-500 {
    color: rgba(156, 163, 175, 1) !important;
}

.browser-sessions-wrapper svg {
    color: rgba(156, 163, 175, 1) !important;
}

.browser-sessions-wrapper h2,
.browser-sessions-wrapper .text-xl {
    color: white !important;
    display: flex !important;
    align-items: center !important;
}

.browser-sessions-wrapper h2 svg {
    color: #f89c00 !important;
    margin-right: 0.5rem !important;
}

.browser-sessions-wrapper .max-w-xl {
    color: rgba(156, 163, 175, 1) !important;
}

.browser-sessions-wrapper button {
    background-color: #f89c00 !important;
    color: #000000 !important;
    font-weight: 500 !important;
    padding: 0.5rem 1.5rem !important;
    border-radius: 0.5rem !important;
    transition: all 0.2s !important;
}

.browser-sessions-wrapper button:hover:not(:disabled) {
    filter: brightness(1.1) !important;
}

/* Modal styling for browser sessions */
.browser-sessions-wrapper [x-show] {
    background-color: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(4px) !important;
}

.browser-sessions-wrapper .bg-white.shadow {
    background-color: #1F2235 !important;
    border: 1px solid #4A4D6A !important;
}

.browser-sessions-wrapper input[type="password"] {
    background-color: #2A2D47 !important;
    border-color: #4A4D6A !important;
    color: white !important;
}

.browser-sessions-wrapper input[type="password"]::placeholder {
    color: rgba(156, 163, 219, 0.5) !important;
}

.browser-sessions-wrapper input[type="password"]:focus {
    outline: none !important;
    ring: 2px !important;
    ring-color: #f89c00 !important;
    border-color: transparent !important;
}

/* Custom scrollbar for activity container */
#activityContainer::-webkit-scrollbar {
    width: 6px;
}

#activityContainer::-webkit-scrollbar-track {
    background: #2A2D47;
    border-radius: 3px;
}

#activityContainer::-webkit-scrollbar-thumb {
    background: #f89c00;
    border-radius: 3px;
}

#activityContainer::-webkit-scrollbar-thumb:hover {
    background: #e68a00;
}

/* Smooth scroll behavior */
#activityContainer {
    scroll-behavior: smooth;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Set up event listeners first
    setupEventListeners();
    
    // Load data sequentially to avoid overwhelming the server
    await loadNotificationPreferences(); // Load this first as it's fastest
    await loadSessions(); // Then sessions
    await loadRecentActivity(); // Finally activity
});

async function loadSessions() {
    try {
        console.log('Loading sessions...');
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
        
        const response = await fetch('/user/sessions/test', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('Sessions loaded:', data.sessions?.length || 0, 'sessions');
        console.log('Session details:', data.sessions);
        
        renderSessions(data.sessions || []);
    } catch (error) {
        console.error('Error loading sessions:', error);
        document.getElementById('sessionsContainer').innerHTML = `
            <div class="text-center py-8 text-red-400">
                <p>Failed to load sessions: ${error.message}</p>
                <button onclick="loadSessions()" class="mt-2 bg-[#f89c00] text-white px-4 py-2 rounded">Retry</button>
            </div>
        `;
    }
}

function renderSessions(sessions) {
    const container = document.getElementById('sessionsContainer');
    
    if (sessions.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-400">
                <p>No active sessions found.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = sessions.map(session => `
        <div class="bg-[#2A2D47] rounded-lg p-4 ${session.is_current ? 'border-2 border-[#f89c00]' : ''}">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="text-2xl">
                        ${getDeviceIcon(session.device_type)}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-white font-medium">${session.browser} on ${session.platform}</span>
                            ${session.is_current ? '<span class="bg-[#f89c00] text-black text-xs px-2 py-1 rounded-full font-medium">Current</span>' : ''}
                            ${session.is_suspicious ? '<span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full font-medium">⚠️ Suspicious</span>' : ''}
                            ${session.trusted_device ? '<span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full font-medium">✓ Trusted</span>' : ''}
                        </div>
                        <div class="text-gray-400 text-sm">
                            ${session.location} • ${session.ip_address}
                        </div>
                        <div class="text-gray-500 text-xs">
                            Last active: ${session.last_activity} • Created: ${session.created_at}
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    ${!session.trusted_device && !session.is_current ? `
                        <button onclick="trustDevice('${session.session_id}')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors">
                            Trust
                        </button>
                    ` : ''}
                    ${!session.is_current ? `
                        <button onclick="terminateSession('${session.session_id}')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors">
                            Terminate
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

function getDeviceIcon(deviceType) {
    switch(deviceType) {
        case 'mobile': return '📱';
        case 'tablet': return '📱';
        case 'desktop': return '💻';
        case 'bot': return '🤖';
        default: return '❓';
    }
}

async function loadNotificationPreferences() {
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout (increased)
        
        const response = await fetch('/user/notifications/preferences', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (!response.ok) throw new Error('Failed to load preferences');

        const data = await response.json();
        const prefs = data.preferences || {};

        // Set checkbox states with defaults
        document.getElementById('email_notifications_enabled').checked = prefs.email_notifications_enabled ?? true;
        document.getElementById('login_notifications_enabled').checked = prefs.login_notifications_enabled ?? true;
        document.getElementById('security_notifications_enabled').checked = prefs.security_notifications_enabled ?? true;
        document.getElementById('activity_notifications_enabled').checked = prefs.activity_notifications_enabled ?? false;
    } catch (error) {
        if (error.name === 'AbortError') {
            console.warn('Notification preferences request timed out, using defaults');
        } else {
            console.error('Error loading notification preferences:', error);
        }
        // Set default values if loading fails
        document.getElementById('email_notifications_enabled').checked = true;
        document.getElementById('login_notifications_enabled').checked = true;
        document.getElementById('security_notifications_enabled').checked = true;
        document.getElementById('activity_notifications_enabled').checked = false;
    }
}

async function loadRecentActivity() {
    try {
        console.log('🔄 Loading recent activity...');
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
        const response = await fetch('/user/activity/test', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            signal: controller.signal
        });

        clearTimeout(timeoutId);
        console.log('📡 Response status:', response.status, response.statusText);

        if (!response.ok) throw new Error('Failed to load activity');

        const data = await response.json();
        console.log('📊 Activity data received:', data);
        console.log('📝 Activities count:', (data.activities || []).length);
        
        renderActivity(data.activities || []);
    } catch (error) {
        console.error('Error loading activity:', error);
        document.getElementById('activityContainer').innerHTML = `
            <div class="text-center py-8 text-red-400">
                <p>Failed to load activity: ${error.message}</p>
                <button onclick="loadRecentActivity()" class="mt-2 bg-[#f89c00] text-white px-4 py-2 rounded">Retry</button>
            </div>
        `;
    }
}

function renderActivity(activities) {
    console.log('🎨 Rendering activities:', activities);
    const container = document.getElementById('activityContainer');
    
    if (activities.length === 0) {
        console.log('❌ No activities to render');
        container.innerHTML = `
            <div class="text-center py-12 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-lg font-medium">No recent activity found</p>
                <p class="text-sm mt-1">Your activities will appear here when you start using the platform</p>
            </div>
        `;
        return;
    }

    const activitiesHtml = activities.map(activity => `
        <div class="flex items-center space-x-3 p-3 bg-[#2A2D47] rounded-lg hover:bg-[#2D3151] transition-colors duration-200">
            <div class="text-xl flex-shrink-0">${activity.activity_type_icon}</div>
            <div class="flex-1 min-w-0">
                <div class="text-white text-sm font-medium leading-tight">${activity.description}</div>
                <div class="text-gray-400 text-xs mt-1 flex items-center space-x-2">
                    <span>${activity.time_ago}</span>
                    ${activity.location ? `<span>•</span><span class="truncate">${activity.location}</span>` : ''}
                    ${activity.device_type ? `<span>•</span><span>${activity.device_type}</span>` : ''}
                </div>
            </div>
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getRiskLevelClass(activity.risk_level)}">
                    ${activity.risk_level_icon} ${activity.risk_level}
                </span>
            </div>
        </div>
    `).join('');

    container.innerHTML = activitiesHtml;

    // Add scroll indicator if content overflows
    setTimeout(() => {
        if (container.scrollHeight > container.clientHeight) {
            // Add a subtle gradient at the bottom to indicate more content
            if (!container.querySelector('.scroll-indicator')) {
                container.insertAdjacentHTML('afterend', `
                    <div class="scroll-indicator text-center mt-2">
                        <span class="text-xs text-gray-500">Scroll to see more activities</span>
                    </div>
                `);
            }
        }
    }, 100);
}

function getRiskLevelClass(riskLevel) {
    switch(riskLevel) {
        case 'low': return 'bg-green-100 text-green-800';
        case 'medium': return 'bg-yellow-100 text-yellow-800';
        case 'high': return 'bg-orange-100 text-orange-800';
        case 'critical': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function setupEventListeners() {
    // Notification preferences form
    document.getElementById('notificationPreferencesForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            email_notifications_enabled: document.getElementById('email_notifications_enabled').checked,
            login_notifications_enabled: document.getElementById('login_notifications_enabled').checked,
            security_notifications_enabled: document.getElementById('security_notifications_enabled').checked,
            activity_notifications_enabled: document.getElementById('activity_notifications_enabled').checked,
        };

        try {
            const response = await fetch('/user/notifications/preferences', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) throw new Error('Failed to update preferences');

            showNotification('Notification preferences updated successfully', 'success');
        } catch (error) {
            console.error('Error updating preferences:', error);
            showNotification('Failed to update preferences', 'error');
        }
    });

    // Terminate all sessions
    document.getElementById('terminateAllBtn').addEventListener('click', function() {
        document.getElementById('terminateAllModal').classList.remove('hidden');
    });

    document.getElementById('cancelTerminateAll').addEventListener('click', function() {
        document.getElementById('terminateAllModal').classList.add('hidden');
        document.getElementById('confirmPassword').value = '';
    });

    document.getElementById('terminateAllForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const password = document.getElementById('confirmPassword').value;
        
        try {
            const response = await fetch('/user/sessions/terminate-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ password })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to terminate sessions');
            }

            const data = await response.json();
            showNotification(data.message, 'success');
            document.getElementById('terminateAllModal').classList.add('hidden');
            document.getElementById('confirmPassword').value = '';
            loadSessions(); // Reload sessions
        } catch (error) {
            console.error('Error terminating sessions:', error);
            showNotification(error.message, 'error');
        }
    });

}

async function terminateSession(sessionId) {
    if (!window.confirm('Are you sure you want to terminate this session?')) {
        return;
    }

    try {
        console.log('Terminating session:', sessionId);
        
        const response = await fetch(`/user/sessions/${sessionId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        console.log('Terminate response status:', response.status);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            console.error('Terminate failed:', errorData);
            throw new Error(errorData.message || 'Failed to terminate session');
        }

        const data = await response.json();
        console.log('Session terminated successfully:', data);
        
        showNotification(data.message || 'Session terminated successfully', 'success');
        
        // Force reload sessions after a short delay to ensure DB update is complete
        setTimeout(() => {
            console.log('Reloading sessions after termination...');
            loadSessions();
        }, 500);
    } catch (error) {
        console.error('Error terminating session:', error);
        showNotification(error.message || 'Failed to terminate session', 'error');
    }
}

async function trustDevice(sessionId) {
    try {
        const response = await fetch(`/user/sessions/${sessionId}/trust`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (!response.ok) throw new Error('Failed to trust device');

        const data = await response.json();
        showNotification(data.message, 'success');
        loadSessions(); // Reload sessions
    } catch (error) {
        console.error('Error trusting device:', error);
        showNotification('Failed to trust device', 'error');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
        type === 'success' ? 'bg-green-600 text-white' :
        type === 'error' ? 'bg-red-600 text-white' :
        'bg-blue-600 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>
@endpush
@endsection
