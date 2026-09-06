<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\MessageDispatcher;
use App\Support\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** Backoff in seconds — Cloud API rate limits usually clear within a minute. */
    public array $backoff = [10, 30, 60, 120];

    public function __construct(public int $messageId) {}

    public function handle(MessageDispatcher $dispatcher): void
    {
        $message = Message::withoutGlobalScope('tenant')
            ->with(['customer', 'template', 'campaign'])
            ->find($this->messageId);

        if (! $message || $message->status !== 'queued') {
            return;
        }

        $dispatcher->sendCampaignMessage($message);

        $campaign = $message->campaign;

        if ($campaign && $campaign->messages()->where('status', 'queued')->doesntExist()) {
            $spend = (float) $campaign->messages()->whereIn('status', ['sent', 'delivered', 'read'])->sum('price');

            $campaign->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'actual_cost' => $spend,
            ])->save();

            ActivityLogger::log(
                'campaign.completed',
                "Campaign “{$campaign->name}” finished sending",
                $campaign,
                $campaign->counts(),
                $campaign->tenant_id,
            );
        }
    }
}
