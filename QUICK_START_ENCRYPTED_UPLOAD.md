# 🚀 Quick Start: Encrypted Arweave Upload

## Upload Encrypted File (3 Steps)

### 1️⃣ Select File & Enable Encryption
- Choose your file
- Toggle "Private/Encrypted" to **ON**

### 2️⃣ Set Password
- Click **"Generate Password"** (recommended)
- **COPY AND SAVE THE PASSWORD** - you cannot recover it later!

### 3️⃣ Upload
- Click **"🔐 Upload Encrypted to Arweave"**
- Wait for confirmation
- Done! Your file is encrypted and stored permanently on Arweave

---

## Access Encrypted File (2 Steps)

### 1️⃣ Find Your File
- Go to dashboard → Arweave files
- Click on the 🔐 encrypted file

### 2️⃣ Enter Password
- Type your password
- Click **"🔓 Access File"**
- File automatically downloads and decrypts

---

## ⚠️ CRITICAL WARNING

**YOUR PASSWORD CANNOT BE RECOVERED!**

If you lose your password:
- ❌ The file is permanently inaccessible
- ❌ No one can help you recover it (not even admins)
- ❌ The encrypted file will remain on Arweave forever, but unusable

**Always save your password in a secure password manager!**

---

## 🔒 What Gets Encrypted?

✅ **Encrypted in your browser:**
- File contents
- File metadata

✅ **Stored securely:**
- Encryption keys (derived from your password)
- Salt and IV (for decryption)

❌ **NOT encrypted:**
- Filename (visible in your file list)
- Upload date
- File size

---

## 💰 Cost

Same as regular Arweave uploads:
- ~0.005 MATIC per upload (varies by file size)
- One-time payment for permanent storage
- No recurring fees

---

## 🆚 Public vs Encrypted Upload

| Feature | Public Upload | Encrypted Upload |
|---------|--------------|------------------|
| **Access** | Anyone with URL | Password required |
| **Privacy** | Public | Private |
| **Cost** | ~0.005 MATIC | ~0.005 MATIC |
| **Speed** | Fast | Slightly slower (encryption) |
| **Password** | Not needed | Required |
| **Recovery** | N/A | Impossible without password |

---

## 📱 Quick Commands

### For Non-Encrypted Upload (Test Page):
```javascript
// The system will automatically:
// 1. Upload file to Arweave
// 2. Save record with is_encrypted: false
// 3. No password needed
```

### For Encrypted Upload (Production):
```javascript
// The system will automatically:
// 1. Encrypt file in browser
// 2. Upload encrypted file to Arweave
// 3. Save encryption metadata
// 4. Require password for access
```

---

## 🐛 Common Issues

### "Salt/IV must be an array" Error
**Fixed!** This error should no longer occur. If you see it:
1. Rebuild assets: `npm run build`
2. Clear browser cache
3. Try again

### "Invalid Password" Error
- Check password (case-sensitive)
- Ensure correct file
- No typos (copy-paste recommended)

### Upload Fails
- Check Bundlr balance
- Verify internet connection
- Check browser console for errors

---

## 📞 Need Help?

1. Read full guide: `ARWEAVE_ENCRYPTION_GUIDE.md`
2. Check browser console (F12)
3. Verify Bundlr wallet balance
4. Contact support with error details

---

**Remember: Save your password before closing the modal!** 🔑
