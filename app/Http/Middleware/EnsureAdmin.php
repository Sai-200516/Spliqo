<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->user()?->is_admin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
