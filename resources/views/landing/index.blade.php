@extends('layouts.base')
@section('title', config('app.name').' — WhatsApp Business messaging, priced before you send')

@section('body')
@php
    $features = [
        [
            'art' => 'template',
            'kicker' => 'Templates',
            'title' => 'Build it, preview it, get it approved',
            'body' => 'Compose headers, body copy with variables, footers, buttons and media card carousels, and watch a live WhatsApp-style preview as you type. Submit to Meta from the same screen. Every edit is versioned and goes back through review, so you always know which wording was approved and when.',
            'points' => ['Live preview with sample values', 'Media card carousels up to 10 cards', 'Version history per approval event'],
        ],
        [
            'art' => 'pricing',
            'kicker' => 'Pricing',
            'title' => 'See the cost before you commit',
            'body' => 'Pick your recipients and the exact cost appears before anything is sent, broken down by country and category. Base cost and margin are held separately, so you can price your clients deliberately instead of guessing at a blended number.',
            'points' => ['Cost shown at the confirmation step', 'Base cost and margin tracked apart', 'Per-country, per-category rate card'],
        ],
        [
            'art' => 'wallet',
            'kicker' => 'Wallet',
            'title' => 'Prepaid balance, ledgered to the paisa',
            'body' => 'Clients top up by scanning a UPI code. Every message debits the wallet when WhatsApp accepts it, and anything WhatsApp reports as undeliverable is refunded automatically. Every movement writes a ledger row, so the balance can always be explained.',
            'points' => ['UPI top-ups with matched references', 'Automatic refunds on failed sends', 'Full transaction history'],
        ],
        [
            'art' => 'delivery',
            'kicker' => 'Delivery',
            'title' => 'Know what landed, recipient by recipient',
            'body' => 'Sent, delivered, read or failed — tracked per person with the timestamp of every receipt WhatsApp returns. Failures carry the actual error, so an undeliverable number looks nothing like a rate limit.',
            'points' => ['Receipt trail per recipient', 'Real error codes on failure', 'Campaign totals that firm up as receipts land'],
        ],
        [
            'art' => 'inbox',
            'kicker' => 'Replies',
            'title' => 'Answer people who write back',
            'body' => 'Inbound messages arrive in a shared inbox with the whole conversation in one thread. The 24-hour service window is shown plainly, so your team knows when a free-form reply is allowed and when a template is required.',
            'points' => ['Threaded conversations', 'Service window countdown', 'Contacts created from inbound messages'],
        ],
        [
            'art' => 'dashboard',
            'kicker' => 'Reporting',
            'title' => 'Numbers you can hand to a client',
            'body' => 'Volume, delivery rate, read rate, replies and spend across any date range. Underneath it sits an audit log of every contact change, template review, send and receipt — worth having the first time someone asks what happened.',
            'points' => ['Date-range dashboard', 'Spend broken down by category', 'Complete audit trail'],
        ],
    ];

    $steps = [
        ['Connect your number', 'Add your WhatsApp Business Account and phone number ID. A connection test confirms both before you rely on them.'],
        ['Get a template approved', 'Build it in the editor, submit to Meta, and watch the review status land back in the console.'],
        ['Send and watch it arrive', 'Choose recipients, confirm the cost, send. Receipts fill in as WhatsApp reports them.'],
    ];
@endphp

