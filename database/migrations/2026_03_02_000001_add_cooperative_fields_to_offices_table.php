<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('cooperative_type')->nullable()->after('code');
            $table->string('registration_number')->nullable()->after('cooperative_type');
            $table->date('date_registered')->nullable()->after('registration_number');
            $table->decimal('asset_size', 18, 2)->nullable()->after('date_registered');
            $table->string('classification')->nullable()->after('asset_size');
            $table->string('status')->default('Active')->after('classification');
            $table->json('key_services')->nullable()->after('status');
            $table->unsignedSmallInteger('year_of_latest_audit')->nullable()->after('key_services');
            $table->string('chairperson')->nullable()->after('year_of_latest_audit');
            $table->string('general_manager')->nullable()->after('chairperson');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn([
                'cooperative_type', 'registration_number', 'date_registered',
                'asset_size', 'classification', 'status', 'key_services',
                'year_of_latest_audit', 'chairperson', 'general_manager',
            ]);
        });
    }
};
