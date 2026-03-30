<?php

namespace App\Console\Commands;

use App\Models\PdsSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigratePdsJsonToNormalized extends Command
{
    protected $signature = 'pds:migrate-json:run';
    protected $description = 'Migrate existing PDS JSON blob columns into normalized C1-C4 tables (internal run)';

    public function handle(): int
    {
        $query = PdsSubmission::query()
            ->whereNotNull('c1_data')
            ->orWhereNotNull('c2_data')
            ->orWhereNotNull('c3_data')
            ->orWhereNotNull('c4_data');

        $total = $query->count();
        $this->info("Found {$total} PDS submissions with JSON data.");

        $processed = 0;
        $succeeded = 0;
        $failed = 0;
        $failedIds = [];

        foreach ($query->cursor() as $pds) {
            $processed++;

            if ($this->isAlreadyMigrated($pds)) {
                $this->info("[{$pds->id}] SKIPPED (already migrated)");
                continue;
            }

            DB::beginTransaction();

            try {
                $this->migrateSubmission($pds);
                DB::commit();

                $succeeded++;
                $this->info("[{$pds->id}] migrated");
            } catch (\Throwable $e) {
                DB::rollBack();
                $failed++;
                $failedIds[] = $pds->id;
                $this->error("[{$pds->id}] FAILED: {$e->getMessage()}");
                Log::error('PDS JSON migration failed for id: ' . $pds->id, ['exception' => $e]);
            }
        }

        $this->info("Summary: processed={$processed}, succeeded={$succeeded}, failed={$failed}");

        if (count($failedIds) > 0) {
            $this->error('Failed IDs: ' . implode(', ', $failedIds));
        }

        return $failed > 0 ? 1 : 0;
    }

    private function isAlreadyMigrated(PdsSubmission $pds): bool
    {
        return $pds->c1Profile()->exists()
            || $pds->c1Children()->exists()
            || $pds->c1Education()->exists()
            || $pds->c2Eligibilities()->exists()
            || $pds->c2WorkExperiences()->exists()
            || $pds->c3VoluntaryWorks()->exists()
            || $pds->c3LearningDevelopments()->exists()
            || $pds->c3SpecialSkills()->exists()
            || $pds->c3Recognitions()->exists()
            || $pds->c3Memberships()->exists()
            || $pds->c4Declaration()->exists()
            || $pds->c4References()->exists()
            || $pds->c4GovernmentId()->exists();
    }

    private function migrateSubmission(PdsSubmission $pds): void
    {
        $c1Data = json_decode($pds->c1_data ?? '{}', true) ?: [];
        $c2Data = json_decode($pds->c2_data ?? '{}', true) ?: [];
        $c3Data = json_decode($pds->c3_data ?? '{}', true) ?: [];
        $c4Data = json_decode($pds->c4_data ?? '{}', true) ?: [];

        $pds->c1Profile()->updateOrCreate([], [
            'surname' => $c1Data['surname'] ?? null,
            'first_name' => $c1Data['first_name'] ?? null,
            'middle_name' => $c1Data['middle_name'] ?? null,
            'name_extension' => $c1Data['name_extension'] ?? null,
            'date_of_birth' => $c1Data['date_of_birth'] ?? null,
            'place_of_birth' => $c1Data['place_of_birth'] ?? null,
            'sex' => $c1Data['sex'] ?? null,
            'civil_status' => $c1Data['civil_status'] ?? null,
            'citizenship' => $c1Data['citizenship'] ?? null,
            'dual_country' => $c1Data['dual_country'] ?? null,
            'dual_citizenship_type' => $c1Data['dual_citizenship_type'] ?? null,
            'height' => $c1Data['height'] ?? null,
            'weight' => $c1Data['weight'] ?? null,
            'blood_type' => $c1Data['blood_type'] ?? null,
            'umid_id' => $c1Data['umid_id'] ?? null,
            'pagibig_id' => $c1Data['pagibig_id'] ?? null,
            'philhealth_no' => $c1Data['philhealth_no'] ?? null,
            'philsys_no' => $c1Data['philsys_no'] ?? null,
            'tin_no' => $c1Data['tin_no'] ?? null,
            'agency_employee_no' => $c1Data['agency_employee_no'] ?? null,
            'telephone_no' => $c1Data['telephone_no'] ?? null,
            'mobile_no' => $c1Data['mobile_no'] ?? null,
            'email' => $c1Data['email'] ?? null,
            'res_house_no' => $c1Data['res_house_no'] ?? null,
            'res_street' => $c1Data['res_street'] ?? null,
            'res_subdivision' => $c1Data['res_subdivision'] ?? null,
            'res_barangay' => $c1Data['res_barangay'] ?? null,
            'res_city' => $c1Data['res_city'] ?? null,
            'res_province' => $c1Data['res_province'] ?? null,
            'res_zipcode' => $c1Data['res_zipcode'] ?? null,
            'perm_house_no' => $c1Data['perm_house_no'] ?? null,
            'perm_street' => $c1Data['perm_street'] ?? null,
            'perm_subdivision' => $c1Data['perm_subdivision'] ?? null,
            'perm_barangay' => $c1Data['perm_barangay'] ?? null,
            'perm_city' => $c1Data['perm_city'] ?? null,
            'perm_province' => $c1Data['perm_province'] ?? null,
            'perm_zipcode' => $c1Data['perm_zipcode'] ?? null,
            'spouse_surname' => $c1Data['spouse_surname'] ?? null,
            'spouse_firstname' => $c1Data['spouse_firstname'] ?? null,
            'spouse_middlename' => $c1Data['spouse_middlename'] ?? null,
            'spouse_extension' => $c1Data['spouse_extension'] ?? null,
            'spouse_occupation' => $c1Data['spouse_occupation'] ?? null,
            'spouse_employer' => $c1Data['spouse_employer'] ?? null,
            'spouse_business_addr' => $c1Data['spouse_business_addr'] ?? null,
            'spouse_telephone' => $c1Data['spouse_telephone'] ?? null,
            'father_surname' => $c1Data['father_surname'] ?? null,
            'father_firstname' => $c1Data['father_firstname'] ?? null,
            'father_middlename' => $c1Data['father_middlename'] ?? null,
            'father_extension' => $c1Data['father_extension'] ?? null,
            'mother_surname' => $c1Data['mother_surname'] ?? null,
            'mother_firstname' => $c1Data['mother_firstname'] ?? null,
            'mother_middlename' => $c1Data['mother_middlename'] ?? null,
            'mother_extension' => $c1Data['mother_extension'] ?? null,
        ]);

        $pds->c1Children()->delete();
        foreach ($c1Data['children'] ?? [] as $child) {
            $pds->c1Children()->create([
                'name' => $child['name'] ?? null,
                'date_of_birth' => $child['date_of_birth'] ?? null,
            ]);
        }

        $pds->c1Education()->delete();
        foreach ($c1Data['education'] ?? [] as $level => $educationData) {
            $pds->c1Education()->create([
                'level' => $level,
                'school_name' => $educationData['school_name'] ?? null,
                'degree' => $educationData['degree'] ?? null,
                'from' => $educationData['from'] ?? null,
                'to' => $educationData['to'] ?? null,
                'units' => $educationData['units'] ?? null,
                'year_graduated' => $educationData['year_graduated'] ?? null,
                'honors' => $educationData['honors'] ?? null,
            ]);
        }

        $pds->c2Eligibilities()->delete();
        foreach ($c2Data['eligibility'] ?? [] as $eligibility) {
            $pds->c2Eligibilities()->create([
                'name' => $eligibility['name'] ?? null,
                'rating' => $eligibility['rating'] ?? null,
                'exam_date' => $eligibility['exam_date'] ?? null,
                'exam_place' => $eligibility['exam_place'] ?? null,
                'license_number' => $eligibility['license_number'] ?? null,
                'license_validity' => $eligibility['license_validity'] ?? null,
            ]);
        }

        $pds->c2WorkExperiences()->delete();
        foreach ($c2Data['work_experience'] ?? [] as $work) {
            $pds->c2WorkExperiences()->create([
                'date_from' => $work['date_from'] ?? null,
                'date_to' => $work['date_to'] ?? null,
                'position_title' => $work['position_title'] ?? null,
                'dept_agency' => $work['dept_agency'] ?? null,
                'monthly_salary' => $work['monthly_salary'] ?? null,
                'salary_grade' => $work['salary_grade'] ?? null,
                'status_appointment' => $work['status_appointment'] ?? null,
                'govt_service' => $work['govt_service'] ?? null,
            ]);
        }

        $pds->c3VoluntaryWorks()->delete();
        foreach ($c3Data['voluntary_work'] ?? [] as $work) {
            $pds->c3VoluntaryWorks()->create([
                'organization' => $work['organization'] ?? null,
                'date_from' => $work['date_from'] ?? null,
                'date_to' => $work['date_to'] ?? null,
                'hours' => $work['hours'] ?? null,
                'position' => $work['position'] ?? null,
            ]);
        }

        $pds->c3LearningDevelopments()->delete();
        foreach ($c3Data['learning_development'] ?? [] as $learning) {
            $pds->c3LearningDevelopments()->create([
                'title' => $learning['title'] ?? null,
                'date_from' => $learning['date_from'] ?? null,
                'date_to' => $learning['date_to'] ?? null,
                'hours' => $learning['hours'] ?? null,
                'type' => $learning['type'] ?? null,
                'conducted_by' => $learning['conducted_by'] ?? null,
            ]);
        }

        $pds->c3SpecialSkills()->delete();
        foreach ($c3Data['special_skills'] ?? [] as $skill) {
            $pds->c3SpecialSkills()->create(['skill' => $skill]);
        }

        $pds->c3Recognitions()->delete();
        foreach ($c3Data['recognitions'] ?? [] as $recognition) {
            $pds->c3Recognitions()->create(['recognition' => $recognition]);
        }

        $pds->c3Memberships()->delete();
        foreach ($c3Data['memberships'] ?? [] as $membership) {
            $pds->c3Memberships()->create(['membership' => $membership]);
        }

        $pds->c4Declaration()->updateOrCreate([], [
            'q34' => $this->toBool($c4Data['q34'] ?? null),
            'q34_details' => $c4Data['q34_details'] ?? null,
            'q35' => $this->toBool($c4Data['q35'] ?? null),
            'q35_details' => $c4Data['q35_details'] ?? null,
            'q36' => $this->toBool($c4Data['q36'] ?? null),
            'q36_details' => $c4Data['q36_details'] ?? null,
            'q37' => $this->toBool($c4Data['q37'] ?? null),
            'q37_details' => $c4Data['q37_details'] ?? null,
            'q38a' => $this->toBool($c4Data['q38a'] ?? null),
            'q38a_details' => $c4Data['q38a_details'] ?? null,
            'q38b' => $this->toBool($c4Data['q38b'] ?? null),
            'q38b_details' => $c4Data['q38b_details'] ?? null,
            'q39' => $this->toBool($c4Data['q39'] ?? null),
            'q39_details' => $c4Data['q39_details'] ?? null,
            'q40a' => $this->toBool($c4Data['q40a'] ?? null),
            'q40a_details' => $c4Data['q40a_details'] ?? null,
            'q40b' => $this->toBool($c4Data['q40b'] ?? null),
            'q40b_details' => $c4Data['q40b_details'] ?? null,
            'q41' => $this->toBool($c4Data['q41'] ?? null),
            'q41_details' => $c4Data['q41_details'] ?? null,
            'signature_date' => $c4Data['signature_date'] ?? null,
        ]);

        $pds->c4References()->delete();
        foreach ($c4Data['references'] ?? [] as $reference) {
            $pds->c4References()->create([
                'name' => $reference['name'] ?? null,
                'address' => $reference['address'] ?? null,
                'tel_no' => $reference['tel_no'] ?? null,
            ]);
        }

        $pds->c4GovernmentId()->updateOrCreate([], [
            'govt_id_type' => $c4Data['govt_id_type'] ?? null,
            'govt_id_no' => $c4Data['govt_id_no'] ?? null,
            'id_issue_date' => $c4Data['id_issue_date'] ?? null,
            'id_issue_place' => $c4Data['id_issue_place'] ?? null,
        ]);
    }

    private function toBool($value): ?bool
    {
        if (is_null($value)) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['yes', 'y', 'true', '1'], true)) {
            return true;
        }

        if (in_array($normalized, ['no', 'n', 'false', '0'], true)) {
            return false;
        }

        return null;
    }
}
