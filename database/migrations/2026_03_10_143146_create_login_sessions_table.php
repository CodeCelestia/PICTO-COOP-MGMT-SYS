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
        Schema::create('login_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->string('ip_address', 45); // IPv6 support
            $table->text('device_info')->nullable(); // User-Agent string
            $table->enum('login_status', ['Success', 'Failed', 'Locked Out'])->default('Success');
            $table->string('fail_reason')->nullable();
            $table->string('session_token')->unique()->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'login_at']);
            $table->index(['user_id', 'login_status']);
            $table->index('session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_sessions');
    }
};
