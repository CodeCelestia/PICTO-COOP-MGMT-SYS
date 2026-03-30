<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
    }

    public function up(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            if (!Schema::hasColumn('cooperatives', 'region')) {
                $table->string('region')->nullable()->after('province');
            }

            if (!Schema::hasColumn('cooperatives', 'city_municipality')) {
                $table->string('city_municipality')->nullable()->after('region');
            }

            if (!Schema::hasColumn('cooperatives', 'barangay')) {
                $table->string('barangay')->nullable()->after('city_municipality');
            }
        });

        Schema::table('cooperatives', function (Blueprint $table) {
            if (Schema::hasColumn('cooperatives', 'region') && !$this->indexExists('cooperatives', 'cooperatives_region_index')) {
                $table->index('region');
            }

            if (Schema::hasColumn('cooperatives', 'city_municipality') && !$this->indexExists('cooperatives', 'cooperatives_city_municipality_index')) {
                $table->index('city_municipality');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            if ($this->indexExists('cooperatives', 'cooperatives_region_index')) {
                $table->dropIndex(['region']);
            }

            if ($this->indexExists('cooperatives', 'cooperatives_city_municipality_index')) {
                $table->dropIndex(['city_municipality']);
            }

            $columns = [];
            foreach (['region', 'city_municipality', 'barangay'] as $column) {
                if (Schema::hasColumn('cooperatives', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
