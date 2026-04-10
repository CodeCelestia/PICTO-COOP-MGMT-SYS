<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('account_number')->unique();
            $table->enum('account_status', ['Active', 'Dormant', 'Closed'])->default('Active');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('interest_rate', 5, 2)->default(3.00);
            $table->date('opened_at');
            $table->date('closed_at')->nullable();
            $table->timestamp('last_interest_calculated')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('member_id');
            $table->index(['coop_id', 'account_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_savings');
    }
};
