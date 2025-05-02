<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Securedocs</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
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
        
        .register-container {
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
        
        .password-requirements {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 8px;
        }
        
        .terms {
            display: flex;
            align-items: flex-start;
            margin-bottom: 24px;
            text-align: left;
        }
        
        .terms input[type="checkbox"] {
            margin-right: 8px;
            margin-top: 3px;
        }
        
        .terms label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .terms a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .terms a:hover {
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
        
        .login-link {
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
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
            .register-container {
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
    <div class="register-container">
        <div class="logo">
            <div class="logo-icon">S</div>
            <div class="logo-text">Securedocs</div>
        </div>
        
        <h1>Create an account</h1>
        @if ($errors->any())
            <div style="color: red; margin-bottom: 15px;">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <p class="subtitle">Get started with secure document management</p>
        
        <form id="register-form" method="POST" action="/register">
            @csrf
            <div class="form-group">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <p class="password-requirements">Must be at least 8 characters with a mix of letters, numbers and symbols</p>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            
            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>
            
            <button type="submit" class="btn">Create account</button>
        </form>
        
    </div>
</body>
</html>