# SecureDocs Database Export Guide

## 🚀 Quick Export (Windows)

**Run the batch script:**
```bash
.\export_database.bat
```

This script will automatically:
- Detect available tools (Supabase CLI or pg_dump)
- Export your database using the best available method
- Create organized export files

## 📁 Export Methods

### Method 1: Supabase CLI (Recommended)
```bash
# Install Supabase CLI first
npm install -g @supabase/cli

# Then run exports
supabase db dump --db-url "postgresql://postgres.fywmgiuvdbsjfchfzixc:Star183795@aws-0-ap-northeast-1.pooler.supabase.com:6543/postgres" -f roles.sql --role-only

supabase db dump --db-url "postgresql://postgres.fywmgiuvdbsjfchfzixc:Star183795@aws-0-ap-northeast-1.pooler.supabase.com:6543/postgres" -f schema.sql

supabase db dump --db-url "postgresql://postgres.fywmgiuvdbsjfchfzixc:Star183795@aws-0-ap-northeast-1.pooler.supabase.com:6543/postgres" -f data.sql --use-copy --data-only
```

### Method 2: PostgreSQL pg_dump
```bash
# Complete database
pg_dump --clean --if-exists --quote-all-identifiers \
  -h aws-0-ap-northeast-1.pooler.supabase.com \
  -p 6543 \
  -U postgres.fywmgiuvdbsjfchfzixc \
  -d postgres \
  --no-owner --no-privileges > securedocs_complete.sql

# Schema only
pg_dump --schema-only \
  -h aws-0-ap-northeast-1.pooler.supabase.com \
  -p 6543 \
  -U postgres.fywmgiuvdbsjfchfzixc \
  -d postgres > securedocs_schema.sql
```

## 📊 What You'll Get

| File | Contents | Use Case |
|------|----------|----------|
| `roles.sql` | Database roles & permissions | User management setup |
| `schema.sql` | Tables, indexes, constraints | Database structure |
| `data.sql` | All your actual data | Data migration |
| `complete.sql` | Everything in one file | Full backup/restore |

## 🔧 Installation Requirements

### Option A: Supabase CLI
```bash
npm install -g @supabase/cli
```

### Option B: PostgreSQL Tools
1. Download from [PostgreSQL.org](https://www.postgresql.org/download/windows/)
2. Add to PATH: `C:\Program Files\PostgreSQL\17\bin`
3. Verify: `pg_dump --version`

## 💡 Usage Examples

### Import to Local PostgreSQL
```bash
# Create local database
createdb securedocs_local

# Import schema
psql -d securedocs_local -f schema.sql

# Import data
psql -d securedocs_local -f data.sql
```

### Import to Another Cloud Provider
```bash
# Example: Import to another Supabase project
psql -d "postgresql://postgres.[NEW-PROJECT]:[PASSWORD]@[HOST]:5432/postgres" -f complete.sql
```

### Backup for Development
```bash
# Create development copy
psql -d "postgresql://localhost:5432/securedocs_dev" -f complete.sql
```

## 🛡️ Security Notes

- Database credentials are included in the script from your `.env`
- Keep exported SQL files secure (they contain your data)
- Consider encrypting backups for long-term storage
- Rotate database passwords after sharing exports

## 📋 Export Checklist

- [ ] Install Supabase CLI or PostgreSQL tools
- [ ] Run export script or manual commands
- [ ] Verify export files are created
- [ ] Test import on development database
- [ ] Store backups securely
- [ ] Document export date and version

## 🔄 Automation Options

### Scheduled Backups
```bash
# Add to Windows Task Scheduler
schtasks /create /tn "SecureDocs Backup" /tr "C:\path\to\export_database.bat" /sc daily /st 02:00
```

### CI/CD Integration
```yaml
# GitHub Actions example
- name: Backup Database
  run: |
    npm install -g @supabase/cli
    supabase db dump --db-url "${{ secrets.DATABASE_URL }}" -f backup.sql
```

## 🆘 Troubleshooting

### Common Issues:
1. **"Command not found"** - Install PostgreSQL tools or Supabase CLI
2. **"Permission denied"** - Check database credentials
3. **"Connection timeout"** - Verify network connectivity
4. **"Large file size"** - Use `--use-copy` flag for faster exports

### Support:
- Check Supabase docs: https://supabase.com/docs
- PostgreSQL docs: https://www.postgresql.org/docs/
- Contact support if migration issues persist
