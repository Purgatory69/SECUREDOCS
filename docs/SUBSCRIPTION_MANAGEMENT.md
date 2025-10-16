# Subscription Management System

## Overview
Automated subscription expiration management system that monitors subscriptions, sends notifications to users, and automatically resets premium status for expired subscriptions.

## Features

### 1. **Automatic Expiration Notifications**
Users receive notifications at critical intervals before their subscription expires:
- **7 days before expiration**: Warning notification
- **3 days before expiration**: Warning notification  
- **1 day before expiration**: Urgent warning notification

### 2. **Automatic Premium Status Reset**
When a subscription expires or a user has `is_premium=true` without an active subscription:
- User's `is_premium` status is automatically set to `false`
- Subscription status is updated to `expired`
- User receives a notification about the expiration

### 3. **Daily Automated Checks**
The system runs daily at **9:00 AM Manila Time** to:
- Check for subscriptions expiring within 7 days
- Send appropriate notifications
- Process expired subscriptions
- Reset premium status for users without active subscriptions

## Database Functions

### `notify_expiring_subscriptions()`
Sends notifications to users whose subscriptions are expiring soon.

**Logic:**
- Finds subscriptions expiring within 7 days
- Sends notifications at any day within the 7-day window (not just specific days)
- Prevents duplicate notifications within 24 hours for the same subscription
- Includes subscription details in notification metadata

**Notification Types:**
- **Type**: `warning`
- **Title**: "Subscription Expiring Soon ⚠️"
- **Message**: Customized based on days remaining (1 day, 2-3 days, 4-7 days)

### `handle_expired_subscriptions()`
Processes expired subscriptions and removes premium access.

**Logic:**
1. Finds subscriptions with `status='active'` but `ends_at <= NOW()`
2. Updates subscription status to `expired`
3. Sets user's `is_premium` to `false`
4. Sends expiration notification
5. Also handles users with `is_premium=true` but no active subscription

**Notification Types:**
- **Type**: `error` (for expired subscriptions)
- **Type**: `warning` (for premium removed without subscription)
- **Title**: "Subscription Expired 🔒" or "Premium Access Removed 🔒"

## Laravel Command

### `subscriptions:check-expiration`

**Purpose**: Executes both database functions and provides statistics.

**Usage:**
```bash
php artisan subscriptions:check-expiration
```

**Output:**
```
🔍 Checking subscription expirations...
📧 Sending expiration warnings for subscriptions expiring soon...
⚠️ Processing expired subscriptions...

✅ Subscription check completed successfully!

+-------------------------------+-------+
| Metric                        | Count |
+-------------------------------+-------+
| Active Subscriptions          | 3     |
| Expiring Soon (7 days)        | 0     |
| Expired Subscriptions         | 0     |
| Premium Users                 | 3     |
| Users Without Subscription    | 0     |
+-------------------------------+-------+
```

**Features:**
- Calls both database functions
- Provides detailed statistics
- Logs results to Laravel logs
- Returns success/failure status codes

## Scheduled Task

**Schedule:** Daily at 9:00 AM (Asia/Manila timezone)

**Configuration:** `routes/console.php`

```php
Schedule::command('subscriptions:check-expiration')
    ->dailyAt('09:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Log::info('Subscription expiration check completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Subscription expiration check failed');
    });
```

**Features:**
- Prevents overlapping executions
- Runs in background
- Logs success/failure
- Uses Manila timezone

## Running the Scheduler

### Development (Local)
For the scheduler to work, you need to run the Laravel scheduler:

```bash
php artisan schedule:work
```

This will check for scheduled tasks every minute.

### Production (Server)
Add this cron entry to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Manual Execution

You can manually run the subscription check anytime:

```bash
php artisan subscriptions:check-expiration
```

This is useful for:
- Testing the system
- Immediate cleanup after changes
- Troubleshooting subscription issues

## Notification Data Structure

### Expiring Subscription Notification
```json
{
    "subscription_id": 4,
    "days_remaining": 7,
    "expires_at": "2025-11-04 15:35:56",
    "plan_name": "premium",
    "amount": "299.00",
    "action_type": "subscription_expiring"
}
```

### Expired Subscription Notification
```json
{
    "subscription_id": 4,
    "expired_at": "2025-10-04 15:35:56",
    "plan_name": "premium",
    "action_type": "subscription_expired"
}
```

### Premium Removed (No Subscription)
```json
{
    "action_type": "premium_removed_no_subscription"
}
```

## Database Schema

### Subscriptions Table
```sql
- id: bigint (PK)
- user_id: bigint (FK -> users.id)
- plan_name: varchar (default: 'premium')
- status: varchar (default: 'active')
- amount: numeric
- currency: varchar (default: 'PHP')
- billing_cycle: varchar (default: 'monthly')
- starts_at: timestamp
- ends_at: timestamp
- auto_renew: boolean (default: true)
- created_at: timestamp
- updated_at: timestamp
```

### Status Values
- `active`: Subscription is currently active
- `expired`: Subscription has passed its end date
- `cancelled`: User manually cancelled
- `pending`: Payment pending

