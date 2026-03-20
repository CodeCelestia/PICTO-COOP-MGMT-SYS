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
        // Add foreign key to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('set null');
        });

        // Add foreign key to user_coop_assignments table
        Schema::table('user_coop_assignments', function (Blueprint $table) {
            $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['coop_id']);
        });

        Schema::table('user_coop_assignments', function (Blueprint $table) {
            $table->dropForeign(['coop_id']);
        });
    }
};
