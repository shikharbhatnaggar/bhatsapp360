# Bhatsapp

A WhatsApp Business Platform facilitator console. Each tenant connects their own
WhatsApp Business Account, builds templates, gets them approved by Meta, sends to
selected customers with the cost shown up front, and tracks every delivery receipt
and reply in one place.

Laravel 12 · MySQL 8 · Blade · Alpine.js · Tailwind

---

## Getting it running

```bash
cp .env.example .env
composer install
php artisan key:generate

# create the schema and load a worked-through demo workspace
mysql -u root -e "CREATE DATABASE bhatsapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed

php artisan serve            # http://localhost:8000
php artisan queue:work       # second terminal — sends run on the queue
```

Sign in with **owner@pakapak.test / password**, or register a fresh workspace.

`WHATSAPP_SANDBOX=true` (the default) simulates every Graph API call, so templates,
sends, receipts and replies all work before you have a live number. Set it to
`false` once real credentials are saved in **WhatsApp settings**.

### Going live

1. **WhatsApp settings** — save your WABA ID, phone number ID, system-user access
   token (stored encrypted), app secret and a verify token of your choosing.
2. Run **Test connection**. It calls `GET /{phone_number_id}` and stores the
   verified name, quality rating and messaging tier.
3. In your Meta app, point Webhooks at the callback URL shown on that screen and
   subscribe to `messages` and `message_template_status_update`.
4. Set `WHATSAPP_SANDBOX=false`.

Schedule the status reconciler if you want approvals to land without a webhook:

```bash
* * * * * cd /path/to/bhatsapp && php artisan schedule:run >> /dev/null 2>&1
```

---

## How the features map to the code

| Feature | Where it lives |
|---|---|
| Tenant registration and login | `AuthController`, `Models\Concerns\BelongsToTenant` (global tenant scope on every model) |
| WhatsApp API configuration | `WhatsappAccountController`, `Services\WhatsAppClient::verifyNumber()` |
| Template builder with live preview | `templates/form.blade.php` (Alpine), `Services\TemplateBuilder` |
| Every edit needs re-approval | `TemplateController::update()` → bumps version, resets to `DRAFT`, resubmits |
| Approval from WhatsApp | `TemplateSyncService::submit()` / `sync()`, plus `message_template_status_update` webhooks |
| Send with preview and pricing | `CampaignController::create → preview → store`, `Services\PricingService` |
| Per-customer delivery tracking | `messages` + `message_status_events`, `WebhookController::applyStatus()` |
| Full event log | `Support\ActivityLogger` → `activity_logs`, browsable at **Activity** |
| Incoming replies | `WebhookController::storeInbound()`, **Replies** screen with 24-hour window logic |
| Dashboard by date range | `DashboardController` |

### The send flow

```
pick template + recipients  →  preview & price  →  confirm
        │                            │                │
        │                            │                ├─ Campaign row (estimate frozen)
        │                            │                ├─ one queued Message per recipient
        │                            │                └─ SendCampaignJob → SendMessageJob per message
        │                            └─ unit price × recipients, grouped by country
        └─ approved templates only
```

Delivery status only ever moves forward — a late `sent` callback never overwrites
`read`. Failed messages are not billed, so a campaign's actual cost can land below
its estimate.

### Templates

`TemplateBuilder` produces the exact component array the Graph API expects, including
media card carousels: a message-bubble `BODY` plus a `CAROUSEL` component holding
1–10 cards, each with a media header, a body and up to two buttons of matching types.
Variables are declared as `{{1}}`, `{{2}}` and mapped per recipient — contact first
name, full name, phone, email or fixed text. The same map generates the sample values
Meta sees during review and the `components` array sent at message time.

Supported components: text/image/video/document headers, body with variables and
`*bold*` `_italic_` formatting, footer, quick reply / URL / phone / copy-code buttons,
and carousels.

### Data model

