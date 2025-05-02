<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Securedocs</title>
    <style>
        :root {
            --primary-color: #4285f4;
            --primary-dark: #3367d6;
            --secondary-color: #34a853;
            --accent-color: #fbbc05;
            --danger-color: #ea4335;
            --text-color: #202124;
            --text-secondary: #5f6368;
            --background-light: #f8f9fa;
            --border-color: #dadce0;
            --box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
        }
        
        body {
            background-color: var(--background-light);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 450px;
            padding: 48px 40px;
            text-align: center;
        }
        
        .logo {
            margin-bottom: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 8px;
            margin-right: 12px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 400;
        }
        
        .subtitle {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        
        .form-group {
            margin-bottom: 24px;
            text-align: left;
        }
        
        label {
            display: block;
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.2s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 24px;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .alternative-auth {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }
        
        .alternative-auth h3 {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 16px;
            font-weight: normal;
        }
        
        .alt-options {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }
        
        .alt-option {
            flex: 1;
            min-width: 120px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .alt-option:hover {
            background-color: var(--background-light);
        }
        
        .alt-option i {
            font-size: 18px;
        }
        
        @media (max-width: 500px) {
            .login-container {
                padding: 32px 20px;
            }
            
            .alt-options {
                flex-direction: column;
            }
            
            .alt-option {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">S</div>
            <div class="logo-text">Securedocs</div>
        </div>
        
        <h1>Sign in</h1>
        @if ($errors->any())
            <div style="color: red; margin-bottom: 15px;">
                {{ $errors->first('error') }}
            </div>
        @endif

        <p class="subtitle">Access your secure document management system</p>
        
        <form id="login-form" method="POST" action="/login">
            @csrf
            <div class="form-group">
                <label for="email">Email or username</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            
            <div class="forgot-password">
                <a href="#">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="alternative-auth">
            <h3>Or continue with</h3>
            <div class="alt-options">
                <div class="alt-option">
                    <i>🔒</i>
                    <span>Multi-Factor</span>
                </div>
                <div class="alt-option">
                    <i>👆</i>
                    <span>Biometrics</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>