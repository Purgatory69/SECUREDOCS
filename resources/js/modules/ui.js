// Contains general UI helper functions and initializers.

// Import closeAllActionsMenus from file-folder module
import { closeAllActionsMenus } from './file-folder.js';
// Import blockchain cleanup function
import { cleanupBlockchainUI } from './blockchain-page.js';

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

// Track which dropdown is currently active
let activeDropdown = null;

// Close all dropdowns or specific ones
function closeAllDropdowns(except = []) {
    const dropdowns = {
        new: { element: document.getElementById('newDropdown'), classes: ['opacity-0', 'invisible', 'translate-y-[-10px]'], arrow: document.getElementById('uploadIcon') },
        profile: { element: document.getElementById('profileDropdown'), classes: ['opacity-0', 'invisible', 'translate-y-[-10px]'] },
        language: { element: document.getElementById('headerLanguageSubmenu2'), classes: ['opacity-0', 'invisible', 'translate-y-[-10px]'], arrow: document.getElementById('langCaret') },
        notification: { element: document.getElementById('notificationDropdown'), classes: ['opacity-0', 'invisible', 'translate-y-[-10px]'] },
        bundlrWallet: { element: document.getElementById('bundlrWalletDropdown'), classes: ['opacity-0', 'invisible', 'translate-y-[-10px]'] }
    };

    for (const [key, config] of Object.entries(dropdowns)) {
        if (!except.includes(key) && config.element) {
            config.classes.forEach(cls => config.element.classList.add(cls));
            if (config.arrow) {
                config.arrow.style.transform = 'rotate(0deg)';
            }
            if (key === 'language') {
                config.element.style.pointerEvents = 'none';
            }
        }
    }
}

// Export for use in other modules
window.closeAllDropdowns = closeAllDropdowns;

export function initializeNewDropdown() {
    const newButton = document.getElementById('newBtn');
    const newDropdown = document.getElementById('newDropdown');
    const arrow = document.getElementById('uploadIcon');
    
    // Find options inside the dropdown
    const uploadFileOption = document.getElementById('uploadFileOption');
    const arweaveBtn = document.getElementById('openClientArweaveBtn');
    const createFolderOption = document.getElementById('createFolderOption');

// Guard against missing dropdown on pages that don't include it
    if (!newButton || !newDropdown || !arrow) return;

    // Helper function to close the menu and reset state
    const closeNewDropdown = () => {
        newDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
        arrow.style.transform = 'rotate(0deg)';
        activeDropdown = null;
    }

newButton.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = newDropdown.classList.contains('opacity-0');
         
   
        if (isHidden) {
            // Close other dropdowns first
            closeAllDropdowns(['new']);
            // Close all actions menus
            if (typeof closeAllActionsMenus === 'function') 
            closeAllActionsMenus();
            activeDropdown = 'new';
            // Show dropdown
            newDropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]');
            arrow.style.transform = 'rotate(180deg)';
        } else {
      // Hide dropdown
            closeNewDropdown();
        }
    });

    // Add listeners to the options to close the menu when they are clicked
    uploadFileOption?.addEventListener('click', closeNewDropdown);
    arweaveBtn?.addEventListener('click', closeNewDropdown);
    createFolderOption?.addEventListener('click', closeNewDropdown);
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
        
        // Check if dropdown is currently hidden
        const isHidden = profileDropdown.classList.contains('opacity-0') || 
                         profileDropdown.classList.contains('invisible');
        
        if (isHidden) {
            // Close other dropdowns first (but keep profile group)
            closeAllDropdowns(['profile', 'language']);
            // Close all actions menus
            if (typeof closeAllActionsMenus === 'function') closeAllActionsMenus();
            activeDropdown = 'profile';
            
            // Show profile dropdown and remove overflow-hidden to allow nested language dropdown
            profileDropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]', 'overflow-hidden');
        } else {
            // Hide profile dropdown
            profileDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]', 'overflow-hidden');
            activeDropdown = null;
        }
    });
}

