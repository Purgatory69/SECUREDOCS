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
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
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
