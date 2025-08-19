// File Preview JavaScript
document.addEventListener('DOMContentLoaded', () => {
    initializePreview();
});

let currentFileData = null;
let currentFileUrl = null;

async function initializePreview() {
    // Get file ID from URL path: /files/{id}/preview
    const pathParts = window.location.pathname.split('/');
    const fileId = pathParts[2]; // /files/{id}/preview -> index 2
    
    if (!fileId || isNaN(fileId)) {
        showError('No file ID provided');
        return;
    }

    try {
        await loadFileData(fileId);
        setupEventListeners();
    } catch (error) {
        console.error('Error initializing preview:', error);
        showError('Failed to load file preview');
    }
}

async function loadFileData(fileId) {
    try {
        const response = await fetch(`/files/${fileId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to fetch file data');
        }

        currentFileData = await response.json();
        
        // Update UI with file information
        updateFileInfo();
        
        // Generate preview based on file type
        await generatePreview();
        
    } catch (error) {
        console.error('Error loading file data:', error);
        showError('Failed to load file information');
    }
}

function updateFileInfo() {
    if (!currentFileData) return;

    document.getElementById('fileName').textContent = currentFileData.file_name || 'Unknown File';
    document.getElementById('fileSize').textContent = formatFileSize(currentFileData.file_size || 0);
    document.getElementById('fileType').textContent = currentFileData.file_type || 'Unknown';
    document.getElementById('fileModified').textContent = formatDate(currentFileData.updated_at);
    document.getElementById('fileOwner').textContent = currentFileData.user?.name || 'Unknown';
    
    const fileInfo = document.getElementById('fileInfo');
    const fileSize = formatFileSize(currentFileData.file_size || 0);
    const fileType = currentFileData.file_type || 'Unknown type';
    fileInfo.textContent = `${fileSize} • ${fileType}`;
}

async function generatePreview() {
    if (!currentFileData || !currentFileData.file_path) {
        showUnsupportedPreview();
        return;
    }

    hideLoadingSpinner();

    const fileType = (currentFileData.file_type || '').toLowerCase();
    const mimeType = currentFileData.mime_type || '';
    const fileName = currentFileData.file_name || '';
    
    // Extract file extension as fallback for type detection
    const fileExtension = fileName.split('.').pop()?.toLowerCase() || '';
    
    // Get file URL from Supabase
    try {
        currentFileUrl = await getFileUrl(currentFileData.file_path);
        console.log('Generated file URL:', currentFileUrl);
    } catch (error) {
        console.error('Error getting file URL:', error);
        showUnsupportedPreview();
        return;
    }

    // Debug file type detection
    console.log('File type detection:', { fileName, fileType, mimeType, fileExtension, currentFileData });
    
    // Force document type for .docx files if type is unknown
    if (fileExtension === 'docx' && (fileType === 'unknown' || fileType === '' || !fileType)) {
        console.log('Forcing document preview for .docx file');
        showDocumentPreview();
        return;
    }
    
    // Determine preview type - prioritize file extension over stored type
    if (isImageFile(fileExtension, mimeType) || isImageFile(fileType, mimeType)) {
        console.log('Showing image preview');
        showImagePreview();
    } else if (isPdfFile(fileExtension, mimeType) || isPdfFile(fileType, mimeType)) {
        console.log('Showing PDF preview');
        showPdfPreview();
    } else if (isVideoFile(fileExtension, mimeType) || isVideoFile(fileType, mimeType)) {
        console.log('Showing video preview');
        showVideoPreview();
    } else if (isAudioFile(fileExtension, mimeType) || isAudioFile(fileType, mimeType)) {
        console.log('Showing audio preview');
        showAudioPreview();
    } else if (isTextFile(fileExtension, mimeType) || isTextFile(fileType, mimeType)) {
        console.log('Showing text preview');
        showTextPreview();
    } else if (isCodeFile(fileName, fileExtension) || isCodeFile(fileName, fileType)) {
        console.log('Showing code preview');
        showCodePreview();
    } else if (isDocumentFile(fileExtension, mimeType, fileName) || isDocumentFile(fileType, mimeType, fileName)) {
        console.log('Showing document preview for:', { fileExtension, fileType, mimeType });
        showDocumentPreview();
    } else {
        console.log('Showing unsupported preview for:', { fileExtension, fileType, mimeType });
        showUnsupportedPreview();
    }
}

async function getFileUrl(filePath) {
    // Use Laravel proxy route to bypass CORS restrictions
    const fileId = currentFileData?.id;
    if (fileId) {
        const proxyUrl = `/file-proxy/${fileId}`;
        console.log('Generated proxy URL:', proxyUrl);
        return proxyUrl;
    }
    
    throw new Error('Could not generate file URL - file ID not available');
}

// File type detection functions
function isImageFile(fileType, mimeType) {
    const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    return imageTypes.includes(fileType) || mimeType.startsWith('image/');
}

function isPdfFile(fileType, mimeType) {
    return fileType === 'pdf' || 
           mimeType === 'application/pdf' || 
           (typeof fileType === 'string' && fileType.toLowerCase().includes('pdf'));
}

function isVideoFile(fileType, mimeType) {
    const videoTypes = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv'];
    return videoTypes.includes(fileType) || mimeType.startsWith('video/');
}

function isAudioFile(fileType, mimeType) {
    const audioTypes = ['mp3', 'wav', 'ogg', 'aac', 'flac'];
    return audioTypes.includes(fileType) || mimeType.startsWith('audio/');
}

function isTextFile(fileType, mimeType) {
    const textTypes = ['txt', 'md', 'csv', 'log', 'xml', 'json'];
    return textTypes.includes(fileType) || mimeType.startsWith('text/');
}

function isCodeFile(fileName, fileType) {
    const codeExtensions = ['js', 'ts', 'php', 'py', 'java', 'cpp', 'c', 'h', 'css', 'html', 'sql', 'sh', 'bat'];
    const extension = fileName.split('.').pop()?.toLowerCase();
    return codeExtensions.includes(extension) || codeExtensions.includes(fileType);
}

function isDocumentFile(fileType, mimeType, fileName) {
    const docTypes = ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt'];
    const fileExtension = fileName ? fileName.split('.').pop()?.toLowerCase() : '';
    
    // Check file extension first (most reliable)
    if (docTypes.includes(fileExtension)) {
        console.log('Document detected by extension:', fileExtension);
        return true;
    }
    
    // Check stored file type
    if (docTypes.includes(fileType)) {
        console.log('Document detected by type:', fileType);
        return true;
    }
    
    // Check MIME type
    const docMimeChecks = [
        'officedocument',
        'msword', 
        'excel',
        'powerpoint',
        'application/vnd.openxmlformats',
        'application/vnd.ms-'
    ];
    
    for (const check of docMimeChecks) {
        if (mimeType.includes(check)) {
            console.log('Document detected by MIME type:', mimeType, 'matched:', check);
            return true;
        }
    }
    
    return false;
}

// Preview display functions
function showImagePreview() {
    hideAllPreviews();
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    
    previewImage.src = currentFileUrl;
    previewImage.alt = currentFileData.file_name;
    imagePreview.classList.remove('hidden');
}

function showPdfPreview() {
    hideAllPreviews();
    const pdfPreview = document.getElementById('pdfPreview');
    const pdfViewer = document.getElementById('pdfViewer');
    
    pdfViewer.src = currentFileUrl;
    pdfPreview.classList.remove('hidden');
}

function showVideoPreview() {
    hideAllPreviews();
    const videoPreview = document.getElementById('videoPreview');
    const videoPlayer = document.getElementById('videoPlayer');
    
    videoPlayer.src = currentFileUrl;
    videoPreview.classList.remove('hidden');
}

function showAudioPreview() {
    hideAllPreviews();
    const audioPreview = document.getElementById('audioPreview');
    const audioPlayer = document.getElementById('audioPlayer');
    
    audioPlayer.src = currentFileUrl;
    audioPreview.classList.remove('hidden');
}

async function showTextPreview() {
    hideAllPreviews();
    const textPreview = document.getElementById('textPreview');
    const textContent = document.getElementById('textContent');
    
    try {
        const response = await fetch(currentFileUrl);
        const text = await response.text();
        textContent.textContent = text;
        textPreview.classList.remove('hidden');
    } catch (error) {
        console.error('Error loading text content:', error);
        showUnsupportedPreview();
    }
}

async function showCodePreview() {
    hideAllPreviews();
    const codePreview = document.getElementById('codePreview');
    const codeContent = document.getElementById('codeContent');
    
    try {
        const response = await fetch(currentFileUrl);
        const code = await response.text();
        codeContent.textContent = code;
        codePreview.classList.remove('hidden');
    } catch (error) {
        console.error('Error loading code content:', error);
        showUnsupportedPreview();
    }
}

function showDocumentPreview() {
    hideAllPreviews();
    const documentPreview = document.getElementById('documentPreview');
    const documentViewer = document.getElementById('documentViewer');
    
    // Try Microsoft Office Online Viewer with proxy URL (should bypass CORS)
    try {
        const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(currentFileUrl)}`;
        console.log('Loading document with Office viewer via proxy:', officeViewerUrl);
        
        documentViewer.src = officeViewerUrl;
        documentPreview.classList.remove('hidden');
        
        // Fallback if Office viewer still fails
        documentViewer.onerror = function() {
            console.log('Office viewer failed even with proxy, showing download option');
            showDocumentDownloadFallback();
        };
        
        // Check if iframe loads successfully
        setTimeout(() => {
            try {
                if (documentViewer.contentDocument === null) {
                    console.log('Document viewer may have failed with proxy, keeping current attempt');
                }
            } catch (e) {
                // Cross-origin access error is normal for iframe content
                console.log('Document viewer iframe is loading (cross-origin access blocked as expected)');
            }
        }, 3000);
        
    } catch (error) {
        console.error('Error setting up document preview with proxy:', error);
        showDocumentDownloadFallback();
    }
}

function showDocumentDownloadFallback() {
    hideAllPreviews();
    const fileName = currentFileData?.file_name || '';
    const fileExtension = fileName.split('.').pop()?.toLowerCase();
    
    const unsupportedDiv = document.getElementById('unsupportedPreview');
    let appSuggestions = '';
    
    if (fileExtension === 'docx' || fileExtension === 'doc') {
        appSuggestions = `
            <div class="mt-4 p-4 bg-gray-800 rounded-lg">
                <h4 class="text-sm font-semibold mb-2">📱 Recommended Apps:</h4>
                <div class="text-xs text-gray-300 space-y-1">
                    <div>• <strong>Microsoft Word</strong> - Best compatibility</div>
                    <div>• <strong>LibreOffice Writer</strong> - Free alternative</div>
                    <div>• <strong>Google Docs</strong> - Upload to view online</div>
                </div>
            </div>
        `;
    } else if (fileExtension === 'xlsx' || fileExtension === 'xls') {
        appSuggestions = `
            <div class="mt-4 p-4 bg-gray-800 rounded-lg">
                <h4 class="text-sm font-semibold mb-2">📊 Recommended Apps:</h4>
                <div class="text-xs text-gray-300 space-y-1">
                    <div>• <strong>Microsoft Excel</strong> - Best compatibility</div>
                    <div>• <strong>LibreOffice Calc</strong> - Free alternative</div>
                    <div>• <strong>Google Sheets</strong> - Upload to view online</div>
                </div>
            </div>
        `;
    } else if (fileExtension === 'pptx' || fileExtension === 'ppt') {
        appSuggestions = `
            <div class="mt-4 p-4 bg-gray-800 rounded-lg">
                <h4 class="text-sm font-semibold mb-2">🎯 Recommended Apps:</h4>
                <div class="text-xs text-gray-300 space-y-1">
                    <div>• <strong>Microsoft PowerPoint</strong> - Best compatibility</div>
                    <div>• <strong>LibreOffice Impress</strong> - Free alternative</div>
                    <div>• <strong>Google Slides</strong> - Upload to view online</div>
                </div>
            </div>
        `;
    }
    
    unsupportedDiv.innerHTML = `
        <div class="p-6 text-center">
            <div class="text-6xl mb-4 text-gray-400">📊</div>
            <h3 class="text-xl mb-2 text-gray-600">Office Document Preview</h3>
            <p class="text-gray-500 mb-4">Online preview for Office documents is temporarily unavailable.</p>
            
            <button class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors mb-4" onclick="downloadFile()">
                📥 Download to View
            </button>
            
            ${appSuggestions}
            
            <div class="mt-4 p-3 bg-yellow-900/30 border border-yellow-700 rounded-lg">
                <p class="text-xs text-yellow-300">
                    💡 <strong>Tip:</strong> After downloading, double-click the file to open it with your preferred app.
                </p>
            </div>
        </div>
    `;
    
    unsupportedDiv.classList.remove('hidden');
}

function showUnsupportedPreview() {
    hideAllPreviews();
    const unsupportedDiv = document.getElementById('unsupportedPreview');
    
    // Update content based on file type
    const fileName = currentFileData?.file_name || '';
    const fileExtension = fileName.split('.').pop()?.toLowerCase();
    const fileType = currentFileData?.file_type || '';
    const mimeType = currentFileData?.mime_type || '';
    
    let helpText = 'This file type cannot be previewed in the browser.';
    let icon = '📄';
    
    // Provide specific guidance for common file types
    if (isPdfFile(fileType, mimeType) || fileExtension === 'pdf') {
        helpText = 'PDF preview is temporarily unavailable. Download to view the document.';
        icon = '📋';
    } else if (isDocumentFile(fileType, mimeType, fileName)) {
        helpText = 'Office document preview requires download. Click "Download to view" below.';
        icon = '📊';
    } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(fileExtension)) {
        helpText = 'Archive files need to be extracted. Download to access contents.';
        icon = '🗜️';
    } else if (['exe', 'msi', 'dmg', 'deb', 'rpm'].includes(fileExtension)) {
        helpText = 'Executable files cannot be previewed for security reasons.';
        icon = '⚠️';
    }
    
    unsupportedDiv.innerHTML = `
        <div class="p-6 text-center">
            <div class="text-6xl mb-4 text-gray-400">${icon}</div>
            <h3 class="text-xl mb-2 text-gray-600">Preview not available</h3>
            <p class="text-gray-500 mb-4">${helpText}</p>
            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors" onclick="downloadFile()">
                Download to view
            </button>
        </div>
    `;
    
    unsupportedDiv.classList.remove('hidden');
}

