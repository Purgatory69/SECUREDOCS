# Public File Sharing - Testing Guide

## Overview
This document provides comprehensive testing procedures for the new public file sharing system that mimics MediaFire's functionality.

## Features Implemented

### ✅ Core Features
- **Public Share Links**: Generate shareable URLs for files and folders
- **MediaFire-style Download Page**: Clean, professional download interface
- **OTP Integration**: Cannot share files with OTP protection enabled
- **Password Protection**: Premium feature for secure sharing
- **One-time Links**: Links that expire after first download
- **Expiration Dates**: Links expire after set time periods
- **Save to My Files**: Visitors can copy shared files to their account
- **Folder ZIP Downloads**: Download entire folders as ZIP files (500MB limit)
- **Share Management**: View and manage all created shares

### ✅ Database Schema
- `public_shares` table for managing share links
- `shared_file_copies` table for tracking copied files
- Proper foreign key relationships and indexes

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

---

**Testing completed successfully indicates the public file sharing system is ready for production use.**
