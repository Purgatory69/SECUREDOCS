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
                    <p class="text-gray-400 mb-4">Connect MetaMask to initialize your Bundlr account</p>
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
                            <p class="text-2xl font-bold text-green-400" id="currentBundlrBalance">0.100000</p>
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
                            <span class="text-gray-300" id="uploadFileName">document.pdf</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>Upload Cost:</span>
                            <span class="text-yellow-400" id="uploadCostFinal">~0.005 MATIC</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>Your Balance:</span>
                            <span class="text-green-400" id="uploadBalanceFinal">0.1 MATIC</span>
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
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   id="arweaveSuccessUrl"
                                   readonly 
                                   class="flex-1 px-3 py-2 bg-[#3C3F58] border border-gray-600 rounded-lg text-white text-sm font-mono">
                            <button onclick="copyToClipboard(document.getElementById('arweaveSuccessUrl').href)" 
                                    class="px-3 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                                Copy
                            </button>
                            <a id="arweaveSuccessUrl" 
                               href="#" 
                               target="_blank"
                               class="px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                                View
                            </a>
                        </div>
                    </div>
                    
                    <div class="border-t border-[#3C3F58] pt-4">
                        <div class="flex items-center justify-between text-sm mb-4">
                            <span>Remaining Balance:</span>
                            <span class="text-green-400" id="arweaveSuccessBalance">0.095 MATIC</span>
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
        // You could add a toast notification here
        console.log('Copied to clipboard!');
    });
}
</script>
