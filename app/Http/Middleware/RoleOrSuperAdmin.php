<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleOrSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            throw new HttpException(403);
        }

        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        throw new HttpException(403);
    }
}
