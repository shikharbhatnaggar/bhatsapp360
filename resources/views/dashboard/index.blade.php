@extends('layouts.app')
@section('title', 'Overview — '.config('app.name'))
@section('heading', 'Overview')
@section('subheading', $from->format('d M Y').' to '.$to->format('d M Y'))

@section('actions')
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="from" class="block text-xs text-ink-500">From</label>
            <input id="from" type="date" name="from" value="{{ $from->toDateString() }}"
                   class="mt-1 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <div>
            <label for="to" class="block text-xs text-ink-500">To</label>
            <input id="to" type="date" name="to" value="{{ $to->toDateString() }}"
                   class="mt-1 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Apply</button>
        <a href="{{ route('campaigns.create') }}" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">New send</a>
    </form>
@endsection

@section('content')
@php
    $cards = [
        ['label' => 'Sent', 'value' => number_format($stats['sent']), 'note' => $stats['queued'].' still queued'],
        ['label' => 'Delivered', 'value' => number_format($stats['delivered']), 'note' => $stats['delivery_rate'].'% of sent'],
        ['label' => 'Read', 'value' => number_format($stats['read']), 'note' => $stats['read_rate'].'% of delivered'],
        ['label' => 'Failed', 'value' => number_format($stats['failed']), 'note' => 'needs a look'],
        ['label' => 'Replies', 'value' => number_format($stats['replies']), 'note' => 'inbound messages'],
        ['label' => 'Spend', 'value' => app(\App\Services\PricingService::class)->format($stats['spend'], $tenant->currency), 'note' => 'billed by category'],
    ];
@endphp

<section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
    @foreach ($cards as $card)
        <div class="rounded-xl border border-ink-200 bg-white p-4">
            <p class="text-sm text-ink-500">{{ $card['label'] }}</p>
            <p class="num mt-1.5 text-2xl tracking-tight">{{ $card['value'] }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ $card['note'] }}</p>
        </div>
    @endforeach
</section>

<section class="mt-4 grid gap-4 lg:grid-cols-3">
    <div class="rounded-xl border border-ink-200 bg-white p-5 lg:col-span-2">
        <div class="flex items-baseline justify-between">
            <h2 class="text-base">Messages per day</h2>
            <p class="text-xs text-ink-500">Outbound by outcome, plus replies received</p>
        </div>
        <div class="mt-4 h-64"><canvas id="volumeChart"></canvas></div>
    </div>

    <div class="rounded-xl border border-ink-200 bg-white p-5">
        <h2 class="text-base">Spend by category</h2>
        @if ($byCategory->isEmpty())
            <p class="mt-4 text-sm text-ink-500">Nothing sent in this window yet. Your first send will show up here.</p>
        @else
            <table class="mt-4 w-full text-sm">
                <tbody class="divide-y divide-ink-100">
                @foreach ($byCategory as $row)
                    <tr>
                        <td class="py-2.5">{{ ucfirst(strtolower($row->pricing_category)) }}</td>
                        <td class="num py-2.5 text-right text-ink-500">{{ number_format($row->total) }}</td>
                        <td class="num py-2.5 text-right">@money((float) $row->spend, $tenant->currency)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <div class="mt-5 border-t border-ink-100 pt-4 text-sm">
            <h3 class="text-ink-700">Workspace</h3>
            <dl class="mt-2 space-y-1.5 text-ink-500">
                <div class="flex justify-between"><dt>Contacts</dt><dd class="num text-ink-900">{{ number_format($counts['customers']) }} ({{ $counts['leads'] }} leads)</dd></div>
                <div class="flex justify-between"><dt>Templates approved</dt><dd class="num text-ink-900">{{ $counts['approved'] }} of {{ $counts['templates'] }}</dd></div>
                <div class="flex justify-between"><dt>Awaiting review</dt><dd class="num text-ink-900">{{ $counts['pending'] }}</dd></div>
            </dl>
        </div>
    </div>
</section>

<section class="mt-4 grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-ink-200 bg-white">
        <h2 class="border-b border-ink-100 px-5 py-3.5 text-base">Recent sends</h2>
        @if ($topCampaigns->isEmpty())
            <p class="px-5 py-6 text-sm text-ink-500">No sends in this window. <a href="{{ route('campaigns.create') }}" class="text-jade-700 underline underline-offset-2">Start one</a>.</p>
        @else
            <table class="w-full text-sm">
                <tbody class="divide-y divide-ink-100">
                @foreach ($topCampaigns as $campaign)
                    <tr class="hover:bg-ink-50">
                        <td class="px-5 py-3">
                            <a href="{{ route('campaigns.show', $campaign) }}" class="hover:underline underline-offset-2">{{ $campaign->name }}</a>
                            <p class="text-xs text-ink-500">{{ $campaign->created_at->format('d M, g:i A') }}</p>
                        </td>
                        <td class="num px-3 py-3 text-right text-ink-500">{{ $campaign->recipients_count }} sent</td>
                        <td class="num px-3 py-3 text-right text-jade-700">{{ $campaign->delivered_count }} delivered</td>
                        <td class="num px-5 py-3 text-right {{ $campaign->failed_count ? 'text-alert-600' : 'text-ink-300' }}">{{ $campaign->failed_count }} failed</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="rounded-xl border border-ink-200 bg-white">
        <div class="flex items-center justify-between border-b border-ink-100 px-5 py-3.5">
            <h2 class="text-base">Latest activity</h2>
            <a href="{{ route('logs.index') }}" class="text-sm text-jade-700 underline underline-offset-2">Full log</a>
        </div>
        <ul class="divide-y divide-ink-100">
            @forelse ($recent as $log)
                <li class="flex items-start gap-3 px-5 py-3">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ str_contains($log->event, 'failed') ? 'bg-alert-600' : 'bg-jade-400' }}"></span>
                    <div class="min-w-0">
                        <p class="text-sm">{{ $log->description }}</p>
                        <p class="text-xs text-ink-500">{{ $log->created_at->diffForHumans() }}@if ($log->user) · {{ $log->user->name }}@endif</p>
                    </div>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-ink-500">Activity will appear here as your team works.</li>
            @endforelse
        </ul>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const labels = @json($daily->pluck('day'));
    const inbound = @json($inboundDaily);

    new Chart(document.getElementById('volumeChart'), {
        type: 'bar',
        data: {
            labels: labels.map(d => new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })),
            datasets: [
                { label: 'Delivered', data: @json($daily->pluck('delivered')), backgroundColor: '#17755E', stack: 'out', borderRadius: 3 },
                { label: 'Failed', data: @json($daily->pluck('failed')), backgroundColor: '#B23A2F', stack: 'out', borderRadius: 3 },
                { label: 'Replies', data: labels.map(d => inbound[d] ?? 0), type: 'line', borderColor: '#B57614', backgroundColor: '#B57614', tension: .3, pointRadius: 2 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#E4EAE7' }, ticks: { precision: 0 } } },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
        },
    });
</script>
@endpush
