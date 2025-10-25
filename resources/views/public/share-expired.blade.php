<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Expired - SecureDocs</title>
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
                
                <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    @switch($reason)
                        @case('expired')
                            Share Link Expired
                            @break
                        @case('used')
                            Link Already Used
                            @break
                        @case('limit_reached')
                            Download Limit Reached
                            @break
                        @default
                            Share Unavailable
                    @endswitch
                </h1>
                
                <p class="text-gray-600">
                    @switch($reason)
                        @case('expired')
                            This share link has expired and is no longer available.
                            @break
                        @case('used')
                            This was a one-time download link and has already been used.
                            @break
                        @case('limit_reached')
                            This share link has reached its maximum download limit.
                            @break
                        @default
                            This share link is no longer available.
                    @endswitch
                </p>
            </div>

            <!-- File Info (if available) -->
            @if(isset($share) && $share->file)
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                            @if($share->file->is_folder)
                                📁
                            @else
                                📄
                            @endif
                        </div>
                        <div class="flex-1 min-w-0 text-left">
                            <p class="font-medium text-gray-900 truncate">{{ $share->file->file_name }}</p>
                            <p class="text-sm text-gray-500">
                                @if($share->expires_at)
                                    Expired on {{ $share->expires_at->format('M j, Y g:i A') }}
                                @else
                                    No longer available
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 rounded-lg text-left">
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium">What can you do?</p>
                            <ul class="mt-2 space-y-1">
                                <li>• Contact the person who shared this file</li>
                                <li>• Ask them to create a new share link</li>
                                @if($reason === 'expired')
                                    <li>• Request a link with a longer expiration time</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <a href="{{ url('/') }}" 
                   class="inline-flex items-center justify-center w-full py-3 px-4 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    🏠 Go to SecureDocs
                </a>
            </div>
        </div>
    </div>
</body>
</html>
