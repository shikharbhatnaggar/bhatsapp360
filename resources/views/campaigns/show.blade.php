@extends('layouts.app')
@section('title', $campaign->name.' — '.config('app.name'))
@section('heading', $campaign->name)
@section('subheading', 'Template '.($campaign->template?->name ?? '—').' · started '.($campaign->started_at?->format('d M Y, g:i A') ?? 'not yet'))

@section('actions')
    @if ($counts['queued'] > 0)
        <form method="POST" action="{{ route('campaigns.run', $campaign) }}">
            @csrf
            <button class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">
                Send {{ $counts['queued'] }} pending
            </button>
        </form>
    @endif
    @if (config('whatsapp.sandbox') && in_array($campaign->status, ['sending', 'completed']))
        <form method="POST" action="{{ route('sandbox.advance', $campaign) }}">
            @csrf
            <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Simulate delivery receipts</button>
        </form>
    @endif
    <a href="{{ route('campaigns.create') }}" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">New send</a>
@endsection

@section('content')
@php
    $cards = [
        ['label' => 'Queued', 'value' => $counts['queued'], 'tone' => 'text-ink-500'],
        ['label' => 'Sent', 'value' => $counts['sent'], 'tone' => 'text-ink-900'],
        ['label' => 'Delivered', 'value' => $counts['delivered'], 'tone' => 'text-jade-700'],
        ['label' => 'Read', 'value' => $counts['read'], 'tone' => 'text-jade-700'],
        ['label' => 'Failed', 'value' => $counts['failed'], 'tone' => 'text-alert-600'],
    ];
@endphp

<section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
    @foreach ($cards as $card)
        <div class="rounded-xl border border-ink-200 bg-white p-4">
            <p class="text-sm text-ink-500">{{ $card['label'] }}</p>
            <p class="num mt-1.5 text-2xl tracking-tight {{ $card['tone'] }}">{{ number_format($card['value']) }}</p>
        </div>
    @endforeach
    <div class="rounded-xl border border-ink-200 bg-white p-4">
        <p class="text-sm text-ink-500">Billed</p>
        <p class="num mt-1.5 text-2xl tracking-tight">@money((float) ($campaign->actual_cost ?: $campaign->estimated_cost), $campaign->currency)</p>
        <p class="mt-1 text-xs text-ink-500">est. @money((float) $campaign->estimated_cost, $campaign->currency)</p>
    </div>
</section>

@if ($counts['queued'] > 0)
    <p class="mt-4 rounded-lg border border-signal-200 bg-signal-50 px-4 py-3 text-sm text-signal-700">
        {{ $counts['queued'] }} {{ Str::plural('message', $counts['queued']) }} still waiting to go out.
        They send when a queue worker picks them up, when your cron endpoint next runs, or when you press
        <span class="text-signal-700">Send pending</span> above.
    </p>
@endif

<div class="mt-4 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('campaigns.show', $campaign) }}"
       class="rounded-lg border px-3 py-1.5 {{ request('status') ? 'border-ink-200 bg-white' : 'border-jade-600 bg-jade-50 text-jade-700' }}">All</a>
    @foreach (['queued', 'sent', 'delivered', 'read', 'failed'] as $status)
        <a href="{{ route('campaigns.show', ['campaign' => $campaign, 'status' => $status]) }}"
           class="rounded-lg border px-3 py-1.5 {{ request('status') === $status ? 'border-jade-600 bg-jade-50 text-jade-700' : 'border-ink-200 bg-white' }}">
            {{ ucfirst($status) }}
        </a>
    @endforeach
</div>

<div class="mt-3 overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Recipient</th>
                <th class="px-3 py-3 font-normal">Status</th>
                <th class="px-3 py-3 font-normal">Receipt trail</th>
                <th class="px-3 py-3 font-normal">Message ID</th>
                <th class="px-5 py-3 text-right font-normal">Price</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        @forelse ($messages as $message)
            <tr class="align-top hover:bg-ink-50">
                <td class="px-5 py-3">
                    {{ $message->customer?->name ?? 'Unknown' }}
                    <p class="num text-xs text-ink-500">+{{ $message->customer?->phone }}</p>
                    <p class="mt-1 max-w-md text-xs text-ink-500">{{ Str::limit($message->body_preview, 90) }}</p>
                </td>
                <td class="px-3 py-3">
                    @php
                        $tone = match ($message->status) {
                            'delivered', 'read' => 'text-jade-700 bg-jade-50 border-jade-200',
                            'failed' => 'text-alert-700 bg-alert-50 border-alert-200',
                            'queued' => 'text-ink-500 bg-ink-50 border-ink-200',
                            default => 'text-signal-700 bg-signal-50 border-signal-200',
                        };
                    @endphp
                    <span class="inline-flex rounded-md border px-2 py-0.5 text-xs {{ $tone }}">{{ ucfirst($message->status) }}</span>
                    @if ($message->error)
                        <p class="mt-1 text-xs text-alert-600">{{ data_get($message->error, 'message', data_get($message->error, 'title')) }}</p>
                    @endif
                </td>
                <td class="px-3 py-3 text-xs text-ink-500">
                    @forelse ($message->statusEvents as $event)
                        <span class="mr-2 whitespace-nowrap">{{ $event->status }} {{ $event->occurred_at->format('H:i:s') }}</span>
                    @empty
                        <span class="text-ink-300">no receipts yet</span>
                    @endforelse
                </td>
                <td class="px-3 py-3 text-xs text-ink-500">{{ Str::limit($message->wamid, 22) }}</td>
                <td class="num px-5 py-3 text-right">@money((float) $message->price, $message->currency)</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-ink-500">No messages match this filter.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $messages->links() }}</div>
@endsection
