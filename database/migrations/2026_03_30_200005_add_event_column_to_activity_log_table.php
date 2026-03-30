<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (!Schema::connection($connection)->hasColumn($tableName, 'event')) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                $table->string('event')->nullable();
            });
        }
    }

    public function down(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (Schema::connection($connection)->hasColumn($tableName, 'event')) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                $table->dropColumn('event');
            });
        }
    }
};
