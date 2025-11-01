<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Account Access Revoked - SecureDocs</title>
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
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .warning-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .warning-title {
            font-size: 20px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 10px;
        }
        .content {
            margin: 25px 0;
            color: #212529;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .info-title {
            font-weight: bold;
            color: #721c24;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .contact-box {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .contact-title {
            font-weight: bold;
            color: #004085;
            margin-bottom: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SECURE<span class="highlight">DOCS</span></div>
            <p>Your secure document management platform</p>
        </div>

        <div class="warning-box">
            <div class="warning-icon">⚠️</div>
            <div class="warning-title">Account Access Revoked</div>
            <p>Hi {{ $user->name }}, your SecureDocs account access has been revoked by an administrator.</p>
        </div>

        <div class="content">
            <p>Your account approval status has been changed, and you currently do not have access to the platform.</p>
            <p><strong>Revoked on:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <div class="info-box">
            <div class="info-title">🔒 What This Means:</div>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>You cannot access your dashboard or files</li>
                <li>File uploads and management are disabled</li>
                <li>Public sharing links may be affected</li>
                <li>Your data remains secure and intact</li>
            </ul>
        </div>

        <div class="contact-box">
            <div class="contact-title">📧 Need Help?</div>
            <p>If you believe this is a mistake or would like to appeal this decision, please contact our support team.</p>
            <p>We're here to help resolve any issues and answer your questions.</p>
        </div>

        <div class="footer">
            <p>This is an automated notification from SecureDocs.</p>
            <p>If you have questions about your account status, please contact our support team.</p>
            <p>&copy; {{ date('Y') }} SecureDocs. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
