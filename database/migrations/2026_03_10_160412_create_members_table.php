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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coop_id')->constrained('cooperatives')->onDelete('cascade');
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->enum('gender', ['Male', 'Female', 'Other']);
            
            // Contact Information
            $table->text('address');
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
            // Membership Details
            $table->date('date_joined');
            $table->enum('membership_type', ['Regular', 'Associate'])->default('Regular');
            $table->enum('membership_status', ['Active', 'Suspended', 'Resigned', 'Deceased'])->default('Active');
            $table->decimal('share_capital', 15, 2)->default(0);
            
            // Socio-Economic Profile
            $table->enum('educational_attainment', [
                'No Formal Education',
                'Elementary',
                'High School',
                'Vocational',
                'College',
                'Post-Graduate'
            ])->nullable();
            $table->string('primary_livelihood')->nullable();
            $table->enum('sector', [
                'Farmer',
                'FisherFolk',
                'Employee',
                'Entrepreneur',
                'Youth',
                'Women',
                'Senior Citizen',
                'PWD',
                'Other'
            ])->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('coop_id');
            $table->index('membership_status');
            $table->index('sector');
            $table->index(['first_name', 'last_name']);
        });

        Schema::create('member_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['member_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_roles');
        Schema::dropIfExists('members');
    }
};
