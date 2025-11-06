# Future Optimizations: More Actions Menu Performance

This document provides ready-to-implement code for further performance improvements.

---

## Optimization 1: OTP Status Caching

### Problem
Every time a user opens the "More actions" menu, a new API request is made to fetch OTP status. If a user opens the menu for the same file multiple times, we're making redundant API calls.

### Solution
Cache OTP status in memory for 5-10 minutes.

### Implementation

**File:** `resources/js/modules/file-folder.js`

Add this at the top of the file (after imports):

```javascript
// OTP Status Cache
const otpStatusCache = new Map();
const OTP_CACHE_TTL = 5 * 60 * 1000; // 5 minutes

/**
 * Get OTP status with caching
 * @param {number} fileId - File ID
 * @returns {Promise<Object>} OTP status data
 */
async function getOtpStatusCached(fileId) {
    const cacheKey = `otp_${fileId}`;
    const cached = otpStatusCache.get(cacheKey);
    
    // Return cached data if still valid
    if (cached && Date.now() - cached.timestamp < OTP_CACHE_TTL) {
        console.debug(`[OTP Cache] Hit for file ${fileId}`);
        return cached.data;
    }
    
    console.debug(`[OTP Cache] Miss for file ${fileId}, fetching...`);
    
    try {
        const response = await fetch(`/file-otp/status?file_type=regular&file_id=${fileId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        // Cache the result
        otpStatusCache.set(cacheKey, {
            data,
            timestamp: Date.now()
        });
        
        console.debug(`[OTP Cache] Cached for file ${fileId}`);
        return data;
    } catch (error) {
        console.error(`[OTP Cache] Error fetching OTP status for file ${fileId}:`, error);
        throw error;
    }
}

/**
 * Clear OTP cache for a specific file (call after OTP settings change)
 * @param {number} fileId - File ID
 */
function clearOtpCache(fileId) {
    const cacheKey = `otp_${fileId}`;
    otpStatusCache.delete(cacheKey);
    console.debug(`[OTP Cache] Cleared for file ${fileId}`);
}

/**
 * Clear all OTP cache (call after bulk operations)
 */
function clearAllOtpCache() {
    otpStatusCache.clear();
    console.debug('[OTP Cache] Cleared all');
}
```

Then replace the async OTP loading in `showActionsMenu()`:

```javascript
// OLD CODE (lines 1587-1641):
(async () => {
    try {
        if (!isFolder) {
            const otpStatusResponse = await fetch(`/file-otp/status?file_type=regular&file_id=${itemId}`, {
                // ... fetch config ...
            });
            // ... rest of code ...
        }
    } catch (e) {
        console.debug('Error loading OTP status asynchronously:', e);
    }
})();

// NEW CODE (with caching):
(async () => {
    try {
        if (!isFolder) {
            const otpData = await getOtpStatusCached(itemId);
            
            if (otpData.success && otpData.otp_enabled) {
                isOtpEnabled = true;
                
                // Hide buttons (same as before)
                const shareBtn = menu.querySelector('.actions-menu-item[data-action="share"]');
                if (shareBtn) {
                    shareBtn.parentElement.remove();
                    shareBtn.remove();
                }
                
                const arweaveBtn = menu.querySelector('.actions-menu-item[data-action="upload-to-arweave"]');
                if (arweaveBtn) {
                    arweaveBtn.parentElement.remove();
                    arweaveBtn.remove();
                }
                
                const vectorBtn = menu.querySelector('.actions-menu-item[data-action="add-to-vector"]');
                if (vectorBtn) {
                    vectorBtn.parentElement.remove();
                    vectorBtn.remove();
                }
                
                const removeVectorBtn = menu.querySelector('.actions-menu-item[data-action="remove-from-vector"]');
                if (removeVectorBtn) {
                    removeVectorBtn.parentElement.remove();
                    removeVectorBtn.remove();
                }
            }
        }
    } catch (e) {
        console.debug('Error loading OTP status asynchronously:', e);
    }
})();
```

### Cache Invalidation

Call `clearOtpCache()` when OTP settings are changed:

**In `saveOtpSettings()` function (around line 4120):**

```javascript
if (result.success) {
    showNotification(isEnabled ? 'OTP protection enabled successfully' : 'OTP protection disabled successfully', 'success');
    
    // Clear OTP cache for this file
    clearOtpCache(fileId);
    
    // Close modal and refresh
    const modal = document.getElementById('otpSecurityModal');
    if (modal) modal.remove();
    
    if (window.loadUserFiles) {
        window.loadUserFiles(state.lastMainSearch, state.currentPage, state.currentParentId);
    }
}
```

### Performance Impact

- **API Calls Reduced:** 50-80% (depends on user behavior)
- **Menu Display Time:** <100ms (unchanged, already instant)
- **Cache Hit Time:** <1ms (very fast)
- **Implementation Time:** 30 minutes

### Testing

```javascript
// In browser console:
// Test cache hit
showActionsMenu(button1, 123); // Fetches from API
showActionsMenu(button2, 123); // Uses cache (check console for "Cache Hit")

