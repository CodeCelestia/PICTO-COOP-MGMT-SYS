<?php

namespace Tests\Feature\Authorization;

use App\Models\Office;
use App\Models\PersonalDataSheet;
use App\Models\Sdn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('coop_sdn_admin', 'web');
        Role::findOrCreate('coop_office_admin', 'web');
        Role::findOrCreate('member', 'web');
    }

    // ─── Super admin ────────────────────────────────────────────────────────────

    public function test_super_admin_can_access_all_sdns(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get(route('super-admin.dashboard'));
        $response->assertOk();
    }

    public function test_super_admin_cannot_be_redirected_to_complete_pds(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'pds_id' => null]);
        $admin->assignRole('super_admin');

        // super_admin should land on their own dashboard, not complete-pds
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertRedirect(route('super-admin.dashboard'));
    }

    // ─── SDN admin ──────────────────────────────────────────────────────────────

    public function test_sdn_admin_can_access_sdn_dashboard(): void
    {
        $sdnAdmin = User::factory()->create(['status' => 'active']);
        $sdnAdmin->assignRole('coop_sdn_admin');

        $response = $this->actingAs($sdnAdmin)->get(route('sdn-admin.dashboard'));
        $response->assertOk();
    }

    public function test_sdn_admin_cannot_access_super_admin_routes(): void
    {
        $sdnAdmin = User::factory()->create(['status' => 'active']);
        $sdnAdmin->assignRole('coop_sdn_admin');

        $response = $this->actingAs($sdnAdmin)->get(route('super-admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_sdn_admin_can_only_view_offices_in_their_sdn(): void
    {
        $sdn1 = Sdn::factory()->create();
        $sdn2 = Sdn::factory()->create();

        $sdnAdmin = User::factory()->create(['status' => 'active', 'sdn_id' => $sdn1->id]);
        $sdnAdmin->assignRole('coop_sdn_admin');

        $officeInSdn1 = Office::factory()->create(['sdn_id' => $sdn1->id]);
        $officeInSdn2 = Office::factory()->create(['sdn_id' => $sdn2->id]);

        // Should be authorized for own SDN office
        $this->assertTrue($sdnAdmin->can('view', $officeInSdn1));
        // Should NOT be authorized for other SDN office
        $this->assertFalse($sdnAdmin->can('view', $officeInSdn2));
    }

    // ─── Officer admin ──────────────────────────────────────────────────────────

    public function test_officer_admin_can_only_access_their_office(): void
    {
        $office1 = Office::factory()->create();
        $office2 = Office::factory()->create();

        $officerAdmin = User::factory()->create(['status' => 'active', 'office_id' => $office1->id]);
        $officerAdmin->assignRole('coop_office_admin');

        $this->assertTrue($officerAdmin->can('view', $office1));
        $this->assertFalse($officerAdmin->can('view', $office2));
    }

    public function test_officer_admin_cannot_view_pds_outside_their_office(): void
    {
        $office1 = Office::factory()->create();
        $office2 = Office::factory()->create();

        $officerAdmin = User::factory()->create(['status' => 'active', 'office_id' => $office1->id]);
        $officerAdmin->assignRole('coop_office_admin');

        $ownPds   = PersonalDataSheet::factory()->create(['office_id' => $office1->id]);
        $otherPds = PersonalDataSheet::factory()->create(['office_id' => $office2->id]);

        $this->assertTrue($officerAdmin->can('view', $ownPds));
        $this->assertFalse($officerAdmin->can('view', $otherPds));
    }

    // ─── Member ─────────────────────────────────────────────────────────────────

    public function test_member_can_only_view_own_pds(): void
    {
        $myPds    = PersonalDataSheet::factory()->create();
        $otherPds = PersonalDataSheet::factory()->create();

        $member = User::factory()->create(['status' => 'active', 'pds_id' => $myPds->id]);
        $member->assignRole('member');

        $this->assertTrue($member->can('view', $myPds));
        $this->assertFalse($member->can('view', $otherPds));
    }

    public function test_member_with_no_pds_is_redirected_to_complete_pds(): void
    {
        $member = User::factory()->create(['status' => 'pending', 'pds_id' => null]);
        $member->assignRole('member');

        $response = $this->actingAs($member)->get(route('member.dashboard'));
        $response->assertRedirect(route('member.complete-pds'));
    }
}
