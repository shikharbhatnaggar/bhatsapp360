<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'email', 'type', 'country_code',
        'attributes', 'tags', 'opted_in', 'status', 'last_inbound_at', 'last_outbound_at',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'tags' => 'array',
            'opted_in' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** The 24-hour customer service window opened by the last inbound message. */
    public function serviceWindowOpen(): bool
    {
        return $this->last_inbound_at !== null && $this->last_inbound_at->gt(now()->subDay());
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0] ?? $this->name;
    }

    /** Resolve a template merge field such as `first_name` or a custom attribute. */
    public function mergeField(string $field): ?string
    {
        return match ($field) {
            'name' => $this->name,
            'first_name' => $this->firstName(),
            'phone' => $this->phone,
            'email' => $this->email,
            default => data_get((array) $this->getAttribute('attributes'), $field),
        };
    }
}
