# Public File Sharing System - Testing Guide

This document provides comprehensive testing procedures for the SecureDocs public file sharing system, which implements MediaFire-style functionality with URL-based navigation.

## Overview

The public sharing system allows users to create shareable links for files and folders that can be accessed by anyone with the link, even without an account. The system now supports both traditional sharing and direct UUID-based sharing.

### Key Features
- **Individual share links** for files and folders using UUID tokens
- **URL-based navigation** for both dashboard and public shares
- **MediaFire-style redirect behavior** for logged-in file owners
- **Password protection** (Premium feature)
- **One-time download links**
- **Expiration dates** (1 day to 1 year)
- **Save to My Files** functionality for visitors
- **Folder ZIP downloads**
- **MediaFire-style download interface**
- **Nested folder navigation** with breadcrumbs

### ✅ Database Schema
- `files` table now includes `uuid`, `share_token` (UUID), `url_slug`, and `full_path` columns
- `public_shares` table for managing advanced share links (legacy system)
- `shared_file_copies` table for tracking copied files
- Proper foreign key relationships and indexes

### ✅ URL Structure
```
Dashboard Navigation:
/user/dashboard                    (root folder)
/user/dashboard/folder/123         (specific folder)
/user/dashboard/file/456           (specific file)

Public Shares:
/s/[uuid-token]                    (direct file/folder share)
/s/[legacy-token]                  (legacy PublicShare system)
/s/[token]/folder/123              (nested folder navigation)
/s/[token]/file/456                (individual file access)
```

### ✅ MediaFire-Style Behavior
- **File owners**: When logged in and accessing their own share links, automatically redirected to dashboard
- **Other users**: See public share interface with download/preview options
- **Individual tokens**: Every file/folder gets a unique UUID share token on creation
- **Persistent links**: Share tokens never change once created

## Testing Procedures

### 1. Basic Share Creation

#### Test 1.1: Create File Share (Free User)
1. **Login** as a free user
2. **Right-click** on any file → Select **"Share"**
3. **Verify** share modal opens with:
   - File name and icon displayed
   - One-time download checkbox
   - Expiration dropdown (default: 1 week)
   - Password protection checkbox with "Premium" badge
4. **Click** "Create Share Link"
5. **Verify** success modal shows with:
   - Generated share URL
   - Copy button functionality
   - Open link button

**Expected Result**: ✅ Share link created successfully

#### Test 1.2: Create Folder Share
1. **Right-click** on any folder → Select **"Share"**
2. **Create** share link with default settings
3. **Verify** success modal shows folder type

**Expected Result**: ✅ Folder share created successfully

#### Test 1.3: Password Protection (Premium Only)
1. **As free user**, check "Password protection"
2. **Click** "Create Share Link"
3. **Verify** error message: "Password protection requires Premium"

**Expected Result**: ✅ Premium restriction enforced

### 2. OTP Integration Testing

#### Test 2.1: OTP-Protected File Sharing
1. **Enable OTP** on a file (via context menu → OTP Security)
2. **Try to share** the same file
3. **Verify** error message: "Cannot share files with OTP protection"

**Expected Result**: ✅ OTP-protected files cannot be shared

#### Test 2.2: Share After Disabling OTP
1. **Disable OTP** on a previously protected file
2. **Share** the file
3. **Verify** share creation succeeds

**Expected Result**: ✅ Share works after OTP removal

### 3. Public Access Testing

#### Test 3.1: Anonymous File Access
1. **Copy** a file share URL
2. **Open** in incognito/private browser window
3. **Verify** MediaFire-style page shows:
   - File name, size, type, upload date
   - Large blue "DOWNLOAD FILE" button
   - "Preview File" button (for supported types)
   - "Save to My Files" button
   - File details and "Can be opened with" section

**Expected Result**: ✅ Professional download page displayed

#### Test 3.2: Anonymous Folder Access
1. **Copy** a folder share URL
2. **Open** in incognito window
3. **Verify** page shows:
   - "DOWNLOAD FOLDER (ZIP)" button
   - No preview option for folders
   - Folder details

