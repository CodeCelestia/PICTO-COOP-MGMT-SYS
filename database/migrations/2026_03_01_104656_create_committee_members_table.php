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
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->enum('position', ['chairperson', 'vice_chairperson', 'secretary', 'treasurer', 'member'])->default('member');
            $table->date('appointed_date');
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->enum('status', ['active', 'inactive', 'resigned'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('appointed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['committee_id', 'member_id']);
            $table->index(['committee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
