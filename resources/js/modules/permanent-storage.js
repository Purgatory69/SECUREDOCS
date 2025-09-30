/**
 * Permanent Storage Upload Module - Production Ready
 * Handles crypto payments and real Arweave uploads for premium users
 */

let currentFile = null;
let paymentRequest = null;
let paymentCheckInterval = null;

// Initialize the permanent storage modal
export function initializePermanentStorageModal() {
    if (window.permanentStorageInitialized) {
        //console.log('Permanent storage modal already initialized');
        return;
    }
    window.permanentStorageInitialized = true;
    
    //console.log('🔧 Initializing permanent storage modal...');
    
    const modal = document.getElementById('permanentStorageModal');
    const openBtn = document.getElementById('openPermanentStorageBtn');
    const closeBtn = document.getElementById('closePermanentStorageBtn');
    const modalBackdrop = document.getElementById('permanentStorageBackdrop');
    
    if (!modal || !openBtn) {
        console.error('❌ Permanent storage modal elements not found');
        return;
    }

    // Event listeners
    openBtn.addEventListener('click', (e) => {
        e.preventDefault();
        openPermanentStorageModal(); // Call without passing event
    });
    closeBtn?.addEventListener('click', closePermanentStorageModal);
    modalBackdrop?.addEventListener('click', closePermanentStorageModal);

    // Initialize file drop zone
    initializeDropZone();
    
    // Initialize wallet connection buttons
    initializeWalletButtons();
    
    //console.log('✅ Permanent storage modal initialized successfully');
}

// Open the modal
function openPermanentStorageModal(fileId = null) {
    const modal = document.getElementById('permanentStorageModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        resetModalState();
        
        // If a file ID is provided, automatically load that file
        if (fileId) {
            loadFileForUpload(fileId);
        }
    }
}

// Close the modal
function closePermanentStorageModal() {
    const modal = document.getElementById('permanentStorageModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetModalState();
        clearPaymentCheck();
    }
}

// Reset modal to initial state
function resetModalState() {
    currentFile = null;
    paymentRequest = null;
    
    clearPaymentCheck();
    
    // Reset to first step
    document.getElementById('costCalculationStep')?.classList.remove('hidden');
    document.getElementById('walletConnectionStep')?.classList.add('hidden');
    document.getElementById('paymentStep')?.classList.add('hidden');
    document.getElementById('uploadingStep')?.classList.add('hidden');
    document.getElementById('successStep')?.classList.add('hidden');
    
    // Clear form data
    document.getElementById('selectedFileInfo').innerHTML = '';
    document.getElementById('costBreakdown').innerHTML = '';
    document.getElementById('paymentDetails').innerHTML = '';
    document.getElementById('uploadProgress')?.setAttribute('value', '0');
}

// Initialize file drop zone
function initializeDropZone() {
    const dropZone = document.getElementById('permanentStorageDropZone');
    const fileInput = document.getElementById('permanentStorageFileInput');
    
    if (!dropZone || !fileInput) {
        console.error('Drop zone or file input not found');
        return;
    }

    //console.log('Initializing drop zone...');

    // Click to browse files
    dropZone.addEventListener('click', () => {
        //console.log('Drop zone clicked, opening file picker...');
        fileInput.click();
    });

    // File selection handler
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            console.log('File selected:', file.name);
            handleFileSelection(file);
        }
    });

    // Drag and drop handlers
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-orange-500', 'bg-orange-50');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-orange-500', 'bg-orange-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-orange-500', 'bg-orange-50');
        
        const file = e.dataTransfer.files[0];
        if (file) {
            //console.log('File dropped:', file.name);
            handleFileSelection(file);
        }
    });

    //console.log('Drop zone initialized successfully');
}

