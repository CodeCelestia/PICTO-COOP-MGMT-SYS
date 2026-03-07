<?php

namespace App\Http\Controllers\SdnAdmin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SdnOfficeController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $offices = Office::where('sdn_id', $user->sdn_id)
            ->withCount('members')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('sdn-admin/Offices/Index', [
            'offices' => $offices,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('sdn-admin/Offices/Create');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'code'                    => 'required|string|max:50|unique:offices,code',
            'address'                 => 'nullable|string|max:500',
            'allow_self_registration' => 'boolean',
            'admin_name'              => 'required|string|max:255',
            'admin_email'             => 'required|email|unique:users,email',
            'admin_password'          => 'required|string|min:8|confirmed',
        ]);

        $this->authorize('create', Office::class);

        DB::transaction(function () use ($validated, $user, &$office, &$admin) {
            $office = Office::create([
                'name'                    => $validated['name'],
                'code'                    => $validated['code'],
                'address'                 => $validated['address'] ?? null,
                'allow_self_registration' => $validated['allow_self_registration'] ?? false,
                'sdn_id'                  => $user->sdn_id,
            ]);

            $admin = User::create([
                'name'      => $validated['admin_name'],
                'email'     => $validated['admin_email'],
                'password'  => bcrypt($validated['admin_password']),
                'office_id' => $office->id,
                'sdn_id'    => $user->sdn_id,
                'status'    => 'active',
            ]);
            $admin->assignRole('coop_office_admin');
        });

        activity('office')
            ->causedBy($user)
            ->performedOn($office)
            ->log('office.created');

        return redirect()->route('sdn-admin.offices.index')
            ->with('success', "Office \"{$office->name}\" created. Admin account: {$admin->email}");
    }

    public function edit(Request $request, Office $office): Response
    {
        $this->authorize('update', $office);

        return Inertia::render('sdn-admin/Offices/Edit', [
            'office' => $office,
        ]);
    }

    public function update(Request $request, Office $office)
    {
        $this->authorize('update', $office);

        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'code'                    => 'required|string|max:50|unique:offices,code,' . $office->id,
            'address'                 => 'nullable|string|max:500',
            'allow_self_registration' => 'boolean',
        ]);

        $office->update($validated);

        activity('office')
            ->causedBy($request->user())
            ->performedOn($office)
            ->log('office.updated');

        return redirect()->route('sdn-admin.offices.index')
            ->with('success', "Office \"{$office->name}\" updated successfully.");
    }

    public function destroy(Request $request, Office $office)
    {
        $this->authorize('delete', $office);

        $name = $office->name;
        $office->delete();

        activity('office')
            ->causedBy($request->user())
            ->log("office.deleted: {$name}");

        return redirect()->route('sdn-admin.offices.index')
            ->with('success', "Office \"{$name}\" deleted.");
    }
}
