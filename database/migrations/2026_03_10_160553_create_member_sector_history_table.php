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
        Schema::create('member_sector_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            
            $table->string('previous_sector')->nullable();
            $table->string('new_sector');
            $table->string('previous_livelihood')->nullable();
            $table->string('new_livelihood')->nullable();
            $table->text('change_reason')->nullable();
            $table->string('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            
            // Indexes
            $table->index('member_id');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_sector_history');
    }
};
