<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'message_template_id', 'whatsapp_account_id', 'name', 'status',
        'recipients_count', 'unit_price', 'estimated_cost', 'actual_cost', 'currency',
        'variable_values', 'scheduled_at', 'started_at', 'completed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'variable_values' => 'array',
            'unit_price' => 'decimal:4',
            'estimated_cost' => 'decimal:4',
            'actual_cost' => 'decimal:4',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function counts(): array
    {
        $rows = $this->messages()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'queued' => (int) ($rows['queued'] ?? 0),
            'sent' => (int) ($rows['sent'] ?? 0),
            'delivered' => (int) ($rows['delivered'] ?? 0),
            'read' => (int) ($rows['read'] ?? 0),
            'failed' => (int) ($rows['failed'] ?? 0),
        ];
    }
}
