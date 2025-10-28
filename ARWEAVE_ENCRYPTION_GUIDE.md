# Arweave Encrypted File Upload & Access Guide

## Overview
Your SecureDocs application now supports encrypted file uploads to the Arweave blockchain. This guide explains how to upload encrypted files and how to access them later.

---

## 🔐 How Encryption Works

### Encryption Process
1. **Client-Side Encryption**: Files are encrypted in your browser using AES-256-GCM encryption before uploading
2. **Password-Based Key Derivation**: Uses PBKDF2 with 100,000 iterations to derive encryption keys from your password
3. **Secure Storage**: Only the encrypted file is uploaded to Arweave. The password is never sent to the server
4. **Metadata Storage**: Encryption metadata (salt, IV, password hash) is stored in your database for verification

### Security Features
- ✅ **End-to-End Encryption**: Files are encrypted before leaving your browser
- ✅ **Zero-Knowledge**: Server never sees your password or decryption keys
- ✅ **Strong Encryption**: AES-256-GCM with random salt and IV for each file
- ✅ **Password Protection**: Only users with the correct password can decrypt files

---

## 📤 Uploading Encrypted Files

### Step 1: Access the Upload Interface
Navigate to your dashboard and click on the Arweave upload button (usually in the Blockchain tab or navigation).

### Step 2: Select Your File
Click "Choose File" and select the file you want to upload.

### Step 3: Enable Encryption
1. Toggle the **"Private/Encrypted"** switch to ON
2. A password section will appear

### Step 4: Set a Password
You have two options:

**Option A: Generate a Secure Password (Recommended)**
1. Click the **"Generate Password"** button
2. A strong random password will be created
3. **IMPORTANT**: Copy and save this password securely - you'll need it to access the file later
4. The password will be shown in a modal - copy it before closing

**Option B: Enter Your Own Password**
1. Type a password in the password field (minimum 8 characters)
2. The system will show password strength:
   - 🔴 Weak (less than 3 criteria)
   - 🟡 Medium (3-4 criteria)
   - 🟢 Strong (5+ criteria)
3. **IMPORTANT**: Remember this password - it cannot be recovered

### Step 5: Upload to Arweave
1. Click **"🔐 Upload Encrypted to Arweave"**
2. The system will:
   - Encrypt your file in the browser
   - Upload the encrypted file to Arweave via Bundlr
   - Save the encryption metadata to your database
3. Wait for the upload to complete
4. You'll see a success message with the Arweave URL

### What Happens During Upload
```
Your File → Browser Encryption (AES-256-GCM) → Encrypted File → Arweave Blockchain
                                                      ↓
                                            Encryption Metadata → Your Database
```

---

## 📥 Accessing Encrypted Files

### Method 1: From Your Dashboard

#### Step 1: View Your Files
1. Go to your dashboard
2. Navigate to the Arweave files section
3. You'll see a list of your uploaded files with encryption status indicators:
   - 🔓 Public files (can be accessed directly)
   - 🔐 Encrypted files (require password)

#### Step 2: Click on Encrypted File
1. Click on the encrypted file you want to access
2. A password modal will appear

#### Step 3: Enter Password
1. Type the password you used when uploading the file
2. Click **"🔓 Access File"** or press Enter

#### Step 4: Automatic Download
If the password is correct:
1. The system will download the encrypted file from Arweave
2. Decrypt it in your browser
3. Automatically download the decrypted file to your computer

### Method 2: Direct Access via Code

If you're integrating this into your own code, here's how to access encrypted files:

```javascript
// Initialize the encrypted file access system
const fileAccess = new EncryptedFileAccess();
fileAccess.init();

// Request access to a file
await fileAccess.requestFileAccess(fileId, fileName);
```

---

## 🔑 Password Management Best Practices

### DO:
- ✅ Use the password generator for maximum security
- ✅ Store passwords in a secure password manager
- ✅ Use unique passwords for different files
- ✅ Keep a backup of important passwords
- ✅ Use strong passwords (12+ characters with mixed case, numbers, symbols)

### DON'T:
- ❌ Share passwords via insecure channels (email, SMS)
- ❌ Use simple or common passwords
- ❌ Reuse passwords across multiple files
- ❌ Store passwords in plain text files
- ❌ Forget to save the password before closing the modal

---

## 🚨 Important Notes

### Password Recovery
**⚠️ CRITICAL: Passwords CANNOT be recovered!**
- The system uses zero-knowledge encryption
- If you lose your password, the file is permanently inaccessible
- Even administrators cannot decrypt your files without the password
- Always save your password in a secure location

