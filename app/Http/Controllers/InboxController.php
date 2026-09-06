<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Message;
use App\Services\MessageDispatcher;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        // One row per customer who has ever written in, newest reply first.
        $threads = Customer::query()
            ->whereNotNull('last_inbound_at')
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('direction', 'inbound')->where('status', 'received')])
            ->orderByDesc('last_inbound_at')
            ->limit(60)
            ->get();

        $active = $request->integer('customer')
            ? $threads->firstWhere('id', $request->integer('customer')) ?? Customer::find($request->integer('customer'))
            : $threads->first();

        $conversation = $active
            ? Message::where('customer_id', $active->id)->orderBy('created_at')->limit(100)->get()
            : collect();

        return view('inbox.index', [
            'threads' => $threads,
            'active' => $active,
            'conversation' => $conversation,
        ]);
    }

    /** Free-form reply, only valid inside the 24-hour service window. */
    public function reply(Request $request, Customer $customer, MessageDispatcher $dispatcher)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:4096'],
        ]);

        $account = $request->user()->tenant->whatsappAccount;

        if (! $account) {
            return back()->with('error', 'Connect a WhatsApp number before replying.');
        }

        if (! $customer->serviceWindowOpen()) {
            return back()->with('error', 'The 24-hour reply window has closed. Send an approved template instead.');
        }

        $dispatcher->sendSessionText($customer, $data['body'], $account);

        return back()->with('status', 'Reply sent.');
    }
}
