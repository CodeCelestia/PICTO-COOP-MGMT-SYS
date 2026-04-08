<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminBypass
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user can view all cooperatives, allow access
        if ($request->user()?->can('view-all-cooperatives')) {
            return $next($request);
        }

        // Otherwise, continue with normal permission checks
        return $next($request);
    }
}
