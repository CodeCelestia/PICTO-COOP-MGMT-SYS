<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Office;
use App\Models\PersonalDataSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Member::query()
            ->with(['pds', 'office', 'user']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('pds', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('member_number', 'like', "%{$search}%");
        }

        // Filter by office
        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by member type
        if ($request->filled('member_type')) {
            $query->where('member_type', $request->member_type);
        }

        $members = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('super-admin/Members/Index', [
            'members' => $members,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'office_id', 'status', 'member_type']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Generate next member number
        $lastMember = Member::latest('id')->first();
        $nextNumber = $lastMember ? (int) substr($lastMember->member_number, 4) + 1 : 1;
        $suggestedMemberNumber = 'MEM-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return Inertia::render('super-admin/Members/Create', [
            'pdsOptions' => PersonalDataSheet::whereDoesntHave('member')
                ->get()
                ->map(fn ($pds) => [
                    'value' => $pds->id,
                    'label' => "{$pds->first_name} {$pds->last_name} - {$pds->email}",
                    'pds' => $pds,
                ]),
            'offices' => Office::orderBy('name')->get(['id', 'name']),
            'suggestedMemberNumber' => $suggestedMemberNumber,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pds_id' => 'required|exists:personal_data_sheets,id|unique:members,pds_id',
            'office_id' => 'required|exists:offices,id',
            'member_number' => 'required|string|unique:members,member_number',
            'member_type' => 'required|in:regular,associate,provisional',
            'status' => 'required|in:active,inactive,resigned,suspended,deceased',
            'date_joined' => 'required|date',
            'date_approved' => 'nullable|date',
            'share_capital' => 'nullable|numeric|min:0',
            'occupation' => 'nullable|string|max:255',
            'employer' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['approved_by'] = $request->user()->id;
        $validated['approved_at'] = now();

        $member = Member::create($validated);

        activity('member_management')
            ->causedBy($request->user())
            ->performedOn($member)
            ->log('super_admin.created_member');

        return redirect()->route('super-admin.members.show', $member)
            ->with('success', 'Member created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member): Response
    {
        $member->load(['pds', 'office', 'user', 'approvedBy', 'committees.committee', 'activityParticipants.activity']);

        return Inertia::render('super-admin/Members/Show', [
            'member' => $member,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member): Response
    {
        $member->load(['pds', 'office']);

        return Inertia::render('super-admin/Members/Edit', [
            'member' => $member,
            'offices' => Office::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'member_type' => 'required|in:regular,associate,provisional',
            'status' => 'required|in:active,inactive,resigned,suspended,deceased',
            'date_joined' => 'required|date',
            'date_approved' => 'nullable|date',
            'date_left' => 'nullable|date',
            'share_capital' => 'nullable|numeric|min:0',
            'savings_balance' => 'nullable|numeric|min:0',
            'loan_balance' => 'nullable|numeric|min:0',
            'occupation' => 'nullable|string|max:255',
            'employer' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $member->update($validated);

        activity('member_management')
            ->causedBy($request->user())
            ->performedOn($member)
            ->log('super_admin.updated_member');

        return redirect()->route('super-admin.members.show', $member)
            ->with('success', 'Member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member): RedirectResponse
    {
        activity('member_management')
            ->causedBy(request()->user())
            ->performedOn($member)
            ->log('super_admin.deleted_member');

        $member->delete();

        return redirect()->route('super-admin.members.index')
            ->with('success', 'Member deleted successfully.');
    }
}
