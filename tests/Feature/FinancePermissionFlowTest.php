<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberLoan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancePermissionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_coop_admin_cannot_see_other_coops_loans(): void
    {
        $readLoans = Permission::findOrCreate('read finance-member-loans', 'web');

        $role = Role::firstOrCreate(
            ['name' => 'Coop Admin'],
            ['guard_name' => 'web', 'description' => 'Test role', 'level' => 2, 'is_active' => true]
        );
        $role->syncPermissions([$readLoans]);

        $coopA = $this->createCooperative('Coop A', 'REG-A-001');
        $coopB = $this->createCooperative('Coop B', 'REG-B-001');

        $memberA = $this->createMember($coopA, 'Alice', 'Alpha');
        $memberB = $this->createMember($coopB, 'Bruno', 'Beta');

        $loanA = MemberLoan::create([
            'coop_id' => $coopA->id,
            'member_id' => $memberA->id,
            'principal' => 10000,
            'interest_rate' => 12,
            'term_months' => 12,
            'status' => 'Pending',
        ]);

        $loanB = MemberLoan::create([
            'coop_id' => $coopB->id,
            'member_id' => $memberB->id,
            'principal' => 20000,
            'interest_rate' => 15,
            'term_months' => 24,
            'status' => 'Pending',
        ]);

        $user = User::factory()->create([
            'coop_id' => $coopA->id,
            'account_type' => 'Coop Admin',
            'account_status' => 'Active',
        ]);
        $user->assignRole($role);

        $this->actingAs($user);

        $visibleIds = MemberLoan::query()->pluck('id')->all();
        $this->assertContains($loanA->id, $visibleIds);
        $this->assertNotContains($loanB->id, $visibleIds);

        $response = $this->get(route('finance.loans.index'));
        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertDontSee('Bruno');
    }

    public function test_member_can_only_see_own_loans(): void
    {
        $permissions = [
            Permission::findOrCreate('read finance-member-loans', 'web'),
            Permission::findOrCreate('apply-own finance-member-loans', 'web'),
        ];

        $role = Role::firstOrCreate(
            ['name' => 'Member'],
            ['guard_name' => 'web', 'description' => 'Test role', 'level' => 7, 'is_active' => true]
        );
        $role->syncPermissions($permissions);

        $coop = $this->createCooperative('Coop M', 'REG-M-001');
        $member1 = $this->createMember($coop, 'Mara', 'One');
        $member2 = $this->createMember($coop, 'Nico', 'Two');

        MemberLoan::create([
            'coop_id' => $coop->id,
            'member_id' => $member1->id,
            'principal' => 8000,
            'interest_rate' => 10,
            'term_months' => 10,
            'status' => 'Pending',
        ]);

        MemberLoan::create([
            'coop_id' => $coop->id,
            'member_id' => $member2->id,
            'principal' => 9000,
            'interest_rate' => 10,
            'term_months' => 10,
            'status' => 'Pending',
        ]);

        $user = User::factory()->create([
            'coop_id' => $coop->id,
            'member_id' => $member1->id,
            'account_type' => 'Member',
            'account_status' => 'Active',
        ]);
        $user->assignRole($role);

        $this->actingAs($user);

        $response = $this->get(route('finance.loans.index'));
        $response->assertOk();
        $response->assertSee('Mara');
        $response->assertDontSee('Nico');
    }

    public function test_officer_cannot_approve_member_loans(): void
    {
        $rolePermissions = [
            Permission::findOrCreate('read finance-member-loans', 'web'),
            Permission::findOrCreate('create finance-member-loans', 'web'),
        ];

        $role = Role::firstOrCreate(
            ['name' => 'Officer'],
            ['guard_name' => 'web', 'description' => 'Test role', 'level' => 5, 'is_active' => true]
        );
        $role->syncPermissions($rolePermissions);

        $coop = $this->createCooperative('Coop O', 'REG-O-001');
        $member = $this->createMember($coop, 'Olive', 'Officer');

        $loan = MemberLoan::create([
            'coop_id' => $coop->id,
            'member_id' => $member->id,
            'principal' => 15000,
            'interest_rate' => 12,
            'term_months' => 12,
            'status' => 'Pending',
        ]);

        $officer = User::factory()->create([
            'coop_id' => $coop->id,
            'account_type' => 'Officer',
            'account_status' => 'Active',
        ]);
        $officer->assignRole($role);

        $this->assertFalse($officer->can('approve finance-member-loans'));

        $this->actingAs($officer);
        $response = $this->post(route('finance.loans.approve', $loan), [
            'remarks' => 'Attempted approval',
        ]);

        $response->assertForbidden();
    }

    private function createCooperative(string $name, string $registration): Cooperative
    {
        return Cooperative::create([
            'name' => $name,
            'registration_number' => $registration,
            'date_established' => now()->subYears(3)->toDateString(),
            'address' => 'Sample Address',
            'province' => 'Leyte',
            'region' => 'Region VIII',
            'city_municipality' => 'Tacloban',
            'barangay' => 'Barangay 1',
            'status' => 'Active',
        ]);
    }

    private function createMember(Cooperative $cooperative, string $firstName, string $lastName): Member
    {
        return Member::create([
            'coop_id' => $cooperative->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => now()->subYears(30)->toDateString(),
            'gender' => 'Male',
            'address' => 'Sample Address',
            'region' => 'Region VIII',
            'province' => 'Leyte',
            'city_municipality' => 'Tacloban',
            'barangay' => 'Barangay 1',
            'date_joined' => now()->subYears(1)->toDateString(),
            'membership_type' => 'Regular',
            'membership_status' => 'Active',
            'share_capital' => 1000,
        ]);
    }
}
