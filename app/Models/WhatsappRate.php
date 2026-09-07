<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappRate extends Model
{
    protected $fillable = [
        'tenant_id', 'category', 'country_code', 'dialing_code',
        'meta_base_price', 'markup', 'client_final_price', 'currency', 'is_active', 'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'meta_base_price' => 'decimal:4',
            'markup' => 'decimal:4',
            'client_final_price' => 'decimal:4',
            'is_active' => 'boolean',
            'effective_from' => 'date',
        ];
    }

    /** Keep the charged price consistent with its parts. */
    protected static function booted(): void
    {
        static::saving(function (self $rate) {
            $rate->client_final_price = round((float) $rate->meta_base_price + (float) $rate->markup, 4);
        });
    }

    public function marginPercent(): float
    {
        $price = (float) $this->client_final_price;

        return $price > 0 ? round((float) $this->markup / $price * 100, 1) : 0.0;
    }
}
