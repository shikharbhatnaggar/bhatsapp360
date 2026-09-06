<?php

namespace App\Services;

use App\Models\WhatsappAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Thin wrapper over the WhatsApp Cloud API (Graph).
 *
 * Every method returns a normalised array:
 *   ['ok' => bool, 'status' => int, 'body' => array, 'request' => array]
 *
 * When config('whatsapp.sandbox') is true the calls are simulated so the whole
 * product can be demoed without a live WABA.
 */
class WhatsAppClient
{
    public function __construct(protected WhatsappAccount $account) {}

    public static function for(WhatsappAccount $account): self
    {
        return new self($account);
    }

    protected function http(): PendingRequest
    {
        return Http::withToken($this->account->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(config('whatsapp.timeout'))
            ->retry(2, 400, throw: false);
    }

    protected function base(): string
    {
        $version = $this->account->graph_version ?: config('whatsapp.graph_version');

        return rtrim(config('whatsapp.graph_url'), '/').'/'.$version;
    }

    protected function sandbox(): bool
    {
        return (bool) config('whatsapp.sandbox');
    }

    // ---------------------------------------------------------------- account

    /** GET /{phone_number_id} — used by "Test connection" on the settings screen. */
    public function verifyNumber(): array
    {
        if ($this->sandbox()) {
            return $this->fake(200, [
                'id' => $this->account->phone_number_id,
                'display_phone_number' => $this->account->display_phone_number ?: '+91 90000 00000',
                'verified_name' => $this->account->tenant?->name ?? 'Sandbox Business',
                'quality_rating' => 'GREEN',
                'messaging_limit_tier' => 'TIER_1K',
            ]);
        }

        $url = $this->base().'/'.$this->account->phone_number_id;
        $response = $this->http()->get($url, [
            'fields' => 'display_phone_number,verified_name,quality_rating,throughput,messaging_limit_tier',
        ]);

        return $this->normalise($response, ['method' => 'GET', 'url' => $url]);
    }

    // --------------------------------------------------------------- templates

    /** POST /{waba_id}/message_templates — submits a template for review. */
    public function createTemplate(array $payload): array
    {
        if ($this->sandbox()) {
            return $this->fake(200, [
                'id' => (string) random_int(100000000000000, 999999999999999),
                'status' => 'PENDING',
                'category' => $payload['category'] ?? 'MARKETING',
            ], $payload);
        }

        $url = $this->base().'/'.$this->account->waba_id.'/message_templates';
        $response = $this->http()->post($url, $payload);

        return $this->normalise($response, ['method' => 'POST', 'url' => $url, 'body' => $payload]);
    }

    /**
     * POST /{template_id} — an edit puts an approved template back into review.
     * Name and language cannot change, so they are stripped here.
     */
    public function updateTemplate(string $templateId, array $payload): array
    {
        // Name, language and allow_category_change are create-only fields.
        unset($payload['name'], $payload['language'], $payload['allow_category_change']);

        if ($this->sandbox()) {
            return $this->fake(200, ['success' => true, 'status' => 'PENDING'], $payload);
        }

        $url = $this->base().'/'.$templateId;
        $response = $this->http()->post($url, $payload);

        return $this->normalise($response, ['method' => 'POST', 'url' => $url, 'body' => $payload]);
    }

    /**
     * GET /{waba_id}/message_templates — pulls current review status.
     * Follows Meta's cursor pagination so accounts with many templates sync fully.
     */
    public function listTemplates(int $maxPages = 10): array
    {
        if ($this->sandbox()) {
            return $this->fake(200, ['data' => [], 'sandbox' => true]);
        }

        $url = $this->base().'/'.$this->account->waba_id.'/message_templates';
        $query = [
            'limit' => 200,
            'fields' => 'id,name,language,status,category,components,quality_score,rejected_reason',
        ];

        $rows = [];
        $page = 0;

        do {
            $response = $this->http()->get($url, $query);

            if (! $response->successful()) {
                return $this->normalise($response, ['method' => 'GET', 'url' => $url, 'query' => $query]);
            }

            $body = $response->json() ?? [];
            $rows = array_merge($rows, $body['data'] ?? []);

            // The `next` link already carries the cursor, so drop our own query.
            $url = $body['paging']['next'] ?? null;
            $query = [];
            $page++;
        } while ($url && $page < $maxPages);

        Log::channel('whatsapp')->info('graph.templates.listed', ['count' => count($rows), 'pages' => $page]);

        return ['ok' => true, 'status' => 200, 'body' => ['data' => $rows], 'error' => null, 'request' => ['method' => 'GET', 'pages' => $page]];
    }

    /** DELETE /{waba_id}/message_templates?name= */
    public function deleteTemplate(string $name): array
    {
        if ($this->sandbox()) {
            return $this->fake(200, ['success' => true]);
        }

        $url = $this->base().'/'.$this->account->waba_id.'/message_templates?name='.urlencode($name);
        $response = $this->http()->delete($url);

        return $this->normalise($response, ['method' => 'DELETE', 'url' => $url]);
    }

    // ---------------------------------------------------------------- messages

    /** POST /{phone_number_id}/messages */
    public function sendMessage(array $payload): array
    {
        if ($this->sandbox()) {
            return $this->fake(200, [
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => $payload['to'] ?? '', 'wa_id' => $payload['to'] ?? '']],
                'messages' => [['id' => 'wamid.SANDBOX'.Str::upper(Str::random(24))]],
            ], $payload);
        }

        $url = $this->base().'/'.$this->account->phone_number_id.'/messages';
        $response = $this->http()->post($url, $payload);

        return $this->normalise($response, ['method' => 'POST', 'url' => $url, 'body' => $payload]);
    }

    public function markRead(string $wamid): array
    {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $wamid,
        ]);
    }

    // ---------------------------------------------------------------- internal

    protected function normalise($response, array $request): array
    {
        $body = $response->json() ?? [];

        Log::channel('whatsapp')->info('graph.call', [
            'account' => $this->account->id,
            'request' => $request,
            'status' => $response->status(),
            'body' => $body,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
            'error' => $body['error'] ?? null,
            'retryable' => $this->isRetryable($response->status(), $body),
            'request' => $request,
        ];
    }

    /**
     * Rate limits and Meta-side faults are worth another attempt; a malformed
     * template or an unreachable number is not.
     */
    protected function isRetryable(int $status, array $body): bool
    {
        if ($status === 429 || $status >= 500) {
            return true;
        }

        // 130429 rate limit hit, 131056 pair rate limit, 133016 internal, 368 temporarily blocked.
        return in_array((int) ($body['error']['code'] ?? 0), [130429, 131056, 133016, 131048, 368], true);
    }

    protected function fake(int $status, array $body, array $request = []): array
    {
        Log::channel('whatsapp')->info('graph.sandbox', ['body' => $body, 'request' => $request]);

        return [
            'ok' => $status < 400,
            'status' => $status,
            'body' => $body,
            'error' => null,
            'retryable' => false,
            'request' => ['sandbox' => true, 'body' => $request],
        ];
    }

    public function requireAccount(): WhatsappAccount
    {
        if (! $this->account->exists) {
            throw new RuntimeException('No WhatsApp number is connected for this workspace.');
        }

        return $this->account;
    }
}
