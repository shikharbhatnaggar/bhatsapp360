<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Services\TemplateBuilder;
use App\Services\TemplateSyncService;
use App\Services\WhatsAppClient;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function __construct(
        protected TemplateBuilder $builder,
        protected TemplateSyncService $sync,
    ) {}

    public function index(Request $request)
    {
        $templates = MessageTemplate::query()
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('category')->toString(), fn ($q, $c) => $q->where('category', $c))
            ->when($request->string('q')->toString(), fn ($q, $t) => $q->where('name', 'like', "%{$t}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.form', [
            'template' => new MessageTemplate([
                'category' => 'MARKETING',
                'language' => 'en_US',
                'components' => [],
            ]),
            'input' => $this->emptyInput(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        $template = MessageTemplate::create([
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => $data['category'],
            'sub_category' => filled($data['carousel']['cards'] ?? []) ? 'media_card_carousel' : null,
            'status' => 'DRAFT',
            'components' => $this->builder->componentsFromInput($data),
            'variable_map' => $data['variable_map'] ?? [],
            'whatsapp_account_id' => $request->user()->tenant->whatsappAccount?->id,
            'created_by' => $request->user()->id,
            'version' => 1,
        ]);

        ActivityLogger::log('template.created', "Template “{$template->name}” created", $template, [
            'category' => $template->category,
            'components' => count($template->components),
        ]);

        if ($request->boolean('submit_for_review')) {
            $result = $this->sync->submit($template, 'created');

            return redirect()->route('templates.show', $template)
                ->with($result['ok'] ? 'status' : 'error', $result['message']);
        }

        return redirect()->route('templates.show', $template)->with('status', 'Draft saved.');
    }

    public function show(MessageTemplate $template)
    {
        $template->load(['versions.submitter', 'author']);

        return view('templates.show', [
            'template' => $template,
            'preview' => $this->builder->renderText($template),
        ]);
    }

    public function edit(MessageTemplate $template)
    {
        return view('templates.form', [
            'template' => $template,
            'input' => $this->inputFromComponents($template),
        ]);
    }

    /**
     * Editing an approved template sends it back through review — WhatsApp
     * treats every content change as a new approval event.
     */
    public function update(Request $request, MessageTemplate $template)
    {
        $data = $this->validateInput($request, $template);
        $before = $template->components;

        $template->update([
            'category' => $data['category'],
            'sub_category' => filled($data['carousel']['cards'] ?? []) ? 'media_card_carousel' : null,
            'components' => $this->builder->componentsFromInput($data),
            'variable_map' => $data['variable_map'] ?? [],
            'version' => $template->version + 1,
            'status' => 'DRAFT',
        ]);

        ActivityLogger::log('template.updated', "Template “{$template->name}” edited to v{$template->version}", $template, [
            'before' => $before,
            'after' => $template->components,
        ]);

        $result = $this->sync->submit($template, 'updated');

        return redirect()->route('templates.show', $template)
            ->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    /** Manual "Submit for review" from the template page. */
    public function submit(MessageTemplate $template)
    {
        $result = $this->sync->submit($template, $template->whatsapp_template_id ? 'resubmitted' : 'created');

        return back()->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    /** Pull the current review decision from WhatsApp. */
    public function refresh(Request $request)
    {
        $account = $request->user()->tenant->whatsappAccount;

        if (! $account) {
            return back()->with('error', 'Connect a WhatsApp number first.');
        }

        $updated = $this->sync->sync($account);

        return back()->with('status', $updated
            ? $updated.' template status'.($updated === 1 ? '' : 'es').' updated from WhatsApp.'
            : 'No status changes yet.');
    }

    public function destroy(MessageTemplate $template)
    {
        $name = $template->name;

        if ($template->whatsapp_template_id && $template->account) {
            WhatsAppClient::for($template->account)->deleteTemplate($name);
        }

        $template->delete();
        ActivityLogger::log('template.deleted', "Template “{$name}” deleted", null, ['name' => $name]);

        return redirect()->route('templates.index')->with('status', "“{$name}” deleted.");
    }

    // ------------------------------------------------------------- helpers

    protected function validateInput(Request $request, ?MessageTemplate $template = null): array
    {
        $rules = [
            'language' => ['required', 'string', 'max:15'],
            'category' => ['required', Rule::in(array_keys(config('whatsapp.categories')))],
            'header.format' => ['required', Rule::in(['NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'])],
            'header.text' => ['nullable', 'string', 'max:60'],
            'header.example_url' => ['nullable', 'url', 'max:500'],
            'body.text' => ['required', 'string', 'max:1024'],
            'body.examples' => ['nullable', 'array'],
            'footer.text' => ['nullable', 'string', 'max:60'],
            'buttons' => ['nullable', 'array', 'max:10'],
            'buttons.*.type' => ['nullable', Rule::in(['QUICK_REPLY', 'URL', 'PHONE_NUMBER', 'COPY_CODE'])],
            'buttons.*.text' => ['nullable', 'string', 'max:25'],
            'buttons.*.url' => ['nullable', 'string', 'max:2000'],
            'buttons.*.phone_number' => ['nullable', 'string', 'max:20'],
            'carousel.cards' => ['nullable', 'array', 'max:10'],
            'carousel.cards.*.body_text' => ['nullable', 'string', 'max:160'],
            'carousel.cards.*.header_example_url' => ['nullable', 'url', 'max:500'],
            'variable_map' => ['nullable', 'array'],
        ];

        // Name is immutable once WhatsApp has seen the template.
        if (! $template) {
            $rules['name'] = [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('message_templates', 'name')->where('tenant_id', $request->user()->tenant_id),
            ];
        }

        $data = $request->validate($rules, [
            'name.regex' => 'Use lowercase letters, numbers and underscores only.',
        ]);

        $data['name'] = $template?->name ?? $data['name'];

        return $data;
    }

    protected function emptyInput(): array
    {
        return [
            'header' => ['format' => 'NONE', 'text' => '', 'example_url' => ''],
            'body' => ['text' => '', 'examples' => []],
            'footer' => ['text' => ''],
            'buttons' => [],
            'carousel' => ['cards' => []],
            'variable_map' => ['body' => [], 'header' => []],
        ];
    }

    /** Turn stored Graph components back into the builder form shape. */
    protected function inputFromComponents(MessageTemplate $template): array
    {
        $input = $this->emptyInput();
        $input['variable_map'] = $template->variable_map ?: $input['variable_map'];

        foreach ($template->components ?? [] as $component) {
            switch (strtoupper($component['type'] ?? '')) {
                case 'HEADER':
                    $input['header'] = [
                        'format' => strtoupper($component['format'] ?? 'TEXT'),
                        'text' => $component['text'] ?? '',
                        'example_url' => data_get($component, 'example.header_handle.0', ''),
                        'examples' => data_get($component, 'example.header_text', []),
                    ];
                    break;

                case 'BODY':
                    $input['body'] = [
                        'text' => $component['text'] ?? '',
                        'examples' => data_get($component, 'example.body_text.0', []),
                    ];
                    break;

                case 'FOOTER':
                    $input['footer'] = ['text' => $component['text'] ?? ''];
                    break;

                case 'BUTTONS':
                    $input['buttons'] = array_map(fn ($b) => [
                        'type' => $b['type'] ?? 'QUICK_REPLY',
                        'text' => $b['text'] ?? '',
                        'url' => $b['url'] ?? '',
                        'url_example' => data_get($b, 'example.0', ''),
                        'phone_number' => $b['phone_number'] ?? '',
                    ], $component['buttons'] ?? []);
                    break;

                case 'CAROUSEL':
                    $input['carousel']['cards'] = array_map(function ($card) {
                        $row = ['header_format' => 'IMAGE', 'header_example_url' => '', 'body_text' => '', 'buttons' => []];

                        foreach ($card['components'] ?? [] as $component) {
                            $type = strtoupper($component['type'] ?? '');
                            if ($type === 'HEADER') {
                                $row['header_format'] = strtoupper($component['format'] ?? 'IMAGE');
                                $row['header_example_url'] = data_get($component, 'example.header_handle.0', '');
                            }
                            if ($type === 'BODY') {
                                $row['body_text'] = $component['text'] ?? '';
                            }
                            if ($type === 'BUTTONS') {
                                $row['buttons'] = array_map(fn ($b) => [
                                    'type' => $b['type'] ?? 'QUICK_REPLY',
                                    'text' => $b['text'] ?? '',
                                    'url' => $b['url'] ?? '',
                                ], $component['buttons'] ?? []);
                            }
                        }

                        return $row;
                    }, $component['cards'] ?? []);
                    break;
            }
        }

        return $input;
    }
}