export function updateBreadcrumbsDisplay(breadcrumbs, currentView = 'main') {
    const container = document.getElementById('breadcrumbsContainer');
    const dropdown = document.getElementById('breadcrumbsDropdown');
    const dropdownMenu = document.getElementById('breadcrumbsDropdownMenu');
    const pathContainer = document.getElementById('breadcrumbsPath');
    
    if (!container || !dropdown || !dropdownMenu || !pathContainer) return;

    // Clear existing content
    dropdownMenu.innerHTML = '';
    pathContainer.innerHTML = '';

    // Create base breadcrumbs based on current view
    let baseBreadcrumbs = [];
    
    switch (currentView) {
        case 'trash':
            baseBreadcrumbs = [{ id: 'trash', name: 'Trash' }];
            break;
        case 'blockchain':
            baseBreadcrumbs = [{ id: 'blockchain', name: 'Blockchain Storage' }];
            break;
        case 'main':
        default:
            baseBreadcrumbs = [{ id: null, name: 'My Documents' }];
            break;
    }

    // For main view, append folder breadcrumbs
    let allBreadcrumbs = baseBreadcrumbs;
    if (currentView === 'main' && breadcrumbs && breadcrumbs.length > 0) {
        // Filter out the root "My Documents" if it exists in breadcrumbs
        const folderBreadcrumbs = breadcrumbs.filter(crumb => crumb.id !== null);
        allBreadcrumbs = [...baseBreadcrumbs, ...folderBreadcrumbs];
    }

    if (allBreadcrumbs.length === 0) {
        dropdown.classList.add('hidden');
        return;
    }

    // Google Drive-style logic: show only current folder when path is long
    const shouldCollapse = allBreadcrumbs.length > 3;
    
    if (shouldCollapse) {
        // Show three-dot menu
        dropdown.classList.remove('hidden');
        
        // Add hidden breadcrumbs to dropdown (all except last 2)
        const hiddenCrumbs = allBreadcrumbs.slice(0, -2);
        hiddenCrumbs.forEach(crumb => {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'block px-3 py-2 text-sm text-gray-300 hover:bg-[#2A2D47] rounded';
            item.textContent = truncateText(crumb.name, 30);
            item.title = crumb.name; // Show full name on hover
            item.dataset.folderId = crumb.id;
            dropdownMenu.appendChild(item);
        });
        
        // Show only last 2 breadcrumbs in main path
        const visibleCrumbs = allBreadcrumbs.slice(-3);
        renderBreadcrumbPath(visibleCrumbs, pathContainer, currentView);
    } else {
        // Show all breadcrumbs normally
        dropdown.classList.add('hidden');
        renderBreadcrumbPath(allBreadcrumbs, pathContainer, currentView);
    }
}

function renderBreadcrumbPath(breadcrumbs, container, currentView) {
    breadcrumbs.forEach((crumb, index) => {
        if (index > 0) {
            const separator = document.createElement('span');
            separator.innerHTML = `
                <svg class="w-3 h-3 mx-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
            `;
            container.appendChild(separator);
        }

        const link = document.createElement('a');
        link.href = '#';
        link.className = 'px-2 py-1 rounded text-sm transition-colors max-w-[200px] truncate inline-block';
        link.textContent = truncateText(crumb.name, 25);
        link.title = crumb.name; // Show full name on hover
        link.dataset.folderId = crumb.id;

        // Disable navigation for non-main views
        if (currentView !== 'main' || crumb.id === 'trash' || crumb.id === 'blockchain') {
            link.style.pointerEvents = 'none';
            link.style.cursor = 'default';
        }

        if (index === breadcrumbs.length - 1) {
            link.className += ' text-white font-medium bg-[#3C3F58]';
        } else {
            link.className += ' text-gray-400 hover:bg-[#2A2D47]';
        }

        container.appendChild(link);
    });
}

// Helper function to truncate text
function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - 3) + '...';
}

function initializeBreadcrumbsDropdown() {
    const menuBtn = document.getElementById('breadcrumbsMenuBtn');
    const dropdownMenu = document.getElementById('breadcrumbsDropdownMenu');
    
    if (!menuBtn || !dropdownMenu) return;
    
    menuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!dropdownMenu.contains(e.target) && !menuBtn.contains(e.target)) {
            dropdownMenu.classList.add('hidden');
        }
    });
    
    // Handle clicks on dropdown items
    dropdownMenu.addEventListener('click', (e) => {
        if (e.target.tagName === 'A' && e.target.dataset.folderId) {
            e.preventDefault();
            const folderId = e.target.dataset.folderId === 'null' ? null : e.target.dataset.folderId;
            const folderName = e.target.textContent;
            
            // Dispatch navigation event that will be caught by file-folder module
            window.dispatchEvent(new CustomEvent('navigate-to-folder', {
                detail: { folderId, folderName }
            }));
            
            dropdownMenu.classList.add('hidden');
        }
    });
}

