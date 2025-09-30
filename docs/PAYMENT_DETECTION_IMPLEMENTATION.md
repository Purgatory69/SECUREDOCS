# 💰 Payment Detection Implementation Guide

## **Current Situation:**

Your system shows users a payment address and amount, but **CANNOT automatically detect** when they send the payment. This needs to be implemented.

## **🔄 Complete Payment Flow:**

### **What Happens Now:**

```
1. User selects file → System calculates cost ($0.01 USDC)
2. User connects MetaMask → Gets payment details
3. User sends USDC to your address → 0xdb688F9B2940f13c51Ac8f98d5e4cC692760DDf4
4. System polls database every 5 seconds → Checks payment status
5. ❌ STUCK HERE - No automatic detection!
6. Admin manually confirms payment in database
7. System detects "completed" status → Uploads to Arweave
8. User gets permanent URL
```

### **What Should Happen:**

```
1-3. Same as above
4. Blockchain webhook detects incoming transaction
5. ✅ Auto-updates database: status = 'completed'
6. System detects completion → Uploads to Arweave
7. User gets permanent URL
```

---

## **🛠️ Implementation Options:**

### **Option 1: Manual Confirmation (Use This Now)**

**Pros:**
- ✅ Works immediately
- ✅ No additional services needed
- ✅ Complete control

**Cons:**
- ❌ Requires manual work
- ❌ Not scalable
- ❌ Users wait for you to confirm

**How to Use:**

1. **User sends payment** via MetaMask
2. **User copies transaction hash** and sends to you
3. **You verify on PolygonScan:**
   - Go to: https://polygonscan.com/
   - Paste transaction hash
   - Verify: Amount, Token (USDC), Recipient (your address)

4. **You update database:**
```sql
-- Via Supabase or direct SQL
UPDATE crypto_payments 
SET status = 'completed',
    tx_hash = '0x_transaction_hash_from_polygonscan',
    confirmed_at = NOW()
WHERE payment_metadata->>'payment_id' = 'pay_xxx';
```

5. **System auto-detects** and uploads file

---

### **Option 2: Alchemy Webhooks (Recommended)**

**Pros:**
- ✅ Automatic detection
- ✅ Real-time notifications
- ✅ Free tier available
- ✅ Reliable

**Setup Steps:**

#### **1. Create Alchemy Account:**
- Go to: https://www.alchemy.com/
- Sign up (free)
- Create app on Polygon network

#### **2. Create Webhook:**
```javascript
// In Alchemy dashboard:
Webhook Type: Address Activity
Address: 0xdb688F9B2940f13c51Ac8f98d5e4cC692760DDf4
Network: Polygon Mainnet
Webhook URL: https://yourdomain.com/webhook/payment-confirmed
```

#### **3. Add Route in Laravel:**

```php
// routes/web.php
Route::post('/webhook/payment-confirmed', [PermanentStorageController::class, 'handlePaymentWebhook'])
    ->name('webhook.payment-confirmed');
```

#### **4. Implement Handler:**

```php
// app/Http/Controllers/PermanentStorageController.php

public function handlePaymentWebhook(Request $request): JsonResponse
{
    try {
        Log::info('Payment webhook received', $request->all());
        
        // Verify webhook signature (Alchemy provides this)
        // ... signature verification code ...
        
        $event = $request->input('event');
        $activity = $event['activity'][0] ?? null;
        
        if (!$activity) {
            return response()->json(['success' => false], 400);
        }
        
        $toAddress = strtolower($activity['toAddress']);
        $value = $activity['value']; // in wei
        $txHash = $activity['hash'];
        $token = $activity['asset']; // USDC
        
        // Convert value to USDC (6 decimals)
        $amountUSDC = $value / 1000000;
        
        // Find matching pending payment
        $payment = CryptoPayment::where('status', 'pending')
            ->where('wallet_address', $toAddress)
            ->where('amount_crypto', '>=', $amountUSDC * 0.99) // 1% tolerance
            ->where('amount_crypto', '<=', $amountUSDC * 1.01)
            ->where('expires_at', '>', now())
            ->first();
        
        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'tx_hash' => $txHash,
                'actual_amount_received' => $amountUSDC,
                'confirmed_at' => now()
            ]);
            
            Log::info('Payment confirmed automatically', [
                'payment_id' => $payment->id,
                'tx_hash' => $txHash,
                'amount' => $amountUSDC
            ]);
            
            return response()->json(['success' => true]);
        }
        
        Log::warning('No matching payment found for webhook', [
            'to_address' => $toAddress,
            'amount' => $amountUSDC,
            'tx_hash' => $txHash
        ]);
        
        return response()->json(['success' => false, 'message' => 'No matching payment'], 404);
        
    } catch (\Exception $e) {
        Log::error('Payment webhook failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json(['success' => false], 500);
    }
}
```

---

### **Option 3: Blockchain Polling (Advanced)**

**Pros:**
- ✅ No external dependencies
- ✅ Complete control

**Cons:**
- ❌ More complex to implement
- ❌ Requires running scheduled job
- ❌ API rate limits

**Implementation:**