// Initialize wallet connection buttons
function initializeWalletButtons() {
    const metaMaskBtn = document.getElementById('connectMetaMaskBtn');
    const roninBtn = document.getElementById('connectRoninBtn');
    const walletConnectBtn = document.getElementById('connectWalletConnectBtn');
    
    //console.log('Initializing wallet buttons...');
    //console.log('- MetaMask button:', !!metaMaskBtn);
    //console.log('- Ronin button:', !!roninBtn);
    //console.log('- WalletConnect button:', !!walletConnectBtn);
    
    if (metaMaskBtn) {
        metaMaskBtn.addEventListener('click', () => {
            //console.log('MetaMask button clicked');
            connectWallet('metamask');
        });
    }
    
    if (roninBtn) {
        roninBtn.addEventListener('click', () => {
            //console.log('Ronin button clicked');
            connectWallet('ronin');
        });
    }
    
    if (walletConnectBtn) {
        walletConnectBtn.addEventListener('click', () => {
            //console.log('WalletConnect button clicked');
            connectWallet('walletconnect');
        });
    }
    
    //console.log('Wallet buttons initialized successfully');
}

// Load file from server by ID
async function loadFileForUpload(fileId) {
    try {
        console.log('[Arweave] Loading file for upload:', fileId, typeof fileId);
        
        // Validate file ID
        if (!fileId || typeof fileId !== 'string' && typeof fileId !== 'number') {
            throw new Error(`Invalid file ID: ${fileId} (type: ${typeof fileId})`);
        }
        
        // Fetch file details from server
        const url = `/files/${fileId}`;
        console.log('[Arweave] Fetching file from:', url);
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        console.log('[Arweave] Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Failed to load file details (status: ${response.status})`);
        }
        
        const fileData = await response.json();
        console.log('[Arweave] File data loaded:', fileData);
        
        // Create a mock File object with the file details
        const mockFile = {
            name: fileData.file_name,
            size: fileData.file_size,
            type: fileData.mime_type || 'application/octet-stream',
            id: fileId
        };
        
        // Store the file ID for later use
        currentFile = mockFile;
        currentFile.serverId = fileId;
        
        // Display file info and calculate cost
        displayFileInfo(mockFile);
        await calculateAndDisplayCost(mockFile);
        
        // Move to wallet connection step
        document.getElementById('costCalculationStep').classList.add('hidden');
        document.getElementById('walletConnectionStep').classList.remove('hidden');
        
    } catch (error) {
        console.error('Failed to load file:', error);
        showError('Failed to load file details. Please try again.');
    }
}

// Handle file selection
async function handleFileSelection(file) {
    currentFile = file;
    
    // Display file info
    displayFileInfo(file);
    
    // Calculate and display cost
    await calculateAndDisplayCost(file);
    
    // Move to wallet connection step
    document.getElementById('costCalculationStep').classList.add('hidden');
    document.getElementById('walletConnectionStep').classList.remove('hidden');
}

// Display file information
function displayFileInfo(file) {
    const fileInfo = document.getElementById('selectedFileInfo');
    const fileSize = formatBytes(file.size);
    
    fileInfo.innerHTML = `
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    📄
                </div>
                <div>
                    <div class="font-medium text-gray-900">${file.name}</div>
                    <div class="text-sm text-gray-500">${fileSize} • ${file.type || 'Unknown type'}</div>
                </div>
            </div>
        </div>
    `;
}

// Calculate and display cost
async function calculateAndDisplayCost(file) {
    try {
        const response = await fetch('/permanent-storage/calculate-cost', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                file_size: file.size,
                file_name: file.name
            })
        });

        const result = await response.json();
        
        if (result.success) {
            displayCostBreakdown(result.cost_breakdown);
        } else {
            showError('Failed to calculate cost: ' + result.message);
        }
    } catch (error) {
        //console.error('Cost calculation failed:', error);
        showError('Failed to calculate storage cost');
    }
}

