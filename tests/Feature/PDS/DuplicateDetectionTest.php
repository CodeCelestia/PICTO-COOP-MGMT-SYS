<?php

namespace Tests\Feature\PDS;

use App\Models\Office;
use App\Models\PersonalDataSheet;
use App\Models\User;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    private DuplicateDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DuplicateDetectionService::class);
        Role::findOrCreate('member', 'web');
    }

    public function test_exact_government_id_match_detected(): void
    {
        PersonalDataSheet::factory()->create(['sss_no' => '12-3456789-0']);

        $matches = $this->service->check(['sss_no' => '12-3456789-0']);

        $this->assertNotEmpty($matches);
        $this->assertEquals('exact_id', $matches[0]['type']);
        $this->assertEquals('high', $matches[0]['confidence']);
    }

    public function test_exact_email_match_detected(): void
    {
        PersonalDataSheet::factory()->create(['email' => 'duplicate@example.com']);

        $matches = $this->service->check(['email' => 'duplicate@example.com']);

        $this->assertNotEmpty($matches);
        $this->assertEquals('exact_email', $matches[0]['type']);
    }

    public function test_fuzzy_name_and_dob_match_detected(): void
    {
        PersonalDataSheet::factory()->create([
            'first_name'    => 'Juan',
            'last_name'     => 'dela Cruz',
            'date_of_birth' => '1985-03-20',
        ]);

        $matches = $this->service->check([
            'first_name'    => 'Juan',
            'last_name'     => 'dela Cruz',
            'date_of_birth' => '1985-03-20',
        ]);

        $this->assertNotEmpty($matches);
        $this->assertEquals('fuzzy_name_dob', $matches[0]['type']);
        $this->assertEquals('medium', $matches[0]['confidence']);
    }

    public function test_no_match_returns_empty_array(): void
    {
        $matches = $this->service->check([
            'first_name'    => 'Unique',
            'last_name'     => 'Person',
            'date_of_birth' => '1999-01-01',
            'email'         => 'unique@unique.example',
        ]);

        $this->assertEmpty($matches);
    }

    public function test_excluded_pds_id_is_not_flagged_as_duplicate(): void
    {
        $pds = PersonalDataSheet::factory()->create(['sss_no' => '99-9999999-9']);

        $matches = $this->service->check(['sss_no' => '99-9999999-9'], $pds->id);

        $this->assertEmpty($matches, 'Excluded PDS should not count as a duplicate of itself');
    }

    public function test_duplicate_detected_during_complete_pds_shows_warning(): void
    {
        $office = Office::factory()->create(['allow_self_registration' => true]);
        PersonalDataSheet::factory()->create(['sss_no' => '11-1111111-1', 'office_id' => $office->id]);

        $member = User::factory()->create(['status' => 'pending', 'pds_id' => null, 'office_id' => $office->id]);
        $member->assignRole('member');

        $response = $this->actingAs($member)->post(route('member.complete-pds.store'), [
            'first_name'    => 'Test',
            'last_name'     => 'User',
            'date_of_birth' => '1990-01-01',
            'gender'        => 'Male',
            'citizenship'   => 'Filipino',
            'email'         => 'test@test.example',
            'sss_no'        => '11-1111111-1',
            // No confirmed_no_duplicate or link_to_pds_id
        ]);

        // Should redirect back with duplicate_matches in session
        $response->assertRedirect();
        $response->assertSessionHas('duplicate_matches');
    }
}
