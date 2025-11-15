# Upload File with AI Vectorization to N8N

## Overview

Your SecureDocs system now supports uploading files with automatic AI vectorization to N8N. This document explains the complete workflow.

---

## System Architecture

### Components

1. **Frontend (Upload Modal)**
   - `resources/js/modules/upload.js` - Upload logic and UI
   - `resources/views/user-dashboard.blade.php` - Upload modal HTML

2. **Backend (Laravel)**
   - `app/Http/Controllers/FileController.php` - Upload endpoints
   - Routes: `/files/upload/standard` and `/files/upload/ai-vectorize`

3. **N8N Integration**
   - Webhook URL: `https://securedocs3.app.n8n.cloud/webhook/f106ab40-0651-4e2c-acc1-6591ab771828`
   - Configured in `.env` at `N8N_WEBHOOK_URL`

4. **Storage**
   - Files stored in Supabase bucket: `docs`
   - File metadata stored in PostgreSQL `files` table

---

## Upload Flow

### Step 1: User Selects File

```
User clicks "Upload File" → Selects file from disk
```

**What happens:**
- File validation: extension, size (<100MB)
- File displayed in upload modal
- Processing options shown (Standard or AI Vectorize)

### Step 2: User Chooses Processing Type

**Option A: Standard Upload**
- File uploaded to Supabase
- File record created in database
- No AI processing

**Option B: AI Vectorize (Premium Only)**
- File uploaded to Supabase
- File record created in database
- File sent to N8N for vectorization
- AI processes file content
- Vectors stored in Supabase `documents` table

### Step 3: Frontend Uploads to Supabase

```javascript
// In upload.js - handleAiVectorizeUploadSingle()
const filePath = await window.uploadFileToSupabase(file, onProgress);
// Returns: "user_18/1762181900602_test.csv"
```

**What happens:**
- File uploaded directly to Supabase bucket
- Returns file path for database storage

### Step 4: Backend Creates File Record

```
POST /files/upload/ai-vectorize
{
  "file_name": "test.csv",
  "file_path": "user_18/1762181900602_test.csv",
  "file_size": 2332,
  "file_type": "file",
  "mime_type": "text/csv",
  "parent_id": null
}
```

**Backend Response:**
```json
{
  "success": true,
  "message": "File uploaded and queued for AI vectorization",
  "file": {
    "id": 249,
    "user_id": 18,
    "file_name": "test.csv",
    "file_path": "user_18/1762181900602_test.csv",
    "file_size": 2332,
    "is_vectorized": false
  }
}
```

### Step 5: Backend Sends to N8N

```php
// In FileController.php - processN8nVectorization()
$payload = [
    'id' => 249,
    'user_id' => 18,
    'file_name' => 'test.csv',
    'file_path' => 'user_18/1762181900602_test.csv',
    'file_size' => 2332,
    'mime_type' => 'text/csv',
    'user_id_for_n8n' => 18,
    'processing_type' => 'vectorization',
    'timestamp' => '2025-11-15T19:11:00Z'
];

Http::timeout(30)->withoutVerifying()->post(
    'https://securedocs3.app.n8n.cloud/webhook/f106ab40-0651-4e2c-acc1-6591ab771828',
    $payload
);
```

### Step 6: N8N Processes File

**N8N Workflow:**
1. Receives file metadata from webhook
2. Fetches file from Supabase: `https://fywmgiuvdbsjfchfzixc.supabase.co/storage/v1/object/public/docs/user_18/1762181900602_test.csv`
3. Extracts text content
4. Generates embeddings (vectors) using AI model
5. Stores vectors in Supabase `documents` table
6. Creates rows in `document_rows` table
7. Creates metadata in `document_metadata` table

### Step 7: Frontend Shows Success

```javascript
// User sees notification
showNotification('All 1 file uploaded successfully!', 'success');

// File list refreshed
window.loadUserFiles('', 1, parentId);
```

---

## Configuration

### Environment Variables

**`.env` file:**
```
N8N_WEBHOOK_URL=https://securedocs3.app.n8n.cloud/webhook/f106ab40-0651-4e2c-acc1-6591ab771828
```

### Laravel Config

**`config/services.php`:**
```php
'n8n' => [
    'premium_webhook_url' => env('N8N_WEBHOOK_URL'),
],
```

---

## Supported File Types

### For Upload
- Documents: pdf, doc, docx, txt, rtf, odt
- Spreadsheets: xls, xlsx, csv, ods
- Presentations: ppt, pptx, odp
- Images: jpg, jpeg, png, gif, bmp, webp, svg
- Videos: mp4, avi, mov, wmv, flv, webm
- Audio: mp3, wav, flac, aac, ogg
- Archives: zip, rar, 7z, tar, gz
- Code: json, xml, html, css, js, md

### For AI Vectorization
- Documents: pdf, doc, docx, txt, rtf, odt
- Spreadsheets: xls, xlsx, csv, ods
- Presentations: ppt, pptx, odp
- Images: jpg, jpeg, png, gif, bmp, webp
- Videos: mp4, avi, mov, wmv, flv, webm
- Audio: mp3, wav, flac, aac, ogg
- Code: json, xml, html, md

---

## Database Schema

