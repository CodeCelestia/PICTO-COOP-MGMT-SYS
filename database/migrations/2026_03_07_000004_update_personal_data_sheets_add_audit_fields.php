<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            // Audit: which user created this PDS (admin vs self-registered)
            $table->foreignId('created_by')
                ->nullable()
                ->after('office_id')
                ->constrained('users')
                ->nullOnDelete();

            // Duplicate detection: if this PDS was flagged as duplicate of another
            $table->foreignId('duplicate_of')
                ->nullable()
                ->after('created_by')
                ->constrained('personal_data_sheets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['duplicate_of']);
            $table->dropColumn(['created_by', 'duplicate_of']);
        });
    }
};
