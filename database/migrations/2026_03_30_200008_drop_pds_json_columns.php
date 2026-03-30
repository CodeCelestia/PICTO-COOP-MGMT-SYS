<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pds_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('pds_submissions', 'c1_data')) {
                $table->dropColumn('c1_data');
            }

            if (Schema::hasColumn('pds_submissions', 'c2_data')) {
                $table->dropColumn('c2_data');
            }

            if (Schema::hasColumn('pds_submissions', 'c3_data')) {
                $table->dropColumn('c3_data');
            }

            if (Schema::hasColumn('pds_submissions', 'c4_data')) {
                $table->dropColumn('c4_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pds_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('pds_submissions', 'c1_data')) {
                $table->json('c1_data')->nullable();
            }

            if (!Schema::hasColumn('pds_submissions', 'c2_data')) {
                $table->json('c2_data')->nullable();
            }

            if (!Schema::hasColumn('pds_submissions', 'c3_data')) {
                $table->json('c3_data')->nullable();
            }

            if (!Schema::hasColumn('pds_submissions', 'c4_data')) {
                $table->json('c4_data')->nullable();
            }
        });
    }
};
