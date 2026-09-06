<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WhatsappAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'label', 'waba_id', 'phone_number_id', 'display_phone_number', 'business_id',
        'access_token', 'app_id', 'app_secret', 'webhook_verify_token', 'graph_version',
        'quality_rating', 'messaging_limit', 'is_active', 'verified_at', 'last_verification_response',
    ];

    protected $hidden = ['access_token', 'app_secret'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'last_verification_response' => 'array',
        ];
    }

    public function maskedToken(): string
    {
        $token = (string) $this->access_token;

        return $token === '' ? '' : substr($token, 0, 6).str_repeat('•', 18).substr($token, -4);
    }

    public function webhookUrl(): string
    {
        return url('/webhooks/whatsapp/'.$this->tenant_id);
    }
}
