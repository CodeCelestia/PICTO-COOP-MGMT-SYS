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
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['board_of_directors', 'audit', 'credit', 'education', 'election', 'ethics', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->integer('term_years')->default(1);
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->enum('status', ['active', 'inactive', 'dissolved'])->default('active');
            $table->text('responsibilities')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['office_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committees');
    }
};
