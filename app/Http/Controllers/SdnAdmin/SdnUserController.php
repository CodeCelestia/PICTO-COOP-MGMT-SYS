<?php

namespace App\Http\Controllers\SdnAdmin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Sdn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SdnUserController extends Controller
{
    /** GET /sdn-admin/users */
    public function index(Request $request)
    {
        $sdnId = $request->user()->sdn_id;

        $users = User::with(['personalDataSheet:id,first_name,last_name', 'office:id,name'])
            ->where('sdn_id', $sdnId)
            ->when($request->office_id, fn ($q) => $q->where('office_id', $request->office_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $offices = Office::where('sdn_id', $sdnId)->get(['id', 'name']);

        return Inertia::render('sdn-admin/Users/Index', [
            'users'   => $users,
            'offices' => $offices,
            'filters' => $request->only(['search', 'office_id', 'status']),
        ]);
    }

    /** POST /sdn-admin/users/{user}/suspend */
    public function suspend(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update(['status' => 'suspended']);

        activity('users')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['action' => 'suspended', 'sdn_id' => $user->sdn_id])
            ->log('User suspended by SDN admin');

        return back()->with('success', "User {$user->name} has been suspended.");
    }

    /** POST /sdn-admin/users/{user}/activate */
    public function activate(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update(['status' => 'active']);

        activity('users')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['action' => 'activated', 'sdn_id' => $user->sdn_id])
            ->log('User activated by SDN admin');

        return back()->with('success', "User {$user->name} has been activated.");
    }

    /** POST /sdn-admin/users - Create user from PDS (admin flow) */
    public function store(Request $request)
    {
        $sdnId = $request->user()->sdn_id;

        $validated = $request->validate([
            'pds_id'   => 'required|exists:personal_data_sheets,id',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:member,coop_office_admin',
            'office_id' => 'nullable|exists:offices,id',
        ]);

        // Ensure office belongs to this SDN
        if ($validated['office_id']) {
            $office = Office::where('id', $validated['office_id'])
                ->where('sdn_id', $sdnId)
                ->firstOrFail();
        }

        $user = User::create([
            'name'                => $request->name ?? 'New User',
            'email'               => $validated['email'],
            'password'            => Hash::make($validated['password']),
            'pds_id'              => $validated['pds_id'],
            'sdn_id'              => $sdnId,
            'office_id'           => $validated['office_id'] ?? null,
            'status'              => 'active',   // Admin-created users are active immediately
            'must_change_password' => true,      // Force password change on first login
        ]);

        $user->assignRole($validated['role']);

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        activity('users')
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties([
                'action'  => 'admin_created_user',
                'sdn_id'  => $sdnId,
                'role'    => $validated['role'],
            ])
            ->log('User account created from PDS by SDN admin');

        return back()->with('success', "Account created for {$user->name}. Email verification sent.");
    }
}
