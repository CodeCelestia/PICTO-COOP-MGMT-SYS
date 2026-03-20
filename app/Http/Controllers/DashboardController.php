<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Member;
use App\Models\Cooperative;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $authUser = auth()->user();
        $isCoopAdmin = $authUser
            ? ($authUser->hasRole('Coop Admin') || $authUser->account_type === 'Coop Admin')
            : false;
        $isMember = $authUser
            ? (!$isCoopAdmin && ($authUser->hasRole('Member') || $authUser->account_type === 'Member'))
            : false;
        $coopId = $authUser?->coop_id;

        $systemStats = $this->getSystemStats();

        $coopStats = null;
        $coopMembers = [];
        $coopInfo = null;
        $memberProfile = null;
        $memberCoop = null;

        if ($isCoopAdmin && $coopId) {
            $coopData = $this->getCoopDashboard($coopId);
            $coopInfo = $coopData['coopInfo'];
            $coopStats = $coopData['coopStats'];
            $coopMembers = $coopData['coopMembers'];
        }

        if ($isMember && $authUser?->member_id) {
            $memberData = $this->getMemberDashboard($authUser->member_id);
            $memberProfile = $memberData['memberProfile'];
            $memberCoop = $memberData['memberCoop'];
        }

        return Inertia::render('Dashboard', [
            'stats' => $systemStats['stats'],
            'usersByRole' => $systemStats['usersByRole'],
            'recentUsers' => $systemStats['recentUsers'],
            'isCoopAdmin' => $isCoopAdmin,
            'isMember' => $isMember,
            'coopInfo' => $coopInfo,
            'coopStats' => $coopStats,
            'coopMembers' => $coopMembers,
            'memberProfile' => $memberProfile,
            'memberCoop' => $memberCoop,
        ]);
    }

    private function getSystemStats(): array
    {
        $totalUsers = User::count();
        $totalRoles = Role::count();

        $usersWithRoles = DB::table('model_has_roles')
            ->distinct('model_id')
            ->count('model_id');

        $usersByRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name', 'roles.id')
            ->orderBy('count', 'desc')
            ->get();

        $recentUsers = User::with('roles')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->diffForHumans(),
                ];
            });

        $usersLastWeek = User::where('created_at', '>=', now()->subWeek())->count();
        $usersGrowth = $totalUsers > 0 ? round(($usersLastWeek / $totalUsers) * 100, 1) : 0;

        return [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalRoles' => $totalRoles,
                'usersWithRoles' => $usersWithRoles,
                'usersGrowth' => $usersGrowth,
            ],
            'usersByRole' => $usersByRole,
            'recentUsers' => $recentUsers,
        ];
    }

    private function getCoopDashboard(int $coopId): array
    {
        $coopInfo = Cooperative::select('id', 'name', 'province', 'city_municipality', 'barangay')
            ->where('id', $coopId)
            ->first();

        $membersQuery = Member::where('coop_id', $coopId);

        $coopStats = [
            'totalMembers' => (int) $membersQuery->count(),
            'activeMembers' => (int) $membersQuery->where('membership_status', 'Active')->count(),
            'inactiveMembers' => (int) $membersQuery->whereIn('membership_status', ['Suspended', 'Resigned', 'Deceased'])->count(),
        ];

        $coopMembers = Member::where('coop_id', $coopId)
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'email' => $member->email,
                    'membership_status' => $member->membership_status,
                    'date_joined' => optional($member->date_joined)->format('M d, Y'),
                ];
            });

        return [
            'coopInfo' => $coopInfo,
            'coopStats' => $coopStats,
            'coopMembers' => $coopMembers,
        ];
    }

    private function getMemberDashboard(int $memberId): array
    {
        $memberProfile = null;
        $memberCoop = null;
        $member = Member::with('cooperative')->find($memberId);

        if ($member) {
            $memberProfile = [
                'id' => $member->id,
                'name' => $member->full_name,
                'email' => $member->email,
                'phone' => $member->phone,
                'membership_status' => $member->membership_status,
                'membership_type' => $member->membership_type,
                'sector' => $member->sector,
                'date_joined' => optional($member->date_joined)->format('M d, Y'),
                'address' => $member->address,
                'city_municipality' => $member->city_municipality,
                'province' => $member->province,
            ];

            if ($member->cooperative) {
                $memberCoop = [
                    'id' => $member->cooperative->id,
                    'name' => $member->cooperative->name,
                    'province' => $member->cooperative->province,
                    'city_municipality' => $member->cooperative->city_municipality,
                    'barangay' => $member->cooperative->barangay,
                    'status' => $member->cooperative->status,
                ];
            }
        }

        return [
            'memberProfile' => $memberProfile,
            'memberCoop' => $memberCoop,
        ];
    }
}
