<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureDocs - File Access OTP</title>
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
        .file-info {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-left: 4px solid #f89c00;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .file-info .label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .file-info .value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            word-break: break-all;
        }
        .otp-container {
            text-align: center;
            margin: 35px 0;
            padding: 30px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 12px;
            border: 2px solid #f59e0b;
        }
        .otp-label {
            font-size: 14px;
            font-weight: 600;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 800;
            color: #92400e;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            background: #ffffff;
            padding: 15px 25px;
            border-radius: 8px;
            display: inline-block;
            border: 2px solid #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
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
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
                padding: 12px 20px;
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
                Hello {{ $user->name }},
            </div>

            <div class="message">
                You have requested access to a protected file in your SecureDocs account. To proceed, please use the One-Time Password (OTP) provided below.
            </div>

            <!-- File Information -->
            <div class="file-info">
                <div class="label">Requested File</div>
                <div class="value">📄 {{ $fileName }}</div>
            </div>

            <!-- OTP Code -->
            <div class="otp-container">
                <div class="otp-label">Your One-Time Password</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <!-- Expiry Information -->
            <div class="expiry-info">
                <div class="icon">⏰</div>
                <div class="text">This OTP will expire in {{ $expiryMinutes }} minutes</div>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <div class="icon">🛡️</div>
                <div class="title">Security Notice</div>
                <div class="text">
                    • Never share this OTP with anyone<br>
                    • This code is only valid for the requested file<br>
                    • If you didn't request this access, please secure your account immediately<br>
                    • Contact support if you notice any suspicious activity
                </div>
            </div>

            <div class="message">
                Enter this code in your SecureDocs application to access the protected file. If you didn't request this access, please ignore this email and consider changing your password.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="signature">Best regards,</div>
            <div class="company">The SecureDocs Security Team</div>
            
            <div class="links">
                <a href="https://securedocs.live">Visit SecureDocs</a> |
                <a href="https://securedocs.live/support">Get Support</a> |
                <a href="https://securedocs.live/security">Security Center</a>
            </div>
        </div>
    </div>
</body>
</html>
