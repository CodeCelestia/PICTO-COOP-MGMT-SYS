<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `account_type` ENUM('Super Admin','Provincial Admin','Coop Admin','Officer','Committee Member','Member','Viewer') NOT NULL DEFAULT 'Member'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `account_type` ENUM('Provincial Admin','Coop Admin','Officer','Committee Member','Member','Viewer') NOT NULL DEFAULT 'Member'");
    }
};
