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
        Schema::create('officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('coop_id')->constrained('cooperatives')->onDelete('cascade');
            $table->string('position');
            $table->string('committee')->nullable();
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->enum('status', ['Active', 'Retired', 'Removed', 'Resigned'])->default('Active');
            $table->timestamps();

            $table->index('member_id');
            $table->index('coop_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officers');
    }
};
