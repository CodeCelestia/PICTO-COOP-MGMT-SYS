<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->json('changes')->nullable()->after('properties');
            $table->string('ip_address')->nullable()->after('changes');
            $table->string('user_name')->nullable()->after('ip_address');
            $table->string('module_name')->nullable()->after('user_name');
            $table->enum('action', ['created', 'updated', 'deleted'])->nullable()->after('module_name');
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropColumn(['changes', 'ip_address', 'user_name', 'module_name', 'action']);
        });
    }
};
