<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>File Name - SecureDocs</title> 
    
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              '#1D1D2F': '#1D1D2F',
              '#141326': '#141326',
              '#f89c00': '#f89c00',
              '#3C3F58': '#3C3F58',
              '#55597C': '#55597C',
              '#1F1F33': '#1F1F33',
            },
            fontFamily: {
              'poppins': ['Poppins', 'sans-serif'],
              'inter': ['Inter', 'sans-serif']
            }
          }
        }
      }
    </script>
    
    <style>
        /* This style block is for custom fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1D1D2F;
            color: white;
        }

        /* Grid View/List View button */
        .view-toggle-btn {
            background-color: #3C3F58 !important;
            border-color: #55597C !important;
            color: #9CA3AF !important; /* Set inactive color */
            transition: filter 0.2s ease;
        }
        .view-toggle-btn:hover {
            filter: brightness(1.1);
        }
        .view-toggle-btn.active {
            background-color: #55597C !important;
            border-color: #6B7280 !important;
            color: #FFFFFF !important; /* Set active color */
        }
    </style>
</head>

<body>
    
    <div class="flex flex-col min-h-screen" id="folder-view-ui"> 
        
        <div class="bg-[#141326] px-6 py-6">
            <div class="flex items-center justify-between w-full">
                <button id="back-button" style="margin-left: 10px;" class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                    <img src="{{ asset('back-arrow.png') }}" alt="Back" class="w-5 h-5">
                </button>
                <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                    <img src="{{ asset('logo-white.png') }}" alt="Logo" class="h-8 w-auto">
                    <h2 class="font-bold text-xl text-[#f89c00] font-['Poppins']">Folder Sharing</h2>
                </div>
                <!-- Right: Login / Sign Up Buttons (from storyboard) -->
                <div class="flex items-center gap-6">
                    <a href="/login" class="text-sm font-medium transition-all duration-200 hover:text-[#ff9c00]">{{ __('auth.login') }}</a>
                    <a href="/register" class="bg-[#ff9c00] text-black px-4 py-2 rounded-full font-bold transition-all duration-200 hover:brightness-110">{{ __('auth.signup') }}</a>
                </div>
            </div>
        </div>

        <div class="fixed bottom-6 right-6 z-50">
            <div class="relative">
                <button id="language-toggle" class="bg-[#3c3f58] text-white p-3 rounded-full shadow-lg transition"
                    style="transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#55597C';"
                    onmouseout="this.style.backgroundColor='';">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                </button>
                <div id="language-dropdown" style="background-color: #3c3f58; border: 3px solid #1F1F33" class="absolute bottom-full right-0 mb-2 hidden bg-[#3c3f58] rounded-lg shadow-xl overflow-hidden min-w-[140px]">
                    <a href="#"
                        class="flex items-center px-4 py-3 text-sm transition-colors bg-[#f89c00] text-black font-bold">
                        <span class="mr-2">🇺🇸</span>
                        English
                    </a>
                    <a href="#"
                        class="flex items-center px-4 py-3 text-sm transition-colors text-white"
                        style="transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#55597C';"
                        onmouseout="this.style.backgroundColor='';">
                        <span class="mr-2">🇵🇭</span>
                        Filipino
                    </a>
                </div>
            </div>
        </div>

        <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 flex-1">
            
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-4">
                <div>
                    <h1 class="text-2xl font-semibold text-white">
                    Folder from <span class="font-bold text-[#ff9c00] inline-block max-w-[30ch] truncate align-bottom">A-Very-Long-User-Name-That-Will-Now-Be-Truncated"</span>
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                <div id="viewToggleBtns" class="flex gap-2">
                        <button id="btnGridLayout" data-view="grid" title="Grid view" aria-label="Grid view"
                                class="view-toggle-btn py-2 px-4 border rounded text-sm"> <span><img src="{{ asset('grid.png') }}" alt="Grid" class="w-4 h-4"></span>
                        </button>
                        <button id="btnListLayout" data-view="list" title="List view" aria-label="List view"
                                class="view-toggle-btn active py-2 px-4 border rounded text-sm"> <span><img src="{{ asset('list.png') }}" alt="List" class="w-4 h-4"></span>
                        </button>
                    </div>
                    <a href="#" class="bg-[#ff9c00] text-black px-4 py-2 rounded-full font-bold transition-all duration-200 hover:brightness-110">
                        Download
                    </a>
                </div>
            </div>
            
            <div class="mb-6">
                <div id="new-breadcrumbs" class="flex items-center text-sm text-gray-400">
                    <a href="#" class="hover:text-white hover:underline">Folder</a>
                    <span class="mx-1.5">/</span>
                    <span class="font-medium text-white px-2 py-1 bg-[#3C3F58] rounded-md">Sub-Folder</span>
                </div>
            </div>

            <div id="file-list-container" class="space-y-2">
                <div class="bg-[#3C3F58] bg-opacity-40 hover:bg-opacity-80 transition-all duration-200 rounded-lg p-4 flex items-center justify-between cursor-pointer">
                    <div class="flex items-center space-x-4 min-w-0">
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <div class="w-8 h-8 flex items-center justify-center">
                                    <svg viewBox="0 0 35 40" height="35" width="30">
                                        <path d="M34.28 12.14V37.86C34.28 38.141 34.2246 38.4193 34.1171 38.6789C34.0096 38.9386 33.8519 39.1745 33.6532 39.3732C33.4545 39.5719 33.2186 39.7296 32.9589 39.8371C32.6993 39.9446 32.421 40 32.14 40H2.14C1.85897 40 1.58069 39.9446 1.32106 39.8371C1.06142 39.7296 0.825509 39.5719 0.626791 39.3732C0.428074 39.1745 0.270443 38.9386 0.162898 38.6789C0.0553525 38.4193 0 38.141 0 37.86V2.14C0 1.57244 0.225464 1.02812 0.626791 0.626791C1.02812 0.225464 1.57244 0 2.14 0H22.14C23.4969 0.0774993 24.7874 0.613415 25.8 1.52L32.8 8.52C33.6838 9.52751 34.2048 10.8019 34.28 12.14ZM31.42 14.28H22.14C21.5724 14.28 21.0281 14.0545 20.6268 13.6532C20.2255 13.2519 20 12.7076 20 12.14V2.86H2.85V37.14H31.43V14.29L31.42 14.28ZM22.85 11.42H31.24C31.1355 11.0855 30.9693 10.7734 30.75 10.5L23.75 3.5C23.4825 3.28063 23.1776 3.11126 22.85 3V11.39V11.42Z" fill="#9ca3af"></path>
                                    </svg>
                                </div>
                                <div class="absolute -bottom-1 -right-1 bg-orange-500 text-white text-xs px-1 rounded">
                                    PDF
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate">
                                FILE NAME
                            </div>
                            <div class="text-xs text-gray-400">
                                YYYY-MM-DD HH:MM
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button class="p-2 text-gray-400 hover:text-white rounded-full hover:bg-white/10 transition-colors" title="More options">
                            <svg viewBox="0 0 6 16" width="8" height="14">
                                <path d="M2 4C3.1 4 4 3.1 4 2C4 0.9 3.1 0 2 0C0.9 0 0 0.9 0 2C0 3.1 0.9 4 2 4ZM2 6C0.9 6 0 6.9 0 8C0 9.1 0.9 10 2 10C3.1 10 4 9.1 4 8C4 6.9 3.1 6 2 6ZM2 12C0.9 12 0 12.9 0 14C0 15.1 0.9 16 2 16C3.1 16 4 15.1 4 14C4 12.9 3.1 12 2 12Z" fill="currentColor"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="bg-[#3C3F58] bg-opacity-40 hover:bg-opacity-80 transition-all duration-200 rounded-lg p-4 flex items-center justify-between cursor-pointer">
                    <div class="flex items-center space-x-4 min-w-0">
                        <div class="flex-shrink-0">
                            <span class="text-3xl">📁</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate">
                                Another Folder
                            </div>
                            <div class="text-xs text-gray-400">
                                YYYY-MM-DD HH:MM
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button class="p-2 text-gray-400 hover:text-white rounded-full hover:bg-white/10 transition-colors" title="More options">
                            <svg viewBox="0 0 6 16" width="8" height="14">
                                <path d="M2 4C3.1 4 4 3.1 4 2C4 0.9 3.1 0 2 0C0.9 0 0 0.9 0 2C0 3.1 0.9 4 2 4ZM2 6C0.9 6 0 6.9 0 8C0 9.1 0.9 10 2 10C3.1 10 4 9.1 4 8C4 6.9 3.1 6 2 6ZM2 12C0.9 12 0 12.9 0 14C0 15.1 0.9 16 2 16C3.1 16 4 15.1 4 14C4 12.9 3.1 12 2 12Z" fill="currentColor"></path>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
            
            <div id="file-grid-view" class="hidden">
                </div>

        </div>
    </div>

    <div class="flex flex-col min-h-screen" id="file-view-ui" style="display: none;"> 
        
        <div class="bg-[#141326] px-6 py-6">
            <div class="flex items-center justify-between w-full">
                <button id="back-button-file" style="margin-left: 10px;" class="flex items-center text-white hover:text-gray-300 transition-colors duration-200">
                    <img src="https://i.imgur.com/gwuBq9H.png" alt="Back" class="w-5 h-5"> </button>
                <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
                    <img src="https://i.imgur.com/s7zQ3S4.png" alt="Logo" class="h-8 w-auto"> <h2 class="font-bold text-xl text-[#f89c00] font-['Poppins']">File Sharing</h2>
                </div>
                <div class="flex items-center gap-6">
                    <a href="#" class="text-sm font-medium transition-all duration-200 hover:text-[#ff9c00]">Login</a>
                    <a href="#" class="bg-[#ff9c00] text-black px-4 py-2 rounded-full font-bold transition-all duration-200 hover:brightness-110">Sign Up</a>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-6 py-8 flex-1 flex items-center justify-center">

            <div class="bg-[#3C3F58] w-full max-w-lg p-8 mb-4 md:p-12 rounded-2xl">
                
                <div class="flex justify-center mb-6">
                    <img src="https://i.imgur.com/mJc2yDk.png" alt="File Icon" class="w-12 h-12"> </div>

                <h2 class="text-xl font-semibold text-white text-center mb-8 truncate">
                    My-File-Name.pdf
                </h2>

                <div class="space-y-2 text-sm text-gray-300 mb-10">
                    <p>
                        <span class="font-medium text-gray-100">Shared By :</span>
                        Sender Name
                    </p>
                    <p>
                        <span class="font-medium text-gray-100">Share Link Expires in :</span>
                        Never
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button class="w-full py-3 px-4 bg-[#55597C] hover:brightness-110 text-white font-medium rounded-lg text-center transition-all duration-200">
                        Save to My Files
                    </button>
                    
                    <a href="#" class="w-full py-3 px-4 bg-[#f89c00] hover:brightness-110 text-black font-semibold rounded-lg text-center block transition-all duration-200">
                        Download
                    </a>
                </div>
            </div>
        </div>
    </div>


    <script>
        // --- Language Toggle Script ---
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('language-toggle');
            const dropdown = document.getElementById('language-dropdown');

            if (toggleButton && dropdown) {
                toggleButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdown.classList.toggle('hidden');
                });
                document.addEventListener('click', function(e) {
                    if (!toggleButton.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
        });

        // --- Grid/List View Toggle Script ---
        
        let currentView = 'list';
        
        // --- MODIFIED FUNCTION ---
        // --- UPDATED FUNCTION ---
        // This function now adds/removes the .active class
        // based on the CSS you provided.
        function toggleListView() {
            const table = document.getElementById('file-list-container');
            const listBtn = document.getElementById('btnListLayout');
            const gridBtn = document.getElementById('btnGridLayout');
            const gridView = document.getElementById('file-grid-view');

            if (!table || !listBtn || !gridBtn) return; 
            if (currentView === 'list') return;
            
            currentView = 'list';
            
            // Update button states using the .active class
            listBtn.classList.add('active');
            listBtn.setAttribute('aria-pressed', 'true');
            
            gridBtn.classList.remove('active');
            gridBtn.setAttribute('aria-pressed', 'false');
            
            // Show list view
            table.style.display = 'block'; 
            
            // Hide grid view
            if (gridView) gridView.style.display = 'none';
        }

        // --- MODIFIED FUNCTION ---
        // --- UPDATED FUNCTION ---
        // This function now adds/removes the .active class
        // based on the CSS you provided.
        function toggleGridView() {
            const table = document.getElementById('file-list-container');
            const listBtn = document.getElementById('btnListLayout');
            const gridBtn = document.getElementById('btnGridLayout');
            let gridView = document.getElementById('file-grid-view');

            if (!table || !listBtn || !gridBtn) return; 
            if (currentView === 'grid') return;
            
            currentView = 'grid';
            
            // Update button states using the .active class
            gridBtn.classList.add('active');
            gridBtn.setAttribute('aria-pressed', 'true');

            listBtn.classList.remove('active');
            listBtn.setAttribute('aria-pressed', 'false');
            
            if (!gridView) {
                console.error("Grid view container '#file-grid-view' not found!");
                return;
            }
            
            // Create grid view content if it's empty
            if (gridView.innerHTML.trim() === '') {
                createGridView(gridView); // Pass the container
            }
            
            // Toggle views
            table.style.display = 'none';
            gridView.style.display = 'grid';
        }

        // --- MODIFIED FUNCTION ---
        // Creates static grid view items
        function createGridView(gridViewContainer) {
            // Set the grid classes on the container
            gridViewContainer.className = 'grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4';
            
            // STATIC PLACEHOLDER DATA
            const staticFiles = [
                { id: 1, name: 'FILE NAME', is_folder: false, size: 'N/A', icon: '📄' },
                { id: 2, name: 'Another Folder', is_folder: true, size: 'N/A', icon: '📁' },
                { id: 3, name: 'report.pdf', is_folder: false, size: '1.2 MB', icon: '📄' },
                { id: 4, name: 'Photos', is_folder: true, size: 'N/A', icon: '📁' },
            ];
            
            if (staticFiles.length > 0) {
                staticFiles.forEach(file => {
                    const fileCard = document.createElement('div');
                    // Styling for grid cards
                    fileCard.className = 'bg-[#3C3F58] bg-opacity-40 hover:bg-opacity-80 border border-transparent hover:border-white/10 rounded-lg p-4 transition-all cursor-pointer';
                    fileCard.innerHTML = `
                        <div class="text-center">
                            <div class="text-4xl mb-2">${file.icon}</div>
                            <div class="text-sm font-medium text-white truncate">${file.name}</div>
                            <div class="text-xs text-gray-400 mt-1">${file.size}</div>
                        </div>
                    `;
                    gridViewContainer.appendChild(fileCard);
                });
            } else {
                gridViewContainer.innerHTML = `
                    <div class="col-span-full text-center py-8 text-gray-400">
                        <div class="text-4xl mb-2">📁</div>
                        <p>This folder is empty</p>
                    </div>
                `;
            }
            
            return gridViewContainer;
        }

        // Initialize list view as active
        document.addEventListener('DOMContentLoaded', function() {
            // Only run folder-specific initializers if we are in folder view
            if (document.getElementById('file-list-container')) {
                toggleListView(); // Set default
                // Add click listeners
                document.getElementById('btnGridLayout')?.addEventListener('click', toggleGridView);
                document.getElementById('btnListLayout')?.addEventListener('click', toggleListView);
            }
        });

    </script>
</body>
</html>