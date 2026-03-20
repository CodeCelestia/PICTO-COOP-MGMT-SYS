<?php

namespace App\Http\Controllers;

use App\Models\ActivityParticipant;
use App\Models\Member;
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
