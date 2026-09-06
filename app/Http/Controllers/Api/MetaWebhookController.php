<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Import your new WhatsApp Service
use App\Services\WhatsAppService; 

class MetaWebhookController extends Controller
{
    protected WhatsAppService $whatsAppService;

    // Use dependency injection to load the service automatically
    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Handle the incoming Meta Webhook requests.
     */
    public function handle(Request $request)
    {
        // 1. Handle Meta's GET Verification Request
        if ($request->isMethod('get')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            $expectedToken = env('META_VERIFY_TOKEN');

            if ($mode === 'subscribe' && $token === $expectedToken) {
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response('Forbidden: Token mismatch', 403);
        }

        // 2. Handle Meta's POST Event Data Payload (Actual Webhook data)
        if ($request->isMethod('post')) {
            $payload = $request->all();
            
            Log::info('Meta Webhook Event Received:', $payload);
            
            // AUTOMATIC REPLY LOGIC FOR INCOMING WHATSAPP MESSAGES:
            try {
                if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
                    $messageData = $payload['entry'][0]['changes'][0]['value']['messages'][0];
                    
                    $fromMobileNumber = $messageData['from']; // The user's WhatsApp number
                    $messageType = $messageData['type'];

                    if ($messageType === 'text') {
                        $userText = $messageData['text']['body'];

                        // Example: Auto-respond if they type "hi"
                        if (strtolower(trim($userText)) === 'hi') {
                            $this->whatsAppService->sendText($fromMobileNumber, 'Hello! Welcome to Birehrua360. How can we help you today?');
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error parsing incoming WhatsApp Webhook payload: ' . $e->getMessage());
            }
            
            return response('EVENT_RECEIVED', 200);
        }

        return response('Method Not Allowed', 405);
    }
}
