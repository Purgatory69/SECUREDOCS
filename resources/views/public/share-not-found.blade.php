<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Not Found - SecureDocs</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css'])
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="error-card w-full max-w-md p-8 text-center">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-center mb-4">
                    <img src="{{ asset('logo-white.png') }}" alt="SecureDocs" class="h-8 w-auto">
                    <span class="ml-3 text-2xl font-bold text-gray-800">SecureDocs</span>
                </div>
                
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Share Not Found</h1>
                <p class="text-gray-600">This share link doesn't exist or has been removed.</p>
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <div class="p-4 bg-yellow-50 rounded-lg text-left">
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-yellow-800">
                            <p class="font-medium">Possible reasons:</p>
                            <ul class="mt-2 space-y-1">
                                <li>• The link was typed incorrectly</li>
                                <li>• The file owner deleted the share</li>
                                <li>• The link has expired</li>
                                <li>• The file was removed</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 rounded-lg text-left">
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium">What to do:</p>
                            <ul class="mt-2 space-y-1">
                                <li>• Double-check the link URL</li>
                                <li>• Contact the person who shared the file</li>
                                <li>• Ask for a new share link</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button onclick="history.back()" 
                            class="flex-1 py-3 px-4 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        ← Go Back
                    </button>
                    <a href="{{ url('/') }}" 
                       class="flex-1 py-3 px-4 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors text-center">
                        🏠 Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