// Test cache miss after TTL
setTimeout(() => {
    showActionsMenu(button3, 123); // Fetches from API (TTL expired)
}, 6 * 60 * 1000); // 6 minutes

// Test manual cache clear
clearOtpCache(123);
showActionsMenu(button4, 123); // Fetches from API (cache cleared)
```

---

## Optimization 2: Batch OTP Requests

### Problem
If a user has 50 files and opens the menu for each one, we make 50 separate API requests. We could batch these into 1-5 requests.

### Solution
Create a batch endpoint and fetch OTP status for multiple files at once.

### Backend Implementation

**File:** `app/Http/Controllers/FileOtpController.php`

Add this new method:

```php
/**
 * Get OTP status for multiple files (batch request)
 */
public function statusBatch(Request $request): JsonResponse
{
    $fileIds = $request->input('file_ids', []);
    
    if (empty($fileIds) || !is_array($fileIds)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid file_ids parameter'
        ], 400);
    }
    
    // Limit to 50 files per request to prevent abuse
    $fileIds = array_slice($fileIds, 0, 50);
    
    // Get OTP status for all files
    $otpStatuses = FileOtpSecurity::whereIn('file_id', $fileIds)
        ->get()
        ->keyBy('file_id')
        ->map(function ($otp) {
            return [
                'is_otp_enabled' => $otp->is_otp_enabled,
                'require_otp_for_download' => $otp->require_otp_for_download,
                'require_otp_for_preview' => $otp->require_otp_for_preview,
                'require_otp_for_arweave_upload' => $otp->require_otp_for_arweave_upload,
                'require_otp_for_ai_share' => $otp->require_otp_for_ai_share,
            ];
        });
    
    // Add missing files with default (no OTP)
    foreach ($fileIds as $fileId) {
        if (!isset($otpStatuses[$fileId])) {
            $otpStatuses[$fileId] = [
                'is_otp_enabled' => false,
                'require_otp_for_download' => false,
                'require_otp_for_preview' => false,
                'require_otp_for_arweave_upload' => false,
                'require_otp_for_ai_share' => false,
            ];
        }
    }
    
    return response()->json([
        'success' => true,
        'data' => $otpStatuses
    ]);
}
```

**File:** `routes/web.php`

Add this route:

```php
Route::post('/file-otp/status-batch', [FileOtpController::class, 'statusBatch'])->name('file-otp.status-batch');
```

### Frontend Implementation

**File:** `resources/js/modules/file-folder.js`

Add this function:

```javascript
// Batch OTP request queue
const otpBatchQueue = new Map();
const OTP_BATCH_DELAY = 100; // ms - wait for more requests before batching
let otpBatchTimer = null;

/**
 * Get OTP status with batching
 * @param {number} fileId - File ID
 * @returns {Promise<Object>} OTP status data
 */
async function getOtpStatusBatched(fileId) {
    return new Promise((resolve, reject) => {
        // Add to batch queue
        otpBatchQueue.set(fileId, { resolve, reject });
        
        // Clear existing timer
        if (otpBatchTimer) clearTimeout(otpBatchTimer);
        
        // Set new timer to batch requests
        otpBatchTimer = setTimeout(async () => {
            const fileIds = Array.from(otpBatchQueue.keys());
            const callbacks = new Map(otpBatchQueue);
            otpBatchQueue.clear();
            
            console.debug(`[OTP Batch] Fetching ${fileIds.length} files`);
            
            try {
                const response = await fetch('/file-otp/status-batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ file_ids: fileIds })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const result = await response.json();
                
                // Resolve all callbacks with their data
                callbacks.forEach((callback, fId) => {
                    const data = result.data[fId];
                    callback.resolve({
                        success: true,
                        otp_enabled: data.is_otp_enabled,
                        require_otp_for_download: data.require_otp_for_download,
                        require_otp_for_preview: data.require_otp_for_preview,
                        require_otp_for_arweave_upload: data.require_otp_for_arweave_upload,
                        require_otp_for_ai_share: data.require_otp_for_ai_share,
                    });
                });
                
                console.debug(`[OTP Batch] Completed for ${fileIds.length} files`);
            } catch (error) {
                console.error('[OTP Batch] Error:', error);
                callbacks.forEach((callback) => callback.reject(error));
            }
        }, OTP_BATCH_DELAY);
    });
}
```

Then use it in `showActionsMenu()`:

```javascript
(async () => {
    try {
        if (!isFolder) {
            const otpData = await getOtpStatusBatched(itemId);
            
            if (otpData.otp_enabled) {
                // Hide buttons (same as before)
                // ...
            }
        }
    } catch (e) {
        console.debug('Error loading OTP status:', e);
    }
})();
```

### Performance Impact

- **API Calls Reduced:** 80-90% (50 files → 1-5 requests)
- **Menu Display Time:** <100ms (unchanged)
- **Implementation Time:** 2 hours (backend + frontend)
- **Complexity:** Medium

### How It Works

1. User clicks "More actions" on file 1
2. Request added to queue
3. User clicks "More actions" on file 2 (within 100ms)
4. Request added to queue
5. 100ms passes, batch request sent with [file1, file2]
6. Both menus update with OTP status

---

## Optimization 3: Include OTP in File List (BEST)

### Problem
We're making a separate API call for OTP status when we could include it in the initial file list response.

### Solution
Add `is_otp_enabled` to the file list response.

### Backend Implementation

**File:** `app/Http/Controllers/FileController.php`

Find the method that returns the file list (likely `list()` or `get()`):

```php
// OLD CODE:
$files = File::where('user_id', auth()->id())
    ->select('id', 'name', 'is_folder', 'is_vectorized', 'vectorized_at', 'is_blockchain_stored')
    ->get();

// NEW CODE:
$files = File::where('user_id', auth()->id())
    ->with('otpSecurity') // Load OTP relationship
    ->select('id', 'name', 'is_folder', 'is_vectorized', 'vectorized_at', 'is_blockchain_stored')
    ->get()
    ->map(function ($file) {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'is_folder' => $file->is_folder,
            'is_vectorized' => $file->is_vectorized,
            'vectorized_at' => $file->vectorized_at,
            'is_blockchain_stored' => $file->is_blockchain_stored,
            'is_otp_enabled' => $file->otpSecurity?->is_otp_enabled ?? false, // ← NEW
        ];
    });
