<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Primary number');
            $table->string('waba_id');                       // WhatsApp Business Account ID
            $table->string('phone_number_id');               // Sender phone number ID
            $table->string('display_phone_number')->nullable();
            $table->string('business_id')->nullable();
            $table->text('access_token');                    // encrypted cast
            $table->string('app_id')->nullable();
            $table->text('app_secret')->nullable();          // encrypted cast, verifies webhook signature
            $table->string('webhook_verify_token');
            $table->string('graph_version')->default('v21.0');
            $table->string('quality_rating')->nullable();
            $table->string('messaging_limit')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('last_verification_response')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone_number_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
