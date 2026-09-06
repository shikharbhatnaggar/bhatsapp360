<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MessageTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Translates the template builder form into the component array the Graph API
 * expects, and back again for previews and for outbound message payloads.
 *
 * Shapes follow the Cloud API template reference, including media card carousel
 * templates (message bubble BODY + a CAROUSEL component holding 1–10 cards).
 */
class TemplateBuilder
{
    /** Build the `components` array for POST /{waba_id}/message_templates. */
    public function componentsFromInput(array $input): array
    {
        $components = [];

        // ---- HEADER (optional, one per template) -------------------------
        $header = $input['header'] ?? [];
        $format = strtoupper($header['format'] ?? 'NONE');

        if ($format === 'TEXT' && filled($header['text'] ?? null)) {
            $component = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $header['text']];
            $vars = $this->placeholders($header['text']);
            if ($vars) {
                // Header text supports a single variable; the example is a flat array.
                $component['example'] = ['header_text' => array_values(array_map(
                    fn ($i) => Arr::get($header, 'examples.'.($i - 1), 'Sample'),
                    $vars
                ))];
            }
            $components[] = $component;
        } elseif (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $components[] = [
                'type' => 'HEADER',
                'format' => $format,
                // A real submission uses a resumable-upload handle; a URL is kept
                // here so the MVP can preview and re-submit without the upload API.
                'example' => ['header_handle' => [$header['example_url'] ?? '']],
            ];
        }

        // ---- BODY (required) --------------------------------------------
        $bodyText = $input['body']['text'] ?? '';
        $body = ['type' => 'BODY', 'text' => $bodyText];
        $bodyVars = $this->placeholders($bodyText);
        if ($bodyVars) {
            $body['example'] = ['body_text' => [array_values(array_map(
                fn ($i) => Arr::get($input, 'body.examples.'.($i - 1), 'Sample'),
                $bodyVars
            ))]];
        }
        $components[] = $body;

        // ---- FOOTER (optional) -------------------------------------------
        if (filled($input['footer']['text'] ?? null)) {
            $components[] = ['type' => 'FOOTER', 'text' => $input['footer']['text']];
        }