// Display cost breakdown
function displayCostBreakdown(cost) {
    const costBreakdown = document.getElementById('costBreakdown');
    
    costBreakdown.innerHTML = `
        <div class="bg-white border rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-3">💰 Storage Cost</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Arweave Storage (Permanent)</span>
                    <span class="font-medium">$${cost.arweave_cost_usd}</span>
                </div>
                <div class="bg-green-50 border border-green-200 rounded p-2 mt-2">
                    <div class="flex items-center text-green-700 text-xs">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>✨ No service fees • No hidden charges</span>
                    </div>
                </div>
                <hr class="my-2">
                <div class="flex justify-between font-medium text-lg">
                    <span>Total to Pay</span>
                    <span class="text-blue-600">$${cost.total_usd}</span>
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    ≈ ${cost.total_crypto} ${cost.recommended_token} on Polygon
                </div>
            </div>
        </div>
    `;
}

// Connect wallet (supports MetaMask, Ronin, WalletConnect)
async function connectWallet(walletType = 'metamask') {
    try {
        //console.log(`Attempting to connect ${walletType} wallet...`);
        
        let walletAddress;
        
        switch (walletType) {
            case 'metamask':
                if (!window.ethereum) {
                    throw new Error('MetaMask not installed. Please install MetaMask browser extension.');
                }
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                walletAddress = accounts[0];
                break;
                
            case 'ronin':
                if (!window.ronin) {
                    throw new Error('Ronin Wallet not installed. Please install Ronin Wallet browser extension.');
                }
                const roninAccounts = await window.ronin.request({ method: 'eth_requestAccounts' });
                walletAddress = roninAccounts[0];
                break;
                
            case 'walletconnect':
                // For now, fallback to MetaMask for WalletConnect
                if (!window.ethereum) {
                    throw new Error('No compatible wallet found. Please install MetaMask or use WalletConnect.');
                }
                const wcAccounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                walletAddress = wcAccounts[0];
                break;
                
            default:
                throw new Error('Unsupported wallet type');
        }
        
        if (!walletAddress) {
            throw new Error('No wallet address found');
        }
        
        //console.log(`${walletType} wallet connected:`, walletAddress);
        
        // Create payment request
        await createPaymentRequest(walletAddress, walletType);
        
    } catch (error) {
        //console.error('Wallet connection failed:', error);
        
        // Provide helpful error messages
        let errorMessage = error.message;
        if (error.message.includes('User rejected')) {
            errorMessage = 'Wallet connection was cancelled by user.';
        } else if (error.message.includes('not installed')) {
            errorMessage = error.message + ' You can download it from the official website.';
        }
        
        showError('Failed to connect wallet: ' + errorMessage);
    }
}

// Create payment request
async function createPaymentRequest(walletAddress, walletType) {
    try {
        const response = await fetch('/permanent-storage/create-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                file_name: currentFile.name,
                file_size: currentFile.size,
                wallet_address: walletAddress,
                wallet_type: walletType
            })
        });

        const result = await response.json();
        
        if (result.success) {
            paymentRequest = result.payment_request;
            displayPaymentRequest(result);
            
            // Move to payment step
            document.getElementById('walletConnectionStep').classList.add('hidden');
            document.getElementById('paymentStep').classList.remove('hidden');
            
            // Start payment monitoring
            startPaymentMonitoring(result.payment_request.payment_id);
        } else {
            throw new Error(result.message || 'Payment request creation failed');
        }
    } catch (error) {
        console.error('Payment request creation failed:', error);
        showError('Failed to create payment request. Please try again.');
    }
}

