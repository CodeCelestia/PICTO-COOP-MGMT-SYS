<?php

namespace App\Http\Controllers;

use App\Models\ActivityParticipant;
use App\Models\Member;
use App\Models\MemberLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class MemberPortalController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        if (!$user?->member_id) {
            abort(403);
        }

        $member = Member::with('cooperative')
            ->where('id', $user->member_id)
            ->firstOrFail();

        $servicesCount = $member->servicesAvailed()->count();
        $activitiesCount = ActivityParticipant::where('member_id', $member->id)->count();

        $sectorHistory = $member->sectorHistory()
            ->latest('changed_at')
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'previous_sector' => $history->previous_sector,
                    'new_sector' => $history->new_sector,
                    'previous_livelihood' => $history->previous_livelihood,
                    'new_livelihood' => $history->new_livelihood,
                    'change_reason' => $history->change_reason,
                    'changed_by' => $history->changed_by,
                    'changed_at' => optional($history->changed_at)->toDateTimeString(),
                ];
            });

        return Inertia::render('Members/Portal', [
            'member' => $member,
            'cooperative' => $member->cooperative,
            'sectorHistory' => $sectorHistory,
            'servicesCount' => $servicesCount,
            'activitiesCount' => $activitiesCount,
        ]);
    }

    public function servicesAvailed(Request $request): Response
    {
        $user = $request->user();

        if (!$user?->member_id) {
            abort(403);
        }

        $member = Member::with('cooperative')
            ->where('id', $user->member_id)
            ->firstOrFail();

        $services = $member->servicesAvailed()
            ->latest('date_availed')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_type' => $service->service_type,
                    'service_detail' => $service->service_detail,
                    'date_availed' => optional($service->date_availed)->toDateString(),
                    'amount' => $service->amount,
                    'status' => $service->status,
                    'reference_no' => $service->reference_no,
                    'remarks' => $service->remarks,
                    'recorded_by' => $service->recorded_by,
                ];
            });

        return Inertia::render('Members/PortalServices', [
            'member' => $member,
            'services' => $services,
        ]);
    }

    public function activityParticipants(Request $request): Response
    {
        $user = $request->user();

        if (!$user?->member_id) {
            abort(403);
        }

        $member = Member::with('cooperative')
            ->where('id', $user->member_id)
            ->firstOrFail();

        $participants = ActivityParticipant::with(['activity.cooperative'])
            ->where('member_id', $member->id)
            ->latest('date_joined')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'activity' => [
                        'id' => $participant->activity?->id,
                        'title' => $participant->activity?->title,
                        'category' => $participant->activity?->category,
                        'status' => $participant->activity?->status,
                        'cooperative' => $participant->activity?->cooperative?->name,
                    ],
                    'role' => $participant->role,
                    'date_joined' => optional($participant->date_joined)->toDateString(),
                    'is_beneficiary' => (bool) $participant->is_beneficiary,
                    'remarks' => $participant->remarks,
                ];
            });

        return Inertia::render('Members/PortalActivities', [
            'member' => $member,
            'participants' => $participants,
        ]);
    }

    public function trainings(Request $request): Response
    {
        $user = $request->user();

        if (! $user?->member_id) {
            abort(403);
        }

        $member = Member::with('cooperative')
            ->where('id', $user->member_id)
            ->firstOrFail();

        $trainingParticipants = $member->trainingParticipants()
            ->with(['training.cooperative'])
            ->latest('created_at')
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'training' => [
                        'id' => $participant->training?->id,
                        'title' => $participant->training?->title,
                        'date_conducted' => optional($participant->training?->date_conducted)->toDateString(),
                        'status' => $participant->training?->status,
                        'cooperative' => $participant->training?->cooperative?->name,
                    ],
                    'outcome' => $participant->outcome,
                    'certificate_no' => $participant->certificate_no,
                    'certificate_date' => optional($participant->certificate_date)->toDateString(),
                    'remarks' => $participant->remarks,
                ];
            });

        return Inertia::render('Members/PortalTrainings', [
            'member' => $member,
            'participants' => $trainingParticipants,
        ]);
    }

    public function loans(Request $request): Response
    {
        $user = $request->user();

        if (! $user?->member_id) {
            abort(403);
        }

        if (! $user->can('read finance-member-loans') && ! $user->can('apply-own finance-member-loans')) {
            abort(403);
        }

        $member = Member::with('cooperative')
            ->where('id', $user->member_id)
            ->firstOrFail();

        $loans = MemberLoan::query()
            ->where('member_id', $member->id)
            ->when($member->coop_id, function ($query) use ($member) {
                $query->where('coop_id', $member->coop_id);
            })
            ->withSum([
                'payments as paid_amount' => function ($query) {
                    $query->whereIn('status', ['Paid', 'Partial']);
                },
            ], 'amount_paid')
            ->latest()
            ->get()
            ->map(function (MemberLoan $loan) {
                $baseAmount = (float) ($loan->amount_disbursed ?? $loan->principal);
                $paidAmount = (float) ($loan->paid_amount ?? 0);

                return [
                    'id' => $loan->id,
                    'principal' => $loan->principal,
                    'interest_rate' => $loan->interest_rate,
                    'term_months' => $loan->term_months,
                    'status' => $loan->status,
                    'purpose' => $loan->purpose,
                    'created_at' => optional($loan->created_at)->toDateString(),
                    'amount_disbursed' => $loan->amount_disbursed,
                    'paid_amount' => $paidAmount,
                    'remaining_balance' => max(0, $baseAmount - $paidAmount),
                ];
            });

        return Inertia::render('Members/PortalLoans', [
            'member' => $member,
            'loans' => $loans,
            'permissions' => [
                'can_view_details' => $user->can('read finance-member-loans'),
            ],
        ]);
    }

    public function showLoan(Request $request, MemberLoan $loan): Response
    {
        $user = $request->user();

        if (! $user?->member_id) {
            abort(403);
        }

        if (! $user->can('read finance-member-loans') && ! $user->can('apply-own finance-member-loans')) {
            abort(403);
        }

        if ($loan->member_id !== $user->member_id) {
            abort(403);
        }

        if ($user->coop_id && $loan->coop_id !== $user->coop_id) {
            abort(403);
        }

        $loan->load(['payments']);

        return Inertia::render('Members/PortalLoanShow', [
            'loan' => [
                'id' => $loan->id,
                'status' => $loan->status,
                'principal' => $loan->principal,
                'interest_rate' => $loan->interest_rate,
                'term_months' => $loan->term_months,
                'purpose' => $loan->purpose,
                'created_at' => optional($loan->created_at)->toDateString(),
            ],
            'repaymentSchedule' => $loan->getRepaymentSchedule()->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'due_date' => optional($payment->due_date)->toDateString(),
                    'total_due' => $payment->total_due,
                    'status' => $payment->status,
                ];
            }),
            'remainingBalance' => $loan->getRemainingBalance(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user?->member_id) {
            abort(403);
        }

        $member = Member::where('id', $user->member_id)->firstOrFail();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'address' => ['nullable', 'string'],
            'region' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'city_municipality' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('members', 'email')->ignore($member->id),
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $member->update($validated);

        $user->update([
            'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
            'email' => $validated['email'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo' => $path]);
        }

        return redirect()->route('member-portal.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
