@extends('layouts.app')
@section('title', 'Sends — Bhatsapp')
@section('heading', 'Sends')
@section('subheading', 'Every batch you have sent, with how it landed.')

@section('actions')
    <a href="{{ route('campaigns.create') }}" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">New send</a>
@endsection

@section('content')
<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Send</th>
                <th class="px-3 py-3 font-normal">Template</th>
                <th class="px-3 py-3 font-normal">Status</th>
                <th class="px-3 py-3 text-right font-normal">Recipients</th>
                <th class="px-3 py-3 text-right font-normal">Delivered</th>
                <th class="px-3 py-3 text-right font-normal">Failed</th>
                <th class="px-5 py-3 text-right font-normal">Cost</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        @forelse ($campaigns as $campaign)
            <tr class="hover:bg-ink-50">
                <td class="px-5 py-3">
                    <a href="{{ route('campaigns.show', $campaign) }}" class="hover:underline underline-offset-2">{{ $campaign->name }}</a>
                    <p class="text-xs text-ink-500">{{ $campaign->created_at->format('d M Y, g:i A') }}</p>
                </td>
                <td class="px-3 py-3 text-ink-500">{{ $campaign->template?->name ?? '—' }}</td>
                <td class="px-3 py-3">
                    <span class="text-{{ $campaign->status === 'completed' ? 'jade-700' : ($campaign->status === 'failed' ? 'alert-600' : 'signal-700') }}">
                        {{ ucfirst($campaign->status) }}
                    </span>
                </td>
                <td class="num px-3 py-3 text-right">{{ number_format($campaign->recipients_count) }}</td>
                <td class="num px-3 py-3 text-right text-jade-700">{{ number_format($campaign->delivered_count) }}</td>
                <td class="num px-3 py-3 text-right {{ $campaign->failed_count ? 'text-alert-600' : 'text-ink-300' }}">{{ number_format($campaign->failed_count) }}</td>
                <td class="num px-5 py-3 text-right">@money((float) ($campaign->actual_cost ?: $campaign->estimated_cost), $campaign->currency)</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-ink-500">
                    Nothing sent yet. <a href="{{ route('campaigns.create') }}" class="text-jade-700 underline underline-offset-2">Start your first send</a>.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $campaigns->links() }}</div>
@endsection
