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
        if ($request->user()?->hasRole('super_admin')) {
            return to_route('super-admin.dashboard');
        }

        return Inertia::render('Dashboard');
    }
}
