<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\SessionHistoryController;
use App\Http\Controllers\AccountStatusHistoryController;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberServiceAvailedController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\CommitteeMemberController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityFundingSourceController;
use App\Http\Controllers\ActivityParticipantController;
use App\Http\Controllers\FinancialRecordController;
use App\Http\Controllers\ExternalSupportController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingParticipantController;
use App\Http\Controllers\SkillInventoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PdsController;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // User Management (Provincial Admin only)
    Route::get('users', [UserController::class, 'index'])
        ->middleware('role:Provincial Admin')
        ->name('users.index');
    Route::post('users', [UserController::class, 'store'])
        ->middleware('role:Provincial Admin')
        ->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])
        ->middleware('role:Provincial Admin')
        ->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('role:Provincial Admin')
        ->name('users.destroy');
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])
        ->middleware('role:Provincial Admin')
        ->name('users.assign-role');
    Route::post('users/{user}/remove-role', [UserController::class, 'removeRole'])
        ->middleware('role:Provincial Admin')
        ->name('users.remove-role');

    // Role Management (Provincial Admin only)
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('role:Provincial Admin')
        ->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('role:Provincial Admin')
        ->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('role:Provincial Admin')
        ->name('roles.destroy');
    
    // Cooperative Management
    Route::get('cooperatives', [CooperativeController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('cooperatives.index');
    Route::get('cooperatives/create', [CooperativeController::class, 'create'])
        ->middleware('role:Provincial Admin')
        ->name('cooperatives.create');
    Route::post('cooperatives', [CooperativeController::class, 'store'])
        ->middleware('role:Provincial Admin')
        ->name('cooperatives.store');
    Route::get('cooperatives/{cooperative}/edit', [CooperativeController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('cooperatives.edit');
    Route::put('cooperatives/{cooperative}', [CooperativeController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('cooperatives.update');
    Route::delete('cooperatives/{cooperative}', [CooperativeController::class, 'destroy'])
        ->middleware('role:Provincial Admin')
        ->name('cooperatives.destroy');
    
    // Member Management
    Route::get('members', [MemberController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('members.index');
    Route::get('members/create', [MemberController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('members.create');
    Route::post('members', [MemberController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('members.store');
    Route::get('members/{member}/edit', [MemberController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('members.edit');
    Route::put('members/{member}', [MemberController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('members.update');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('members.destroy');
    Route::get('members/{member}/services-availed', [MemberController::class, 'servicesAvailed'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('members.services-availed.index');
    Route::get('members/{member}/activity-participants', [MemberController::class, 'activityParticipants'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('members.activity-participants.index');
    Route::get('members/{member}', [MemberController::class, 'show'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('members.show');
    Route::post('members/{member}/services-availed', [MemberServiceAvailedController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('members.services-availed.store');
    Route::put('members/{member}/services-availed/{service}', [MemberServiceAvailedController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('members.services-availed.update');
    Route::delete('members/{member}/services-availed/{service}', [MemberServiceAvailedController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('members.services-availed.destroy');

    // Member-facing: single PDS route (their own only)
    Route::get('pds/my', [PdsController::class, 'myPds'])
        ->middleware('role:Member')
        ->name('pds.my');
    Route::get('pds/member/{member}', [PdsController::class, 'memberPds'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('pds.member');
    Route::post('pds/my', [PdsController::class, 'myPdsUpdate'])
        ->middleware('role:Member')
        ->name('pds.my.update');

    // Personal Data Sheet
    Route::resource('pds', PdsController::class)
        ->except(['show'])
        ->parameters(['pds' => 'pds'])
        ->middleware('role:Provincial Admin|Coop Admin');
    Route::get('pds/{pds}/download', [PdsController::class, 'download'])
        ->middleware('role:Provincial Admin|Coop Admin|Member')
        ->name('pds.download');

    // Member Self-Service
    Route::get('member-portal', [MemberPortalController::class, 'edit'])
        ->middleware('role:Member')
        ->name('member-portal.edit');
    Route::get('member-portal/services', [MemberPortalController::class, 'servicesAvailed'])
        ->middleware('role:Member')
        ->name('member-portal.services');
    Route::get('member-portal/activities', [MemberPortalController::class, 'activityParticipants'])
        ->middleware('role:Member')
        ->name('member-portal.activities');
    Route::put('member-portal', [MemberPortalController::class, 'update'])
        ->middleware('role:Member')
        ->name('member-portal.update');

    // Officers & Committees
    Route::get('officers', [OfficerController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('officers.index');
    Route::get('officers/create', [OfficerController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('officers.create');
    Route::post('officers', [OfficerController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('officers.store');
    Route::get('officers/{officer}/edit', [OfficerController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('officers.edit');
    Route::put('officers/{officer}', [OfficerController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('officers.update');
    Route::delete('officers/{officer}', [OfficerController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('officers.destroy');

    Route::get('committee-members', [CommitteeMemberController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('committee-members.index');
    Route::get('committee-members/create', [CommitteeMemberController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('committee-members.create');
    Route::post('committee-members', [CommitteeMemberController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('committee-members.store');
    Route::get('committee-members/{committeeMember}/edit', [CommitteeMemberController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('committee-members.edit');
    Route::put('committee-members/{committeeMember}', [CommitteeMemberController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('committee-members.update');
    Route::delete('committee-members/{committeeMember}', [CommitteeMemberController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('committee-members.destroy');

    // Activities & Projects
    Route::get('activities', [ActivityController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('activities.index');
    Route::get('activities/create', [ActivityController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activities.create');
    Route::post('activities', [ActivityController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activities.store');
    Route::get('activities/{activity}/edit', [ActivityController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('activities.edit');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('activities.update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activities.destroy');

    // Activity Funding Sources
    Route::get('activity-funding-sources', [ActivityFundingSourceController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('activity-funding-sources.index');
    Route::get('activity-funding-sources/create', [ActivityFundingSourceController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activity-funding-sources.create');
    Route::post('activity-funding-sources', [ActivityFundingSourceController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activity-funding-sources.store');
    Route::get('activity-funding-sources/{activityFundingSource}/edit', [ActivityFundingSourceController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('activity-funding-sources.edit');
    Route::put('activity-funding-sources/{activityFundingSource}', [ActivityFundingSourceController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('activity-funding-sources.update');
    Route::delete('activity-funding-sources/{activityFundingSource}', [ActivityFundingSourceController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activity-funding-sources.destroy');

    Route::get('activity-participants', [ActivityParticipantController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('activity-participants.index');
    Route::get('activity-participants/create', [ActivityParticipantController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activity-participants.create');
    Route::post('activity-participants', [ActivityParticipantController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activity-participants.store');
    Route::get('activity-participants/{activityParticipant}/edit', [ActivityParticipantController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('activity-participants.edit');
    Route::put('activity-participants/{activityParticipant}', [ActivityParticipantController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('activity-participants.update');
    Route::delete('activity-participants/{activityParticipant}', [ActivityParticipantController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('activity-participants.destroy');

    // Trainings
    Route::get('trainings', [TrainingController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('trainings.index');
    Route::get('trainings/create', [TrainingController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('trainings.create');
    Route::post('trainings', [TrainingController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('trainings.store');
    Route::get('trainings/{training}/edit', [TrainingController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('trainings.edit');
    Route::put('trainings/{training}', [TrainingController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('trainings.update');
    Route::delete('trainings/{training}', [TrainingController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('trainings.destroy');

    // Training Participants
    Route::get('training-participants', [TrainingParticipantController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('training-participants.index');
    Route::get('training-participants/create', [TrainingParticipantController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('training-participants.create');
    Route::post('training-participants', [TrainingParticipantController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('training-participants.store');
    Route::get('training-participants/{trainingParticipant}/edit', [TrainingParticipantController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('training-participants.edit');
    Route::put('training-participants/{trainingParticipant}', [TrainingParticipantController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('training-participants.update');
    Route::delete('training-participants/{trainingParticipant}', [TrainingParticipantController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('training-participants.destroy');

    // Skills Inventory
    Route::get('skill-inventories', [SkillInventoryController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('skill-inventories.index');
    Route::get('skill-inventories/create', [SkillInventoryController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('skill-inventories.create');
    Route::post('skill-inventories', [SkillInventoryController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('skill-inventories.store');
    Route::get('skill-inventories/{skillInventory}/edit', [SkillInventoryController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('skill-inventories.edit');
    Route::put('skill-inventories/{skillInventory}', [SkillInventoryController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('skill-inventories.update');
    Route::delete('skill-inventories/{skillInventory}', [SkillInventoryController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('skill-inventories.destroy');

    // Financial Records
    Route::get('financial-records', [FinancialRecordController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('financial-records.index');
    Route::get('financial-records/create', [FinancialRecordController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('financial-records.create');
    Route::post('financial-records', [FinancialRecordController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('financial-records.store');
    Route::get('financial-records/{financialRecord}/edit', [FinancialRecordController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('financial-records.edit');
    Route::put('financial-records/{financialRecord}', [FinancialRecordController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('financial-records.update');
    Route::delete('financial-records/{financialRecord}', [FinancialRecordController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('financial-records.destroy');

    // External Supports
    Route::get('external-supports', [ExternalSupportController::class, 'index'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer|Committee Member|Viewer')
        ->name('external-supports.index');
    Route::get('external-supports/create', [ExternalSupportController::class, 'create'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('external-supports.create');
    Route::post('external-supports', [ExternalSupportController::class, 'store'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('external-supports.store');
    Route::get('external-supports/{externalSupport}/edit', [ExternalSupportController::class, 'edit'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('external-supports.edit');
    Route::put('external-supports/{externalSupport}', [ExternalSupportController::class, 'update'])
        ->middleware('role:Provincial Admin|Coop Admin|Officer')
        ->name('external-supports.update');
    Route::delete('external-supports/{externalSupport}', [ExternalSupportController::class, 'destroy'])
        ->middleware('role:Provincial Admin|Coop Admin')
        ->name('external-supports.destroy');
    
    // Activity Logs (Provincial Admin only)
    Route::get('activity-logs', [ActivityLogsController::class, 'index'])
        ->middleware('permission:read audit-logs')
        ->name('activity-logs.index');

    // Audit Logs (Provincial Admin only)
    Route::get('audit-logs', [ActivityLogsController::class, 'index'])
        ->middleware('permission:read audit-logs')
        ->defaults('tab', 'audit')
        ->name('audit-logs.index');
    
    // Session History (Provincial Admin only)
    Route::get('session-history', [ActivityLogsController::class, 'index'])
        ->middleware('permission:read audit-logs')
        ->defaults('tab', 'sessions')
        ->name('session-history.index');
    
    // Account Status History (Provincial Admin only)
    Route::get('account-status-history', [ActivityLogsController::class, 'index'])
        ->middleware('permission:read audit-logs')
        ->defaults('tab', 'accounts')
        ->name('account-status-history.index');
});

require __DIR__.'/settings.php';
