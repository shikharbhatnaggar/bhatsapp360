@extends('layouts.app')
@section('title', 'Wallet — Bhatsapp')
@section('heading', 'Wallet')
@section('subheading', 'Prepaid balance. Every message is charged when WhatsApp accepts it.')

@section('actions')
    <form method="POST" action="{{ route('wallet.store') }}" class="flex items-end gap-2">
        @csrf
        <div>
            <label for="amount" class="block text-xs text-ink-500">Add funds</label>
            <input id="amount" name="amount" type="number" step="1" min="{{ config('wallet.minimum_topup') }}"
                   value="{{ config('wallet.suggested_amounts')[1] ?? 1000 }}"
                   class="num mt-1 w-32 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <button class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">Top up</button>
    </form>
@endsection

@section('content')
<section class="grid gap-3 lg:grid-cols-4">
    <div class="rounded-xl border p-5 lg:col-span-2 {{ $balance <= config('wallet.low_balance_threshold') ? 'border-alert-200 bg-alert-50' : 'border-ink-200 bg-white' }}">
        <p class="text-sm text-ink-500">Available balance</p>
        <p class="num mt-1.5 text-4xl tracking-tight">@money($balance, $tenant->currency)</p>
        @if ($balance <= config('wallet.low_balance_threshold'))
            <p class="mt-2 text-sm text-alert-700">Running low. Sends are blocked once the balance cannot cover them.</p>
        @endif
    </div>
    <div class="rounded-xl border border-ink-200 bg-white p-5">
        <p class="text-sm text-ink-500">Spent this month</p>
        <p class="num mt-1.5 text-2xl tracking-tight">@money($spentThisMonth, $tenant->currency)</p>
    </div>
    <div class="rounded-xl border border-ink-200 bg-white p-5">
        <p class="text-sm text-ink-500">Pending top-ups</p>
        <p class="num mt-1.5 text-2xl tracking-tight">{{ $openTopups->count() }}</p>
        @if ($openTopups->isNotEmpty())
            <a href="{{ route('wallet.topup', $openTopups->first()) }}" class="mt-1 inline-block text-sm text-jade-700 underline underline-offset-2">
                Finish {{ $openTopups->first()->reference }}
            </a>
        @endif
    </div>
</section>

<section class="mt-4 grid gap-4 lg:grid-cols-[1.6fr_1fr]">
    <div class="rounded-xl border border-ink-200 bg-white">
        <h2 class="border-b border-ink-100 px-5 py-3.5 text-base">Transactions</h2>
        <table class="w-full text-sm">
            <thead class="border-b border-ink-100 text-left text-ink-500">
                <tr>
                    <th class="px-5 py-3 font-normal">When</th>
                    <th class="px-3 py-3 font-normal">Detail</th>
                    <th class="px-3 py-3 text-right font-normal">Amount</th>
                    <th class="px-5 py-3 text-right font-normal">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
            @forelse ($transactions as $transaction)
                <tr class="hover:bg-ink-50">
                    <td class="whitespace-nowrap px-5 py-3 text-ink-500">{{ $transaction->created_at->format('d M, H:i') }}</td>
                    <td class="px-3 py-3">
                        {{ $transaction->description }}
                        <p class="text-xs text-ink-500">{{ $transaction->type }}</p>
                    </td>
                    <td class="num px-3 py-3 text-right {{ $transaction->direction === 'credit' ? 'text-jade-700' : 'text-ink-900' }}">
                        {{ $transaction->direction === 'credit' ? '+' : '−' }}@money((float) $transaction->amount, $transaction->currency)
                    </td>
                    <td class="num px-5 py-3 text-right text-ink-500">@money((float) $transaction->balance_after, $transaction->currency)</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-ink-500">No transactions yet. Top up to start sending.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4">{{ $transactions->links() }}</div>
    </div>

    <div class="rounded-xl border border-ink-200 bg-white">
        <h2 class="border-b border-ink-100 px-5 py-3.5 text-base">Your rates</h2>
        <table class="w-full text-sm">
            <thead class="border-b border-ink-100 text-left text-ink-500">
                <tr>
                    <th class="px-5 py-3 font-normal">Category</th>
                    <th class="px-3 py-3 font-normal">Country</th>
                    <th class="px-5 py-3 text-right font-normal">Per message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
            @foreach ($rates as $rate)
                <tr>
                    <td class="px-5 py-2.5">{{ ucfirst(strtolower($rate->category)) }}</td>
                    <td class="px-3 py-2.5 text-ink-500">{{ $rate->country_code }}</td>
                    <td class="num px-5 py-2.5 text-right">@money((float) $rate->client_final_price, $rate->currency)</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="px-5 py-4 text-xs leading-relaxed text-ink-500">
            Charged when WhatsApp accepts the message. Messages WhatsApp reports as undeliverable are refunded automatically.
        </p>
    </div>
</section>
@endsection
