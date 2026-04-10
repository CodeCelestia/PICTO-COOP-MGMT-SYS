<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('member_loans')->cascadeOnDelete();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->unsignedInteger('payment_number')->nullable();
            $table->decimal('principal_due', 15, 2)->nullable();
            $table->decimal('interest_due', 15, 2)->nullable();
            $table->decimal('total_due', 15, 2)->nullable();
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->enum('status', ['Pending', 'Paid', 'Partial', 'Missed'])->default('Pending');
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['loan_id', 'status']);
            $table->index(['coop_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
