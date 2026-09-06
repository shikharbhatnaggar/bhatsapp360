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

        return view('settings.whatsapp', [
            'account' => $account,
            'verifyToken' => $account?->webhook_verify_token ?? Str::random(24),
        ]);
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

    /** Calls GET /{phone_number_id} and stores what comes back. */
    public function verify(Request $request)
    {
        $account = $request->user()->tenant->whatsappAccounts()->firstOrFail();
        $result = WhatsAppClient::for($account)->verifyNumber();

        if (! $result['ok']) {
            ActivityLogger::log('whatsapp_account.verify_failed', 'Connection test failed', $account, $result['error'] ?? []);

            return back()->withErrors([
                'connection' => Arr::get($result, 'error.message', 'WhatsApp did not accept these credentials.'),
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

        ActivityLogger::log('whatsapp_account.verified', 'Connection test passed', $account, $result['body']);

        return back()->with('status', 'Connected. '.Arr::get($result, 'body.verified_name', 'Number verified').' is ready to send.');
    }
}
