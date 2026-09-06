<?php

namespace App\Services;

use App\Models\MessageTemplate;
use App\Models\TemplateVersion;
use App\Models\WhatsappAccount;
use App\Support\ActivityLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Owns the review lifecycle: every create or edit is submitted to WhatsApp,
 * recorded as a version, and later reconciled with the status Meta returns.
 */
class TemplateSyncService
{
    public function __construct(protected TemplateBuilder $builder) {}

    /** Submit a draft (or an edit) for WhatsApp review. */
    public function submit(MessageTemplate $template, string $action = 'created'): array
    {
        $account = $template->account ?? $template->tenant->whatsappAccount;

        if (! $account) {
            return ['ok' => false, 'message' => 'Connect a WhatsApp number before submitting templates.'];
        }

        $client = WhatsAppClient::for($account);
        $payload = $this->builder->createPayload($template);

        $result = $template->whatsapp_template_id
            ? $client->updateTemplate($template->whatsapp_template_id, $payload)
            : $client->createTemplate($payload);

        $version = TemplateVersion::create([
            'message_template_id' => $template->id,
            'version' => $template->version,
            'action' => $action,
            'status' => $result['ok'] ? 'PENDING' : $template->status,
            'components' => $template->components,
            'request_payload' => $payload,
            'response_payload' => $result['body'],
            'submitted_by' => Auth::id(),
            'review_note' => $result['ok'] ? null : Arr::get($result, 'error.message', 'Submission failed'),
        ]);

        if ($result['ok']) {
            $template->update([
                'status' => 'PENDING',
                'whatsapp_template_id' => Arr::get($result, 'body.id', $template->whatsapp_template_id),
                'whatsapp_account_id' => $account->id,
                'submitted_at' => now(),
                'rejected_reason' => null,
            ]);

            ActivityLogger::log(
                'template.submitted',
                "Template “{$template->name}” v{$template->version} submitted to WhatsApp for review",
                $template,
                ['version_id' => $version->id, 'action' => $action, 'category' => $template->category],
            );

            return ['ok' => true, 'message' => 'Submitted for review. WhatsApp usually responds within a few minutes.'];
        }

        ActivityLogger::log(
            'template.submission_failed',
            "WhatsApp rejected the submission of “{$template->name}”",
            $template,
            ['error' => $result['error']],
        );

        return ['ok' => false, 'message' => Arr::get($result, 'error.message', 'WhatsApp rejected the submission.')];
    }

    /** Pull live statuses for one account and reconcile local rows. */
    public function sync(WhatsappAccount $account): int
    {
        $client = WhatsAppClient::for($account);
        $response = $client->listTemplates();
        $updated = 0;

        $remote = collect(Arr::get($response, 'body.data', []))
            ->keyBy(fn ($row) => $row['name'].'|'.$row['language']);

        $templates = MessageTemplate::withoutGlobalScope('tenant')
            ->where('whatsapp_account_id', $account->id)
            ->whereIn('status', ['PENDING', 'APPROVED', 'PAUSED', 'REJECTED'])
            ->get();

        foreach ($templates as $template) {
            $row = $remote->get($template->name.'|'.$template->language);

            // Sandbox mode has no remote list, so approve anything that has
            // been pending longer than the configured delay.
            if (! $row && config('whatsapp.sandbox')) {
                if ($template->status === 'PENDING'
                    && $template->submitted_at
                    && $template->submitted_at->addSeconds(config('whatsapp.sandbox_approval_delay'))->isPast()) {
                    $row = ['status' => 'APPROVED', 'quality_score' => ['score' => 'UNKNOWN']];
                } else {
                    continue;
                }
            }

            if (! $row) {
                continue;
            }

            $status = strtoupper($row['status'] ?? $template->status);
            $changed = $status !== $template->status;

            $template->update([
                'status' => $status,
                'whatsapp_template_id' => $row['id'] ?? $template->whatsapp_template_id,
                'quality_score' => Arr::get($row, 'quality_score.score'),
                'rejected_reason' => $row['rejected_reason'] ?? null,
                'approved_at' => $status === 'APPROVED' ? ($template->approved_at ?? now()) : null,
                'last_synced_at' => now(),
            ]);

            if ($changed) {
                $updated++;

                $template->versions()->latest('version')->first()?->update([
                    'status' => $status,
                    'reviewed_at' => now(),
                    'review_note' => $row['rejected_reason'] ?? null,
                ]);

                ActivityLogger::log(
                    'template.status_changed',
                    "Template “{$template->name}” is now {$status}",
                    $template,
                    ['status' => $status, 'reason' => $row['rejected_reason'] ?? null],
                    $template->tenant_id,
                );
            }
        }

        return $updated;
    }
}
