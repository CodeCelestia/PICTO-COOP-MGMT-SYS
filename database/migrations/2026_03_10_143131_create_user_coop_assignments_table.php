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
        Schema::create('user_coop_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('coop_id')->nullable(); // FK to cooperatives (will be added later)
            $table->enum('access_level', ['Full Access', 'View Only', 'Report Only'])->default('View Only');
            $table->string('assigned_by');
            $table->date('assigned_at');
            $table->date('expires_at')->nullable();
            $table->enum('status', ['Active', 'Revoked', 'Expired'])->default('Active');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index('coop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_coop_assignments');
    }
};