**Expected Result**: ✅ Folder download page displayed

#### Test 3.3: File Download
1. **Click** "DOWNLOAD FILE" button
2. **Verify** file downloads successfully
3. **Check** download count increments

**Expected Result**: ✅ File downloads correctly

#### Test 3.4: Folder ZIP Download
1. **Click** "DOWNLOAD FOLDER (ZIP)" button
2. **Verify** ZIP file downloads with folder name
3. **Extract** ZIP and verify contents

**Expected Result**: ✅ Folder downloads as ZIP

### 4. Password Protection Testing (Premium)

#### Test 4.1: Create Password-Protected Share
1. **Login** as premium user
2. **Create** share with password protection
3. **Set** password: "test123"
4. **Verify** share created successfully

**Expected Result**: ✅ Password-protected share created

#### Test 4.2: Access Password-Protected Share
1. **Open** password-protected share URL
2. **Verify** password prompt page shows:
   - File name and owner
   - Password input field
   - "Access File" button
3. **Enter** wrong password → Verify error
4. **Enter** correct password → Verify access granted

**Expected Result**: ✅ Password protection works correctly

### 5. One-Time Links Testing

#### Test 5.1: Create One-Time Link
1. **Create** share with "One-time download link" checked
2. **Access** link and download file
3. **Try** to access link again
4. **Verify** "Link Already Used" error page

**Expected Result**: ✅ One-time restriction enforced

### 6. Expiration Testing

#### Test 6.1: Create Expiring Link
1. **Create** share with 1-day expiration
2. **Verify** share shows expiration date
3. **Manually** set expires_at to past date in database
4. **Access** link → Verify "Share Link Expired" page

**Expected Result**: ✅ Expiration works correctly

### 7. Save to My Files Testing

#### Test 7.1: Save as Anonymous User
1. **Access** share as anonymous user
2. **Click** "Save to My Files"
3. **Verify** login prompt appears

**Expected Result**: ✅ Login required for saving

#### Test 7.2: Save as Logged-in User
1. **Login** and access share
2. **Click** "Save to My Files"
3. **Verify** success message
4. **Check** dashboard → File appears in root directory
5. **Try** to save same file again → Verify "already saved" message

**Expected Result**: ✅ File copied to user account

### 8. Share Management Testing

#### Test 8.1: View My Shares
1. **Create** several shares
2. **Access** `/share/my-shares` endpoint
3. **Verify** JSON response contains:
   - Share tokens and URLs
   - File names and types
   - Download counts
   - Expiration dates
   - Status (active/expired/used)

**Expected Result**: ✅ Share list retrieved correctly

#### Test 8.2: Delete Share
1. **Delete** a share via API: `DELETE /share/{shareId}`
2. **Try** to access deleted share URL
3. **Verify** "Share Not Found" page

**Expected Result**: ✅ Share deletion works

### 9. Error Handling Testing

#### Test 9.1: Invalid Share Token
1. **Access** URL with invalid token: `/s/invalid-token`
2. **Verify** "Share Not Found" page

**Expected Result**: ✅ Proper error page displayed

#### Test 9.2: Large Folder Download
1. **Create** folder with files totaling >500MB
2. **Try** to download as ZIP
3. **Verify** error: "Folder too large for download"

**Expected Result**: ✅ Size limit enforced

### 10. Security Testing

#### Test 10.1: Share Token Uniqueness
1. **Create** multiple shares
2. **Verify** all tokens are unique (32 characters)
3. **Check** database for token collisions

**Expected Result**: ✅ All tokens unique

#### Test 10.2: Access Control
1. **Try** to create share for file you don't own
2. **Verify** 404 error or access denied

**Expected Result**: ✅ Ownership verified

### 11. UI/UX Testing

#### Test 11.1: Mobile Responsiveness
1. **Access** public share pages on mobile device
2. **Verify** responsive design works correctly
3. **Test** all buttons and interactions

