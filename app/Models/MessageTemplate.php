<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'whatsapp_account_id', 'name', 'language', 'category', 'sub_category',
        'status', 'whatsapp_template_id', 'components', 'variable_map', 'rejected_reason',
        'quality_score', 'version', 'submitted_at', 'approved_at', 'last_synced_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'variable_map' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class)->orderByDesc('version');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function component(string $type): ?array
    {
        foreach ($this->components ?? [] as $component) {
            if (strtoupper($component['type'] ?? '') === strtoupper($type)) {
                return $component;
            }
        }

        return null;
    }

    public function isCarousel(): bool
    {
        return $this->component('CAROUSEL') !== null;
    }

    public function isSendable(): bool
    {
        return $this->status === 'APPROVED';
    }

    /** Placeholder numbers found in the message-bubble body, e.g. [1, 2]. */
    public function bodyVariables(): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', $this->component('BODY')['text'] ?? '', $matches);

        return array_values(array_unique(array_map('intval', $matches[1] ?? [])));
    }

    public function headerVariables(): array
    {
        $header = $this->component('HEADER');
        if (! $header || ($header['format'] ?? 'TEXT') !== 'TEXT') {
            return [];
        }
        preg_match_all('/\{\{(\d+)\}\}/', $header['text'] ?? '', $matches);

        return array_values(array_unique(array_map('intval', $matches[1] ?? [])));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'APPROVED' => 'text-jade-700 bg-jade-50 border-jade-200',
            'PENDING' => 'text-signal-700 bg-signal-50 border-signal-200',
            'REJECTED', 'DISABLED' => 'text-alert-700 bg-alert-50 border-alert-200',
            'PAUSED' => 'text-signal-700 bg-signal-50 border-signal-200',
            default => 'text-ink-500 bg-ink-50 border-ink-200',
        };
    }
}
