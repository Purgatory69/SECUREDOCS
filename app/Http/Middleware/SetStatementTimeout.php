<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetStatementTimeout
{
    /**
     * Handle an incoming request.
     *
     * Set a per-request PostgreSQL statement timeout (in milliseconds).
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // 15s timeout to avoid PHP 30s fatal; adjust via env if needed
            $timeoutMs = (int) (env('DB_STATEMENT_TIMEOUT_MS', 15000));
            if ($timeoutMs > 0) {
                DB::unprepared('SET LOCAL statement_timeout = ' . (int) $timeoutMs);
            }
        } catch (\Throwable $e) {
            // If the DB is not available yet, ignore; request may still proceed for non-DB routes
        }

        return $next($request);
    }
}
