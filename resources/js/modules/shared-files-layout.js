/**
 * Shared Files Grid/List View Rendering
 * This module handles rendering shared files in grid or list layout
 */

/**
 * Render shared files in grid layout
 */
export function renderSharedFilesGrid(sharedFiles) {
    const container = document.getElementById('filesContainer');
    if (!container) return;

    const cardsHtml = sharedFiles.map(sharedFile => {
        const file = sharedFile.copied_file;
        const originalShare = sharedFile.original_share;
        const sharedBy = originalShare.user;
        const fileSize = file.file_size ? formatFileSize(parseInt(file.file_size, 10)) : '';
        const modifiedDate = new Date(sharedFile.copied_at).toLocaleDateString();
        
        return `
            <div class="file-row bg-[#2A2D47] rounded-lg border border-[#4A4D6A] overflow-hidden hover:border-[#6B7280] transition-all cursor-pointer p-4" 
                 data-item-id="${file.id}" data-file-id="${file.id}" data-is-folder="${file.is_folder}">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <div class="w-12 h-12 flex items-center justify-center mb-2">
                            ${getFileIconSvg(file.file_name, file.is_folder)}
                        </div>
                        <div class="text-sm font-medium text-white truncate" title="${escapeHtml(file.file_name)}">${escapeHtml(file.file_name)}</div>
                    </div>
                    <button class="actions-menu-btn p-1 hover:bg-[#3C3F58] rounded flex-shrink-0" 
                            data-item-id="${file.id}" title="More actions">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center space-x-2 mb-2">
                    <input type="checkbox" class="file-checkbox rounded border-[#4A4D6A] text-[#f89c00] focus:ring-[#f89c00] bg-[#1F2235]" 
                           data-item-id="${file.id}">
                    <span class="text-xs text-gray-400 truncate">Shared by ${escapeHtml(sharedBy.name)}</span>
                </div>
                <div class="text-xs text-gray-400">
                    ${fileSize ? `<span>${fileSize}</span>` : ''} • ${modifiedDate}
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = `
        <div class="space-y-4">
            <!-- Header -->
            <div class="bg-[#2A2D47] border border-[#4A4D6A] rounded-lg px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-[#f89c00] rounded flex items-center justify-center">
                            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-white">Shared with Me</h2>
                            <p class="text-sm text-gray-400">${sharedFiles.length} files</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2" data-view-toggle-btns>
                        <button class="p-1.5 text-gray-400 hover:text-gray-300 hover:bg-[#3C3F58] rounded" data-view="list" title="List view">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <button class="p-1.5 text-[#f89c00] bg-[#3C3F58] rounded" data-view="grid" title="Grid view">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                ${cardsHtml}
            </div>
        </div>
    `;
}

/**
 * Render shared files in list layout
 */
export function renderSharedFilesList(sharedFiles) {
    const container = document.getElementById('filesContainer');
    if (!container) return;

    const filesHtml = sharedFiles.map(sharedFile => {
        const file = sharedFile.copied_file;
        const originalShare = sharedFile.original_share;
        const sharedBy = originalShare.user;
        const fileSize = file.file_size ? formatFileSize(parseInt(file.file_size, 10)) : '';
        const modifiedDate = new Date(sharedFile.copied_at).toLocaleDateString();
        
        return `
            <tr class="file-row hover:bg-[#2A2D47] border-b border-[#4A4D6A] cursor-pointer" 
                data-item-id="${file.id}" data-file-id="${file.id}" data-is-folder="${file.is_folder}">
                <td class="px-4 py-3">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" class="file-checkbox rounded border-[#4A4D6A] text-[#f89c00] focus:ring-[#f89c00] bg-[#2A2D47]" 
                               data-item-id="${file.id}">
                        <div class="w-8 h-8 flex items-center justify-center">
                            ${getFileIconSvg(file.file_name, file.is_folder)}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate">${escapeHtml(file.file_name)}</div>
                            <div class="text-xs text-gray-400">
                                Shared by ${escapeHtml(sharedBy.name)} • ${fileSize}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-300 text-right">
                    <div class="flex items-center justify-end space-x-2">
                        <span>${modifiedDate}</span>
                        <button class="actions-menu-btn p-1 hover:bg-[#3C3F58] rounded" 
                                data-item-id="${file.id}" aria-expanded="false" title="More actions">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    container.innerHTML = `
        <div class="bg-[#1F2235] rounded-lg border border-[#4A4D6A] overflow-hidden">
            <!-- Header -->
            <div class="bg-[#2A2D47] border-b border-[#4A4D6A] px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-[#f89c00] rounded flex items-center justify-center">
                            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-white">Shared with Me</h2>
                            <p class="text-sm text-gray-400">${sharedFiles.length} files</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2" data-view-toggle-btns>
                        <button class="p-1.5 text-[#f89c00] bg-[#3C3F58] rounded" data-view="list" title="List view">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <button class="p-1.5 text-gray-400 hover:text-gray-300 hover:bg-[#3C3F58] rounded" data-view="grid" title="Grid view">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#2A2D47] border-b border-[#4A4D6A]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" class="select-all-checkbox rounded border-[#4A4D6A] text-[#f89c00] focus:ring-[#f89c00] bg-[#2A2D47]">
                                    <span>NAME</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center justify-end space-x-1">
                                    <span>MODIFIED</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#4A4D6A]">
                        ${filesHtml}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}
