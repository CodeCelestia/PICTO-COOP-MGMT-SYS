<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->foreignId('financial_record_id')->nullable()->constrained('financial_records')->nullOnDelete();
            $table->enum('support_type', ['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other']);
            $table->string('provider_name');
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('date_granted')->nullable();
            $table->date('date_completed')->nullable();
            $table->enum('status', ['Ongoing', 'Completed', 'Pending']);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_supports');
    }
};
