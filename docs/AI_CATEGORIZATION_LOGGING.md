# AI Categorization Endpoint Logging

## Overview
This document describes the logging system for monitoring requests to the `/api/ai/categorization-status-public` endpoint.

## Log File Location
- **Path**: `storage/logs/ai-categorization-pings-YYYY-MM-DD.log`
- **Rotation**: Daily (Laravel's daily log driver)
- **Retention**: 7 days by default (configurable via `LOG_AI_CATEGORIZATION_DAYS`)

## Logged Information
Each request to the AI categorization status endpoint logs:

- **timestamp**: ISO 8601 formatted timestamp
- **user_id**: User ID making the request (or 'anonymous' if not provided)
- **ip**: Client IP address
- **user_agent**: Browser/client user agent string
- **referrer**: HTTP referrer header
- **response_status**: Response type returned by the endpoint
- **user_premium**: Premium status of the user (when applicable)

## Response Status Types
- `disabled`: AI categorization is disabled globally
- `premium_required`: User is not premium (feature requires premium)
- `idle`: No categorization in progress (for premium users)
- `in_progress`: Categorization is currently running
- `completed`: Categorization has finished
- `idle_no_user`: No user context provided

## Configuration
Add to your `.env` file:

```env
# AI Categorization Logging
LOG_AI_CATEGORIZATION_DAYS=7
```

## Sample Log Entry
```
[2025-10-12 14:24:35] local.INFO: AI Categorization Status Request {
    "timestamp": "2025-10-12T14:24:35.000000Z",
    "user_id": "21",
    "ip": "127.0.0.1",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "referrer": "https://securedocs.live/user-dashboard",
    "response_status": "disabled",
    "user_premium": false
}
```

## Monitoring Commands
To monitor the logs in real-time:

```bash
# Windows PowerShell
Get-Content storage\logs\ai-categorization-pings.log -Wait -Tail 10

# Or use the 'type' command (user preference)
type storage\logs\ai-categorization-pings-2025-10-12.log -Tail 50
```

## Disabling Logging
The logging is tied to the endpoint itself. To disable logging:
1. Set `AI_CATEGORIZATION_ENABLED=false` in `.env` (stops the endpoint entirely)
2. Or comment out the logging lines in `FileController@getCategorizationStatusPublic()`

## Performance Notes
- Logging is asynchronous and minimal overhead
- Files rotate daily to prevent large file sizes
- Old logs are automatically cleaned up after retention period
