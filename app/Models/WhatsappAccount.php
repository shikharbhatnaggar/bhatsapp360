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

        /** The value the demo seeder plants, so we can tell it apart from a real token. */
    public const PLACEHOLDER_TOKEN = 'EAAG-sandbox-token-replace-me';

    /**
     * A usable token is present and is not the seeded placeholder. Meta tokens
     * are long and start with EAA; anything short is a paste that went wrong.
     */
    public function hasUsableToken(): bool
    {
        $token = (string) $this->access_token;

        return $token !== ''
            && $token !== self::PLACEHOLDER_TOKEN
            && strlen($token) > 40;
    }

    /** True when a live Graph call would fail for a reason we can explain up front. */
    public function blockedReason(): ?string
    {
        if (config('whatsapp.sandbox')) {
            return null;
        }

        if (! $this->hasUsableToken()) {
            return 'Sandbox mode is off but this workspace is still using the demo access token. '
                .'Paste your system user token in WhatsApp settings, save, then run Test connection.';
        }

        return null;
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