function hideAllPreviews() {
    const previews = [
        'imagePreview', 'pdfPreview', 'documentPreview', 
        'videoPreview', 'audioPreview', 'textPreview', 
        'codePreview', 'unsupportedPreview'
    ];
    
    previews.forEach(id => {
        document.getElementById(id)?.classList.add('hidden');
    });
}

function hideLoadingSpinner() {
    document.getElementById('loadingSpinner')?.classList.add('hidden');
}

function showError(message) {
    hideLoadingSpinner();
    hideAllPreviews();
    
    const container = document.getElementById('previewContainer');
    container.innerHTML = `
        <div class="flex items-center justify-center h-96 text-center">
            <div>
                <div class="text-6xl mb-4 text-red-400">⚠️</div>
                <h3 class="text-xl mb-2 text-gray-600">Error</h3>
                <p class="text-gray-500">${message}</p>
            </div>
        </div>
    `;
}

function setupEventListeners() {
    // Back button
    document.getElementById('backBtn')?.addEventListener('click', () => {
        window.history.back();
    });

    // Share button
    document.getElementById('shareBtn')?.addEventListener('click', () => {
        if (currentFileData) {
            // Integrate with existing share modal
            if (typeof openShareModal === 'function') {
                openShareModal(currentFileData.id, currentFileData.file_name);
            } else {
                alert('Share functionality not available');
            }
        }
    });

    // Download button
    document.getElementById('downloadBtn')?.addEventListener('click', downloadFile);
}

async function downloadFile() {
    if (!currentFileUrl || !currentFileData) {
        alert('File not available for download');
        return;
    }

    try {
        const downloadLink = document.createElement('a');
        downloadLink.href = currentFileUrl;
        downloadLink.download = currentFileData.file_name || 'download';
        downloadLink.target = '_blank';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    } catch (error) {
        console.error('Error downloading file:', error);
        alert('Error downloading file. Please try again.');
    }
}

// Utility functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    if (!dateString) return 'Unknown';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Export functions for global access
window.previewFile = (fileId) => {
    window.location.href = `/files/${fileId}/preview`;
};

window.downloadFile = downloadFile;
