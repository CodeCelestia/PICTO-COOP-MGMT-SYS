<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_c3_voluntary_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('organization')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('hours')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c3_learning_developments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('hours')->nullable();
            $table->string('type')->nullable();
            $table->string('conducted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c3_special_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('skill')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c3_recognitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('recognition')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c3_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('membership')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_c3_memberships');
        Schema::dropIfExists('pds_c3_recognitions');
        Schema::dropIfExists('pds_c3_special_skills');
        Schema::dropIfExists('pds_c3_learning_developments');
        Schema::dropIfExists('pds_c3_voluntary_works');
    }
};
