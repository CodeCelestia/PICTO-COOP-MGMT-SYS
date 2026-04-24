<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

trait LogsActivityWithChanges
{
    protected function logDetailedActivity(string $action, Model $model, array $oldData, array $newData, string $moduleName): Activity
    {
        $changes = $this->extractChanges($oldData, $newData);

        $activity = activity()
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->withProperties([
                'changes' => $changes,
                'module_name' => $moduleName,
            ])
            ->event($action)
            ->log("{$moduleName} {$action}");

        DB::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'))
            ->where('id', $activity->id)
            ->update([
                'changes' => json_encode($changes),
                'ip_address' => request()?->ip() ?? request()->getClientIp() ?? '127.0.0.1',
                'user_name' => auth()->user()?->name ?? 'System',
                'module_name' => $moduleName,
                'action' => $action,
            ]);

        return $activity;
    }

    protected function extractChanges(array $oldData, array $newData): array
    {
        $excluded = ['created_at', 'updated_at', 'deleted_at'];
        $changes = [];

        foreach (array_unique(array_merge(array_keys($oldData), array_keys($newData))) as $field) {
            if (in_array($field, $excluded, true)) {
                continue;
            }

            $oldValue = $oldData[$field] ?? null;
            $newValue = $newData[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
