<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $role = Auth::user()->role;
            switch ($role) {
                case 'admin':
                    return redirect('/admin/dashboard');
                case 'record admin':
                    return redirect('/record-admin/dashboard');
                case 'user':
                default:
                    return redirect('/dashboard');
            }
        }
        return $next($request);
    }
}
