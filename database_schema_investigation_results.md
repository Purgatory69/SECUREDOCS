# Database Schema Investigation Results

## Summary of Findings

The investigation has revealed the root cause of the database schema mismatch and identified all problematic areas.

## Current Database Schema

### Users Table Structure (Actual)
The users table contains the following columns:
- `id`, `email`, `email_verified_at`, `password`, `remember_token`
- `created_at`, `updated_at`
- `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`
- `is_approved`, `role`, `is_premium`, `current_team_id`, `profile_photo_path`
- `email_notifications_enabled`, `login_notifications_enabled`, `security_notifications_enabled`, `activity_notifications_enabled`
- **`firstname`, `lastname`, `birthday`** ✅

**Key Finding**: The users table does NOT have a `name` column, but DOES have `firstname` and `lastname` columns.

### User Model Configuration
- **Fillable fields**: Includes `firstname`, `lastname` ✅
- **Name accessor**: `getNameAttribute()` method correctly constructs name from `firstname` and `lastname` ✅
- **Model works correctly**: Testing shows the accessor properly returns "Kento Bubuli Futamata" from firstname="Kento" and lastname="Bubuli Futamata"

## Identified Issues

### 1. Missing Database Views
The activity tracking migration (`20250812221801_create_activity_tracking.sql`) defines views that reference `u.name`, but these views were never created:

**Missing Views:**
- `recent_activities` - References `u.name as user_name`
- `user_activity_summary` - References `u.name`

**Existing Views:**
- `file_activity_summary` - ✅ Exists and works

### 2. Problematic SQL in Migration File
Location: `supabase/migrations/20250812221801_create_activity_tracking.sql`

**Lines 185-192 (recent_activities view):**
```sql
CREATE VIEW recent_activities AS
SELECT 
    sa.*,
    u.name as user_name,  -- ❌ PROBLEM: u.name doesn't exist
    u.email as user_email,
    f.file_name,
    target_user.name as target_user_name  -- ❌ PROBLEM: target_user.name doesn't exist
FROM system_activities sa
JOIN users u ON sa.user_id = u.id
LEFT JOIN files f ON sa.file_id = f.id
LEFT JOIN users target_user ON sa.target_user_id = target_user.id
ORDER BY sa.created_at DESC;
```

**Lines 194-206 (user_activity_summary view):**
```sql
CREATE VIEW user_activity_summary AS
SELECT 
    u.id as user_id,
    u.name,  -- ❌ PROBLEM: u.name doesn't exist
    u.email,
    COUNT(sa.id) as total_activities,
    -- ... rest of query
FROM users u
LEFT JOIN system_activities sa ON u.id = sa.user_id
GROUP BY u.id, u.name, u.email;  -- ❌ PROBLEM: u.name doesn't exist
```

### 3. Outdated Schema Documentation
The file `public/db-schema.json` incorrectly shows the users table as having a `name` column:
```json
{"t":"users","c":[{"n":"id","t":"bigint"},{"n":"name","t":"character varying"},...]}
```
This file needs to be updated to reflect the actual schema.

## Specific Failing Queries

Any attempt to execute these views will result in:
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "recent_activities" does not exist
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "user_activity_summary" does not exist
```

And if the views were created with the current SQL, they would fail with:
```
ERROR: column "name" does not exist
```

## Code References Analysis

### Application Code
- ✅ No direct references to `u.name` or `users.name` found in PHP files
- ✅ User model correctly uses `firstname` and `lastname`
- ✅ Name accessor works properly

### Migration Files
- ❌ `supabase/migrations/20250812221801_create_activity_tracking.sql` contains problematic view definitions

### Documentation Files
- ❌ `public/db-schema.json` contains outdated schema information
- ❌ `docs/schema/SecureDocs_Graphviz.dot` shows users table with `name` column

## Root Cause

The issue stems from the activity tracking migration file containing SQL that assumes a `name` column exists in the users table, when the actual table structure uses `firstname` and `lastname` columns. The migration was either:

1. Written before the users table structure was finalized, or
2. Never properly tested against the actual database schema

## Next Steps Required

1. **Fix the SQL views** to use `CONCAT(firstname, ' ', lastname)` instead of `name`
2. **Update schema documentation** to reflect actual database structure
3. **Test the corrected migration** to ensure views work properly
4. **Apply the fix** to resolve the immediate database errors