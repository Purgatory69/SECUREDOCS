<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust proxies (ngrok, cloudflare, etc.) for HTTPS detection
        $middleware->trustProxies(at: '*');
        
        // Add ngrok headers to bypass interstitial page
        $middleware->append(\App\Http\Middleware\AddNgrokHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Gracefully handle CSRF token mismatch (HTTP 419) to avoid a hard error page
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                // JSON requests: respond with a clear message so clients can retry
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your session expired. Please refresh and try again.',
                        'error' => 'token_mismatch'
                    ], 419);
                }

                // Web requests: redirect back with a friendly error and preserve input (except password)
                // Regenerate a fresh token for the next attempt
                try { $request->session()->regenerateToken(); } catch (\Throwable $t) {}

                if ($request->is('login') && $request->method() === 'POST') {
                    return redirect('/login')
                        ->withErrors(['email' => 'Your session expired. Please try again.'])
                        ->withInput($request->except('password'));
                }

                return redirect()->back()->with('error', 'Your session expired. Please try again.');
            }
            // Handle PostgreSQL statement timeout (SQLSTATE 57014) and similar DB timeout errors
            $isDbTimeout = false;

            if ($e instanceof \Illuminate\Database\QueryException) {
                $sqlState = method_exists($e, 'getCode') ? (string) $e->getCode() : '';
                $message = $e->getMessage();
                $isDbTimeout = str_contains($message, 'statement timeout') || str_contains($message, 'SQLSTATE[57014]') || $sqlState === '57014';
            } elseif ($e instanceof \PDOException) {
                $message = $e->getMessage();
                $code = (string) $e->getCode();
                $isDbTimeout = str_contains($message, 'statement timeout') || $code === '57014';
            }

            if ($isDbTimeout) {
                // API requests: return JSON error
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The request took too long and was canceled. Please try again.',
                        'error' => 'statement_timeout'
                    ], 503);
                }

                // Web requests: if during login post, redirect back to login with error and preserve input (except password)
                if ($request->is('login') && $request->method() === 'POST') {
                    return redirect('/login')
                        ->withErrors(['email' => 'Login took too long. Please try again.'])
                        ->withInput($request->except('password'));
                }

                // For other pages, go back with a generic message
                return redirect()->back()->with('error', 'Operation timed out. Please try again.');
            }

            return null; // fall through to default handling
        });
    })->create();
