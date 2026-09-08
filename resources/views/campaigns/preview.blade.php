@extends('layouts.app')
@section('title', 'Confirm send — '.config('app.name'))
@section('heading', 'Confirm this send')
@section('subheading', 'This is exactly what goes out, and what it costs. Nothing is sent until you confirm.')

@section('content')
<div class="grid gap-4 lg:grid-cols-[1fr_360px]">
    <div class="space-y-4">
        @if ($quote['uses_fallback'])
            <div class="rounded-xl border border-alert-200 bg-alert-50 p-4 text-sm text-alert-700">
                Some recipients have no matching rate card row, so they are priced at cost with no markup.
                Check <span class="font-mono text-xs">whatsapp_rates</span> before sending.
            </div>
        @endif

        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Cost</h2>
            <table class="mt-4 w-full text-sm">
                <thead class="text-left text-ink-500">
                    <tr>
                        <th class="pb-2 font-normal">Country</th>
                        <th class="pb-2 text-right font-normal">Recipients</th>
                        <th class="pb-2 text-right font-normal">Per message</th>
                        <th class="pb-2 text-right font-normal">Line total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                @foreach ($quote['breakdown'] as $line)
                    <tr>
                        <td class="py-2.5">{{ $line['country_code'] }}</td>
                        <td class="num py-2.5 text-right">{{ number_format($line['count']) }}</td>
                        <td class="num py-2.5 text-right">@money($line['unit_price'], $line['currency'])</td>
                        <td class="num py-2.5 text-right">@money($line['line_total'], $line['currency'])</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr class="text-ink-500">
                        <td class="pt-3 text-xs" colspan="2">Meta cost @money($quote['meta_total'], $quote['currency'])
                            · your margin @money($quote['markup_total'], $quote['currency'])</td>
                        <td class="pt-3"></td><td class="pt-3"></td>
                    </tr>
                    <tr class="border-t border-ink-200">
                        <td class="pt-3">{{ ucfirst(strtolower($template->category)) }} rate</td>
                        <td class="num pt-3 text-right">{{ number_format($quote['count']) }}</td>
                        <td class="num pt-3 text-right">@money($quote['unit_price'], $quote['currency'])</td>
                        <td class="num pt-3 text-right text-base">@money($quote['total'], $quote['currency'])</td>
                    </tr>
                </tfoot>
            </table>
            @php $balance = (float) $tenant->wallet_balance; $affordable = $balance >= $quote['total']; @endphp

            <div class="mt-4 rounded-lg border {{ $affordable ? 'border-ink-200 bg-ink-50' : 'border-alert-200 bg-alert-50' }} px-4 py-3 text-sm">
                <div class="flex justify-between"><span class="text-ink-500">Wallet balance</span><span class="num">@money($balance, $quote['currency'])</span></div>
                <div class="flex justify-between"><span class="text-ink-500">This send</span><span class="num">−@money($quote['total'], $quote['currency'])</span></div>
                <div class="mt-1.5 flex justify-between border-t border-ink-200 pt-1.5">
                    <span>Balance after</span><span class="num">@money($balance - $quote['total'], $quote['currency'])</span>
                </div>
                @unless ($affordable)
                    <p class="mt-2 text-alert-700">
                        Not enough to cover this send.
                        <a href="{{ route('wallet.index') }}" class="underline underline-offset-2">Top up your wallet</a> first.
                    </p>
                @endunless
            </div>

            <p class="mt-3 text-xs leading-relaxed text-ink-500">
                Each message is charged when WhatsApp accepts it. Anything WhatsApp reports as undeliverable is refunded to your wallet automatically.
            </p>
        </section>

        <section class="rounded-xl border border-ink-200 bg-white">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-3.5">
                <h2 class="text-base">Recipients</h2>
                <p class="num text-sm text-ink-500">{{ $recipients->count() }} selected</p>
            </div>
            <div class="max-h-72 overflow-y-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-ink-100">
                    @foreach ($recipients as $customer)
                        <tr>
                            <td class="px-5 py-2.5">{{ $customer->name }}</td>
                            <td class="num py-2.5 text-ink-500">+{{ $customer->phone }}</td>
                            <td class="px-5 py-2.5 text-right text-xs {{ $customer->opted_in ? 'text-ink-300' : 'text-signal-700' }}">
                                {{ $customer->opted_in ? 'opted in' : 'no opt-in on record' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <details class="rounded-xl border border-ink-200 bg-white p-5">
            <summary class="cursor-pointer text-base">API payload for the first recipient</summary>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-ink-900 p-4 text-xs leading-relaxed text-ink-100">{{ json_encode($samplePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>

        <form method="POST" action="{{ route('campaigns.store') }}" class="flex flex-wrap items-center gap-3">
            @csrf
            <input type="hidden" name="name" value="{{ $name }}">
            <input type="hidden" name="message_template_id" value="{{ $template->id }}">
            @foreach ($recipients as $customer)
                <input type="hidden" name="customer_ids[]" value="{{ $customer->id }}">
            @endforeach

            <button class="rounded-lg bg-jade-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-jade-700">
                Send to {{ $recipients->count() }} {{ Str::plural('contact', $recipients->count()) }} · @money($quote['total'], $quote['currency'])
            </button>
            <a href="{{ url()->previous() }}" class="text-sm text-ink-500 underline underline-offset-2">Back to selection</a>
        </form>
    </div>

    <aside>
        <div class="sticky top-6">
            <h2 class="mb-2 text-sm text-ink-500">
                Preview @if ($sample) as {{ $sample->name }} sees it @endif
            </h2>
            @include('partials.phone-preview', ['components' => $template->components, 'sample' => $sample])
            <div class="mt-3 rounded-lg border border-ink-200 bg-white p-3.5 text-xs leading-relaxed text-ink-500">
                Template <span class="text-ink-900">{{ $template->name }}</span> · {{ $template->language }} ·
                approved {{ $template->approved_at?->diffForHumans() ?? 'recently' }}.
            </div>
        </div>
    </aside>
</div>
@endsection
