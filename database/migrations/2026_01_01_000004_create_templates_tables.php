<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                          // lowercase_snake, unique per WABA
            $table->string('language', 15)->default('en_US');
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION']);
            $table->string('sub_category')->nullable();      // e.g. media_card_carousel
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'PAUSED', 'DISABLED'])
                ->default('DRAFT');
            $table->string('whatsapp_template_id')->nullable();
            $table->json('components');                      // Graph-shaped component array
            $table->json('variable_map')->nullable();        // {{1}} -> customer field / static value
            $table->text('rejected_reason')->nullable();
            $table->string('quality_score')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'language']);
            $table->index(['tenant_id', 'status']);
        });

        // Every create/edit is a reviewable event: keep the submitted snapshot.
        Schema::create('template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->enum('action', ['created', 'updated', 'resubmitted', 'category_changed']);
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'PAUSED', 'DISABLED']);
            $table->json('components');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_versions');
        Schema::dropIfExists('message_templates');
    }
};
