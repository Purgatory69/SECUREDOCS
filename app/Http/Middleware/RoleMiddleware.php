<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:admin') or ->middleware('role:user') or ->middleware('role:admin,user')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Ensure authenticated
        $user = $request->user();
        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $role = (string)($user->role ?? '');
        $allowed = empty($roles) || in_array($role, $roles, true);

        if ($allowed) {
            return $next($request);
        }

        // Not allowed - smart redirect by current user role
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($role === 'admin') {
            return redirect()->to('/admin/dashboard');
        }
        if ($role === 'user') {
            return redirect()->to('/user/dashboard');
        }

        abort(403);
    }
}
