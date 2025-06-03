<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WebAuthnAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            
            return redirect()->route('login');
        }
        
        // Check if user has at least one WebAuthn key registered
        $user = Auth::user();
        if ($user->webauthnKeys()->count() === 0) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No biometric devices registered.'], 403);
            }
            
            return redirect()->route('webauthn.index')
                ->with('error', 'You need to register at least one biometric device before using this feature.');
        }
        
        return $next($request);
    }
}
