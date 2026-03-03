<?php

use App\Http\Controllers\Member\MemberDashboardController;
use App\Http\Controllers\Member\MyPDSController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:member'])
    ->prefix('member')
    ->name('member.')
    ->group(function (): void {
        Route::get('dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');
        
        // My PDS Management
        Route::get('my-pds', [MyPDSController::class, 'show'])->name('my-pds.show');
        Route::get('my-pds/edit', [MyPDSController::class, 'edit'])->name('my-pds.edit');
        Route::patch('my-pds', [MyPDSController::class, 'update'])->name('my-pds.update');
    });
