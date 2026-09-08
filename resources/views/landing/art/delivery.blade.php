{{-- Delivery: one row per recipient, with its receipt trail. --}}
<svg viewBox="0 0 280 200" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-auto w-full" role="img" aria-label="Per-recipient delivery receipts">
    <rect x="20" y="24" width="240" height="152" rx="12" fill="#FFFFFF" stroke="#DCE4E1" stroke-width="2"/>
    <rect x="20" y="24" width="240" height="30" rx="12" fill="#F1F4F2"/>
    <rect x="20" y="42" width="240" height="12" fill="#F1F4F2"/>
    <rect x="38" y="35" width="52" height="7" rx="3.5" fill="#A9B8B3"/>
    <rect x="150" y="35" width="42" height="7" rx="3.5" fill="#A9B8B3"/>

    @foreach ([0, 1, 2] as $row)
        <rect x="38" y="{{ 72 + $row * 32 }}" width="70" height="7" rx="3.5" fill="#DCE4E1"/>
        <rect x="38" y="{{ 84 + $row * 32 }}" width="46" height="5" rx="2.5" fill="#E4EAE7"/>
    @endforeach

    <rect x="150" y="70" width="52" height="16" rx="8" fill="#E7F3EF"/>
    <rect x="160" y="75" width="32" height="6" rx="3" fill="#14624F"/>
    <path d="m216 78 3 3 6-6M222 78l3 3 6-6" stroke="#17755E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

    <rect x="150" y="102" width="52" height="16" rx="8" fill="#E7F3EF"/>
    <rect x="160" y="107" width="32" height="6" rx="3" fill="#14624F"/>
    <path d="m216 110 3 3 6-6" stroke="#5A706A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

    <rect x="150" y="134" width="52" height="16" rx="8" fill="#FBEDEB"/>
    <rect x="160" y="139" width="32" height="6" rx="3" fill="#B23A2F"/>
    <path d="M218 138l8 8m0-8l-8 8" stroke="#B23A2F" stroke-width="2" stroke-linecap="round"/>
</svg>