## Testing

### Test Current Setup
```bash
# Run the command manually
php artisan subscriptions:check-expiration

# Check notifications created
SELECT * FROM notifications 
WHERE title LIKE '%Subscription%' 
ORDER BY created_at DESC 
LIMIT 10;

# Check premium users
SELECT id, name, email, is_premium 
FROM users 
WHERE is_premium = true;

# Check active subscriptions
SELECT s.*, u.name, u.email 
FROM subscriptions s 
JOIN users u ON s.user_id = u.id 
WHERE s.status = 'active' 
ORDER BY s.ends_at ASC;
```

### Test Expiring Subscription
To test the notification system, create a test subscription expiring soon:

```sql
-- Create a subscription expiring in 3 days
INSERT INTO subscriptions (user_id, plan_name, status, amount, currency, billing_cycle, starts_at, ends_at, auto_renew)
VALUES (
    1, 
    'premium', 
    'active', 
    299.00, 
    'PHP', 
    'monthly',
    NOW(),
    NOW() + INTERVAL '3 days',
    true
);

-- Run the command
php artisan subscriptions:check-expiration
```

## Logs

### Application Logs
Check `storage/logs/laravel.log` for:
- Command execution logs
- Success/failure messages
- Statistics from each run

### Database Logs
The PostgreSQL functions use `RAISE NOTICE` which can be viewed in database logs:
```
NOTICE: Notification sent to user 7 for subscription expiring in 3 days
NOTICE: Subscription 4 expired for user 7. Premium status removed.
```

## Troubleshooting

### PostgreSQL Boolean Comparison Error
**Problem**: `SQLSTATE[42883]: Undefined function: 7 ERROR: operator does not exist: boolean = integer`

**Root Cause**: Laravel Eloquent queries sometimes don't properly cast boolean values for PostgreSQL, causing `is_premium = 1` instead of `is_premium = true`.

**Solution Applied**: Updated `CheckSubscriptionExpiration.php` to use `DB::raw()` with explicit boolean comparisons:
```php
// Instead of: ->where('is_premium', true)
// Use: ->whereRaw('is_premium = true')
```

**Files Fixed**: `app/Console/Commands/CheckSubscriptionExpiration.php`

### Scheduler Not Running
**Problem**: Scheduled task not executing at 9 AM

**Solutions:**
1. Verify cron job is set up (production)
2. Run `php artisan schedule:work` (development)
3. Check timezone configuration in `config/app.php`
4. Verify `routes/console.php` has the schedule

### Notifications Not Sent
**Problem**: Users not receiving expiration notifications

**Solutions:**
1. Run command manually: `php artisan subscriptions:check-expiration`
2. Check if notifications table has entries
3. Verify subscription `ends_at` dates
4. Check for duplicate notification prevention (24-hour window)

### Premium Status Not Reset
**Problem**: Users still have `is_premium=true` after expiration

**Solutions:**
1. Run: `SELECT handle_expired_subscriptions();`
2. Check subscription status and end dates
3. Verify user has no active subscriptions
4. Check database function logs

## Current Status (as of Oct 15, 2025)

### Active Subscriptions
- **3 active subscriptions** with valid end dates
- All users with active subscriptions have `is_premium=true`

### Cleaned Up
- **4 users** had `is_premium=true` without active subscriptions
- All were reset to `is_premium=false`
- All received notifications about premium removal

### Users Reset
1. Louie J (louiejaybonghanoy@gmail.com)
2. qwertyuiop (qwertyuiop@gmail.com)
3. premium (premium@gmail.com)
4. zxcvbnm (zxcvbnm@gmail.com)

## Future Enhancements

### Possible Additions
1. **Email notifications** in addition to in-app notifications
2. **Auto-renewal** processing for subscriptions with `auto_renew=true`
3. **Grace period** (e.g., 3 days after expiration before removing premium)
4. **Subscription renewal reminders** at 30, 14, and 7 days
5. **Payment retry logic** for failed auto-renewals
6. **Subscription analytics dashboard** for admins

## PayMongo Integration

The system is configured to work with PayMongo webhooks:

**Webhook URL:** `https://securedocs.live/webhook/paymongo`

**Test Keys:**
- Public: `pk_test_pfrhqV9xkJiEqfH6beze6fYt`
- Secret: `sk_test_uKcGUHuFLtVQJBrs5w2gSfDo`

When implementing auto-renewal, the webhook will handle:
- Payment success → Renew subscription
- Payment failure → Send notification, mark for retry
- Multiple failures → Cancel subscription

## Related Files

- **Database Functions**: Supabase migration `create_subscription_expiration_notifications`
- **Laravel Command**: `app/Console/Commands/CheckSubscriptionExpiration.php`
- **Scheduler**: `routes/console.php`
- **Model**: `app/Models/Subscription.php`
- **Documentation**: `docs/SUBSCRIPTION_MANAGEMENT.md`

## Support

For issues or questions:
1. Check logs in `storage/logs/laravel.log`
2. Review database function output
3. Run manual command with `-v` flag for verbose output
4. Check notification table for created entries
