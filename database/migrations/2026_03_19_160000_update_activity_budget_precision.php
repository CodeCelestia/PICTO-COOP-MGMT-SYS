<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE activities MODIFY budget DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE activities MODIFY actual_expense DECIMAL(15,2) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE activities MODIFY budget DECIMAL(12,2) NULL');
        DB::statement('ALTER TABLE activities MODIFY actual_expense DECIMAL(12,2) NULL');
    }
};
