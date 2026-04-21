<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\CooperativeStatusHistory;
use App\Models\CooperativeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CooperativeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createUserWithPermissions(array $permissions, ?int $coopId = null): User
    {
        $user = User::factory()->create(['coop_id' => $coopId]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function createCooperative(array $overrides = []): Cooperative
    {
        return Cooperative::create(array_merge([
            'name' => 'Sample Coop ' . uniqid(),
            'registration_number' => 'REG-' . uniqid(),
            'classification' => 'micro',
            'date_established' => '2020-01-01',
            'address' => '123 Main St',
            'province' => 'Cebu',
            'region' => 'Region VII',
            'city_municipality' => 'Cebu City',
            'barangay' => 'Lahug',
            'email' => 'coop@example.com',
            'phone' => '09171234567',
            'status' => 'Active',
        ], $overrides));
    }

    public function test_index_limits_results_for_non_privileged_users()
    {
        $coopA = $this->createCooperative(['name' => 'Alpha Cooperative']);
        $this->createCooperative(['name' => 'Beta Cooperative']);

        $user = $this->createUserWithPermissions(['read coop-master-profile'], $coopA->id);

        $this->actingAs($user)
            ->get(route('cooperatives.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperatives/Index')
                ->has('cooperatives.data', 1)
                ->where('cooperatives.data.0.id', $coopA->id)
                ->where('cooperatives.data.0.name', $coopA->name)
            );
    }

    public function test_index_allows_view_all_permission()
    {
        $this->createCooperative(['name' => 'Alpha Cooperative']);
        $this->createCooperative(['name' => 'Beta Cooperative']);

        $user = $this->createUserWithPermissions([
            'read coop-master-profile',
            'view-all-cooperatives',
        ]);

        $this->actingAs($user)
            ->get(route('cooperatives.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperatives/Index')
                ->where('cooperatives.total', 2)
            );
    }

    public function test_store_creates_cooperative_and_status_history()
    {
        $type = CooperativeType::create([
            'name' => 'Credit',
            'slug' => 'credit',
            'level' => 'region',
            'sort_order' => 1,
        ]);

        $user = $this->createUserWithPermissions(['create coop-master-profile']);

        $payload = [
            'name' => 'New Cooperative',
            'registration_number' => 'CDA-REG-TEST-1',
            'type_ids' => [$type->id],
            'classification' => 'micro',
            'date_established' => '2022-05-01',
            'address' => '123 Test Ave',
            'province' => 'Cebu',
            'region' => 'Region VII',
            'city_municipality' => 'Cebu City',
            'barangay' => 'Lahug',
            'email' => 'newcoop@example.com',
            'phone' => '09990001111',
            'status' => 'Active',
        ];

        $this->actingAs($user)
            ->post(route('cooperatives.store'), $payload)
            ->assertRedirect(route('cooperatives.index'));

        $this->assertDatabaseHas('cooperatives', [
            'name' => 'New Cooperative',
            'registration_number' => 'CDA-REG-TEST-1',
            'status' => 'Active',
        ]);

        $cooperative = Cooperative::where('registration_number', 'CDA-REG-TEST-1')->firstOrFail();

        $this->assertDatabaseHas('cooperative_cooperative_type', [
            'cooperative_id' => $cooperative->id,
            'cooperative_type_id' => $type->id,
        ]);

        $this->assertDatabaseHas('cooperative_status_history', [
            'coop_id' => $cooperative->id,
            'previous_status' => null,
            'new_status' => 'Active',
            'change_reason' => 'Initial registration',
        ]);
    }

    public function test_update_requires_change_reason_when_status_changes()
    {
        $type = CooperativeType::create([
            'name' => 'Credit',
            'slug' => 'credit',
            'level' => 'region',
            'sort_order' => 1,
        ]);

        $cooperative = $this->createCooperative();

        $user = $this->createUserWithPermissions(['update coop-master-profile']);

        $payload = [
            'name' => $cooperative->name,
            'registration_number' => $cooperative->registration_number,
            'type_ids' => [$type->id],
            'classification' => $cooperative->classification,
            'date_established' => $cooperative->date_established->toDateString(),
            'address' => $cooperative->address,
            'province' => $cooperative->province,
            'region' => $cooperative->region,
            'city_municipality' => $cooperative->city_municipality,
            'barangay' => $cooperative->barangay,
            'email' => $cooperative->email,
            'phone' => $cooperative->phone,
            'status' => 'Inactive',
        ];

        $this->actingAs($user)
            ->put(route('cooperatives.update', $cooperative), $payload)
            ->assertSessionHasErrors(['change_reason']);
    }

    public function test_update_logs_status_history_when_status_changes()
    {
        $type = CooperativeType::create([
            'name' => 'Credit',
            'slug' => 'credit',
            'level' => 'region',
            'sort_order' => 1,
        ]);

        $cooperative = $this->createCooperative([
            'status' => 'Active',
        ]);

        $user = $this->createUserWithPermissions(['update coop-master-profile']);

        $payload = [
            'name' => $cooperative->name,
            'registration_number' => $cooperative->registration_number,
            'type_ids' => [$type->id],
            'classification' => $cooperative->classification,
            'date_established' => $cooperative->date_established->toDateString(),
            'address' => $cooperative->address,
            'province' => $cooperative->province,
            'region' => $cooperative->region,
            'city_municipality' => $cooperative->city_municipality,
            'barangay' => $cooperative->barangay,
            'email' => $cooperative->email,
            'phone' => $cooperative->phone,
            'status' => 'Inactive',
            'change_reason' => 'Board resolution',
            'status_remarks' => 'Non-compliance',
        ];

        $this->actingAs($user)
            ->put(route('cooperatives.update', $cooperative), $payload)
            ->assertRedirect(route('cooperatives.index'));

        $this->assertDatabaseHas('cooperatives', [
            'id' => $cooperative->id,
            'status' => 'Inactive',
        ]);

        $this->assertDatabaseHas('cooperative_status_history', [
            'coop_id' => $cooperative->id,
            'previous_status' => 'Active',
            'new_status' => 'Inactive',
            'change_reason' => 'Board resolution',
            'remarks' => 'Non-compliance',
        ]);
    }
}
