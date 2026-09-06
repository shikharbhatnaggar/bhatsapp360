@extends('layouts.guest')
@section('title', 'Create a workspace — Bhatsapp')

@section('form')
    <h1 class="text-2xl tracking-tight">Create your workspace</h1>
    <p class="mt-1.5 text-sm text-ink-500">One workspace holds your number, contacts, templates and sends.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="workspace" class="block text-sm text-ink-700">Business name</label>
            <input id="workspace" name="workspace" value="{{ old('workspace') }}" required autofocus
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            @error('workspace')<p class="mt-1 text-xs text-alert-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="name" class="block text-sm text-ink-700">Your name</label>
            <input id="name" name="name" value="{{ old('name') }}" required
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            @error('name')<p class="mt-1 text-xs text-alert-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="block text-sm text-ink-700">Work email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            @error('email')<p class="mt-1 text-xs text-alert-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="password" class="block text-sm text-ink-700">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm text-ink-700">Confirm</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>
            @error('password')<p class="col-span-2 -mt-1 text-xs text-alert-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="country_code" class="block text-sm text-ink-700">Billing country</label>
            <select id="country_code" name="country_code" class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <option value="IN" @selected(old('country_code', 'IN') === 'IN')>India (INR)</option>
                <option value="US" @selected(old('country_code') === 'US')>United States (USD)</option>
                <option value="AE" @selected(old('country_code') === 'AE')>United Arab Emirates (USD)</option>
                <option value="GB" @selected(old('country_code') === 'GB')>United Kingdom (USD)</option>
            </select>
            <p class="mt-1.5 text-xs text-ink-500">Sets the rate card used to price your sends.</p>
        </div>
        <button class="w-full rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700 focus:outline-none focus:ring-2 focus:ring-jade-600 focus:ring-offset-2">
            Create workspace
        </button>
    </form>

    <p class="mt-6 text-sm text-ink-500">
        Already set up? <a href="{{ route('login') }}" class="text-jade-700 underline underline-offset-2">Sign in</a>
    </p>
@endsection
