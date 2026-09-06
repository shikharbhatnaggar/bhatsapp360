<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('status', ['draft', 'queued', 'sending', 'completed', 'failed', 'cancelled'])
                ->default('draft');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->decimal('unit_price', 10, 4)->default(0);
            $table->decimal('estimated_cost', 12, 4)->default(0);
            $table->decimal('actual_cost', 12, 4)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->json('variable_values')->nullable();   // resolved param mapping used for this run
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['outbound', 'inbound']);
            $table->string('wamid')->nullable()->index();
            $table->string('type')->default('template');   // template|text|image|video|document|interactive
            $table->enum('pricing_category', ['MARKETING', 'UTILITY', 'AUTHENTICATION', 'SERVICE'])
                ->default('MARKETING');
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed', 'received'])
                ->default('queued');
            $table->text('body_preview')->nullable();      // rendered text shown in the UI
            $table->json('payload')->nullable();           // exact Graph request/response body
            $table->json('error')->nullable();
            $table->decimal('price', 10, 4)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('conversation_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'direction', 'created_at']);
            $table->index(['tenant_id', 'status']);
        });

        // Immutable receipt trail: one row per status callback from WhatsApp.
        Schema::create('message_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('source')->default('webhook');  // webhook|api|system
            $table->json('raw')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['message_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_status_events');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('campaigns');
    }
};
