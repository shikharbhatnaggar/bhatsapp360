@extends('layouts.base')

@php
    $nav = [
        ['route' => 'dashboard', 'label' => 'Overview', 'match' => 'dashboard'],
        ['route' => 'campaigns.index', 'label' => 'Sends', 'match' => 'campaigns.*'],
        ['route' => 'templates.index', 'label' => 'Templates', 'match' => 'templates.*'],
        ['route' => 'customers.index', 'label' => 'Contacts', 'match' => 'customers.*'],
        ['route' => 'inbox.index', 'label' => 'Replies', 'match' => 'inbox.*'],
        ['route' => 'logs.index', 'label' => 'Activity', 'match' => 'logs.*'],
    ];
@endphp

@section('body')
<div class="min-h-full lg:flex" x-data="{ mobileNav: false }">
    <aside class="lg:w-60 lg:shrink-0 bg-ink-900 text-ink-300 lg:min-h-screen">
        <div class="flex items-center justify-between px-5 py-4 lg:py-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-jade-600 text-white font-semibold">B</span>
                <span class="text-white tracking-tight">Bhatsapp</span>
            </a>
            <button type="button" class="lg:hidden rounded p-2 text-ink-300 hover:text-white" @click="mobileNav = !mobileNav" aria-label="Toggle navigation">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M3 5h14v2H3V5zm0 4h14v2H3V9zm0 4h14v2H3v-2z"/></svg>
            </button>
        </div>

        <nav class="px-3 pb-4 lg:block" :class="mobileNav ? 'block' : 'hidden lg:block'">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs($item['match']) ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach

            <div class="my-3 border-t border-ink-700"></div>

            <a href="{{ route('settings.whatsapp') }}"
               class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('settings.*') ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white' }}">
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
                @if (config('whatsapp.sandbox'))
                    <p class="mt-2 text-ink-300">Sandbox mode — Graph calls are simulated.</p>
                @endif
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-ink-700/50 hover:text-white">
                    Sign out, {{ auth()->user()->name }}
                </button>
            </form>
        </nav>
    </aside>

    <main class="flex-1 min-w-0">
        <div class="mx-auto max-w-7xl px-5 py-7 lg:px-10 lg:py-10">
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
    </main>
</div>
@endsection