// Display payment request
function displayPaymentRequest(paymentData) {
    const paymentDetails = document.getElementById('paymentDetails');
    const { payment_request, cost_breakdown, expires_in_minutes } = paymentData;
    
    const paymentInfo = payment_request.payment_details || payment_request;
    
    paymentDetails.innerHTML = `
        <div class="space-y-6">
            <div class="text-center">
                <h4 class="text-lg font-medium text-white mb-2">Send Payment</h4>
                <p class="text-sm text-gray-400">Send exactly the amount below to complete your permanent storage purchase</p>
            </div>
            
            <div class="bg-[#1F2235] border border-[#f89c00] rounded-lg p-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-[#f89c00] mb-1">
                        ${paymentInfo.amount} ${paymentInfo.token}
                    </div>
                    <div class="text-sm text-gray-400">
                        on ${paymentInfo.network} network
                    </div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Send to Address:</label>
                    <div class="flex items-center gap-2">
                        <input type="text" value="${paymentInfo.to_address}" readonly 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono">
                        <button onclick="copyToClipboard('${paymentInfo.to_address}')" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                            Copy
                        </button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount:</label>
                    <div class="flex items-center gap-2">
                        <input type="text" value="${paymentInfo.amount}" readonly 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono">
                        <button onclick="copyToClipboard('${paymentInfo.amount}')" 
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <div class="inline-flex items-center gap-2 text-sm text-orange-600 bg-orange-50 px-3 py-2 rounded-lg">
                    <span>⏰</span>
                    Payment expires in ${expires_in_minutes || 15} minutes
                </div>
            </div>
            
            <div class="text-center text-sm text-gray-500">
                <p>Waiting for payment confirmation...</p>
                <div class="animate-spin inline-block w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full mt-2"></div>
            </div>
            
            <!-- Production Mode Notice -->
            <div class="text-center mt-4">
                <p class="text-xs text-gray-400">
                    💳 Complete payment to upload to Arweave
                </p>
            </div>
        </div>
    `;
}

// Start payment monitoring
function startPaymentMonitoring(paymentId) {
    paymentCheckInterval = setInterval(async () => {
        try {
            const response = await fetch(`/permanent-storage/payment-status/${paymentId}`);
            const result = await response.json();
            
            if (result.success && result.payment_status) {
                const status = result.payment_status.status;
                if (status === 'confirmed' || status === 'completed') {
                    clearPaymentCheck();
                    handlePaymentSuccess(result.payment_status);
                } else if (status === 'expired' || status === 'failed') {
                    clearPaymentCheck();
                    handlePaymentTimeout();
                }
            }
        } catch (error) {
            console.error('Payment status check failed:', error);
        }
    }, 5000); // Check every 5 seconds
}

// Clear payment check interval
function clearPaymentCheck() {
    if (paymentCheckInterval) {
        clearInterval(paymentCheckInterval);
        paymentCheckInterval = null;
    }
}

// Handle successful payment
async function handlePaymentSuccess(paymentResult) {
    // Move to uploading step
    document.getElementById('paymentStep').classList.add('hidden');
    document.getElementById('uploadingStep').classList.remove('hidden');
    
    // Start file upload to Arweave
    try {
        await uploadFileToArweave(paymentResult);
    } catch (error) {
        console.error('File upload failed:', error);
        showError('File upload failed: ' + error.message);
    }
}

// Upload file to Arweave
async function uploadFileToArweave(paymentResult) {
    const progressBar = document.getElementById('uploadProgress');
    const progressText = document.getElementById('uploadProgressText');
    
    try {
        // Simulate upload progress
        for (let i = 0; i <= 100; i += 10) {
            progressBar.value = i;
            progressText.textContent = `Uploading to Arweave... ${i}%`;
            await new Promise(resolve => setTimeout(resolve, 200));
        }
        
        let fileContent;
        
        // Check if this is an existing file on the server or a new upload
        if (currentFile.serverId) {
            // File is already on server, fetch its content
            const fileResponse = await fetch(`/files/${currentFile.serverId}/download`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (!fileResponse.ok) {
                throw new Error('Failed to fetch file content from server');
            }
            
            const blob = await fileResponse.blob();
            fileContent = await blobToBase64(blob);
        } else {
            // New file upload, convert to base64
            fileContent = await fileToBase64(currentFile);
        }
        
        // Upload to server
        const response = await fetch('/permanent-storage/upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                payment_id: paymentResult.payment_id || 'demo_payment',
                file_name: currentFile.name,
                file_size: currentFile.size,
                file_content: fileContent,
                mime_type: currentFile.type,
                file_id: currentFile.serverId || null
            })
        });

        const result = await response.json();
        
        if (result.success) {
            handleUploadSuccess(result);
        } else {
            throw new Error(result.message || 'Upload failed');
        }
        
    } catch (error) {
        console.error('Upload failed:', error);
        showError('Upload failed: ' + error.message);
    }
}

