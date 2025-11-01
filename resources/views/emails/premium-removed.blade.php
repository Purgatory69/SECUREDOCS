<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Premium Access Removed - SecureDocs</title>
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
        .notice-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .notice-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .notice-title {
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
        .features-box {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .features-title {
            font-weight: bold;
            color: #004085;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            background-color: #f89c00;
            color: white;
            margin: 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SECURE<span class="highlight">DOCS</span></div>
            <p>Your secure document management platform</p>
        </div>

        <div class="notice-box">
            <div class="notice-icon">⚠️</div>
            <div class="notice-title">Premium Access Removed</div>
            <p>Hi {{ $user->name }}, your premium access to SecureDocs has been removed by an administrator.</p>
        </div>

        <div class="content">
            <p>Your account has been reverted to the standard (free) plan. You still have access to all core features of SecureDocs.</p>
            <p><strong>Effective Date:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <div class="info-box">
            <div class="info-title">🔒 Premium Features No Longer Available:</div>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Password-protected file sharing</li>
                <li>Blockchain storage integration</li>
                <li>AI-powered file categorization</li>
                <li>Advanced analytics and insights</li>
                <li>Increased storage capacity</li>
                <li>Priority customer support</li>
            </ul>
        </div>

        <div class="features-box">
            <div class="features-title">✓ Standard Features Still Available:</div>
            <ul style="margin: 10px 0; padding-left: 20px; color: #004085;">
                <li>Secure file upload and storage</li>
                <li>Folder organization and management</li>
                <li>Public file sharing links</li>
                <li>Basic search functionality</li>
                <li>File activity tracking</li>
                <li>Standard storage allocation</li>
            </ul>
        </div>

        <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center;">
            <p style="font-size: 16px; color: #212529; margin-bottom: 15px;">
                <strong>Want to continue enjoying premium features?</strong>
            </p>
            <p style="color: #6c757d; margin-bottom: 20px;">
                Upgrade to premium and unlock all advanced features again!
            </p>
            <div class="action-buttons" style="margin: 0;">
                <a href="{{ url('/pricing') }}" class="btn">View Premium Plans</a>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for using SecureDocs!</p>
            <p>If you have any questions about your account, please contact our support team.</p>
            <p>&copy; {{ date('Y') }} SecureDocs. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
