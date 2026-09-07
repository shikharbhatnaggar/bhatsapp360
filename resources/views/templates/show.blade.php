@extends('layouts.app')
@section('title', $template->name.' — Bhatsapp')
@section('heading', $template->name)
@section('subheading', ucfirst(strtolower($template->category)).' · '.$template->language.' · version '.$template->version)

@section('actions')
    @if ($template->isSendable())
        <a href="{{ route('campaigns.create', ['template_id' => $template->id]) }}"
           class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">Send this</a>
    @endif
    <a href="{{ route('templates.edit', $template) }}" class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Edit</a>
    @if (in_array($template->status, ['DRAFT', 'REJECTED']))
        <form method="POST" action="{{ route('templates.submit', $template) }}">
            @csrf
            <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Submit for review</button>
        </form>
    @endif
@endsection

@section('content')
<div class="grid gap-4 lg:grid-cols-[1fr_360px]">
    <div class="space-y-4">
        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-md border px-2.5 py-1 text-sm {{ $template->statusColor() }}">
                    {{ ucfirst(strtolower($template->status)) }}
                </span>
                @if ($template->submitted_at)
                    <span class="text-sm text-ink-500">Submitted {{ $template->submitted_at->diffForHumans() }}</span>
                @endif
                @if ($template->approved_at)
                    <span class="text-sm text-ink-500">Approved {{ $template->approved_at->diffForHumans() }}</span>
                @endif
                @if ($template->whatsapp_template_id)
                    <span class="num text-sm text-ink-500">Meta ID {{ $template->whatsapp_template_id }}</span>
                @endif
                @if ($template->last_synced_at)
                    <span class="text-sm text-ink-500">Checked {{ $template->last_synced_at->diffForHumans() }}</span>
                @endif
            </div>
            @if ($template->status === 'APPROVED')
                <p class="mt-4 text-sm text-ink-500">
                    Quality rating:
                    <span class="text-ink-900">
                        {{ $template->quality_score && $template->quality_score !== 'UNKNOWN'
                            ? ucfirst(strtolower($template->quality_score))
                            : 'pending' }}
                    </span>
                    — Meta scores this once enough messages have been delivered. It does not affect whether you can send.
                </p>
            @endif
            @if ($template->status === 'PENDING')
                <p class="mt-4 rounded-lg bg-signal-50 px-3.5 py-2.5 text-sm text-signal-700">
                    Waiting on WhatsApp. Use “Check review status” on the templates list to pull the decision.
                </p>
            @endif
            @if ($template->rejected_reason)
                <p class="mt-4 rounded-lg bg-alert-50 px-3.5 py-2.5 text-sm text-alert-700">
                    WhatsApp rejected this: {{ $template->rejected_reason }}
                </p>
            @endif
        </div>

        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Review history</h2>
            <p class="mt-1 text-sm text-ink-500">Every edit is submitted to WhatsApp as a new approval event.</p>
            <ol class="mt-4 space-y-3">
                @forelse ($template->versions as $version)
                    <li class="flex gap-3 border-l-2 border-ink-100 pl-4">
                        <div class="min-w-0">
                            <p class="text-sm">
                                Version {{ $version->version }} — {{ $version->action }}
                                <span class="text-ink-500">· {{ strtolower($version->status) }}</span>
                            </p>
                            <p class="text-xs text-ink-500">
                                {{ $version->created_at->format('d M Y, g:i A') }}
                                @if ($version->submitter) · {{ $version->submitter->name }} @endif
                            </p>
                            @if ($version->review_note)
                                <p class="mt-1 text-xs text-alert-600">{{ $version->review_note }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-ink-500">Not submitted yet.</li>
                @endforelse
            </ol>
        </div>

        <details class="rounded-xl border border-ink-200 bg-white p-5">
            <summary class="cursor-pointer text-base">Component JSON sent to WhatsApp</summary>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-ink-900 p-4 text-xs leading-relaxed text-ink-100">{{ json_encode($template->components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>

    <aside>
        <div class="sticky top-6">
            <h2 class="mb-2 text-sm text-ink-500">Preview</h2>
            @include('partials.phone-preview', ['components' => $template->components])
        </div>
    </aside>
</div>
@endsection
