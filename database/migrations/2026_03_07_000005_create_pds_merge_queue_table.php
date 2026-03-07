<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_merge_queue', function (Blueprint $table) {
            $table->id();

            // Type of match that triggered this entry
            $table->enum('match_type', ['exact_id', 'exact_email', 'fuzzy_name_dob', 'suspected'])
                ->index();

            // The new PDS being submitted (potential duplicate)
            $table->foreignId('source_pds_id')
                ->constrained('personal_data_sheets')
                ->cascadeOnDelete();

            // The existing PDS that was matched against
            $table->foreignId('target_pds_id')
                ->constrained('personal_data_sheets')
                ->cascadeOnDelete();

            // The user who triggered detection (could be member self-completing or admin import)
            $table->foreignId('triggered_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Admin who reviewed the merge request
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->index();

            $table->text('notes')->nullable();

            // Scope context for scoped admin visibility
            $table->foreignId('sdn_id')
                ->nullable()
                ->constrained('sdns')
                ->nullOnDelete();

            $table->foreignId('office_id')
                ->nullable()
                ->constrained('offices')
                ->nullOnDelete();

            // Raw match data (IDs matched, similarity scores, etc.)
            $table->json('match_context')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_merge_queue');
    }
};
