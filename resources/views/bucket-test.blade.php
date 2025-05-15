<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Supabase Bucket Test</title>
    <script>
        window.SUPABASE_URL = "{{ config('services.supabase.url') }}";
        window.SUPABASE_KEY = "{{ config('services.supabase.key') }}";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .loading { display: none; }
        .loading.active { display: block; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Supabase Storage API Test - "trial" Bucket</h1>
        
        <!-- Auth Status -->
        <div id="authStatus" class="mb-8 p-4 border border-gray-200 rounded">
            <h2 class="text-xl font-semibold mb-4">Authentication Status</h2>
            <div id="currentUser" class="mb-4">Checking authentication...</div>
            
            <div id="loginForm" class="hidden">
                <p class="mb-2 text-sm text-gray-700">You need to be logged in for bucket policies to apply correctly.</p>
                <input type="email" id="email" placeholder="Email" class="border p-2 mb-2 w-full max-w-md">
                <input type="password" id="password" placeholder="Password" class="border p-2 mb-2 w-full max-w-md">
                <button id="loginBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Login</button>
                <div id="loginResult" class="mt-2"></div>
            </div>
            
            <div id="loggedInActions" class="hidden">
                <button id="logoutBtn" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">Logout</button>
            </div>
        </div>
        
        <!-- Upload Test -->
        <div class="mb-8 p-4 border border-gray-200 rounded">
            <h2 class="text-xl font-semibold mb-4">Test 1: Upload File</h2>
            <input type="file" id="testUpload" class="mb-4">
            <button id="uploadBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Upload to TRIAL bucket</button>
            <div id="uploadResult" class="mt-4 p-2 bg-gray-50"></div>
            <div id="uploadLoading" class="loading mt-2 text-blue-500">Uploading...</div>
        </div>
        
        <!-- List Test -->
        <div class="mb-8 p-4 border border-gray-200 rounded">
            <h2 class="text-xl font-semibold mb-4">Test 2: List Files</h2>
            <button id="listBtn" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">List Files in TRIAL bucket</button>
            <div id="listResult" class="mt-4 p-2 bg-gray-50"></div>
            <div id="listLoading" class="loading mt-2 text-green-500">Loading file list...</div>
        </div>
        
        <!-- Delete Test -->
        <div class="mb-8 p-4 border border-gray-200 rounded">
            <h2 class="text-xl font-semibold mb-4">Test 3: Delete File</h2>
            <input type="text" id="deleteInput" class="border p-2 w-full mb-4" placeholder="Enter file path to delete (e.g. test.jpg)">
            <button id="deleteBtn" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete from TRIAL bucket</button>
            <div id="deleteResult" class="mt-4 p-2 bg-gray-50"></div>
            <div id="deleteLoading" class="loading mt-2 text-red-500">Deleting...</div>
        </div>

        <!-- File List -->
        <div class="p-4 border border-gray-200 rounded">
            <h2 class="text-xl font-semibold mb-4">Files in TRIAL Bucket</h2>
            <div id="fileList" class="mt-4 divide-y divide-gray-200">
                <!-- Files will be listed here -->
                <div class="text-gray-500">No files loaded yet. Click "List Files" to view.</div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Supabase client
        const supabaseUrl = window.SUPABASE_URL;
        const supabaseKey = window.SUPABASE_KEY;
        const supabase = window.supabase.createClient(supabaseUrl, supabaseKey);
        const bucketName = 'trial'; // New test bucket
        
        // Authentication handling
        async function checkAuthStatus() {
            const { data: { user }, error } = await supabase.auth.getUser();
            updateAuthUI(user);
            return user;
        }
        
        function updateAuthUI(user) {
            const currentUserDiv = document.getElementById('currentUser');
            const loginForm = document.getElementById('loginForm');
            const loggedInActions = document.getElementById('loggedInActions');
            
            if (user) {
                currentUserDiv.innerHTML = `<div class="text-green-600 font-medium">✓ Authenticated as: ${user.email}</div>`;
                loginForm.classList.add('hidden');
                loggedInActions.classList.remove('hidden');
            } else {
                currentUserDiv.innerHTML = `<div class="text-red-600 font-medium">✗ Not authenticated</div>`;
                loginForm.classList.remove('hidden');
                loggedInActions.classList.add('hidden');
            }
        }
        
        // Login functionality
        document.getElementById('loginBtn')?.addEventListener('click', async function() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const resultDiv = document.getElementById('loginResult');
            
            if (!email || !password) {
                resultDiv.innerHTML = '<div class="text-red-500">Please enter both email and password</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="text-blue-500">Logging in...</div>';
            
            try {
                const { data, error } = await supabase.auth.signInWithPassword({ email, password });
                if (error) {
                    resultDiv.innerHTML = `<div class="text-red-500">Login failed: ${error.message}</div>`;
                } else {
                    resultDiv.innerHTML = '<div class="text-green-500">Login successful!</div>';
                    updateAuthUI(data.user);
                }
            } catch (err) {
                resultDiv.innerHTML = `<div class="text-red-500">Exception: ${err.message}</div>`;
            }
        });
        
        // Logout functionality
        document.getElementById('logoutBtn')?.addEventListener('click', async function() {
            try {
                await supabase.auth.signOut();
                updateAuthUI(null);
            } catch (err) {
                console.error('Logout error:', err);
            }
        });
        
        // Check auth status on page load
        checkAuthStatus();

        // Upload functionality
        document.getElementById('uploadBtn').addEventListener('click', async function() {
            const fileInput = document.getElementById('testUpload');
            const resultDiv = document.getElementById('uploadResult');
            const loadingDiv = document.getElementById('uploadLoading');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                resultDiv.innerHTML = '<div class="text-red-500">Please select a file first</div>';
                return;
            }
            
            const file = fileInput.files[0];
            const filePath = file.name; // Simple path, just the filename
            
            loadingDiv.classList.add('active');
            resultDiv.innerHTML = '';
            
            try {
                const { data, error } = await supabase.storage
                    .from(bucketName)
                    .upload(filePath, file, {
                        cacheControl: '3600',
                        upsert: true
                    });
                
                if (error) {
                    resultDiv.innerHTML = `<div class="text-red-500">Error: ${error.message}</div>`;
                    console.error('Upload error:', error);
                } else {
                    resultDiv.innerHTML = `
                        <div class="text-green-500">Upload successful!</div>
                        <div class="mt-2 text-sm break-all">Path: ${filePath}</div>
                        <pre class="mt-2 text-xs bg-gray-100 p-2 overflow-auto">${JSON.stringify(data, null, 2)}</pre>
                    `;
                    console.log('Upload success:', data);
                    
                    // Refresh the file list
                    document.getElementById('listBtn').click();
                }
            } catch (err) {
                resultDiv.innerHTML = `<div class="text-red-500">Exception: ${err.message}</div>`;
                console.error('Upload exception:', err);
            } finally {
                loadingDiv.classList.remove('active');
            }
        });
        
        // List functionality
        document.getElementById('listBtn').addEventListener('click', async function() {
            const resultDiv = document.getElementById('listResult');
            const loadingDiv = document.getElementById('listLoading');
            const fileListDiv = document.getElementById('fileList');
            
            loadingDiv.classList.add('active');
            resultDiv.innerHTML = '';
            
            try {
                const { data, error } = await supabase.storage
                    .from(bucketName)
                    .list();
                
                if (error) {
                    resultDiv.innerHTML = `<div class="text-red-500">Error: ${error.message}</div>`;
                    console.error('List error:', error);
                } else {
                    resultDiv.innerHTML = `
                        <div class="text-green-500">List successful! Found ${data?.length || 0} files</div>
                        <pre class="mt-2 text-xs bg-gray-100 p-2 overflow-auto">${JSON.stringify(data, null, 2)}</pre>
                    `;
                    console.log('List success:', data);
                    
                    // Update the file list UI
                    if (data && data.length > 0) {
                        let fileListHTML = '';
                        data.forEach(item => {
                            fileListHTML += `
                                <div class="py-3 flex justify-between items-center">
                                    <div>
                                        <div class="font-medium">${item.name}</div>
                                        <div class="text-xs text-gray-500">${item.id || ''}</div>
                                    </div>
                                    <button class="delete-file bg-red-100 text-red-700 px-3 py-1 rounded text-sm"
                                            data-path="${item.name}">Delete</button>
                                </div>
                            `;
                        });
                        fileListDiv.innerHTML = fileListHTML;
                        
                        // Add delete handlers
                        document.querySelectorAll('.delete-file').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const path = this.getAttribute('data-path');
                                document.getElementById('deleteInput').value = path;
                                document.getElementById('deleteBtn').click();
                            });
                        });
                    } else {
                        fileListDiv.innerHTML = '<div class="text-gray-500">No files found in bucket</div>';
                    }
                }
            } catch (err) {
                resultDiv.innerHTML = `<div class="text-red-500">Exception: ${err.message}</div>`;
                console.error('List exception:', err);
            } finally {
                loadingDiv.classList.remove('active');
            }
        });
        
        // Delete functionality
        document.getElementById('deleteBtn').addEventListener('click', async function() {
            const filePathInput = document.getElementById('deleteInput');
            const resultDiv = document.getElementById('deleteResult');
            const loadingDiv = document.getElementById('deleteLoading');
            
            const filePath = filePathInput.value.trim();
            
            if (!filePath) {
                resultDiv.innerHTML = '<div class="text-red-500">Please enter a file path to delete</div>';
                return;
            }
            
            loadingDiv.classList.add('active');
            resultDiv.innerHTML = '';
            
            try {
                const { data, error } = await supabase.storage
                    .from(bucketName)
                    .remove([filePath]);
                
                if (error) {
                    resultDiv.innerHTML = `<div class="text-red-500">Error: ${error.message}</div>`;
                    console.error('Delete error:', error);
                } else {
                    resultDiv.innerHTML = `
                        <div class="text-green-500">Delete successful!</div>
                        <pre class="mt-2 text-xs bg-gray-100 p-2 overflow-auto">${JSON.stringify(data, null, 2)}</pre>
                    `;
                    console.log('Delete success:', data);
                    
                    // Refresh the file list
                    document.getElementById('listBtn').click();
                }
            } catch (err) {
                resultDiv.innerHTML = `<div class="text-red-500">Exception: ${err.message}</div>`;
                console.error('Delete exception:', err);
            } finally {
                loadingDiv.classList.remove('active');
            }
        });
        
        // Initial list load
        window.addEventListener('load', function() {
            // Wait a moment for everything to initialize
            setTimeout(() => {
                document.getElementById('listBtn').click();
            }, 500);
        });
    </script>
</body>
</html>
