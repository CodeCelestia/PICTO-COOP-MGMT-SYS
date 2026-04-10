<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_savings_id')->constrained('member_savings')->cascadeOnDelete();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->enum('type', ['Deposit', 'Withdrawal', 'Interest']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_savings_id', 'type']);
            $table->index(['coop_id', 'type']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
    }
};
