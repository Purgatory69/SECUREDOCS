# n8n Integration Guide - Local API Setup

## Overview
This guide explains how to set up your local Laravel API to work with n8n for automated notifications and website refresh when files are modified.

## API Endpoints

### 1. n8n Notification Endpoint
- **URL**: `http://127.0.0.1:8000/api/n8n/notification`
- **Method**: POST
- **Description**: Receives notifications from n8n and triggers Windows notifications + website refresh

#### Request Format
```json
{
  "title": "AI Agent Update",
  "message": "Files have been modified by AI agent",
  "type": "info",
  "workflow": "AI File Monitor",
  "refresh_website": true
}
```

#### Parameters
- `title` (string): Notification title
- `message` (string): Notification message
- `type` (string): Notification type (info, success, warning, error)
- `workflow` (string): Workflow name for identification
- `refresh_website` (boolean): Whether to trigger website refresh (default: true)

#### Response
```json
{
  "status": "success",
  "message": "Notification received and displayed",
  "data": {
    "title": "AI Agent Update",
    "message": "Files have been modified by AI agent",
    "type": "info",
    "workflow": "AI File Monitor",
    "refresh_website": true,
    "received_at": "2025-08-07T12:18:19.000Z"
  }
}
```

### 2. Website Refresh Endpoints
- **Get Refresh Event**: `GET /api/refresh/event`
- **Trigger Refresh**: `POST /api/refresh/trigger`
- **Check File Changes**: `POST /api/refresh/check-files`
- **SSE Events**: `GET /api/refresh/sse`

## n8n HTTP Request Configuration

### Basic Setup
1. Add an **HTTP Request** node to your n8n workflow
2. Configure the following:
   - **URL**: `http://127.0.0.1:8000/api/n8n/notification`
   - **Method**: POST
   - **Content-Type**: application/json

### Example Configuration
```json
{
  "url": "http://127.0.0.1:8000/api/n8n/notification",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "title": "{{$node['File Monitor'].json['title']}}",
    "message": "{{$node['File Monitor'].json['message']}}",
    "type": "{{$node['File Monitor'].json['type']}}",
    "workflow": "{{$workflow.name}}",
    "refresh_website": true
  }
}
```

## Complete n8n Workflow Example

### Workflow: AI File Monitor
```json
{
  "name": "AI File Monitor",
  "nodes": [
    {
      "name": "File Watcher",
      "type": "n8n-nodes-base.localFileTrigger",
      "parameters": {
        "path": "c:\\Users\\LENOVO\\Desktop\\codes\\SECUREDOCS",
        "events": ["add", "change", "unlink"],
        "options": {
          "recursive": true,
          "ignore": ["node_modules/**", "*.log", "*.tmp"]
        }
      }
    },
    {
      "name": "Prepare Notification",
      "type": "n8n-nodes-base.function",
      "parameters": {
        "functionCode": "const filePath = items[0].json.filePath;\nconst eventType = items[0].json.eventType;\n\nreturn [\n  {\n    json: {\n      title: 'File Change Detected',\n      message: `File ${eventType}: ${filePath}`,\n      type: 'info',\n      filePath: filePath,\n      eventType: eventType\n    }\n  }\n];"
      }
    },
    {
      "name": "Send to Local API",
      "type": "n8n-nodes-base.httpRequest",
      "parameters": {
        "method": "POST",
        "url": "http://127.0.0.1:8000/api/n8n/notification",
        "authentication": "none",
        "headers": {
          "Content-Type": "application/json"
        },
        "body": {
          "title": "{{$node['Prepare Notification'].json['title']}}",
          "message": "{{$node['Prepare Notification'].json['message']}}",
          "type": "{{$node['Prepare Notification'].json['type']}}",
          "workflow": "AI File Monitor",
          "refresh_website": true
        }
      }
    }
  ]
}
```

## JavaScript Integration

### Include in Your Website
Add the following script to your website's JavaScript:

```html
<script src="/js/website-refresh.js"></script>
```

### Manual Refresh Trigger
You can also trigger refresh manually from JavaScript:

```javascript
// Trigger website refresh
window.triggerWebsiteRefresh('manual', {
  reason: 'AI agent completed task',
  files: ['index.html', 'styles.css']
});

// Check refresh status
fetch('/api/refresh/event')
  .then(response => response.json())
  .then(data => {
    if (data.should_refresh) {
      window.location.reload();
    }
  });
```

## Testing

### 1. Test API Status
```bash
# Check if API is running
curl http://127.0.0.1:8000/api/n8n/status
```

### 2. Test Notification
```bash
# Send test notification
curl -X POST http://127.0.0.1:8000/api/n8n/notification \
  -H "Content-Type: application/json" \
  -d '{"title":"Test","message":"Test notification","type":"info","workflow":"Test"}'
```

### 3. Test Website Refresh
```bash
# Trigger website refresh
curl -X POST http://127.0.0.1:8000/api/refresh/trigger \
  -H "Content-Type: application/json" \
  -d '{"source":"test","data":{"reason":"manual test"}}'
```

