/**
 * Notification System
 * Handles database-stored notifications with bell icon dropdown
 */

// Import closeAllActionsMenus from file-folder module
import { closeAllActionsMenus } from './file-folder.js';

// CSRF helpers
function getMetaCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta && meta.content ? meta.content : '';
}
function getXsrfCookie() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

class NotificationManager {
    constructor() {
        this.bell = document.getElementById('notificationBell');
        this.badge = document.getElementById('notificationBadge');
        this.dropdown = document.getElementById('notificationDropdown');
        this.list = document.getElementById('notificationsList');
        this.markAllReadBtn = document.getElementById('markAllRead');
        this.deleteAllBtn = document.getElementById('deleteAllNotifications');
        this.viewAllBtn = document.getElementById('viewAllNotifications');
        // this.isOpen = false;

        this.init();
    }

    init() {
        // Guard: if required DOM nodes are missing, skip setup gracefully
        if (!this.bell || !this.dropdown || !this.list || !this.badge) {
            console.debug('[Notifications] UI elements not found. Skipping NotificationManager initialization.');
            return;
        }

        // Event listeners
        
        /*this.bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        */

        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }

        if (this.deleteAllBtn) {
            this.deleteAllBtn.addEventListener('click', () => {
                this.deleteAllNotifications();
            });
        }

        if (this.viewAllBtn) {
            this.viewAllBtn.addEventListener('click', async (e) => {
                e.preventDefault(); // Prevent default link behavior

                // Show loading state
                const originalText = this.viewAllBtn.textContent;
                this.viewAllBtn.textContent = 'Loading...';
                this.viewAllBtn.style.pointerEvents = 'none'; // Disable clicks during loading

                try {
                    await this.loadNotifications(0); // Load all notifications (no limit)
                } finally {
                    // Restore original text and enable clicks
                    this.viewAllBtn.textContent = originalText;
                    this.viewAllBtn.style.pointerEvents = 'auto';
                }
            });
        }

        // Close dropdown when clicking outside
        /* document.addEventListener('click', (e) => {
            if (!this.dropdown.contains(e.target) && !this.bell.contains(e.target)) {
                this.closeDropdown();
            }
        }); */

        // Load initial notifications and count
        this.loadNotifications();
        this.updateUnreadCount();

