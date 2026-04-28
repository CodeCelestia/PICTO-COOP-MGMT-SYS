<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cooperatives', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->unique();
            $table->enum('classification', ['micro', 'small', 'medium', 'large', 'billion'])->nullable();
            $table->date('date_established');
            $table->text('address');
            $table->string('province');
            $table->string('region')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Dissolved', 'Suspended'])->default('Active');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('status');
            $table->index('province');
            $table->index('region');
            $table->index('city_municipality');
        });

        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->string('level');
            $table->date('date_granted');
            $table->date('valid_until')->nullable();
            $table->string('issuing_body')->nullable()->default('CDA');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cooperative_id', 'date_granted']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('set null');
        });

        Schema::table('user_coop_assignments', function (Blueprint $table) {
            $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_coop_assignments', function (Blueprint $table) {
            $table->dropForeign(['coop_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['coop_id']);
        });

        Schema::dropIfExists('accreditations');

        Schema::dropIfExists('cooperatives');
    }
};
