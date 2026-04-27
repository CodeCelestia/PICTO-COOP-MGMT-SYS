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
        DB::statement("ALTER TABLE members MODIFY membership_type ENUM('Regular', 'Associate', 'Honorary') NOT NULL DEFAULT 'Regular'");
        DB::statement("ALTER TABLE members MODIFY educational_attainment ENUM('No Formal Education', 'Elementary', 'High School', 'Vocational', 'College', 'Post-Graduate') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE members MODIFY membership_type ENUM('Regular', 'Associate') NOT NULL DEFAULT 'Regular'");
        DB::statement("ALTER TABLE members MODIFY educational_attainment ENUM('No Formal Education', 'Elementary', 'High School', 'Vocational', 'College', 'Post-Graduate') NULL");
    }
};