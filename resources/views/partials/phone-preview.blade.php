{{--
    Renders a WhatsApp-style preview from a Graph component array.
    Usage: @include('partials.phone-preview', ['components' => $template->components, 'sample' => $customer])
--}}
@php
    $components = $components ?? [];
    $sampleName = isset($sample) && $sample ? $sample->firstName() : 'Priya';

    $find = function (string $type) use ($components) {
        foreach ($components as $c) {
            if (strtoupper($c['type'] ?? '') === $type) return $c;
        }
        return null;
    };
    $fill = function (?string $text) use ($sampleName) {
        return preg_replace_callback('/\{\{(\d+)\}\}/', fn ($m) => $m[1] === '1' ? $sampleName : 'Value '.$m[1], (string) $text);
    };
    $format = function (?string $text) {
        $text = e($text);
        $text = preg_replace('/\*(.+?)\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/_(.+?)_/s', '<em>$1</em>', $text);
        return nl2br($text);
    };

    $header = $find('HEADER');
    $body = $find('BODY');
    $footer = $find('FOOTER');
    $buttons = $find('BUTTONS');
    $carousel = $find('CAROUSEL');
@endphp

<div class="chat-canvas rounded-xl border border-ink-200 p-4">
    <div class="mx-auto max-w-[320px] space-y-2">
        <div class="rounded-lg rounded-tl-sm bg-white px-3 py-2.5 shadow-sm">
            @if ($header)
                @if (strtoupper($header['format'] ?? 'TEXT') === 'TEXT')
                    <p class="mb-1.5 text-[15px] font-semibold leading-snug text-ink-900">{!! $format($fill($header['text'] ?? '')) !!}</p>
                @else
                    @php
                        $mediaUrl = data_get($header, 'example.header_handle.0');
                        $mediaFormat = strtoupper($header['format'] ?? 'IMAGE');
                        $fileName = $mediaUrl ? rawurldecode(basename(parse_url($mediaUrl, PHP_URL_PATH) ?? '')) : '';
                    @endphp
                    @if ($mediaUrl && $mediaFormat === 'IMAGE')
                        <img src="{{ $mediaUrl }}" alt="" class="mb-2 h-32 w-full rounded-md object-cover">
                    @elseif ($mediaUrl && $mediaFormat === 'VIDEO')
                        <video src="{{ $mediaUrl }}" muted playsinline controls preload="metadata"
                               class="mb-2 h-32 w-full rounded-md bg-black object-cover"></video>
                    @elseif ($mediaUrl && $mediaFormat === 'DOCUMENT')
                        <div class="mb-2 flex items-center gap-2.5 rounded-md bg-ink-50 px-3 py-2.5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded bg-white text-[10px] text-ink-500">
                                {{ Str::upper(Str::limit(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'file', 4, '')) }}
                            </span>
                            <span class="truncate text-[13px] text-ink-700">{{ $fileName ?: 'document' }}</span>
                        </div>
                    @else
                        <div class="mb-2 grid h-24 w-full place-items-center rounded-md bg-ink-100 text-xs text-ink-500">
                            {{ strtolower($header['format'] ?? 'media') }} header
                        </div>
                    @endif
                @endif
            @endif

            <p class="whitespace-pre-line text-[14.5px] leading-relaxed text-ink-900">{!! $format($fill($body['text'] ?? 'Your message body appears here.')) !!}</p>

            @if ($footer)
                <p class="mt-2 text-[12px] text-ink-500">{{ $footer['text'] }}</p>
            @endif

            <p class="mt-1 text-right text-[11px] text-ink-300">{{ now()->format('g:i A') }}</p>
        </div>

        @if ($buttons)
            <div class="space-y-1">
                @foreach ($buttons['buttons'] ?? [] as $button)
                    <div class="rounded-lg bg-white py-2 text-center text-[14px] text-[#0a7cff] shadow-sm">
                        {{ $button['text'] ?? ($button['example'] ?? 'Copy code') }}
                    </div>
                @endforeach
            </div>
        @endif

        @if ($carousel)
            <div class="flex gap-2 overflow-x-auto pb-1">
                @foreach ($carousel['cards'] ?? [] as $card)
                    @php
                        $cardHeader = collect($card['components'] ?? [])->firstWhere('type', 'HEADER');
                        $cardBody = collect($card['components'] ?? [])->firstWhere('type', 'BODY');
                        $cardButtons = collect($card['components'] ?? [])->firstWhere('type', 'BUTTONS');
                        $cardMedia = data_get($cardHeader, 'example.header_handle.0');
                    @endphp
                    <div class="w-[190px] shrink-0 overflow-hidden rounded-lg bg-white shadow-sm">
                        @if ($cardMedia && strtoupper($cardHeader['format'] ?? 'IMAGE') === 'VIDEO')
                            <video src="{{ $cardMedia }}" muted playsinline preload="metadata" class="h-24 w-full bg-black object-cover"></video>
                        @elseif ($cardMedia)
                            <img src="{{ $cardMedia }}" alt="" class="h-24 w-full object-cover">
                        @else
                            <div class="grid h-24 w-full place-items-center bg-ink-100 text-xs text-ink-500">card media</div>
                        @endif
                        <p class="px-2.5 py-2 text-[13px] leading-snug text-ink-900">{!! $format($fill($cardBody['text'] ?? '')) !!}</p>
                        @foreach ($cardButtons['buttons'] ?? [] as $button)
                            <div class="border-t border-ink-100 py-1.5 text-center text-[13px] text-[#0a7cff]">{{ $button['text'] ?? '' }}</div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