<div class="bg-paper">
    <header class="sticky top-0 z-30 border-b border-ink-200/70 bg-paper/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}">@include('partials.brand', ['size' => 'h-9'])</a>
            <nav class="flex items-center gap-2 text-sm">
                <a href="#features" class="hidden rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-100 sm:block">Features</a>
                <a href="#how" class="hidden rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-100 sm:block">How it works</a>
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-100">Sign in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-jade-600 px-3.5 py-2 font-medium text-white hover:bg-jade-700">Get started</a>
            </nav>
        </div>
    </header>

    {{-- Hero ---------------------------------------------------------- --}}
    <section class="mx-auto max-w-6xl px-6 pb-16 pt-14 lg:pb-24 lg:pt-20">
        <div class="grid items-center gap-12 lg:grid-cols-[1fr_1.05fr]">
            <div>
                <p class="text-sm text-jade-700">WhatsApp Business Platform</p>
                <h1 class="mt-3 text-4xl leading-[1.1] tracking-tight text-ink-900 lg:text-5xl">
                    Run every WhatsApp conversation your business has, from one desk.
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-500">
                    {{ config('app.name') }} connects your own WhatsApp Business number, gets your templates
                    through Meta's review, prices a send before you commit to it, and tracks every message
                    until it lands.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('register') }}" class="rounded-lg bg-jade-600 px-5 py-3 text-sm font-medium text-white hover:bg-jade-700">
                        Create your workspace
                    </a>
                    <a href="#features" class="rounded-lg border border-ink-200 bg-white px-5 py-3 text-sm hover:border-ink-300">
                        See what it does
                    </a>
                </div>
                <p class="mt-4 text-sm text-ink-500">Your own WhatsApp number. Your own rates. No per-seat pricing.</p>
            </div>

            <div class="lg:pl-4">@include('landing.art.console')</div>
        </div>
    </section>

    {{-- How it works -------------------------------------------------- --}}
    <section id="how" class="border-y border-ink-200 bg-white">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <h2 class="text-2xl tracking-tight">Three steps to your first send</h2>
            <ol class="mt-10 grid gap-8 md:grid-cols-3">
                @foreach ($steps as $index => $step)
                    <li>
                        <span class="num grid h-9 w-9 place-items-center rounded-lg bg-jade-50 text-sm text-jade-700">{{ $index + 1 }}</span>
                        <h3 class="mt-4 text-lg tracking-tight">{{ $step[0] }}</h3>
                        <p class="mt-2 leading-relaxed text-ink-500">{{ $step[1] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Features ------------------------------------------------------ --}}
    <section id="features" class="mx-auto max-w-6xl px-6 py-16 lg:py-24">
        <h2 class="max-w-2xl text-3xl leading-tight tracking-tight">
            Everything between “we should message our customers” and knowing that it worked.
        </h2>

        <div class="mt-14 space-y-16 lg:space-y-24">
            @foreach ($features as $index => $feature)
                <article class="grid items-center gap-10 lg:grid-cols-2">
                    <div class="{{ $index % 2 ? 'lg:order-2' : '' }}">
                        <p class="text-sm text-jade-700">{{ $feature['kicker'] }}</p>
                        <h3 class="mt-2 text-2xl leading-snug tracking-tight">{{ $feature['title'] }}</h3>
                        <p class="mt-4 leading-relaxed text-ink-500">{{ $feature['body'] }}</p>
                        <ul class="mt-5 space-y-2">
                            @foreach ($feature['points'] as $point)
                                <li class="flex items-start gap-2.5 text-sm">
                                    <svg class="mt-1 h-4 w-4 shrink-0 text-jade-600" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="m3.5 8.5 3 3 6-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    {{ $point }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-ink-200 bg-white p-6 {{ $index % 2 ? 'lg:order-1' : '' }}">
                        @include('landing.art.'.$feature['art'])
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Built for facilitators ---------------------------------------- --}}
    <section class="border-y border-ink-200 bg-ink-900 text-ink-300">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <div class="grid gap-10 lg:grid-cols-[1.1fr_1fr]">
                <div>
                    <h2 class="text-2xl tracking-tight text-white">Built for whoever is holding the account</h2>
                    <p class="mt-4 max-w-xl leading-relaxed">
                        Every workspace is fenced off from the others, with its own number, contacts, templates,
                        rate card and wallet. Run it for your own brand, or resell it to clients with a margin
                        you set per category and per country.
                    </p>
                </div>
                <dl class="grid grid-cols-2 gap-6 text-sm">
                    <div><dt class="text-white">Multi-tenant</dt><dd class="mt-1">One workspace per client, isolated by default.</dd></div>
                    <div><dt class="text-white">Your margin</dt><dd class="mt-1">Set markup per category and country.</dd></div>
                    <div><dt class="text-white">Prepaid</dt><dd class="mt-1">Nothing sends without funds behind it.</dd></div>
                    <div><dt class="text-white">Audited</dt><dd class="mt-1">Every action recorded, with who and when.</dd></div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Close ---------------------------------------------------------- --}}
    <section class="mx-auto max-w-6xl px-6 py-20 text-center">
        <h2 class="mx-auto max-w-2xl text-3xl leading-tight tracking-tight">
            Start with your own number and one approved template.
        </h2>
        <p class="mx-auto mt-4 max-w-xl leading-relaxed text-ink-500">
            Connect a WhatsApp Business Account, build a template, and send to a handful of contacts.
            You will know inside an afternoon whether this fits how you work.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="rounded-lg bg-jade-600 px-5 py-3 text-sm font-medium text-white hover:bg-jade-700">
                Create your workspace
            </a>
            <a href="{{ route('login') }}" class="rounded-lg border border-ink-200 bg-white px-5 py-3 text-sm hover:border-ink-300">
                Sign in
            </a>
        </div>
    </section>

    <footer class="border-t border-ink-200">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-8 text-sm text-ink-500">
            <div class="flex items-center gap-3">
                @include('partials.brand', ['size' => 'h-7'])
            </div>
            <p>&copy; {{ now()->year }} {{ config('app.company') }}. All rights reserved.</p>
            <nav class="flex gap-5">
                <a href="{{ route('privacy') }}" class="hover:text-ink-700 hover:underline underline-offset-2">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-ink-700 hover:underline underline-offset-2">Terms</a>
            </nav>
        </div>
        <p class="mx-auto max-w-6xl px-6 pb-8 text-xs leading-relaxed text-ink-300">
            {{ config('app.name') }} is not affiliated with or endorsed by Meta Platforms, Inc. WhatsApp is a
            trademark of Meta Platforms, Inc. Message delivery, template approval and account standing are
            determined by Meta.
        </p>
    </footer>
</div>
@endsection
