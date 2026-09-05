<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user() || $request->user()->peran !== $role) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki izin (role: ' . $role . ') untuk mengakses resource ini.'
            ], 403);
        }

        return $next($request);
    }
}
