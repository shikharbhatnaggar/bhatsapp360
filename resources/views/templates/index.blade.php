@extends('layouts.app')
@section('title', 'Templates — Bhatsapp')
@section('heading', 'Templates')
@section('subheading', 'Every template WhatsApp has reviewed, and the ones still waiting.')

@section('actions')
    <form method="POST" action="{{ route('templates.refresh') }}">
        @csrf
        <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Check review status</button>
    </form>
    <a href="{{ route('templates.create') }}" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">New template</a>
@endsection

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Search by name"
           class="w-56 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    <select name="category" class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
        <option value="">All categories</option>
        @foreach (config('whatsapp.categories') as $value => $label)
            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
        <option value="">Any status</option>
        @foreach (config('whatsapp.statuses') as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(strtolower($status)) }}</option>
        @endforeach
    </select>
    <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Filter</button>
</form>

<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Name</th>
                <th class="px-3 py-3 font-normal">Category</th>
                <th class="px-3 py-3 font-normal">Status</th>
                <th class="px-3 py-3 font-normal">Version</th>
                <th class="px-3 py-3 font-normal">Last change</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        @forelse ($templates as $template)
            <tr class="hover:bg-ink-50">
                <td class="px-5 py-3">
                    <a href="{{ route('templates.show', $template) }}" class="hover:underline underline-offset-2">{{ $template->name }}</a>
                    <p class="text-xs text-ink-500">
                        {{ $template->language }}@if ($template->isCarousel()) · media card carousel @endif
                    </p>
                </td>
                <td class="px-3 py-3 text-ink-500">{{ ucfirst(strtolower($template->category)) }}</td>
                <td class="px-3 py-3">
                    <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs {{ $template->statusColor() }}">
                        {{ ucfirst(strtolower($template->status)) }}
                    </span>
                    @if ($template->rejected_reason)
                        <p class="mt-1 text-xs text-alert-600">{{ $template->rejected_reason }}</p>
                    @endif
                </td>
                <td class="num px-3 py-3 text-ink-500">v{{ $template->version }}</td>
                <td class="px-3 py-3 text-ink-500">{{ $template->updated_at->diffForHumans() }}</td>
                <td class="px-5 py-3 text-right">
                    @if ($template->isSendable())
                        <a href="{{ route('campaigns.create', ['template_id' => $template->id]) }}" class="text-jade-700 underline underline-offset-2">Send</a>
                    @else
                        <a href="{{ route('templates.edit', $template) }}" class="text-jade-700 underline underline-offset-2">Edit</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-5 py-10 text-center text-ink-500">
                    No templates yet. <a href="{{ route('templates.create') }}" class="text-jade-700 underline underline-offset-2">Build your first one</a> — WhatsApp reviews it before you can send.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $templates->links() }}</div>
@endsection
