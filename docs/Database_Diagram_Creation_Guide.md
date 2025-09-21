# Database Diagram Creation Guide

## 🎯 Recommended Approach: Import Mermaid into Draw.io

### Step 1: Open Draw.io
1. Go to https://app.diagrams.net/
2. Create new diagram or open existing

### Step 2: Import Mermaid Diagram
1. Click **Arrange** → **Insert** → **Advanced** → **Mermaid**
2. Copy content from `docs/secure_docs_er_diagram.mmd`
3. Paste into Mermaid dialog
4. Click **Insert**
5. Draw.io automatically creates your ER diagram!

### Step 3: Customize
- Adjust colors, fonts, layout
- Add annotations or notes
- Export as SVG, PNG, or PDF

## 📊 Alternative: Manual Creation in Draw.io

### Core Tables to Create:
1. **users** (16 fields)
2. **files** (19 fields) 
3. **documents** (6 fields)
4. **document_metadata** (7 fields)
5. **document_rows** (4 fields)

### Blockchain Tables:
6. **blockchain_configs** (7 fields)
7. **blockchain_uploads** (9 fields)

### Activity Tables:
8. **system_activities** (21 fields)
9. **file_access_logs** (15 fields)
10. **daily_activity_stats** (14 fields)

### Search & UI Tables:
11. **saved_searches** (9 fields)
12. **search_logs** (10 fields)
13. **notifications** (8 fields)

### Security Tables:
14. **user_sessions** (16 fields)
15. **webauthn_credentials** (18 fields)
16. **password_reset_tokens** (3 fields)
17. **personal_access_tokens** (8 fields)
18. **sessions** (6 fields)

### System Tables:
19. **chat_histories** (3 fields)
20. **cache** (3 fields)
21. **cache_locks** (3 fields)
22. **jobs** (7 fields)
23. **job_batches** (9 fields)
24. **failed_jobs** (7 fields)
25. **migrations** (3 fields)

## 🔗 Key Relationships to Draw:
- users → files (one-to-many)
- files → files (self-reference for folders)
- files → documents (one-to-many)
- files → blockchain_uploads (one-to-many)
- users → system_activities (one-to-many)
- files → file_access_logs (one-to-many)

## 💡 Pro Tips:
1. **Use Mermaid import** - fastest and most accurate
2. **Start with core tables** - users, files, documents
3. **Group by category** - use colors to distinguish table types
4. **Show primary keys** - highlight with different color
5. **Draw relationships last** - connect tables with lines

## 🛠️ Tools Comparison:

| Tool | Best For | Import Method |
|------|----------|---------------|
| Draw.io | Free, web-based | Mermaid import |
| Lucidchart | Professional diagrams | CSV import |
| dbdiagram.io | Database-specific | DBML syntax |
| MySQL Workbench | MySQL databases | Reverse engineering |
| pgAdmin | PostgreSQL | Built-in ER tool |

## 📁 Files Available:
- `docs/secure_docs_er_diagram.mmd` - Complete Mermaid diagram
- `docs/secure_docs_er_diagram_simple.mmd` - Simplified version  
- `docs/lucidchart_database_schema.csv` - CSV for imports
- `docs/database_data_dictionary.csv` - Complete field reference
