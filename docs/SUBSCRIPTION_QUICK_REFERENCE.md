# Subscription Management - Quick Reference

## 🚀 Quick Commands

### Run Subscription Check Manually
```bash
php artisan subscriptions:check-expiration
```

### Start Scheduler (Development)
```bash
php artisan schedule:work
```

### Check Subscription Status
```sql
-- View all subscriptions with user info
SELECT 
    s.id,
    u.name,
    u.email,
    s.status,
    s.ends_at,
    EXTRACT(DAY FROM (s.ends_at - NOW())) as days_remaining,
    u.is_premium
FROM subscriptions s
JOIN users u ON s.user_id = u.id
ORDER BY s.ends_at ASC;
```

### Check Recent Notifications
```sql
SELECT 
    n.id,
    u.name,
    n.title,
    n.message,
    n.created_at
FROM notifications n
JOIN users u ON n.user_id = u.id
WHERE n.title LIKE '%Subscription%'
ORDER BY n.created_at DESC
LIMIT 10;
```

## 📅 Notification Schedule

| Days Before Expiry | Notification Type | Title |
|-------------------|------------------|-------|
| 7 days or less | ⚠️ Warning | "Subscription Expiring Soon" |
| Expired | ❌ Error | "Subscription Expired" |

## 🔄 Automated Actions

### Daily at 9:00 AM (Manila Time)
1. ✅ Check for subscriptions expiring within 7 days
2. ✅ Send notifications at any day within the 7-day window (not just specific days)
3. ✅ Process expired subscriptions and reset premium status
4. ✅ Reset `is_premium=false` for expired users
5. ✅ Send expiration notifications
6. ✅ Clean up users with premium but no subscription

## 🛠️ Database Functions

### Call Manually via SQL
```sql
-- Send expiring subscription notifications
SELECT notify_expiring_subscriptions();

-- Process expired subscriptions and reset premium
SELECT handle_expired_subscriptions();
```

## 📊 Current Status (Oct 15, 2025)

### ✅ Active Subscriptions: 3
- fool@gmail.com - Expires Nov 4, 2025
- louiejaybonghanoy69@gmail.com - Expires Nov 8, 2025
- shannenrhey@gmail.com - Expires Nov 8, 2025

### ✅ Cleaned Up: 4 users
- louiejaybonghanoy@gmail.com
- qwertyuiop@gmail.com
- premium@gmail.com
- zxcvbnm@gmail.com

All had `is_premium=true` without active subscriptions and were reset.

## 🔍 Troubleshooting

### PostgreSQL Boolean Error?
**Error**: `operator does not exist: boolean = integer`

**Fix**: Command updated to use `DB::raw('is_premium = true')` instead of `->where('is_premium', true)`

### Scheduler not running?
```bash
# Development
php artisan schedule:work

# Production - Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Check logs
```bash
# Windows
type storage\logs\laravel.log -Tail 50

# View specific date
type storage\logs\laravel-2025-10-15.log
```

### Reset a specific user's premium
```sql
UPDATE users 
SET is_premium = false 
WHERE id = 123;
```

### Manually expire a subscription
```sql
UPDATE subscriptions 
SET status = 'expired' 
WHERE id = 456;
```

## 📝 Files Modified/Created

### Created
- ✅ `app/Console/Commands/CheckSubscriptionExpiration.php`
- ✅ `docs/SUBSCRIPTION_MANAGEMENT.md`
- ✅ `docs/SUBSCRIPTION_QUICK_REFERENCE.md`
- ✅ Supabase migration: `create_subscription_expiration_notifications`

### Modified
- ✅ `routes/console.php` - Added scheduled task

## 🎯 Next Steps

1. **Test the scheduler** (run `php artisan schedule:work`)
2. **Monitor logs** for the next few days
3. **Verify notifications** are being sent to users
4. **Set up cron job** on production server
5. **Consider adding email notifications** (optional)

## 💡 Tips

- Run manual check after making subscription changes
- Check notification table to verify users are being notified
- Monitor `is_premium` status matches active subscriptions
- Use the command's statistics output to track system health
- Keep an eye on logs for any errors

## 🔗 Related Documentation

- Full documentation: `docs/SUBSCRIPTION_MANAGEMENT.md`
- PayMongo webhook: `https://securedocs.live/webhook/paymongo`
- Test keys configured in `.env`
