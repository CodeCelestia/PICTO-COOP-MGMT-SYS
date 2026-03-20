<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->string('period');
            $table->enum('type', ['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital']);
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('source')->nullable();
            $table->text('purpose')->nullable();
            $table->date('date_recorded')->nullable();
            $table->decimal('total_assets', 15, 2)->nullable();
            $table->decimal('total_liabilities', 15, 2)->nullable();
            $table->decimal('net_surplus', 15, 2)->nullable();
            $table->decimal('capital_build_up', 15, 2)->nullable();
            $table->decimal('external_assistance_received', 15, 2)->nullable();
            $table->enum('type_of_assistance', ['Grant', 'Loan', 'Training', 'Equipment', 'Technical Assistance', 'Other'])->nullable();
            $table->string('reference_doc')->nullable();
            $table->string('recorded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
