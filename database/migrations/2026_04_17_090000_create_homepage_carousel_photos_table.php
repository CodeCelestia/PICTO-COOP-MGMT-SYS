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
        Schema::create('homepage_carousel_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('original_name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_core')->default(false);
            $table->timestamps();

            $table->index(['is_default', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_carousel_photos');
    }
};
