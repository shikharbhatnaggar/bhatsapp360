@extends('layouts.app')
@section('title', ($customer->exists ? 'Edit contact' : 'Add contact').' — Bhatsapp')
@section('heading', $customer->exists ? 'Edit '.$customer->name : 'Add a contact')

@section('content')
<form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}"
      class="max-w-2xl rounded-xl border border-ink-200 bg-white p-6">
    @csrf
    @if ($customer->exists) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="block text-sm text-ink-700">Full name</label>
            <input id="name" name="name" value="{{ old('name', $customer->name) }}" required
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <div>
            <label for="phone" class="block text-sm text-ink-700">WhatsApp number</label>
            <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required placeholder="919812345678"
                   class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            <p class="mt-1 text-xs text-ink-500">Country code first, digits only.</p>
        </div>
        <div>
            <label for="email" class="block text-sm text-ink-700">Email <span class="text-ink-300">optional</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}"
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <div>
            <label for="type" class="block text-sm text-ink-700">Type</label>
            <select id="type" name="type" class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <option value="lead" @selected(old('type', $customer->type) === 'lead')>Lead</option>
                <option value="customer" @selected(old('type', $customer->type) === 'customer')>Customer</option>
            </select>
        </div>
        <div>
            <label for="country_code" class="block text-sm text-ink-700">Country</label>
            <select id="country_code" name="country_code" class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                @foreach (['IN' => 'India', 'US' => 'United States', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom'] as $code => $label)
                    <option value="{{ $code }}" @selected(old('country_code', $customer->country_code) === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">Decides the per-message rate.</p>
        </div>
        <div>
            <label for="status" class="block text-sm text-ink-700">Status</label>
            <select id="status" name="status" class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                @foreach (['active' => 'Active', 'blocked' => 'Blocked', 'unsubscribed' => 'Unsubscribed'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $customer->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="tags" class="block text-sm text-ink-700">Tags <span class="text-ink-300">comma separated</span></label>
            <input id="tags" name="tags" value="{{ old('tags', implode(', ', $customer->tags ?? [])) }}" placeholder="hyderabad, repeat-buyer"
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <label class="sm:col-span-2 flex items-start gap-2.5 text-sm">
            <input type="checkbox" name="opted_in" value="1" @checked(old('opted_in', $customer->opted_in ?? true))
                   class="mt-0.5 rounded border-ink-300 text-jade-600 focus:ring-jade-600">
            <span>This contact agreed to receive WhatsApp messages from us.
                <span class="block text-xs text-ink-500">Meta requires opt-in before you send marketing templates.</span></span>
        </label>
    </div>

    <div class="mt-6 flex items-center gap-2">
        <button class="rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700">
            {{ $customer->exists ? 'Save changes' : 'Add contact' }}
        </button>
        <a href="{{ route('customers.index') }}" class="rounded-lg border border-ink-200 px-4 py-2.5 text-sm hover:border-ink-300">Cancel</a>

        @if ($customer->exists)
            <button form="delete-contact" class="ml-auto text-sm text-alert-600 underline underline-offset-2">Remove contact</button>
        @endif
    </div>
</form>

@if ($customer->exists)
    <form id="delete-contact" method="POST" action="{{ route('customers.destroy', $customer) }}"
          onsubmit="return confirm('Remove {{ $customer->name }}? Their message history stays in the log.')">
        @csrf @method('DELETE')
    </form>
@endif
@endsection
