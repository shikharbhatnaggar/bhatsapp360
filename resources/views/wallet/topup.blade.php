@extends('layouts.app')
@section('title', 'Add funds — '.config('app.name'))
@section('heading', 'Add '.app(\App\Services\PricingService::class)->format((float) $topup->amount, $topup->currency))
@section('subheading', 'Reference '.$topup->reference.' — include it in the payment note so we can match it.')

@section('content')
<div class="grid gap-4 lg:grid-cols-[360px_1fr]">
    <div class="rounded-xl border border-ink-200 bg-white p-6 text-center">
        <p class="text-sm text-ink-500">Scan with any UPI app</p>
        <div id="qr" class="mx-auto mt-4 grid h-[220px] w-[220px] place-items-center rounded-lg bg-ink-50"></div>

        <dl class="mt-5 space-y-1.5 text-left text-sm">
            <div class="flex justify-between"><dt class="text-ink-500">Pay to</dt><dd>{{ config('wallet.upi.payee_name') }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-500">UPI ID</dt><dd class="num">{{ config('wallet.upi.vpa') }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-500">Amount</dt><dd class="num">@money((float) $topup->amount, $topup->currency)</dd></div>
            <div class="flex justify-between"><dt class="text-ink-500">Note</dt><dd class="num">{{ $topup->reference }}</dd></div>
        </dl>

        <a href="{{ $topup->upiUri() }}"
           class="mt-5 block rounded-lg border border-ink-200 px-4 py-2.5 text-sm hover:border-ink-300 lg:hidden">
            Open my UPI app
        </a>
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-ink-200 bg-white p-6">
            <h2 class="text-base">After you pay</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-500">
                Enter the UPI transaction ID from your payment app. We match it against our bank statement and credit
                your wallet — usually within a few working hours. Nothing is credited automatically.
            </p>

            <form method="POST" action="{{ route('wallet.submit', $topup) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="upi_reference" class="block text-sm text-ink-700">UPI transaction ID (UTR)</label>
                    <input id="upi_reference" name="upi_reference" value="{{ old('upi_reference') }}" required
                           placeholder="e.g. 431298765432"
                           class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                    <p class="mt-1 text-xs text-ink-500">In your UPI app, open the payment and look for “UPI transaction ID” or “UTR”.</p>
                </div>
                <div>
                    <label for="payer_note" class="block text-sm text-ink-700">Anything we should know <span class="text-ink-300">optional</span></label>
                    <input id="payer_note" name="payer_note" value="{{ old('payer_note') }}"
                           class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                </div>
                <button class="rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700">
                    I have paid
                </button>
                <a href="{{ route('wallet.index') }}" class="ml-2 text-sm text-ink-500 underline underline-offset-2">Back to wallet</a>
            </form>
        </div>

        <p class="text-xs leading-relaxed text-ink-500">
            Paying without the reference {{ $topup->reference }} in the note makes the payment harder to match and slows
            down the credit.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
    const canvas = document.createElement('canvas');
    document.getElementById('qr').appendChild(canvas);
    new QRious({ element: canvas, value: @json($topup->upiUri()), size: 220, background: '#F1F4F2', foreground: '#0C1B18', level: 'M' });
</script>
@endpush
