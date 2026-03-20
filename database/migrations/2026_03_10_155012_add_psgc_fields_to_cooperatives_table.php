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
        Schema::table('cooperatives', function (Blueprint $table) {
            $table->string('region')->after('province')->nullable();
            $table->string('city_municipality')->after('region')->nullable();
            $table->string('barangay')->after('city_municipality')->nullable();
            
            $table->index('region');
            $table->index('city_municipality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropIndex(['city_municipality']);
            $table->dropColumn(['region', 'city_municipality', 'barangay']);
        });
    }
};
