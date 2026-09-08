{{--
    Brand lockup. $tone: 'dark' for the ink sidebar, 'light' for white pages.
    $size controls the mark; the wordmark scales with it.
--}}
@php
    $tone = $tone ?? 'light';
    $size = $size ?? 'h-9';
@endphp

<span class="flex items-center gap-2.5">
    <img src="{{ asset('images/bhatsapp-logo.png') }}" alt="" class="{{ $size }} w-auto shrink-0">
    <span class="tracking-tight {{ $tone === 'dark' ? 'text-white' : 'text-ink-900' }}">Bhatsapp</span>
</span>
