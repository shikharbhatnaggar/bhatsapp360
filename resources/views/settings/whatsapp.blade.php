@extends('layouts.app')
@section('title', 'WhatsApp settings — '.config('app.name'))
@section('heading', 'WhatsApp settings')
@section('subheading', 'Point '.config('app.name').' at your WhatsApp Business account so it can send on your behalf.')

@section('content')
@if ($account && ! config('whatsapp.sandbox') && ! $account->hasUsableToken())
    <div class="mb-5 rounded-xl border border-alert-200 bg-alert-50 p-5 text-sm text-alert-700">
        <p class="text-base">This workspace has no working access token</p>
        <p class="mt-1.5 leading-relaxed">
            Sandbox mode is off, so template submissions and sends go to Meta for real — but the saved token is the
            demo placeholder. Meta will answer “Malformed access token”. Paste your system user token below, save,
            then run <span class="text-alert-700">Test connection</span>.
        </p>
        <p class="mt-2 leading-relaxed">
            Not ready for a live number yet? Set <span class="font-mono text-xs">WHATSAPP_SANDBOX=true</span> in
            <span class="font-mono text-xs">.env</span> and run <span class="font-mono text-xs">php artisan config:clear</span>.
        </p>
    </div>
@endif

<div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
    <form method="POST" action="{{ route('settings.whatsapp.save') }}" class="rounded-xl border border-ink-200 bg-white p-6">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="label" class="block text-sm text-ink-700">Name this number</label>
                <input id="label" name="label" value="{{ old('label', $account->label ?? 'Primary number') }}" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div>
                <label for="waba_id" class="block text-sm text-ink-700">WhatsApp Business Account ID</label>
                <input id="waba_id" name="waba_id" value="{{ old('waba_id', $account->waba_id ?? '') }}" required
                       class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">
                    Templates are created against this account. Business Settings → Accounts → WhatsApp Accounts.
                    This is <span class="text-ink-700">not</span> the phone number ID or your business portfolio ID.
                </p>
            </div>

            <div>
                <label for="phone_number_id" class="block text-sm text-ink-700">Phone number ID</label>
                <input id="phone_number_id" name="phone_number_id" value="{{ old('phone_number_id', $account->phone_number_id ?? '') }}" required
                       class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">The sender. Not the phone number itself.</p>
            </div>

            <div>
                <label for="display_phone_number" class="block text-sm text-ink-700">Display number</label>
                <input id="display_phone_number" name="display_phone_number" value="{{ old('display_phone_number', $account->display_phone_number ?? '') }}"
                       placeholder="+91 90000 00000"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div>
                <label for="graph_version" class="block text-sm text-ink-700">Graph API version</label>
                <input id="graph_version" name="graph_version" value="{{ old('graph_version', $account->graph_version ?? config('whatsapp.graph_version')) }}" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div class="sm:col-span-2">
                <label for="access_token" class="block text-sm text-ink-700">System user access token</label>
                <input id="access_token" name="access_token" type="password" autocomplete="off"
                       placeholder="{{ $account?->access_token ? $account->maskedToken() : 'EAAG...' }}"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs {{ $account && ! $account->hasUsableToken() ? 'text-alert-600' : 'text-ink-500' }}">
                    @if ($account && ! $account->hasUsableToken())
                        No real token saved yet — leaving this blank keeps the placeholder.
                    @else
                        Stored encrypted. Leave blank to keep the saved token.
                    @endif
                </p>
            </div>

            <div>
                <label for="app_id" class="block text-sm text-ink-700">Meta app ID</label>
                <input id="app_id" name="app_id" value="{{ old('app_id', $account->app_id ?? '') }}"
                       class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div>
                <label for="app_secret" class="block text-sm text-ink-700">App secret</label>
                <input id="app_secret" name="app_secret" type="password" autocomplete="off" placeholder="{{ $account?->app_secret ? '•••••••••••' : '' }}"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">Used to check the signature on incoming callbacks.</p>
            </div>

            <div class="sm:col-span-2">
                <label for="webhook_verify_token" class="block text-sm text-ink-700">Webhook verify token</label>
                <input id="webhook_verify_token" name="webhook_verify_token" value="{{ old('webhook_verify_token', $account->webhook_verify_token ?? $verifyToken) }}" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-2">
            <button class="rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700">Save settings</button>
            @if ($account)
                <button form="verify-form" class="rounded-lg border border-ink-200 px-4 py-2.5 text-sm hover:border-ink-300">Test connection</button>
            @endif
        </div>
    </form>

    @if ($account)
        <form id="verify-form" method="POST" action="{{ route('settings.whatsapp.verify') }}">@csrf</form>
    @endif

    <aside class="space-y-4">
        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Connection</h2>
            @if ($account?->verified_at)
                <p class="mt-2 flex items-center gap-2 text-sm text-jade-700">
                    <span class="h-2 w-2 rounded-full bg-jade-400"></span>
                    Verified {{ $account->verified_at->diffForHumans() }}
                </p>
                <dl class="mt-3 space-y-1.5 text-sm text-ink-500">
                    <div class="flex justify-between"><dt>Number</dt><dd class="text-ink-900">{{ $account->display_phone_number }}</dd></div>
                    <div class="flex justify-between"><dt>Quality</dt><dd class="text-ink-900">{{ $account->quality_rating ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt>Send limit</dt><dd class="text-ink-900">{{ $account->messaging_limit ?? '—' }}</dd></div>
                </dl>
            @else
                <p class="mt-2 text-sm text-ink-500">Not verified yet. Save your credentials, then run a connection test.</p>
            @endif
        </div>

        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Callback URL</h2>
            <p class="mt-2 text-sm text-ink-500">Paste this into the Webhooks section of your Meta app, subscribe to <span class="text-ink-900">messages</span> and <span class="text-ink-900">message_template_status_update</span>.</p>
            <code class="mt-3 block break-all rounded-lg bg-ink-50 px-3 py-2 text-xs text-ink-700">{{ url('/webhooks/whatsapp/'.$tenant->id) }}</code>

            @if ($account)
                @php $subscribed = $subscribed ?? null; @endphp
                <div class="mt-4 border-t border-ink-100 pt-4">
                    <h3 class="text-sm text-ink-700">App subscription</h3>

                    @if ($subscribed === true)
                        <p class="mt-1.5 flex items-center gap-2 text-sm text-jade-700">
                            <span class="h-2 w-2 rounded-full bg-jade-400"></span>
                            Your app is subscribed to this business account.
                        </p>
                    @elseif ($subscribed === false)
                        <p class="mt-1.5 text-sm text-alert-700">
                            No app is subscribed to this business account, so WhatsApp has nowhere to send events.
                            Setting webhook fields in the App dashboard is not enough on its own.
                        </p>
                        <form method="POST" action="{{ route('settings.whatsapp.subscribe') }}" class="mt-3">
                            @csrf
                            <button class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">
                                Subscribe this app
                            </button>
                        </form>
                    @else
                        <p class="mt-1.5 text-sm text-ink-500">
                            Save working credentials to check whether your app is subscribed.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if (config('whatsapp.sandbox'))
            <div class="rounded-xl border border-signal-200 bg-signal-50 p-5 text-sm text-signal-700">
                <h2 class="text-base text-signal-700">Sandbox is on</h2>
                <p class="mt-2 leading-relaxed">Graph calls are simulated, so templates approve themselves after {{ config('whatsapp.sandbox_approval_delay') }} seconds and sends return fake message IDs. Set <span class="font-mono text-xs">WHATSAPP_SANDBOX=false</span> to go live.</p>
            </div>
        @endif
    </aside>
</div>
@endsection
