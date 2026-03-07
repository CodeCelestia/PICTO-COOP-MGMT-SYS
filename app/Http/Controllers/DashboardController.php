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

        if ($user?->hasRole('coop_sdn_admin')) {
            return to_route('sdn-admin.dashboard');
        }

        if ($user?->hasRole('coop_office_admin')) {
            return to_route('office-admin.dashboard');
        }

        if ($user?->hasRole('member')) {
            // Enforce PDS completion before allowing dashboard access
            if (is_null($user->pds_id)) {
                return to_route('member.complete-pds');
            }
            return to_route('member.dashboard');
        }

        // Fallback: legacy roles or unassigned users
        return Inertia::render('Dashboard');
    }
}