        // ---- BUTTONS (optional, max 10 with type limits) ------------------
        $buttons = $this->buttonsFromInput($input['buttons'] ?? []);
        if ($buttons) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
        }

        // ---- CAROUSEL (optional, media cards) -----------------------------
        $cards = $this->cardsFromInput($input['carousel']['cards'] ?? []);
        if ($cards) {
            $components[] = ['type' => 'CAROUSEL', 'cards' => $cards];
        }

        return $components;
    }

    protected function buttonsFromInput(array $rows): array
    {
        $buttons = [];

        foreach ($rows as $row) {
            $type = strtoupper($row['type'] ?? '');
            $text = trim($row['text'] ?? '');
            if ($text === '') {
                continue;
            }

            $button = match ($type) {
                'QUICK_REPLY' => ['type' => 'QUICK_REPLY', 'text' => $text],
                'URL' => array_filter([
                    'type' => 'URL',
                    'text' => $text,
                    'url' => $row['url'] ?? '',
                    // A trailing {{1}} makes the link dynamic; Graph needs a sample.
                    'example' => filled($row['url_example'] ?? null) ? [$row['url_example']] : null,
                ], fn ($v) => $v !== null),
                'PHONE_NUMBER' => ['type' => 'PHONE_NUMBER', 'text' => $text, 'phone_number' => $row['phone_number'] ?? ''],
                'COPY_CODE' => ['type' => 'COPY_CODE', 'example' => $row['coupon_code'] ?? 'SAVE20'],
                default => null,
            };

            if ($button) {
                $buttons[] = $button;
            }
        }

        return array_slice($buttons, 0, 10);
    }

    /**
     * Carousel cards. Every card must carry the same component structure —
     * a media header, a body, and 1–2 buttons of matching types.
     */
    protected function cardsFromInput(array $rows): array
    {
        $cards = [];

        foreach (array_slice($rows, 0, 10) as $row) {
            if (blank($row['body_text'] ?? null)) {
                continue;
            }

            $format = strtoupper($row['header_format'] ?? 'IMAGE');
            $components = [[
                'type' => 'HEADER',
                'format' => in_array($format, ['IMAGE', 'VIDEO'], true) ? $format : 'IMAGE',
                'example' => ['header_handle' => [$row['header_example_url'] ?? '']],
            ]];

            $cardBody = ['type' => 'BODY', 'text' => $row['body_text']];
            $vars = $this->placeholders($row['body_text']);
            if ($vars) {
                $cardBody['example'] = ['body_text' => [array_values(array_map(
                    fn ($i) => Arr::get($row, 'body_examples.'.($i - 1), 'Sample'),
                    $vars
                ))]];
            }
            $components[] = $cardBody;

            $buttons = $this->buttonsFromInput($row['buttons'] ?? []);
            if ($buttons) {
                $components[] = ['type' => 'BUTTONS', 'buttons' => array_slice($buttons, 0, 2)];
            }

            $cards[] = ['components' => $components];
        }

        return $cards;
    }

    /** Full create payload for the Graph API. */
    public function createPayload(MessageTemplate $template): array
    {
        return array_filter([
            'name' => $template->name,
            'language' => $template->language,
            'category' => $template->category,
            'components' => $template->components,
            'allow_category_change' => true,
        ], fn ($v) => $v !== null && $v !== []);
    }

    // ------------------------------------------------------------- rendering

    public function placeholders(?string $text): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', (string) $text, $matches);

        return array_values(array_unique(array_map('intval', $matches[1] ?? [])));
    }

    /**
     * Resolve {{n}} values for one customer using the saved variable map.
     * Map entries look like `customer.first_name` or `static:Diwali`.
     */
    public function resolveVariables(MessageTemplate $template, ?Customer $customer, array $overrides = []): array
    {
        $map = array_replace((array) $template->variable_map, $overrides);
        $values = [];

        foreach ($template->bodyVariables() as $index) {
            $values[$index] = $this->resolveOne($map['body'][$index] ?? null, $customer, $index);
        }

        return $values;
    }

    public function resolveHeaderVariables(MessageTemplate $template, ?Customer $customer, array $overrides = []): array
    {
        $map = array_replace((array) $template->variable_map, $overrides);
        $values = [];

        foreach ($template->headerVariables() as $index) {
            $values[$index] = $this->resolveOne($map['header'][$index] ?? null, $customer, $index);
        }

        return $values;
    }

    protected function resolveOne(?string $rule, ?Customer $customer, int $index): string
    {
        if (blank($rule)) {
            return $customer?->firstName() ?? 'Sample '.$index;
        }

        if (Str::startsWith($rule, 'customer.')) {
            $field = Str::after($rule, 'customer.');

            return (string) ($customer?->mergeField($field) ?? Str::headline($field));
        }

        return (string) Str::after($rule, 'static:');
    }

    /** Plain-text render used by the preview panes and message logs. */
    public function renderText(MessageTemplate $template, ?Customer $customer = null): string
    {
        $body = $template->component('BODY')['text'] ?? '';
        foreach ($this->resolveVariables($template, $customer) as $index => $value) {
            $body = str_replace('{{'.$index.'}}', $value, $body);
        }

        return $body;
    }

    // ------------------------------------------------------- send components

    /** Build the `components` array for POST /{phone_number_id}/messages. */
    public function sendComponents(MessageTemplate $template, Customer $customer): array
    {
        $components = [];

        $header = $template->component('HEADER');
        if ($header) {
            $format = strtoupper($header['format'] ?? 'TEXT');

            if ($format === 'TEXT' && $template->headerVariables()) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => array_map(
                        fn ($v) => ['type' => 'text', 'text' => $v],
                        array_values($this->resolveHeaderVariables($template, $customer))
                    ),
                ];
            } elseif (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                $link = Arr::get($header, 'example.header_handle.0');
                if ($link) {
                    $components[] = [
                        'type' => 'header',
                        'parameters' => [[
                            'type' => strtolower($format),
                            strtolower($format) => ['link' => $link],
                        ]],
                    ];
                }
            }
        }

        $bodyValues = $this->resolveVariables($template, $customer);
        if ($bodyValues) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => $v], array_values($bodyValues)),
            ];
        }

        if ($carousel = $template->component('CAROUSEL')) {
            $cards = [];

            foreach ($carousel['cards'] ?? [] as $index => $card) {
                $cardComponents = [];

                foreach ($card['components'] ?? [] as $component) {
                    $type = strtoupper($component['type'] ?? '');

                    if ($type === 'HEADER') {
                        $format = strtolower($component['format'] ?? 'image');
                        $link = Arr::get($component, 'example.header_handle.0');
                        if ($link) {
                            $cardComponents[] = [
                                'type' => 'header',
                                'parameters' => [['type' => $format, $format => ['link' => $link]]],
                            ];
                        }
                    }

                    if ($type === 'BODY' && $this->placeholders($component['text'] ?? '')) {
                        $cardComponents[] = [
                            'type' => 'body',
                            'parameters' => array_map(
                                fn ($i) => ['type' => 'text', 'text' => $customer->firstName()],
                                $this->placeholders($component['text'])
                            ),
                        ];
                    }

                    if ($type === 'BUTTONS') {
                        foreach ($component['buttons'] ?? [] as $buttonIndex => $button) {
                            if (strtoupper($button['type']) === 'QUICK_REPLY') {
                                $cardComponents[] = [
                                    'type' => 'button',
                                    'sub_type' => 'quick_reply',
                                    'index' => (string) $buttonIndex,
                                    'parameters' => [['type' => 'payload', 'payload' => Str::slug($button['text'] ?? 'reply').'_'.$index]],
                                ];
                            } elseif (strtoupper($button['type']) === 'URL' && Str::contains($button['url'] ?? '', '{{1}}')) {
                                $cardComponents[] = [
                                    'type' => 'button',
                                    'sub_type' => 'url',
                                    'index' => (string) $buttonIndex,
                                    'parameters' => [['type' => 'text', 'text' => (string) $customer->id]],
                                ];
                            }
                        }
                    }
                }

                $cards[] = ['card_index' => $index, 'components' => $cardComponents];
            }

            if ($cards) {
                $components[] = ['type' => 'carousel', 'cards' => $cards];
            }
        }

        return $components;
    }

    /** Complete message payload for one recipient. */
    public function messagePayload(MessageTemplate $template, Customer $customer): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $customer->phone,
            'type' => 'template',
            'template' => [
                'name' => $template->name,
                'language' => ['code' => $template->language],
            ],
        ];

        $components = $this->sendComponents($template, $customer);
        if ($components) {
            $payload['template']['components'] = $components;
        }

        return $payload;
    }
}
