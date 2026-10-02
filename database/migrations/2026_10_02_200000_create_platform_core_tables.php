<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('keywords')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->string('default_urgency', 20)->default('medium');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('trade_name');
            $table->string('legal_name')->nullable();
            $table->text('registration_number')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('country', 2)->default('IR');
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('industry')->nullable();
            $table->string('employees_range', 20)->nullable();
            $table->string('size', 20)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->text('products_services')->nullable();
            $table->string('preferred_language', 5)->default('fa');
            $table->text('contact_name')->nullable();
            $table->text('contact_email')->nullable();
            $table->text('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->json('main_needs')->nullable();
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['industry', 'country', 'province']);
        });

        Schema::create('business_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->timestamps();
            $table->unique(['business_id', 'user_id']);
        });

        Schema::create('business_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->default('other');
            $table->string('title')->nullable();
            $table->string('disk', 20);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->string('scan_status', 20)->default('pending');
            $table->string('visibility', 30)->default('case_team');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('field', 60);
            $table->string('visibility', 30);
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id', 'field']);
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('version', 20);
            $table->boolean('granted');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['user_id', 'type']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['action', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'audit_logs', 'consents', 'privacy_settings', 'business_documents', 'business_members', 'businesses', 'case_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
