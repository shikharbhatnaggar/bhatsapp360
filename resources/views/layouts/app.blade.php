@extends('layouts.base')

@php
    $nav = [
        ['route' => 'dashboard', 'label' => 'Overview', 'match' => 'dashboard'],
        ['route' => 'campaigns.index', 'label' => 'Sends', 'match' => 'campaigns.*'],
        ['route' => 'templates.index', 'label' => 'Templates', 'match' => 'templates.*'],
        ['route' => 'customers.index', 'label' => 'Contacts', 'match' => 'customers.*'],
        ['route' => 'inbox.index', 'label' => 'Replies', 'match' => 'inbox.*'],
        ['route' => 'wallet.index', 'label' => 'Wallet', 'match' => 'wallet.*'],
        ['route' => 'logs.index', 'label' => 'Activity', 'match' => 'logs.*'],
    ];
@endphp

@section('body')
<div class="min-h-full" x-data="{ nav: false }" @keydown.escape.window="nav = false">

    {{-- Mobile bar. The drawer lives outside it so it can cover the full height. --}}
    <header class="sticky top-0 z-30 flex items-center gap-3 bg-ink-900 px-4 py-3 lg:hidden">
        <button type="button" @click="nav = true"
                class="-ml-1 rounded-lg p-2 text-ink-300 hover:bg-ink-700/50 hover:text-white"
                aria-label="Open navigation" :aria-expanded="nav">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M3 5h14v2H3V5zm0 4h14v2H3V9zm0 4h14v2H3v-2z"/>
            </svg>
        </button>
        <a href="{{ route('dashboard') }}">@include('partials.brand', ['tone' => 'dark', 'size' => 'h-7'])</a>
    </header>

    {{-- Scrim: tapping outside the drawer closes it. --}}
    <div x-show="nav" x-cloak x-transition.opacity.duration.200ms @click="nav = false"
         class="fixed inset-0 z-40 bg-ink-900/60 lg:hidden" aria-hidden="true"></div>

    <div class="lg:flex">
        <aside x-cloak
               class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-ink-900 text-ink-300 transition-transform duration-200 ease-out
                      lg:static lg:z-auto lg:w-60 lg:shrink-0 lg:translate-x-0 lg:transition-none"
               :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            <div class="flex items-center justify-between px-5 py-5">
                <a href="{{ route('dashboard') }}">@include('partials.brand', ['tone' => 'dark'])</a>
                <button type="button" @click="nav = false"
                        class="rounded-lg p-2 text-ink-300 hover:bg-ink-700/50 hover:text-white lg:hidden"
                        aria-label="Close navigation">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.3 5 5 6.3 8.7 10 5 13.7 6.3 15 10 11.3 13.7 15 15 13.7 11.3 10 15 6.3 13.7 5 10 8.7 6.3 5z"/>
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 pb-4">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       class="block rounded-lg px-3 py-2.5 text-sm lg:py-2 {{ request()->routeIs($item['match']) ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <div class="my-3 border-t border-ink-700"></div>

                @if (auth()->user()->is_platform_admin)
                    <a href="{{ route('admin.topups') }}"
                       class="block rounded-lg px-3 py-2.5 text-sm lg:py-2 {{ request()->routeIs('admin.*') ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white' }}">
                        Top-up approvals
                    </a>
                @endif

                <a href="{{ route('diagnostics') }}"
                   class="block rounded-lg px-3 py-2.5 text-sm lg:py-2 {{ request()->routeIs('diagnostics') ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white' }}">
                    Diagnostics
                </a>

                <a href="{{ route('settings.whatsapp') }}"
                   class="block rounded-lg px-3 py-2.5 text-sm lg:py-2 {{ request()->routeIs('settings.*') ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white' }}">
                    WhatsApp settings
                </a>

                <div class="mt-6 rounded-lg bg-ink-700/40 px-3 py-3 text-xs leading-relaxed">
                    <p class="text-white">{{ $tenant->name }}</p>
                    @if ($whatsappAccount?->verified_at)
                        <p class="mt-1 flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-jade-400"></span>
                            {{ $whatsappAccount->display_phone_number ?: $whatsappAccount->phone_number_id }}
                        </p>
                    @else
                        <p class="mt-1 text-signal-200">No number connected</p>
                    @endif
                    <p class="num mt-2 {{ $tenant->wallet_balance <= config('wallet.low_balance_threshold') ? 'text-signal-200' : 'text-ink-300' }}">
                        Wallet @money((float) $tenant->wallet_balance, $tenant->currency)
                    </p>
                    @if (config('whatsapp.sandbox'))
                        <p class="mt-2 text-ink-300">Sandbox mode — Graph calls are simulated.</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button class="w-full rounded-lg px-3 py-2.5 text-left text-sm hover:bg-ink-700/50 hover:text-white lg:py-2">
                        Sign out, {{ auth()->user()->name }}
                    </button>
                </form>
            </nav>
        </aside>

        <main class="flex min-h-screen flex-1 flex-col min-w-0">
            <div class="mx-auto w-full max-w-7xl flex-1 px-5 py-7 lg:px-10 lg:py-10">
                <header class="mb-7 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-2xl tracking-tight">@yield('heading')</h1>
                        @hasSection('subheading')
                            <p class="mt-1 text-sm text-ink-500">@yield('subheading')</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">@yield('actions')</div>
                </header>

                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-jade-200 bg-jade-50 px-4 py-3 text-sm text-jade-700">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-5 rounded-lg border border-alert-200 bg-alert-50 px-4 py-3 text-sm text-alert-700">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-alert-200 bg-alert-50 px-4 py-3 text-sm text-alert-700">
                        <p>Fix the following before continuing:</p>
                        <ul class="mt-1.5 list-disc pl-5 space-y-0.5">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>

            @include('partials.footer')
        </main>
    </div>
</div>
@endsection
