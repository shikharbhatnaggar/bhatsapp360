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

    /**
     * Pull live statuses for one account and reconcile local rows.
     *
     * @return array{ok: bool, updated: int, fetched: int, imported: int, message: string}
     */
    public function sync(WhatsappAccount $account): array
    {
        $client = WhatsAppClient::for($account);
        $response = $client->listTemplates();

        // A failed lookup must not look like "nothing changed".
        if (! ($response['ok'] ?? false)) {
            ActivityLogger::log(
                'template.sync_failed',
                'Could not read template statuses from WhatsApp',
                null,
                $response['error'] ?? [],
                $account->tenant_id,
            );

            return [
                'ok' => false,
                'updated' => 0,
                'fetched' => 0,
                'imported' => 0,
                'message' => 'WhatsApp would not return your templates: '.WhatsAppClient::describeError($response),
            ];
        }

        $remote = collect(Arr::get($response, 'body.data', []))
            ->keyBy(fn ($row) => strtolower($row['name'] ?? '').'|'.($row['language'] ?? ''));

        // Match on the tenant, not the account column: templates submitted before
        // a number was connected would otherwise never reconcile.
        $templates = MessageTemplate::withoutGlobalScope('tenant')
            ->where('tenant_id', $account->tenant_id)
            ->where(fn ($q) => $q->where('whatsapp_account_id', $account->id)->orWhereNull('whatsapp_account_id'))
            ->get();

        $updated = 0;

        foreach ($templates as $template) {
            $key = strtolower($template->name).'|'.$template->language;
            $row = $remote->pull($key);

            // Sandbox has no remote list, so approve anything pending long enough.
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
            // Meta re-classifies templates during review; the category drives pricing,
            // so take theirs rather than trusting what we submitted.
            $category = strtoupper($row['category'] ?? $template->category);
            $reason = $row['rejected_reason'] ?? null;
            $reason = in_array($reason, [null, '', 'NONE'], true) ? null : $reason;

            $changed = $status !== $template->status || $category !== $template->category;

            $template->update([
                'status' => $status,
                'category' => in_array($category, array_keys(config('whatsapp.categories')), true) ? $category : $template->category,
                'whatsapp_account_id' => $template->whatsapp_account_id ?? $account->id,
                'whatsapp_template_id' => $row['id'] ?? $template->whatsapp_template_id,
                'quality_score' => Arr::get($row, 'quality_score.score'),
                'rejected_reason' => $reason,
                'approved_at' => $status === 'APPROVED' ? ($template->approved_at ?? now()) : null,
                'last_synced_at' => now(),
            ]);

            if (! $changed) {
                continue;
            }

            $updated++;

            $template->versions()->latest('version')->first()?->update([
                'status' => $status,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ]);

            ActivityLogger::log(
                'template.status_changed',
                "Template “{$template->name}” is now {$status}",
                $template,
                ['status' => $status, 'category' => $category, 'reason' => $reason],
                $template->tenant_id,
            );
        }

        // Anything left in $remote exists on WhatsApp but not here — usually built
        // in the Meta UI. Adopt it so the console reflects the account.
        $imported = $this->adopt($account, $remote);

        $fetched = count(Arr::get($response, 'body.data', []));

        return [
            'ok' => true,
            'updated' => $updated,
            'fetched' => $fetched,
            'imported' => $imported,
            'message' => $this->summarise($fetched, $updated, $imported),
        ];
    }

    /** Create local rows for templates that exist on WhatsApp but not here. */
    protected function adopt(WhatsappAccount $account, $remote): int
    {
        $imported = 0;

        foreach ($remote as $row) {
            $category = strtoupper($row['category'] ?? 'MARKETING');

            if (! in_array($category, array_keys(config('whatsapp.categories')), true)) {
                continue;
            }

            $template = MessageTemplate::withoutGlobalScope('tenant')->create([
                'tenant_id' => $account->tenant_id,
                'whatsapp_account_id' => $account->id,
                'name' => $row['name'],
                'language' => $row['language'] ?? 'en_US',
                'category' => $category,
                'status' => strtoupper($row['status'] ?? 'APPROVED'),
                'whatsapp_template_id' => $row['id'] ?? null,
                'components' => $row['components'] ?? [],
                'quality_score' => Arr::get($row, 'quality_score.score'),
                'approved_at' => strtoupper($row['status'] ?? '') === 'APPROVED' ? now() : null,
                'last_synced_at' => now(),
                'version' => 1,
            ]);

            ActivityLogger::log(
                'template.imported',
                "Template “{$template->name}” imported from WhatsApp",
                $template,
                ['status' => $template->status],
                $account->tenant_id,
            );

            $imported++;
        }

        return $imported;
    }

    protected function summarise(int $fetched, int $updated, int $imported): string
    {
        if ($fetched === 0) {
            return 'WhatsApp returned no templates for this business account.';
        }

        $parts = [];

        if ($updated) {
            $parts[] = $updated.' status'.($updated === 1 ? '' : 'es').' updated';
        }

        if ($imported) {
            $parts[] = $imported.' template'.($imported === 1 ? '' : 's').' imported';
        }

        return $parts
            ? implode(' and ', $parts).'.'
            : 'Checked '.$fetched.' template'.($fetched === 1 ? '' : 's').' on WhatsApp — nothing has changed.';
    }
}