**Expected Result**: ✅ Mobile-friendly interface

#### Test 11.2: Browser Compatibility
1. **Test** on Chrome, Firefox, Safari, Edge
2. **Verify** all features work consistently
3. **Check** clipboard API fallback

**Expected Result**: ✅ Cross-browser compatibility

## Database Verification Queries

### Check Share Creation
```sql
SELECT * FROM public_shares 
WHERE user_id = [USER_ID] 
ORDER BY created_at DESC;
```

### Check File Copies
```sql
SELECT sfc.*, ps.share_token, f.file_name
FROM shared_file_copies sfc
JOIN public_shares ps ON sfc.original_share_id = ps.id
JOIN files f ON sfc.copied_file_id = f.id
WHERE sfc.copied_by_user_id = [USER_ID];
```

### Check Share Statistics
```sql
SELECT 
    share_type,
    COUNT(*) as total_shares,
    SUM(download_count) as total_downloads,
    COUNT(CASE WHEN expires_at < NOW() THEN 1 END) as expired_shares
FROM public_shares 
GROUP BY share_type;
```

## Performance Testing

### Load Testing
1. **Create** 100+ shares rapidly
2. **Access** multiple shares simultaneously
3. **Monitor** database performance
4. **Check** for memory leaks in ZIP generation

### File Size Testing
1. **Test** ZIP creation with various folder sizes:
   - Small folders (< 10MB)
   - Medium folders (50-200MB)
   - Large folders (400-500MB)
2. **Monitor** server memory usage
3. **Verify** timeout handling

## Common Issues & Troubleshooting

### Issue 1: Share Modal Not Opening
- **Check**: JavaScript console for errors
- **Verify**: `showShareModal` function is loaded
- **Solution**: Refresh page and try again

### Issue 2: ZIP Download Fails
- **Check**: Folder size under 500MB limit
- **Verify**: All files accessible in Supabase storage
- **Check**: Server disk space for temp files

### Issue 3: Password Protection Not Working
- **Verify**: User has premium status
- **Check**: Session storage for password verification
- **Test**: Password hash comparison

### Issue 4: One-Time Links Not Expiring
- **Check**: Download count in database
- **Verify**: Link validation logic
- **Test**: Multiple access attempts

## Test Data Setup

### Sample Users
- **Free User**: test-free@example.com
- **Premium User**: test-premium@example.com

### Sample Files
- **Small File**: test.txt (< 1MB)
- **Medium File**: document.pdf (5-10MB)
- **Large File**: video.mp4 (50-100MB)
- **Test Folder**: Contains mix of file types and sizes

### Sample Shares
- **Basic Share**: No expiration, no password
- **One-Time Share**: Single download only
- **Password Share**: Premium user with password
- **Expiring Share**: 1-day expiration

## Automated Testing

### API Tests
```bash
# Create share
curl -X POST /share/create \
  -H "Content-Type: application/json" \
  -d '{"file_id": 1, "is_one_time": false}'

# Access share
curl -X GET /s/[SHARE_TOKEN]

# Save to my files
curl -X POST /share/[SHARE_TOKEN]/save-to-my-files
```

### Browser Tests
Use Selenium or Playwright for automated UI testing:
- Share creation workflow
- Public access scenarios
- Error handling paths

## Success Criteria

### ✅ All Tests Pass
- Share creation works for files and folders
- Public access displays MediaFire-style interface
- OTP integration prevents sharing protected files
- Password protection works for premium users
- One-time links expire after use
- Expiration dates are enforced
- Save to My Files copies files correctly
- ZIP downloads work within size limits
- Error pages display appropriately
- Mobile interface is responsive

### ✅ Performance Metrics
- Share creation: < 2 seconds
- Public page load: < 3 seconds
- ZIP generation: < 30 seconds for 500MB
- Database queries: < 100ms average

### ✅ Security Verified
- Share tokens are cryptographically secure
- Access control prevents unauthorized sharing
- Password protection uses proper hashing
- No sensitive data exposed in public pages

