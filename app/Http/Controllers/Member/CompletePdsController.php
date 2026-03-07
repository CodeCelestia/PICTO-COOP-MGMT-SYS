<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\PersonalDataSheet;
use App\Notifications\NewPendingMember;
use App\Services\DuplicateDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompletePdsController extends Controller
{
    public function __construct(private DuplicateDetectionService $duplicates) {}

    /** GET /member/complete-pds */
    public function show(Request $request): Response
    {
        $user = $request->user()->load(['personalDataSheet', 'office', 'member']);

        return Inertia::render('member/CompletePds', [
            'existingPds' => $user->personalDataSheet,
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'status'   => $user->status,
                'pds_id'   => $user->pds_id,
                'office_id' => $user->office_id,
            ],
            'member' => $user->member ? [
                'status'  => $user->member->status,
                'member_number' => $user->member->member_number,
            ] : null,
            'office' => $user->office ? [
                'id'                     => $user->office->id,
                'name'                   => $user->office->name,
                'allow_self_registration' => $user->office->allow_self_registration,
            ] : null,
            'duplicateMatches' => $request->session()->get('duplicate_matches', []),
        ]);
    }

    /** POST /member/complete-pds */
    public function store(Request $request)
    {
        $user = $request->user()->load('office');

        $validated = $request->validate([
            // Required identity fields
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'middle_name'    => 'nullable|string|max:255',
            'name_extension' => 'nullable|string|max:20',
            'date_of_birth'  => 'required|date',
            'gender'         => 'required|in:Male,Female,Other',
            'citizenship'    => 'required|string|max:100',
            'email'          => 'required|email|max:255',
            'civil_status'   => 'nullable|string|max:50',
            'place_of_birth' => 'nullable|string|max:255',
            'height'         => 'nullable|numeric|min:0',
            'weight'         => 'nullable|numeric|min:0',
            'blood_type'     => 'nullable|string|max:5',
            'phone_number'   => 'nullable|string|max:20',
            // Government IDs
            'gsis_id'        => 'nullable|string|max:50',
            'sss_no'         => 'nullable|string|max:50',
            'philhealth_no'  => 'nullable|string|max:50',
            'pagibig_no'     => 'nullable|string|max:50',
            'tin_no'         => 'nullable|string|max:50',
            // Address (minimum)
            'province_name'           => 'nullable|string|max:255',
            'city_municipality_name'  => 'nullable|string|max:255',
            // Explicit link to existing PDS (user chose to link after seeing duplicate warning)
            'link_to_pds_id'          => 'nullable|integer|exists:personal_data_sheets,id',
            // User confirmed no duplicate after being warned
            'confirmed_no_duplicate'  => 'nullable|boolean',
        ]);

        // ── Duplicate detection ───────────────────────────────────────────────
        $rawMatches = $this->duplicates->check($validated, $user->pds_id);

        if (
            !empty($rawMatches)
            && !$request->filled('link_to_pds_id')
            && !$request->boolean('confirmed_no_duplicate')
        ) {
            $formatted = $this->duplicates->formatForFrontend($rawMatches, $user->office_id);
            return back()->with('duplicate_matches', $formatted);
        }

        // ── Atomic transaction ────────────────────────────────────────────────
        $result = DB::transaction(function () use ($user, $validated, $request, $rawMatches) {

            if ($request->filled('link_to_pds_id')) {
                // User is linking to a pre-existing PDS
                $pds = PersonalDataSheet::findOrFail($validated['link_to_pds_id']);
                $user->update(['pds_id' => $pds->id]);
            } else {
                // Create a brand-new PDS
                $pds = PersonalDataSheet::create([
                    ...$validated,
                    'office_id'  => $user->office_id,
                    'created_by' => $user->id,
                ]);
                $user->update(['pds_id' => $pds->id]);

                // Queue any suspected duplicates for admin review
                foreach ($rawMatches as $match) {
                    $this->duplicates->queueMerge(
                        $pds->id,
                        $match['pds']->id,
                        $match['type'],
                        ['confidence' => $match['confidence']],
                        $user->id,
                        $user->sdn_id,
                        $user->office_id,
                    );
                }
            }

            // Determine if office requires admin approval before activating member
            $requiresApproval = $user->office && !$user->office->allow_self_registration;

            // Create or update the member record
            $member = Member::updateOrCreate(
                ['pds_id' => $pds->id],
                [
                    'user_id'       => $user->id,
                    'office_id'     => $user->office_id,
                    'member_number' => Member::generateNumber($user->office_id),
                    'status'        => $requiresApproval ? 'pending' : 'active',
                    'date_joined'   => now(),
                ]
            );

            // Activate user immediately if no approval required
            if (!$requiresApproval) {
                $user->update(['status' => 'active']);
            }

            activity('pds')
                ->causedBy($user)
                ->performedOn($pds)
                ->withProperties([
                    'sdn_id'    => $user->sdn_id,
                    'office_id' => $user->office_id,
                    'event'     => 'pds.completed',
                ])
                ->log('Member completed PDS onboarding');

            return compact('pds', 'member', 'requiresApproval');
        });

        // ── Notify relevant office admins if pending approval ─────────────────
        if ($result['requiresApproval']) {
            $this->notifyOfficeAdmins($user, $result['member']);
        }

        $message = $result['requiresApproval']
            ? 'Your PDS has been submitted and is pending approval from your office administrator.'
            : 'PDS completed successfully! Welcome to the cooperative.';

        return redirect()->route('member.dashboard')->with('success', $message);
    }

    private function notifyOfficeAdmins($user, $member): void
    {
        if (!$user->office_id) return;

        $admins = \App\Models\User::query()
            ->where('office_id', $user->office_id)
            ->get()
            ->filter(fn ($u) => $u->hasAnyRole(['coop_office_admin', 'coop_sdn_admin']));

        foreach ($admins as $admin) {
            $admin->notify(new NewPendingMember($member, $user));
        }
    }
}
