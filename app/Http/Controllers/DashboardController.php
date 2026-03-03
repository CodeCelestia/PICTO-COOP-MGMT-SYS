<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Route authenticated users to the proper dashboard context.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasRole('super_admin')) {
            return to_route('super-admin.dashboard');
        }

        if ($user?->hasRole('coop_admin')) {
            return to_route('office-admin.dashboard');
        }

        if ($user?->hasRole('member')) {
            return to_route('member.dashboard');
        }

        // Default dashboard for other roles
        return Inertia::render('Dashboard');
    }
}
