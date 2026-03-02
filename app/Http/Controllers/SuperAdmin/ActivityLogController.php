<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Show paginated system activity logs.
     */
    public function index(): Response
    {
        if (! Schema::hasTable('activity_log')) {
            $emptyLogs = new LengthAwarePaginator([], 0, 20);

            return Inertia::render('super-admin/Logs', [
                'logs' => $emptyLogs,
            ]);
        }

        $logs = Activity::query()
            ->with('causer:id,name,email')
            ->latest()
            ->paginate(20)
            ->through(fn (Activity $log): array => [
                'id' => $log->id,
                'description' => $log->description,
                'event' => $log->event,
                'log_name' => $log->log_name,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'causer_name' => $log->causer?->name,
                'causer_email' => $log->causer?->email,
                'properties' => $log->properties,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('super-admin/Logs', [
            'logs' => $logs,
        ]);
    }
}
