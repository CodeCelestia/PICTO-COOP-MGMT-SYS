<?php

namespace Tests\Feature\ActivityLog;

use App\Models\Office;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('coop_sdn_admin', 'web');
        Role::findOrCreate('member', 'web');
    }

    public function test_login_creates_audit_log_entry(): void
    {
        $user = User::factory()->create(['status' => 'active', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('activity_log', [
            'log_name'   => 'auth',
            'description' => 'user.logged_in',
            'causer_id'  => $user->id,
        ]);
    }

    public function test_registration_creates_audit_log_entry(): void
    {
        Role::findOrCreate('member', 'web');

        $this->post(route('register.store'), [
            'name'                  => 'Audit Test',
            'email'                 => 'auditlog@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'auditlog@example.com')->firstOrFail();

        $this->assertDatabaseHas('activity_log', [
            'log_name'   => 'registration',
            'causer_id'  => $user->id,
        ]);
    }

    public function test_pds_completion_creates_audit_log(): void
    {
        $office = Office::factory()->create(['allow_self_registration' => true]);
        $member = User::factory()->create(['status' => 'pending', 'pds_id' => null, 'office_id' => $office->id]);
        $member->assignRole('member');

        $this->actingAs($member)->post(route('member.complete-pds.store'), [
            'first_name'             => 'Log',
            'last_name'              => 'Test',
            'date_of_birth'          => '1992-07-04',
            'gender'                 => 'Male',
            'citizenship'            => 'Filipino',
            'email'                  => 'logtest@example.com',
            'confirmed_no_duplicate' => true,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'pds',
            'description' => 'pds.completed',
            'causer_id'   => $member->id,
        ]);
    }

    public function test_office_update_by_sdn_admin_creates_audit_log(): void
    {
        $sdnAdmin = User::factory()->create(['status' => 'active']);
        $sdnAdmin->assignRole('coop_sdn_admin');
        $office   = Office::factory()->create(['sdn_id' => $sdnAdmin->sdn_id]);

        $this->actingAs($sdnAdmin)
            ->patch(route('sdn-admin.offices.update', $office), [
                'name'                    => 'Updated Office Name',
                'code'                    => $office->code,
                'allow_self_registration' => false,
            ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'office',
            'description' => 'office.updated',
            'causer_id'   => $sdnAdmin->id,
        ]);
    }
}
