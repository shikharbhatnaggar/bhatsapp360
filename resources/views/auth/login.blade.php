@extends('layouts.guest')
@section('title', 'Sign in — '.config('app.name'))

@section('form')
    <h1 class="text-2xl tracking-tight">Sign in</h1>
    <p class="mt-1.5 text-sm text-ink-500">Pick up where your workspace left off.</p>

    @if ($errors->any())
        <div class="mt-5 rounded-lg border border-alert-200 bg-alert-50 px-3.5 py-2.5 text-sm text-alert-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm text-ink-700">Work email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <div>
            <label for="password" class="block text-sm text-ink-700">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-500">
            <input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-jade-600 focus:ring-jade-600">
            Keep me signed in
        </label>
        <button class="w-full rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700 focus:outline-none focus:ring-2 focus:ring-jade-600 focus:ring-offset-2">
            Sign in
        </button>
    </form>

    <p class="mt-6 text-sm text-ink-500">
        New here? <a href="{{ route('register') }}" class="text-jade-700 underline underline-offset-2">Create a workspace</a>
    </p>
@endsection
