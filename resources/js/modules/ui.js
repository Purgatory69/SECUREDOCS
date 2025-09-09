// Contains general UI helper functions and initializers.

/**
 * Creates and displays a notification toast.
 * @param {string} message The message to display.
 * @param {string} type The type of notification ('info', 'success', 'error', 'warning').
 */
export function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.error('Notification container not found.');
        return;
    }

// Expose globally for modules that do not import this directly
if (typeof window !== 'undefined' && !window.showNotification) {
    window.showNotification = showNotification;
}

    const icons = {
        info: 'ℹ️',
        success: '✅',
        error: '❌',
        warning: '⚠️'
    };

    const colors = {
        info: 'bg-blue-500',
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500'
    };

    const icon = icons[type] || icons['info'];
    const color = colors[type] || colors['info'];

    const notification = document.createElement('div');
    notification.className = `flex items-center ${color} text-white text-sm font-bold px-4 py-3 rounded-md shadow-lg transform transition-all duration-300 translate-y-4 opacity-0`;
    notification.innerHTML = `<span>${icon}</span><p class="ml-2">${escapeHtml(message)}</p>`;

    container.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-y-4', 'opacity-0');
    }, 100);

    // Auto-dismiss
    setTimeout(() => {
        notification.classList.add('opacity-0');
        notification.addEventListener('transitionend', () => notification.remove());
    }, 5000);
}

/**
 * Escapes HTML to prevent XSS attacks.
 * @param {string} str The string to escape.
 * @returns {string} The escaped string.
 */
export function escapeHtml(str) {
    if (str === null || typeof str === 'undefined') return '';
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

/**
 * Initializes the view toggling logic between 'My Documents' and 'Trash'.
 * @param {function} loadUserFiles - The function to load the main file view.
 * @param {function} loadTrashItems - The function to load the trash view.
 * @param {object} state - An object containing the current state (lastMainSearch).
 */
export function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

export function initializeNewDropdown() {
    const newButton = document.getElementById('newBtn');
    const newDropdown = document.getElementById('newDropdown');

    newButton?.addEventListener('click', (e) => {
        e.stopPropagation();
        newDropdown.classList.toggle('hidden');
        newDropdown.classList.toggle('opacity-0');
        newDropdown.classList.toggle('invisible');
        newDropdown.classList.toggle('translate-y-[-10px]');
    });

    document.addEventListener('click', (e) => {
        if (!newButton?.contains(e.target) && !newDropdown?.contains(e.target)) {
            newDropdown?.classList.add('hidden', 'opacity-0', 'invisible', 'translate-y-[-10px]');
        }
    });
}

export function initializeUserProfile() {
    const userProfileBtn = document.getElementById('userProfileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    if (!userProfileBtn || !profileDropdown) {
        console.debug('Profile dropdown elements not found - skipping profile initialization');
        return;
    }

    userProfileBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        profileDropdown.classList.toggle('opacity-0');
        profileDropdown.classList.toggle('invisible');
        profileDropdown.classList.toggle('translate-y-[-10px]');
        // Also toggle scale for a smoother pop animation and to avoid staying scaled down
        profileDropdown.classList.toggle('scale-95');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (event) {
        if (!profileDropdown.contains(event.target) && !userProfileBtn.contains(event.target)) {
            profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]', 'scale-95');
        }
    });
}

export function updateBreadcrumbsDisplay(breadcrumbs) {
    const breadcrumbsContainer = document.getElementById('breadcrumbsContainer') || document.getElementById('breadcrumbs');
    if (!breadcrumbsContainer) return;

    breadcrumbsContainer.innerHTML = breadcrumbs.map((crumb, index) => {
        if (index === breadcrumbs.length - 1) {
            return `<span class="text-text-primary font-semibold">${escapeHtml(crumb.name)}</span>`;
        }
        return `<a href="#" class="text-text-secondary hover:text-primary" data-folder-id="${crumb.id}">${escapeHtml(crumb.name)}</a> <span class="mx-2 text-text-secondary">/</span>`;
    }).join('');
}

export function initializeUi(dependencies) {
    const { loadUserFiles, loadTrashItems, loadBlockchainItems, state } = dependencies;
    
    initializeNewDropdown();
    initializeUserProfile();
    initializeModalSystem();
    initializeViewToggling(loadUserFiles, loadTrashItems, loadBlockchainItems, state);
}

