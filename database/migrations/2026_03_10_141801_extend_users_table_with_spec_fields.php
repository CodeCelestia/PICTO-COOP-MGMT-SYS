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
        Schema::table('users', function (Blueprint $table) {
            // Foreign Keys - linking to other tables
            $table->unsignedBigInteger('coop_id')->nullable()->after('id');
            $table->unsignedBigInteger('member_id')->nullable()->after('coop_id');
            $table->unsignedBigInteger('officer_id')->nullable()->after('member_id');
            
            // Account Type and Status
            $table->enum('account_type', [
                'Provincial Admin',
                'Coop Admin',
                'Officer',
                'Committee Member',
                'Member',
                'Viewer'
            ])->default('Member')->after('officer_id');
            
            $table->enum('account_status', [
                'Active',
                'Inactive',
                'Suspended',
                'Locked',
                'Pending Approval'
            ])->default('Pending Approval')->after('account_type');
            
            // Profile and Tracking Fields
            $table->string('profile_photo')->nullable()->after('account_status');
            $table->timestamp('last_login_at')->nullable()->after('profile_photo');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            $table->string('created_by')->nullable()->after('password_changed_at');
            
            // Add indexes for foreign keys (for future use when tables exist)
            $table->index('coop_id');
            $table->index('member_id');
            $table->index('officer_id');
            $table->index('account_status');
            $table->index('account_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['coop_id']);
            $table->dropIndex(['member_id']);
            $table->dropIndex(['officer_id']);
            $table->dropIndex(['account_status']);
            $table->dropIndex(['account_type']);
            
            // Drop columns
            $table->dropColumn([
                'coop_id',
                'member_id',
                'officer_id',
                'account_type',
                'account_status',
                'profile_photo',
                'last_login_at',
                'password_changed_at',
                'created_by',
            ]);
        });
    }
};
