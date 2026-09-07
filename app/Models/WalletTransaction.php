<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'type', 'direction', 'amount', 'balance_after', 'currency',
        'description', 'reference_type', 'reference_id', 'meta', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Signed amount for display: credits add, debits subtract. */
    public function signedAmount(): float
    {
        return $this->direction === 'credit' ? (float) $this->amount : -(float) $this->amount;
    }
}