function initializeLanguageDropdown() {
    const toggle = document.getElementById('headerLanguageToggle2');
    const dropdown = document.getElementById('headerLanguageSubmenu2');
    const arrow = document.getElementById('langCaret');
    
    if (!toggle || !dropdown) return;
    
    // Set initial state
    dropdown.style.pointerEvents = 'none';
    
    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // Check if dropdown is currently hidden
        const isHidden = dropdown.classList.contains('opacity-0') ||
                         dropdown.classList.contains('invisible');
        
        if (isHidden) {
            // Don't close profile dropdown since language is nested inside it
            activeDropdown = 'language';
            
            // Open dropdown
            dropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]');
            dropdown.style.pointerEvents = 'auto';
            if (arrow) {
                arrow.style.transform = 'rotate(180deg)';
            }
        } else {
            // Close dropdown
            dropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            dropdown.style.pointerEvents = 'none';
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
            activeDropdown = 'profile'; // Return to profile state
        }
    });
}

function initializeNotificationDropdown() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (!notificationBell || !notificationDropdown) {
        console.debug('Notification dropdown elements not found - skipping notification initialization');
        return;
    }

    notificationBell.addEventListener('click', function (event) {
        event.stopPropagation();

        // Check if dropdown is currently hidden
        const isHidden = notificationDropdown.classList.contains('opacity-0');

        if (isHidden) {
            // Close other dropdowns first
            closeAllDropdowns(['notification']);
            if (typeof closeAllActionsMenus === 'function') closeAllActionsMenus();
            activeDropdown = 'notification';

            // Show notification dropdown
            notificationDropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]');

            // Fire a custom event to tell notifications.js to load
            window.dispatchEvent(new CustomEvent('open-notifications'));

        } else {
            // Hide notification dropdown
            notificationDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            activeDropdown = null;
        }
    });
}

function initializeBundlrWalletDropdown() {
    const bundlrBtn = document.getElementById('bundlrWalletBtn');
    const bundlrDropdown = document.getElementById('bundlrWalletDropdown');

    if (!bundlrBtn || !bundlrDropdown) {
        return; // Don't run if the elements don't exist
    }

    bundlrBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        
        // Check if dropdown is currently hidden
        const isHidden = bundlrDropdown.classList.contains('opacity-0');
        
        if (isHidden) {
             // Close other dropdowns first
            closeAllDropdowns(['bundlrWallet']);
            if (typeof closeAllActionsMenus === 'function') closeAllActionsMenus();
             activeDropdown = 'bundlrWallet';

            // Show dropdown
            bundlrDropdown.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]');

            // Fire a custom event to tell bundlr-wallet-widget.js to update the balance
            window.dispatchEvent(new CustomEvent('open-bundlr-wallet'));

             } else {
            // Hide dropdown
            bundlrDropdown.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            activeDropdown = null;
        }
    });
}

// --- ADD THE NEW SCRIPT CODE HERE ---

/**
 * Closes all open custom select dropdowns.
 * @param {HTMLElement} [except] - A menu element to keep open.
 */
function closeAllCustomSelects(except = null) {
    document.querySelectorAll('.custom-select-menu').forEach(menu => {
        if (menu !== except) {
            menu.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
            
            // Find this menu's trigger to rotate the caret back
            const trigger = document.querySelector(`[data-dropdown-toggle="${menu.id}"]`);
            if (trigger) {
                trigger.querySelector('.custom-select-caret')?.classList.remove('rotate-180');
            }
        }
    });
}

/**
 * Initializes all custom select dropdowns inside the Advanced Search modal.
 * This finds the hidden <input> tags by their ID (like 'searchMatchType')
 * and updates their .value, so your search.js script works perfectly.
 */
