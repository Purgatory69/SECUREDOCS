// Blockchain Storage Module (Vite module)
// Encapsulated to avoid polluting global scope

// Blockchain module API: init() and open()
export function init() {
  // Guard to avoid double init
  if (window.__blockchainModuleInitialized) return;
  window.__blockchainModuleInitialized = true;

  let currentBlockchainTab = 'files';
  let blockchainFiles = [];
  let blockchainStats = { fileCount: 0, storageUsed: '0 MB', monthlyCost: '—' };

  const qs = (sel) => document.querySelector(sel);
  const qsa = (sel) => Array.from(document.querySelectorAll(sel));
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  async function apiFetch(url, options = {}) {
    const headers = {
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {}),
      ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    };
    const resp = await fetch(url, { ...options, headers });
    if (!resp.ok) {
      throw new Error(`Request failed: ${resp.status}`);
    }
    return resp.json();
  }

  function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let idx = Math.floor(Math.log(bytes) / Math.log(1024));
    idx = Math.min(idx, units.length - 1);
    const val = bytes / Math.pow(1024, idx);
    return `${val.toFixed(val >= 10 ? 0 : 1)} ${units[idx]}`;
  }

  function toDateString(ts) {
    try {
      const d = new Date(ts);
      if (Number.isNaN(d.getTime())) return '';
      return d.toISOString().slice(0, 10);
    } catch { return ''; }
  }

  function initModal() {
    const modal = qs('#blockchainStorageModal');
    const closeBtn = qs('#closeBlockchainModal');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (modal) {
      modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    }
  }

  function openModal() {
    const modal = qs('#blockchainStorageModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    loadStats();
    loadFiles();
  }

  function closeModal() {
    const modal = qs('#blockchainStorageModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }

  function initTabs() {
    qsa('.blockchain-tab-btn').forEach(btn => {
      btn.addEventListener('click', () => switchTab(btn.getAttribute('data-tab')));
    });
  }

  function switchTab(tabName) {
    qsa('.blockchain-tab-btn').forEach(b => {
      b.classList.remove('active', 'border-purple-500', 'text-purple-600');
      b.classList.add('border-transparent', 'text-gray-500');
    });
    const activeBtn = qs(`[data-tab="${tabName}"]`);
    if (activeBtn) {
      activeBtn.classList.add('active', 'border-purple-500', 'text-purple-600');
      activeBtn.classList.remove('border-transparent', 'text-gray-500');
    }
    qsa('.blockchain-tab-content').forEach(c => c.classList.add('hidden'));
    const active = qs(`#${tabName}Tab`);
    if (active) active.classList.remove('hidden');
    currentBlockchainTab = tabName;
    loadTabData(tabName);
  }

  function loadTabData(tab) {
    if (tab === 'files') loadFiles();
    if (tab === 'providers') {/* future settings */}
    if (tab === 'analytics') {/* future analytics */}
  }

  function loadStats() {
    const countEl = qs('#ipfsFileCount');
    const usedEl = qs('#blockchainStorageUsed');
    const costEl = qs('#blockchainMonthlyCost');
    if (countEl) countEl.textContent = '—';
    if (usedEl) usedEl.textContent = '—';
    if (costEl) costEl.textContent = '—';
    apiFetch('/blockchain/stats')
      .then((data) => {
        if (!data?.success) throw new Error(data?.message || 'Failed to load stats');
        const s = data.stats || {};
        blockchainStats = {
          fileCount: s.total_blockchain_files || 0,
          storageUsed: formatBytes(s.total_blockchain_size || 0),
          monthlyCost: '—',
        };
        if (countEl) countEl.textContent = String(blockchainStats.fileCount);
        if (usedEl) usedEl.textContent = blockchainStats.storageUsed;
        if (costEl) costEl.textContent = blockchainStats.monthlyCost;
      })
      .catch((err) => {
        console.error('Stats error', err);
        window.showNotification && window.showNotification('Failed to load blockchain stats', 'error');
      });
  }

  function loadFiles() {
    const filesList = qs('#blockchainFilesList');
    const refreshBtn = qs('#refreshBlockchainFiles');
    if (refreshBtn && !refreshBtn._bound) {
      refreshBtn.addEventListener('click', loadFiles);
      refreshBtn._bound = true;
    }
    if (!filesList) return;
    filesList.innerHTML = `
      <div class="flex items-center justify-center py-6">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div>
        <span class="ml-2 text-gray-600">Loading blockchain files...</span>
      </div>`;
    apiFetch('/blockchain/files')
      .then((data) => {
        if (!data?.success) throw new Error(data?.message || 'Failed to load files');
        const files = Array.isArray(data.files) ? data.files : [];
        blockchainFiles = files.map(f => ({
          id: f.id,
          name: f.file_name || f.name || f.original_filename || 'Untitled',
          size: f.size_human || formatBytes(f.file_size || f.size || 0),
          provider: f.provider || f.blockchain_provider || 'pinata',
          ipfsHash: f.ipfs_hash,
          gatewayUrl: f.gateway_url,
          uploadDate: toDateString(f.upload_timestamp || f.updated_at || f.created_at),
          status: f.status || 'pinned',
          encrypted: !!f.encrypted,
        }));
        console.debug('Loaded blockchain files:', blockchainFiles);
        renderFiles(blockchainFiles);
      })
      .catch((err) => {
        console.error('Files error', err);
        filesList.innerHTML = `<div class="text-center py-8 text-red-600">Failed to load blockchain files</div>`;
      });
  }

  function renderFiles(files) {
    const list = qs('#blockchainFilesList');
    if (!list) return;
    if (!files.length) { list.innerHTML = `<div class="text-center py-8 text-gray-600">No files on blockchain yet</div>`; return; }
    list.innerHTML = files.map(file => `
      <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
            </div>
            <div>
              <h4 class="font-medium text-gray-900">${file.name || 'Untitled'}</h4>
              <div class="flex items-center space-x-2 text-sm text-gray-500">
                <span>${file.size}</span><span>•</span><span class="capitalize">${file.provider}</span><span>•</span><span>${file.uploadDate || ''}</span>
                ${file.encrypted ? '<span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">🔒 Encrypted</span>' : ''}
              </div>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">${file.status.toUpperCase()}</span>
            <div class="relative">
              <button class="p-1 text-gray-400 hover:text-gray-600" data-menu="${file.id}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg>
              </button>
              <div id="blockchainFileMenu-${file.id}" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 hidden">
                <div class="py-1">
                  <button data-action="view" data-hash="${file.ipfsHash}" data-gateway="${file.gatewayUrl || ''}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">View on IPFS</button>
                  <button data-action="copy" data-hash="${file.ipfsHash}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Copy IPFS Hash</button>
                  <button data-action="download" data-id="${file.id}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Download</button>
                  <button data-action="unpin" data-id="${file.id}" class="block px-4 py-2 text-sm text-red-700 hover:bg-red-50 w-full text-left">Unpin from IPFS</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-500"><span class="font-mono bg-gray-100 px-2 py-1 rounded">IPFS: ${file.ipfsHash}</span></div>
      </div>`).join('');

    // bind menus
    qsa('[data-menu]').forEach(btn => btn.addEventListener('click', () => toggleMenu(btn.getAttribute('data-menu'))));
    qsa('#blockchainFilesList [data-action]').forEach(btn => btn.addEventListener('click', handleAction));
  }

  function handleAction(e) {
    const t = e.currentTarget;
    const action = t.getAttribute('data-action');
    if (action === 'view') viewOnIPFS(t.getAttribute('data-hash'), t.getAttribute('data-gateway'));
    if (action === 'copy') copyIPFSHash(t.getAttribute('data-hash'));
    if (action === 'download') downloadFromBlockchain(parseInt(t.getAttribute('data-id')));
    if (action === 'unpin') unpinFromIPFS(parseInt(t.getAttribute('data-id')));
    closeAllMenus();
  }

  function toggleMenu(id) {
    const menu = qs(`#blockchainFileMenu-${id}`);
    if (!menu) return;
    menu.classList.toggle('hidden');
    qsa('[id^="blockchainFileMenu-"]').forEach(m => { if (m.id !== menu.id) m.classList.add('hidden'); });
  }

  function closeAllMenus() { qsa('[id^="blockchainFileMenu-"]').forEach(m => m.classList.add('hidden')); }

  function viewOnIPFS(hash, gateway) {
    const url = gateway && gateway.trim().length ? gateway : (hash ? `https://gateway.pinata.cloud/ipfs/${hash}` : null);
    if (url) window.open(url, '_blank');
  }
  function copyIPFSHash(hash) {
    navigator.clipboard.writeText(hash)
      .then(() => window.showNotification && window.showNotification('IPFS hash copied', 'success'))
      .catch(() => window.showNotification && window.showNotification('Copy failed', 'error'));
  }
  function downloadFromBlockchain(id) {
    const f = blockchainFiles.find(x => x.id === id);
    if (f && window.showNotification) window.showNotification(`Downloading ${f.name}...`, 'info');
  }
  function unpinFromIPFS(id) {
    const f = blockchainFiles.find(x => x.id === id);
    if (!f) return;
    if (!confirm(`Unpin "${f.name}" from IPFS?`)) return;
    apiFetch(`/blockchain/unpin/${id}`, { method: 'DELETE' })
      .then((res) => {
        if (!res?.success) throw new Error(res?.message || 'Unpin failed');
        window.showNotification && window.showNotification('Unpinned from IPFS', 'success');
        loadFiles();
        loadStats();
      })
      .catch((err) => {
        console.error('Unpin error', err);
        window.showNotification && window.showNotification('Failed to unpin file', 'error');
      });
  }

  function initUpload() {
    const dropZone = qs('#blockchainDropZone');
    const fileInput = qs('#blockchainFileInput');
    const uploadForm = qs('#blockchainUploadForm');
    if (dropZone && fileInput) {
      dropZone.addEventListener('click', () => fileInput.click());
      dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-purple-400','bg-purple-50'); });
      dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.classList.remove('border-purple-400','bg-purple-50'); });
      dropZone.addEventListener('drop', (e) => { e.preventDefault(); dropZone.classList.remove('border-purple-400','bg-purple-50'); handleFileSelection(e.dataTransfer.files); });
      fileInput.addEventListener('change', (e) => handleFileSelection(e.target.files));
    }
    if (uploadForm) uploadForm.addEventListener('submit', handleUpload);
    qsa('input[name="provider"]').forEach(r => r.addEventListener('change', updateProviderSelection));
  }

  function handleFileSelection(files) {
    const dz = qs('#blockchainDropZone');
    if (!dz || !files.length) return;
    const names = Array.from(files).map(f => f.name).join(', ');
    dz.innerHTML = `
      <svg class="mx-auto h-12 w-12 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
      <p class="mt-2 text-sm text-green-600"><span class="font-medium">${files.length} file(s) selected</span></p>
      <p class="text-xs text-gray-500">${names}</p>`;
  }

  function updateProviderSelection() {
    const selected = qs('input[name="provider"]:checked');
    qsa('input[name="provider"]').forEach(r => {
      const label = r.parentElement.querySelector('label');
      if (!label) return;
      if (r === selected) { label.classList.add('border-purple-300','bg-purple-50'); label.classList.remove('border-gray-200'); }
      else { label.classList.remove('border-purple-300','bg-purple-50'); label.classList.add('border-gray-200'); }
    });
  }

  async function handleUpload(e) {
    e.preventDefault();
    const fileInput = qs('#blockchainFileInput');
    const provider = qs('input[name="provider"]:checked')?.value || 'pinata';
    if (!fileInput || !fileInput.files.length) { window.showNotification && window.showNotification('Please select files to upload', 'error'); return; }
    const btn = e.target.querySelector('button[type="submit"]');
    if (!btn) return;
    const prev = btn.textContent; btn.disabled = true; btn.innerHTML = `<div class="flex items-center"><div class=\"animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2\"></div>Uploading to ${provider.toUpperCase()}...</div>`;
    try {
      let successCount = 0;
      for (const file of Array.from(fileInput.files)) {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('provider', provider);
        const res = await fetch('/blockchain/upload', {
          method: 'POST',
          headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : undefined,
          body: fd,
        });
        const json = await res.json();
        if (res.ok && json?.success) successCount += 1;
        else throw new Error(json?.message || json?.error || `Upload failed for ${file.name}`);
      }
      window.showNotification && window.showNotification(`Uploaded ${successCount}/${fileInput.files.length} file(s) to ${provider.toUpperCase()}`, 'success');
      fileInput.value = '';
      const dz = qs('#blockchainDropZone');
      if (dz) dz.innerHTML = `
        <svg class=\"mx-auto h-12 w-12 text-purple-400\" stroke=\"currentColor\" fill=\"none\" viewBox=\"0 0 48 48\"><path d=\"M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>
        <p class=\"mt-2 text-sm text-gray-600\"><span class=\"font-medium text-purple-600 hover:text-purple-500\">Click to upload</span> or drag and drop</p>
        <p class=\"text-xs text-gray-500\">PNG, JPG, PDF, DOC up to 100MB</p>`;
      loadFiles();
      loadStats();
      switchTab('files');
    } catch (err) {
      console.error('Upload error', err);
      window.showNotification && window.showNotification(err.message || 'Upload failed', 'error');
    } finally {
      btn.disabled = false; btn.textContent = prev;
    }
  }

  function bindGlobal() {
    document.addEventListener('click', (e) => {
      if (!e.target.closest('[id^="blockchainFileMenu-"]') && !e.target.closest('[data-menu]')) closeAllMenus();
    });
  }

  // Expose refresh hook for external open() to call
  window.__blockchainRefresh = function() {
    try { loadStats(); } catch (_) {}
    try { loadFiles(); } catch (_) {}
  };

  // init
  initModal();
  initTabs();
  initUpload();
  bindGlobal();
}

export function open() {
  const modal = document.querySelector('#blockchainStorageModal');
  if (!modal) return;
  if (!window.__blockchainModuleInitialized) init();
  // Call open after ensuring init
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  // Load latest
  if (typeof window.__blockchainRefresh === 'function') {
    window.__blockchainRefresh();
  }
}

/**
 * Sets up a lazy-loading mechanism for the blockchain module.
 * The full module is dynamically imported only when the user clicks the trigger element.
 */
export function setupBlockchainLazyInit() {
    const blockchainLink = document.getElementById('blockchain-storage-link');
    if (!blockchainLink) return;

    // The event listener is attached only once.
    blockchainLink.addEventListener('click', (event) => {
        event.preventDefault();
        console.log('Opening blockchain storage...');
        
        // The `open` function is already defined in this module.
        // It ensures the module is initialized and opens the modal.
        open();

    }, { once: true });
}

export default { init, open, setupBlockchainLazyInit };
