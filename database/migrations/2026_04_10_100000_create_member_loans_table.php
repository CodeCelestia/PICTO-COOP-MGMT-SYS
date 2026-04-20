<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->string('name');
            $table->enum('classification', ['micro', 'small', 'medium', 'large'])->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('member_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('loan_type_id')->nullable()->constrained('loan_types')->nullOnDelete();
            $table->decimal('principal', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->unsignedSmallInteger('term_months');
            $table->enum('status', ['Pending', 'Approved', 'Active', 'Completed', 'Defaulted', 'Rejected'])->default('Pending');
            $table->text('purpose')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->decimal('amount_disbursed', 15, 2)->nullable();
            $table->enum('disbursement_method', ['check', 'cash', 'bank_transfer'])->nullable();
            $table->text('disburse_remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['coop_id', 'status']);
            $table->index(['member_id', 'status']);
            $table->index('approved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_loans');
        Schema::dropIfExists('loan_types');
    }
};