export function initializeAdvancedSearchSelects() {
    const modal = document.getElementById('advancedSearchModal');
    if (!modal) return;

    // Find all trigger buttons
    const triggers = modal.querySelectorAll('.custom-select-trigger');

    triggers.forEach(trigger => {
        const menuId = trigger.getAttribute('data-dropdown-toggle');
        const menu = document.getElementById(menuId);
        const label = trigger.querySelector('.custom-select-label');
        const caret = trigger.querySelector('.custom-select-caret');
        
        // Find the hidden input.
        const parentDiv = trigger.closest('.relative');
        const hiddenInput = parentDiv.querySelector('input[type="hidden"]');

        if (!menu || !label || !hiddenInput) {
            console.warn('Custom select is missing parts:', { trigger, menu, label, hiddenInput });
            return;
        }

        // --- 1. Toggle this menu ---
        trigger.addEventListener('click', (e) => {
            e.stopPropagation(); // Stop click from bubbling to document
            const isHidden = menu.classList.contains('opacity-0');

            // Close all *other* dropdowns first
            closeAllCustomSelects(isHidden ? menu : null);

            if (isHidden) {
                // Open this one
                menu.classList.remove('opacity-0', 'invisible', 'translate-y-[-10px]');
                caret?.classList.add('rotate-180');
            } else {
                // Close this one
                menu.classList.add('opacity-0', 'invisible', 'translate-y-[-10px]');
                caret?.classList.remove('rotate-180');
            }
        });

        // --- 2. Handle option selection ---
        menu.querySelectorAll('.custom-select-option').forEach(option => {
            option.addEventListener('click', () => {
                const selectedValue = option.getAttribute('data-value');
                const selectedText = option.textContent;

                // Update the visible label
                label.textContent = selectedText;
                
                // Update the hidden input's value (this is what your search.js reads)
                hiddenInput.value = selectedValue;

                // Close the menu
                closeAllCustomSelects();
            });
        });
    });

    // --- 3. Global click to close ---
    modal.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select-trigger')) {
            closeAllCustomSelects();
        }
    });
}

// --- END OF NEW SCRIPT CODE ---

export function initializeUi(dependencies) {
    const { loadUserFiles, loadTrashItems, loadSharedFiles, loadBlockchainItems, state } = dependencies;
    
    initializeNewDropdown();
    initializeUserProfile();
    initializeModalSystem();
    initializeBreadcrumbsDropdown();
    initializeLanguageDropdown();
    initializeNotificationDropdown();
    initializeBundlrWalletDropdown();
    initializeViewToggling(loadUserFiles, loadTrashItems, loadSharedFiles, loadBlockchainItems, state);
    
    // Global click handler to close all dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const dropdownElements = {
            new: [document.getElementById('newBtn'), document.getElementById('newDropdown')],
            profile: [document.getElementById('userProfileBtn'), document.getElementById('profileDropdown')],
            language: [document.getElementById('headerLanguageToggle2'), document.getElementById('headerLanguageSubmenu2')],
            notification: [document.getElementById('notificationBell'), document.getElementById('notificationDropdown')],
            bundlrWallet: [document.getElementById('bundlrWalletBtn'), document.getElementById('bundlrWalletDropdown')]
        };
        
        // Check if click is inside any dropdown
        let clickedInside = false;
        for (const elements of Object.values(dropdownElements)) {
            if (elements.some(el => el && el.contains(e.target))) {
                clickedInside = true;
                break;
            }
        }
        
        // If clicked outside all dropdowns, close everything
        if (!clickedInside) {
            closeAllDropdowns();
            activeDropdown = null;
        }
    });
}

