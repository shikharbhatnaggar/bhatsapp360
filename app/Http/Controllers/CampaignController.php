<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Services\PricingService;
use App\Services\TemplateBuilder;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function __construct(
        protected TemplateBuilder $builder,
        protected PricingService $pricing,
    ) {}

    public function index()
    {
        $campaigns = Campaign::with('template')
            ->withCount([
                'messages as delivered_count' => fn ($q) => $q->whereIn('status', ['delivered', 'read']),
                'messages as failed_count' => fn ($q) => $q->where('status', 'failed'),
            ])
            ->latest()->paginate(15);

        return view('campaigns.index', compact('campaigns'));
    }

    /** Step 1 — pick a template and the recipients. */
    public function create(Request $request)
    {
        $templates = MessageTemplate::where('status', 'APPROVED')->orderBy('name')->get();

        $customers = Customer::query()
            ->where('status', 'active')
            ->when($request->string('q')->toString(), fn ($q, $t) => $q->where(
                fn ($sub) => $sub->where('name', 'like', "%{$t}%")->orWhere('phone', 'like', "%{$t}%")
            ))
            ->when($request->string('type')->toString(), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('name')->get();

        // Indicative unit price per category so the picker can show a running total.
        $rates = collect(array_keys(config('whatsapp.categories')))
            ->mapWithKeys(fn ($category) => [
                $category => $this->pricing->rate($request->user()->tenant, $category, $request->user()->tenant->country_code)['price'],
            ]);

        return view('campaigns.create', [
            'templates' => $templates,
            'customers' => $customers,
            'rates' => $rates,
            'selectedTemplate' => $templates->firstWhere('id', $request->integer('template_id')),
        ]);
    }

    /** Step 2 — preview the exact message and what the send will cost. */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'message_template_id' => ['required', 'exists:message_templates,id'],
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer'],
        ], [
            'customer_ids.required' => 'Select at least one recipient.',
        ]);

        $template = MessageTemplate::findOrFail($data['message_template_id']);
        abort_unless($template->isSendable(), 422, 'Only approved templates can be sent.');

        $recipients = Customer::whereIn('id', $data['customer_ids'])->where('status', 'active')->get();
        $quote = $this->pricing->quote($request->user()->tenant, $template->category, $recipients);

        return view('campaigns.preview', [
            'name' => $data['name'],
            'template' => $template,
            'recipients' => $recipients,
            'quote' => $quote,
            'sample' => $recipients->first(),
            'sampleText' => $this->builder->renderText($template, $recipients->first()),
            'samplePayload' => $recipients->first()
                ? $this->builder->messagePayload($template, $recipients->first())
                : [],
        ]);
    }

    /** Step 3 — confirm: create the campaign, queue one message per recipient. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'message_template_id' => ['required', 'exists:message_templates,id'],
            'customer_ids' => ['required', 'array', 'min:1'],
        ]);

        $tenant = $request->user()->tenant;
        $account = $tenant->whatsappAccount;

        if (! $account) {
            return back()->with('error', 'Connect a WhatsApp number before sending.');
        }

        $template = MessageTemplate::findOrFail($data['message_template_id']);
        abort_unless($template->isSendable(), 422, 'Only approved templates can be sent.');

        $recipients = Customer::whereIn('id', $data['customer_ids'])->where('status', 'active')->get();
        $quote = $this->pricing->quote($tenant, $template->category, $recipients);

        $campaign = DB::transaction(function () use ($data, $template, $recipients, $quote, $account, $request) {
            $campaign = Campaign::create([
                'message_template_id' => $template->id,
                'whatsapp_account_id' => $account->id,
                'name' => $data['name'],
                'status' => 'queued',
                'recipients_count' => $recipients->count(),
                'unit_price' => $quote['unit_price'],
                'estimated_cost' => $quote['total'],
                'currency' => $quote['currency'],
                'variable_values' => $template->variable_map,
                'created_by' => $request->user()->id,
            ]);

            foreach ($recipients as $customer) {
                $rate = $this->pricing->rate($request->user()->tenant, $template->category, $customer->country_code);

                Message::create([
                    'campaign_id' => $campaign->id,
                    'customer_id' => $customer->id,
                    'message_template_id' => $template->id,
                    'whatsapp_account_id' => $account->id,
                    'direction' => 'outbound',
                    'type' => 'template',
                    'pricing_category' => $template->category,
                    'status' => 'queued',
                    'body_preview' => $this->builder->renderText($template, $customer),
                    'price' => $rate['price'],
                    'currency' => $rate['currency'],
                ]);
            }

            return $campaign;
        });

        ActivityLogger::log(
            'campaign.created',
            "Campaign “{$campaign->name}” queued for {$campaign->recipients_count} recipients",
            $campaign,
            ['template' => $template->name, 'estimated_cost' => $quote['total'], 'currency' => $quote['currency']],
        );

        SendCampaignJob::dispatch($campaign->id);

        return redirect()->route('campaigns.show', $campaign)
            ->with('status', 'Sending to '.$campaign->recipients_count.' recipients. Delivery updates appear below as WhatsApp confirms them.');
    }

    /** Per-recipient delivery tracking. */
    public function show(Request $request, Campaign $campaign)
    {
        $messages = $campaign->messages()
            ->with(['customer', 'statusEvents'])
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('campaigns.show', [
            'campaign' => $campaign->load('template', 'creator'),
            'messages' => $messages,
            'counts' => $campaign->counts(),
        ]);
    }
}
