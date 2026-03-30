<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperative_cooperative_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->foreignId('cooperative_type_id')->constrained('cooperative_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cooperative_id', 'cooperative_type_id'], 'coop_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperative_cooperative_type');
    }
};
