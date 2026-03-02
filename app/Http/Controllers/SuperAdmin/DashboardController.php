<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Activity as ActivityModel;
use App\Models\Committee;
use App\Models\Member;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Show super admin dashboard with system-wide aggregates.
     */
    public function index(): Response
    {
        $recentLogs = [];

        if (Schema::hasTable('activity_log')) {
            $recentLogs = Activity::query()
                ->with('causer:id,name,email')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Activity $log): array => [
                    'id' => $log->id,
                    'description' => $log->description,
                    'event' => $log->event,
                    'log_name' => $log->log_name,
                    'created_at' => $log->created_at?->toDateTimeString(),
                    'causer_name' => $log->causer?->name,
                ])
                ->all();
        }

        return Inertia::render('super-admin/Dashboard', [
            'stats' => [
                'totalUsers' => User::query()->count(),
                'totalMembers' => Member::query()->count(),
                'totalOffices' => Office::query()->count(),
                'totalCommittees' => Committee::query()->count(),
                'totalActivities' => ActivityModel::query()->count(),
                'totalCooperatives' => Schema::hasTable('cooperatives')
                    ? (int) \DB::table('cooperatives')->count()
                    : 0,
                'totalRoles' => Role::query()->count(),
                'totalPermissions' => Permission::query()->count(),
                'totalActivityLogs' => Schema::hasTable('activity_log')
                    ? Activity::query()->count()
                    : 0,
                'totalCoopAdmins' => User::role('coop_admin')->count(),
            ],
            'recentLogs' => $recentLogs,
            'roleSummary' => Role::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->get(['id', 'name'])
                ->map(fn (Role $role): array => [
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                ])
                ->all(),
        ]);
    }
}
