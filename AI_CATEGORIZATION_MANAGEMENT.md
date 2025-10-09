# AI Categorization Management

## Overview
This document explains how to manage AI categorization status when processes get stuck or need manual completion.

## Problem
AI categorization can sometimes stop mid-process due to various reasons (server issues, timeouts, API limits, etc.). When this happens, users may see a stuck progress bar.

## Solution
We've created tools to manually complete the AI categorization process.

## Available Tools

### 1. Standalone PHP Script
**File:** `complete_ai_categorization.php`

**Usage:**
```bash
# View current status
php complete_ai_categorization.php <user_id> status

# Complete categorization to 100%
php complete_ai_categorization.php <user_id> complete
php complete_ai_categorization.php <user_id>  # default is complete
```

**Example:**
```bash
php complete_ai_categorization.php 18 status
php complete_ai_categorization.php 18 complete
```

### 2. Laravel Artisan Command
**Command:** `ai:complete-categorization`

**Usage:**
```bash
# Complete to 100% (default)
php artisan ai:complete-categorization <user_id>

# Set custom progress percentage
php artisan ai:complete-categorization <user_id> --progress=75
```

**Example:**
```bash
php artisan ai:complete-categorization 18
php artisan ai:complete-categorization 18 --progress=100
```

## How It Works

### Cache Storage
AI categorization status is stored in the `cache` table with the key format:
```
securedocs_cache_ai_categorization_status_{user_id}
```

### Status Structure
```php
[
    'status' => 'completed',        // 'idle', 'in_progress', 'completed', 'failed'
    'progress' => 100,              // 0-100
    'message' => 'Success message', // User-friendly message
    'updated_at' => '2025-10-09T15:30:37+00:00',
    'details' => null
]
```

### Frontend Detection
The frontend JavaScript (`ai-categorization.js`) automatically polls the status every 2-3 seconds and will:
- Hide the loading overlay when status is 'completed'
- Show completion notification
- Refresh the file list
- Remove tamper protection

## Troubleshooting

### Common Issues
1. **Stuck at specific percentage**: Use the tools above to complete it
2. **Frontend not updating**: Check browser console for JavaScript errors
3. **Cache not updating**: Verify database connection in tools

### Checking Current Status
```sql
SELECT * FROM cache WHERE key LIKE '%ai_categorization_status_%';
```

### Manual Cache Update
```sql
UPDATE cache 
SET value = 'a:5:{s:6:"status";s:9:"completed";s:8:"progress";i:100;s:7:"message";s:40:"AI categorization completed successfully";s:10:"updated_at";s:25:"2025-10-09T15:30:37+00:00";s:7:"details";N;}'
WHERE key = 'securedocs_cache_ai_categorization_status_18';
```

## Logging
All status updates are logged to `storage/logs/laravel.log` in the format:
```
[2025-10-09 15:30:37] local.INFO: AI categorization status updated {"user_id":"18","status":"completed","progress":100}
```

## Best Practices
1. Always check current status before making changes
2. Use the provided tools rather than manual SQL updates
3. Monitor logs for any errors
4. Test frontend behavior after completion
5. Set reasonable cache expiration times (1 hour default)
