<?php

namespace App\Services;

use App\Models\PdsMergeQueue;
use App\Models\PersonalDataSheet;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    /**
     * Run all duplicate checks against the given data payload.
     *
     * @param  array       $data          PDS field data to check
     * @param  int|null    $excludePdsId  Exclude this PDS id from results (for updates)
     * @return array       Array of ['type', 'confidence', 'pds' => PersonalDataSheet]
     */
    public function check(array $data, ?int $excludePdsId = null): array
    {
        $matches  = [];
        $foundIds = [];

        // ── 1. Exact government ID match (highest confidence) ─────────────────
        $idFields = ['gsis_id', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin_no'];
        $hasAnyId = collect($idFields)->contains(fn ($f) => !empty($data[$f]));

        if ($hasAnyId) {
            $query = PersonalDataSheet::query()
                ->when($excludePdsId, fn ($q) => $q->where('id', '!=', $excludePdsId))
                ->where(function ($q) use ($data, $idFields) {
                    foreach ($idFields as $field) {
                        if (!empty($data[$field])) {
                            $q->orWhere($field, $data[$field]);
                        }
                    }
                });

            foreach ($query->get() as $found) {
                if (!in_array($found->id, $foundIds)) {
                    $matches[]  = ['type' => 'exact_id', 'confidence' => 'high', 'pds' => $found];
                    $foundIds[] = $found->id;
                }
            }
        }

        // ── 2. Exact email match ───────────────────────────────────────────────
        if (!empty($data['email'])) {
            $found = PersonalDataSheet::query()
                ->where('email', $data['email'])
                ->when($excludePdsId, fn ($q) => $q->where('id', '!=', $excludePdsId))
                ->first();

            if ($found && !in_array($found->id, $foundIds)) {
                $matches[]  = ['type' => 'exact_email', 'confidence' => 'high', 'pds' => $found];
                $foundIds[] = $found->id;
            }
        }

        // ── 3. Fuzzy name + date of birth match (medium confidence) ───────────
        if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['date_of_birth'])) {
            $found = PersonalDataSheet::query()
                ->whereRaw('LOWER(first_name) = ?', [strtolower($data['first_name'])])
                ->whereRaw('LOWER(last_name) = ?', [strtolower($data['last_name'])])
                ->whereDate('date_of_birth', $data['date_of_birth'])
                ->when($excludePdsId, fn ($q) => $q->where('id', '!=', $excludePdsId))
                ->first();

            if ($found && !in_array($found->id, $foundIds)) {
                $matches[]  = ['type' => 'fuzzy_name_dob', 'confidence' => 'medium', 'pds' => $found];
                $foundIds[] = $found->id;
            }
        }

        return $matches;
    }

    /**
     * Format matches for the frontend (safe subset of PDS data).
     */
    public function formatForFrontend(array $matches, ?int $currentOfficeId = null): array
    {
        return collect($matches)->map(fn ($m) => [
            'type'        => $m['type'],
            'confidence'  => $m['confidence'],
            'pds_id'      => $m['pds']->id,
            'full_name'   => $m['pds']->full_name,
            'email'       => $m['pds']->email,
            'date_of_birth' => $m['pds']->date_of_birth?->toDateString(),
            'same_office' => $m['pds']->office_id === $currentOfficeId,
        ])->all();
    }

    /**
     * Enqueue a merge request for admin review.
     */
    public function queueMerge(
        int $sourcePdsId,
        int $targetPdsId,
        string $matchType,
        ?array $context = null,
        ?int $triggeredBy = null,
        ?int $sdnId = null,
        ?int $officeId = null
    ): PdsMergeQueue {
        return PdsMergeQueue::create([
            'match_type'    => $matchType,
            'source_pds_id' => $sourcePdsId,
            'target_pds_id' => $targetPdsId,
            'triggered_by'  => $triggeredBy,
            'status'        => 'pending',
            'match_context' => $context,
            'sdn_id'        => $sdnId,
            'office_id'     => $officeId,
        ]);
    }
}
