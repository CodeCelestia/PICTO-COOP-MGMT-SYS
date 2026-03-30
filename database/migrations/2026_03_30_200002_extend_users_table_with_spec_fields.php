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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'coop_id')) {
                $table->unsignedBigInteger('coop_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('users', 'member_id')) {
                $table->unsignedBigInteger('member_id')->nullable()->after('coop_id');
            }

            if (!Schema::hasColumn('users', 'officer_id')) {
                $table->unsignedBigInteger('officer_id')->nullable()->after('member_id');
            }

            if (!Schema::hasColumn('users', 'account_type')) {
                $table->enum('account_type', [
                    'Provincial Admin',
                    'Coop Admin',
                    'Officer',
                    'Committee Member',
                    'Member',
                    'Viewer',
                ])->default('Member')->after('officer_id');
            }

            if (!Schema::hasColumn('users', 'account_status')) {
                $table->enum('account_status', [
                    'Active',
                    'Inactive',
                    'Suspended',
                    'Locked',
                    'Pending Approval',
                ])->default('Pending Approval')->after('account_type');
            }

            if (!Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('account_status');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('profile_photo');
            }

            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            }

            if (!Schema::hasColumn('users', 'created_by')) {
                $table->string('created_by')->nullable()->after('password_changed_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'coop_id') && !$this->indexExists('users', 'users_coop_id_index')) {
                $table->index('coop_id');
            }

            if (Schema::hasColumn('users', 'member_id') && !$this->indexExists('users', 'users_member_id_index')) {
                $table->index('member_id');
            }

            if (Schema::hasColumn('users', 'officer_id') && !$this->indexExists('users', 'users_officer_id_index')) {
                $table->index('officer_id');
            }

            if (Schema::hasColumn('users', 'account_status') && !$this->indexExists('users', 'users_account_status_index')) {
                $table->index('account_status');
            }

            if (Schema::hasColumn('users', 'account_type') && !$this->indexExists('users', 'users_account_type_index')) {
                $table->index('account_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_coop_id_index')) {
                $table->dropIndex(['coop_id']);
            }

            if ($this->indexExists('users', 'users_member_id_index')) {
                $table->dropIndex(['member_id']);
            }

            if ($this->indexExists('users', 'users_officer_id_index')) {
                $table->dropIndex(['officer_id']);
            }

            if ($this->indexExists('users', 'users_account_status_index')) {
                $table->dropIndex(['account_status']);
            }

            if ($this->indexExists('users', 'users_account_type_index')) {
                $table->dropIndex(['account_type']);
            }

            $columns = [];
            foreach ([
                'coop_id',
                'member_id',
                'officer_id',
                'account_type',
                'account_status',
                'profile_photo',
                'last_login_at',
                'password_changed_at',
                'created_by',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