        // Poll for new notifications every 30 seconds
        this._poller = setInterval(() => {
            this.updateUnreadCount();
        }, 30000);
    }

    /*
    async toggleDropdown() {
        if (this.isOpen) {
            this.closeDropdown();
        } else {
            // Close all other dropdowns first (except language which is nested)
            if (window.closeAllDropdowns) {
                window.closeAllDropdowns(['notification', 'language']);
            }
            // Close all actions menus
            if (typeof closeAllActionsMenus === 'function') closeAllActionsMenus();
            
            // Open immediately, then load content
            this.openDropdown();
            this.loadNotifications();
        }
    }

    openDropdown() {
        // Make sure any hidden state is cleared across Tailwind variants
        this.dropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]', 'scale-95');
        this.dropdown.classList.add('opacity-100', 'visible', 'translate-y-0', 'scale-100');
        this.isOpen = true;
    }

    closeDropdown() {
        this.dropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]', 'scale-95');
        this.dropdown.classList.remove('opacity-100', 'visible', 'translate-y-0', 'scale-100');
        this.isOpen = false;
    }
    */

    async loadNotifications(limit = 5) {
        try {
            const response = await fetch('/notifications?limit=' + limit, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getMetaCsrf(),
                    'X-XSRF-TOKEN': getXsrfCookie(),
                },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error('Failed to load notifications');

            const data = await response.json();
            this.renderNotifications(data.notifications);
            this.updateBadge(data.unread_count);
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.list.innerHTML = '<div class="p-4 text-center text-gray-200">Failed to load notifications</div>';
        }
    }

    renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            this.list.innerHTML = '<div class="p-4 text-center text-gray-400">No notifications</div>';
            return;
        }

        this.list.innerHTML = notifications.map(notification => {
            // Check if read
            const isRead = notification.read_at;
            
            // Assign colors based on your new style guide
            const itemBg = isRead ? 'bg-[#3C3F58]' : 'bg-[#55597C]';
            const titleColor = 'text-white';
            // Using text-gray-400 for subtext for better contrast against the white title
            const messageColor = 'text-gray-400'; 
            const hoverEffect = 'hover:brightness-110';
            const borderColor = 'border-b border-gray-900/50'; // Dark border

            return `
            <div class="notification-item group ${itemBg} ${borderColor} p-4 ${hoverEffect} transition-all" data-id="${notification.id}">
                <div class="flex items-start gap-3">
                    <div class="notification-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${this.getNotificationIconClass(notification.type)}">
                         ${this.getNotificationIcon(notification.type)}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium ${titleColor} truncate">${notification.title}</h4>
                            <div class="flex-shrink-0 flex items-center gap-2 ml-2">
                                <span class="text-xs text-gray-400">${this.formatRelativeTime(notification.created_at)}</span>
                                <button class="delete-notification-btn text-gray-400 hover:text-white text-xl leading-none" data-id="${notification.id}" title="Delete notification">×</button>
                            </div>
                        </div>
                        <p class="text-sm ${messageColor} mt-1">${notification.message}</p>
                        ${!notification.read_at ?
                        '<div class="w-2 h-2 bg-blue-500 rounded-full mt-2" title="Unread"></div>' : ''}
                    </div>
                </div>
            </div>
            `;
        }).join('');


        // Add click handlers for marking as read and deleting
        this.list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', (e) => {
                // Don't mark as read if clicking delete button
                if (!e.target.classList.contains('delete-notification-btn')) {
                    const id = item.dataset.id;
                    this.markAsRead(id);
                }
            });
        });

        // Add click handlers for delete buttons
        this.list.querySelectorAll('.delete-notification-btn').forEach(deleteBtn => {
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent marking as read
                const id = deleteBtn.dataset.id;
                this.deleteNotification(id);
            });
        });
    }

    getNotificationIcon(type) {
        // Using SVGs to match your storyboard image
        const icons = {
            success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
            error: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            warning: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            info: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };
        return icons[type] || icons.info; // Default to info icon
    }

    getNotificationIconClass(type) {
        const classes = {
            success: 'bg-green-100 text-green-600',
            error: 'bg-red-100 text-red-600',
            warning: 'bg-yellow-100 text-yellow-600',
            info: 'bg-blue-100 text-blue-600'
        };
        return classes[type] || 'bg-blue-100 text-blue-600';
    }

    formatRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}d ago`;
        
        return date.toLocaleDateString();
    }

    async updateUnreadCount() {
        try {
            const response = await fetch('/notifications/unread-count', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getMetaCsrf(),
                    'X-XSRF-TOKEN': getXsrfCookie(),
                },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error('Failed to get unread count');

            const data = await response.json();
            this.updateBadge(data.unread_count);
        } catch (error) {
            console.error('Error updating unread count:', error);
        }
    }

    updateBadge(count) {
        if (!this.badge) return;
        if (count > 0) {
            this.badge.textContent = count > 99 ? '99+' : count;
            this.badge.classList.remove('hidden');
        } else {
            this.badge.classList.add('hidden');
        }
    }

    async markAsRead(id) {
        try {
            const response = await fetch(`/notifications/${id}/read`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getMetaCsrf(),
                    'X-XSRF-TOKEN': getXsrfCookie(),
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                this.loadNotifications();
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getMetaCsrf(),
                    'X-XSRF-TOKEN': getXsrfCookie(),
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                this.loadNotifications();
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    async deleteNotification(id) {
        try {
            const response = await fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getMetaCsrf(),
                    'X-XSRF-TOKEN': getXsrfCookie(),
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                this.loadNotifications();
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    }

    async deleteAllNotifications() {
        // Show confirmation dialog
        if (!window.confirm('Are you sure you want to delete all notifications? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('/notifications', {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getMetaCsrf(),
                    'X-XSRF-TOKEN': getXsrfCookie(),
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                
                // Show success message
                if (window.showNotification) {
                    window.showNotification(data.message || 'All notifications deleted', 'success');
                }
                
                // Reload notifications and update count
                this.loadNotifications();
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Error deleting all notifications:', error);
            if (window.showNotification) {
                window.showNotification('Failed to delete notifications', 'error');
            }
        }
    }

    // Create a new notification
    async createNotification(type, title, message, data = null) {
        try {
            const response = await fetch('/notifications', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getMetaCsrf(),
                    'X-XSRF-TOKEN': getXsrfCookie(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    user_id: window.currentUserId || window.userId, // Fallback to userId
                    type,
                    title,
                    message,
                    data
                })
            });

            if (response.ok) {
                this.updateUnreadCount();
                // Optionally show a brief visual indication
                this.bell.classList.add('animate-pulse');
                setTimeout(() => {
                    this.bell.classList.remove('animate-pulse');
                }, 2000);
            }
        } catch (error) {
            console.error('Error creating notification:', error);
        }
    }

    // Helper methods for common notification types
    showSuccess(title, message, data = null) {
        return this.createNotification('success', title, message, data);
    }

    showError(title, message, data = null) {
        return this.createNotification('error', title, message, data);
    }

    showWarning(title, message, data = null) {
        return this.createNotification('warning', title, message, data);
    }

    showInfo(title, message, data = null) {
        return this.createNotification('info', title, message, data);
    }

}
// Export for use in other modules
export { NotificationManager };