export function initializeViewToggling(loadUserFiles, loadTrashItems, loadBlockchainItems, state) {
    const myDocumentsLink = document.getElementById('my-documents-link');
    const trashLink = document.getElementById('trash-link');
    const blockchainLink = document.getElementById('blockchain-storage-link');
    const newButton = document.getElementById('new-button-container');
    const headerTitle = document.getElementById('header-title');

    function clearActiveStates() {
        myDocumentsLink?.classList.remove('bg-primary', 'text-white');
        trashLink?.classList.remove('bg-primary', 'text-white');
        blockchainLink?.classList.remove('bg-primary', 'text-white');
    }

    myDocumentsLink?.addEventListener('click', (e) => {
        e.preventDefault();
        headerTitle.textContent = 'My Documents';
        newButton.style.display = 'block';
        clearActiveStates();
        myDocumentsLink.classList.add('bg-primary', 'text-white');
        // Use the state object to get the last search query
        loadUserFiles(state.lastMainSearch, 1, null);
    });

    trashLink?.addEventListener('click', (e) => {
        e.preventDefault();
        headerTitle.textContent = 'Trash';
        newButton.style.display = 'none';
        clearActiveStates();
        trashLink.classList.add('bg-primary', 'text-white');
        loadTrashItems();
    });

    // Security Dashboard feature removed

    blockchainLink?.addEventListener('click', (e) => {
        e.preventDefault();
        headerTitle.textContent = 'Blockchain Storage';
        newButton.style.display = 'block'; // Show new button for blockchain upload
        clearActiveStates();
        blockchainLink.classList.add('bg-primary', 'text-white');
        // Keep page-based blockchain view functional (if implemented)
        try { typeof loadBlockchainItems === 'function' && loadBlockchainItems(); } catch (_) {}
        // Intentionally do NOT open the blockchain modal.
    });
}

// Initialize tooltips for elements with data-tooltip attribute
export function initializeTooltips() {
    // Remove existing tooltips
    document.querySelectorAll('.tooltip').forEach(tooltip => tooltip.remove());
    
    // Find all elements with data-tooltip
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
        element.addEventListener('focus', showTooltip);
        element.addEventListener('blur', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const tooltipText = element.getAttribute('data-tooltip');
    if (!tooltipText) return;

    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip absolute bg-gray-800 text-white text-xs rounded py-1 px-2 z-50 pointer-events-none';
    tooltip.textContent = tooltipText;
    tooltip.style.whiteSpace = 'nowrap';
    
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltipRect.width / 2)}px`;
    tooltip.style.top = `${rect.top - tooltipRect.height - 5}px`;
    
    // Ensure tooltip stays within viewport
    if (tooltip.offsetLeft < 5) {
        tooltip.style.left = '5px';
    }
    if (tooltip.offsetLeft + tooltipRect.width > window.innerWidth - 5) {
        tooltip.style.left = `${window.innerWidth - tooltipRect.width - 5}px`;
    }
    if (tooltip.offsetTop < 5) {
        tooltip.style.top = `${rect.bottom + 5}px`;
    }
}

function hideTooltip() {
    document.querySelectorAll('.tooltip').forEach(tooltip => tooltip.remove());
}


// ------------------------------
// Generic Modal Helpers
// ------------------------------
export function openModalById(id) {
    const el = document.getElementById(id);
    if (!el) {
        console.warn(`Modal #${id} not found`);
        return;
    }
    el.classList.remove('hidden');
    // prevent background scroll
    document.documentElement.style.overflow = 'hidden';
}

export function closeModalById(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    // restore background scroll if no modals are open
    const anyOpen = document.querySelector('.fixed.inset-0.z-50:not(.hidden)');
    if (!anyOpen) {
        document.documentElement.style.overflow = '';
    }
}

export function initializeModalSystem() {
    // Attribute-based open/close bindings
    document.querySelectorAll('[data-modal-open]')
        .forEach(btn => btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-modal-open');
            if (id) openModalById(id);
        }));

    document.querySelectorAll('[data-modal-close]')
        .forEach(btn => btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-modal-close');
            if (id) closeModalById(id);
        }));

    // Close on ESC for whichever modal is open
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0.z-50:not(.hidden)')
                .forEach(el => el.classList.add('hidden'));
            document.documentElement.style.overflow = '';
        }
    });

    // Expose globally so you can open via console or inline handlers
    if (typeof window !== 'undefined') {
        window.openModal = openModalById;
        window.closeModal = closeModalById;
    }
}

