<?php

use App\Http\Controllers\SdnAdmin\MergeQueueController;
use App\Http\Controllers\SdnAdmin\SdnDashboardController;
use App\Http\Controllers\SdnAdmin\SdnOfficeController;
use App\Http\Controllers\SdnAdmin\SdnPdsController;
use App\Http\Controllers\SdnAdmin\SdnUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:coop_sdn_admin'])
    ->prefix('sdn-admin')
    ->name('sdn-admin.')
    ->group(function (): void {

        Route::get('dashboard', SdnDashboardController::class)->name('dashboard');

        // PDS management scoped to this SDN
        Route::resource('pds', SdnPdsController::class);

        // Office management scoped to this SDN
        Route::resource('offices', SdnOfficeController::class);

        // User management scoped to this SDN
        Route::get('users', [SdnUserController::class, 'index'])->name('users.index');
        Route::post('users', [SdnUserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/suspend', [SdnUserController::class, 'suspend'])->name('users.suspend');
        Route::patch('users/{user}/activate', [SdnUserController::class, 'activate'])->name('users.activate');

        // Merge queue
        Route::get('merge-queue', [MergeQueueController::class, 'index'])->name('merge-queue.index');
        Route::post('merge-queue/{mergeQueue}/approve', [MergeQueueController::class, 'approve'])->name('merge-queue.approve');
        Route::post('merge-queue/{mergeQueue}/reject', [MergeQueueController::class, 'reject'])->name('merge-queue.reject');
    });
