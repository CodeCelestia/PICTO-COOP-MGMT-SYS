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

        // Activity Logs (existing)
        Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');

        // PDS Management
        Route::resource('pds', PDSController::class);

        // User from PDS
        Route::get('pds/{pds}/create-user', [UserFromPDSController::class, 'create'])->name('pds.create-user');
        Route::post('pds/{pds}/create-user', [UserFromPDSController::class, 'store'])->name('pds.store-user');

        // Office Management
        Route::resource('offices', OfficeController::class);

        // Office Roles Management
        Route::resource('office-roles', OfficeRoleController::class);

        // Office User Role Management
        Route::get('offices/{office}/users', [OfficeUserRoleController::class, 'index'])->name('offices.users.index');
        Route::post('offices/{office}/assign-user', [OfficeUserRoleController::class, 'assignToOffice'])->name('offices.users.assign');
        Route::patch('offices/{office}/users/{user}/role', [OfficeUserRoleController::class, 'updateRole'])->name('offices.users.update-role');
        Route::delete('offices/{office}/users/{user}', [OfficeUserRoleController::class, 'removeFromOffice'])->name('offices.users.remove');

        // Member Management
        Route::resource('members', MemberController::class);

        // Committee Management
        Route::resource('committees', CommitteeController::class);

        // Activity Management
        Route::resource('activities', ActivityController::class);
    });