// Handle successful upload
function handleUploadSuccess(uploadResult) {
    // Move to success step
    document.getElementById('uploadingStep').classList.add('hidden');
    document.getElementById('successStep').classList.remove('hidden');
    
    // Display success information
    const successContent = document.getElementById('successContent');
    successContent.innerHTML = `
        <div class="text-center space-y-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <div>
                <h3 class="text-xl font-semibold text-white mb-2">File Permanently Stored!</h3>
                <p class="text-gray-400">Your file has been uploaded to the Arweave blockchain and will be accessible forever.</p>
            </div>
            
            <div class="bg-[#1F2235] border border-green-500 rounded-lg p-4">
                <div class="text-sm text-gray-400 mb-2">Arweave Transaction ID:</div>
                <div class="font-mono text-sm text-white break-all">${uploadResult.arweave_id}</div>
            </div>
            
            <div class="space-y-2">
                <div class="text-sm text-gray-400">Access your file:</div>
                <div class="text-sm">
                    <a href="${uploadResult.arweave_url}" target="_blank" 
                       class="text-blue-400 hover:text-blue-300 underline">
                        ${uploadResult.arweave_url}
                    </a>
                </div>
            </div>
            
            <div class="flex gap-3 justify-center">
                <button onclick="copyToClipboard('${uploadResult.arweave_url}')" 
                        class="bg-[#3C3F58] text-white px-6 py-2 rounded-lg hover:bg-[#4A4F6B] transition-colors">
                    📋 Copy URL
                </button>
                <button onclick="closePermanentStorageModal()" 
                        class="bg-[#2A2A3E] text-white px-6 py-2 rounded-lg hover:bg-[#3A3A4E] transition-colors">
                    ✕ Close
                </button>
            </div>
        </div>
    `;
}

// Utility functions
function blobToBase64(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const result = reader.result;
                if (typeof result === 'string' && result.includes(',')) {
                    resolve(result.split(',')[1]);
                } else {
                    reject(new Error('Invalid file reader result'));
                }
            } catch (error) {
                reject(error);
            }
        };
        reader.onerror = error => reject(error);
        reader.readAsDataURL(blob);
    });
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        if (!file || !(file instanceof File || file instanceof Blob)) {
            reject(new Error('Invalid file object provided to fileToBase64'));
            return;
        }
        
        const reader = new FileReader();
        reader.onload = () => {
            try {
                const result = reader.result;
                if (typeof result === 'string' && result.includes(',')) {
                    resolve(result.split(',')[1]);
                } else {
                    reject(new Error('Invalid file reader result'));
                }
            } catch (error) {
                reject(error);
            }
        };
        reader.onerror = error => reject(error);
        reader.readAsDataURL(file);
    });
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            //console.log('Copied to clipboard:', text);
        }).catch(err => {
           //console.error('Failed to copy:', err);
        });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            //console.log('Copied to clipboard:', text);
        } catch (err) {
           //console.error('Failed to copy:', err);
        }
        document.body.removeChild(textArea);
    }
}

// Demo mode disabled for production
// Users must complete real payment to upload to Arweave

function handlePaymentTimeout() {
    showError('Payment expired. Please try again.');
    setTimeout(() => {
        closePermanentStorageModal();
    }, 3000);
}

function showError(message) {
    alert(message); // Replace with proper toast notification
}

// Make functions globally available
window.openPermanentStorageModal = openPermanentStorageModal;
window.closePermanentStorageModal = closePermanentStorageModal;
window.copyToClipboard = copyToClipboard;
window.connectWallet = connectWallet;

//console.log('✅ Permanent Storage module loaded successfully');
