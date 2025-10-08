# 🧹 .env Cleanup Instructions

## **Remove These Lines from .env:**

```bash
# OLD: Server-side Bundlr (not needed anymore)
BUNDLR_PRIVATE_KEY=3aaceca18877702006419f839c53539e32f1ef15a27bf8baccba6f0b083da824
BUNDLR_NETWORK=https://node1.bundlr.network
BUNDLR_WALLET_ADDRESS=0xb3422af424A25ae4bF9a6CC04CdfB28CDCA788DE
CRYPTO_PAYMENT_WALLET=0xdb688F9B2940f13c51Ac8f98d5e4cC692760DDf4
ARWEAVE_PRODUCTION_MODE=true
```

## **Keep These Lines:**
```bash
# Core application settings
APP_NAME=SecureDocs
APP_ENV=local
APP_KEY=base64:ZP73TOTg9osa59mgVHoijC4rf48AkU3L7QsbAXgtK88=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (Supabase)
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-northeast-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.fywmgiuvdbsjfchfzixc
DB_PASSWORD=Star183795

# Supabase
SUPABASE_URL=https://fywmgiuvdbsjfchfzixc.supabase.co
SUPABASE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_BUCKET_PUBLIC=docs
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# All other settings...
```

## **Result:**
- ✅ No server-side wallet needed
- ✅ No private keys in .env  
- ✅ Users control their own funds
- ✅ True decentralization!
