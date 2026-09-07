<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use App\Services\CampaignRunner;
use App\Services\TemplateSyncService;
use Illuminate\Http\Request;

/**
 * Replaces `queue:work` and `schedule:run` for hosts with no shell access.
 * Point an external pinger (cron-job.org, UptimeRobot, GitHub Actions) at
 * /cron/run/{token} once a minute.
 */
class CronController extends Controller
{
    public function run(Request $request, string $token, CampaignRunner $runner, TemplateSyncService $sync)
    {
        $expected = (string) config('whatsapp.cron_token');

        // No token configured means the endpoint stays closed.
        abort_if($expected === '' || ! hash_equals($expected, $token), 404);

        // Keep well inside a typical 30s request ceiling.
        $result = $runner->drain(
            limit: (int) $request->integer('limit', 25),
            maxSeconds: (int) $request->integer('seconds', 20),
        );

        $synced = 0;

        // Only reconcile templates when there is time left over.
        if (! $result['throttled'] && $result['remaining'] === 0) {
            foreach (WhatsappAccount::withoutGlobalScope('tenant')->where('is_active', true)->get() as $account) {
                $outcome = $sync->sync($account);
                $synced += $outcome['updated'] ?? 0;
            }
        }

        return response()->json([
            'ok' => true,
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'remaining' => $result['remaining'],
            'throttled' => $result['throttled'],
            'templates_updated' => $synced,
            'at' => now()->toIso8601String(),
        ]);
    }
}