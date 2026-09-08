{{-- Reporting: volume and outcomes over a date range. --}}
<svg viewBox="0 0 280 200" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-auto w-full" role="img" aria-label="A chart of message volume and outcomes">
    <rect x="20" y="20" width="240" height="160" rx="12" fill="#FFFFFF" stroke="#DCE4E1" stroke-width="2"/>
    <rect x="40" y="38" width="64" height="8" rx="4" fill="#1E332E"/>
    <rect x="196" y="36" width="44" height="12" rx="6" fill="#F1F4F2"/>

    <path d="M40 148h200" stroke="#E4EAE7" stroke-width="1.5"/>
    <path d="M40 116h200" stroke="#E4EAE7" stroke-width="1.5" stroke-dasharray="3 5"/>
    <path d="M40 84h200" stroke="#E4EAE7" stroke-width="1.5" stroke-dasharray="3 5"/>

    @foreach ([[56, 44], [86, 66], [116, 32], [146, 78], [176, 54], [206, 88]] as $bar)
        <rect x="{{ $bar[0] }}" y="{{ 148 - $bar[1] }}" width="18" height="{{ $bar[1] }}" rx="4" fill="#17755E"/>
        <rect x="{{ $bar[0] }}" y="{{ 148 - min($bar[1], 12) }}" width="18" height="{{ min($bar[1], 12) }}" rx="4" fill="#B23A2F"/>
    @endforeach

    <path d="M65 78c30 14 60-22 90 6s45-18 75-6" stroke="#B57614" stroke-width="2.5" stroke-linecap="round" fill="none"/>
</svg>
