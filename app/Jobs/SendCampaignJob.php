<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Support\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScope('tenant')->find($this->campaignId);

        if (! $campaign || $campaign->status === 'completed') {
            return;
        }

        $campaign->forceFill(['status' => 'sending', 'started_at' => now()])->save();

        $campaign->messages()->where('status', 'queued')->pluck('id')
            ->each(fn ($id) => SendMessageJob::dispatch($id));

        ActivityLogger::log(
            'campaign.started',
            "Campaign “{$campaign->name}” started sending to {$campaign->recipients_count} recipients",
            $campaign,
            ['estimated_cost' => (float) $campaign->estimated_cost],
            $campaign->tenant_id,
        );
    }
}
