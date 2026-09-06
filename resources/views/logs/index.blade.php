@extends('layouts.app')
@section('title', 'Activity — Bhatsapp')
@section('heading', 'Activity')
@section('subheading', 'Every contact change, template review, send and delivery receipt.')

@section('content')
<form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
    <div>
        <label for="event" class="block text-xs text-ink-500">Area</label>
        <select id="event" name="event" class="mt-1 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
            <option value="">Everything</option>
            @foreach ($groups as $group => $total)
                <option value="{{ $group }}" @selected(request('event') === $group)>{{ ucfirst(str_replace('_', ' ', $group)) }} ({{ $total }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="from" class="block text-xs text-ink-500">From</label>
        <input id="from" type="date" name="from" value="{{ request('from') }}" class="mt-1 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    </div>
    <div>
        <label for="to" class="block text-xs text-ink-500">To</label>
        <input id="to" type="date" name="to" value="{{ request('to') }}" class="mt-1 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    </div>
    <input name="q" value="{{ request('q') }}" placeholder="Search descriptions"
           class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Filter</button>
</form>

<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">When</th>
                <th class="px-3 py-3 font-normal">Event</th>
                <th class="px-3 py-3 font-normal">What happened</th>
                <th class="px-3 py-3 font-normal">Who</th>
                <th class="px-5 py-3 font-normal">Subject</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        @forelse ($logs as $log)
            <tr class="align-top hover:bg-ink-50">
                <td class="whitespace-nowrap px-5 py-3 text-ink-500">{{ $log->created_at->format('d M, H:i:s') }}</td>
                <td class="px-3 py-3">
                    <code class="rounded bg-ink-50 px-1.5 py-0.5 text-xs text-ink-700">{{ $log->event }}</code>
                </td>
                <td class="px-3 py-3">
                    {{ $log->description }}
                    @if ($log->properties)
                        <details class="mt-1">
                            <summary class="cursor-pointer text-xs text-ink-500">details</summary>
                            <pre class="mt-1 max-w-lg overflow-x-auto rounded bg-ink-50 p-2 text-[11px] leading-relaxed">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </td>
                <td class="px-3 py-3 text-ink-500">{{ $log->user?->name ?? 'System' }}</td>
                <td class="px-5 py-3 text-ink-500">{{ $log->subject_type }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-ink-500">No activity matches these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