## Deployment Checklist

### Pre-Deployment
- [ ] All tests pass
- [ ] Database migration applied
- [ ] Environment variables configured
- [ ] SSL certificate valid for domain

### Post-Deployment
- [ ] Test public share URLs work
- [ ] Verify domain routing (securedocs.live/s/*)
- [ ] Check CloudFlare settings
- [ ] Monitor error logs
- [ ] Test from different geographic locations

## Monitoring & Analytics

### Key Metrics
- Total shares created
- Download success rate
- Average file size shared
- Popular file types
- Geographic distribution of access

### Alerts
- High error rates on public pages
- ZIP generation failures
- Database connection issues
- Storage space warnings

## 8. URL-Based Navigation Testing

### Test 8.1: Dashboard Folder Navigation
1. **Login** to dashboard
2. **Navigate** to a folder by clicking it
3. **Verify** URL changes to `/user/dashboard/folder/123`
4. **Check** breadcrumbs update correctly
5. **Test** browser back/forward buttons work

**Expected Result**: ✅ URL-based navigation works seamlessly

### Test 8.2: Direct Folder URL Access
1. **Copy** folder URL from address bar: `/user/dashboard/folder/123`
2. **Open** in new tab while logged in
3. **Verify** folder opens directly
4. **Check** breadcrumbs show correct path

**Expected Result**: ✅ Direct folder URLs work

### Test 8.3: MediaFire-Style Owner Redirect
1. **Create** a file share and copy the UUID link: `/s/abc123-uuid`
2. **While logged in as owner**, open the share link
3. **Verify** automatically redirected to dashboard with file/folder open
4. **Test** with both files and folders

**Expected Result**: ✅ File owners get redirected to dashboard

### Test 8.4: UUID Share Token Generation
1. **Create** a new file/folder
2. **Verify** `share_token` (UUID) is auto-generated
3. **Check** share URL uses format: `/s/[uuid]`
4. **Confirm** token never changes once created

**Expected Result**: ✅ UUID tokens generated automatically

### Test 8.5: Nested Folder Navigation in Shares
1. **Share** a folder with nested subfolders
2. **Open** share link as anonymous user
3. **Navigate** into subfolders
4. **Verify** breadcrumbs show full path
5. **Test** breadcrumb clicks work for navigation

**Expected Result**: ✅ Nested navigation works with breadcrumbs

## 9. Implementation Summary

### ✅ Completed Features
- **Dual UUID System**: Every file/folder gets both `uuid` and `share_token` on creation
- **URL-Based Navigation**: Dashboard supports `/folder/123` and `/file/456` URLs
- **MediaFire-Style Redirects**: File owners redirected to dashboard when accessing own shares
- **Flexible Share System**: Supports UUID shares, share_token shares, and legacy PublicShare system
- **Database Migration**: Added `uuid`, `share_token`, `url_slug`, `full_path` columns to files table
- **Auto-Generation**: UUIDs, share tokens and URL slugs created automatically on file creation
- **Existing File Support**: Command to populate UUIDs for existing files

### 🔧 Technical Implementation
- **File Model**: Enhanced with share token methods and auto-generation
- **FileController**: Added `showFolder()` and `showFile()` methods for URL navigation
- **PublicShareController**: Updated to handle UUID shares and owner redirects
- **Routes**: Added dashboard navigation routes with integer ID constraints
- **Migration**: `2025_10_25_000001_add_url_navigation_to_files.php`

### 📋 Migration Instructions
1. **Run Migration**: `php artisan migrate` to add new columns
2. **Populate Existing Files**: `php artisan files:populate-uuids` to add UUIDs to existing files
3. **Force Update**: `php artisan files:populate-uuids --force` to regenerate all UUIDs
4. **No Data Loss**: Existing PublicShare system continues to work
5. **Auto-Generation**: New files automatically get UUIDs and share tokens

---

**Testing completed successfully indicates the public file sharing system with URL-based navigation is ready for production use.**
