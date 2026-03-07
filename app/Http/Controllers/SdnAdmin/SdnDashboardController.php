<?php

namespace App\Http\Controllers\SdnAdmin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Member;
use App\Models\Office;
use App\Models\PdsMergeQueue;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SdnDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user  = $request->user();
        $sdnId = $user->sdn_id;

        $officeIds = Office::where('sdn_id', $sdnId)->pluck('id');

        return Inertia::render('sdn-admin/Dashboard', [
            'stats' => [
                'offices'         => $officeIds->count(),
                'members'         => Member::whereIn('office_id', $officeIds)->count(),
                'pending_members' => Member::whereIn('office_id', $officeIds)
                                        ->where('status', 'pending')->count(),
                'pds_records'     => PersonalDataSheet::whereIn('office_id', $officeIds)->count(),
                'merge_queue'     => PdsMergeQueue::where('sdn_id', $sdnId)
                                        ->where('status', 'pending')->count(),
                'users'           => User::where('sdn_id', $sdnId)->count(),
            ],
            'offices' => Office::where('sdn_id', $sdnId)
                ->withCount(['members', 'members as pending_members_count' => fn ($q) => $q->where('status', 'pending')])
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'status', 'allow_self_registration']),
            'pendingMembers' => Member::whereIn('office_id', $officeIds)
                ->where('status', 'pending')
                ->with(['pds:id,first_name,last_name,email', 'office:id,name'])
                ->orderByDesc('created_at')
                ->take(10)
                ->get(),
        ]);
    }
}
