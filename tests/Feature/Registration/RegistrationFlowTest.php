<?php

namespace Tests\Feature\Registration;

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('member', 'web');
        Role::findOrCreate('super_admin', 'web');
    }

    public function test_registration_page_renders(): void
    {
        $response = $this->get(route('register'));
        $response->assertOk();
    }

    public function test_basic_registration_creates_pending_member(): void
    {
        $response = $this->post(route('register.store'), [
            'name'                  => 'Jane Dela Cruz',
            'email'                 => 'jane@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertEquals('pending', $user->status);
        $this->assertTrue($user->hasRole('member'));
        $this->assertNull($user->pds_id);
    }

    public function test_registration_with_valid_self_registration_office(): void
    {
        $office = Office::factory()->create(['allow_self_registration' => true]);

        $response = $this->post(route('register.store'), [
            'name'                  => 'Pedro Santos',
            'email'                 => 'pedro@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'office_id'             => $office->id,
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'pedro@example.com')->firstOrFail();
        $this->assertEquals($office->id, $user->office_id);
        $this->assertEquals($office->sdn_id, $user->sdn_id);
    }

    public function test_registration_ignores_office_that_disallows_self_registration(): void
    {
        $office = Office::factory()->create(['allow_self_registration' => false]);

        $this->post(route('register.store'), [
            'name'                  => 'Ana Reyes',
            'email'                 => 'ana@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'office_id'             => $office->id,
        ]);

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertNull($user->office_id, 'office_id should be null when office disallows self-registration');
    }

    public function test_new_member_is_redirected_to_complete_pds_after_login(): void
    {
        $user = User::factory()->create(['status' => 'pending', 'pds_id' => null]);
        $user->assignRole('member');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('member.complete-pds'));
    }
}
