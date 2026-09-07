<?php

namespace App\Http\Controllers;

use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Models\WhatsappRate;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        return view('wallet.index', [
            'balance' => (float) $tenant->wallet_balance,
            'transactions' => WalletTransaction::with('user')->latest()->paginate(25),
            'openTopups' => WalletTopup::whereIn('status', ['pending', 'submitted'])->latest()->get(),
            'rates' => WhatsappRate::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))
                ->orderBy('country_code')->orderBy('category')->get()
                // Show the tenant's own rate where one exists, else the default.
                ->groupBy(fn ($rate) => $rate->country_code.'|'.$rate->category)
                ->map(fn ($group) => $group->sortByDesc('tenant_id')->first())
                ->values(),
            'spentThisMonth' => (float) WalletTransaction::where('type', 'debit')
                ->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
        ]);
    }

    /** Opens a top-up: generates the reference the customer puts in the UPI note. */
    public function store(Request $request)
    {
        $minimum = config('wallet.minimum_topup');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.$minimum, 'max:500000'],
        ], [
            'amount.min' => 'The smallest top-up is '.app(\App\Services\PricingService::class)
                ->format($minimum, config('wallet.currency')).'.',
        ]);

        $topup = WalletTopup::create([
            'amount' => round((float) $data['amount'], 2),
            'currency' => config('wallet.currency'),
            'status' => 'pending',
            'requested_by' => $request->user()->id,
        ]);

        ActivityLogger::log('wallet.topup_started', 'Top-up '.$topup->reference.' started for '.$topup->amount, $topup);

        return redirect()->route('wallet.topup', $topup);
    }

    public function show(WalletTopup $topup)
    {
        abort_unless($topup->isOpen(), 404);

        return view('wallet.topup', compact('topup'));
    }

    /** Customer confirms they have paid, and gives us the UTR to match against. */
    public function submit(Request $request, WalletTopup $topup)
    {
        abort_unless($topup->isOpen(), 404);

        $data = $request->validate([
            'upi_reference' => ['required', 'string', 'min:6', 'max:40'],
            'payer_note' => ['nullable', 'string', 'max:190'],
        ], [
            'upi_reference.required' => 'Enter the UPI transaction ID (UTR) from your payment app.',
        ]);

        $topup->update($data + ['status' => 'submitted', 'submitted_at' => now()]);

        ActivityLogger::log(
            'wallet.topup_submitted',
            'Top-up '.$topup->reference.' submitted with UTR '.$topup->upi_reference,
            $topup,
            ['amount' => (float) $topup->amount],
        );

        return redirect()->route('wallet.index')
            ->with('status', 'Payment noted. Your balance updates once we confirm it against our bank statement.');
    }
}
