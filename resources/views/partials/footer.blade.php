<footer class="mt-10 border-t border-ink-200 px-5 py-6 lg:px-10">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 text-xs text-ink-500">
        <p>&copy; {{ now()->year }} {{ config('app.company', config('app.name')) }}. All rights reserved.</p>
        <nav class="flex gap-4">
            <a href="{{ route('privacy') }}" class="hover:text-ink-700 hover:underline underline-offset-2">Privacy</a>
            <a href="{{ route('terms') }}" class="hover:text-ink-700 hover:underline underline-offset-2">Terms</a>
        </nav>
    </div>
</footer>
