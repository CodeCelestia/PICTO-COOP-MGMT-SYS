<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Member;
use App\Models\MemberLoan;
use App\Models\Cooperative;
use App\Models\Activity;
use App\Models\Training;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $authUser = auth()->user();
        $canViewAllCoops = $authUser ? $authUser->can('view-all-cooperatives') : false;
        $canViewReports = $authUser ? $authUser->can('read reports-&-dashboard') : false;
        $isCoopScoped = $authUser ? ($authUser->coop_id && ! $canViewAllCoops) : false;
        $isCoopAdmin = $authUser ? ($isCoopScoped && $canViewReports) : false;
        $isMember = $authUser ? ($authUser->member_id && $authUser->can('read members-profile')) : false;
        $coopId = $authUser?->coop_id;

        $systemStats = $this->getSystemStats();
        $superAdminStats = $canViewAllCoops ? $this->getSuperAdminStats() : null;

        $coopStats = null;
        $coopMembers = [];
        $coopInfo = null;
        $coopTrends = null;
        $memberProfile = null;
        $memberCoop = null;
        $memberLoansCount = 0;
        $memberRecentLoans = [];
        $memberServicesCount = 0;
        $memberActivitiesCount = 0;

        if ($isCoopAdmin && $coopId) {
            $period = $this->normalizeTrendPeriod(request('period', 'month'));
            $trendFilters = $this->resolveTrendFilters();
            $coopData = $this->getCoopDashboard($coopId);
            $coopInfo = $coopData['coopInfo'];
            $coopStats = $coopData['coopStats'];
            $coopMembers = $coopData['coopMembers'];
            $coopTrends = $this->getCoopTrends($coopId, $period, $trendFilters);
        }

        if ($isMember && $authUser?->member_id) {
            $memberData = $this->getMemberDashboard($authUser->member_id);
            $memberProfile = $memberData['memberProfile'];
            $memberCoop = $memberData['memberCoop'];
            $memberLoansCount = $memberData['memberLoansCount'];
            $memberRecentLoans = $memberData['memberRecentLoans'];
            $memberServicesCount = $memberData['memberServicesCount'];
            $memberActivitiesCount = $memberData['memberActivitiesCount'];
        }

        return Inertia::render('Dashboard', [
            'stats' => $systemStats['stats'],
            'usersByRole' => $systemStats['usersByRole'],
            'recentUsers' => $systemStats['recentUsers'],
            'systemTrends' => $systemStats['systemTrends'],
            'sectorDistribution' => $systemStats['sectorDistribution'],
            'isSuperAdmin' => $canViewAllCoops,
            'superAdminStats' => $superAdminStats,
            'isCoopAdmin' => $isCoopAdmin,
            'isMember' => $isMember,
            'coopInfo' => $coopInfo,
            'coopStats' => $coopStats,
            'coopMembers' => $coopMembers,
            'coopTrends' => $coopTrends,
            'memberProfile' => $memberProfile,
            'memberCoop' => $memberCoop,
            'memberLoansCount' => $memberLoansCount,
            'memberRecentLoans' => $memberRecentLoans,
            'memberServicesCount' => $memberServicesCount,
            'memberActivitiesCount' => $memberActivitiesCount,
        ]);
    }

    private function getSystemStats(): array
    {
        $totalUsers = User::count();
        $totalRoles = Role::count();

        $trendStart = Carbon::now()->startOfMonth()->subMonths(5);
        $trendEnd = Carbon::now()->endOfMonth();
        $trendLabels = [];
        $trendKeys = [];

        foreach (CarbonPeriod::create($trendStart, '1 month', $trendEnd) as $point) {
            $trendLabels[] = $point->format('M Y');
            $trendKeys[] = $point->format('Y-m');
        }

        $trendBuckets = [];

        User::query()
            ->whereBetween('created_at', [$trendStart, $trendEnd])
            ->pluck('created_at')
            ->each(function ($createdAt) use (&$trendBuckets): void {
                $key = Carbon::parse($createdAt)->startOfMonth()->format('Y-m');
                $trendBuckets[$key] = ($trendBuckets[$key] ?? 0) + 1;
            });

        $systemTrendValues = array_map(function ($key) use ($trendBuckets) {
            return $trendBuckets[$key] ?? 0;
        }, $trendKeys);

        $sectorGroupExpression = "COALESCE(NULLIF(TRIM(status), ''), 'Unspecified')";

        $sectorRows = Cooperative::query()
            ->selectRaw("{$sectorGroupExpression} as label, COUNT(*) as count")
            ->groupBy(DB::raw($sectorGroupExpression))
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        $sectorLabels = $sectorRows->pluck('label')->map(function ($label) {
            return (string) $label;
        })->values()->all();

        $sectorValues = $sectorRows->pluck('count')->map(function ($count) {
            return (int) $count;
        })->values()->all();

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
            'systemTrends' => [
                'labels' => $trendLabels,
                'registrations' => $systemTrendValues,
            ],
            'sectorDistribution' => [
                'labels' => $sectorLabels,
                'values' => $sectorValues,
                'total' => array_sum($sectorValues),
            ],
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
            'totalActivities' => (int) Activity::where('coop_id', $coopId)->count(),
            'totalTrainings' => (int) Training::where('coop_id', $coopId)->count(),
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
        $memberLoansCount = 0;
        $memberRecentLoans = [];
        $memberServicesCount = 0;
        $memberActivitiesCount = 0;
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

            $loanQuery = MemberLoan::query()
                ->where('member_id', $member->id)
                ->when($member->coop_id, function ($query) use ($member) {
                    $query->where('coop_id', $member->coop_id);
                });

            $memberLoansCount = (int) (clone $loanQuery)->count();

            $memberRecentLoans = (clone $loanQuery)
                ->latest()
                ->take(3)
                ->get()
                ->map(function (MemberLoan $loan) {
                    return [
                        'id' => $loan->id,
                        'principal' => $loan->principal,
                        'status' => $loan->status,
                        'created_at' => optional($loan->created_at)->format('M d, Y'),
                    ];
                })
                ->toArray();

            $memberServicesCount = (int) $member->servicesAvailed()->count();
            $memberActivitiesCount = (int) $member->activityParticipants()->count();
        }

        return [
            'memberProfile' => $memberProfile,
            'memberCoop' => $memberCoop,
            'memberLoansCount' => $memberLoansCount,
            'memberRecentLoans' => $memberRecentLoans,
            'memberServicesCount' => $memberServicesCount,
            'memberActivitiesCount' => $memberActivitiesCount,
        ];
    }

    private function normalizeTrendPeriod(string $period): string
    {
        $allowed = ['day', 'week', 'month', 'year'];

        return in_array($period, $allowed, true) ? $period : 'month';
    }

    private function getCoopTrends(int $coopId, string $period, array $filters = []): array
    {
        [$start, $end, $labels, $keys] = $this->getTrendPeriodConfig($period);

        $activitySeries = $this->buildTrendSeries(
            Activity::query()
                ->where('coop_id', $coopId)
                ->when($filters['activity_status'] ?? null, function ($query, $value) {
                    $query->where('status', $value);
                })
                ->when($filters['activity_category'] ?? null, function ($query, $value) {
                    $query->where('category', $value);
                }),
            'date_started',
            $start,
            $end,
            $period,
            $keys
        );

        $trainingSeries = $this->buildTrendSeries(
            Training::query()
                ->where('coop_id', $coopId)
                ->when($filters['training_status'] ?? null, function ($query, $value) {
                    $query->where('status', $value);
                })
                ->when($filters['training_target_group'] ?? null, function ($query, $value) {
                    $query->where('target_group', $value);
                }),
            'date_conducted',
            $start,
            $end,
            $period,
            $keys
        );

        $memberSeries = $this->buildTrendSeries(
            Member::query()
                ->where('coop_id', $coopId)
                ->when($filters['member_status'] ?? null, function ($query, $value) {
                    $query->where('membership_status', $value);
                }),
            'date_joined',
            $start,
            $end,
            $period,
            $keys
        );

        return [
            'period' => $period,
            'labels' => $labels,
            'activities' => $activitySeries,
            'trainings' => $trainingSeries,
            'members' => $memberSeries,
            'filters' => $filters,
        ];
    }

    private function resolveTrendFilters(): array
    {
        return [
            'member_status' => $this->sanitizeFilter(
                request('member_status'),
                ['Active', 'Suspended', 'Resigned', 'Deceased']
            ),
            'activity_status' => $this->sanitizeFilter(
                request('activity_status'),
                ['Planned', 'In Progress', 'Completed', 'Cancelled']
            ),
            'activity_category' => $this->sanitizeFilter(
                request('activity_category'),
                ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other']
            ),
            'training_status' => $this->sanitizeFilter(
                request('training_status'),
                ['Planned', 'Completed', 'Cancelled', 'Follow-Up Pending']
            ),
            'training_target_group' => $this->sanitizeFilter(
                request('training_target_group'),
                ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'FisherFolk', 'New Members', 'Other']
            ),
        ];
    }

    private function sanitizeFilter(?string $value, array $allowed): ?string
    {
        return in_array($value, $allowed, true) ? $value : null;
    }

    private function getTrendPeriodConfig(string $period): array
    {
        $today = Carbon::now();

        switch ($period) {
            case 'day':
                $start = $today->copy()->startOfDay()->subDays(6);
                $end = $today->copy()->endOfDay();
                $interval = '1 day';
                $labelFormat = 'M d';
                $keyFormat = 'Y-m-d';
                break;
            case 'week':
                $start = $today->copy()->startOfWeek()->subWeeks(7);
                $end = $today->copy()->endOfWeek();
                $interval = '1 week';
                $labelFormat = 'M d';
                $keyFormat = 'o-W';
                break;
            case 'year':
                $start = $today->copy()->startOfYear()->subYears(4);
                $end = $today->copy()->endOfYear();
                $interval = '1 year';
                $labelFormat = 'Y';
                $keyFormat = 'Y';
                break;
            case 'month':
            default:
                $start = $today->copy()->startOfMonth()->subMonths(11);
                $end = $today->copy()->endOfMonth();
                $interval = '1 month';
                $labelFormat = 'M Y';
                $keyFormat = 'Y-m';
                break;
        }

        $labels = [];
        $keys = [];

        foreach (CarbonPeriod::create($start, $interval, $end) as $point) {
            $labels[] = $point->format($labelFormat);
            $keys[] = $point->format($keyFormat);
        }

        return [$start, $end, $labels, $keys];
    }

    private function buildTrendSeries($query, string $dateColumn, Carbon $start, Carbon $end, string $period, array $keys): array
    {
        $dates = $query
            ->whereNotNull($dateColumn)
            ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])
            ->pluck($dateColumn)
            ->map(function ($date) {
                return Carbon::parse($date);
            });

        $buckets = [];

        foreach ($dates as $date) {
            $bucketKey = $this->resolveTrendBucketKey($date, $period);
            $buckets[$bucketKey] = ($buckets[$bucketKey] ?? 0) + 1;
        }

        return array_map(function ($key) use ($buckets) {
            return $buckets[$key] ?? 0;
        }, $keys);
    }

    private function resolveTrendBucketKey(Carbon $date, string $period): string
    {
        switch ($period) {
            case 'week':
                return $date->copy()->startOfWeek()->format('o-W');
            case 'month':
                return $date->copy()->startOfMonth()->format('Y-m');
            case 'year':
                return $date->copy()->startOfYear()->format('Y');
            case 'day':
            default:
                return $date->format('Y-m-d');
        }
    }

    private function getSuperAdminStats(): array
    {
        $totalUsers = User::count();
        $totalCooperatives = Cooperative::count();
        $activeCooperatives = Cooperative::query()
            ->whereRaw("LOWER(TRIM(status)) = ?", ['active'])
            ->count();
        $totalMembers = Member::count();
        $totalActivities = Activity::count();
        $totalTrainings = Training::count();
        $totalRoles = Role::count();
        $totalPermissions = DB::table('permissions')->count();

        // Users by role
        $usersByRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name', 'roles.id')
            ->orderBy('count', 'desc')
            ->get();

        // Recent users
        $recentUsers = User::with('roles')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->diffForHumans(),
                    'account_status' => $user->account_status,
                ];
            });

        // Recent activities
        $recentActivities = Activity::with('cooperative')
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'status' => $activity->status,
                    'cooperative' => $activity->cooperative?->name,
                    'date_started' => optional($activity->date_started)->format('M d, Y'),
                    'category' => $activity->category,
                ];
            });

        // Cooperatives by province
        $coopsByProvince = Cooperative::select('province', DB::raw('count(*) as count'))
            ->groupBy('province')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Members by status
        $membersByStatus = Member::select('membership_status', DB::raw('count(*) as count'))
            ->groupBy('membership_status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->membership_status ?? 'Unspecified' => $item->count];
            });

        // User growth trend
        $trendStart = Carbon::now()->startOfMonth()->subMonths(11);
        $trendEnd = Carbon::now()->endOfMonth();
        $trendLabels = [];
        $trendKeys = [];

        foreach (CarbonPeriod::create($trendStart, '1 month', $trendEnd) as $point) {
            $trendLabels[] = $point->format('M Y');
            $trendKeys[] = $point->format('Y-m');
        }

        $trendBuckets = [];
        User::query()
            ->whereBetween('created_at', [$trendStart, $trendEnd])
            ->pluck('created_at')
            ->each(function ($createdAt) use (&$trendBuckets): void {
                $key = Carbon::parse($createdAt)->startOfMonth()->format('Y-m');
                $trendBuckets[$key] = ($trendBuckets[$key] ?? 0) + 1;
            });

        $trendValues = array_map(function ($key) use ($trendBuckets) {
            return $trendBuckets[$key] ?? 0;
        }, $trendKeys);

        return [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalCooperatives' => $totalCooperatives,
                'activeCooperatives' => $activeCooperatives,
                'totalMembers' => $totalMembers,
                'totalActivities' => $totalActivities,
                'totalTrainings' => $totalTrainings,
                'totalRoles' => $totalRoles,
                'totalPermissions' => $totalPermissions,
            ],
            'usersByRole' => $usersByRole,
            'recentUsers' => $recentUsers,
            'recentActivities' => $recentActivities,
            'coopsByProvince' => $coopsByProvince,
            'membersByStatus' => $membersByStatus,
            'userGrowthTrend' => [
                'labels' => $trendLabels,
                'values' => $trendValues,
            ],
        ];
    }
}
