<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $token;
    protected string $phoneId;
    protected string $wabaId;
    protected string $baseUrl;

    public function __construct()
    {
        // Pulls variables from your Wasmer environment settings
        $this->token = env('WHATSAPP_TOKEN');
        $this->phoneId = env('WHATSAPP_PHONE_NUMBER_ID');
        $this->wabaId = env('WHATSAPP_BUSINESS_ACCOUNT_ID');
        $this->baseUrl = "https://facebook.com{$this->phoneId}/messages";
    }

    /**
     * Send a template message (e.g., the default 'hello_world')
     */
    public function sendTemplate(string $toMobileNumber, string $templateName, string $languageCode = 'en_US')
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toMobileNumber,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];

        return $this->executeRequest($payload);
    }

    /**
     * Send a regular text reply (Only works within 24 hours of user's last message)
     */
    public function sendText(string $toMobileNumber, string $messageText)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $toMobileNumber,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $messageText
            ]
        ];

        return $this->executeRequest($payload);
    }

    private function executeRequest(array $payload)
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->post($this->baseUrl, $payload);

        if ($response->failed()) {
            Log::error('WhatsApp API outbound send failure:', $response->json() ?? [$response->body()]);
            return false;
        }

        return $response->json();
    }
}
