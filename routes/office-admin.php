<?php

use App\Http\Controllers\OfficeAdmin\OfficeDashboardController;
use App\Http\Controllers\OfficeAdmin\OfficeProfileController;
use App\Http\Controllers\OfficeAdmin\OfficePDSController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:coop_office_admin'])
    ->prefix('office-admin')
    ->name('office-admin.')
    ->group(function (): void {
        Route::get('dashboard', [OfficeDashboardController::class, 'index'])->name('dashboard');
        
        // Office Profile Management
        Route::get('profile', [OfficeProfileController::class, 'show'])->name('profile.show');
        Route::get('profile/edit', [OfficeProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [OfficeProfileController::class, 'update'])->name('profile.update');
        
        // PDS Management for Office
        Route::resource('pds', OfficePDSController::class);
        Route::post('pds/{pd}/create-user', [OfficePDSController::class, 'createUser'])->name('pds.create-user');
    });
