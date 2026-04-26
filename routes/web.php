<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\SessionHistoryController;
use App\Http\Controllers\AccountStatusHistoryController;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\CooperativeTypeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberServiceAvailedController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\CommitteeMemberController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityFundingSourceController;
use App\Http\Controllers\ActivityParticipantController;
use App\Http\Controllers\FinanceReportsController;
use App\Http\Controllers\FinancialRecordController;
use App\Http\Controllers\FinancialRecordsController;
use App\Http\Controllers\FundingSourcesController;
use App\Http\Controllers\LoanPaymentsController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\LoansController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\SavingsTransactionsController;
use App\Http\Controllers\ExternalSupportController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingParticipantController;
use App\Http\Controllers\SkillInventoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PdsController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\RecycleBinController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('homepage');
    }

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('homepage', [HomepageController::class, 'index'])->name('homepage');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:Super Admin')->prefix('display')->name('display.')->group(function () {
        Route::get('/', [DisplayController::class, 'index'])->name('index');
        Route::post('carousel-photos', [DisplayController::class, 'storeCarouselPhoto'])->name('carousel-photos.store');
        Route::patch('carousel-photos/{carouselPhoto}/default', [DisplayController::class, 'setDefaultCarouselPhoto'])->name('carousel-photos.default');
        Route::patch('carousel-photos/{carouselPhoto}/toggle', [DisplayController::class, 'toggleCarouselPhoto'])->name('carousel-photos.toggle');
        Route::delete('carousel-photos/{carouselPhoto}', [DisplayController::class, 'destroyCarouselPhoto'])->name('carousel-photos.destroy');

        Route::post('whats-new', [DisplayController::class, 'storeWhatsNew'])->name('whats-new.store');
        Route::put('whats-new/{whatsNewEntry}', [DisplayController::class, 'updateWhatsNew'])->name('whats-new.update');
        Route::delete('whats-new/{whatsNewEntry}', [DisplayController::class, 'destroyWhatsNew'])->name('whats-new.destroy');
    });
    
    // User Management (Provincial Admin & Super Admin)
    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:read user-accounts')
        ->name('users.index');
    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:create user-accounts')
        ->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])
        ->middleware('permission:update user-accounts')
        ->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:delete user-accounts')
        ->name('users.destroy');
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])
        ->middleware('permission:assign roles')
        ->name('users.assign-role');
    Route::post('users/{user}/remove-role', [UserController::class, 'removeRole'])
        ->middleware('permission:assign roles')
        ->name('users.remove-role');

    // Role Management (Provincial Admin & Super Admin)
    Route::get('roles-permissions', [RoleController::class, 'index'])
        ->middleware('permission:manage-permissions')
        ->name('roles-permissions.index');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:manage-permissions')
        ->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:manage-permissions')
        ->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:manage-permissions')
        ->name('roles.destroy');
    
    // Cooperative Management
    Route::get('cooperatives', [CooperativeController::class, 'index'])
        ->middleware('permission:read coop-master-profile')
        ->name('cooperatives.index');
    Route::get('cooperatives/{id}/report', [CooperativeController::class, 'report'])
        ->middleware('permission:read coop-master-profile')
        ->name('cooperatives.report');
    Route::get('cooperatives/create', [CooperativeController::class, 'create'])
        ->middleware('permission:create coop-master-profile')
        ->name('cooperatives.create');
    Route::post('cooperatives', [CooperativeController::class, 'store'])
        ->middleware('permission:create coop-master-profile')
        ->name('cooperatives.store');
    Route::get('cooperatives/{cooperative}/edit', [CooperativeController::class, 'edit'])
        ->middleware('permission:update coop-master-profile')
        ->name('cooperatives.edit');
    Route::put('cooperatives/{cooperative}', [CooperativeController::class, 'update'])
        ->middleware('permission:update coop-master-profile')
        ->name('cooperatives.update');
    Route::delete('cooperatives/{cooperative}', [CooperativeController::class, 'destroy'])
        ->middleware('permission:delete coop-master-profile')
        ->name('cooperatives.destroy');
    Route::post('cooperatives/{id}/restore', [CooperativeController::class, 'restore'])
        ->middleware(['permission:update coop-master-profile', 'role:Super Admin|Provincial Admin'])
        ->name('cooperatives.restore');
    Route::get('cooperatives/my', [CooperativeController::class, 'show'])
        ->middleware('permission:read coop-master-profile')
        ->name('cooperatives.show');
    Route::get('cooperatives/{cooperative}', [CooperativeController::class, 'show'])
        ->middleware('permission:read coop-master-profile')
        ->name('cooperatives.show-specific');
    Route::get('cooperatives/{cooperative}/accreditations', [AccreditationController::class, 'index'])
        ->middleware('permission:update coop-master-profile')
        ->name('cooperatives.accreditations.index');
    Route::post('cooperatives/{cooperative}/accreditations', [AccreditationController::class, 'store'])
        ->middleware('permission:update coop-master-profile')
        ->name('cooperatives.accreditations.store');
    Route::put('cooperatives/{cooperative}/accreditations/{accreditation}', [AccreditationController::class, 'update'])
        ->middleware('permission:update coop-master-profile')
        ->name('cooperatives.accreditations.update');
    Route::delete('cooperatives/{cooperative}/accreditations/{accreditation}', [AccreditationController::class, 'destroy'])
        ->middleware('permission:update coop-master-profile')
        ->name('cooperatives.accreditations.destroy');

    // Cooperative Type Management
    Route::get('cooperative-types', [CooperativeTypeController::class, 'index'])
        ->middleware('permission:manage-system-settings')
        ->name('cooperative-types.index');
    Route::post('cooperative-types', [CooperativeTypeController::class, 'store'])
        ->middleware('permission:manage-system-settings')
        ->name('cooperative-types.store');
    Route::put('cooperative-types/{cooperativeType}', [CooperativeTypeController::class, 'update'])
        ->middleware('permission:manage-system-settings')
        ->name('cooperative-types.update');
    Route::delete('cooperative-types/{cooperativeType}', [CooperativeTypeController::class, 'destroy'])
        ->middleware('permission:manage-system-settings')
        ->name('cooperative-types.destroy');
    
    // Member Management
    Route::get('members', [MemberController::class, 'index'])
        ->middleware(['permission:read members-profile', 'deny_coop_admin'])
        ->name('members.index');
    Route::get('members/management', [MemberController::class, 'management'])
        ->middleware('permission:read members-management')
        ->name('members.management');
    Route::get('members/management/select', [MemberController::class, 'managementSelect'])
        ->middleware('permission:read members-management')
        ->name('members.management.select');
    Route::get('members/management/{cooperative}', [MemberController::class, 'management'])
        ->middleware('permission:read members-management')
        ->name('members.management.cooperative');
    Route::get('members/create', [MemberController::class, 'create'])
        ->middleware('permission:create members-profile')
        ->name('members.create');
    Route::post('members', [MemberController::class, 'store'])
        ->middleware('permission:create members-profile')
        ->name('members.store');
    Route::get('members/{member}/edit', [MemberController::class, 'edit'])
        ->middleware('permission:update members-profile')
        ->name('members.edit');
    Route::put('members/{member}', [MemberController::class, 'update'])
        ->middleware('permission:update members-profile')
        ->name('members.update');
    Route::post('members/{member}/create-account', [MemberController::class, 'createAccount'])
        ->middleware('permission:create user-accounts')
        ->name('members.create-account');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])
        ->middleware('permission:delete members-profile')
        ->name('members.destroy');
    Route::post('members/{id}/restore', [MemberController::class, 'restore'])
        ->middleware(['permission:update members-profile', 'role:Super Admin|Provincial Admin'])
        ->name('members.restore');
    Route::get('members/{member}/services-availed', [MemberController::class, 'servicesAvailed'])
        ->middleware('permission:read members-profile')
        ->name('members.services-availed.index');
    Route::get('members/{member}/activity-participants', [MemberController::class, 'activityParticipants'])
        ->middleware('permission:read members-profile')
        ->name('members.activity-participants.index');
    Route::get('members/{member}', [MemberController::class, 'show'])
        ->middleware('permission:read members-profile')
        ->name('members.show');
    Route::post('members/{member}/services-availed', [MemberServiceAvailedController::class, 'store'])
        ->middleware('permission:update members-profile')
        ->name('members.services-availed.store');
    Route::put('members/{member}/services-availed/{service}', [MemberServiceAvailedController::class, 'update'])
        ->middleware('permission:update members-profile')
        ->name('members.services-availed.update');
    Route::delete('members/{member}/services-availed/{service}', [MemberServiceAvailedController::class, 'destroy'])
        ->middleware('permission:delete members-profile')
        ->name('members.services-availed.destroy');

    // Member-facing: single PDS route (their own only)
    Route::get('pds/my', [PdsController::class, 'myPds'])
        ->middleware('permission:read members-profile')
        ->name('pds.my');
    Route::get('pds/member/{member}', [PdsController::class, 'memberPds'])
        ->middleware('permission:read members-profile')
        ->name('pds.member');
    Route::post('pds/my', [PdsController::class, 'myPdsUpdate'])
        ->middleware('permission:update members-profile')
        ->name('pds.my.update');

    // Personal Data Sheet
    Route::resource('pds', PdsController::class)
        ->except(['show'])
        ->parameters(['pds' => 'pds'])
        ->middleware('permission:read members-profile|update members-profile');
    Route::post('pds/{id}/restore', [PdsController::class, 'restore'])
        ->middleware(['permission:update members-profile', 'role:Super Admin|Provincial Admin'])
        ->name('pds.restore');
    Route::get('pds/{pds}/download', [PdsController::class, 'download'])
        ->middleware('permission:read members-profile')
        ->name('pds.download');

    // Member Self-Service
    Route::get('member-portal', [MemberPortalController::class, 'edit'])
        ->middleware('permission:read members-profile')
        ->name('member-portal.edit');
    Route::get('member-portal/services', [MemberPortalController::class, 'servicesAvailed'])
        ->middleware('permission:read members-profile')
        ->name('member-portal.services');
    Route::get('member-portal/activities', [MemberPortalController::class, 'activityParticipants'])
        ->middleware('permission:read members-profile')
        ->name('member-portal.activities');
    Route::get('member-portal/loans', [MemberPortalController::class, 'loans'])
        ->middleware('permission:read finance-member-loans|apply-own finance-member-loans')
        ->name('member-portal.loans');
    Route::get('member-portal/loans/{loan}', [MemberPortalController::class, 'showLoan'])
        ->middleware('permission:read finance-member-loans|apply-own finance-member-loans')
        ->name('member-portal.loans.show');
    Route::put('member-portal', [MemberPortalController::class, 'update'])
        ->middleware('permission:update members-profile')
        ->name('member-portal.update');

    // Officers & Committees
    Route::get('officers/select', [OfficerController::class, 'select'])
        ->middleware('permission:read officers-&-committees')
        ->name('officers.select');
    Route::get('officers', [OfficerController::class, 'index'])
        ->middleware(['permission:read officers-&-committees', 'deny_coop_admin'])
        ->name('officers.index');
    Route::get('officers/create', [OfficerController::class, 'create'])
        ->middleware('permission:create officers-&-committees')
        ->name('officers.create');
    Route::post('officers', [OfficerController::class, 'store'])
        ->middleware('permission:create officers-&-committees')
        ->name('officers.store');
    Route::get('officers/{officer}/edit', [OfficerController::class, 'edit'])
        ->middleware('permission:update officers-&-committees')
        ->name('officers.edit');
    Route::put('officers/{officer}', [OfficerController::class, 'update'])
        ->middleware('permission:update officers-&-committees')
        ->name('officers.update');
    Route::delete('officers/{officer}', [OfficerController::class, 'destroy'])
        ->middleware('permission:delete officers-&-committees')
        ->name('officers.destroy');
    Route::post('officers/{id}/restore', [OfficerController::class, 'restore'])
        ->middleware(['permission:update officers-&-committees', 'role:Super Admin|Provincial Admin'])
        ->name('officers.restore');

    Route::get('committee-members', [CommitteeMemberController::class, 'index'])
        ->middleware(['permission:read officers-&-committees', 'deny_coop_admin'])
        ->name('committee-members.index');
    Route::get('committee-members/create', [CommitteeMemberController::class, 'create'])
        ->middleware('permission:create officers-&-committees')
        ->name('committee-members.create');
    Route::post('committee-members', [CommitteeMemberController::class, 'store'])
        ->middleware('permission:create officers-&-committees')
        ->name('committee-members.store');
    Route::get('committee-members/{committeeMember}/edit', [CommitteeMemberController::class, 'edit'])
        ->middleware('permission:update officers-&-committees')
        ->name('committee-members.edit');
    Route::put('committee-members/{committeeMember}', [CommitteeMemberController::class, 'update'])
        ->middleware('permission:update officers-&-committees')
        ->name('committee-members.update');
    Route::delete('committee-members/{committeeMember}', [CommitteeMemberController::class, 'destroy'])
        ->middleware('permission:delete officers-&-committees')
        ->name('committee-members.destroy');

    // Activities & Projects
    Route::get('activities/select', [ActivityController::class, 'select'])
        ->middleware('permission:read activities-&-projects')
        ->name('activities.select');
    Route::get('activities', [ActivityController::class, 'index'])
        ->middleware('permission:read activities-&-projects')
        ->name('activities.index');
    Route::get('activities/{id}/report', [ActivityController::class, 'report'])
        ->middleware('permission:read activities-&-projects')
        ->name('activities.report');
    Route::get('activities/{activity}/cooperatives-participating', [ActivityController::class, 'cooperativesParticipating'])
        ->middleware('permission:read activities-&-projects')
        ->name('activities.cooperatives-participating');
    Route::get('activities/{activity}/cooperatives/{cooperative}/participants', [ActivityController::class, 'participantsByCooperative'])
        ->middleware('permission:read activities-&-projects')
        ->name('activities.cooperative-participants');
    Route::get('activities/create', [ActivityController::class, 'create'])
        ->middleware('permission:create activities-&-projects')
        ->name('activities.create');
    Route::get('activities/{activity}', [ActivityController::class, 'show'])
        ->middleware('permission:read activities-&-projects')
        ->name('activities.show');
    Route::post('activities', [ActivityController::class, 'store'])
        ->middleware('permission:create activities-&-projects')
        ->name('activities.store');
    Route::get('activities/{activity}/edit', [ActivityController::class, 'edit'])
        ->middleware('permission:update activities-&-projects')
        ->name('activities.edit');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])
        ->middleware('permission:update activities-&-projects')
        ->name('activities.update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])
        ->middleware('permission:delete activities-&-projects')
        ->name('activities.destroy');
    Route::post('activities/{id}/restore', [ActivityController::class, 'restore'])
        ->middleware(['permission:update activities-&-projects', 'role:Super Admin|Provincial Admin'])
        ->name('activities.restore');

    // Activity Funding Sources
    Route::get('activity-funding-sources/select', [ActivityFundingSourceController::class, 'select'])
        ->middleware('permission:read financial-&-support')
        ->name('activity-funding-sources.select');
    Route::get('activity-funding-sources', [ActivityFundingSourceController::class, 'index'])
        ->middleware('permission:read financial-&-support')
        ->name('activity-funding-sources.index');
    Route::get('activity-funding-sources/create', [ActivityFundingSourceController::class, 'create'])
        ->middleware('permission:create financial-&-support')
        ->name('activity-funding-sources.create');
    Route::post('activity-funding-sources', [ActivityFundingSourceController::class, 'store'])
        ->middleware('permission:create financial-&-support')
        ->name('activity-funding-sources.store');
    Route::get('activity-funding-sources/{activityFundingSource}/edit', [ActivityFundingSourceController::class, 'edit'])
        ->middleware('permission:update financial-&-support')
        ->name('activity-funding-sources.edit');
    Route::put('activity-funding-sources/{activityFundingSource}', [ActivityFundingSourceController::class, 'update'])
        ->middleware('permission:update financial-&-support')
        ->name('activity-funding-sources.update');
    Route::delete('activity-funding-sources/{activityFundingSource}', [ActivityFundingSourceController::class, 'destroy'])
        ->middleware('permission:delete financial-&-support')
        ->name('activity-funding-sources.destroy');

    Route::get('activity-participants', [ActivityParticipantController::class, 'index'])
        ->middleware('permission:read activities-&-projects')
        ->name('activity-participants.index');
    Route::get('activity-participants/create', [ActivityParticipantController::class, 'create'])
        ->middleware('permission:create activities-&-projects')
        ->name('activity-participants.create');
    Route::post('activity-participants', [ActivityParticipantController::class, 'store'])
        ->middleware('permission:create activities-&-projects')
        ->name('activity-participants.store');
    Route::get('activity-participants/{activityParticipant}/edit', [ActivityParticipantController::class, 'edit'])
        ->middleware('permission:update activities-&-projects')
        ->name('activity-participants.edit');
    Route::put('activity-participants/{activityParticipant}', [ActivityParticipantController::class, 'update'])
        ->middleware('permission:update activities-&-projects')
        ->name('activity-participants.update');
    Route::delete('activity-participants/{activityParticipant}', [ActivityParticipantController::class, 'destroy'])
        ->middleware('permission:delete activities-&-projects')
        ->name('activity-participants.destroy');

    // Trainings
    Route::get('trainings/select', [TrainingController::class, 'select'])
        ->middleware('permission:read training-&-capacity')
        ->name('trainings.select');
    Route::get('trainings', [TrainingController::class, 'index'])
        ->middleware('permission:read training-&-capacity')
        ->name('trainings.index');
    Route::get('trainings/create', [TrainingController::class, 'create'])
        ->middleware('permission:create training-&-capacity')
        ->name('trainings.create');
    Route::post('trainings', [TrainingController::class, 'store'])
        ->middleware('permission:create training-&-capacity')
        ->name('trainings.store');
    Route::match(['get', 'post'], 'trainings/members/by-cooperatives', [TrainingController::class, 'membersByCooperatives'])
        ->middleware('permission:read training-&-capacity|create training-&-capacity|update training-&-capacity')
        ->name('trainings.members-by-cooperatives');
    Route::get('trainings/{training}', [TrainingController::class, 'show'])
        ->middleware('permission:read training-&-capacity')
        ->name('trainings.show');
    Route::get('trainings/{training}/edit', [TrainingController::class, 'edit'])
        ->middleware('permission:update training-&-capacity')
        ->name('trainings.edit');
    Route::get('trainings/{training}/cooperatives-participating', [TrainingController::class, 'cooperativesParticipating'])
        ->middleware('permission:read training-&-capacity')
        ->name('trainings.cooperatives-participating');
    Route::get('trainings/{training}/cooperatives/{cooperative}/participants', [TrainingController::class, 'participantsByCooperative'])
        ->middleware('permission:read training-&-capacity')
        ->name('trainings.cooperative-participants');
    Route::put('trainings/{training}', [TrainingController::class, 'update'])
        ->middleware('permission:update training-&-capacity')
        ->name('trainings.update');
    Route::delete('trainings/{training}', [TrainingController::class, 'destroy'])
        ->middleware('permission:delete training-&-capacity')
        ->name('trainings.destroy');

    // Training Participants
    Route::get('training-participants', [TrainingParticipantController::class, 'index'])
        ->middleware('permission:read training-&-capacity')
        ->name('training-participants.index');
    Route::get('training-participants/create', [TrainingParticipantController::class, 'create'])
        ->middleware('permission:create training-&-capacity')
        ->name('training-participants.create');
    Route::post('training-participants', [TrainingParticipantController::class, 'store'])
        ->middleware('permission:create training-&-capacity')
        ->name('training-participants.store');
    Route::get('training-participants/{trainingParticipant}/edit', [TrainingParticipantController::class, 'edit'])
        ->middleware('permission:update training-&-capacity')
        ->name('training-participants.edit');
    Route::put('training-participants/{trainingParticipant}', [TrainingParticipantController::class, 'update'])
        ->middleware('permission:update training-&-capacity')
        ->name('training-participants.update');
    Route::delete('training-participants/{trainingParticipant}', [TrainingParticipantController::class, 'destroy'])
        ->middleware('permission:delete training-&-capacity')
        ->name('training-participants.destroy');

    // Skills Inventory
    Route::get('skill-inventories/select', [SkillInventoryController::class, 'select'])
        ->middleware('permission:read training-&-capacity')
        ->name('skill-inventories.select');
    Route::get('skill-inventories', [SkillInventoryController::class, 'index'])
        ->middleware('permission:read training-&-capacity')
        ->name('skill-inventories.index');
    Route::get('skill-inventories/create', [SkillInventoryController::class, 'create'])
        ->middleware('permission:create training-&-capacity')
        ->name('skill-inventories.create');
    Route::post('skill-inventories', [SkillInventoryController::class, 'store'])
        ->middleware('permission:create training-&-capacity')
        ->name('skill-inventories.store');
    Route::get('skill-inventories/{skillInventory}/edit', [SkillInventoryController::class, 'edit'])
        ->middleware('permission:update training-&-capacity')
        ->name('skill-inventories.edit');
    Route::put('skill-inventories/{skillInventory}', [SkillInventoryController::class, 'update'])
        ->middleware('permission:update training-&-capacity')
        ->name('skill-inventories.update');
    Route::delete('skill-inventories/{skillInventory}', [SkillInventoryController::class, 'destroy'])
        ->middleware('permission:delete training-&-capacity')
        ->name('skill-inventories.destroy');

    // Financial Records
    Route::get('financial-records/select', [FinancialRecordController::class, 'select'])
        ->middleware('permission:read financial-&-support')
        ->name('financial-records.select');
    Route::get('financial-records', [FinancialRecordController::class, 'index'])
        ->middleware('permission:read financial-&-support')
        ->name('financial-records.index');
    Route::get('financial-records/create', [FinancialRecordController::class, 'create'])
        ->middleware('permission:create financial-&-support')
        ->name('financial-records.create');
    Route::post('financial-records', [FinancialRecordController::class, 'store'])
        ->middleware('permission:create financial-&-support')
        ->name('financial-records.store');
    Route::get('financial-records/{financialRecord}/edit', [FinancialRecordController::class, 'edit'])
        ->middleware('permission:update financial-&-support')
        ->name('financial-records.edit');
    Route::put('financial-records/{financialRecord}', [FinancialRecordController::class, 'update'])
        ->middleware('permission:update financial-&-support')
        ->name('financial-records.update');
    Route::delete('financial-records/{financialRecord}', [FinancialRecordController::class, 'destroy'])
        ->middleware('permission:delete financial-&-support')
        ->name('financial-records.destroy');

    // Finance Hub
    Route::get('finance', function () {
        return redirect()->route('finance.funding-sources.index');
    })
        ->middleware('permission:read financial-&-support|read finance-funding-sources|read finance-member-loans|read finance-savings-accounts|read finance-reports|read finance-ledger-entries')
        ->name('finance.index');

    // Finance: Funding Sources
    Route::prefix('finance/funding-sources')->name('finance.funding-sources.')->group(function () {
        Route::get('/', [FundingSourcesController::class, 'index'])
            ->middleware('permission:read finance-funding-sources')
            ->name('index');

        Route::get('create', [ActivityFundingSourceController::class, 'create'])
            ->middleware('permission:create finance-funding-sources')
            ->name('create');

        Route::post('/', [ActivityFundingSourceController::class, 'store'])
            ->middleware('permission:create finance-funding-sources')
            ->name('store');

        Route::get('{fundingSource}', [FundingSourcesController::class, 'show'])
            ->middleware('permission:read finance-funding-sources')
            ->name('show');

        Route::get('{activityFundingSource}/edit', [ActivityFundingSourceController::class, 'edit'])
            ->middleware('permission:update finance-funding-sources')
            ->name('edit');

        Route::put('{activityFundingSource}', [ActivityFundingSourceController::class, 'update'])
            ->middleware('permission:update finance-funding-sources')
            ->name('update');

        Route::delete('{activityFundingSource}', [ActivityFundingSourceController::class, 'destroy'])
            ->middleware('permission:delete finance-funding-sources')
            ->name('destroy');
    });

    // Finance: Financial Records (Ledger)
    Route::prefix('finance/financial-records')->name('finance.financial-records.')->group(function () {
        Route::get('/', [FinancialRecordsController::class, 'index'])
            ->middleware('permission:read finance-ledger-entries')
            ->name('index');

        Route::get('create', [FinancialRecordController::class, 'create'])
            ->middleware('permission:create finance-ledger-entries')
            ->name('create');

        Route::post('/', [FinancialRecordController::class, 'store'])
            ->middleware('permission:create finance-ledger-entries')
            ->name('store');

        Route::get('{financialRecord}', [FinancialRecordsController::class, 'show'])
            ->middleware('permission:read finance-ledger-entries')
            ->name('show');

        Route::get('{financialRecord}/edit', [FinancialRecordController::class, 'edit'])
            ->middleware('permission:update finance-ledger-entries')
            ->name('edit');

        Route::put('{financialRecord}', [FinancialRecordController::class, 'update'])
            ->middleware('permission:update finance-ledger-entries')
            ->name('update');

        Route::delete('{financialRecord}', [FinancialRecordController::class, 'destroy'])
            ->middleware('permission:delete finance-ledger-entries')
            ->name('destroy');
    });

    // Finance: Loans
    Route::prefix('finance/loans')->name('finance.loans.')->group(function () {
        Route::get('/', [LoansController::class, 'index'])
            ->middleware('permission:read finance-member-loans')
            ->name('index');

        Route::get('create', [LoansController::class, 'create'])
            ->middleware('permission:create finance-member-loans|apply-own finance-member-loans')
            ->name('create');

        Route::post('/', [LoansController::class, 'store'])
            ->middleware('permission:create finance-member-loans|apply-own finance-member-loans')
            ->name('store');

        Route::get('{loan}', [LoansController::class, 'show'])
            ->middleware('permission:read finance-member-loans')
            ->name('show');

        Route::get('{loan}/edit', [LoansController::class, 'edit'])
            ->middleware('permission:update finance-member-loans')
            ->name('edit');

        Route::put('{loan}', [LoansController::class, 'update'])
            ->middleware('permission:update finance-member-loans')
            ->name('update');

        Route::delete('{loan}', [LoansController::class, 'destroy'])
            ->middleware('permission:delete finance-member-loans')
            ->name('destroy');

        Route::post('{loan}/approve', [LoansController::class, 'approve'])
            ->middleware('permission:approve finance-member-loans|approve-major finance-member-loans')
            ->name('approve');

        Route::post('{loan}/disburse', [LoansController::class, 'disburse'])
            ->middleware('permission:disburse finance-member-loans')
            ->name('disburse');

        Route::post('{loan}/payments', [LoanPaymentsController::class, 'store'])
            ->middleware('permission:record-payment finance-member-loans')
            ->name('payments.store');
    });

    // Finance: Loan Types
    Route::prefix('finance/loan-types')->name('finance.loan-types.')->group(function () {
        Route::get('/', [LoanTypeController::class, 'index'])
            ->middleware('permission:read finance-member-loans')
            ->name('index');

        Route::post('/', [LoanTypeController::class, 'store'])
            ->middleware('permission:create finance-member-loans')
            ->name('store');

        Route::put('{loanType}', [LoanTypeController::class, 'update'])
            ->middleware('permission:update finance-member-loans')
            ->name('update');

        Route::delete('{loanType}', [LoanTypeController::class, 'destroy'])
            ->middleware('permission:delete finance-member-loans')
            ->name('destroy');
    });

    // Finance: Savings
    Route::prefix('finance/savings')->name('finance.savings.')->group(function () {
        Route::get('/', [SavingsController::class, 'index'])
            ->middleware('permission:read finance-savings-accounts')
            ->name('index');

        Route::get('create', [SavingsController::class, 'create'])
            ->middleware('permission:open finance-savings-accounts')
            ->name('create');

        Route::post('/', [SavingsController::class, 'store'])
            ->middleware('permission:open finance-savings-accounts')
            ->name('store');

        Route::get('{savings}', [SavingsController::class, 'show'])
            ->middleware('permission:read finance-savings-accounts')
            ->name('show');

        Route::get('{savings}/edit', [SavingsController::class, 'edit'])
            ->middleware('permission:update finance-savings-accounts')
            ->name('edit');

        Route::put('{savings}', [SavingsController::class, 'update'])
            ->middleware('permission:update finance-savings-accounts')
            ->name('update');

        Route::delete('{savings}', [SavingsController::class, 'destroy'])
            ->middleware('permission:close finance-savings-accounts')
            ->name('destroy');

        Route::post('{savings}/transactions', [SavingsTransactionsController::class, 'store'])
            ->middleware('permission:record-deposit finance-savings-accounts|record-withdrawal finance-savings-accounts')
            ->name('transactions.store');

        Route::post('{savings}/calculate-interest', [SavingsController::class, 'calculateInterest'])
            ->middleware('permission:calculate-interest finance-savings-accounts|override finance-auto-jobs')
            ->name('calculate-interest');
    });

    // Finance: Reports
    Route::prefix('finance/reports')->name('finance.reports.')->group(function () {
        Route::get('statements', [FinanceReportsController::class, 'statements'])
            ->middleware('permission:read finance-reports')
            ->name('statements');

        Route::get('statements/export', [FinanceReportsController::class, 'exportStatements'])
            ->middleware('permission:export finance-reports')
            ->name('statements.export');

        Route::get('loan-portfolio', [FinanceReportsController::class, 'loanPortfolio'])
            ->middleware('permission:read finance-reports')
            ->name('loan-portfolio');

        Route::get('loan-portfolio/export', [FinanceReportsController::class, 'exportLoanPortfolio'])
            ->middleware('permission:export finance-reports')
            ->name('loan-portfolio.export');

        Route::get('savings-summary', [FinanceReportsController::class, 'savingsSummary'])
            ->middleware('permission:read finance-reports')
            ->name('savings-summary');

        Route::get('savings-summary/export', [FinanceReportsController::class, 'exportSavingsSummary'])
            ->middleware('permission:export finance-reports')
            ->name('savings-summary.export');

        Route::get('funder-accountability', [FinanceReportsController::class, 'funderAccountability'])
            ->middleware('permission:read finance-reports')
            ->name('funder-accountability');

        Route::get('funder-accountability/export', [FinanceReportsController::class, 'exportFunderAccountability'])
            ->middleware('permission:export finance-reports')
            ->name('funder-accountability.export');

        Route::get('trends', [FinanceReportsController::class, 'trends'])
            ->middleware('permission:read finance-reports')
            ->name('trends');

        Route::get('trends/export', [FinanceReportsController::class, 'exportTrends'])
            ->middleware('permission:export finance-reports')
            ->name('trends.export');
    });

    // External Supports
    Route::get('external-supports/select', [ExternalSupportController::class, 'select'])
        ->middleware('permission:read financial-&-support')
        ->name('external-supports.select');
    Route::get('external-supports', [ExternalSupportController::class, 'index'])
        ->middleware('permission:read financial-&-support')
        ->name('external-supports.index');
    Route::get('external-supports/create', [ExternalSupportController::class, 'create'])
        ->middleware('permission:create financial-&-support')
        ->name('external-supports.create');
    Route::post('external-supports', [ExternalSupportController::class, 'store'])
        ->middleware('permission:create financial-&-support')
        ->name('external-supports.store');
    Route::get('external-supports/{externalSupport}/edit', [ExternalSupportController::class, 'edit'])
        ->middleware('permission:update financial-&-support')
        ->name('external-supports.edit');
    Route::put('external-supports/{externalSupport}', [ExternalSupportController::class, 'update'])
        ->middleware('permission:update financial-&-support')
        ->name('external-supports.update');
    Route::delete('external-supports/{externalSupport}', [ExternalSupportController::class, 'destroy'])
        ->middleware('permission:delete financial-&-support')
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

    // Recycle Bin (permission-controlled: view, restore, delete)
    Route::get('recycle-bin', [RecycleBinController::class, 'index'])
        ->middleware('permission:read recycle-bin')
        ->name('recycle-bin.index');
    Route::post('recycle-bin/restore', [RecycleBinController::class, 'restore'])
        ->middleware('permission:restore recycle-bin')
        ->name('recycle-bin.restore');
    Route::delete('recycle-bin', [RecycleBinController::class, 'destroy'])
        ->middleware('permission:delete recycle-bin')
        ->name('recycle-bin.destroy');
});

require __DIR__.'/settings.php';
