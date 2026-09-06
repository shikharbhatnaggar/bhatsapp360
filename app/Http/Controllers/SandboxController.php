<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Message;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Demo helpers, only available while WHATSAPP_SANDBOX=true. They replay the
 * webhook payloads Meta would send so delivery receipts and replies can be
 * exercised without a live number.
 */
class SandboxController extends Controller
{
    public function advanceCampaign(Campaign $campaign)
    {
        abort_unless(config('whatsapp.sandbox'), 404);

        $moved = 0;

        foreach ($campaign->messages()->whereIn('status', ['sent', 'delivered'])->get() as $message) {
            $next = match ($message->status) {
                'sent' => random_int(1, 10) === 1 ? 'failed' : 'delivered',
                'delivered' => random_int(1, 3) === 1 ? 'delivered' : 'read',
                default => null,
            };

            if (! $next || $next === $message->status) {
                continue;
            }

            $message->forceFill([
                'status' => $next,
                'delivered_at' => in_array($next, ['delivered', 'read']) ? ($message->delivered_at ?? now()) : $message->delivered_at,
                'read_at' => $next === 'read' ? now() : $message->read_at,
                'failed_at' => $next === 'failed' ? now() : null,
                'error' => $next === 'failed'
                    ? ['code' => 131026, 'title' => 'Message undeliverable', 'message' => 'Recipient phone number not on WhatsApp']
                    : null,
            ])->save();

            app(\App\Services\MessageDispatcher::class)->recordEvent($message, $next, 'webhook', ['simulated' => true]);
            ActivityLogger::log('message.'.$next, "Message to {$message->customer?->phone} marked {$next}", $message, ['simulated' => true]);
            $moved++;
        }

        return back()->with('status', $moved
            ? "{$moved} delivery receipt".($moved === 1 ? '' : 's').' received.'
            : 'No further receipts to apply.');
    }

    public function inboundReply(Request $request)
    {
        abort_unless(config('whatsapp.sandbox'), 404);

        $customer = Customer::query()
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('id', $id))
            ->inRandomOrder()->firstOrFail();

        $body = $request->input('body') ?: Str::of(collect([
            'Yes please, share the details.',
            'How much is the delivery charge?',
            'Stop sending me offers.',
            'Is this available in Hyderabad?',
            'Just ordered, thank you!',
        ])->random());

        $message = Message::create([
            'customer_id' => $customer->id,
            'whatsapp_account_id' => $request->user()->tenant->whatsappAccount?->id,
            'direction' => 'inbound',
            'wamid' => 'wamid.SIM'.Str::upper(Str::random(20)),
            'type' => 'text',
            'pricing_category' => 'SERVICE',
            'status' => 'received',
            'body_preview' => (string) $body,
            'payload' => ['simulated' => true, 'text' => ['body' => (string) $body]],
        ]);

        $customer->forceFill(['last_inbound_at' => now()])->save();
        ActivityLogger::log('message.received', "Reply from {$customer->name}: ".Str::limit($body, 60), $message);

        return back()->with('status', "Inbound message from {$customer->name} received.");
    }
}
