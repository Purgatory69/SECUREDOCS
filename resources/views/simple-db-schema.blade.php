<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Schema - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1f2937;
            line-height: 1.6;
        }

        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
        }

        .controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-top: 0.5rem;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-box input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .zoom-controls {
            display: flex;
            gap: 0.25rem;
            align-items: center;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.25rem;
        }

        .zoom-btn {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
            width: 2rem;
            height: 2rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .zoom-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .zoom-btn:active {
            transform: scale(0.95);
        }

        .zoom-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f9fafb;
        }

        .zoom-btn:disabled:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .zoom-level {
            font-size: 0.75rem;
            color: #6b7280;
            min-width: 3rem;
            text-align: center;
            font-weight: 500;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: background-color 0.2s;
        }

        .zoom-level:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .refresh-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .refresh-btn:hover {
            background: #2563eb;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1rem;
            transition: all 0.2s ease-in-out;
            align-items: start;
            grid-auto-rows: auto;
        }

        .tables-grid.zoomed-out {
            grid-template-columns: repeat(5, 1fr);
        }

        .table-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
            height: auto;
            min-height: fit-content;
            display: flex;
            flex-direction: column;
        }

        .table-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .table-card.hidden {
            display: none;
        }

        .table-header {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem;
        }

        .table-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .table-columns {
            padding: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .column-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.5rem 1rem;
            border-bottom: 1px solid #f3f4f6;
            min-height: auto;
            gap: 0.75rem;
        }

        .column-row:last-child {
            border-bottom: none;
        }

        .column-row:nth-child(even) {
            background: #f9fafb;
        }

        .column-name {
            font-weight: 500;
            color: #374151;
            flex: 1;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.4;
            padding: 0.25rem 0;
        }

        .column-type {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.75rem;
            color: #6b7280;
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
            align-self: flex-start;
            margin-top: 0.25rem;
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Database Schema</h1>
        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search tables or columns...">
            </div>
            <div class="zoom-controls">
                <button class="zoom-btn" id="zoomOutBtn" title="Zoom Out">−</button>
                <span class="zoom-level" id="zoomLevel">100%</span>
                <button class="zoom-btn" id="zoomInBtn" title="Zoom In">+</button>
            </div>
            <button class="refresh-btn" id="refreshBtn">Refresh</button>
        </div>
    </div>

    <div class="container">
        <div id="stats" class="stats" style="display: none;">
            <span id="tableCount">0 tables</span>
            <span id="columnCount">0 columns</span>
        </div>

        <div id="loading" class="loading">
            Loading database schema...
        </div>

        <div id="error" class="error" style="display: none;">
            Failed to load database schema. <button id="retryBtn" style="margin-left: 0.5rem;">Retry</button>
        </div>

        <div id="tablesGrid" class="tables-grid" style="display: none;">
            <!-- Tables will be inserted here -->
        </div>

        <div id="emptyState" class="empty-state" style="display: none;">
            No tables found in the database.
        </div>
    </div>

    <script>
        let schemaData = null;
        let isZoomedOut = false; // false = 3 columns, true = 5 columns

        async function fetchSchema() {
            try {
                const response = await fetch('{{ route('admin.db-schema.json') }}', {
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                return await response.json();
            } catch (error) {
                console.error('Failed to fetch schema:', error);
                throw error;
            }
        }

        function renderTables(data) {
            const tablesGrid = document.getElementById('tablesGrid');
            const stats = document.getElementById('stats');
            const tableCount = document.getElementById('tableCount');
            const columnCount = document.getElementById('columnCount');

            if (!data || !data.tables || data.tables.length === 0) {
                document.getElementById('emptyState').style.display = 'block';
                return;
            }

            // Calculate stats
            const totalTables = data.tables.length;
            const totalColumns = data.tables.reduce((sum, table) =>
                sum + (table.c ? table.c.length : 0), 0);

            tableCount.textContent = `${totalTables} table${totalTables !== 1 ? 's' : ''}`;
            columnCount.textContent = `${totalColumns} column${totalColumns !== 1 ? 's' : ''}`;
            stats.style.display = 'flex';

            // Clear existing content
            tablesGrid.innerHTML = '';

            // Render each table
            data.tables.forEach(table => {
                const tableCard = document.createElement('div');
                tableCard.className = 'table-card';
                tableCard.dataset.tableName = table.t.toLowerCase();

                const header = document.createElement('div');
                header.className = 'table-header';
                header.innerHTML = `<h3 class="table-name">${table.t}</h3>`;

                const columns = document.createElement('div');
                columns.className = 'table-columns';

                if (table.c && table.c.length > 0) {
                    table.c.forEach(column => {
                        const row = document.createElement('div');
                        row.className = 'column-row';
                        row.dataset.columnName = column.n.toLowerCase();

                        row.innerHTML = `
                            <span class="column-name">${column.n}</span>
                            <span class="column-type">${column.t}</span>
                        `;

                        columns.appendChild(row);
                    });
                } else {
                    const emptyRow = document.createElement('div');
                    emptyRow.className = 'column-row';
                    emptyRow.innerHTML = '<span style="color: #9ca3af; font-style: italic;">No columns</span>';
                    columns.appendChild(emptyRow);
                }

                tableCard.appendChild(header);
                tableCard.appendChild(columns);
                tablesGrid.appendChild(tableCard);
            });

            tablesGrid.style.display = 'grid';
        }

        function filterTables(searchTerm) {
            const tableCards = document.querySelectorAll('.table-card');
            const term = searchTerm.toLowerCase().trim();

            if (!term) {
                tableCards.forEach(card => {
                    card.classList.remove('hidden');
                });
                return;
            }

            tableCards.forEach(card => {
                const tableName = card.dataset.tableName;
                const columnRows = card.querySelectorAll('.column-row');
                
                let matches = tableName.includes(term);
                
                if (!matches) {
                    columnRows.forEach(row => {
                        if (row.dataset.columnName && row.dataset.columnName.includes(term)) {
                            matches = true;
                        }
                    });
                }

                card.classList.toggle('hidden', !matches);
            });
        }

        function updateZoom() {
            const tablesGrid = document.getElementById('tablesGrid');
            const zoomLevel = document.getElementById('zoomLevel');
            
            // Toggle grid columns
            if (isZoomedOut) {
                tablesGrid.classList.add('zoomed-out');
                zoomLevel.textContent = '5 cols';
            } else {
                tablesGrid.classList.remove('zoomed-out');
                zoomLevel.textContent = '3 cols';
            }
            
            // Update button states
            document.getElementById('zoomOutBtn').disabled = isZoomedOut;
            document.getElementById('zoomInBtn').disabled = !isZoomedOut;
        }

        function zoomIn() {
            if (isZoomedOut) {
                isZoomedOut = false;
                updateZoom();
            }
        }

        function zoomOut() {
            if (!isZoomedOut) {
                isZoomedOut = true;
                updateZoom();
            }
        }

        function resetZoom() {
            isZoomedOut = false;
            updateZoom();
        }

        async function loadSchema() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const tablesGrid = document.getElementById('tablesGrid');
            const stats = document.getElementById('stats');

            // Show loading state
            loading.style.display = 'block';
            error.style.display = 'none';
            tablesGrid.style.display = 'none';
            stats.style.display = 'none';

            try {
                schemaData = await fetchSchema();
                loading.style.display = 'none';
                renderTables(schemaData);
            } catch (err) {
                loading.style.display = 'none';
                error.style.display = 'block';
                console.error('Schema loading error:', err);
            }
        }

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', (e) => {
            filterTables(e.target.value);
        });

        document.getElementById('refreshBtn').addEventListener('click', loadSchema);
        document.getElementById('retryBtn').addEventListener('click', loadSchema);
        
        // Zoom controls
        document.getElementById('zoomInBtn').addEventListener('click', zoomIn);
        document.getElementById('zoomOutBtn').addEventListener('click', zoomOut);
        document.getElementById('zoomLevel').addEventListener('click', resetZoom);
        
        // Keyboard shortcuts for zoom
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                if (e.key === '=' || e.key === '+') {
                    e.preventDefault();
                    zoomIn(); // Switch to 3 columns
                } else if (e.key === '-') {
                    e.preventDefault();
                    zoomOut(); // Switch to 5 columns
                } else if (e.key === '0') {
                    e.preventDefault();
                    resetZoom(); // Reset to 3 columns
                }
            }
        });

        // Initialize zoom controls
        updateZoom();

        // Load schema on page load
        loadSchema();

        // Auto-refresh every 10 minutes
        setInterval(loadSchema, 10 * 60 * 1000);
    </script>
</body>
</html>