```

### Frontend Implementation

**File:** `resources/js/modules/file-folder.js`

In `showActionsMenu()`, replace the async OTP loading:

```javascript
// OLD CODE (lines 1587-1641):
(async () => {
    try {
        if (!isFolder) {
            const otpStatusResponse = await fetch(`/file-otp/status?file_type=regular&file_id=${itemId}`, {
                // ... fetch config ...
            });
            // ... process response ...
        }
    } catch (e) {
        console.debug('Error loading OTP status asynchronously:', e);
    }
})();

// NEW CODE (use data from itemData):
// OTP status is already in itemData from file list
if (!isFolder && itemData?.is_otp_enabled) {
    isOtpEnabled = true;
    
    // Hide buttons immediately (no async needed)
    const shareBtn = menu.querySelector('.actions-menu-item[data-action="share"]');
    if (shareBtn) {
        shareBtn.parentElement.remove();
        shareBtn.remove();
    }
    
    const arweaveBtn = menu.querySelector('.actions-menu-item[data-action="upload-to-arweave"]');
    if (arweaveBtn) {
        arweaveBtn.parentElement.remove();
        arweaveBtn.remove();
    }
    
    const vectorBtn = menu.querySelector('.actions-menu-item[data-action="add-to-vector"]');
    if (vectorBtn) {
        vectorBtn.parentElement.remove();
        vectorBtn.remove();
    }
    
    const removeVectorBtn = menu.querySelector('.actions-menu-item[data-action="remove-from-vector"]');
    if (removeVectorBtn) {
        removeVectorBtn.parentElement.remove();
        removeVectorBtn.remove();
    }
}
```

### Performance Impact

- **API Calls Reduced:** 100% (eliminates OTP API call entirely)
- **Menu Display Time:** <100ms (unchanged, but no async needed)
- **File List Size:** +1 boolean per file (negligible)
- **Implementation Time:** 1 hour (backend only)
- **Complexity:** Low

### Comparison

| Aspect | Current | Option 1 (Cache) | Option 2 (Batch) | Option 3 (Include) |
|--------|---------|------------------|------------------|-------------------|
| **API Calls** | 1 per menu | 0.2-0.5 per menu | 0.02-0.1 per menu | 0 per menu |
| **Menu Display** | <100ms | <100ms | <100ms | <100ms |
| **Implementation** | Done | 30 min | 2 hours | 1 hour |
| **Complexity** | Low | Low | Medium | Low |
| **Recommended** | ✅ | ✅ | 🔄 | ⭐ Best |

---

## Recommendation

**Implement in this order:**

1. **Current (Done)** ✅ - Async background loading
   - Menu appears instantly
   - OTP updates in background

2. **Next (Easy)** - Option 1: Caching
   - 30 minutes of work
   - 50-80% fewer API calls
   - No backend changes

3. **Later (Best)** - Option 3: Include in file list
   - 1 hour of work
   - 100% elimination of OTP API calls
   - Requires backend change

---

## Testing All Optimizations

### Test Caching

```javascript
// Open menu for same file twice
showActionsMenu(btn1, 123); // API call
showActionsMenu(btn2, 123); // Cache hit (check console)
```

### Test Batching

```javascript
// Open menu for multiple files quickly
showActionsMenu(btn1, 123);
showActionsMenu(btn2, 124);
showActionsMenu(btn3, 125);
// Should see 1 batch request instead of 3
```

### Test Include in List

```javascript
// Check file list response
fetch('/api/files').then(r => r.json()).then(data => {
    console.log(data[0]); // Should have is_otp_enabled field
});
```

---

## Conclusion

The current fix (async background loading) provides immediate relief from the 2-3 second delay. For further optimization, implement Option 1 (caching) for quick wins, then Option 3 (include in list) for the best long-term solution.
