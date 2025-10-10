<!-- Client-Side Arweave Upload Modal -->
<div id="clientArweaveModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="relative z-10 bg-[#0D0E2F] text-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-[#3C3F58]">
            <h3 class="text-xl font-semibold">🚀 Upload to Arweave (Client-Side)</h3>
            <button id="clientArweaveCloseBtn" class="text-2xl leading-none hover:text-gray-300">&times;</button>
        </div>

        <!-- Content -->
        <div class="p-6 max-h-[calc(90vh-140px)] overflow-y-auto">
            
            <!-- Error/Success Messages -->
            <div id="clientArweaveError" class="hidden mb-4 p-3 bg-red-600 rounded-lg text-sm"></div>
            <div id="clientArweaveSuccess" class="hidden mb-4 p-3 bg-green-600 rounded-lg text-sm"></div>

            <!-- Step 1: File Selection -->
            <div id="stepFileSelection" class="space-y-6">
                <div class="text-center">
                    <h4 class="text-lg font-medium text-white mb-2">📄 Select File</h4>
                    <p class="text-gray-400 mb-2">Choose a file to upload permanently to Arweave</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                        <div class="flex items-center text-blue-700 text-sm">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span><strong>New Approach:</strong> You pay directly with your MetaMask wallet - no middleman!</span>
                        </div>
                    </div>
                </div>
                
                <div class="border-2 border-dashed border-[#3C3F58] bg-[#1F2235] rounded-lg p-8 text-center">
                    <div class="text-4xl mb-4">📄</div>
                    <p class="text-lg font-medium text-white mb-2">Drop your file here or click to browse</p>
                    <p class="text-sm text-gray-400">Maximum file size: 100MB</p>
                    <input type="file" id="clientArweaveFile" class="hidden" accept="*/*">
                    <button onclick="document.getElementById('clientArweaveFile').click()" 
                            class="mt-4 px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white">
                        Select File
                    </button>
                </div>
                
                <div id="selectedFileInfo" class="text-center text-gray-400"></div>
            </div>

            <!-- Step 2: Wallet Connection -->
            <div id="stepWalletConnection" class="hidden space-y-6">
                <div class="text-center">
                    <h4 class="text-lg font-medium text-white mb-2">🔗 Connect Your Wallet</h4>
                    <p class="text-gray-400 mb-4">Use the Bundlr wallet widget (B button) in the navigation bar first</p>
                </div>
                
                <!-- Wallet Status Display -->
                <div id="walletStatusDisplay" class="bg-[#1F2235] rounded-lg p-6 space-y-4 hidden">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-3xl mr-3">✅</span>
                            <div>
                                <p class="font-medium text-green-400">Wallet Connected</p>
                                <p class="text-sm text-gray-400" id="connectedWalletAddress">0x...</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button id="viewWalletDetailsBtn"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white text-sm">
                                View Details
                            </button>
                            <button id="continueToBalanceBtn"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-white text-sm">
                                Continue →
                            </button>
                        </div>
                    </div>
                    
                    <!-- Wallet Details -->
                    <div id="walletDetailsPanel" class="border-t border-[#3C3F58] pt-4 hidden">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Network:</span>
                                <span class="text-white ml-2">Polygon</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Status:</span>
                                <span class="text-green-400 ml-2">Active</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Bundlr Balance:</span>
                                <span class="text-yellow-400 ml-2" id="walletBundlrBalance">Loading...</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Last Updated:</span>
                                <span class="text-gray-300 ml-2" id="walletLastUpdate">Now</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Connection Panel -->
                <div id="walletConnectionPanel" class="bg-[#1F2235] rounded-lg p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-3xl mr-3">🦊</span>
                            <div>
                                <p class="font-medium">MetaMask Wallet</p>
                                <p class="text-sm text-gray-400">Connect to Polygon Network</p>
                            </div>
                        </div>
                        <button id="connectWalletBtn" 
                                class="px-6 py-2 bg-orange-600 hover:bg-orange-700 rounded-lg text-white">
                            Connect MetaMask
                        </button>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start text-yellow-800 text-sm">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <strong>Make sure:</strong> Your MetaMask is connected to Polygon network. 
                            Switch network if needed.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Bundlr Balance (YouTube Style) -->
            <div id="stepBalanceCheck" class="hidden space-y-6">
                <div class="text-center">
                    <h4 class="text-lg font-medium text-white mb-2">💰 Check Bundlr Balance</h4>
                    <p class="text-gray-400 mb-4">Your Bundlr account balance for uploads</p>
                </div>
                
                <div class="bg-[#1F2235] rounded-lg p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-white">Bundlr Balance</p>
                            <p class="text-sm text-gray-400">Available for uploads</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-400" id="currentBundlrBalance">0.000000</p>
                            <p class="text-xs text-gray-400">MATIC</p>
                        </div>
                    </div>
                    
                    <div class="border-t border-[#3C3F58] pt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span>Upload Cost:</span>
                            <span class="text-yellow-400">~0.005 MATIC</span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-2">
                            <span>Sufficient Balance:</span>
                            <span id="balanceSufficient" class="text-green-400">✅ Yes</span>
                        </div>
                    </div>
                    
                    <!-- Fund Button (YouTube Style) -->
                    <div class="flex gap-3">
                        <button id="fundBundlrAccountBtn"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white text-sm">
                            💳 Fund Account
                        </button>
                        <button id="refreshBundlrBalanceBtn"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-white text-sm">
                            🔄 Refresh
                        </button>
                    </div>
                </div>

                <!-- Continue Buttons -->
                <div class="flex gap-3">
                    <button onclick="showStep('walletConnection')" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-white text-sm">
                        ← Back
                    </button>
                    <button id="proceedFromBalanceBtn"
                            class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg text-white font-medium">
                        🚀 Continue to Upload
                    </button>
                </div>
            </div>

            <!-- Step 4: Funding (if needed) -->
            <div id="stepFunding" class="hidden space-y-6">
                <div class="text-center">
                    <h4 class="text-lg font-medium text-white mb-2">💳 Fund Your Bundlr</h4>
                    <p class="text-gray-400 mb-4">Add MATIC to your Bundlr balance for uploads</p>
                </div>
                
                <div class="bg-[#1F2235] rounded-lg p-6 space-y-4">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium">Amount to Fund (MATIC):</label>
                        <input type="number" 
                               id="fundAmount" 
                               placeholder="0.1" 
                               step="0.001"
                               min="0.001"
                               class="w-full px-3 py-2 bg-[#3C3F58] border border-gray-600 rounded-lg text-white placeholder-gray-400">
                        
                        <div class="grid grid-cols-3 gap-2 mt-2">
                            <button onclick="document.getElementById('fundAmount').value = '0.05'"
                                    class="px-3 py-1 bg-[#3C3F58] hover:bg-[#55597C] rounded text-xs">
                                0.05 MATIC
                            </button>
                            <button onclick="document.getElementById('fundAmount').value = '0.1'"
                                    class="px-3 py-1 bg-[#3C3F58] hover:bg-[#55597C] rounded text-xs">
                                0.1 MATIC
                            </button>
                            <button onclick="document.getElementById('fundAmount').value = '0.2'"
                                    class="px-3 py-1 bg-[#3C3F58] hover:bg-[#55597C] rounded text-xs">
                                0.2 MATIC
                            </button>
                        </div>
                        
                        <div class="text-xs text-gray-400 mt-2">
                            <p>• 0.05 MATIC ≈ 10 uploads (~1MB each)</p>
                            <p>• 0.1 MATIC ≈ 20 uploads (~1MB each)</p>
                            <p>• 0.2 MATIC ≈ 40 uploads (~1MB each)</p>
                        </div>
                    </div>
                    
                    <button id="fundBundlrBtn"
                            class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg text-white font-medium">
                        Fund Bundlr Balance
                    </button>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start text-blue-800 text-sm">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <strong>How it works:</strong> You're funding YOUR OWN Bundlr account, not paying us. 
                            This MATIC stays in your control and is used only for your uploads.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Upload -->
            <div id="stepUpload" class="hidden space-y-6">
                <div class="text-center">
                    <h4 class="text-lg font-medium text-white mb-2">🚀 Upload to Arweave</h4>
                    <p class="text-gray-400 mb-4">Ready to upload your file permanently</p>
                </div>
                
                <div class="bg-[#1F2235] rounded-lg p-6 space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span>File:</span>
                            <span class="text-gray-300" id="uploadFileName">No file selected</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>Upload Cost:</span>
                            <span class="text-yellow-400" id="uploadCostFinal">~0.005 MATIC</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>Your Balance:</span>
                            <span class="text-green-400" id="uploadBalanceFinal">0.000000 MATIC</span>
                        </div>
                    </div>
                    
                    <button id="uploadToArweaveBtn"
                            class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-medium">
                        🚀 Upload to Arweave
                    </button>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-start text-green-800 text-sm">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <strong>Permanent Storage:</strong> Once uploaded, your file will be stored forever on Arweave. 
                            You'll get a permanent URL that never expires.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 6: Success -->
            <div id="stepSuccess" class="hidden space-y-6">
                <div class="text-center">
                    <div class="text-6xl mb-4">🎉</div>
                    <h4 class="text-lg font-medium text-white mb-2">Upload Successful!</h4>
                    <p class="text-gray-400 mb-4">Your file is now permanently stored on Arweave</p>
                </div>
                
                <div class="bg-[#1F2235] rounded-lg p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Permanent URL:</label>
                        <div class="mb-2 text-xs text-gray-400">
                            <span>File Type: </span><span id="uploadedFileType">Unknown</span> |
                            <span>Size: </span><span id="uploadedFileSize">Unknown</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   id="arweaveSuccessUrlInput"
                                   readonly 
                                   class="flex-1 px-3 py-2 bg-[#3C3F58] border border-gray-600 rounded-lg text-white text-sm font-mono">
                            <button id="copyUrlBtn" onclick="copyToClipboard(document.getElementById('arweaveSuccessUrlInput').value)" 
                                    class="px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                                Copy
                            </button>
                            <a id="arweaveSuccessUrlLink" 
                               href="#" 
                               target="_blank"
                               class="px-2 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                                View
                            </a>
                            <a id="arweaveAltUrlLink" 
                               href="#" 
                               target="_blank"
                               title="Alternative gateway if main doesn't work"
                               class="px-2 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm">
                                Alt
                            </a>
                            <button id="checkStatusBtn" onclick="checkArweaveStatus()" 
                                    title="Check if file is ready on Arweave"
                                    class="px-2 py-2 bg-orange-600 hover:bg-orange-700 rounded-lg text-sm">
                                Status
                            </button>
                            <button id="autoCheckBtn" onclick="toggleAutoCheck()" 
                                    title="Auto-check every 30 seconds"
                                    class="px-2 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                                Auto
                            </button>
                        </div>
                    </div>
                    
                    <div class="border-t border-[#3C3F58] pt-4">
                        <div class="flex items-center justify-between text-sm mb-4">
                            <span>Remaining Balance:</span>
                            <span class="text-green-400" id="arweaveSuccessBalance">0.000000 MATIC</span>
                        </div>
                        
                        <!-- Save Options -->
                        <div class="border-t border-[#3C3F58] pt-4">
                            <label class="text-sm text-gray-400 mb-3 block">💾 Save to Your Files:</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="saveOption" value="save_url" checked 
                                           class="mr-3 text-blue-600">
                                    <span class="text-white text-sm">Save URL reference (recommended)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="saveOption" value="skip_save" 
                                           class="mr-3 text-blue-600">
                                    <span class="text-white text-sm">Don't save to files (URL only)</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                URL reference saves the Arweave link to your files list without downloading content locally.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Arweave Info Panel -->
                    <div class="bg-blue-900/20 border border-blue-500/30 rounded-lg p-4">
                        <h5 class="text-sm font-medium text-blue-400 mb-2">📋 Arweave File Access Guide</h5>
                        <div class="text-xs text-gray-300 space-y-1">
                            <div><strong>Images/Videos:</strong> Display directly in browser</div>
                            <div><strong>PDFs:</strong> Usually display in browser</div>
                            <div><strong>Documents (DOC, TXT):</strong> May download instead of display</div>
                            <div><strong>Binary files:</strong> Typically trigger download</div>
                            <div class="mt-2 text-yellow-300"><strong>⏳ New uploads take 5-30 minutes to propagate globally</strong></div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button onclick="document.getElementById('clientArweaveModal').classList.add('hidden')"
                            class="flex-1 px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-white">
                        Close
                    </button>
                    <button onclick="window.location.reload()"
                            class="flex-1 px-6 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-white">
                        Upload Another File
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('copyUrlBtn');
        const originalText = btn.textContent;
        
        // Show feedback
        btn.textContent = 'Copied!';
        btn.classList.add('bg-green-600');
        btn.classList.remove('bg-gray-600');
        
        // Reset after 2 seconds
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('bg-green-600');
            btn.classList.add('bg-gray-600');
        }, 2000);
        
        console.log('✅ Copied to clipboard!');
    }).catch(err => {
        console.error('❌ Failed to copy:', err);
        alert('Failed to copy. Please select and copy manually.');
    });
}

