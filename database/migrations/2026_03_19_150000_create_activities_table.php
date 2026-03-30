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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other']);
            $table->date('date_started')->nullable();
            $table->date('date_ended')->nullable();
            $table->enum('status', ['Planned', 'In Progress', 'Completed', 'Cancelled'])->default('Planned');
            $table->foreignId('responsible_officer_id')->nullable()->constrained('officers')->nullOnDelete();
            $table->string('funding_source')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('actual_expense', 15, 2)->nullable();
            $table->unsignedInteger('target_member_beneficiaries')->nullable();
            $table->unsignedInteger('target_community_beneficiaries')->nullable();
            $table->unsignedInteger('actual_member_beneficiaries')->nullable();
            $table->unsignedInteger('actual_community_beneficiaries')->nullable();
            $table->string('implementing_partner')->nullable();
            $table->text('outcomes')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('coop_id');
            $table->index('category');
            $table->index('status');
            $table->index('responsible_officer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
