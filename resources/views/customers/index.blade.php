@extends('layouts.app')
@section('title', 'Contacts — Bhatsapp')
@section('heading', 'Contacts')
@section('subheading', 'Customers and leads you can message.')

@section('actions')
    <a href="{{ route('customers.create') }}" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">Add contact</a>
@endsection

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Search name, phone or email"
           class="w-64 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    <select name="type" class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
        <option value="">Everyone</option>
        <option value="customer" @selected(request('type') === 'customer')>Customers</option>
        <option value="lead" @selected(request('type') === 'lead')>Leads</option>
    </select>
    <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Search</button>
</form>

<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Name</th>
                <th class="px-3 py-3 font-normal">WhatsApp number</th>
                <th class="px-3 py-3 font-normal">Type</th>
                <th class="px-3 py-3 font-normal">Reply window</th>
                <th class="px-3 py-3 font-normal">Added</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        @forelse ($customers as $customer)
            <tr class="hover:bg-ink-50">
                <td class="px-5 py-3">
                    {{ $customer->name }}
                    @if ($customer->email)<p class="text-xs text-ink-500">{{ $customer->email }}</p>@endif
                </td>
                <td class="num px-3 py-3">+{{ $customer->phone }}</td>
                <td class="px-3 py-3 text-ink-500">{{ ucfirst($customer->type) }}</td>
                <td class="px-3 py-3">
                    @if ($customer->serviceWindowOpen())
                        <span class="text-jade-700">Open · closes {{ $customer->last_inbound_at->addDay()->diffForHumans() }}</span>
                    @else
                        <span class="text-ink-300">Closed</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-ink-500">{{ $customer->created_at->format('d M Y') }}</td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('customers.edit', $customer) }}" class="text-jade-700 underline underline-offset-2">Edit</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-5 py-10 text-center text-ink-500">
                    No contacts yet. <a href="{{ route('customers.create') }}" class="text-jade-700 underline underline-offset-2">Add your first one</a>, or let inbound replies create them for you.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $customers->links() }}</div>
@endsection
