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
        Schema::create('account_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('previous_status', ['Active', 'Inactive', 'Suspended', 'Locked', 'Pending Approval']);
            $table->enum('new_status', ['Active', 'Inactive', 'Suspended', 'Locked', 'Pending Approval']);
            $table->text('change_reason');
            $table->string('changed_by');
            $table->timestamp('changed_at');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'changed_at']);
            $table->index('new_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_status_histories');
    }
};
