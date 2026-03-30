<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $result = DB::select(
            'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$table, $constraint]
        );

        return !empty($result);
    }

    public function up(): void
    {
        if (Schema::hasColumn('users', 'coop_id')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->foreignKeyExists('users', 'users_coop_id_foreign')) {
                    $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('set null');
                }
            });
        }

        if (Schema::hasColumn('user_coop_assignments', 'coop_id')) {
            Schema::table('user_coop_assignments', function (Blueprint $table) {
                if (!$this->foreignKeyExists('user_coop_assignments', 'user_coop_assignments_coop_id_foreign')) {
                    $table->foreign('coop_id')->references('id')->on('cooperatives')->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->foreignKeyExists('users', 'users_coop_id_foreign')) {
                $table->dropForeign(['coop_id']);
            }
        });

        Schema::table('user_coop_assignments', function (Blueprint $table) {
            if ($this->foreignKeyExists('user_coop_assignments', 'user_coop_assignments_coop_id_foreign')) {
                $table->dropForeign(['coop_id']);
            }
        });
    }
};
