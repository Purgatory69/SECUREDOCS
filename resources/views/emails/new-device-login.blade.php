<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Device Login - SecureDocs</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f89c00;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #141326;
            margin-bottom: 10px;
        }
        .logo .highlight {
            color: #f89c00;
        }
        .alert-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .alert-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .alert-title {
            font-size: 20px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
        }
        .info-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #f89c00;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
            font-size: 14px;
        }
        .security-notice {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .security-title {
            font-weight: bold;
            color: #004085;
            margin-bottom: 10px;
        }
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
        }
        .btn-primary {
            background-color: #f89c00;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SECURE<span class="highlight">DOCS</span></div>
            <p>Your secure document management platform</p>
        </div>

        <div class="alert-box">
            <div class="alert-icon">🔐</div>
            <div class="alert-title">New Device Login Detected</div>
            <p>Hi {{ $user->name }}, we detected a login to your SecureDocs account from a new device.</p>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Login Time</div>
                <div class="info-value">{{ $loginTime }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Device Type</div>
                <div class="info-value">{{ $deviceType }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Browser & Platform</div>
                <div class="info-value">{{ $browserInfo }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Location</div>
                <div class="info-value">{{ $locationDisplay }}</div>
            </div>
        </div>

        <div class="security-notice">
            <div class="security-title">🛡️ Was this you?</div>
            <p><strong>If this was you:</strong> No action is needed. You can safely ignore this email.</p>
            <p><strong>If this wasn't you:</strong> Your account may be compromised. Please secure your account immediately by changing your password and reviewing your recent activity.</p>
        </div>

        <div class="action-buttons">
            <a href="{{ url('/profile') }}" class="btn btn-primary">Review Account Security</a>
            <a href="{{ url('/profile/sessions') }}" class="btn btn-secondary">Manage Active Sessions</a>
        </div>

        <div class="security-notice">
            <div class="security-title">🔒 Security Tips</div>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Always log out from shared or public computers</li>
                <li>Use strong, unique passwords for your account</li>
                <li>Enable two-factor authentication for extra security</li>
                <li>Regularly review your account activity</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automated security notification from SecureDocs.</p>
            <p>If you no longer wish to receive these notifications, you can disable them in your <a href="{{ url('/profile') }}">account settings</a>.</p>
            <p>&copy; {{ date('Y') }} SecureDocs. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
