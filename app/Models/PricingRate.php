<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRate extends Model
{
    protected $fillable = ['tenant_id', 'country_code', 'category', 'price', 'currency'];

    protected function casts(): array
    {
        return ['price' => 'decimal:4'];
    }
}
