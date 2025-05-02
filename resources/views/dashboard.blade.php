<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Securedocs</title>
    <style>
        :root {
            --primary-color: #4285f4;
            --primary-dark: #3367d6;
            --secondary-color: #34a853;
            --accent-color: #fbbc05;
            --danger-color: #ea4335;
            --text-color: #202124;
            --text-secondary: #5f6368;
            --background-light: #f8f9fa;
            --border-color: #dadce0;
            --box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --sidebar-width: 260px;
            --header-height: 64px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
        }
        
        body {
            background-color: white;
            color: var(--text-color);
            height: 100vh;
            display: grid;
            grid-template-rows: var(--header-height) 1fr;
            grid-template-columns: var(--sidebar-width) 1fr;
            grid-template-areas:
                "header header"
                "sidebar main";
        }
        
        /* Header */
        header {
            grid-area: header;
            display: flex;
            align-items: center;
            padding: 0 16px;
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            z-index: 100;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            margin-right: 40px;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            background-color: var(--primary-color);
            border-radius: 8px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .search-container {
            flex-grow: 1;
            max-width: 720px;
            position: relative;
        }
        
        .search-container input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border-radius: 8px;
            border: none;
            background-color: var(--background-light);
            font-size: 16px;
        }
        
        .search-container input:focus {
            outline: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .search-icon::before {
            content: "🔍";
            font-size: 16px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            margin-left: auto;
            gap: 16px;
        }
        
        .icon-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .icon-button:hover {
            background-color: var(--background-light);
        }
        
        .notification-icon::before {
            content: "🔔";
            font-size: 18px;
        }
        
        /* User Profile Dropdown */
        .user-profile-container {
            position: relative;
            display: inline-block;
        }
        
        .user-profile {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            z-index: 110;
            position: relative;
        }
        
        .profile-dropdown {
            position: absolute;
            top: 54px;
            right: 0;
            width: 280px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 100;
            overflow: hidden;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
        }
        
        .profile-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-header {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }
        
        .profile-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 16px;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .profile-email {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .dropdown-menu {
            list-style-type: none;
        }
        
        .menu-item {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .menu-item:hover {
            background-color: var(--background-light);
        }
        
        .menu-icon {
            margin-right: 16px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .menu-text {
            font-size: 14px;
        }
        
        .menu-divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 4px 0;
        }
        
        .dropdown-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }
        
        .logout-btn {
            padding: 8px 16px;
            background-color: var(--background-light);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .logout-btn:hover {
            background-color: #e8e8e8;
        }
        
        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            background-color: white;
            border-right: 1px solid var(--border-color);
            padding: 16px 0;
            overflow-y: auto;
        }
        
        .create-btn {
            display: flex;
            align-items: center;
            margin: 8px 16px 16px;
            padding: 12px 24px;
            background-color: #fff;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: box-shadow 0.2s;
        }
        
        .create-btn:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .create-icon {
            margin-right: 12px;
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .create-icon::before {
            content: "+";
        }
        
        .create-text {
            font-size: 16px;
            font-weight: 500;
        }
        
        .nav-list {
            list-style: none;
            margin-top: 16px;
        }
        
        .nav-item {
            padding: 12px 24px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
            margin-right: 16px;
        }
        
        .nav-item.active {
            background-color: #e8f0fe;
            color: var(--primary-color);
        }
        
        .nav-item:hover:not(.active) {
            background-color: var(--background-light);
        }
        
        .nav-icon {
            margin-right: 16px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .storage-info {
            margin-top: 32px;
            padding: 16px 24px;
        }
        
        .storage-progress {
            width: 100%;
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .storage-bar {
            height: 100%;
            width: 35%;
            background-color: var(--primary-color);
        }
        
        .storage-text {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        /* Main Content */
        .main-content {
            grid-area: main;
            background-color: white;
            padding: 24px;
            overflow-y: auto;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 400;
            margin-bottom: 24px;
        }
        
        .filters {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .filter-button {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            font-size: 14px;
            background-color: white;
            color: var(--text-secondary);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .filter-button:hover {
            background-color: var(--background-light);
        }
        
        .filter-button.active {
            background-color: #e8f0fe;
            color: var(--primary-color);
            border-color: #c6dafc;
        }
        
        .view-options {
            margin-left: auto;
            display: flex;
            gap: 8px;
        }
        
        /* Document Grid */
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .document-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            transition: box-shadow 0.2s;
            cursor: pointer;
        }
        
        .document-card:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .document-preview {
            height: 120px;
            background-color: var(--background-light);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }
        
        .document-icon {
            font-size: 32px;
        }
        
        .document-details {
            padding: 12px;
        }
        
        .document-name {
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }
        
        .document-meta {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .secure-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background-color: #e8f0fe;
            color: var(--primary-color);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }
        
        /* Overlay for dropdown */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: transparent;
            z-index: 90;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            body {
                grid-template-columns: 220px 1fr;
            }
        }
        
        @media (max-width: 768px) {
            body {
                grid-template-rows: var(--header-height) 1fr;
                grid-template-columns: 1fr;
                grid-template-areas:
                    "header"
                    "main";
            }
            
            .sidebar {
                display: none;
            }
            
            .header-logo {
                margin-right: 16px;
            }
            
            .profile-dropdown {
                width: 260px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-logo">
            <div class="logo-icon">S</div>
            <div class="logo-text">Securedocs</div>
        </div>
        
        <div class="search-container">
            <span class="search-icon"></span>
            <input type="text" placeholder="Search with AI-powered search">
        </div>
        
        <div class="header-actions">
            <div class="icon-button notification-icon"></div>
            <div class="user-profile-container">
                <div class="user-profile" id="userProfileBtn">U</div>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="dropdown-header">
                        <div class="profile-avatar">U</div>
                        <div class="profile-info">
                            <div class="profile-name">{{ Auth::user()->name }}</div>
                            <div class="profile-email">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <ul class="dropdown-menu">
                        <li class="menu-item">
                            <span class="menu-icon">👤</span>
                            <span class="menu-text">Profile Settings</span>
                        </li>
                        <li class="menu-item">
                            <span class="menu-icon">🔒</span>
                            <span class="menu-text">Security & Privacy</span>
                        </li>
                        <li class="menu-item">
                            <span class="menu-icon">🔑</span>
                            <span class="menu-text">Encryption Keys</span>
                        </li>
                        <li class="menu-divider"></li>
                        <li class="menu-item">
                            <span class="menu-icon">⚙️</span>
                            <span class="menu-text">Preferences</span>
                        </li>
                        <li class="menu-item">
                            <span class="menu-icon">🌙</span>
                            <span class="menu-text">Dark Mode</span>
                        </li>
                        <li class="menu-divider"></li>
                        <li class="menu-item">
                            <span class="menu-icon">❓</span>
                            <span class="menu-text">Help & Support</span>
                        </li>
                        <li class="menu-item">
                            <span class="menu-icon">📝</span>
                            <span class="menu-text">Send Feedback</span>
                        </li>
                    </ul>
                    <div class="dropdown-footer">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="logout-btn">Sign Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="overlay" id="overlay"></div>
    
    <div class="sidebar">
        <div class="create-btn">
            <span class="create-icon"></span>
            <span class="create-text">New</span>
        </div>
        
        <ul class="nav-list">
            <li class="nav-item active">
                <span class="nav-icon">📄</span>
                <span>My Documents</span>
            </li>
            <li class="nav-item">
                <span class="nav-icon">🔄</span>
                <span>Shared with Me</span>
            </li>
            <li class="nav-item">
                <span class="nav-icon">⭐</span>
                <span>Starred</span>
            </li>
            <li class="nav-item">
                <span class="nav-icon">🔒</span>
                <span>Secure Vault</span>
            </li>
            <li class="nav-item">
                <span class="nav-icon">⏱️</span>
                <span>Recent</span>
            </li>
            <li class="nav-item">
                <span class="nav-icon">🔗</span>
                <span>Blockchain Verified</span>
            </li>
            <li class="nav-item">
                <span class="nav-icon">🗑️</span>
                <span>Trash</span>
            </li>
        </ul>
        
        <div class="storage-info">
            <div class="storage-progress">
                <div class="storage-bar"></div>
            </div>
            <div class="storage-text">3.5 GB of 10 GB used</div>
        </div>
    </div>
    
    <main class="main-content">
        <h1 class="page-title">My Documents</h1>
        
        <div class="filters">
            <button class="filter-button active">All Documents</button>
            <button class="filter-button">PDF Files</button>
            <button class="filter-button">Word Documents</button>
            <button class="filter-button">Images</button>
            <button class="filter-button">Spreadsheets</button>
            
            <div class="view-options">
                <button class="filter-button">
                    <span>📊</span>
                </button>
                <button class="filter-button active">
                    <span>📑</span>
                </button>
            </div>
        </div>
        
        <div class="document-grid">
            <!-- Document Card 1 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">📄</span>
                    <div class="secure-badge">Secured</div>
                </div>
                <div class="document-details">
                    <div class="document-name">Financial Report Q1 2025.pdf</div>
                    <div class="document-meta">Modified: Apr 18, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 2 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">📝</span>
                    <div class="secure-badge">Blockchain</div>
                </div>
                <div class="document-details">
                    <div class="document-name">Contract Agreement.docx</div>
                    <div class="document-meta">Modified: Apr 15, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 3 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">📊</span>
                </div>
                <div class="document-details">
                    <div class="document-name">Sales Analysis.xlsx</div>
                    <div class="document-meta">Modified: Apr 10, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 4 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">🖼️</span>
                </div>
                <div class="document-details">
                    <div class="document-name">Project Mockup.png</div>
                    <div class="document-meta">Modified: Apr 5, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 5 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">📊</span>
                    <div class="secure-badge">AI-Enhanced</div>
                </div>
                <div class="document-details">
                    <div class="document-name">Budget 2025.xlsx</div>
                    <div class="document-meta">Modified: Apr 2, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 6 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">📝</span>
                </div>
                <div class="document-details">
                    <div class="document-name">Meeting Notes.docx</div>
                    <div class="document-meta">Modified: Mar 28, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 7 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">📄</span>
                    <div class="secure-badge">Blockchain</div>
                </div>
                <div class="document-details">
                    <div class="document-name">Legal Document.pdf</div>
                    <div class="document-meta">Modified: Mar 25, 2025</div>
                </div>
            </div>
            
            <!-- Document Card 8 -->
            <div class="document-card">
                <div class="document-preview">
                    <span class="document-icon">🎬</span>
                </div>
                <div class="document-details">
                    <div class="document-name">Presentation.pptx</div>
                    <div class="document-meta">Modified: Mar 20, 2025</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // User Profile Dropdown Functionality
        const userProfileBtn = document.getElementById('userProfileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const overlay = document.getElementById('overlay');

        userProfileBtn.addEventListener('click', function() {
            profileDropdown.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function() {
            profileDropdown.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInside = userProfileBtn.contains(event.target) || 
                                 profileDropdown.contains(event.target);
            
            if (!isClickInside && profileDropdown.classList.contains('active')) {
                profileDropdown.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>