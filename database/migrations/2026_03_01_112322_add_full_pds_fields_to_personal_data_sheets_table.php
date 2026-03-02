<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            // Name extras
            $table->string('name_extension')->nullable()->after('last_name');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');

            // Physical
            $table->decimal('height', 5, 2)->nullable()->after('civil_status');
            $table->decimal('weight', 5, 2)->nullable()->after('height');
            $table->string('blood_type')->nullable()->after('weight');

            // Citizenship extras
            $table->string('dual_citizenship_type')->nullable()->after('citizenship');
            $table->string('dual_country')->nullable()->after('dual_citizenship_type');

            // Government IDs
            $table->string('gsis_id')->nullable()->after('dual_country');
            $table->string('sss_no')->nullable()->after('gsis_id');
            $table->string('philhealth_no')->nullable()->after('sss_no');
            $table->string('pagibig_no')->nullable()->after('philhealth_no');
            $table->string('tin_no')->nullable()->after('pagibig_no');
            $table->string('telephone_no')->nullable()->after('tin_no');

            // Residential address extras (existing region/province/city/barangay columns = residential)
            $table->string('res_house')->nullable()->after('street_address');
            $table->string('res_subdivision')->nullable()->after('res_house');
            $table->string('res_zip')->nullable()->after('res_subdivision');

            // Permanent address
            $table->boolean('perm_same_as_res')->default(true)->after('res_zip');
            $table->string('perm_house')->nullable()->after('perm_same_as_res');
            $table->string('perm_street')->nullable()->after('perm_house');
            $table->string('perm_subdivision')->nullable()->after('perm_street');
            $table->string('perm_zip')->nullable()->after('perm_subdivision');
            $table->string('perm_region_code')->nullable()->after('perm_zip');
            $table->string('perm_region_name')->nullable()->after('perm_region_code');
            $table->string('perm_province_code')->nullable()->after('perm_region_name');
            $table->string('perm_province_name')->nullable()->after('perm_province_code');
            $table->string('perm_city_municipality_code')->nullable()->after('perm_province_name');
            $table->string('perm_city_municipality_name')->nullable()->after('perm_city_municipality_code');
            $table->string('perm_barangay_code')->nullable()->after('perm_city_municipality_name');
            $table->string('perm_barangay_name')->nullable()->after('perm_barangay_code');

            // Spouse
            $table->string('spouse_surname')->nullable()->after('perm_barangay_name');
            $table->string('spouse_first_name')->nullable()->after('spouse_surname');
            $table->string('spouse_middle_name')->nullable()->after('spouse_first_name');
            $table->string('spouse_name_extension')->nullable()->after('spouse_middle_name');
            $table->string('spouse_occupation')->nullable()->after('spouse_name_extension');
            $table->string('spouse_employer')->nullable()->after('spouse_occupation');
            $table->string('spouse_business_address')->nullable()->after('spouse_employer');
            $table->string('spouse_telephone')->nullable()->after('spouse_business_address');

            // Father
            $table->string('father_surname')->nullable()->after('spouse_telephone');
            $table->string('father_first_name')->nullable()->after('father_surname');
            $table->string('father_middle_name')->nullable()->after('father_first_name');
            $table->string('father_name_extension')->nullable()->after('father_middle_name');

            // Mother
            $table->string('mother_surname')->nullable()->after('father_name_extension');
            $table->string('mother_first_name')->nullable()->after('mother_surname');
            $table->string('mother_middle_name')->nullable()->after('mother_first_name');

            // JSON sub-records
            $table->json('children')->nullable()->after('mother_middle_name');
            $table->json('education')->nullable()->after('children');
            $table->json('eligibilities')->nullable()->after('education');
            $table->json('work_experiences')->nullable()->after('eligibilities');
            $table->json('voluntary_works')->nullable()->after('work_experiences');
            $table->json('learning_developments')->nullable()->after('voluntary_works');
            $table->json('references_list')->nullable()->after('learning_developments');

            // Other information
            $table->text('special_skills')->nullable()->after('references_list');
            $table->text('non_academic_distinctions')->nullable()->after('special_skills');
            $table->text('memberships')->nullable()->after('non_academic_distinctions');
            $table->json('questions')->nullable()->after('memberships');

            // Government-issued ID
            $table->string('government_issued_id')->nullable()->after('questions');
            $table->string('id_no')->nullable()->after('government_issued_id');
            $table->string('id_issue_place')->nullable()->after('id_no');
            $table->date('date_accomplished')->nullable()->after('id_issue_place');
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->dropColumn([
                'name_extension','place_of_birth','height','weight','blood_type',
                'dual_citizenship_type','dual_country',
                'gsis_id','sss_no','philhealth_no','pagibig_no','tin_no','telephone_no',
                'res_house','res_subdivision','res_zip',
                'perm_same_as_res','perm_house','perm_street','perm_subdivision','perm_zip',
                'perm_region_code','perm_region_name','perm_province_code','perm_province_name',
                'perm_city_municipality_code','perm_city_municipality_name',
                'perm_barangay_code','perm_barangay_name',
                'spouse_surname','spouse_first_name','spouse_middle_name','spouse_name_extension',
                'spouse_occupation','spouse_employer','spouse_business_address','spouse_telephone',
                'father_surname','father_first_name','father_middle_name','father_name_extension',
                'mother_surname','mother_first_name','mother_middle_name',
                'children','education','eligibilities','work_experiences',
                'voluntary_works','learning_developments','references_list',
                'special_skills','non_academic_distinctions','memberships','questions',
                'government_issued_id','id_no','id_issue_place','date_accomplished',
            ]);
        });
    }
};