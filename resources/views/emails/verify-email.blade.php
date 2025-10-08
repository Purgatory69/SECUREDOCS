<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureDocs - Verify Your Email Address</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #374151;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #1F2235 0%, #2A2D47 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23ffffff" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        .logo {
            position: relative;
            z-index: 1;
        }
        .logo h1 {
            color: #f89c00;
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .logo p {
            color: #e5e7eb;
            font-size: 14px;
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
            line-height: 1.7;
        }
        .verification-container {
            text-align: center;
            margin: 35px 0;
            padding: 30px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 12px;
            border: 2px solid #3b82f6;
        }
        .verification-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 20px;
        }
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }
        .verify-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }
        .alternative-link {
            margin-top: 20px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }
        .alternative-link .label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .alternative-link .url {
            font-size: 12px;
            color: #4b5563;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            background: #ffffff;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .expiry-info {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            text-align: center;
        }
        .expiry-info .icon {
            font-size: 20px;
            margin-bottom: 8px;
        }
        .expiry-info .text {
            font-size: 14px;
            color: #dc2626;
            font-weight: 600;
        }
        .security-notice {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .security-notice .icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .security-notice .title {
            font-size: 16px;
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 8px;
        }
        .security-notice .text {
            font-size: 14px;
            color: #0c4a6e;
            line-height: 1.6;
        }
        .footer {
            background: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer .signature {
            font-size: 16px;
            color: #374151;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .footer .company {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        .footer .links {
            font-size: 12px;
            color: #9ca3af;
        }
        .footer .links a {
            color: #f89c00;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer .links a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .header, .content, .footer {
                padding: 25px 20px;
            }
            .verify-button {
                padding: 12px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <h1>🔐 SecureDocs</h1>
                <p>Secure Document Management System</p>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Welcome to SecureDocs!
            </div>

            <div class="message">
                Thank you for creating your SecureDocs account. To get started with secure document management, please verify your email address by clicking the button below.
            </div>

            <!-- Verification Section -->
            <div class="verification-container">
                <div class="verification-title">✅ Verify Your Email Address</div>
                
                <a href="{{ $actionUrl }}" class="verify-button">
                    Verify Email Address
                </a>

                <div class="alternative-link">
                    <div class="label">If the button doesn't work, copy and paste this link:</div>
                    <div class="url">{{ $actionUrl }}</div>
                </div>
            </div>

            <!-- Expiry Information -->
            <div class="expiry-info">
                <div class="icon">⏰</div>
                <div class="text">This verification link will expire in 60 minutes</div>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <div class="icon">🛡️</div>
                <div class="title">Security Notice</div>
                <div class="text">
                    • This email was sent because someone created an account with this email address<br>
                    • If you didn't create a SecureDocs account, you can safely ignore this email<br>
                    • Your email will not be verified and no account will be activated<br>
                    • For security questions, contact our support team
                </div>
            </div>

            <div class="message">
                Once verified, you'll have access to SecureDocs' powerful features including secure file storage, sharing, and advanced security controls.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="signature">Welcome to the future of secure document management!</div>
            <div class="company">The SecureDocs Team</div>
            
            <div class="links">
                <a href="https://securedocs.live">Visit SecureDocs</a> |
                <a href="https://securedocs.live/support">Get Support</a> |
                <a href="https://securedocs.live/security">Security Center</a>
            </div>
        </div>
    </div>
</body>
</html>
