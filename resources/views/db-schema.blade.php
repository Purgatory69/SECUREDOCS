<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Database Schema - {{ config('app.name') }}</title>
  <style>
    :root {
      --bg: #0f172a;          /* slate-900 */
      --panel: #111827;       /* gray-900 */
      --muted: #6b7280;       /* gray-500 */
      --text: #e5e7eb;        /* gray-200 */
      --accent: #22d3ee;      /* cyan-400 */
      --accent-2: #a78bfa;    /* violet-400 */
      --danger: #ef4444;      /* red-500 */
      --ok: #10b981;          /* emerald-500 */
      --line: #334155;        /* slate-700 */
      --highlight: #111827;   /* gray-900 */
    }

    html, body { height: 100%; }
    body {
      margin: 0;
      background: linear-gradient(180deg, #0b1220, #0f172a);
      color: var(--text);
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
      overflow: hidden;
    }

    .toolbar {
      position: fixed;
      inset: 12px 12px auto 12px;
      display: flex;
      gap: 8px;
      align-items: center;
      background: rgba(17, 24, 39, 0.8);
      backdrop-filter: blur(6px);
      border: 1px solid #1f2937;
      border-radius: 10px;
      padding: 10px;
      z-index: 50;
      box-shadow: 0 10px 30px rgba(0,0,0,.35);
    }

    .toolbar input[type="search"] {
      width: 340px;
      background: #0b1020;
      color: var(--text);
      border: 1px solid #1f2937;
      border-radius: 8px;
      padding: 10px 12px;
      outline: none;
    }

    .toolbar button {
      background: #0b1020;
      color: var(--text);
      border: 1px solid #1f2937;
      border-radius: 8px;
      padding: 8px 12px;
      cursor: pointer;
    }
    .toolbar button:hover { border-color: var(--accent); color: var(--accent); }

    .legend { margin-left: 6px; font-size: 12px; color: var(--muted); }
    .badge { padding: 2px 6px; border-radius: 6px; border: 1px solid #334155; margin-right: 6px; }

    .viewport { position: absolute; inset: 0; overflow: hidden; }
    .scene {
      position: absolute; inset: 0;
      transform-origin: 0 0; /* top-left */
      will-change: transform;
    }

    /* SVG lives inside the scene so it scales/translate with it */
    svg#links {
      position: absolute; left: 0; top: 0; width: 8000px; height: 6000px;
      pointer-events: none; /* allow pointer events to pass through */
    }

    .table {
      position: absolute;
      min-width: 260px;
      max-width: 360px;
      background: rgba(17, 24, 39, 0.9);
      border: 1px solid #243041;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,.35), inset 0 0 0 1px rgba(255,255,255,.02);
      overflow: hidden;
      user-select: none;
    }
    .table.dragging { opacity: 0.85; border-color: var(--accent); }

    .table-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 12px; font-weight: 700; letter-spacing: .3px;
      background: linear-gradient(180deg, #0f172a, #0b1020);
      border-bottom: 1px solid #223047;
      cursor: grab;
    }

    .table-columns { padding: 8px 10px 12px 10px; }
    .col { display: flex; justify-content: space-between; gap: 10px; padding: 6px 8px; border-radius: 8px; }
    .col:nth-child(odd) { background: rgba(148,163,184,.05); }
    .ctype { color: var(--muted); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }

    .pk { color: var(--ok); font-weight: 700; font-size: 12px; margin-left: 6px; }
    .fk { color: var(--accent-2); font-weight: 700; font-size: 12px; margin-left: 6px; }

    .dimmed { opacity: 0.25; filter: grayscale(0.3); }

    .notice {
      position: fixed; right: 12px; bottom: 12px; z-index: 60;
      background: rgba(17,24,39,.85); border: 1px solid #243041; border-radius: 10px; padding: 10px 12px; font-size: 13px;
    }
    .notice button { margin-left: 8px; }

    .hint { position: fixed; left: 12px; bottom: 12px; color: var(--muted); font-size: 12px; }
  </style>
</head>
<body>
  <div class="toolbar">
    <input id="search" type="search" placeholder="Search tables or columns (e.g., users, file_id)" />
    <button id="fit">Fit</button>
    <button id="reset">Reset</button>
    <button id="refresh">Refresh</button>
    <span class="legend">
      <span class="badge">PK</span>
      <span class="badge">FK</span>
      <span class="badge">Wheel: Zoom</span>
      <span class="badge">Drag empty space: Pan</span>
      <span class="badge">Drag header: Move table</span>
    </span>
  </div>

  <div class="viewport" id="viewport">
    <div class="scene" id="scene">
      <svg id="links"></svg>
      <!-- tables injected here -->
    </div>
  </div>

  <div class="notice" id="notice" style="display:none">
    Could not load schema data. 
    <button id="retryBtn">Retry</button>
  </div>

  <div class="hint">{{ config('app.name') }} Database Schema - Interactive visualization of all tables and relationships</div>

  <script>
  (function() {
    const viewport = document.getElementById('viewport');
    const scene = document.getElementById('scene');
    const svg = document.getElementById('links');
    const searchInput = document.getElementById('search');
    const fitBtn = document.getElementById('fit');
    const resetBtn = document.getElementById('reset');
    const refreshBtn = document.getElementById('refresh');
    const notice = document.getElementById('notice');
    const retryBtn = document.getElementById('retryBtn');

    // View transform state
    let scale = 0.9, tx = 40, ty = 40; // defaults

    // Data state
    let schema = null;            // { tables: [...] }
    const tables = new Map();     // name -> { el, headerEl, colsEl, x, y, w, h }

    // Helpers
    function applyTransform() {
      scene.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
    }

    function setSceneSize(width, height) {
      // Expand scene and SVG canvas to cover content
      scene.style.width = width + 'px';
      scene.style.height = height + 'px';
      svg.setAttribute('width', Math.max(width, 2000));
      svg.setAttribute('height', Math.max(height, 1500));
    }

    function tableBBox(name) {
      const t = tables.get(name);
      if (!t) return { x:0, y:0, w:0, h:0 };
      const rect = t.el.getBoundingClientRect();
      // We need logical (scene) size, not viewport size; use stored w/h
      return { x: t.x, y: t.y, w: t.w, h: t.h };
    }

    function anchorRight(b) { return { x: b.x + b.w, y: b.y + b.h/2 }; }
    function anchorLeft(b) { return { x: b.x, y: b.y + b.h/2 }; }

    function drawLinks() {
      // Clear
      while (svg.firstChild) svg.removeChild(svg.firstChild);
      if (!schema) return;

      for (const t of schema.tables) {
        if (!t.fk || !t.fk.length) continue;
        for (const fk of t.fk) {
          const src = tableBBox(t.t);
          const dst = tableBBox(fk.tt);
          if (!src.w || !dst.w) continue;
          const p1 = anchorRight(src);
          const p2 = anchorLeft(dst);

          const midX = (p1.x + p2.x) / 2;
          const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
          path.setAttribute('d', `M ${p1.x} ${p1.y} C ${midX} ${p1.y}, ${midX} ${p2.y}, ${p2.x} ${p2.y}`);
          path.setAttribute('fill', 'none');
          path.setAttribute('stroke', 'url(#grad)');
          path.setAttribute('stroke-width', '2');
          path.setAttribute('opacity', '0.8');

          svg.appendChild(path);
        }
      }

      // defs gradient
      const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
      const grad = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
      grad.setAttribute('id', 'grad');
      grad.setAttribute('x1', '0%'); grad.setAttribute('x2', '100%'); grad.setAttribute('y1', '0%'); grad.setAttribute('y2', '0%');
      const s1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop'); s1.setAttribute('offset','0%'); s1.setAttribute('stop-color','#22d3ee');
      const s2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop'); s2.setAttribute('offset','100%'); s2.setAttribute('stop-color','#a78bfa');
      grad.appendChild(s1); grad.appendChild(s2); defs.appendChild(grad); svg.insertBefore(defs, svg.firstChild);
    }

    function createTableBox(t) {
      const el = document.createElement('div');
      el.className = 'table';
      el.dataset.name = t.t;

      const header = document.createElement('div');
      header.className = 'table-header';
      header.innerHTML = `<span>${t.t}</span><span class="muted"></span>`;

      const cols = document.createElement('div');
      cols.className = 'table-columns';

      const pkSet = new Set(t.pk || []);
      const fkCols = new Set((t.fk || []).map(x => x.sc));

      for (const col of (t.c || [])) {
        const row = document.createElement('div');
        row.className = 'col';
        const labels = [];
        if (pkSet.has(col.n)) labels.push('<span class="pk">PK</span>');
        if (fkCols.has(col.n)) labels.push('<span class="fk">FK</span>');
        const safeType = (col.t || '').replace(/</g, '&lt;');
        row.innerHTML = `<span>${col.n}${labels.join('')}</span><span class="ctype">${safeType}</span>`;
        cols.appendChild(row);
      }

      el.appendChild(header);
      el.appendChild(cols);

      // Insert into scene
      scene.appendChild(el);
      const box = { el, headerEl: header, colsEl: cols, x: 0, y: 0, w: 0, h: 0 };
      tables.set(t.t, box);

      // After in DOM, measure
      requestAnimationFrame(() => {
        const rect = el.getBoundingClientRect();
        box.w = Math.ceil(rect.width / scale);
        box.h = Math.ceil(rect.height / scale);
        // Update again after fonts render
        setTimeout(() => {
          const r2 = el.getBoundingClientRect();
          box.w = Math.ceil(r2.width / scale);
          box.h = Math.ceil(r2.height / scale);
          drawLinks();
        }, 50);
      });

      // Dragging
      let dragging = false, dx = 0, dy = 0;
      header.addEventListener('mousedown', (e) => {
        dragging = true;
        el.classList.add('dragging');
        const start = tables.get(t.t);
        dx = (e.clientX - (start.x * scale + tx));
        dy = (e.clientY - (start.y * scale + ty));
        e.preventDefault();
      });
      window.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        const nx = (e.clientX - tx - dx) / scale;
        const ny = (e.clientY - ty - dy) / scale;
        positionTable(t.t, nx, ny);
        drawLinks();
      });
      window.addEventListener('mouseup', () => {
        if (dragging) el.classList.remove('dragging');
        dragging = false;
      });
    }

    function positionTable(name, x, y) {
      const box = tables.get(name);
      if (!box) return;
      box.x = Math.round(x);
      box.y = Math.round(y);
      box.el.style.transform = `translate(${box.x}px, ${box.y}px)`;
    }

    function autoLayout() {
      // Grid layout based on table count
      const list = Array.from(schema.tables);
      const n = list.length;
      const cols = Math.ceil(Math.sqrt(n));
      const cellW = 420, cellH = 260;
      let i = 0, maxW = 0, maxH = 0;
      for (const t of list) {
        const r = Math.floor(i / cols);
        const c = i % cols;
        const x = c * cellW;
        const y = r * cellH;
        positionTable(t.t, x, y);
        const box = tables.get(t.t);
        maxW = Math.max(maxW, x + (box?.w || 320) + 40);
        maxH = Math.max(maxH, y + (box?.h || 200) + 40);
        i++;
      }
      setSceneSize(maxW, maxH);
      drawLinks();
    }

    function fitView(padding = 40) {
      // Fit all tables into viewport
      let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
      for (const [name, box] of tables) {
        minX = Math.min(minX, box.x);
        minY = Math.min(minY, box.y);
        maxX = Math.max(maxX, box.x + box.w);
        maxY = Math.max(maxY, box.y + box.h);
      }
      if (!isFinite(minX)) return;
      const vp = viewport.getBoundingClientRect();
      const w = maxX - minX + padding*2;
      const h = maxY - minY + padding*2;
      const s = Math.min(vp.width / w, vp.height / h, 1.0);
      scale = s;
      tx = Math.round((vp.width - (maxX - minX) * s)/2 - minX * s);
      ty = Math.round((vp.height - (maxY - minY) * s)/2 - minY * s);
      applyTransform();
      drawLinks();
    }

    function resetView() {
      scale = 0.9; tx = 40; ty = 40; applyTransform(); drawLinks();
    }

    function enablePanZoom() {
      // Wheel zoom to cursor
      viewport.addEventListener('wheel', (e) => {
        if (!e.ctrlKey && !e.metaKey) {
          // treat as zoom anyway for simplicity
        }
        e.preventDefault();
        const delta = -Math.sign(e.deltaY) * 0.1;
        const newScale = Math.min(2.2, Math.max(0.3, scale + delta));
        const rect = viewport.getBoundingClientRect();
        const px = e.clientX - rect.left; // pointer in viewport coords
        const py = e.clientY - rect.top;
        // Adjust tx,ty so the zoom centers around pointer
        const sx = (px - tx) / scale;
        const sy = (py - ty) / scale;
        tx = px - sx * newScale;
        ty = py - sy * newScale;
        scale = newScale;
        applyTransform();
      }, { passive: false });

      // Drag to pan when background is dragged
      let panning = false, startX = 0, startY = 0, startTx = 0, startTy = 0;
      viewport.addEventListener('mousedown', (e) => {
        // Ignore if starting on a table
        if (e.target.closest('.table')) return;
        panning = true; startX = e.clientX; startY = e.clientY; startTx = tx; startTy = ty; e.preventDefault();
      });
      window.addEventListener('mousemove', (e) => {
        if (!panning) return;
        tx = startTx + (e.clientX - startX);
        ty = startTy + (e.clientY - startY);
        applyTransform();
      });
      window.addEventListener('mouseup', () => { panning = false; });
    }

    function enableSearch() {
      function doFilter() {
        const q = searchInput.value.trim().toLowerCase();
        if (!q) {
          for (const [, box] of tables) box.el.classList.remove('dimmed');
          return;
        }
        for (const t of schema.tables) {
          const nameMatch = t.t.toLowerCase().includes(q);
          const colMatch = (t.c || []).some(col => col.n.toLowerCase().includes(q));
          const hit = nameMatch || colMatch;
          const box = tables.get(t.t);
          if (box) box.el.classList.toggle('dimmed', !hit);
        }
      }
      searchInput.addEventListener('input', doFilter);
    }

    async function fetchSchema() {
      // Prefer web-session protected admin JSON, then API, then static file
      try {
        const webRes = await fetch('{{ route('admin.db-schema.json') }}', {
          cache: 'no-store',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!webRes.ok) throw new Error('HTTP ' + webRes.status);
        return await webRes.json();
      } catch (eWeb) {
        console.warn('Admin web JSON fetch failed, trying API:', eWeb);
        try {
          const apiRes = await fetch('{{ route('api.db-schema') }}', {
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          if (!apiRes.ok) throw new Error('HTTP ' + apiRes.status);
          return await apiRes.json();
        } catch (eApi) {
          console.warn('API fetch failed, trying static JSON:', eApi);
          const fileRes = await fetch('{{ asset("db-schema.json") }}', { cache: 'no-store' });
          if (!fileRes.ok) throw new Error('HTTP ' + fileRes.status);
          return await fileRes.json();
        }
      }
    }

    function clearTables() {
      tables.clear();
      while (scene.children.length > 1) { // Keep SVG
        scene.removeChild(scene.lastChild);
      }
    }

    function build(data) {
      clearTables();
      schema = data && data.tables ? data : { tables: [] };
      // Create boxes
      for (const t of schema.tables) createTableBox(t);
      autoLayout();
      // Fit after a tick to ensure sizes known
      setTimeout(() => fitView(), 60);
    }

    async function loadSchema() {
      try {
        notice.style.display = 'none';
        const data = await fetchSchema();
        build(data);
      } catch (e) {
        notice.style.display = 'block';
      }
    }

    // Init
    applyTransform();
    enablePanZoom();
    enableSearch();

    // Load schema on page load
    loadSchema();

    // Button handlers
    fitBtn.addEventListener('click', () => fitView());
    resetBtn.addEventListener('click', () => resetView());
    refreshBtn.addEventListener('click', () => loadSchema());
    retryBtn.addEventListener('click', () => loadSchema());

    // Recompute links on window resize (since table sizes might flow differently)
    window.addEventListener('resize', () => { drawLinks(); });
  })();
  </script>
</body>
</html>
