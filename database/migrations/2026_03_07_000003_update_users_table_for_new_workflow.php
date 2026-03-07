<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SDN affiliation (for coop_sdn_admin — which SDN they own)
            $table->foreignId('sdn_id')
                ->nullable()
                ->after('pds_id')
                ->constrained('sdns')
                ->nullOnDelete();

            // Primary office assignment (determines scope for officer/member)
            $table->foreignId('office_id')
                ->nullable()
                ->after('sdn_id')
                ->constrained('offices')
                ->nullOnDelete();

            // Optional department within an office (freeform string for now)
            $table->string('department_id')->nullable()->after('office_id');

            // Onboarding/account lifecycle status
            $table->enum('status', ['pending', 'active', 'suspended'])
                ->default('pending')
                ->after('department_id');

            // Role request payload submitted at registration (stored for admin review)
            $table->json('role_request')->nullable()->after('status');

            // Force password change on next login (for admin-created accounts)
            $table->boolean('must_change_password')->default(false)->after('role_request');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sdn_id']);
            $table->dropForeign(['office_id']);
            $table->dropColumn([
                'sdn_id', 'office_id', 'department_id',
                'status', 'role_request', 'must_change_password',
            ]);
        });
    }
};
