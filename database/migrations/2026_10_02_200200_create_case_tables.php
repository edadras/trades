<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('case_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('input_mode', 10)->default('text');
            $table->string('voice_path')->nullable();
            $table->text('voice_transcript')->nullable();
            $table->string('locale', 5)->default('fa');
            $table->foreignId('category_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->string('urgency', 20)->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->string('classification_source', 30)->nullable();
            $table->text('summary')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('needs_expert')->default(true);
            $table->string('status', 30)->default('draft');
            $table->text('next_action')->nullable();
            $table->string('next_action_owner', 20)->nullable();
            $table->timestamp('next_action_due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('first_reviewed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'urgency']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('case_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('case_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('disk', 20);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->string('scan_status', 20)->default('pending');
            $table->boolean('is_requested')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('case_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('question_key', 60)->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->string('source', 20)->default('ai');
            $table->timestamps();
        });

        Schema::create('case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visibility', 20)->default('internal');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('case_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('owner_role', 20)->default('business');
            $table->string('status', 20)->default('open');
            $table->boolean('is_next_action')->default(false);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_at']);
        });

        Schema::create('case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 60);
            $table->string('visibility', 20)->default('team');
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['case_id', 'created_at']);
        });

        Schema::create('case_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('cases')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome', 30);
            $table->text('reason');
            $table->text('result_summary')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamps();
        });

        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('problem_solved')->nullable();
            $table->boolean('would_recommend_expert')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'user_id']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('agenda')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->json('attendee_ids')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        foreach (['appointments', 'satisfaction_surveys', 'case_outcomes', 'case_events', 'case_tasks', 'case_notes', 'case_answers', 'case_documents', 'case_status_history', 'cases'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