export function initializeViewToggling(loadUserFiles, loadTrashItems, loadSharedFiles, loadBlockchainItems, stateObj) {
    // Function to get translated text, checking multiple times if needed
    const getMyDocumentsText = () => {
        return window.I18N?.dbMyDocuments || 
               document.getElementById('db-js-localization-data')?.getAttribute('data-my-documents') || 
               'My Documents';
    };
    
    const myDocumentsLink = document.getElementById('my-documents-link');
    const sharedWithMeLink = document.getElementById('shared-with-me-link');
    const trashLink = document.getElementById('trash-link');
    const blockchainLink = document.getElementById('blockchain-storage-link');
    const newButton = document.getElementById('new-button-container');
    const headerTitle = document.getElementById('header-title');

    function clearActiveStates() {
        myDocumentsLink?.classList.remove('bg-primary', 'text-white');
        sharedWithMeLink?.classList.remove('bg-primary', 'text-white');
        trashLink?.classList.remove('bg-primary', 'text-white');
        blockchainLink?.classList.remove('bg-primary', 'text-white');
    }

    function hideNavigationElements() {
        // Hide the breadcrumb container and reset navigation
        const breadcrumbContainer = document.getElementById('breadcrumbsContainer');
        if (breadcrumbContainer) {
            breadcrumbContainer.style.display = 'none';
        }
        
        // Hide new folder buttons
        const newButton = document.getElementById('new-button');
        const createFolderBtn = document.getElementById('create-folder-btn');
        if (newButton) newButton.style.display = 'none';
        if (createFolderBtn) createFolderBtn.style.display = 'none';
        
        // Hide advanced search button
        const advancedSearchBtn = document.getElementById('advanced-search-button');
        if (advancedSearchBtn) advancedSearchBtn.style.display = 'none';
        
        // Hide view toggle buttons (grid/list)
        const viewToggleBtns = document.getElementById('viewToggleBtns');
        if (viewToggleBtns) viewToggleBtns.style.display = 'none';
    }

    function showMyDocumentsElements() {
        // Show the breadcrumb container
        const breadcrumbContainer = document.getElementById('breadcrumbsContainer');
        if (breadcrumbContainer) {
            breadcrumbContainer.style.display = 'flex';
        }
        
        // Show new folder buttons
        const newButton = document.getElementById('new-button');
        const createFolderBtn = document.getElementById('create-folder-btn');
        if (newButton) newButton.style.display = 'block';
        if (createFolderBtn) createFolderBtn.style.display = 'block';
        
        // Show advanced search button
        const advancedSearchBtn = document.getElementById('advanced-search-button');
        
        // Show view toggle buttons (grid/list)
        const viewToggleBtns = document.getElementById('viewToggleBtns');
        if (viewToggleBtns) viewToggleBtns.style.display = 'flex';
        if (advancedSearchBtn) advancedSearchBtn.style.display = 'flex';
    }

    function showBreadcrumbsForView(viewType) {
        // Always show breadcrumbs, but update them based on view
        const breadcrumbContainer = document.getElementById('breadcrumbsContainer');
        if (breadcrumbContainer) {
            breadcrumbContainer.style.display = 'flex';
        }
        
        // For main view, get current folder breadcrumbs from localStorage
        let breadcrumbsToShow = [];
        if (viewType === 'main') {
            try {
                const storedBreadcrumbs = localStorage.getItem('breadcrumbs');
                if (storedBreadcrumbs) {
                    breadcrumbsToShow = JSON.parse(storedBreadcrumbs);
                }
            } catch (e) {
                console.warn('Failed to parse breadcrumbs from localStorage:', e);
                breadcrumbsToShow = [];
            }
        }
        
        // Update breadcrumbs based on current view
        updateBreadcrumbsDisplay(breadcrumbsToShow, viewType);
        
        // Hide new folder buttons and advanced search for non-main views
        const newButton = document.getElementById('new-button');
        const createFolderBtn = document.getElementById('create-folder-btn');
        const advancedSearchBtn = document.getElementById('advanced-search-button');
        
        if (viewType !== 'main') {
            if (newButton) newButton.style.display = 'none';
            if (createFolderBtn) createFolderBtn.style.display = 'none';
            if (advancedSearchBtn) advancedSearchBtn.style.display = 'none';
        } else {
            // Show buttons for main view
            if (newButton) newButton.style.display = 'block';
            if (createFolderBtn) createFolderBtn.style.display = 'block';
            if (advancedSearchBtn) advancedSearchBtn.style.display = 'flex';
        }
    }

    myDocumentsLink?.addEventListener('click', (e) => {
        e.preventDefault();
        // Clean up blockchain UI elements when switching away
        cleanupBlockchainUI();
        
        const dbMyDocuments = getMyDocumentsText(); // Get fresh value on click
        if (headerTitle) headerTitle.textContent = dbMyDocuments;
        if (newButton) newButton.style.display = 'block';
        clearActiveStates();
        myDocumentsLink.classList.add('bg-primary', 'text-white');
        // Show My Documents specific elements
        showMyDocumentsElements();
        // Update breadcrumbs to show "My Documents"
        showBreadcrumbsForView('main');
        // Use the state object to get the last search query
        loadUserFiles(stateObj.lastMainSearch, 1, null);
    });

    sharedWithMeLink?.addEventListener('click', (e) => {
        e.preventDefault();
        // Clean up blockchain UI elements when switching away
        cleanupBlockchainUI();
        
        if (headerTitle) headerTitle.textContent = 'Shared with Me';
        if (newButton) newButton.style.display = 'none';
        clearActiveStates();
        sharedWithMeLink.classList.add('bg-primary', 'text-white');
        // Hide navigation elements for shared view
        hideNavigationElements();
        // Show breadcrumbs for shared view
        showBreadcrumbsForView('shared');
        loadSharedFiles();
    });

    trashLink?.addEventListener('click', (e) => {
        e.preventDefault();
        // Clean up blockchain UI elements when switching away
        cleanupBlockchainUI();
        
        if (headerTitle) headerTitle.textContent = 'Trash';
        if (newButton) newButton.style.display = 'none';
        clearActiveStates();
        trashLink.classList.add('bg-primary', 'text-white');
        // Hide navigation elements for trash view
        hideNavigationElements();
        // Show breadcrumbs for trash view
        showBreadcrumbsForView('trash');
        loadTrashItems();
    });

    // Security Dashboard feature removed

    blockchainLink?.addEventListener('click', (e) => {
        // Check if user is premium before allowing navigation
        if (!window.userIsPremium) {
            // Non-premium user - don't proceed with navigation
            return;
        }

        e.preventDefault();
        if (headerTitle) headerTitle.textContent = 'Blockchain Storage';
        if (newButton) newButton.style.display = 'block'; // Show new button for blockchain upload
        clearActiveStates();
        blockchainLink.classList.add('bg-primary', 'text-white');
        // Hide navigation elements (breadcrumbs, view toggle, etc.) for blockchain view
        hideNavigationElements();
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
    // Initialize premium upgrade modal
    initializePremiumModal();
    // Note: Permanent storage modal now handled by ArweavePayment class
    // But we still need the global function for the button click
    window.openPermanentStorageModal = function() {
        const modal = document.getElementById('permanentStorageModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    window.closePermanentStorageModal = function() {
        const modal = document.getElementById('permanentStorageModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
    
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
}

function initializePremiumModal() {
    // Premium upgrade modal functions (make global)
    window.showPremiumUpgradeModal = function(feature) {
        const modal = document.getElementById('premiumUpgradeModal');
        const modalText = document.getElementById('premiumModalText');
        
        const messages = {
            'blockchain': 'Blockchain storage provides immutable, decentralized file storage using Arweave technology. Upgrade to Premium to secure your documents forever.',
            'ai': 'AI Vectorization enables advanced search capabilities and intelligent document analysis. Upgrade to Premium to unlock AI-powered features.',
            'hybrid': 'Hybrid processing combines blockchain storage with AI analysis for maximum security and functionality. Upgrade to Premium for the complete solution.'
        };
        
        if (modalText) {
            modalText.textContent = messages[feature] || messages['blockchain'];
        }
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    window.closePremiumModal = function() {
        const modal = document.getElementById('premiumUpgradeModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // Close modal when clicking outside
    const premiumModal = document.getElementById('premiumUpgradeModal');
    if (premiumModal) {
        premiumModal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.closePremiumModal();
            }
        });
    }
}

// Removed duplicate initializePermanentStorageModal function
// Modal functions are now defined in initializeModalSystem above

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
    window.handleBlockchainClick = function(event) {
        // Check if user is premium
        if (!window.userIsPremium) {
            // Non-premium user - prevent navigation and show modal
            event.preventDefault();
            event.stopPropagation();
            showPremiumUpgradeModal('blockchain');
        }
        // Premium user - allow normal navigation flow (addEventListener will handle it)
    };
}