### File Permanence
- Files uploaded to Arweave are **permanent** and **cannot be deleted**
- You can delete the database record, but the encrypted file remains on Arweave
- Only users with the password can decrypt the file

### Access Tracking
- The system tracks how many times each file is accessed
- Last access time is recorded
- This helps you monitor file usage

---

## 🔧 Technical Details

### Encryption Specifications
- **Algorithm**: AES-256-GCM
- **Key Derivation**: PBKDF2 with SHA-256
- **Iterations**: 100,000
- **Salt Length**: 128 bits (16 bytes)
- **IV Length**: 96 bits (12 bytes)
- **Key Length**: 256 bits

### Database Storage
Encrypted files store the following metadata:
```
- file_name: Original filename
- url: Arweave URL of encrypted file
- is_encrypted: true
- encryption_method: "AES-256-GCM"
- password_hash: SHA-256 hash of password + salt
- salt: Random salt (JSON array)
- iv: Random initialization vector (JSON array)
- access_count: Number of times accessed
- last_accessed_at: Timestamp of last access
```

### Browser Compatibility
Requires browsers with Web Crypto API support:
- ✅ Chrome 37+
- ✅ Firefox 34+
- ✅ Safari 11+
- ✅ Edge 79+

---

## 📊 Monitoring Your Uploads

### View File Statistics
Access your Arweave file statistics to see:
- Total files uploaded
- Number of encrypted vs public files
- Total storage costs (MATIC and USD)
- Total access count
- Average upload cost

### API Endpoint
```
GET /arweave-client/stats
```

---

## 🐛 Troubleshooting

### "Invalid Password" Error
- Double-check your password (case-sensitive)
- Ensure you're using the correct password for this specific file
- Try copying and pasting the password to avoid typos

### Upload Fails
- Ensure you have sufficient Bundlr balance
- Check your internet connection
- Verify the file size is within limits
- Check browser console for detailed error messages

### Can't Access File
- Verify you're logged in to the correct account
- Ensure the file belongs to your account
- Check that the file is marked as encrypted in the database

### Browser Not Supported
- Update your browser to the latest version
- Try a different modern browser
- Ensure JavaScript is enabled

---

## 💡 Example Workflow

### Uploading a Confidential Document
```
1. Select your confidential PDF file
2. Toggle encryption ON
3. Click "Generate Password"
4. Copy password: "X7k#mP9$qR2@vL5n"
5. Save password in your password manager
6. Click "Upload Encrypted to Arweave"
7. Wait for confirmation
8. File is now securely stored on Arweave
```

### Accessing the Document Later
```
1. Go to your Arweave files list
2. Click on the encrypted PDF
3. Enter password: "X7k#mP9$qR2@vL5n"
4. Click "Access File"
5. File automatically downloads and decrypts
6. Open the decrypted PDF
```

---

## 🔗 API Endpoints Reference

### Upload File Record
```
POST /arweave-client/save-upload
Body: {
  arweave_url: string,
  file_name: string,
  is_encrypted: boolean,
  encryption_method: string (if encrypted),
  password_hash: string (if encrypted),
  salt: array (if encrypted),
  iv: array (if encrypted),
  file_size_bytes: number,
  mime_type: string,
  upload_cost_matic: number
}
```

### Verify File Access
```
POST /arweave-client/files/{fileId}/verify-access
Body: {
  password: string
}
Response: {
  success: boolean,
  decryption_data: {
    url: string,
    file_name: string,
    salt: array,
    iv: array,
    password_hash: string
  }
}
```

### Get User Files
```
GET /arweave-client/files
Response: {
  success: boolean,
  files: [
    {
      id: number,
      file_name: string,
      url: string,
      is_encrypted: boolean,
      access_count: number,
      created_at: timestamp
    }
  ]
}
```

---

## 📞 Support

If you encounter issues:
1. Check this guide first
2. Review browser console for error messages
3. Verify your Bundlr wallet has sufficient balance
4. Ensure you're using a supported browser
5. Contact support with error details

---

## ✅ Quick Checklist

Before uploading an encrypted file:
- [ ] File is selected
- [ ] Encryption is enabled
- [ ] Password is set (generated or custom)
- [ ] Password is saved securely
- [ ] Bundlr wallet has sufficient balance
- [ ] Internet connection is stable

After uploading:
- [ ] Upload confirmation received
- [ ] Arweave URL is displayed
- [ ] Password is safely stored
- [ ] Test access to verify encryption works

---

**Remember**: Your password is the ONLY way to decrypt your files. Keep it safe!
