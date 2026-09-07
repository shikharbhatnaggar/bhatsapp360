<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\WalletTransaction;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Prepaid wallet.
 *
 * `tenants.wallet_balance` is a cached running total; `wallet_transactions` is
 * the ledger and the source of truth. Every change locks the tenant row inside
 * a transaction, so two concurrent sends cannot both spend the last rupee.
 */
class WalletService
{
    public function balance(Tenant $tenant): float
    {
        return (float) $tenant->fresh()->wallet_balance;
    }

    public function canAfford(Tenant $tenant, float $amount): bool
    {
        return $this->balance($tenant) >= $amount;
    }

    /** Money in: an approved top-up, a refund, or a manual adjustment. */
    public function credit(
        Tenant $tenant,
        float $amount,
        string $type,
        string $description,
        ?Model $reference = null,
        array $meta = [],
        ?int $userId = null,
    ): WalletTransaction {
        return $this->record($tenant, 'credit', $amount, $type, $description, $reference, $meta, $userId);
    }

    /**
     * Money out. Returns null when the balance will not cover it — the caller
     * decides what to do rather than being thrown at.
     */
    public function debit(
        Tenant $tenant,
        float $amount,
        string $type,
        string $description,
        ?Model $reference = null,
        array $meta = [],
        ?int $userId = null,
    ): ?WalletTransaction {
        try {
            return $this->record($tenant, 'debit', $amount, $type, $description, $reference, $meta, $userId);
        } catch (InsufficientBalance $e) {
            return null;
        }
    }

    protected function record(
        Tenant $tenant,
        string $direction,
        float $amount,
        string $type,
        string $description,
        ?Model $reference,
        array $meta,
        ?int $userId,
    ): WalletTransaction {
        $amount = round(abs($amount), 4);

        return DB::transaction(function () use ($tenant, $direction, $amount, $type, $description, $reference, $meta, $userId) {
            // Lock so concurrent sends serialise on this row.
            $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $balance = (float) $locked->wallet_balance;

            if ($direction === 'debit' && $balance < $amount) {
                throw new InsufficientBalance(
                    'Wallet balance '.$balance.' is short of '.$amount
                );
            }

            $after = round($direction === 'credit' ? $balance + $amount : $balance - $amount, 4);

            $locked->forceFill(['wallet_balance' => $after])->save();
            $tenant->wallet_balance = $after;

            return WalletTransaction::withoutGlobalScope('tenant')->create([
                'tenant_id' => $tenant->id,
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'balance_after' => $after,
                'currency' => $tenant->currency ?: 'INR',
                'description' => $description,
                'reference_type' => $reference ? class_basename($reference) : null,
                'reference_id' => $reference?->getKey(),
                'meta' => $meta ?: null,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Give back what a message cost, once — used when WhatsApp reports a send
     * as undeliverable after we already charged for it.
     */
    public function refundMessage(Model $message): ?WalletTransaction
    {
        $charge = WalletTransaction::withoutGlobalScope('tenant')
            ->where('reference_type', 'Message')
            ->where('reference_id', $message->getKey())
            ->where('type', 'debit')
            ->first();

        if (! $charge) {
            return null;
        }

        $alreadyRefunded = WalletTransaction::withoutGlobalScope('tenant')
            ->where('reference_type', 'Message')
            ->where('reference_id', $message->getKey())
            ->where('type', 'refund')
            ->exists();

        if ($alreadyRefunded) {
            return null;
        }

        $tenant = Tenant::find($message->tenant_id);

        if (! $tenant) {
            return null;
        }

        $refund = $this->credit(
            $tenant,
            (float) $charge->amount,
            'refund',
            'Refund for undelivered message to '.($message->customer?->phone ?? 'recipient'),
            $message,
            ['original_transaction_id' => $charge->id],
        );

        ActivityLogger::log(
            'wallet.refunded',
            'Refunded '.$charge->amount.' for an undelivered message',
            $message,
            ['transaction_id' => $refund->id],
            $tenant->id,
        );

        return $refund;
    }
}
