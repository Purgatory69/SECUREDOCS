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

## Troubleshooting

### Common Issues

1. **API Not Responding**
   - Ensure Laravel server is running: `php artisan serve`
   - Check firewall settings
   - Verify URL: `http://127.0.0.1:8000`

2. **n8n Connection Refused**
   - Use `host.docker.internal` instead of `localhost` in n8n
   - Ensure both n8n and Laravel are on the same network

3. **Windows Notifications Not Showing**
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
