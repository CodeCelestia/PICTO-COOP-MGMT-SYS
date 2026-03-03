<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberDashboardController extends Controller
{
    /**
     * Show member dashboard with their PDS information
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $pds = $user->personalDataSheet;
        
        // Get user's office information from PDS or user's office assignments
        $office = null;
        if ($pds && $pds->office_id) {
            $office = \App\Models\Office::find($pds->office_id);
        }
        if (!$office) {
            $office = $user->offices()->first();
        }

        return Inertia::render('member/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'pds' => $pds ? [
                'id' => $pds->id,
                'first_name' => $pds->first_name,
                'middle_name' => $pds->middle_name,
                'last_name' => $pds->last_name,
                'email' => $pds->email,
                'phone_number' => $pds->phone_number,
                'date_of_birth' => $pds->date_of_birth?->format('F d, Y'),
                'gender' => $pds->gender,
                'civil_status' => $pds->civil_status,
                'city_municipality_name' => $pds->city_municipality_name,
                'province_name' => $pds->province_name,
                'is_complete' => $pds->isComplete(),
                'completion_percentage' => $pds->getCompletionPercentage(),
            ] : null,
            'office' => $office ? [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
            ] : null,
        ]);
    }
}