## Webhook Readiness and Testing (n8n)

Use these quick checks to verify your n8n webhooks are ready and correct.

### 1) Confirm configured webhook URLs (from .env)

PowerShell (Windows):

```powershell
# Show only N8N-related entries
type .env | findstr N8N_
```

Expected keys (examples):
- `N8N_WEBHOOK_URL`
- `N8N_DEFAULT_CHAT_WEBHOOK_URL`
- `N8N_PREMIUM_CHAT_WEBHOOK_URL`

Note: Ensure the path segment matches your n8n node (e.g., `/webhook/<id>` vs `/webhook-test/<id>`), and that chat webhooks often end with `/chat`.

### 2) Send a health-check POST to general workflow webhook

```powershell
# Replace with your actual N8N_WEBHOOK_URL value from .env
$u = "http://localhost:5678/webhook/f106ab40-0651-4e2c-acc1-6591ab771828"
Invoke-RestMethod -Method Post -Uri $u -ContentType 'application/json' -Body (
  @{ ping = 'ok'; source = 'health-check'; timestamp = (Get-Date).ToString('o') } | ConvertTo-Json
)
```

### 3) Send a test message to Default Chat webhook

```powershell
# Replace with your actual N8N_DEFAULT_CHAT_WEBHOOK_URL from .env
$chat = "http://localhost:5678/webhook/0a216509-e55c-4a43-8d4a-581dffe09d18/chat"
Invoke-RestMethod -Method Post -Uri $chat -ContentType 'application/json' -Body (
  @{ chatInput = 'hello'; sessionId = [guid]::NewGuid().ToString(); metadata = @{ userEmail = 'test@example.com'; userName = 'Tester' } } | ConvertTo-Json
)
```

### 4) Send a test message to Premium Chat webhook

```powershell
# Replace with your actual N8N_PREMIUM_CHAT_WEBHOOK_URL from .env
$chat = "http://localhost:5678/webhook/e104e40e-6134-4825-a6f0-8a646d882662/chat"
Invoke-RestMethod -Method Post -Uri $chat -ContentType 'application/json' -Body (
  @{ chatInput = 'hello (premium)'; sessionId = [guid]::NewGuid().ToString(); metadata = @{ userId = 1; userEmail = 'premium@example.com'; isPremium = $true } } | ConvertTo-Json
)
```

### 5) Interpreting responses

- 200 OK: Webhook is reachable. Body may be JSON or text depending on your workflow (e.g., a Respond node).
- 404 Not Found: ID/path mismatch (verify `/webhook` vs `/webhook-test` and the UUID).
- 405 Method Not Allowed: Use POST, not GET.
- 401/403: Your webhook may require authentication; configure node credentials/headers accordingly.

### 6) If n8n is in Docker/WSL

- From host PowerShell to n8n in Docker (default): `http://localhost:5678` should work.
- From n8n to Laravel in Windows host: use `http://host.docker.internal:8000` in your n8n HTTP Request nodes.

## Troubleshooting

### Common Issues

1. **API Not Responding**
   - Ensure Laravel server is running: `php artisan serve`
   - Check firewall settings
   - Verify URL: `http://127.0.0.1:8000`

2. **n8n Connection Refused**
   - Use `host.docker.internal` instead of `localhost` in n8n
   - Ensure both n8n and Laravel are on the same network

3. **Windows Notifications Not ShI followed your preferences to use PowerShell type and concise test commands. Summary: Documentation updated; config and frontend wiring pending. Should I proceed with the config and layout fixes?

owing**
   - Check PHP error logs: `storage/logs/laravel.log`
   - Ensure PowerShell execution policy allows scripts

### Debug Commands
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Test API endpoints
curl http://127.0.0.1:8000/api/n8n/status

# Check file permissions
ls -la storage/logs/
```

## Advanced Configuration

### Environment Variables
Add to `.env`:
```bash
REFRESH_ENABLED=true
REFRESH_POLL_INTERVAL=3000
REFRESH_SSE_ENABLED=true
```

### Custom Notification Styling
Modify `resources/js/website-refresh.js` to customize the refresh notification appearance.

## Integration with Other AI Agents

### Python Agent Integration
```python
import requests

def notify_file_change(file_path, event_type):
    url = "http://127.0.0.1:8000/api/n8n/notification"
    payload = {
        "title": "AI Agent Update",
        "message": f"File {event_type}: {file_path}",
        "type": "info",
        "workflow": "AI Agent",
        "refresh_website": True
    }
    
    response = requests.post(url, json=payload)
    return response.json()
```

### Node.js Agent Integration
```javascript
const axios = require('axios');

async function notifyChange(message) {
  try {
    const response = await axios.post('http://127.0.0.1:8000/api/n8n/notification', {
      title: 'AI Agent Update',
      message: message,
      type: 'info',
      workflow: 'AI Agent',
      refresh_website: true
    });
    
    console.log('Notification sent:', response.data);
  } catch (error) {
    console.error('Error sending notification:', error);
  }
}
```

## Support
For issues or questions, check the Laravel logs at `storage/logs/laravel.log`.
