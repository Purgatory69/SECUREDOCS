@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#141326] text-white">
    <!-- Header -->
    <div class="bg-[#1F2235] border-b border-[#4A4D6A] px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Account Security</h1>
                <p class="text-gray-400 mt-1">Manage your active sessions and notification preferences</p>
            </div>
            <a href="{{ route('user.dashboard') }}" class="bg-[#3C3F58] hover:bg-[#4A4D6A] text-white px-4 py-2 rounded-lg transition-colors">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Active Sessions -->
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-[#f89c00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Active Sessions
                    </h2>
                    <button id="terminateAllBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        Terminate All Others
                    </button>
                </div>

                <div id="sessionsContainer" class="space-y-4">
                    <!-- Sessions will be loaded here -->
                    <div class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#f89c00]"></div>
                        <span class="ml-3 text-gray-400">Loading sessions...</span>
                    </div>
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-[#f89c00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2zM4 7h8V5H4v2z"></path>
                    </svg>
                    Notification Preferences
                </h2>

                <form id="notificationPreferencesForm" class="space-y-4">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-[#2A2D47] rounded-lg">
                            <div>
                                <label class="text-white font-medium">Email Notifications</label>
                                <p class="text-gray-400 text-sm">Receive notifications via email</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="email_notifications_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#f89c00]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#f89c00]"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-[#2A2D47] rounded-lg">
                            <div>
                                <label class="text-white font-medium">New Device Login Alerts</label>
                                <p class="text-gray-400 text-sm">Get notified when you log in from a new device</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="login_notifications_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#f89c00]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#f89c00]"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-[#2A2D47] rounded-lg">
                            <div>
                                <label class="text-white font-medium">Security Alerts</label>
                                <p class="text-gray-400 text-sm">Important security-related notifications</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="security_notifications_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#f89c00]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#f89c00]"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-[#2A2D47] rounded-lg">
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

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-[#f89c00] hover:bg-[#e88900] text-white font-medium py-3 px-4 rounded-lg transition-colors">
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 bg-[#1F2235] border border-[#4A4D6A] rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-[#f89c00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Recent Activity
            </h2>

            <div id="activityContainer" class="space-y-3">
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
                        <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition-colors">
                            Terminate All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
        
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
        console.error('Error loading notification preferences:', error);
        // Set default values if loading fails
        document.getElementById('email_notifications_enabled').checked = true;
        document.getElementById('login_notifications_enabled').checked = true;
        document.getElementById('security_notifications_enabled').checked = true;
        document.getElementById('activity_notifications_enabled').checked = false;
    }
}

async function loadRecentActivity() {
    try {
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

        if (!response.ok) throw new Error('Failed to load activity');

        const data = await response.json();
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
    const container = document.getElementById('activityContainer');
    
    if (activities.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-400">
                <p>No recent activity found.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = activities.map(activity => `
        <div class="flex items-center space-x-3 p-3 bg-[#2A2D47] rounded-lg">
            <div class="text-xl">${activity.activity_type_icon}</div>
            <div class="flex-1">
                <div class="text-white text-sm">${activity.description}</div>
                <div class="text-gray-400 text-xs">
                    ${activity.time_ago}
                    ${activity.location ? ` • ${activity.location}` : ''}
                    ${activity.device_type ? ` • ${activity.device_type}` : ''}
                </div>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getRiskLevelClass(activity.risk_level)}">
                    ${activity.risk_level_icon} ${activity.risk_level}
                </span>
            </div>
        </div>
    `).join('');
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
        const response = await fetch(`/user/sessions/${sessionId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (!response.ok) throw new Error('Failed to terminate session');

        const data = await response.json();
        showNotification(data.message, 'success');
        loadSessions(); // Reload sessions
    } catch (error) {
        console.error('Error terminating session:', error);
        showNotification('Failed to terminate session', 'error');
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
