<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Account Approved - SecureDocs</title>
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
        .success-box {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .success-title {
            font-size: 20px;
            font-weight: bold;
            color: #155724;
            margin-bottom: 10px;
        }
        .content {
            margin: 25px 0;
            color: #212529;
        }
        .features-box {
            background-color: #f8f9fa;
            border-left: 4px solid #f89c00;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .features-title {
            font-weight: bold;
            color: #141326;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .features-list {
            margin: 10px 0;
            padding-left: 20px;
        }
        .features-list li {
            margin: 8px 0;
            color: #495057;
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
        }
        .btn:hover {
            opacity: 0.9;
        }
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .info-title {
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

        <div class="success-box">
            <div class="success-title">Your Account Has Been Approved!</div>
            <p>Hi {{ $user->name }}, great news! Your SecureDocs account has been approved by our administrator.</p>
        </div>

        <div class="content">
            <p>You now have full access to SecureDocs and can start managing your documents securely.</p>
            <p><strong>Approved on:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <div class="features-box">
            <div class="features-title">What You Can Do Now:</div>
            <ul class="features-list">
                <li>Upload and organize your documents securely</li>
                <li>Create folders and manage file structures</li>
                <li>Share files with public links</li>
                <li>Search and categorize your documents</li>
                <li>Track your file activities and history</li>
                <li>Access your files from anywhere</li>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="{{ url('/user/dashboard') }}" class="btn">Access Your Dashboard</a>
        </div>

        <div class="info-box">
            <div class="info-title">Getting Started</div>
            <p>Ready to get started? Here are some quick tips:</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Upload your first document to get familiar with the interface</li>
                <li>Create folders to organize your files</li>
                <li>Explore the search feature to find documents quickly</li>
                <li>Check out premium features for advanced capabilities</li>
            </ul>
        </div>

        <div class="footer">
            <p>Welcome to SecureDocs! We're excited to have you on board.</p>
            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>&copy; {{ date('Y') }} SecureDocs. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
