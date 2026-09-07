<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\WalletTopup;
use App\Services\WalletService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Operator-side review of manual UPI top-ups. Nothing credits a wallet until
 * a human has matched the UTR against the bank statement.
 */
class AdminTopupController extends Controller
{
    protected function authorise(Request $request): void
    {
        abort_unless($request->user()->is_platform_admin, 403, 'Platform administrators only.');
    }

    public function index(Request $request)
    {
        $this->authorise($request);

        return view('admin.topups', [
            'topups' => WalletTopup::withoutGlobalScope('tenant')
                ->with(['requester', 'reviewer'])
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->where('status', 'submitted'))
                ->latest()->paginate(30)->withQueryString(),
            'tenants' => Tenant::pluck('name', 'id'),
        ]);
    }

    public function approve(Request $request, WalletTopup $topup, WalletService $wallet)
    {
        $this->authorise($request);
        abort_unless($topup->isOpen(), 422, 'This top-up has already been reviewed.');

        $tenant = Tenant::findOrFail($topup->tenant_id);

        $transaction = $wallet->credit(
            $tenant,
            (float) $topup->amount,
            'topup',
            'Wallet top-up '.$topup->reference.' via UPI',
            $topup,
            ['upi_reference' => $topup->upi_reference],
            $request->user()->id,
        );

        $topup->update([
            'status' => 'approved',
            'wallet_transaction_id' => $transaction->id,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLogger::log(
            'wallet.topup_approved',
            'Top-up '.$topup->reference.' approved, '.$topup->amount.' credited',
            $topup,
            ['balance_after' => (float) $transaction->balance_after],
            $tenant->id,
        );

        return back()->with('status', $topup->reference.' approved. Balance is now '
            .app(\App\Services\PricingService::class)->format((float) $transaction->balance_after, $tenant->currency).'.');
    }

    public function reject(Request $request, WalletTopup $topup)
    {
        $this->authorise($request);
        abort_unless($topup->isOpen(), 422, 'This top-up has already been reviewed.');

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:190']]);

        $topup->update($data + [
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLogger::log(
            'wallet.topup_rejected',
            'Top-up '.$topup->reference.' rejected: '.$data['rejection_reason'],
            $topup,
            [],
            $topup->tenant_id,
        );

        return back()->with('status', $topup->reference.' rejected.');
    }
}
