<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageStatusEvent;
use App\Support\ActivityLogger;
use App\Support\TransientSendFailure;
use Illuminate\Support\Arr;

/**
 * Sends one message and records its receipt trail.
 */
class MessageDispatcher
{
    public function __construct(protected TemplateBuilder $builder) {}

    public function sendCampaignMessage(Message $message): Message
    {
        $campaign = $message->campaign;
        $template = $message->template;
        $customer = $message->customer;
        $account = $campaign?->whatsapp_account_id
            ? \App\Models\WhatsappAccount::withoutGlobalScope('tenant')->find($campaign->whatsapp_account_id)
            : null;

        if (! $account || ! $template || ! $customer) {
            return $this->fail($message, ['message' => 'Missing sender, template or recipient.']);
        }

        $payload = $this->builder->messagePayload($template, $customer);
        $result = WhatsAppClient::for($account)->sendMessage($payload);

        if (! $result['ok']) {
            // Rate limits and Meta-side faults: leave the message queued and let the job retry.
            if ($result['retryable'] ?? false) {
                throw new TransientSendFailure(Arr::get($result, 'error.message', 'WhatsApp is throttling or unavailable'));
            }

            return $this->fail($message, $result['error'] ?? ['message' => 'Send failed'], $payload);
        }

        $wamid = Arr::get($result, 'body.messages.0.id');

        $message->forceFill([
            'wamid' => $wamid,
            'status' => 'sent',
            'sent_at' => now(),
            'payload' => ['request' => $payload, 'response' => $result['body']],
        ])->save();

        $this->recordEvent($message, 'sent', 'api', $result['body']);

        $customer->forceFill(['last_outbound_at' => now()])->save();

        ActivityLogger::log(
            'message.sent',
            "Message sent to {$customer->name} ({$customer->phone})",
            $message,
            ['campaign_id' => $campaign?->id, 'wamid' => $wamid],
            $message->tenant_id,
        );

        return $message;
    }

    public function sendSessionText(Customer $customer, string $text, $account): Message
    {
        $message = Message::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'whatsapp_account_id' => $account->id,
            'direction' => 'outbound',
            'type' => 'text',
            'pricing_category' => 'SERVICE',
            'status' => 'queued',
            'body_preview' => $text,
        ]);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $customer->phone,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $text],
        ];

        $result = WhatsAppClient::for($account)->sendMessage($payload);

        if (! $result['ok']) {
            if ($result['retryable'] ?? false) {
                throw new TransientSendFailure(Arr::get($result, 'error.message', 'WhatsApp is throttling or unavailable'));
            }

            return $this->fail($message, $result['error'] ?? ['message' => 'Send failed'], $payload);
        }

        $message->forceFill([
            'wamid' => Arr::get($result, 'body.messages.0.id'),
            'status' => 'sent',
            'sent_at' => now(),
            'payload' => ['request' => $payload, 'response' => $result['body']],
        ])->save();

        $this->recordEvent($message, 'sent', 'api', $result['body']);
        $customer->forceFill(['last_outbound_at' => now()])->save();

        ActivityLogger::log('message.replied', "Replied to {$customer->name}", $message, [], $message->tenant_id);

        return $message;
    }

    protected function fail(Message $message, array $error, array $payload = []): Message
    {
        $message->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error' => $error,
            'payload' => $payload ? ['request' => $payload] : $message->payload,
        ])->save();

        $this->recordEvent($message, 'failed', 'api', $error);

        ActivityLogger::log(
            'message.failed',
            'Message to '.($message->customer?->phone ?? 'unknown').' failed: '.($error['message'] ?? 'unknown error'),
            $message,
            ['error' => $error],
            $message->tenant_id,
        );

        return $message;
    }

    public function recordEvent(Message $message, string $status, string $source, ?array $raw = null, ?\DateTimeInterface $at = null): void
    {
        MessageStatusEvent::create([
            'message_id' => $message->id,
            'status' => $status,
            'source' => $source,
            'raw' => $raw,
            'occurred_at' => $at ?? now(),
        ]);
    }
}