### Files Table
```sql
CREATE TABLE files (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(2048),
    file_size BIGINT,
    mime_type VARCHAR(255),
    parent_id BIGINT,
    is_folder BOOLEAN DEFAULT false,
    is_vectorized BOOLEAN DEFAULT false,
    vectorization_metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### Documents Table (N8N Creates)
```sql
CREATE TABLE documents (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    file_id BIGINT,
    content TEXT,
    embedding VECTOR(1536),
    created_at TIMESTAMP
);
```

### Document Rows Table (N8N Creates)
```sql
CREATE TABLE document_rows (
    id BIGINT PRIMARY KEY,
    document_id BIGINT,
    file_id BIGINT,
    user_id BIGINT,
    row_number INTEGER,
    content TEXT,
    embedding VECTOR(1536),
    created_at TIMESTAMP
);
```

### Document Metadata Table (N8N Creates)
```sql
CREATE TABLE document_metadata (
    id BIGINT PRIMARY KEY,
    document_id BIGINT,
    file_id BIGINT,
    user_id BIGINT,
    key VARCHAR(255),
    value TEXT,
    is_deleted BOOLEAN DEFAULT false,
    deleted_at TIMESTAMP,
    created_at TIMESTAMP
);
```

---

## API Endpoints

### Upload Standard File
```
POST /files/upload/standard
Content-Type: application/json

{
  "file_name": "document.pdf",
  "file_path": "user_18/1762181900602_document.pdf",
  "file_size": 102400,
  "file_type": "file",
  "mime_type": "application/pdf",
  "parent_id": null,
  "replace_existing": false
}

Response: 201 Created
{
  "success": true,
  "message": "File uploaded successfully",
  "file": { ... }
}
```

### Upload with AI Vectorization
```
POST /files/upload/ai-vectorize
Content-Type: application/json

{
  "file_name": "document.pdf",
  "file_path": "user_18/1762181900602_document.pdf",
  "file_size": 102400,
  "file_type": "file",
  "mime_type": "application/pdf",
  "parent_id": null,
  "replace_existing": false
}

Response: 201 Created
{
  "success": true,
  "message": "File uploaded and queued for AI vectorization",
  "file": { ... }
}
```

---

## Error Handling

### Common Errors

**1. Premium Required**
```json
{
  "success": false,
  "message": "Premium subscription required for AI vectorization",
  "status": 403
}
```

**2. File Too Large**
```json
{
  "success": false,
  "message": "File size (150.5MB) exceeds 100MB limit",
  "status": 422
}
```

**3. Unsupported File Type**
```json
{
  "success": false,
  "message": "File type '.exe' is not supported",
  "status": 422
}
```

**4. N8N Webhook Failure**
- Logged in `storage/logs/laravel.log`
- File still created in database
- Vectorization will not occur
- User can retry via "Share File to AI" action

---

## Testing

### Manual Test Steps

1. **Login as Premium User**
   - Navigate to `/user/dashboard`
   - Ensure user has `is_premium = true`

2. **Upload File**
   - Click "Upload File" button
   - Select a PDF or CSV file
   - Choose "Process with AI for advanced search capabilities"
   - Click "Upload"

3. **Verify Upload**
   - File appears in file list
   - Check database: `SELECT * FROM files WHERE file_name = 'test.csv';`

4. **Verify N8N Processing**
   - Check N8N logs at: https://securedocs3.app.n8n.cloud
   - Check Supabase `documents` table for vectors
   - Check `document_rows` table for processed rows

5. **Verify Search**
   - Use search functionality to find vectorized content
   - Results should include AI-processed file content

### Debug Commands

**Check file in database:**
```bash
php artisan tinker
>>> $file = App\Models\File::find(249);
>>> $file->is_vectorized;
>>> $file->vectorization_metadata;
```

**Check N8N webhook logs:**
```bash
tail -f storage/logs/laravel.log | grep "N8n"
```

**Check Supabase vectors:**
```sql
SELECT COUNT(*) FROM documents WHERE file_id = 249;
SELECT COUNT(*) FROM document_rows WHERE file_id = 249;
```

---

## Performance Considerations

### File Upload
- Direct to Supabase (no server processing)
- Typical speed: 1-100 MB/s depending on connection
- No server storage needed

### N8N Processing
- Asynchronous (doesn't block user)
- Processing time: 5-60 seconds depending on file size
- User can continue working while N8N processes

### Vector Storage
- Vectors stored in Supabase PostgreSQL with pgvector extension
- Search queries use vector similarity
- Typical search time: <100ms

---

## Troubleshooting

### Issue: "Validation service unavailable"

**Cause:** `requirements` object not defined in validation function

**Solution:** ✅ Fixed in this update - validation now uses hardcoded supported types

### Issue: File uploaded but not vectorized

**Cause:** 
- User is not premium
- N8N webhook URL not configured
- N8N service down
- File type not supported

**Solution:**
1. Check user premium status: `$user->is_premium`
2. Check N8N URL in `.env`: `N8N_WEBHOOK_URL`
3. Check N8N service status
4. Check file type in supported list

### Issue: N8N webhook returns 404

**Cause:** Webhook URL incorrect or N8N workflow deleted

**Solution:**
1. Verify webhook URL in `.env`
2. Check N8N workflow exists at: https://securedocs3.app.n8n.cloud
3. Test webhook manually with curl:
   ```bash
   curl -X POST https://securedocs3.app.n8n.cloud/webhook/f106ab40-0651-4e2c-acc1-6591ab771828 \
     -H "Content-Type: application/json" \
     -d '{"test": "data"}'
   ```

---

## Next Steps

1. **Test the upload flow** with a premium user
2. **Monitor N8N processing** via logs
3. **Verify vectors** are stored in Supabase
4. **Test search functionality** with vectorized content
5. **Monitor performance** and optimize if needed

---

## Related Documentation

- [N8N Integration](./N8N_INTEGRATION.md)
- [AI Categorization](./AI_CATEGORIZATION_LOGGING.md)
- [File Management](./FILE_MANAGEMENT.md)
- [Database Schema](./schema/DATABASE_SCHEMA_FULL.md)
