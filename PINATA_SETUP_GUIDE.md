# 📌 Pinata Setup Guide - Step by Step

## What is Pinata?
Pinata is a service that makes it easy to store files on IPFS (InterPlanetary File System) - the decentralized web. When you upload a file to Pinata:
- It gets stored across multiple nodes worldwide
- It receives a unique IPFS hash (like a blockchain fingerprint)
- It becomes permanently accessible via this hash
- It can be accessed from any IPFS gateway globally

## 🔧 Setup Steps

### 1. Create Pinata Account
1. Visit https://pinata.cloud
2. Click "Get Started" and create a free account
3. Verify your email address

### 2. Get API Keys
1. Go to your Pinata dashboard
2. Navigate to "API Keys" section
3. Click "New Key"
4. Give it a name like "SECUREDOCS-Production"
5. Select permissions:
   - ✅ `pinFileToIPFS`
   - ✅ `pinJSONToIPFS` 
   - ✅ `unpin`
   - ✅ `userPinPolicy`
6. Copy the API Key and API Secret (save them securely!)

### 3. Test API Connection
Use this simple test to verify your API keys work:

```bash
curl -X GET "https://api.pinata.cloud/data/testAuthentication" \
  -H "pinata_api_key: YOUR_API_KEY_HERE" \
  -H "pinata_secret_api_key: YOUR_SECRET_HERE"
```

Expected response:
```json
{
  "message": "Congratulations! You are communicating with the Pinata API!"
}
```

## 📝 Next Steps After Setup
Once you have your API keys:
1. Add them to your Laravel .env file
2. Install the Pinata PHP SDK (we'll create a service for this)
3. Test file uploads to IPFS
4. Integrate with your existing file management system

## 💡 Free Tier Limits
Pinata's free tier includes:
- 1GB storage
- 500 files
- 10GB bandwidth per month
- 10K requests per month

Perfect for development and testing!

---
**Ready to get started? Get your API keys and let me know when you have them!** 🚀
