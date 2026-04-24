<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::select('*')->with(['causer', 'subject'])
            ->latest();

        // Filter by event type
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by subject type (model)
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Search by description or causer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $activities = $query->paginate(20)->withQueryString();

        // Transform the data for the frontend
        $activities->getCollection()->transform(function ($activity) {
            $changes = $activity->changes;

            if (is_string($changes)) {
                $changes = json_decode($changes, true) ?: [];
            }

            if (!is_array($changes)) {
                $changes = (array) $changes;
            }

            if (!$changes) {
                $changes = [];
            }

            $properties = $activity->properties;
            if (is_string($properties)) {
                $properties = json_decode($properties, true) ?: [];
            } elseif ($properties instanceof \Illuminate\Support\Collection) {
                $properties = $properties->toArray();
            } elseif (!is_array($properties)) {
                $properties = (array) $properties;
            }

            if ($activity->event === 'created' && empty($changes)) {
                $newData = $properties['attributes'] ?? [];
                if (is_string($newData)) {
                    $newData = json_decode($newData, true) ?: [];
                } elseif ($newData instanceof \Illuminate\Support\Collection) {
                    $newData = $newData->toArray();
                } elseif (!is_array($newData)) {
                    $newData = (array) $newData;
                }

                $changes = [];
                foreach ($newData as $field => $value) {
                    $changes[$field] = [
                        'old' => null,
                        'new' => $value,
                    ];
                }
            }

            $oldValues = [];
            $newValues = [];
            foreach ($changes as $field => $change) {
                $oldValues[$field] = $change['old'] ?? null;
                $newValues[$field] = $change['new'] ?? null;
            }

            return [
                'id' => $activity->id,
                'table_name' => class_basename($activity->subject_type),
                'record_id' => $activity->subject_id,
                'description' => $activity->description,
                'event' => $activity->event,
                'action' => ucfirst($activity->event),
                'subject_type' => class_basename($activity->subject_type),
                'subject_id' => $activity->subject_id,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'email' => $activity->causer->email,
                ] : null,
                'changed_by' => $activity->causer?->name ?? 'System',
                'ip_address' => $activity->ip_address,
                'user_name' => $activity->user_name,
                'module_name' => $activity->module_name,
                'changes' => $changes,
                'old_value' => $oldValues,
                'new_value' => $newValues,
                'properties' => $activity->properties,
                'created_at' => $activity->created_at->diffForHumans(),
                'created_at_full' => $activity->created_at->format('M d, Y h:i A'),
                'changed_at' => $activity->created_at->format('M d, Y h:i A'),
            ];
        });

        // Get unique event types and subject types for filters
        $eventTypes = Activity::distinct()->pluck('event')->filter()->values();
        $subjectTypes = Activity::distinct()
            ->pluck('subject_type')
            ->filter()
            ->map(fn($type) => class_basename($type))
            ->unique()
            ->values();

        return Inertia::render('AuditLogs/Index', [
            'activities' => $activities,
            'eventTypes' => $eventTypes,
            'subjectTypes' => $subjectTypes,
            'filters' => $request->only(['search', 'event', 'subject_type']),
        ]);
    }
}
