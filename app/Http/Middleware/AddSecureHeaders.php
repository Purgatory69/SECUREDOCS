<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AddSecureHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Headers for secure domain access
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        // Debug: Log session cookie details on login attempts
        if ($request->is('login')) {
            $cookieHeader = $request->header('Cookie', 'none');
            $sessionCookieName = config('session.cookie');
            
            if ($request->isMethod('GET')) {
                Log::debug('Login GET - Session Created', [
                    'session_id' => $request->session()->getId(),
                    'csrf_token' => substr($request->session()->token(), 0, 15) . '...',
                    'has_session_cookie' => str_contains($cookieHeader, $sessionCookieName),
                    'cookie_header_length' => strlen($cookieHeader),
                ]);
            } elseif ($request->isMethod('POST')) {
                Log::debug('Login POST - CSRF Validation', [
                    'session_id' => $request->session()->getId(),
                    'session_domain' => config('session.domain'),
                    'session_secure' => config('session.secure'),
                    'session_same_site' => config('session.same_site'),
                    'has_csrf_in_session' => $request->session()->has('_token'),
                    'csrf_from_form' => substr($request->input('_token', ''), 0, 15) . '...',
                    'csrf_from_session' => substr($request->session()->token(), 0, 15) . '...',
                    'tokens_match' => $request->input('_token') === $request->session()->token(),
                    'has_session_cookie' => str_contains($cookieHeader, $sessionCookieName),
                    'request_has_session' => $request->hasSession(),
                ]);
            }
        }
        
        return $response;
    }
}
