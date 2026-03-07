<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('sdn_id')
                ->nullable()
                ->after('id')
                ->constrained('sdns')
                ->nullOnDelete();

            $table->boolean('allow_self_registration')
                ->default(false)
                ->after('status')
                ->comment('Allow members to self-register under this office');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropForeign(['sdn_id']);
            $table->dropColumn(['sdn_id', 'allow_self_registration']);
        });
    }
};
