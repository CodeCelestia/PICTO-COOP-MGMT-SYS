<?php

namespace App\Http\Controllers\OfficeAdmin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeDashboardController extends Controller
{
    /**
     * Show office admin dashboard with their office information
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get the office(s) this user is assigned to with their office role
        $userOffices = $user->offices()
            ->withPivot('office_role_id', 'assigned_at')
            ->with(['users' => function ($query) {
                $query->with('roles:id,name');
            }])
            ->get();

        // For now, we'll use the first office
        $office = $userOffices->first();

        if (!$office) {
            return Inertia::render('office-admin/Dashboard', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'system_roles' => $user->getRoleNames(),
                    'office_position' => null,
                ],
                'office' => null,
                'stats' => null,
            ]);
        }

        // Get the user's office role/position for this office
        $officeRole = \App\Models\OfficeRole::find($office->pivot->office_role_id);

        // Get office statistics
        $stats = [
            'total_members' => $office->users()->count(),
            'total_offices' => $userOffices->count(),
            'status' => $office->status,
        ];

        return Inertia::render('office-admin/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'system_roles' => $user->getRoleNames(),
                'office_position' => $officeRole ? [
                    'id' => $officeRole->id,
                    'name' => $officeRole->name,
                    'display_name' => $officeRole->display_name,
                ] : null,
            ],
            'office' => [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
                'cooperative_type' => $office->cooperative_type,
                'status' => $office->status,
                'contact_email' => $office->contact_email,
                'contact_phone' => $office->contact_phone,
                'city_municipality_name' => $office->city_municipality_name,
                'province_name' => $office->province_name,
                'region_name' => $office->region_name,
                'registration_number' => $office->registration_number,
                'date_registered' => $office->date_registered?->format('M d, Y'),
                'chairperson' => $office->chairperson,
                'general_manager' => $office->general_manager,
            ],
            'stats' => $stats,
            'recentMembers' => $office->users()
                ->with('roles:id,name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $u->getRoleNames(),
                    'created_at' => $u->created_at?->format('M d, Y'),
                ]),
        ]);
    }
}
