<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_c1_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('name_extension')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('sex')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('dual_country')->nullable();
            $table->string('dual_citizenship_type')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('blood_type')->nullable();
            $table->string('umid_id')->nullable();
            $table->string('pagibig_id')->nullable();
            $table->string('philhealth_no')->nullable();
            $table->string('philsys_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('agency_employee_no')->nullable();
            $table->string('telephone_no')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('email')->nullable();
            $table->string('res_house_no')->nullable();
            $table->string('res_street')->nullable();
            $table->string('res_subdivision')->nullable();
            $table->string('res_barangay')->nullable();
            $table->string('res_city')->nullable();
            $table->string('res_province')->nullable();
            $table->string('res_zipcode')->nullable();
            $table->string('perm_house_no')->nullable();
            $table->string('perm_street')->nullable();
            $table->string('perm_subdivision')->nullable();
            $table->string('perm_barangay')->nullable();
            $table->string('perm_city')->nullable();
            $table->string('perm_province')->nullable();
            $table->string('perm_zipcode')->nullable();
            $table->string('spouse_surname')->nullable();
            $table->string('spouse_firstname')->nullable();
            $table->string('spouse_middlename')->nullable();
            $table->string('spouse_extension')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->string('spouse_employer')->nullable();
            $table->string('spouse_business_addr')->nullable();
            $table->string('spouse_telephone')->nullable();
            $table->string('father_surname')->nullable();
            $table->string('father_firstname')->nullable();
            $table->string('father_middlename')->nullable();
            $table->string('father_extension')->nullable();
            $table->string('mother_surname')->nullable();
            $table->string('mother_firstname')->nullable();
            $table->string('mother_middlename')->nullable();
            $table->string('mother_extension')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c1_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c1_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('level')->nullable();
            $table->string('school_name')->nullable();
            $table->string('degree')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('units')->nullable();
            $table->string('year_graduated')->nullable();
            $table->string('honors')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_c1_education');
        Schema::dropIfExists('pds_c1_children');
        Schema::dropIfExists('pds_c1_profiles');
    }
};