```php
// app/Console/Commands/CheckPendingPayments.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CryptoPayment;
use Illuminate\Support\Facades\Http;

class CheckPendingPayments extends Command
{
    protected $signature = 'payments:check';
    protected $description = 'Check blockchain for pending payments';

    public function handle()
    {
        $pendingPayments = CryptoPayment::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();
        
        foreach ($pendingPayments as $payment) {
            $this->checkPayment($payment);
        }
    }
    
    protected function checkPayment(CryptoPayment $payment)
    {
        // Use PolygonScan API
        $apiKey = env('POLYGONSCAN_API_KEY');
        $address = $payment->wallet_address;
        
        $response = Http::get("https://api.polygonscan.com/api", [
            'module' => 'account',
            'action' => 'tokentx',
            'address' => $address,
            'startblock' => 0,
            'endblock' => 99999999,
            'sort' => 'desc',
            'apikey' => $apiKey
        ]);
        
        $transactions = $response->json()['result'] ?? [];
        
        foreach ($transactions as $tx) {
            // Check if transaction matches payment
            $txAmount = $tx['value'] / 1000000; // USDC has 6 decimals
            $txTo = strtolower($tx['to']);
            $expectedTo = strtolower(config('crypto.payment_wallet_address'));
            
            if ($txTo === $expectedTo && 
                abs($txAmount - $payment->amount_crypto) < 0.01) {
                
                $payment->update([
                    'status' => 'completed',
                    'tx_hash' => $tx['hash'],
                    'actual_amount_received' => $txAmount,
                    'confirmed_at' => now()
                ]);
                
                $this->info("Payment confirmed: {$payment->id}");
                break;
            }
        }
    }
}
```

**Schedule it:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('payments:check')
             ->everyMinute();
}
```

---

## **🎮 Ronin Wallet Support:**

### **The Problem:**

Ronin network is **NOT supported by Bundlr**. Bundlr only supports:
- ✅ Ethereum
- ✅ Polygon
- ✅ Arbitrum
- ✅ Avalanche
- ❌ Ronin (not supported)

### **Solutions for Ronin Users:**

#### **Option A: Bridge to Polygon (Recommended)**

1. **Use Ronin Bridge:**
   - Go to: https://bridge.roninchain.com/
   - Bridge your tokens from Ronin → Ethereum
   - Then bridge Ethereum → Polygon

2. **Or use a DEX:**
   - Swap Ronin tokens → ETH
   - Bridge ETH → Polygon
   - Swap to USDC on Polygon

#### **Option B: Accept Ronin Payments Separately**

1. **Add Ronin as payment option**
2. **User sends RON/AXS to your Ronin address**
3. **You manually convert to USDC**
4. **You confirm payment in database**
5. **System uploads to Arweave**

**Implementation:**

```php
// Add to createPayment() in controller:
$networkConfig = match($request->wallet_type) {
    'metamask' => ['network' => 'Polygon', 'token' => 'USDC', 'chain_id' => 137],
    'ronin' => [
        'network' => 'Ronin', 
        'token' => 'RON', 
        'chain_id' => 2020,
        'note' => 'Manual conversion required - not supported by Bundlr'
    ],
    // ...
};
```

---

## **📊 Recommended Setup:**

### **For Testing (Now):**
```bash
ARWEAVE_PRODUCTION_MODE=false  # Demo mode
```
- Use **Manual Confirmation** (Option 1)
- Test with MetaMask on Polygon
- No real money spent

### **For Production (Later):**
```bash
ARWEAVE_PRODUCTION_MODE=true
```
- Use **Alchemy Webhooks** (Option 2)
- Support MetaMask on Polygon
- Ronin users must bridge to Polygon first

---

## **🚀 Quick Start Guide:**

### **Today (Manual Testing):**

1. **Set demo mode:**
```bash
ARWEAVE_PRODUCTION_MODE=false
```

2. **User uploads file**
3. **System shows payment details**
4. **User sends payment** (or skip in demo)
5. **You manually confirm** in database
6. **System uploads** to Arweave

### **This Week (Automatic Detection):**

1. **Sign up for Alchemy** (free)
2. **Create webhook** pointing to your server
3. **Implement webhook handler** (code above)
4. **Test with real payment**
5. **Go live!**

### **For Ronin Users:**

1. **Add note in UI:** "Ronin users: Please bridge to Polygon first"
2. **Provide bridge link:** https://bridge.roninchain.com/
3. **Or:** Accept Ronin payments manually (you convert)

---

## **💡 Summary:**

**Payment Detection:**
- ❌ Not automatic yet
- ✅ Manual confirmation works now
- ✅ Alchemy webhooks = best solution
- ✅ Implement this week

**Ronin Wallet:**
- ❌ Not supported by Bundlr
- ✅ Users can bridge to Polygon
- ✅ Or you accept manually

**Current Status:**
- ✅ System ready for manual testing
- ⏳ Need automatic payment detection
- ⏳ Ronin requires workaround

**Next Steps:**
1. Test with MetaMask + manual confirmation
2. Implement Alchemy webhooks
3. Add Ronin bridge instructions for users