// Check Arweave transaction status using official APIs
async function checkArweaveStatus() {
    const btn = document.getElementById('checkStatusBtn');
    const urlInput = document.getElementById('arweaveSuccessUrlInput');
    const url = urlInput?.value;
    
    if (!url) {
        alert('No URL to check');
        return;
    }
    
    const txId = url.split('/').pop();
    btn.textContent = 'Checking...';
    btn.disabled = true;
    
    try {
        let statusMsg = '';
        let found = false;
        
        // 1. Check transaction status via Arweave API
        console.log('🔍 Checking transaction:', txId);
        
        try {
            const txResponse = await fetch(`https://arweave.net/tx/${txId}/status`);
            const txStatus = await txResponse.json();
            
            if (txResponse.ok && txStatus.confirmed) {
                statusMsg += `✅ Transaction confirmed in block ${txStatus.confirmed.block_height}\n`;
                statusMsg += `📦 Block indep hash: ${txStatus.confirmed.block_indep_hash.substring(0, 16)}...\n`;
                found = true;
                
                // Check how many confirmations
                try {
                    const networkResponse = await fetch('https://arweave.net/info');
                    const networkInfo = await networkResponse.json();
                    const confirmations = networkInfo.height - txStatus.confirmed.block_height;
                    statusMsg += `🔗 Confirmations: ${confirmations}\n`;
                    
                    if (confirmations >= 50) {
                        statusMsg += `✅ File should be available on all gateways\n`;
                    } else if (confirmations >= 25) {
                        statusMsg += `🟡 File should be available on most gateways\n`;
                    } else {
                        statusMsg += `🟠 File might not be available on all gateways yet\n`;
                    }
                } catch (e) {
                    console.warn('Could not get confirmations:', e);
                }
                
            } else if (txResponse.ok && txStatus.accepted) {
                statusMsg += `🟡 Transaction accepted but not yet confirmed\n`;
                statusMsg += `⏳ Usually takes 1-2 minutes to confirm\n`;
            } else {
                statusMsg += `❌ Transaction not found in mempool\n`;
            }
        } catch (txError) {
            console.warn('Transaction API failed:', txError);
            statusMsg += `⚠️ Could not check transaction status\n`;
        }
        
        // 2. Test actual file availability on gateways
        statusMsg += `\n🌐 Gateway Availability:\n`;
        const gateways = [
            { name: 'Arweave.net', url: `https://arweave.net/${txId}` },
            { name: 'Gateway.dev', url: `https://gateway.arweave.dev/${txId}` },
            { name: 'AR.io', url: `https://ar.io/${txId}` }
        ];
        
        let availableCount = 0;
        for (const gateway of gateways) {
            try {
                const response = await fetch(gateway.url, { 
                    method: 'HEAD',
                    signal: AbortSignal.timeout(5000) // 5 second timeout
                });
                
                if (response.ok) {
                    statusMsg += `✅ ${gateway.name}: Available\n`;
                    availableCount++;
                    found = true;
                } else {
                    statusMsg += `❌ ${gateway.name}: ${response.status}\n`;
                }
            } catch (err) {
                statusMsg += `⏳ ${gateway.name}: Not ready\n`;
            }
        }
        
        // 3. Overall status
        if (found && availableCount >= 2) {
            btn.textContent = '✅ Ready';
            btn.classList.remove('bg-orange-600');
            btn.classList.add('bg-green-600');
            statusMsg += `\n🎉 File is ready! Available on ${availableCount}/3 gateways`;
        } else if (found && availableCount >= 1) {
            btn.textContent = '🟡 Partial';
            btn.classList.remove('bg-orange-600');
            btn.classList.add('bg-yellow-600');
            statusMsg += `\n⏳ File is partially ready (${availableCount}/3 gateways)`;
        } else {
            btn.textContent = '⏳ Wait';
            statusMsg += `\n⏳ File not ready yet. Try again in 2-5 minutes.`;
        }
        
        // Show detailed status
        const statusWindow = window.open('', '_blank', 'width=600,height=400,scrollbars=yes');
        statusWindow.document.write(`
            <html>
                <head><title>Arweave Status - ${txId}</title></head>
                <body style="font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff;">
                    <h2>📊 Arweave Transaction Status</h2>
                    <p><strong>Transaction ID:</strong> ${txId}</p>
                    <pre style="background: #2a2a2a; padding: 15px; border-radius: 5px; white-space: pre-wrap;">${statusMsg}</pre>
                    <br>
                    <button onclick="window.close()" style="padding: 10px 20px; background: #4a5568; color: white; border: none; border-radius: 5px;">Close</button>
                </body>
            </html>
        `);
        
    } catch (error) {
        btn.textContent = '❌ Error';
        alert('Status check failed: ' + error.message);
        console.error('Status check error:', error);
    } finally {
        btn.disabled = false;
        setTimeout(() => {
            btn.textContent = 'Status';
            btn.classList.remove('bg-green-600', 'bg-yellow-600');
            btn.classList.add('bg-orange-600');
        }, 10000); // Reset after 10 seconds
    }
}

