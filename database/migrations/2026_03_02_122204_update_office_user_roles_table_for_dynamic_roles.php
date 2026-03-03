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
        Schema::table('office_user_roles', function (Blueprint $table) {
            // Drop the old enum column if it exists
            if (Schema::hasColumn('office_user_roles', 'role_name')) {
                $table->dropColumn('role_name');
            }
        });

        Schema::table('office_user_roles', function (Blueprint $table) {
            // Add foreign key to office_roles if not already exists
            if (!Schema::hasColumn('office_user_roles', 'office_role_id')) {
                $table->foreignId('office_role_id')->after('user_id')->constrained('office_roles')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_user_roles', function (Blueprint $table) {
            $table->dropForeign(['office_role_id']);
            $table->dropColumn('office_role_id');
        });

        Schema::table('office_user_roles', function (Blueprint $table) {
            $table->enum('role_name', ['member', 'officer', 'committee_member', 'general_manager', 'chairperson'])->after('user_id');
        });
    }
};
