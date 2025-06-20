<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') - {{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('logo-white.png') }}" type="image/png"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #1a202c;
            color: #f7fafc;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }
        .error-container {
            background: #2d3748;
            padding: 2.5rem 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 400px;
        }
        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fc8181;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        .countdown {
            font-size: 1.5rem;
            color: #f6e05e;
            margin-bottom: 1.5rem;
        }
        .support {
            font-size: 1rem;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        @yield('content')
    </div>
</body>
</html>
