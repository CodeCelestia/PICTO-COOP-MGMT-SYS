<?php

use App\Http\Controllers\SuperAdmin\ActivityController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\CommitteeController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\MemberController;
use App\Http\Controllers\SuperAdmin\OfficeController;
use App\Http\Controllers\SuperAdmin\OfficeRoleController;
use App\Http\Controllers\SuperAdmin\OfficeUserRoleController;
use App\Http\Controllers\SuperAdmin\PDSController;
use App\Http\Controllers\SuperAdmin\UserFromPDSController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // User Management
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::patch('users/{user}/role', [UserManagementController::class, 'updateRole'])
            ->name('users.update-role');
        Route::patch('roles/{role}/permissions', [UserManagementController::class, 'updateRolePermissions'])
            ->name('roles.update-permissions');

        // Offices
        Route::get('offices', [OfficeController::class, 'index'])->name('offices.index');
        Route::get('offices/create', [OfficeController::class, 'create'])->name('offices.create');
        Route::post('offices', [OfficeController::class, 'store'])->name('offices.store');
        Route::get('offices/{office}', [OfficeController::class, 'show'])->name('offices.show');
        Route::get('offices/{office}/edit', [OfficeController::class, 'edit'])->name('offices.edit');
        Route::patch('offices/{office}', [OfficeController::class, 'update'])->name('offices.update');
        Route::delete('offices/{office}', [OfficeController::class, 'destroy'])->name('offices.destroy');
        Route::get('offices/{office}/users', [OfficeUserRoleController::class, 'index'])->name('offices.users.index');
        Route::post('offices/{office}/users', [OfficeUserRoleController::class, 'assignToOffice'])->name('offices.users.assign');
        Route::patch('offices/{office}/users/{user}/role', [OfficeUserRoleController::class, 'updateRole'])->name('offices.users.update-role');
        Route::delete('offices/{office}/users/{user}', [OfficeUserRoleController::class, 'removeFromOffice'])->name('offices.users.remove');

        // Office Roles
        Route::get('office-roles', [OfficeRoleController::class, 'index'])->name('office-roles.index');
        Route::get('office-roles/create', [OfficeRoleController::class, 'create'])->name('office-roles.create');
        Route::post('office-roles', [OfficeRoleController::class, 'store'])->name('office-roles.store');
        Route::get('office-roles/{officeRole}', [OfficeRoleController::class, 'show'])->name('office-roles.show');
        Route::get('office-roles/{officeRole}/edit', [OfficeRoleController::class, 'edit'])->name('office-roles.edit');
        Route::patch('office-roles/{officeRole}', [OfficeRoleController::class, 'update'])->name('office-roles.update');
        Route::delete('office-roles/{officeRole}', [OfficeRoleController::class, 'destroy'])->name('office-roles.destroy');

        // Members
        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::get('members/create', [MemberController::class, 'create'])->name('members.create');
        Route::post('members', [MemberController::class, 'store'])->name('members.store');
        Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
        Route::get('members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
        Route::patch('members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

        // Committees
        Route::get('committees', [CommitteeController::class, 'index'])->name('committees.index');
        Route::get('committees/create', [CommitteeController::class, 'create'])->name('committees.create');
        Route::post('committees', [CommitteeController::class, 'store'])->name('committees.store');
        Route::get('committees/{committee}', [CommitteeController::class, 'show'])->name('committees.show');
        Route::get('committees/{committee}/edit', [CommitteeController::class, 'edit'])->name('committees.edit');
        Route::patch('committees/{committee}', [CommitteeController::class, 'update'])->name('committees.update');
        Route::delete('committees/{committee}', [CommitteeController::class, 'destroy'])->name('committees.destroy');

        // Activities
        Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('activities/create', [ActivityController::class, 'create'])->name('activities.create');
        Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::get('activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');
        Route::get('activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
        Route::patch('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        // PDS Management
        Route::get('pds', [PDSController::class, 'index'])->name('pds.index');
        Route::get('pds/create', [PDSController::class, 'create'])->name('pds.create');
        Route::post('pds', [PDSController::class, 'store'])->name('pds.store');
        Route::get('pds/{pds}', [PDSController::class, 'show'])->name('pds.show');
        Route::get('pds/{pds}/edit', [PDSController::class, 'edit'])->name('pds.edit');
        Route::patch('pds/{pds}', [PDSController::class, 'update'])->name('pds.update');
        Route::delete('pds/{pds}', [PDSController::class, 'destroy'])->name('pds.destroy');
        Route::get('pds/{pds}/create-user', [UserFromPDSController::class, 'create'])->name('pds.create-user');
        Route::post('pds/{pds}/store-user', [UserFromPDSController::class, 'store'])->name('pds.store-user');

        // Activity Logs
        Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');
    });

