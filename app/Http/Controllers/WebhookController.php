<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageStatusEvent;
use App\Models\Tenant;
use App\Models\WhatsappAccount;
use App\Services\MessageDispatcher;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Receives Cloud API callbacks: delivery receipts and inbound customer replies.
 * One URL per tenant keeps routing simple: /webhooks/whatsapp/{tenant}
 */
class WebhookController extends Controller
{
    public function __construct(protected MessageDispatcher $dispatcher) {}

    /** Meta's subscription handshake. */
    public function verify(Request $request, Tenant $tenant)
    {
        $account = $tenant->whatsappAccounts()->withoutGlobalScope('tenant')->first();

        if ($request->query('hub_mode') === 'subscribe'
            && $account
            && hash_equals($account->webhook_verify_token, (string) $request->query('hub_verify_token'))) {
            return response($request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Verification failed', 403);
    }

    public function handle(Request $request, Tenant $tenant)
    {
        $account = WhatsappAccount::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->first();

        if ($account && ! $this->signatureValid($request, $account)) {
            Log::channel('whatsapp')->warning('webhook.bad_signature', ['tenant' => $tenant->id]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        foreach ($request->input('entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $value = $change['value'] ?? [];
                $field = $change['field'] ?? 'messages';

                // Template review decisions arrive as their own field, with a flat value object.
                if (in_array($field, ['message_template_status_update', 'message_template_quality_update'], true)) {
                    $this->applyTemplateStatus($tenant, $value);

                    continue;
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->applyStatus($tenant, $status);
                }

                foreach ($value['messages'] ?? [] as $inbound) {
                    $this->storeInbound($tenant, $account, $inbound, $value);
                }
            }
        }

        // Meta retries anything that is not a fast 200.
        return response()->json(['received' => true]);
    }

    protected function signatureValid(Request $request, WhatsappAccount $account): bool
    {
        $secret = $account->app_secret;
        $header = $request->header('X-Hub-Signature-256');

        // No secret configured (or sandbox): accept and rely on the obscure URL.
        if (blank($secret) || blank($header)) {
            return true;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }

    /** sent -> delivered -> read -> (failed), never backwards. */
    protected function applyStatus(Tenant $tenant, array $status): void
    {
        $message = Message::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('wamid', $status['id'] ?? '')
            ->first();

        if (! $message) {
            return;
        }

        $newStatus = strtolower($status['status'] ?? '');
        // Carbon 3 builds timestamps in UTC whatever app.timezone says, so convert
        // explicitly — otherwise receipts land hours adrift of our own events.
        $at = isset($status['timestamp'])
            ? Carbon::createFromTimestamp((int) $status['timestamp'], config('app.timezone'))
            : now();

        MessageStatusEvent::create([
            'message_id' => $message->id,
            'status' => $newStatus,
            'source' => 'webhook',
            'raw' => $status,
            'occurred_at' => $at,
        ]);

        $fields = ['conversation_id' => Arr::get($status, 'conversation.id', $message->conversation_id)];

        // Meta reports how it billed this message on the status callback.
        if (Arr::has($status, 'pricing')) {
            $fields['pricing_category'] = strtoupper(Arr::get($status, 'pricing.category', $message->pricing_category));

            // Utility templates delivered inside an open customer service window
            // are free. If we already charged for it, hand the money back.
            if (Arr::get($status, 'pricing.billable') === false) {
                $fields['price'] = 0;
                $fields['meta_cost'] = 0;

                if ($message->charged_at && config('wallet.refund_non_billable')) {
                    app(\App\Services\WalletService::class)->refundMessage($message);
                    $fields['charged_at'] = null;
                }
            }
        }

        if ($newStatus === 'sent') {
            $fields['sent_at'] = $message->sent_at ?? $at;
        } elseif ($newStatus === 'delivered') {
            $fields['delivered_at'] = $at;
        } elseif ($newStatus === 'read') {
            $fields['read_at'] = $at;
        } elseif ($newStatus === 'failed') {
            $fields['failed_at'] = $at;
            $fields['error'] = Arr::get($status, 'errors.0');
        }

        if (Message::rankFor($newStatus) > $message->statusRank()) {
            $fields['status'] = $newStatus;
        }

        $message->forceFill($fields)->save();

        // We charge on send; if WhatsApp could not deliver it, give the money back.
        if ($newStatus === 'failed' && $message->charged_at) {
            app(\App\Services\WalletService::class)->refundMessage($message);
        }

        // WhatsApp bills on delivery, so the campaign total firms up as receipts arrive.
        if ($message->campaign_id) {
            \App\Models\Campaign::withoutGlobalScope('tenant')
                ->whereKey($message->campaign_id)
                ->update(['actual_cost' => \App\Models\Message::withoutGlobalScope('tenant')
                    ->where('campaign_id', $message->campaign_id)
                    ->whereIn('status', ['delivered', 'read'])
                    ->sum('price')]);
        }

        ActivityLogger::log(
            'message.'.$newStatus,
            'Message to '.($message->customer?->phone ?? 'recipient').' marked '.$newStatus,
            $message,
            ['wamid' => $message->wamid],
            $tenant->id,
        );
    }

    protected function storeInbound(Tenant $tenant, ?WhatsappAccount $account, array $inbound, array $value): void
    {
        $from = $inbound['from'] ?? null;
        if (! $from) {
            return;
        }

        $profileName = Arr::get($value, 'contacts.0.profile.name', 'WhatsApp user');

        $customer = Customer::withoutGlobalScope('tenant')->firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $from],
            ['name' => $profileName, 'type' => 'lead', 'country_code' => str_starts_with($from, '91') ? 'IN' : 'IN'],
        );

        if ($customer->wasRecentlyCreated) {
            ActivityLogger::log(
                'customer.created',
                "{$customer->name} added from an inbound WhatsApp message",
                $customer,
                ['phone' => $from],
                $tenant->id,
            );
        }

        $type = $inbound['type'] ?? 'text';
        $body = match ($type) {
            'text' => Arr::get($inbound, 'text.body', ''),
            'button' => Arr::get($inbound, 'button.text', ''),
            'interactive' => Arr::get($inbound, 'interactive.button_reply.title')
                ?? Arr::get($inbound, 'interactive.list_reply.title', ''),
            'image', 'video', 'document', 'audio' => '['.$type.'] '.Arr::get($inbound, $type.'.caption', 'media received'),
            default => '['.$type.']',
        };

        $message = Message::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'whatsapp_account_id' => $account?->id,
            'direction' => 'inbound',
            'wamid' => $inbound['id'] ?? null,
            'type' => $type,
            'pricing_category' => 'SERVICE',
            'status' => 'received',
            'body_preview' => $body,
            'payload' => $inbound,
            'price' => 0,
            'currency' => $tenant->currency,
        ]);

        $this->dispatcher->recordEvent($message, 'received', 'webhook', $inbound);

        $customer->forceFill([
            'last_inbound_at' => isset($inbound['timestamp'])
                ? Carbon::createFromTimestamp((int) $inbound['timestamp'], config('app.timezone'))
                : now(),
        ])->save();

        ActivityLogger::log(
            'message.received',
            "Reply from {$customer->name}: ".\Illuminate\Support\Str::limit($body, 60),
            $message,
            ['type' => $type],
            $tenant->id,
        );
    }

    protected function applyTemplateStatus(Tenant $tenant, array $update): void
    {
        $query = \App\Models\MessageTemplate::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id);

        // Meta identifies the template by id on status updates and by name+language
        // on quality updates, so match on whichever is present.
        if ($id = ($update['message_template_id'] ?? null)) {
            $query->where('whatsapp_template_id', (string) $id);
        } elseif ($name = ($update['message_template_name'] ?? null)) {
            $query->where('name', $name)
                ->when($update['message_template_language'] ?? null, fn ($q, $lang) => $q->where('language', $lang));
        } else {
            return;
        }

        $template = $query->first();

        if (! $template) {
            return;
        }

        // Quality updates carry no review status; only the send-eligibility state.
        $status = strtoupper($update['event'] ?? $update['new_quality_score'] ?? '');
        $isReviewDecision = in_array($status, ['APPROVED', 'REJECTED', 'PAUSED', 'DISABLED', 'PENDING', 'PENDING_DELETION'], true);

        if (! $isReviewDecision) {
            $template->forceFill(['quality_score' => $status ?: $template->quality_score, 'last_synced_at' => now()])->save();

            return;
        }

        $reason = $update['reason'] ?? null;

        $template->forceFill([
            'status' => $status,
            'rejected_reason' => in_array($reason, [null, 'NONE'], true) ? null : $reason,
            'approved_at' => $status === 'APPROVED' ? ($template->approved_at ?? now()) : null,
            'last_synced_at' => now(),
        ])->save();

        $template->versions()->latest('version')->first()?->update([
            'status' => $status,
            'reviewed_at' => now(),
            'review_note' => in_array($reason, [null, 'NONE'], true) ? null : $reason,
        ]);

        ActivityLogger::log(
            'template.status_changed',
            "WhatsApp marked “{$template->name}” as {$status}",
            $template,
            $update,
            $tenant->id,
        );
    }
}
