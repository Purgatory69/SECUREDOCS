# SECUREDOCS — Activity Tracking & Audit Logs Test Plan

This document is a step-by-step checklist to validate and debug the Activity Tracking & Audit Logs feature.

## 1) Overview
- __Scope__: `system_activities`, `user_sessions`, `security_events`, analytics, export, and UI.
- __Backend__: `App\Http\Controllers\ActivityController`, models: `SystemActivity`, `UserSession`, `SecurityEvent`.
- __Frontend__: Activity modal in `resources/views/user-dashboard.blade.php` and logic in `resources/js/dashboard.js`.

## 2) Prerequisites
- Logged-in user.
- DB schema applied in Supabase (activity tables created).
- Routes active in `routes/web.php`.
- Frontend built with Vite and app served.

## 3) API Endpoints (Smoke Test)
- __User Activities__: `GET /activities?limit=20&page=1&activity_type=&risk_level=&date_from=&date_to=`
- __File Activities__: `GET /files/{fileId}/activities?limit=50`
- __Dashboard Stats__: `GET /activities/dashboard-stats`
- __Timeline__: `GET /activities/timeline?days=7&group_by=day`
- __Export__: `GET /activities/export?format=json|csv&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD&include_metadata=true`
- __Sessions__: `GET /sessions`, `DELETE /sessions/{id}`
- __Security Events__: `GET /security-events?limit=20&severity=&resolved=`
- __Admin Analytics__: `GET /admin/analytics?days=30` (protect via middleware)

## 4) Backend Tests
- __Activity Logging__
  - [ ] File create/update/delete/move/rename generates activity entries.
  - [ ] Auth login/logout/failed login recorded.
  - [ ] Version create/restore/delete recorded.
- __Filters & Pagination__
  - [ ] Type, action, risk filters work individually and combined.
  - [ ] Date range filter works; inclusive of end date.
  - [ ] Pagination metadata correct and consistent with totals.
- __Permissions__
  - [ ] Users only see their activities.
  - [ ] File activity requires access (owner only).
  - [ ] Session revoke only for own sessions.
- __Export__
  - [ ] JSON export returns structured data with metadata when `include_metadata=true`.
  - [ ] CSV export creates properly formatted file with headers.
  - [ ] Date range exports filter correctly.
  - [ ] Large exports handle memory/timeout gracefully.
- __File Preview__
  - [ ] Click on file (not folder) opens preview page at `/files/{id}/preview`.
  - [ ] Preview displays correctly for supported formats (images, PDFs, text, code).
  - [ ] Download button works for files without browser preview support.
  - [ ] File metadata shows correctly (size, type, modified date, owner).
  - [ ] Back button returns to file list maintaining context (folder/search).
  - [ ] Preview page loads file data securely (owner-only access).
  - [ ] Large files handle gracefully without browser crashes.

## 5) Frontend UI Tests
- __Activity Modal__
  - [ ] Sidebar "Activity Logs" button opens modal.
  - [ ] Close button and backdrop close modal.
- __Stats & Timeline__
  - [ ] Today / This Week / This Month / Total show correct counts.
  - [ ] Timeline renders bars and scales with volume.
- __Activity List__
  - [ ] Risk color coding (low/medium/high/critical) styles applied.
  - [ ] Type icons (file/auth/system) correct.
  - [ ] Relative time shows (e.g., "2h ago").
  - [ ] File name shows for file-related activities.
  - [ ] Load More shows/hides based on pagination.
- __Filters__
  - [ ] Activity type, risk, date range filters apply and can reset.
  - [ ] Export button downloads JSON by default (change to CSV to test CSV flow).
- __Sessions Tab__
  - [ ] Active/inactive/suspicious badges appear correctly.
  - [ ] Revoke Session works and list refreshes.
- __Security Tab__
  - [ ] Events appear with severity icon/color; unresolved/resolved badges.

## 6) Integration Tests
- __Versions__ → Creates `version_*` activities; restore/delete recorded.
- __Search__ → If tracked, entries recorded for queries.

## 7) Edge Cases
- [ ] Deleted file/user referenced in old activity renders gracefully.
- [ ] Missing metadata handled (no UI crashes).
- [ ] Timezone display consistent; date range filters match expected local time.
- [ ] High volume lists (1000+ records) paginate without perf issues.

## 8) Performance Checks
- [ ] Modal opens in < 2s.
- [ ] Pagination smooth with 50+ items.
- [ ] Export completes under acceptable time for 5k records.
- [ ] Timeline rendering remains responsive.

## 9) Regression Checks
- [ ] Dashboard file operations unaffected.
- [ ] File preview functionality continues to work.
- [ ] Version history modal continues to work.

## 10) Debug Tips
- __Backend__
  - Check `ActivityController` responses via browser DevTools (Network tab).
  - Verify scopes: `SystemActivity::byType`, `byRiskLevel`, `highRisk`, `today/thisWeek/thisMonth`.
  - Ensure boolean filters compare against `true/false` (PostgreSQL compatibility).
- __Frontend__
  - Open DevTools Console; look for `Activity Error:` logs.
  - Ensure CSRF token header present.
  - Confirm sidebar button injected by `initializeActivityTrackingSystem()`.

## 11) Sample cURL
```bash
# User activities
curl -s "http://localhost:8000/activities?limit=10" -H "Accept: application/json"

# Dashboard stats
curl -s "http://localhost:8000/activities/dashboard-stats" -H "Accept: application/json"

# Timeline
curl -s "http://localhost:8000/activities/timeline?days=7&group_by=day" -H "Accept: application/json"

# Export JSON
curl -s "http://localhost:8000/activities/export?format=json&date_from=2025-07-01&date_to=2025-07-31&include_metadata=true" -H "Accept: application/json"

# Export CSV
curl -s "http://localhost:8000/activities/export?format=csv&date_from=2025-07-01&date_to=2025-07-31" -H "Accept: text/csv"

# Sessions
curl -s "http://localhost:8000/sessions" -H "Accept: application/json"

# Security events
curl -s "http://localhost:8000/security-events?limit=20" -H "Accept: application/json"
```

## 12) Known Non-Goals
- Mobile responsiveness improvements are currently out of scope per user direction.
