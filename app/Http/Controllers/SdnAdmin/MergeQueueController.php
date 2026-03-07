<?php

namespace App\Http\Controllers\SdnAdmin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\PdsMergeQueue;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MergeQueueController extends Controller
{
    /** GET /sdn-admin/merge-queue */
    public function index(Request $request)
    {
        $user  = $request->user();
        $sdnId = $user->sdn_id;

        $queue = PdsMergeQueue::where('sdn_id', $sdnId)
            ->with([
                'sourcePds:id,first_name,last_name,email,date_of_birth,gsis_id,sss_no',
                'targetPds:id,first_name,last_name,email,date_of_birth,gsis_id,sss_no',
                'triggeredBy:id,name,email',
            ])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('sdn-admin/MergeQueue', [
            'queue'   => $queue,
            'filters' => ['status' => $request->status ?? ''],
        ]);
    }

    /** POST /sdn-admin/merge-queue/{entry}/approve */
    public function approve(Request $request, PdsMergeQueue $entry)
    {
        $this->authorize('update', $entry->sourcePds);

        $request->validate(['notes' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($entry, $request) {
            // Mark the source PDS as a confirmed duplicate of the target
            $entry->sourcePds->update(['duplicate_of' => $entry->target_pds_id]);

            // Transfer member/user links from source to target if applicable
            Member::where('pds_id', $entry->source_pds_id)
                ->update(['pds_id' => $entry->target_pds_id]);

            User::where('pds_id', $entry->source_pds_id)
                ->update(['pds_id' => $entry->target_pds_id]);

            $entry->update([
                'status'      => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'notes'       => $request->notes,
            ]);

            activity('merge_queue')
                ->causedBy($request->user())
                ->performedOn($entry)
                ->withProperties(['action' => 'merge_approved', 'sdn_id' => $entry->sdn_id])
                ->log('Merge approved: source PDS ' . $entry->source_pds_id . ' → target ' . $entry->target_pds_id);
        });

        return back()->with('success', 'Merge approved and records consolidated.');
    }

    /** POST /sdn-admin/merge-queue/{entry}/reject */
    public function reject(Request $request, PdsMergeQueue $entry)
    {
        $this->authorize('update', $entry->sourcePds);

        $request->validate(['notes' => 'required|string|max:1000']);

        $entry->update([
            'status'      => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'notes'       => $request->notes,
        ]);

        activity('merge_queue')
            ->causedBy($request->user())
            ->performedOn($entry)
            ->withProperties(['action' => 'merge_rejected', 'sdn_id' => $entry->sdn_id])
            ->log('Merge rejected for PDS ' . $entry->source_pds_id);

        return back()->with('success', 'Merge request rejected.');
    }
}
