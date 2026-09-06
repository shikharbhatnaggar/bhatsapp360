<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'campaign_id', 'customer_id', 'message_template_id', 'whatsapp_account_id',
        'direction', 'wamid', 'type', 'pricing_category', 'status', 'body_preview', 'payload',
        'error', 'price', 'currency', 'conversation_id',
        'sent_at', 'delivered_at', 'read_at', 'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'error' => 'array',
            'price' => 'decimal:4',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(MessageStatusEvent::class)->orderBy('occurred_at');
    }

    /** Delivery moves forward only; a late `sent` never overwrites `read`. */
    public function statusRank(): int
    {
        return match ($this->status) {
            'queued' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3,
            'failed' => 4, 'received' => 5, default => 0,
        };
    }

    public static function rankFor(string $status): int
    {
        return match ($status) {
            'queued' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3,
            'failed' => 4, 'received' => 5, default => 0,
        };
    }
}