// Auto-check functionality
let autoCheckInterval = null;
let autoCheckCount = 0;

function toggleAutoCheck() {
    const btn = document.getElementById('autoCheckBtn');
    
    if (autoCheckInterval) {
        // Stop auto-check
        clearInterval(autoCheckInterval);
        autoCheckInterval = null;
        autoCheckCount = 0;
        btn.textContent = 'Auto';
        btn.classList.remove('bg-green-600');
        btn.classList.add('bg-gray-600');
        console.log('🔄 Auto-check stopped');
    } else {
        // Start auto-check
        autoCheckInterval = setInterval(async () => {
            autoCheckCount++;
            console.log(`🔄 Auto-check #${autoCheckCount}`);
            
            // Quick status check (just transaction confirmation)
            const urlInput = document.getElementById('arweaveSuccessUrlInput');
            const url = urlInput?.value;
            
            if (url) {
                const txId = url.split('/').pop();
                
                try {
                    const txResponse = await fetch(`https://arweave.net/tx/${txId}/status`);
                    const txStatus = await txResponse.json();
                    
                    if (txResponse.ok && txStatus.confirmed) {
                        // Check network height for confirmations
                        const networkResponse = await fetch('https://arweave.net/info');
                        const networkInfo = await networkResponse.json();
                        const confirmations = networkInfo.height - txStatus.confirmed.block_height;
                        
                        btn.textContent = `${confirmations}✓`;
                        
                        if (confirmations >= 50) {
                            // File should be ready, stop auto-check
                            clearInterval(autoCheckInterval);
                            autoCheckInterval = null;
                            btn.textContent = '✅ Done';
                            btn.classList.remove('bg-green-600');
                            btn.classList.add('bg-blue-600');
                            
                            // Show notification
                            if (window.Notification && Notification.permission === 'granted') {
                                new Notification('Arweave File Ready!', {
                                    body: `Your file has ${confirmations} confirmations and should be available on all gateways.`,
                                    icon: '/favicon.ico'
                                });
                            }
                            
                            console.log('✅ File ready! Auto-check completed.');
                        }
                    } else {
                        btn.textContent = `${autoCheckCount}⏳`;
                    }
                } catch (e) {
                    btn.textContent = `${autoCheckCount}❌`;
                }
                
                // Stop after 20 checks (10 minutes)
                if (autoCheckCount >= 20) {
                    clearInterval(autoCheckInterval);
                    autoCheckInterval = null;
                    btn.textContent = 'Auto';
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-gray-600');
                    console.log('⏰ Auto-check timeout after 10 minutes');
                }
            }
        }, 30000); // Check every 30 seconds
        
        btn.textContent = '1⏳';
        btn.classList.remove('bg-gray-600');
        btn.classList.add('bg-green-600');
        
        // Request notification permission
        if (window.Notification && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        console.log('🔄 Auto-check started (every 30 seconds)');
    }
}
</script>
