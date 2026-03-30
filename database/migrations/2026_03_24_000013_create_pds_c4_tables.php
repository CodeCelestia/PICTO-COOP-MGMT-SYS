<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_c4_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->boolean('q34')->nullable();
            $table->text('q34_details')->nullable();
            $table->boolean('q35')->nullable();
            $table->text('q35_details')->nullable();
            $table->boolean('q36')->nullable();
            $table->text('q36_details')->nullable();
            $table->boolean('q37')->nullable();
            $table->text('q37_details')->nullable();
            $table->boolean('q38a')->nullable();
            $table->text('q38a_details')->nullable();
            $table->boolean('q38b')->nullable();
            $table->text('q38b_details')->nullable();
            $table->boolean('q39')->nullable();
            $table->text('q39_details')->nullable();
            $table->boolean('q40a')->nullable();
            $table->text('q40a_details')->nullable();
            $table->boolean('q40b')->nullable();
            $table->text('q40b_details')->nullable();
            $table->boolean('q41')->nullable();
            $table->text('q41_details')->nullable();
            $table->date('signature_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c4_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('tel_no')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pds_c4_government_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pds_submission_id')->constrained('pds_submissions')->cascadeOnDelete();
            $table->string('govt_id_type')->nullable();
            $table->string('govt_id_no')->nullable();
            $table->date('id_issue_date')->nullable();
            $table->string('id_issue_place')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_c4_government_ids');
        Schema::dropIfExists('pds_c4_references');
        Schema::dropIfExists('pds_c4_declarations');
    }
};
