@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#141326] text-white">
    <!-- Header -->
    <header class="flex items-center justify-between p-4 bg-[#0D0E2F] border-b border-border-color">
        <div class="flex items-center space-x-4">
            <button id="backBtn" class="p-2 hover:bg-gray-700 rounded">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <div>
                <h1 id="fileName" class="text-xl font-semibold"></h1>
                <p id="fileInfo" class="text-sm text-gray-400"></p>
            </div>
        </div>
        
        <div class="flex items-center space-x-2">
            <button id="downloadBtn" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded text-sm">Download</button>
            <button id="moreBtn" class="p-2 hover:bg-gray-700 rounded">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"></path>
                </svg>
            </button>
        </div>
    </header>

    <!-- Preview Container -->
    <div class="flex flex-1">
        <!-- Main Preview Area -->
        <main class="flex-1 p-6">
            <div id="previewContainer" class="w-full h-full bg-white rounded-lg shadow-lg overflow-hidden">
                <div id="loadingSpinner" class="flex items-center justify-center h-96">
                    <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500"></div>
                </div>
                
                <!-- Image Preview -->
                <div id="imagePreview" class="hidden">
                    <img id="previewImage" class="w-full h-auto max-h-screen object-contain" />
                </div>

                <!-- PDF Preview -->
                <div id="pdfPreview" class="hidden h-full">
                    <iframe id="pdfViewer" class="w-full h-screen border-0"></iframe>
                </div>

                <!-- Document Preview (Office files) -->
                <div id="documentPreview" class="hidden h-full">
                    <iframe id="documentViewer" class="w-full h-screen border-0"></iframe>
                </div>

                <!-- Video Preview -->
                <div id="videoPreview" class="hidden">
                    <video id="videoPlayer" class="w-full h-auto" controls>
                        Your browser does not support the video tag.
                    </video>
                </div>

                <!-- Audio Preview -->
                <div id="audioPreview" class="hidden p-6">
                    <div class="bg-gray-100 rounded-lg p-8 text-center">
                        <div class="text-6xl mb-4">🎵</div>
                        <audio id="audioPlayer" class="w-full" controls>
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </div>

                <!-- Text Preview -->
                <div id="textPreview" class="hidden p-6">
                    <pre id="textContent" class="bg-gray-50 p-4 rounded text-gray-800 overflow-auto max-h-96 whitespace-pre-wrap font-mono text-sm"></pre>
                </div>

                <!-- Code Preview -->
                <div id="codePreview" class="hidden p-6">
                    <pre id="codeContent" class="bg-gray-900 text-green-400 p-4 rounded overflow-auto max-h-96 font-mono text-sm"></pre>
                </div>

                <!-- Unsupported File -->
                <div id="unsupportedPreview" class="hidden p-6 text-center">
                    <div class="text-6xl mb-4 text-gray-400">📄</div>
                    <h3 class="text-xl mb-2 text-gray-600">Preview not available</h3>
                    <p class="text-gray-500 mb-4">This file type cannot be previewed in the browser.</p>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="downloadFile()">
                        Download to view
                    </button>
                </div>
            </div>
        </main>

        <!-- Sidebar -->
        <aside id="sidebar" class="w-80 bg-[#1A1B3E] border-l border-border-color p-6 hidden lg:block">
            <!-- File Details -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">File Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Size:</span>
                        <span id="fileSize"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Type:</span>
                        <span id="fileType"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Modified:</span>
                        <span id="fileModified"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Owner:</span>
                        <span id="fileOwner"></span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
                <div id="recentActivity" class="space-y-2 text-sm text-gray-400">
                    <!-- Activity items will be populated by JavaScript -->
                </div>
            </div>

        </aside>
    </div>
</div>

<!-- Hidden file input for uploading new versions -->
<input type="file" id="newVersionInput" class="hidden" />

@push('scripts')
    @vite(['resources/js/file-preview.js'])
@endpush

@endsection
