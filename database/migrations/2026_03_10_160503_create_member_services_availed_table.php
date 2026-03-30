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
        Schema::create('member_services_availed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('coop_id')->constrained('cooperatives')->onDelete('cascade');
            
            $table->enum('service_type', [
                'Loan',
                'Marketing',
                'Training',
                'Savings',
                'Insurance',
                'Technical Assistance',
                'Other'
            ]);
            $table->string('service_detail')->nullable();
            $table->date('date_availed');
            $table->decimal('amount', 15, 2)->nullable();
            $table->enum('status', ['Active', 'Completed', 'Pending', 'Cancelled'])->default('Active');
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();
            $table->string('recorded_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('member_id');
            $table->index('coop_id');
            $table->index('service_type');
            $table->index('status');
            $table->index('date_availed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_services_availed');
    }
};
