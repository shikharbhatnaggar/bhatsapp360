<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use App\Services\WhatsAppClient;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WhatsappAccountController extends Controller
{
    public function edit(Request $request)
    {
        $account = $request->user()->tenant->whatsappAccounts()->first();
        $subscribed = null;

        // Only ask Meta if we have something worth asking with.
        if ($account && $account->hasUsableToken() && ! $account->blockedReason()) {
            $result = WhatsAppClient::for($account)->subscribedApps();
            $subscribed = $result['ok'] ? count(Arr::get($result, 'body.data', [])) > 0 : null;
        }

        return view('settings.whatsapp', [
            'account' => $account,
            'subscribed' => $subscribed,
            'verifyToken' => $account?->webhook_verify_token ?? Str::random(24),
        ]);
    }

    /** Subscribes your Meta app to this WABA's webhook events. */
    public function subscribe(Request $request)
    {
        $account = $request->user()->tenant->whatsappAccounts()->firstOrFail();
        $result = WhatsAppClient::for($account)->subscribeApp();

        if (! $result['ok']) {
            ActivityLogger::log('whatsapp_account.subscribe_failed', 'Webhook subscription failed', $account, $result['error'] ?? []);

            return back()->withErrors(['subscribe' => 'Could not subscribe your app to this business account: '
                .WhatsAppClient::describeError($result)]);
        }

        ActivityLogger::log('whatsapp_account.subscribed', 'App subscribed to WABA webhooks', $account, $result['body']);

        return back()->with('status', 'Subscribed. Delivery receipts, replies and template decisions will now be delivered to your callback URL.');
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'waba_id' => ['required', 'string', 'max:64'],
            'phone_number_id' => ['required', 'string', 'max:64'],
            'display_phone_number' => ['nullable', 'string', 'max:32'],
            'business_id' => ['nullable', 'string', 'max:64'],
            'access_token' => ['nullable', 'string'],
            'app_id' => ['nullable', 'string', 'max:64'],
            'app_secret' => ['nullable', 'string', 'max:190'],
            'webhook_verify_token' => ['required', 'string', 'max:190'],
            'graph_version' => ['required', 'string', 'max:12'],
        ]);

        $tenant = $request->user()->tenant;
        $account = $tenant->whatsappAccounts()->first();

        // An empty token field means "keep the stored one".
        if (blank($data['access_token'])) {
            unset($data['access_token']);
        }
        if (blank($data['app_secret'])) {
            unset($data['app_secret']);
        }

        if ($account) {
            $account->update($data);
            $event = 'whatsapp_account.updated';
            $description = 'WhatsApp API settings updated';
        } else {
            $account = $tenant->whatsappAccounts()->create($data + ['is_active' => true]);
            $event = 'whatsapp_account.connected';
            $description = 'WhatsApp number connected';
        }

        ActivityLogger::log($event, $description, $account, [
            'waba_id' => $account->waba_id,
            'phone_number_id' => $account->phone_number_id,
        ]);

        return back()->with('status', 'Settings saved.');
    }

    /**
     * Checks both IDs, not just the sender: templates are created against the
     * WABA, so a wrong WABA ID only shows up at submission time otherwise.
     */
    public function verify(Request $request)
    {
        $account = $request->user()->tenant->whatsappAccounts()->firstOrFail();
        $client = WhatsAppClient::for($account);

        $waba = $client->verifyBusinessAccount();

        if (! $waba['ok']) {
            $hint = $this->explainWabaFailure($client, $account->waba_id, $waba);

            ActivityLogger::log('whatsapp_account.verify_failed', 'WABA check failed', $account, $waba['error'] ?? []);

            return back()->withErrors(['waba' => $hint]);
        }

        $result = $client->verifyNumber();

        if (! $result['ok']) {
            ActivityLogger::log('whatsapp_account.verify_failed', 'Phone number check failed', $account, $result['error'] ?? []);

            return back()->withErrors([
                'connection' => 'The business account is reachable, but the phone number ID is not: '
                    .WhatsAppClient::describeError($result),
            ]);
        }

        $account->update([
            'display_phone_number' => Arr::get($result, 'body.display_phone_number', $account->display_phone_number),
            'quality_rating' => Arr::get($result, 'body.quality_rating'),
            'messaging_limit' => Arr::get($result, 'body.messaging_limit_tier'),
            'is_active' => true,
            'verified_at' => now(),
            'last_verification_response' => $result['body'],
        ]);

        ActivityLogger::log('whatsapp_account.verified', 'Connection test passed', $account, [
            'number' => $result['body'],
            'waba' => $waba['body'],
        ]);

        return back()->with('status', 'Connected. '.Arr::get($result, 'body.verified_name', 'Number verified')
            .' on business account '.Arr::get($waba, 'body.name', $account->waba_id).' is ready to send.');
    }

    /**
     * Error 100 on a WABA lookup has three usual causes. Probe the ID so the
     * message names the actual problem instead of repeating Meta's generic text.
     */
    protected function explainWabaFailure(WhatsAppClient $client, string $wabaId, array $result): string
    {
        $detail = WhatsAppClient::describeError($result);

        if ($kind = $client->identify($wabaId)) {
            return "That ID belongs to a {$kind}, not a WhatsApp Business Account. "
                .'Copy the WABA ID from Business Settings → Accounts → WhatsApp Accounts, '
                ."or from the API Setup panel where it is labelled “WhatsApp Business Account ID”. ({$detail})";
        }

        return 'WhatsApp cannot load business account '.$wabaId.'. Check that the ID is correct, that your '
            .'system user is assigned to this WhatsApp account in Business Settings with full control, and that '
            .'the token carries both whatsapp_business_management and whatsapp_business_messaging. ('.$detail.')';
    }
}
