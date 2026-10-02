<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 30)->default('intake');
            $table->string('status', 20)->default('open');
            $table->string('provider', 40)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedSmallInteger('turns')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_session_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignId('category_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->string('urgency', 20)->nullable();
            $table->decimal('confidence', 4, 3)->default(0);
            $table->text('summary')->nullable();
            $table->json('facts')->nullable();
            $table->json('missing_information')->nullable();
            $table->json('suggested_actions')->nullable();
            $table->json('required_expertises')->nullable();
            $table->json('guidance')->nullable();
            $table->json('safety_flags')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('needs_expert')->default(true);
            $table->string('verification_state', 30)->default('ai_suggested');
            $table->string('provider', 40)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('ai_analysis_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->string('urgency', 20)->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->string('source', 30);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_human_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('ai_analysis_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 30)->default('low_confidence');
            $table->string('status', 20)->default('pending');
            $table->foreignId('ai_category_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->foreignId('ai_subcategory_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->string('ai_urgency', 20)->nullable();
            $table->decimal('ai_confidence', 4, 3)->nullable();
            $table->foreignId('final_category_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->foreignId('final_subcategory_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->string('final_urgency', 20)->nullable();
            $table->boolean('category_agreed')->nullable();
            $table->boolean('urgency_agreed')->nullable();
            $table->string('decision', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('expert_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->json('breakdown')->nullable();
            $table->json('reasons')->nullable();
            $table->string('status', 30)->default('proposed');
            $table->string('source', 20)->default('engine');
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->timestamp('business_decided_at')->nullable();
            $table->timestamp('expert_decided_at')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'expert_profile_id']);
        });

        Schema::create('case_experts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('expert_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expert_match_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 20)->default('lead');
            $table->string('status', 20)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'expert_profile_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->nullable()->constrained('cases')->cascadeOnDelete();
            $table->string('type', 20)->default('case');
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('type', 20)->default('text');
            $table->text('body')->nullable();
            $table->json('mentions')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['conversation_id', 'id']);
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 20);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->string('scan_status', 20)->default('pending');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['message_attachments', 'messages', 'conversation_members', 'conversations', 'case_experts', 'expert_matches', 'ai_human_reviews', 'ai_classifications', 'ai_analyses', 'ai_messages', 'ai_sessions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
