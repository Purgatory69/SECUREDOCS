<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Arweave Upload Test - Boolean Fix Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-2 text-center">🧪 Arweave Upload Test</h1>
            <p class="text-gray-400 text-center mb-8">Testing PostgreSQL Boolean Fix</p>
            
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">📋 Test Instructions</h2>
                <ul class="text-sm text-gray-300 space-y-2">
                    <li>• This page tests the boolean fix for is_encrypted column</li>
                    <li>• Uses random test data (no real Arweave upload)</li>
                    <li>• Check database after test to verify boolean values</li>
                    <li>• Should store <code class="bg-gray-700 px-1 rounded">true</code>/<code class="bg-gray-700 px-1 rounded">false</code> not <code class="bg-gray-700 px-1 rounded">1</code>/<code class="bg-gray-700 px-1 rounded">0</code></li>
                </ul>
            </div>

            <!-- Test Form -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">🚀 Upload Tests</h2>
                
                <!-- Non-Encrypted Test -->
                <div class="mb-6">
                    <button id="testNonEncrypted" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                        🔓 Test Non-Encrypted Upload (is_encrypted: false)
                    </button>
                    <div id="result1" class="mt-2 p-3 rounded bg-gray-700 hidden"></div>
                </div>

                <!-- Encrypted Test -->
                <div class="mb-6">
                    <button id="testEncrypted" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                        🔐 Test Encrypted Upload (is_encrypted: true)
                    </button>
                    <div id="result2" class="mt-2 p-3 rounded bg-gray-700 hidden"></div>
                </div>

                <!-- Test Both -->
                <div class="mb-6">
                    <button id="testBoth" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                        🧪 Test Both (Sequential)
                    </button>
                    <div id="result3" class="mt-2 p-3 rounded bg-gray-700 hidden"></div>
                </div>
            </div>

            <!-- Database Check -->
            <div class="bg-gray-800 rounded-lg p-6 mt-6">
                <h2 class="text-xl font-semibold mb-4">🗄️ Database Verification</h2>
                <p class="text-sm text-gray-300 mb-4">After testing, check the database:</p>
                <code class="block bg-gray-900 p-3 rounded text-sm text-green-400">
SELECT id, file_name, is_encrypted, created_at 
FROM arweave_urls 
WHERE file_name LIKE 'test-%' 
ORDER BY created_at DESC 
LIMIT 5;
                </code>
                <p class="text-xs text-gray-400 mt-2">
                    ✅ Success: is_encrypted shows <code>true</code>/<code>false</code><br>
                    ❌ Failed: is_encrypted shows <code>1</code>/<code>0</code>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Helper function to make API calls
        async function testArweaveUpload(testData, resultElement) {
            resultElement.classList.remove('hidden');
            resultElement.innerHTML = '<div class="text-yellow-400">🔄 Testing...</div>';
            
            try {
                const response = await fetch('/arweave-client/save-upload', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(testData)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    resultElement.innerHTML = `
                        <div class="text-green-400">✅ SUCCESS!</div>
                        <div class="text-sm text-gray-300 mt-1">
                            Record ID: ${result.id || 'N/A'}<br>
                            Boolean fix working: is_encrypted stored as proper boolean
                        </div>
                    `;
                } else {
                    const errorText = await response.text();
                    resultElement.innerHTML = `
                        <div class="text-red-400">❌ HTTP ${response.status}</div>
                        <div class="text-sm text-gray-300 mt-1">${errorText}</div>
                    `;
                }
            } catch (error) {
                resultElement.innerHTML = `
                    <div class="text-red-400">❌ ERROR</div>
                    <div class="text-sm text-gray-300 mt-1">${error.message}</div>
                `;
            }
        }
        
        // Generate random test data
        function generateTestData(isEncrypted) {
            const timestamp = Date.now();
            const randomId = Math.random().toString(36).substring(2, 15);
            
            const baseData = {
                arweave_url: `https://arweave.net/TEST_${randomId}`,
                file_name: `test-${isEncrypted ? 'encrypted' : 'public'}-${timestamp}.png`,
                is_encrypted: isEncrypted,
                upload_cost_matic: 0.005,
                transaction_id: `TEST_${randomId}`,
                file_size_bytes: Math.floor(Math.random() * 1000000) + 50000,
                mime_type: 'image/png'
            };
            
            if (isEncrypted) {
                baseData.encryption_method = 'AES-256-GCM';
                baseData.password_hash = `hash_${randomId}`;
                baseData.salt = [1,2,3,4,5,6,7,8];
                baseData.iv = [9,10,11,12,13,14,15,16];
            }
            
            return baseData;
        }
        
        // Event listeners
        document.getElementById('testNonEncrypted').addEventListener('click', () => {
            const testData = generateTestData(false);
            testArweaveUpload(testData, document.getElementById('result1'));
        });
        
        document.getElementById('testEncrypted').addEventListener('click', () => {
            const testData = generateTestData(true);
            testArweaveUpload(testData, document.getElementById('result2'));
        });
        
        document.getElementById('testBoth').addEventListener('click', async () => {
            const resultElement = document.getElementById('result3');
            resultElement.classList.remove('hidden');
            resultElement.innerHTML = '<div class="text-yellow-400">🔄 Testing both scenarios...</div>';
            
            try {
                // Test non-encrypted first
                const testData1 = generateTestData(false);
                await testArweaveUpload(testData1, { 
                    classList: { remove: () => {}, add: () => {} }, 
                    innerHTML: '' 
                });
                
                // Wait a bit
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Test encrypted
                const testData2 = generateTestData(true);
                await testArweaveUpload(testData2, { 
                    classList: { remove: () => {}, add: () => {} }, 
                    innerHTML: '' 
                });
                
                resultElement.innerHTML = `
                    <div class="text-green-400">✅ BOTH TESTS COMPLETED!</div>
                    <div class="text-sm text-gray-300 mt-1">
                        Non-encrypted: ${testData1.file_name}<br>
                        Encrypted: ${testData2.file_name}<br>
                        Check database to verify boolean values!
                    </div>
                `;
            } catch (error) {
                resultElement.innerHTML = `
                    <div class="text-red-400">❌ BATCH TEST ERROR</div>
                    <div class="text-sm text-gray-300 mt-1">${error.message}</div>
                `;
            }
        });
    </script>
</body>
</html>
