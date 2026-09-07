<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Meta's cost, your margin, and what the client pays — kept as three
        // separate figures so margin is reportable rather than implied.
        Schema::create('whatsapp_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete(); // null = platform default
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION', 'SERVICE']);
            $table->char('country_code', 2)->default('IN');       // ISO-3166, matches customers.country_code
            $table->string('dialing_code', 5)->default('91');     // for reference and phone-prefix lookups
            $table->decimal('meta_base_price', 10, 4)->default(0);
            $table->decimal('markup', 10, 4)->default(0);
            $table->decimal('client_final_price', 10, 4)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'country_code', 'category'], 'whatsapp_rate_scope_unique');
            $table->index(['category', 'country_code']);
        });

        // Append-only ledger. tenants.wallet_balance is a cached running total;
        // this table is the source of truth.
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['topup', 'debit', 'refund', 'adjustment']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 12, 4);                 // always positive; direction carries the sign
            $table->decimal('balance_after', 12, 4);
            $table->string('currency', 3)->default('INR');
            $table->string('description');
            $table->string('reference_type')->nullable();     // Message, WalletTopup, Campaign
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('meta')->nullable();                 // meta_cost, markup, category
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Manual UPI top-ups: customer pays, submits the UTR, operator approves.
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();            // shown in the UPI note, e.g. BH-7F3K2M
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('method')->default('upi');
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected'])->default('pending');
            $table->string('upi_reference')->nullable();      // UTR / transaction ID from the payer
            $table->string('payer_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::table('messages', function (Blueprint $table) {
            // price stays the client-facing charge; these two make margin reportable.
            $table->decimal('meta_cost', 10, 4)->default(0)->after('price');
            $table->decimal('markup', 10, 4)->default(0)->after('meta_cost');
            $table->timestamp('charged_at')->nullable()->after('markup');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_platform_admin'));
        Schema::table('messages', fn (Blueprint $table) => $table->dropColumn(['meta_cost', 'markup', 'charged_at']));
        Schema::dropIfExists('wallet_topups');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('whatsapp_rates');
    }
};
