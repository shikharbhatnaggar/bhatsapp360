<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Message;
use App\Support\ActivityLogger;
use App\Support\TransientSendFailure;
use Illuminate\Support\Facades\DB;

/**
 * Sends pending messages without a queue worker.
 *
 * Works on `messages.status = 'queued'` rather than the jobs table, so it is
 * driver-agnostic: usable from an HTTP request, an external cron ping, or a
 * button in the UI. Hosts like Wasmer Edge cannot run `queue:work`, and this
 * is the path for them.
 */
class CampaignRunner
{
    public function __construct(protected MessageDispatcher $dispatcher) {}

    /**
     * Send up to $limit queued messages, stopping early if $maxSeconds elapses
     * so the caller's request never times out.
     *
     * @return array{sent: int, failed: int, remaining: int, throttled: bool}
     */
    public function drain(int $limit = 25, int $maxSeconds = 20, ?int $campaignId = null): array
    {
        $startedAt = microtime(true);
        $sent = 0;
        $failed = 0;
        $throttled = false;

        $messages = Message::withoutGlobalScope('tenant')
            ->where('direction', 'outbound')
            ->where('status', 'queued')
            ->when($campaignId, fn ($q) => $q->where('campaign_id', $campaignId))
            ->with(['customer', 'template', 'campaign'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($messages as $message) {
            if (microtime(true) - $startedAt > $maxSeconds) {
                break;
            }

            try {
                $this->dispatcher->sendCampaignMessage($message);
                $message->status === 'failed' ? $failed++ : $sent++;
            } catch (TransientSendFailure $e) {
                // Rate limited or Meta is down. The message stays queued; stop
                // here so the next run picks it up rather than burning attempts.
                $throttled = true;
                break;
            }
        }

        $this->finaliseCampaigns();

        return [
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => $this->pendingCount($campaignId),
            'throttled' => $throttled,
        ];
    }

    public function pendingCount(?int $campaignId = null): int
    {
        return Message::withoutGlobalScope('tenant')
            ->where('direction', 'outbound')
            ->where('status', 'queued')
            ->when($campaignId, fn ($q) => $q->where('campaign_id', $campaignId))
            ->count();
    }

    /** Close out campaigns that have nothing left queued. */
    protected function finaliseCampaigns(): void
    {
        $campaigns = Campaign::withoutGlobalScope('tenant')
            ->whereIn('status', ['queued', 'sending'])
            ->whereDoesntHave('messages', fn ($q) => $q->where('status', 'queued'))
            ->get();

        foreach ($campaigns as $campaign) {
            $spend = (float) DB::table('messages')
                ->where('campaign_id', $campaign->id)
                ->whereIn('status', ['sent', 'delivered', 'read'])
                ->sum('price');

            $campaign->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'actual_cost' => $spend,
                'started_at' => $campaign->started_at ?? now(),
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