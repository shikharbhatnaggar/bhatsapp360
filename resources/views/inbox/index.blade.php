@extends('layouts.app')
@section('title', 'Replies — '.config('app.name'))
@section('heading', 'Replies')
@section('subheading', 'Messages customers have sent back to your number.')

@section('actions')
    @if (config('whatsapp.sandbox'))
        <form method="POST" action="{{ route('sandbox.inbound') }}">
            @csrf
            <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Simulate an incoming reply</button>
        </form>
    @endif
@endsection

@section('content')
<div class="grid gap-4 overflow-hidden rounded-xl border border-ink-200 bg-white lg:grid-cols-[280px_1fr] lg:gap-0">
    <div class="lg:border-r lg:border-ink-100">
        <p class="border-b border-ink-100 px-4 py-3 text-sm text-ink-500">{{ $threads->count() }} conversations</p>
        <ul class="max-h-[32rem] divide-y divide-ink-100 overflow-y-auto">
            @forelse ($threads as $thread)
                <li>
                    <a href="{{ route('inbox.index', ['customer' => $thread->id]) }}"
                       class="block px-4 py-3 hover:bg-ink-50 {{ $active?->id === $thread->id ? 'bg-jade-50' : '' }}">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm">{{ $thread->name }}</span>
                            <span class="shrink-0 text-xs text-ink-500">{{ $thread->last_inbound_at?->diffForHumans(null, true) }}</span>
                        </div>
                        <p class="num mt-0.5 text-xs text-ink-500">+{{ $thread->phone }}</p>
                        @if ($thread->serviceWindowOpen())
                            <p class="mt-1 text-xs text-jade-700">Reply window open</p>
                        @endif
                    </a>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-ink-500">
                    No replies yet. They land here the moment a customer writes back.
                </li>
            @endforelse
        </ul>
    </div>

    <div class="flex min-h-[32rem] flex-col">
        @if ($active)
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-3">
                <div>
                    <p class="text-sm">{{ $active->name }}</p>
                    <p class="num text-xs text-ink-500">+{{ $active->phone }} · {{ ucfirst($active->type) }}</p>
                </div>
                <a href="{{ route('customers.edit', $active) }}" class="text-sm text-jade-700 underline underline-offset-2">Contact details</a>
            </div>

            <div class="chat-canvas flex-1 space-y-2 overflow-y-auto p-5">
                @foreach ($conversation as $message)
                    <div class="flex {{ $message->direction === 'inbound' ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[70%] rounded-lg px-3 py-2 text-sm shadow-sm {{ $message->direction === 'inbound' ? 'bg-white' : 'bg-[#D9FDD3]' }}">
                            <p class="whitespace-pre-line leading-relaxed">{{ $message->body_preview }}</p>
                            <p class="mt-1 text-right text-[11px] text-ink-500">
                                {{ $message->created_at->format('d M, g:i A') }}
                                @if ($message->direction === 'outbound') · {{ $message->status }} @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-ink-100 p-4">
                @if ($active->serviceWindowOpen())
                    <form method="POST" action="{{ route('inbox.reply', $active) }}" class="flex gap-2">
                        @csrf
                        <input name="body" required placeholder="Write a reply" autocomplete="off"
                               class="flex-1 rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <button class="rounded-lg bg-jade-600 px-4 py-2 text-sm font-medium text-white hover:bg-jade-700">Send</button>
                    </form>
                    <p class="mt-2 text-xs text-ink-500">
                        Free-form replies are allowed until {{ $active->last_inbound_at->addDay()->format('d M, g:i A') }} — 24 hours after their last message.
                    </p>
                @else
                    <p class="rounded-lg bg-signal-50 px-3.5 py-2.5 text-sm text-signal-700">
                        The 24-hour reply window has closed. Send an approved template to reopen the conversation.
                    </p>
                @endif
            </div>
        @else
            <div class="grid flex-1 place-items-center p-10 text-center text-sm text-ink-500">
                <p>Pick a conversation on the left to read it.</p>
            </div>
        @endif
    </div>
</div>
@endsection
