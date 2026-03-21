<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cooperative_id')->nullable()->constrained('cooperatives')->nullOnDelete();
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->json('c1_data')->nullable();
            $table->json('c2_data')->nullable();
            $table->json('c3_data')->nullable();
            $table->json('c4_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_submissions');
    }
};
