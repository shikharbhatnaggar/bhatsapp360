<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageStatusEvent;
use App\Models\MessageTemplate;
use App\Models\PricingRate;
use App\Models\Tenant;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Support\ActivityLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * One worked-through workspace: a connected number, three templates in
 * different review states, contacts, a completed send with receipts, and
 * a couple of inbound replies.
 */
class DemoWorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'pakapak-foods'],
            [
                'name' => 'PakaPak Foods',
                'contact_email' => 'ops@pakapak.test',
                'country_code' => 'IN',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
            ],
        );

        User::updateOrCreate(
            ['email' => 'owner@pakapak.test'],
            ['tenant_id' => $tenant->id, 'name' => 'Aarti Rao', 'password' => 'password', 'role' => 'owner'],
        );

        foreach (PricingRate::whereNull('tenant_id')->where('country_code', 'IN')->get() as $rate) {
            PricingRate::updateOrCreate(
                ['tenant_id' => $tenant->id, 'country_code' => 'IN', 'category' => $rate->category],
                ['price' => $rate->price, 'currency' => $rate->currency],
            );
        }

        $account = WhatsappAccount::updateOrCreate(
            ['tenant_id' => $tenant->id, 'phone_number_id' => '109876543210987'],
            [
                'label' => 'PakaPak main line',
                'waba_id' => '204060801020304',
                'display_phone_number' => '+91 90000 12345',
                'access_token' => 'EAAG-sandbox-token-replace-me',
                'webhook_verify_token' => Str::random(24),
                'graph_version' => config('whatsapp.graph_version'),
                'quality_rating' => 'GREEN',
                'messaging_limit' => 'TIER_1K',
                'is_active' => true,
                'verified_at' => now()->subDays(9),
            ],
        );

        // ---------------------------------------------------------- contacts
        $contacts = [
            ['Priya Nair', '919812345001', 'customer', ['hyderabad', 'repeat-buyer']],
            ['Rahul Menon', '919812345002', 'customer', ['bengaluru']],
            ['Sneha Kulkarni', '919812345003', 'lead', ['instagram']],
            ['Imran Sheikh', '919812345004', 'customer', ['hyderabad']],
            ['Divya Reddy', '919812345005', 'lead', ['warangal', 'wholesale']],
            ['Karthik Iyer', '919812345006', 'customer', ['chennai']],
            ['Meera Joshi', '919812345007', 'lead', ['pune']],
            ['Anil Kumar', '919812345008', 'customer', ['hyderabad', 'wholesale']],
        ];

        $customers = collect($contacts)->map(fn ($row) => Customer::updateOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $row[1]],
            [
                'name' => $row[0],
                'email' => Str::slug(explode(' ', $row[0])[0]).'@example.com',
                'type' => $row[2],
                'country_code' => 'IN',
                'tags' => $row[3],
                'opted_in' => true,
                'status' => 'active',
            ],
        ));

        // --------------------------------------------------------- templates
        $approved = MessageTemplate::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'diwali_masala_offer', 'language' => 'en_US'],
            [
                'whatsapp_account_id' => $account->id,
                'category' => 'MARKETING',
                'status' => 'APPROVED',
                'whatsapp_template_id' => '731245789012345',
                'components' => [
                    ['type' => 'HEADER', 'format' => 'IMAGE', 'example' => ['header_handle' => ['https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=800']]],
                    [
                        'type' => 'BODY',
                        'text' => "Hi {{1}}, our Diwali masala box is back.\n\n*20% off* until Sunday, and free delivery above ₹799.",
                        'example' => ['body_text' => [['Priya']]],
                    ],
                    ['type' => 'FOOTER', 'text' => 'Reply STOP to opt out'],
                    ['type' => 'BUTTONS', 'buttons' => [
                        ['type' => 'URL', 'text' => 'Shop the box', 'url' => 'https://pakapak.example.com/diwali'],
                        ['type' => 'QUICK_REPLY', 'text' => 'Send me the menu'],
                    ]],
                ],
                'variable_map' => ['body' => [1 => 'customer.first_name']],
                'version' => 2,
                'submitted_at' => now()->subDays(8),
                'approved_at' => now()->subDays(8)->addHours(2),
                'last_synced_at' => now()->subDay(),
            ],
        );

        $carousel = MessageTemplate::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'masala_carousel_launch', 'language' => 'en_US'],
            [
                'whatsapp_account_id' => $account->id,
                'category' => 'MARKETING',
                'sub_category' => 'media_card_carousel',
                'status' => 'PENDING',
                'whatsapp_template_id' => '731245789054321',
                'components' => [
                    ['type' => 'BODY', 'text' => 'Hi {{1}}, three new blends just landed. Tap through and pick your favourite.', 'example' => ['body_text' => [['Priya']]]],
                    ['type' => 'CAROUSEL', 'cards' => [
                        ['components' => [
                            ['type' => 'HEADER', 'format' => 'IMAGE', 'example' => ['header_handle' => ['https://images.unsplash.com/photo-1509358271058-acd22cc93898?w=600']]],
                            ['type' => 'BODY', 'text' => 'Garam masala, 200g — ₹249'],
                            ['type' => 'BUTTONS', 'buttons' => [
                                ['type' => 'URL', 'text' => 'Buy now', 'url' => 'https://pakapak.example.com/garam'],
                                ['type' => 'QUICK_REPLY', 'text' => 'Tell me more'],
                            ]],
                        ]],
                        ['components' => [
                            ['type' => 'HEADER', 'format' => 'IMAGE', 'example' => ['header_handle' => ['https://images.unsplash.com/photo-1532336414038-cf19250c5757?w=600']]],
                            ['type' => 'BODY', 'text' => 'Biryani masala, 200g — ₹279'],
                            ['type' => 'BUTTONS', 'buttons' => [
                                ['type' => 'URL', 'text' => 'Buy now', 'url' => 'https://pakapak.example.com/biryani'],
                                ['type' => 'QUICK_REPLY', 'text' => 'Tell me more'],
                            ]],
                        ]],
                        ['components' => [
                            ['type' => 'HEADER', 'format' => 'IMAGE', 'example' => ['header_handle' => ['https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=600']]],
                            ['type' => 'BODY', 'text' => 'Chai masala, 100g — ₹149'],
                            ['type' => 'BUTTONS', 'buttons' => [
                                ['type' => 'URL', 'text' => 'Buy now', 'url' => 'https://pakapak.example.com/chai'],
                                ['type' => 'QUICK_REPLY', 'text' => 'Tell me more'],
                            ]],
                        ]],
                    ]],
                ],
                'variable_map' => ['body' => [1 => 'customer.first_name']],
                'version' => 1,
                'submitted_at' => now()->subMinutes(20),
            ],
        );

        MessageTemplate::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'order_shipped_update', 'language' => 'en_US'],
            [
                'whatsapp_account_id' => $account->id,
                'category' => 'UTILITY',
                'status' => 'APPROVED',
                'whatsapp_template_id' => '731245789099887',
                'components' => [
                    ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Your order is on the way'],
                    [
                        'type' => 'BODY',
                        'text' => "Hi {{1}}, order {{2}} left our kitchen today and arrives in 2–3 days.",
                        'example' => ['body_text' => [['Priya', 'PK-10482']]],
                    ],
                    ['type' => 'BUTTONS', 'buttons' => [['type' => 'URL', 'text' => 'Track order', 'url' => 'https://pakapak.example.com/track']]],
                ],
                'variable_map' => ['body' => [1 => 'customer.first_name', 2 => 'static:PK-10482']],
                'version' => 1,
                'submitted_at' => now()->subDays(20),
                'approved_at' => now()->subDays(20)->addHour(),
            ],
        );

        TemplateVersion::firstOrCreate(
            ['message_template_id' => $approved->id, 'version' => 1],
            ['action' => 'created', 'status' => 'APPROVED', 'components' => $approved->components, 'reviewed_at' => now()->subDays(8)],
        );
        TemplateVersion::firstOrCreate(
            ['message_template_id' => $approved->id, 'version' => 2],
            ['action' => 'updated', 'status' => 'APPROVED', 'components' => $approved->components, 'reviewed_at' => now()->subDays(3)],
        );
        TemplateVersion::firstOrCreate(
            ['message_template_id' => $carousel->id, 'version' => 1],
            ['action' => 'created', 'status' => 'PENDING', 'components' => $carousel->components],
        );

        // ----------------------------------------------------------- a send
        $campaign = Campaign::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Diwali box — Hyderabad list'],
            [
                'message_template_id' => $approved->id,
                'whatsapp_account_id' => $account->id,
                'status' => 'completed',
                'recipients_count' => $customers->count(),
                'unit_price' => 0.7846,
                'estimated_cost' => round(0.7846 * $customers->count(), 4),
                'actual_cost' => round(0.7846 * ($customers->count() - 1), 4),
                'currency' => 'INR',
                'started_at' => now()->subDays(2),
                'completed_at' => now()->subDays(2)->addMinutes(3),
            ],
        );

        if ($campaign->messages()->count() === 0) {
            foreach ($customers as $index => $customer) {
                $status = match (true) {
                    $index === 4 => 'failed',
                    $index < 3 => 'read',
                    default => 'delivered',
                };

                $message = Message::create([
                    'tenant_id' => $tenant->id,
                    'campaign_id' => $campaign->id,
                    'customer_id' => $customer->id,
                    'message_template_id' => $approved->id,
                    'whatsapp_account_id' => $account->id,
                    'direction' => 'outbound',
                    'wamid' => 'wamid.DEMO'.Str::upper(Str::random(18)),
                    'type' => 'template',
                    'pricing_category' => 'MARKETING',
                    'status' => $status,
                    'body_preview' => "Hi {$customer->firstName()}, our Diwali masala box is back. 20% off until Sunday.",
                    'price' => $status === 'failed' ? 0 : 0.7846,
                    'currency' => 'INR',
                    'sent_at' => now()->subDays(2),
                    'delivered_at' => $status === 'failed' ? null : now()->subDays(2)->addSeconds(12),
                    'read_at' => $status === 'read' ? now()->subDays(2)->addMinutes(9) : null,
                    'failed_at' => $status === 'failed' ? now()->subDays(2)->addSeconds(6) : null,
                    'error' => $status === 'failed'
                        ? ['code' => 131026, 'title' => 'Message undeliverable', 'message' => 'Recipient phone number not on WhatsApp']
                        : null,
                    'created_at' => now()->subDays(2),
                ]);

                $trail = $status === 'failed' ? ['sent', 'failed'] : ($status === 'read' ? ['sent', 'delivered', 'read'] : ['sent', 'delivered']);

                foreach ($trail as $step => $event) {
                    MessageStatusEvent::create([
                        'message_id' => $message->id,
                        'status' => $event,
                        'source' => 'webhook',
                        'raw' => ['seeded' => true],
                        'occurred_at' => now()->subDays(2)->addSeconds($step * 6),
                    ]);
                }
            }
        }

        // -------------------------------------------------------- inbound
        $replies = [
            [$customers[0], 'Yes please, send me the menu!'],
            [$customers[3], 'Do you deliver to Kukatpally?'],
        ];

        foreach ($replies as [$customer, $body]) {
            if (Message::where('customer_id', $customer->id)->where('direction', 'inbound')->exists()) {
                continue;
            }

            $message = Message::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'whatsapp_account_id' => $account->id,
                'direction' => 'inbound',
                'wamid' => 'wamid.IN'.Str::upper(Str::random(18)),
                'type' => 'text',
                'pricing_category' => 'SERVICE',
                'status' => 'received',
                'body_preview' => $body,
                'payload' => ['text' => ['body' => $body]],
                'created_at' => now()->subHours(3),
            ]);

            MessageStatusEvent::create([
                'message_id' => $message->id,
                'status' => 'received',
                'source' => 'webhook',
                'occurred_at' => now()->subHours(3),
            ]);

            $customer->forceFill(['last_inbound_at' => now()->subHours(3)])->save();
        }

        ActivityLogger::log('tenant.seeded', 'Demo workspace loaded', $tenant, [], $tenant->id);
    }
}
