<?php

namespace App\Http\Controllers;

use App\Models\AccountStatusHistory;
use App\Models\LoginSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;

class ActivityLogsController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', $request->route('tab', 'audit'));

        $auditData = null;
        $eventTypes = [];
        $subjectTypes = [];
        $auditFilters = $request->only(['search', 'event', 'subject_type']);

        if ($tab === 'audit') {
            $query = Activity::with(['causer', 'subject'])->latest();

            if ($request->filled('event')) {
                $query->where('event', $request->event);
            }

            if ($request->filled('subject_type')) {
                $query->where('subject_type', $request->subject_type);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('causer', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $auditData = $query->paginate(20)->withQueryString();

            $auditData->getCollection()->transform(function ($activity) {
                $subjectType = $activity->subject_type;
                $tableName = null;

                if ($subjectType && class_exists($subjectType)) {
                    $tableName = (new $subjectType)->getTable();
                }

                if (!$tableName) {
                    $tableName = Str::snake(class_basename($subjectType ?? ''));
                }

                return [
                    'id' => $activity->id,
                    'table_name' => $tableName,
                    'record_id' => $activity->subject_id,
                    'action' => strtoupper((string) $activity->event),
                    'changed_by' => $activity->causer ? $activity->causer->name : 'System',
                    'changed_at' => $activity->created_at?->format('M d, Y h:i A'),
                    'old_value' => $activity->properties['old'] ?? null,
                    'new_value' => $activity->properties['attributes'] ?? null,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'subject_type' => class_basename($activity->subject_type),
                    'subject_id' => $activity->subject_id,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'created_at_full' => $activity->created_at->format('M d, Y h:i A'),
                ];
            });

            $eventTypes = Activity::distinct()->pluck('event')->filter()->values();
            $subjectTypes = Activity::distinct()
                ->pluck('subject_type')
                ->filter()
                ->map(fn($type) => class_basename($type))
                ->unique()
                ->values();
        }

        $sessionData = null;
        $sessionFilters = $request->only(['search', 'status', 'user_id', 'date_from', 'date_to']);

        if ($tab === 'sessions') {
            $query = LoginSession::with('user:id,name,email')
                ->orderBy('login_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('ip_address', 'like', "%{$search}%");
            }

            if ($request->filled('status')) {
                $query->where('login_status', $request->status);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('login_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('login_at', '<=', $request->date_to);
            }

            $sessionData = $query->paginate(20)->withQueryString();

            $sessionData->getCollection()->transform(function ($session) {
                $duration = null;
                if ($session->logout_at) {
                    $duration = $session->login_at->diffInMinutes($session->logout_at);
                }
                $session->duration_minutes = $duration;
                return $session;
            });
        }

        $accountData = null;
        $accountFilters = $request->only(['search', 'new_status', 'user_id', 'date_from', 'date_to']);

        if ($tab === 'accounts') {
            $query = AccountStatusHistory::with('user:id,name,email,account_status')
                ->orderBy('changed_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('changed_by', 'like', "%{$search}%");
            }

            if ($request->filled('new_status')) {
                $query->where('new_status', $request->new_status);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('changed_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('changed_at', '<=', $request->date_to);
            }

            $accountData = $query->paginate(20)->withQueryString();
        }

        return Inertia::render('ActivityLogs/Index', [
            'tab' => $tab,
            'audit' => $auditData,
            'sessions' => $sessionData,
            'accounts' => $accountData,
            'eventTypes' => $eventTypes,
            'subjectTypes' => $subjectTypes,
            'filters' => [
                'audit' => $auditFilters,
                'sessions' => $sessionFilters,
                'accounts' => $accountFilters,
            ],
        ]);
    }
}
