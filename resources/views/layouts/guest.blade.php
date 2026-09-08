@extends('layouts.base')

@section('body')
<div class="min-h-full lg:grid lg:grid-cols-[1.1fr_1fr]">
    <div class="hidden lg:flex flex-col justify-between bg-ink-900 text-ink-100 p-12">
        @include('partials.brand', ['tone' => 'dark', 'size' => 'h-10'])

        <div class="max-w-md">
            <h1 class="text-4xl leading-tight text-white">Run every WhatsApp conversation your business has, from one desk.</h1>
            <p class="mt-5 text-ink-300 leading-relaxed">
                Connect your WhatsApp Business number, build templates that pass review the first time,
                price a send before you commit to it, and watch every message land.
            </p>
            <dl class="mt-10 grid grid-cols-3 gap-6 text-sm">
                <div><dt class="text-ink-300">Templates</dt><dd class="mt-1 text-white">Reviewed by Meta</dd></div>
                <div><dt class="text-ink-300">Costing</dt><dd class="mt-1 text-white">Shown before send</dd></div>
                <div><dt class="text-ink-300">Receipts</dt><dd class="mt-1 text-white">Per recipient</dd></div>
            </dl>
        </div>

        <p class="text-sm text-ink-500">
            Built on the WhatsApp Cloud API. &copy; {{ now()->year }} {{ config('app.company') }}.
        </p>
    </div>

    <div class="flex min-h-full flex-col items-center justify-center px-6 py-14">
        <div class="w-full max-w-sm">
            <a href="{{ url('/') }}" class="mb-8 inline-block lg:hidden">
                @include('partials.brand', ['size' => 'h-9'])
            </a>

            @yield('form')
        </div>

        <p class="mt-10 text-xs text-ink-500">
            &copy; {{ now()->year }} {{ config('app.company') }} ·
            <a href="{{ route('privacy') }}" class="hover:underline underline-offset-2">Privacy</a> ·
            <a href="{{ route('terms') }}" class="hover:underline underline-offset-2">Terms</a>
        </p>
    </div>
</div>
@endsection
