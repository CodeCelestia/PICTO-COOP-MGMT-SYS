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
            $table->string('classification')->nullable()->default(null);
            $table->date('date_established');
            $table->text('address');
            $table->string('province');
            $table->string('region')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Dissolved', 'Suspended'])->default('Active');
            $table->string('accreditation_status')->nullable(); // Accredited / Pending / Revoked
            $table->date('accreditation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('status');
            $table->index('province');
            $table->index('region');
            $table->index('city_municipality');
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

        Schema::dropIfExists('cooperatives');
    }
};
