<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_funding_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->string('funder_name');
            $table->enum('funder_type', ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor']);
            $table->decimal('amount_allocated', 15, 2)->nullable();
            $table->decimal('amount_released', 15, 2)->nullable();
            $table->date('date_released')->nullable();
            $table->enum('status', ['Released', 'Pending', 'Partially Released']);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_funding_sources');
    }
};
