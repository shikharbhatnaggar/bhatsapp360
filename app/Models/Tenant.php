<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'contact_email', 'country_code', 'currency', 'timezone', 'wallet_balance',
    ];

    protected function casts(): array
    {
        return ['wallet_balance' => 'decimal:4'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function whatsappAccount(): HasOne
    {
        return $this->hasOne(WhatsappAccount::class)->where('is_active', true);
    }

    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsappAccount::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(MessageTemplate::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