```
tenants ──┬── users
          ├── whatsapp_accounts        (credentials, encrypted at rest)
          ├── customers                (customers and leads, opt-in, 24h window)
          ├── message_templates ── template_versions   (one row per approval event)
          ├── campaigns ── messages ── message_status_events
          ├── pricing_rates            (per country × category; null tenant = platform default)
          └── activity_logs
```

---

## Demo helpers

While sandbox mode is on, two buttons replay what Meta would send:

- **Simulate delivery receipts** on a send page walks messages `sent → delivered → read`,
  failing roughly one in ten.
- **Simulate an incoming reply** on the Replies screen drops an inbound message in
  and opens the 24-hour service window.

Pending templates auto-approve after `WHATSAPP_SANDBOX_APPROVAL_DELAY` seconds when
you press **Check review status**.

---

## Notes before production

- Tailwind runs from the CDN for a zero-build MVP. Move to `laravel/vite` and a
  compiled stylesheet before you ship.
- Media headers use a sample URL rather than Meta's resumable upload handle. Swap in
  the upload API (`/uploads`) when you need real header attachments.
- Add rate limiting to the send queue to respect your number's messaging tier.
- Seeded rate cards are indicative. Replace them with your own margin-adjusted card
  in `PricingSeeder`.

---

## Going live: what is real and what still needs work

The Graph integration is real — the calls, payload shapes, webhook handling and
signature verification are what you would write for production. These are the gaps
that will bite on a live WABA, in the order they will hit you.

### 1. Media headers need an upload handle, not a URL

`components[].example.header_handle` must be a handle from the Resumable Upload API,
not a link. Image, video and document headers will be **rejected on create** until
you add it:

```
POST /{app_id}/uploads?file_length=&file_type=   → upload session id
POST /{upload_session_id}  (Authorization: OAuth <token>, body = file bytes)  → handle
```

Store that handle in `header.example_url`'s place. Sending is unaffected — outbound
messages legitimately use a public `link`, which `TemplateBuilder::sendComponents()`
already does.

### 2. Authentication templates have a fixed shape

Meta requires `add_security_recommendation`, `code_expiration_minutes` and an
`OTP` button — not free text. The builder submits a normal body, so authentication
templates will be rejected. Either add the special-case form or drop the category
from `config('whatsapp.categories')`.

### 3. Dynamic URL buttons need an example

If a button URL contains `{{1}}`, Meta wants a sample in `example`. The builder
accepts `buttons.*.url_example` but the form has no field for it, and
`sendComponents()` does not attach the button parameter for non-carousel URL
buttons. Add both before you ship a template with a dynamic link.

### 4. Throughput

Default Cloud API throughput is 80 messages/second, and your number's messaging
tier caps unique recipients per 24 hours. The queue has no rate limiter. For any
real list size, add one:

```php
Redis::throttle('whatsapp:'.$account->id)->allow(70)->every(1)->then(...)
```

Retries are handled: `WhatsAppClient::isRetryable()` flags 429s, 5xx and codes like
130429/131056, and `SendMessageJob` backs off over five attempts. Permanent errors
(131026 not on WhatsApp, 132000 template mismatch) fail immediately.

### 5. Template edit limits

Meta allows edits only on approved, rejected or paused templates, capped at 10 per
month and 1 per day per template. `TemplateController::update()` submits every save,
so a rapid second edit returns an error — surfaced to the user, but not prevented.

### 6. Costs are estimates until receipts land

The webhook reports `pricing.billable` and `pricing.category`, not an amount, so
actual spend is your rate card applied to delivered messages. `actual_cost` now
recalculates on every delivery receipt, and non-billable messages are zeroed. If you
need exact figures, reconcile against Meta's billing export.

### Before flipping the switch

- `WHATSAPP_SANDBOX=false`, real token saved, **Test connection** passes
- Webhook URL reachable over HTTPS with a valid certificate, `messages` and
  `message_template_status_update` subscribed, app secret saved so signatures verify
- `php artisan queue:work` running under supervisor, plus `schedule:run` in cron
- Send to one internal number first and confirm the receipt trail fills in
