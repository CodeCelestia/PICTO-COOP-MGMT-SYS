<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_c2_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('rating')->nullable();
            $table->date('exam_date')->nullable();
            $table->string('exam_place')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_validity')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c2_work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('position_title')->nullable();
            $table->string('dept_agency')->nullable();
            $table->string('monthly_salary')->nullable();
            $table->string('salary_grade')->nullable();
            $table->string('status_appointment')->nullable();
            $table->string('govt_service')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_c2_work_experiences');
        Schema::dropIfExists('pds_c2_eligibilities');
    }
};
