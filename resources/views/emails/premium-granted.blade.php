<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Premium Access Granted - SecureDocs</title>
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
        .premium-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
            text-align: center;
            color: white;
        }
        .premium-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .premium-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .premium-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            font-size: 16px;
            font-weight: bold;
        }
        .features-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .features-title {
            font-weight: bold;
            color: #141326;
            margin-bottom: 15px;
            font-size: 18px;
            text-align: center;
        }
        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        .feature-item {
            display: flex;
            align-items: start;
            padding: 10px;
            background-color: white;
            border-radius: 6px;
        }
        .feature-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .feature-text {
            flex: 1;
        }
        .feature-name {
            font-weight: bold;
            color: #141326;
            margin-bottom: 3px;
        }
        .feature-desc {
            font-size: 12px;
            color: #6c757d;
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
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
        @media (max-width: 600px) {
            .info-grid, .features-grid {
                grid-template-columns: 1fr;
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

        <div class="premium-box">
            <div class="premium-icon">👑</div>
            <div class="premium-title">Premium Access Granted!</div>
            <div class="premium-subtitle">Welcome to SecureDocs Premium</div>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <p style="font-size: 16px; color: #212529;">Hi <strong>{{ $user->name }}</strong>, congratulations! You've been granted premium access to SecureDocs by our administrator.</p>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value" style="color: #28a745;">✓ Active</div>
            </div>
            <div class="info-item">
                <div class="info-label">Duration</div>
                <div class="info-value">{{ $duration }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Activated On</div>
                <div class="info-value">{{ $activatedAt }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Expires On</div>
                <div class="info-value">{{ $expiresAt }}</div>
            </div>
        </div>

        <div class="features-box">
            <div class="features-title">🌟 Your Premium Features</div>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">🔐</div>
                    <div class="feature-text">
                        <div class="feature-name">Password Protection</div>
                        <div class="feature-desc">Secure shares with passwords</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⛓️</div>
                    <div class="feature-text">
                        <div class="feature-name">Blockchain Storage</div>
                        <div class="feature-desc">Permanent file storage</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🤖</div>
                    <div class="feature-text">
                        <div class="feature-name">AI Categorization</div>
                        <div class="feature-desc">Smart file organization</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">
                        <div class="feature-name">Advanced Analytics</div>
                        <div class="feature-desc">Detailed insights & reports</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💾</div>
                    <div class="feature-text">
                        <div class="feature-name">Increased Storage</div>
                        <div class="feature-desc">More space for your files</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-text">
                        <div class="feature-name">Priority Support</div>
                        <div class="feature-desc">Faster response times</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="{{ url('/user/dashboard') }}" class="btn">Explore Premium Features</a>
        </div>

        <div style="background-color: #e7f3ff; border: 1px solid #b8daff; border-radius: 8px; padding: 20px; margin: 25px 0;">
            <div style="font-weight: bold; color: #004085; margin-bottom: 10px;">💡 Getting Started with Premium</div>
            <ul style="margin: 10px 0; padding-left: 20px; color: #004085;">
                <li>Try password-protecting your shared files</li>
                <li>Upload files to blockchain for permanent storage</li>
                <li>Enable AI categorization for automatic organization</li>
                <li>Check out your enhanced analytics dashboard</li>
            </ul>
        </div>

        <div class="footer">
            <p>Thank you for being a valued member of SecureDocs!</p>
            <p>Your premium access will automatically expire on {{ $expiresAt }}.</p>
            <p>&copy; {{ date('Y') }} SecureDocs. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
