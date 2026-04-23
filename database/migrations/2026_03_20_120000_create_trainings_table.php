<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->string('title');
            $table->date('date_conducted')->nullable();
            $table->string('facilitator')->nullable();
            $table->text('skills_targeted')->nullable();
            $table->string('venue')->nullable();
            $table->enum('target_group', ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other']);
            $table->unsignedInteger('no_of_participants')->nullable();
            $table->boolean('follow_up_needed')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_remarks')->nullable();
            $table->enum('status', ['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
