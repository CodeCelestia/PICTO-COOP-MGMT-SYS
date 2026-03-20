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
        Schema::create('user_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('assigned_by')->nullable()->comment('Admin who assigned the role');
            $table->date('assigned_at')->default(now())->comment('Date the role was assigned');
            $table->date('expires_at')->nullable()->comment('Role expiry date (nullable if permanent)');
            $table->enum('status', ['Active', 'Expired', 'Revoked'])->default('Active');
            $table->text('remarks')->nullable()->comment('Reason for role assignment or revocation');
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'role_id', 'status']);
            $table->index('assigned_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_role_assignments');
    }
};
