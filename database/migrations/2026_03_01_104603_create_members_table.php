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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('pds_id')->constrained('personal_data_sheets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            
            // Member Information
            $table->string('member_number')->unique();
            $table->enum('member_type', ['regular', 'associate', 'provisional'])->default('regular');
            $table->enum('status', ['active', 'inactive', 'resigned', 'suspended', 'deceased'])->default('active');
            $table->date('date_joined');
            $table->date('date_approved')->nullable();
            $table->date('date_left')->nullable();
            
            // Financial Information
            $table->decimal('share_capital', 15, 2)->default(0);
            $table->decimal('savings_balance', 15, 2)->default(0);
            $table->decimal('loan_balance', 15, 2)->default(0);
            
            // Additional Information
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // Metadata
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('member_number');
            $table->index('status');
            $table->index('office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
