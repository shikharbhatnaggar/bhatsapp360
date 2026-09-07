@extends('layouts.app')
@section('title', 'Top-up approvals — Bhatsapp')
@section('heading', 'Top-up approvals')
@section('subheading', 'Match each UTR against your bank statement before approving. Approval credits the wallet immediately.')

@section('content')
<div class="mb-4 flex flex-wrap gap-2 text-sm">
    @foreach (['submitted' => 'Awaiting review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'pending' => 'Not yet paid'] as $value => $label)
        <a href="{{ route('admin.topups', ['status' => $value]) }}"
           class="rounded-lg border px-3 py-1.5 {{ request('status', 'submitted') === $value ? 'border-jade-600 bg-jade-50 text-jade-700' : 'border-ink-200 bg-white' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Reference</th>
                <th class="px-3 py-3 font-normal">Workspace</th>
                <th class="px-3 py-3 font-normal">UTR</th>
                <th class="px-3 py-3 text-right font-normal">Amount</th>
                <th class="px-5 py-3 text-right font-normal">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        @forelse ($topups as $topup)
            <tr class="align-top hover:bg-ink-50">
                <td class="px-5 py-3">
                    <span class="num">{{ $topup->reference }}</span>
                    <p class="text-xs text-ink-500">{{ $topup->submitted_at?->format('d M, H:i') ?? $topup->created_at->format('d M, H:i') }}</p>
                </td>
                <td class="px-3 py-3">
                    {{ $tenants[$topup->tenant_id] ?? 'Workspace #'.$topup->tenant_id }}
                    <p class="text-xs text-ink-500">{{ $topup->requester?->name }}</p>
                </td>
                <td class="num px-3 py-3">
                    {{ $topup->upi_reference ?? '—' }}
                    @if ($topup->payer_note)<p class="text-xs text-ink-500">{{ $topup->payer_note }}</p>@endif
                </td>
                <td class="num px-3 py-3 text-right">@money((float) $topup->amount, $topup->currency)</td>
                <td class="px-5 py-3 text-right">
                    @if ($topup->isOpen())
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('admin.topups.approve', $topup) }}"
                                  onsubmit="return confirm('Credit {{ $topup->amount }} to this workspace?')">
                                @csrf
                                <button class="rounded-lg bg-jade-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-jade-700">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.topups.reject', $topup) }}"
                                  onsubmit="return (this.rejection_reason.value = prompt('Reason for rejecting?') || '') !== ''">
                                @csrf
                                <input type="hidden" name="rejection_reason">
                                <button class="rounded-lg border border-ink-200 px-3 py-1.5 text-xs hover:border-ink-300">Reject</button>
                            </form>
                        </div>
                    @else
                        <span class="text-ink-500">{{ ucfirst($topup->status) }}</span>
                        @if ($topup->reviewer)<p class="text-xs text-ink-500">{{ $topup->reviewer->name }}</p>@endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-ink-500">Nothing here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $topups->links() }}</div>
@endsection
