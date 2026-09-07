<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletTopup extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'reference', 'amount', 'currency', 'method', 'status',
        'upi_reference', 'payer_note', 'rejection_reason', 'wallet_transaction_id',
        'requested_by', 'reviewed_by', 'submitted_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $topup) {
            $topup->reference ??= 'BH-'.Str::upper(Str::random(6));
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * UPI intent URI. Scanning this opens the payer's UPI app with the amount
     * and our reference prefilled, so payments can be matched on arrival.
     */
    public function upiUri(): string
    {
        return 'upi://pay?'.http_build_query([
            'pa' => config('wallet.upi.vpa'),
            'pn' => config('wallet.upi.payee_name'),
            'am' => number_format((float) $this->amount, 2, '.', ''),
            'cu' => $this->currency,
            'tn' => $this->reference,
        ]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'submitted'], true);
    }
}
