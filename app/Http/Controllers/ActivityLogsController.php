<?php

namespace App\Http\Controllers;

use App\Models\Activity as ActivityRecord;
use App\Models\AccountStatusHistory;
use App\Models\Cooperative;
use App\Models\LoginSession;
use App\Models\Member;
use App\Models\MemberLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as ActivityLogEntry;

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
            $query = ActivityLogEntry::with(['causer', 'subject'])
                ->orderByDesc('created_at')
                ->orderByDesc('id');

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

            $mappedActivities = $auditData->getCollection()->map(function ($activity) {
                $subjectType = $activity->subject_type;
                $tableName = null;
                $recordName = $this->resolveRecordName($activity);
                $moduleName = class_basename((string) $subjectType);
                $actorName = $activity->causer?->name ?? 'System';
                $properties = $activity->properties?->toArray() ?? [];
                $fieldChanges = $this->normalizeFieldChanges(
                    $properties,
                    (string) $activity->event,
                    class_basename((string) $subjectType),
                    $recordName
                );
                $description = $fieldChanges['description'] ?? $activity->description;
                $humanDescription = in_array(strtolower((string) $activity->event), ['deleted', 'restored'], true)
                    ? $description
                    : $this->buildHumanDescription($activity->event, $actorName, $moduleName ?: $tableName, $recordName);
                $batchGroup = $activity->batch_uuid
                    ?? sprintf('%s:%s', $activity->causer_id ?? 'system', $activity->created_at?->format('Y-m-d H:i:s'));

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
                    'record_name' => $recordName,
                    'module_name' => $moduleName ?: $tableName,
                    'action' => strtoupper((string) $activity->event),
                    'changed_by' => $activity->causer ? $activity->causer->name : 'System',
                    'user_name' => $actorName,
                    'changed_at' => $activity->created_at?->format('M d, Y h:i A'),
                    'old_value' => $fieldChanges['old_value'],
                    'new_value' => $fieldChanges['new_value'],
                    'field_changes' => $fieldChanges['rows'],
                    'description' => $description,
                    'human_description' => $humanDescription,
                    'event' => $activity->event,
                    'subject_type' => class_basename($activity->subject_type),
                    'subject_id' => $activity->subject_id,
                    'ip_address' => data_get($properties, 'ip_address') ?: $activity->ip_address,
                    'batch_group' => $batchGroup,
                    'created_at_raw' => $activity->created_at?->timestamp,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'properties' => $properties,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'created_at_full' => $activity->created_at->format('M d, Y h:i A'),
                ];
            });

            $auditData->setCollection($this->deduplicateBatchEntries($mappedActivities));

            $eventTypes = ActivityLogEntry::distinct()->pluck('event')->filter()->values();
            $subjectTypes = ActivityLogEntry::distinct()
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

    private function resolveRecordName(ActivityLogEntry $activity): string
    {
        $subject = $activity->subject;

        if ($subject) {
            foreach (['title', 'name', 'full_name', 'display_name', 'description'] as $field) {
                $value = data_get($subject, $field);
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }

            $firstName = data_get($subject, 'first_name');
            $lastName = data_get($subject, 'last_name');
            if (is_string($firstName) || is_string($lastName)) {
                $fullName = trim(($firstName ?? '').' '.($lastName ?? ''));
                if ($fullName !== '') {
                    return $fullName;
                }
            }

            if (class_basename((string) $activity->subject_type) === 'MemberLoan') {
                $memberName = data_get($subject, 'member.full_name')
                    ?? trim((string) data_get($subject, 'member.first_name', '').' '.(string) data_get($subject, 'member.last_name', ''));

                if ($memberName !== '') {
                    return sprintf('Loan #%s (%s)', (string) data_get($subject, 'id', $activity->subject_id), $memberName);
                }
            }
        }

        $recordId = $activity->subject_id;
        $subjectType = class_basename((string) $activity->subject_type);

        if (!$recordId) {
            return 'Record';
        }

        if ($subjectType === 'Activity') {
            $title = ActivityRecord::query()->whereKey($recordId)->value('title');
            if (is_string($title) && trim($title) !== '') {
                return $title;
            }
        }

        if ($subjectType === 'Cooperative') {
            $name = Cooperative::query()->whereKey($recordId)->value('name');
            if (is_string($name) && trim($name) !== '') {
                return $name;
            }
        }

        if ($subjectType === 'Member') {
            $member = Member::query()->select(['id', 'full_name', 'first_name', 'last_name'])->find($recordId);
            if ($member) {
                $fullName = $member->full_name ?: trim(($member->first_name ?? '').' '.($member->last_name ?? ''));
                if ($fullName !== '') {
                    return $fullName;
                }
            }
        }

        if ($subjectType === 'MemberLoan') {
            $loan = MemberLoan::query()->with('member:id,full_name,first_name,last_name')->find($recordId);
            if ($loan) {
                $memberName = $loan->member?->full_name ?: trim(($loan->member?->first_name ?? '').' '.($loan->member?->last_name ?? ''));
                return $memberName !== ''
                    ? sprintf('Loan #%s (%s)', (string) $loan->id, $memberName)
                    : sprintf('Loan #%s', (string) $loan->id);
            }
        }

        return 'Record #'.$recordId;
    }

    private function buildHumanDescription(string $event, string $actorName, string $moduleName, string $recordName): string
    {
        $module = trim($moduleName) !== '' ? $moduleName : 'Record';
        $record = trim($recordName) !== '' ? $recordName : 'Record';

        return match (strtolower($event)) {
            'created' => sprintf('%s created a new %s: %s', $actorName, $module, $record),
            'updated' => sprintf('%s updated %s: %s', $actorName, $module, $record),
            'deleted' => sprintf('%s deleted %s: %s', $actorName, $module, $record),
            'restored' => sprintf('%s restored %s: %s', $actorName, $module, $record),
            default => sprintf('%s performed %s on %s: %s', $actorName, strtoupper($event), $module, $record),
        };
    }

    private function normalizeFieldChanges(
        array $properties,
        ?string $event = null,
        string $subjectType = 'Record',
        string $recordName = 'Record'
    ): array
    {
        $skipFields = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'ip_address',
            'module_name',
            'module',
            'causer_id',
            'causer_type',
            'subject_id',
            'subject_type',
        ];
        $fieldChanges = [];
        $oldValues = [];
        $newValues = [];
        $normalizedEvent = strtolower((string) $event);

        if ($normalizedEvent === 'deleted') {
            return [
                'rows' => [],
                'old_value' => [],
                'new_value' => [],
                'description' => sprintf('Deleted %s: %s', $subjectType ?: 'Record', $recordName ?: 'Record'),
            ];
        }

        if ($normalizedEvent === 'restored') {
            return [
                'rows' => [],
                'old_value' => [],
                'new_value' => [],
                'description' => sprintf('Restored %s: %s', $subjectType ?: 'Record', $recordName ?: 'Record'),
            ];
        }

        $changes = is_array($properties['changes'] ?? null) ? $properties['changes'] : [];
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];
        $attributes = is_array($properties['attributes'] ?? null) ? $properties['attributes'] : [];

        if ($normalizedEvent === 'created') {
            if (!empty($changes)) {
                foreach ($changes as $field => $change) {
                    if (in_array($field, $skipFields, true)) {
                        continue;
                    }

                    $newValue = is_array($change) ? ($change['new'] ?? null) : $change;
                    $newValue = $this->normalizeComparableValue($newValue);

                    if ($newValue === null || $newValue === '') {
                        continue;
                    }

                    $fieldChanges[] = [
                        'field' => (string) $field,
                        'old' => null,
                        'new' => $newValue,
                    ];
                    $oldValues[(string) $field] = null;
                    $newValues[(string) $field] = $newValue;
                }
            } else {
                foreach ($attributes as $field => $newValue) {
                    if (in_array($field, $skipFields, true)) {
                        continue;
                    }

                    $newValue = $this->normalizeComparableValue($newValue);
                    if ($newValue === null || $newValue === '') {
                        continue;
                    }

                    $fieldChanges[] = [
                        'field' => (string) $field,
                        'old' => null,
                        'new' => $newValue,
                    ];
                    $oldValues[(string) $field] = null;
                    $newValues[(string) $field] = $newValue;
                }
            }

            return $this->deduplicateFieldChanges($fieldChanges, $oldValues, $newValues);
        }

        $fields = array_values(array_unique(array_merge(array_keys($old), array_keys($attributes), array_keys($changes))));

        foreach ($fields as $field) {
            if (in_array($field, $skipFields, true)) {
                continue;
            }

            $oldValue = array_key_exists($field, $old)
                ? $old[$field]
                : (is_array($changes[$field] ?? null) ? ($changes[$field]['old'] ?? null) : null);

            $newValue = array_key_exists($field, $attributes)
                ? $attributes[$field]
                : (is_array($changes[$field] ?? null) ? ($changes[$field]['new'] ?? null) : null);

            $oldValue = $this->normalizeComparableValue($oldValue);
            $newValue = $this->normalizeComparableValue($newValue);

            if ($oldValue === $newValue) {
                continue;
            }

            $fieldChanges[] = [
                'field' => (string) $field,
                'old' => $oldValue,
                'new' => $newValue,
            ];
            $oldValues[(string) $field] = $oldValue;
            $newValues[(string) $field] = $newValue;
        }

        return $this->deduplicateFieldChanges($fieldChanges, $oldValues, $newValues);
    }

    private function normalizeComparableValue(mixed $value): mixed
    {
        if (is_string($value) && str_contains($value, 'T')) {
            return substr($value, 0, 10);
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value;
    }

    private function deduplicateFieldChanges(array $rows, array $oldValues, array $newValues): array
    {
        $seen = [];
        $dedupedRows = [];
        $dedupedOld = [];
        $dedupedNew = [];

        foreach ($rows as $row) {
            $field = (string) ($row['field'] ?? '');
            $old = $row['old'] ?? null;
            $new = $row['new'] ?? null;
            $signature = sprintf('%s|%s|%s', $field, (string) $old, (string) $new);

            if (isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;
            $dedupedRows[] = $row;
            $dedupedOld[$field] = $oldValues[$field] ?? $old;
            $dedupedNew[$field] = $newValues[$field] ?? $new;
        }

        return [
            'rows' => $dedupedRows,
            'old_value' => $dedupedOld,
            'new_value' => $dedupedNew,
        ];
    }

    private function deduplicateBatchEntries(Collection $activities): Collection
    {
        $keepIds = [];
        $activitiesByBatch = $activities->groupBy('batch_group');

        foreach ($activitiesByBatch as $batchEntries) {
            $seen = [];

            foreach ($batchEntries->sortBy('id') as $entry) {
                $key = sprintf(
                    '%s|%s|%s',
                    (string) ($entry['subject_type'] ?? ''),
                    (string) ($entry['subject_id'] ?? ''),
                    strtolower((string) ($entry['event'] ?? ''))
                );

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $keepIds[(int) ($entry['id'] ?? 0)] = true;
            }
        }

        return $activities
            ->filter(fn ($entry) => isset($keepIds[(int) ($entry['id'] ?? 0)]))
            ->values();
    }
}
