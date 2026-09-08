@extends('layouts.app')
@section('title', 'Diagnostics — '.config('app.name'))
@section('heading', 'Diagnostics')
@section('subheading', 'What is actually deployed on this server, and what is missing.')

@section('content')
@php
    $all = collect($groups)->flatten(1);
    $failing = $all->where('ok', false);
@endphp

<div class="mb-4 rounded-xl border p-5 {{ $failing->isEmpty() ? 'border-jade-200 bg-jade-50' : 'border-alert-200 bg-alert-50' }}">
    <p class="text-base {{ $failing->isEmpty() ? 'text-jade-700' : 'text-alert-700' }}">
        {{ $failing->isEmpty()
            ? 'Everything checks out — '.$all->count().' checks passed.'
            : $failing->count().' of '.$all->count().' checks need attention.' }}
    </p>
    <p class="mt-1 text-sm {{ $failing->isEmpty() ? 'text-jade-700' : 'text-alert-700' }}">
        PHP {{ $phpVersion }} · Laravel {{ $laravelVersion }} · {{ $timezone }} · server time {{ $serverTime }}
    </p>
</div>

@foreach ($groups as $title => $checks)
    <section class="mb-4 overflow-hidden rounded-xl border border-ink-200 bg-white">
        <h2 class="border-b border-ink-100 px-5 py-3.5 text-base">{{ $title }}</h2>
        <ul class="divide-y divide-ink-100">
            @foreach ($checks as $check)
                <li class="flex items-start gap-3 px-5 py-3.5">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $check['ok'] ? 'bg-jade-400' : 'bg-alert-600' }}"></span>
                    <div class="min-w-0">
                        <p class="text-sm {{ $check['ok'] ? '' : 'text-alert-700' }}">{{ $check['label'] }}</p>
                        <p class="mt-0.5 text-xs text-ink-500">{{ $check['detail'] }}</p>
                        @unless ($check['ok'])
                            <p class="mt-1 text-xs text-alert-600">Fix: {{ $check['fix'] }}</p>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endforeach

<p class="text-xs leading-relaxed text-ink-500">
    Read-only. Nothing on this page changes data.
</p>
@endsection
