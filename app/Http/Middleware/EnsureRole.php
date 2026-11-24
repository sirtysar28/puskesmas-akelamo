<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas akses berbasis peran (RBAC).
 * Dipakai sebagai: ->middleware('role:admin,petugas')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->hasRole(...$roles)) {
            abort(403, 'Anda tidak mempunyai izin untuk menu ini.');
        }

        return $next($request);
    }
}
