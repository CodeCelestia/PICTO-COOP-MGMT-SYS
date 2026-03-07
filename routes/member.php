<?php

use App\Http\Controllers\Member\CompletePdsController;
use App\Http\Controllers\Member\MemberDashboardController;
use App\Http\Controllers\Member\MyPDSController;
use Illuminate\Support\Facades\Route;

// Complete-PDS route: auth + verified, but NOT gated by pds.complete
Route::middleware(['auth', 'verified', 'role:member'])
    ->prefix('member')
    ->name('member.')
    ->group(function (): void {
        Route::get('complete-pds', [CompletePdsController::class, 'show'])->name('complete-pds');
        Route::post('complete-pds', [CompletePdsController::class, 'store'])->name('complete-pds.store');
    });

// Protected member routes: enforce PDS completion gate
Route::middleware(['auth', 'verified', 'role:member', 'pds.complete'])
    ->prefix('member')
    ->name('member.')
    ->group(function (): void {
        Route::get('dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

        // My PDS Management
        Route::get('my-pds', [MyPDSController::class, 'show'])->name('my-pds.show');
        Route::get('my-pds/edit', [MyPDSController::class, 'edit'])->name('my-pds.edit');
        Route::patch('my-pds', [MyPDSController::class, 'update'])->name('my-pds.update');
    });
