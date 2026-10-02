<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline');
            $table->text('bio')->nullable();
            $table->string('country', 2)->default('IR');
            $table->string('city')->nullable();
            $table->string('timezone')->default('Asia/Tehran');
            $table->unsignedTinyInteger('years_experience')->default(0);
            $table->json('industries')->nullable();
            $table->json('serves_countries')->nullable();
            $table->json('collaboration_types')->nullable();
            $table->json('certifications')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->unsignedTinyInteger('max_active_cases')->default(5);
            $table->boolean('is_available')->default(true);
            $table->timestamp('nda_accepted_at')->nullable();
            $table->string('nda_version', 20)->nullable();
            $table->string('verification_status', 20)->default('draft');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('avg_response_minutes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expert_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(3);
            $table->unsignedTinyInteger('years')->default(0);
            $table->timestamps();
            $table->unique(['expert_profile_id', 'case_category_id']);
        });

        Schema::create('expert_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
            $table->string('language', 5);
            $table->string('proficiency', 20)->default('fluent');
            $table->timestamps();
            $table->unique(['expert_profile_id', 'language']);
        });

        Schema::create('expert_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });

        Schema::create('expert_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->default('certificate');
            $table->string('title')->nullable();
            $table->string('disk', 20);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->string('scan_status', 20)->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expert_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->json('checklist')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['expert_verifications', 'expert_documents', 'expert_availability', 'expert_languages', 'expert_skills', 'expert_profiles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
