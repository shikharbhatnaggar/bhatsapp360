<?php $__env->startSection('title', ($template->exists ? 'Edit template' : 'New template').' — Bhatsapp'); ?>
<?php $__env->startSection('heading', $template->exists ? 'Edit '.$template->name : 'New template'); ?>
<?php $__env->startSection('subheading', $template->exists
    ? 'Saving sends this back to WhatsApp for review. It cannot be used to send until they approve it again.'
    : 'Build the message, then submit it to WhatsApp for review.'); ?>

<?php $__env->startSection('content'); ?>
<form method="POST"
      action="<?php echo e($template->exists ? route('templates.update', $template) : route('templates.store')); ?>"
      x-data="templateBuilder(<?php echo e(Js::from(['input' => $input, 'template' => ['name' => $template->name, 'language' => $template->language, 'category' => $template->category]])); ?>)"
      class="grid gap-4 lg:grid-cols-[1fr_360px]">
    <?php echo csrf_field(); ?>
    <?php if($template->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

    <div class="space-y-4">
        
        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Basics</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="name" class="block text-sm text-ink-700">Template name</label>
                    <input id="name" name="name" x-model="name" @input="name = name.toLowerCase().replace(/[^a-z0-9_]/g,'_')"
                           value="<?php echo e(old('name', $template->name)); ?>" <?php if($template->exists): echo 'disabled'; endif; ?> required
                           class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600 disabled:bg-ink-50">
                    <p class="mt-1 text-xs text-ink-500"><?php echo e($template->exists ? 'Locked once WhatsApp has seen it.' : 'Lowercase, numbers and underscores.'); ?></p>
                </div>
                <div>
                    <label for="category" class="block text-sm text-ink-700">Category</label>
                    <select id="category" name="category" x-model="category"
                            class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <?php $__currentLoopData = config('whatsapp.categories'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <p class="mt-1 text-xs text-ink-500" x-text="categoryHint()"></p>
                </div>
                <div>
                    <label for="language" class="block text-sm text-ink-700">Language</label>
                    <select id="language" name="language" x-model="language"
                            class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <?php $__currentLoopData = ['en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'hi' => 'Hindi', 'te' => 'Telugu', 'ta' => 'Tamil', 'mr' => 'Marathi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($code); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
        </section>

        
        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <div class="flex items-baseline justify-between">
                <h2 class="text-base">Header</h2>
                <p class="text-xs text-ink-500">One per template, optional</p>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <template x-for="option in ['NONE','TEXT','IMAGE','VIDEO','DOCUMENT']" :key="option">
                    <label class="cursor-pointer rounded-lg border px-3 py-1.5 text-sm"
                           :class="header.format === option ? 'border-jade-600 bg-jade-50 text-jade-700' : 'border-ink-200 hover:border-ink-300'">
                        <input type="radio" name="header[format]" :value="option" x-model="header.format" class="sr-only">
                        <span x-text="option === 'NONE' ? 'No header' : option.charAt(0) + option.slice(1).toLowerCase()"></span>
                    </label>
                </template>
            </div>

            <div class="mt-4" x-show="header.format === 'TEXT'" x-cloak>
                <label for="header_text" class="block text-sm text-ink-700">Header text</label>
                <input id="header_text" name="header[text]" x-model="header.text" maxlength="60"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">Up to 60 characters and one variable.</p>
            </div>

            <div class="mt-4" x-show="['IMAGE','VIDEO','DOCUMENT'].includes(header.format)" x-cloak>
                <label for="header_media" class="block text-sm text-ink-700">Sample media URL</label>
                <input id="header_media" name="header[example_url]" x-model="header.example_url" type="url"
                       placeholder="https://cdn.example.com/offer.jpg"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">WhatsApp reviews the sample; the same URL is used at send time.</p>
            </div>
        </section>

        
        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <div class="flex items-baseline justify-between">
                <h2 class="text-base">Message body</h2>
                <p class="num text-xs text-ink-500"><span x-text="body.text.length"></span>/1024</p>
            </div>

            <textarea name="body[text]" x-model="body.text" rows="5" maxlength="1024" required
                      placeholder="Hi {{1}}, our Diwali box is back — 20% off until Sunday."
                      class="mt-3 w-full rounded-lg border-ink-200 text-sm leading-relaxed focus:border-jade-600 focus:ring-jade-600"></textarea>

            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                <button type="button" @click="addVariable()" class="rounded-md border border-ink-200 px-2 py-1 hover:border-ink-300">Add variable</button>
                <span class="text-ink-500">Wrap text in *asterisks* for bold, _underscores_ for italic.</span>
            </div>

            
            <div class="mt-4" x-show="variables().length" x-cloak>
                <h3 class="text-sm text-ink-700">What goes in each variable</h3>
                <div class="mt-2 space-y-2">
                    <template x-for="index in variables()" :key="index">
                        <div class="flex flex-wrap items-center gap-2 rounded-lg bg-ink-50 px-3 py-2">
                            <span class="num w-12 text-sm text-ink-500" x-text="label(index)"></span>
                            <select :name="'variable_map[body][' + index + ']'" x-model="variableMap.body[index]"
                                    class="rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                                <option value="customer.first_name">Contact first name</option>
                                <option value="customer.name">Contact full name</option>
                                <option value="customer.phone">Contact phone</option>
                                <option value="customer.email">Contact email</option>
                                <option value="static:">Fixed text</option>
                            </select>
                            <input type="text" x-show="(variableMap.body[index] || '').startsWith('static:')"
                                   :value="(variableMap.body[index] || '').replace('static:','')"
                                   @input="variableMap.body[index] = 'static:' + $event.target.value"
                                   placeholder="Fixed value" x-cloak
                                   class="flex-1 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                            <input type="hidden" :name="'body[examples][' + (index - 1) + ']'" :value="sampleFor(index)">
                        </div>
                    </template>
                </div>
                <p class="mt-2 text-xs text-ink-500">These sample values are what WhatsApp sees during review.</p>
            </div>
        </section>

        
        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Footer and buttons</h2>

            <div class="mt-4">
                <label for="footer_text" class="block text-sm text-ink-700">Footer <span class="text-ink-300">optional</span></label>
                <input id="footer_text" name="footer[text]" x-model="footer.text" maxlength="60"
                       placeholder="Reply STOP to opt out"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div class="mt-5 space-y-2">
                <template x-for="(button, i) in buttons" :key="i">
                    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-ink-200 p-2.5">
                        <select :name="'buttons[' + i + '][type]'" x-model="button.type"
                                class="rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                            <option value="QUICK_REPLY">Quick reply</option>
                            <option value="URL">Visit website</option>
                            <option value="PHONE_NUMBER">Call number</option>
                            <option value="COPY_CODE">Copy offer code</option>
                        </select>
                        <input :name="'buttons[' + i + '][text]'" x-model="button.text" maxlength="25" placeholder="Button label"
                               class="w-40 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <input x-show="button.type === 'URL'" :name="'buttons[' + i + '][url]'" x-model="button.url" x-cloak
                               placeholder="https://shop.example.com/diwali"
                               class="flex-1 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <input x-show="button.type === 'PHONE_NUMBER'" :name="'buttons[' + i + '][phone_number]'" x-model="button.phone_number" x-cloak
                               placeholder="+919812345678"
                               class="w-44 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <button type="button" @click="buttons.splice(i, 1)" class="ml-auto text-sm text-alert-600 underline underline-offset-2">Remove</button>
                    </div>
                </template>
            </div>

            <button type="button" @click="addButton()" x-show="buttons.length < 10"
                    class="mt-3 rounded-lg border border-ink-200 px-3 py-1.5 text-sm hover:border-ink-300">Add button</button>
        </section>

        
        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <div class="flex items-baseline justify-between">
                <h2 class="text-base">Media card carousel</h2>
                <p class="text-xs text-ink-500">Marketing templates only, 1–10 cards</p>
            </div>
            <p class="mt-1 text-sm text-ink-500">
                Cards ride below the message body. Every card needs the same shape: one media header, a short body, and matching buttons.
            </p>

            <div class="mt-4 space-y-3">
                <template x-for="(card, i) in cards" :key="i">
                    <div class="rounded-lg border border-ink-200 p-3.5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-ink-700">Card <span x-text="i + 1"></span></p>
                            <button type="button" @click="cards.splice(i, 1)" class="text-sm text-alert-600 underline underline-offset-2">Remove card</button>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs text-ink-500">Media type</label>
                                <select :name="'carousel[cards][' + i + '][header_format]'" x-model="card.header_format"
                                        class="mt-1 w-full rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                                    <option value="IMAGE">Image</option>
                                    <option value="VIDEO">Video</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-ink-500">Sample media URL</label>
                                <input :name="'carousel[cards][' + i + '][header_example_url]'" x-model="card.header_example_url" type="url"
                                       placeholder="https://cdn.example.com/card1.jpg"
                                       class="mt-1 w-full rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-ink-500">Card text</label>
                                <textarea :name="'carousel[cards][' + i + '][body_text]'" x-model="card.body_text" rows="2" maxlength="160"
                                          placeholder="Garam masala, 200g — ₹249"
                                          class="mt-1 w-full rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600"></textarea>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <template x-for="(button, j) in card.buttons" :key="j">
                                <div class="flex flex-wrap items-center gap-2">
                                    <select :name="'carousel[cards][' + i + '][buttons][' + j + '][type]'" x-model="button.type"
                                            class="rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                                        <option value="QUICK_REPLY">Quick reply</option>
                                        <option value="URL">Visit website</option>
                                    </select>
                                    <input :name="'carousel[cards][' + i + '][buttons][' + j + '][text]'" x-model="button.text" maxlength="25"
                                           placeholder="Button label"
                                           class="w-36 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                                    <input x-show="button.type === 'URL'" x-cloak :name="'carousel[cards][' + i + '][buttons][' + j + '][url]'" x-model="button.url"
                                           placeholder="https://shop.example.com/item"
                                           class="flex-1 rounded-lg border-ink-200 py-1.5 text-sm focus:border-jade-600 focus:ring-jade-600">
                                    <button type="button" @click="card.buttons.splice(j, 1)" class="text-sm text-alert-600 underline underline-offset-2">Remove</button>
                                </div>
                            </template>
                            <button type="button" @click="addCardButton(card)" x-show="card.buttons.length < 2"
                                    class="rounded-lg border border-ink-200 px-2.5 py-1 text-xs hover:border-ink-300">Add card button</button>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addCard()" x-show="cards.length < 10"
                    class="mt-3 rounded-lg border border-ink-200 px-3 py-1.5 text-sm hover:border-ink-300">
                <span x-text="cards.length ? 'Add another card' : 'Add a carousel'"></span>
            </button>
        </section>

        <div class="flex flex-wrap items-center gap-2">
            <?php if($template->exists): ?>
                <button class="rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700">
                    Save and resubmit for review
                </button>
            <?php else: ?>
                <button name="submit_for_review" value="1"
                        class="rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700">
                    Submit for review
                </button>
                <button name="submit_for_review" value="0"
                        class="rounded-lg border border-ink-200 px-4 py-2.5 text-sm hover:border-ink-300">Save as draft</button>
            <?php endif; ?>
            <a href="<?php echo e(route('templates.index')); ?>" class="text-sm text-ink-500 underline underline-offset-2">Cancel</a>
        </div>
    </div>

    
    <aside>
        <div class="sticky top-6">
            <h2 class="mb-2 text-sm text-ink-500">Preview</h2>
            <div class="chat-canvas rounded-xl border border-ink-200 p-4">
                <div class="mx-auto max-w-[320px] space-y-2">
                    <div class="rounded-lg rounded-tl-sm bg-white px-3 py-2.5 shadow-sm">
                        <template x-if="header.format === 'TEXT' && header.text">
                            <p class="mb-1.5 text-[15px] font-semibold leading-snug" x-html="render(header.text)"></p>
                        </template>
                        <template x-if="header.format === 'IMAGE' && header.example_url && !mediaBroken">
                            <img :src="header.example_url" alt="" x-on:error="mediaBroken = true"
                                 class="mb-2 h-32 w-full rounded-md object-cover">
                        </template>
                        <template x-if="header.format === 'VIDEO' && header.example_url && !mediaBroken">
                            <video :src="header.example_url" muted playsinline controls preload="metadata"
                                   x-on:error="mediaBroken = true"
                                   class="mb-2 h-32 w-full rounded-md bg-black object-cover"></video>
                        </template>
                        <template x-if="header.format === 'DOCUMENT' && header.example_url && !mediaBroken">
                            <div class="mb-2 flex items-center gap-2.5 rounded-md bg-ink-50 px-3 py-2.5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded bg-white text-[10px] text-ink-500"
                                      x-text="fileExtension(header.example_url)"></span>
                                <span class="truncate text-[13px] text-ink-700" x-text="fileName(header.example_url)"></span>
                            </div>
                        </template>
                        <template x-if="header.format !== 'NONE' && header.format !== 'TEXT' && (!header.example_url || mediaBroken)">
                            <div class="mb-2 grid h-24 w-full place-items-center rounded-md bg-ink-100 px-3 text-center text-xs text-ink-500"
                                 x-text="mediaBroken
                                    ? 'That ' + header.format.toLowerCase() + ' URL would not load — recipients will not see it either'
                                    : header.format.toLowerCase() + ' header'"></div>
                        </template>

                        <p class="whitespace-pre-line text-[14.5px] leading-relaxed"
                           x-html="render(body.text || 'Your message body appears here.')"></p>

                        <template x-if="footer.text">
                            <p class="mt-2 text-[12px] text-ink-500" x-text="footer.text"></p>
                        </template>

                        <p class="mt-1 text-right text-[11px] text-ink-300"><?php echo e(now()->format('g:i A')); ?></p>
                    </div>

                    <template x-for="(button, i) in buttons.filter(b => b.text)" :key="i">
                        <div class="rounded-lg bg-white py-2 text-center text-[14px] text-[#0a7cff] shadow-sm" x-text="button.text"></div>
                    </template>

                    <div class="flex gap-2 overflow-x-auto pb-1" x-show="cards.length" x-cloak>
                        <template x-for="(card, i) in cards" :key="i">
                            <div class="w-[190px] shrink-0 overflow-hidden rounded-lg bg-white shadow-sm">
                                <template x-if="card.header_example_url && card.header_format === 'IMAGE'">
                                    <img :src="card.header_example_url" alt="" class="h-24 w-full object-cover">
                                </template>
                                <template x-if="card.header_example_url && card.header_format === 'VIDEO'">
                                    <video :src="card.header_example_url" muted playsinline preload="metadata"
                                           class="h-24 w-full bg-black object-cover"></video>
                                </template>
                                <template x-if="!card.header_example_url">
                                    <div class="grid h-24 w-full place-items-center bg-ink-100 text-xs text-ink-500">card media</div>
                                </template>
                                <p class="px-2.5 py-2 text-[13px] leading-snug" x-html="render(card.body_text)"></p>
                                <template x-for="(button, j) in card.buttons.filter(b => b.text)" :key="j">
                                    <div class="border-t border-ink-100 py-1.5 text-center text-[13px] text-[#0a7cff]" x-text="button.text"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <p class="mt-3 text-xs leading-relaxed text-ink-500">
                Variables show sample values. Each recipient sees their own.
            </p>
        </div>
    </aside>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>

<script>
function templateBuilder(seed) {
    const input = seed.input || {};

    return {
        name: seed.template.name || '',
        language: seed.template.language || 'en_US',
        category: seed.template.category || 'MARKETING',
        header: Object.assign({ format: 'NONE', text: '', example_url: '' }, input.header || {}),
        body: Object.assign({ text: '' }, input.body || {}),
        footer: Object.assign({ text: '' }, input.footer || {}),
        buttons: (input.buttons || []).map(b => Object.assign({ type: 'QUICK_REPLY', text: '', url: '', phone_number: '' }, b)),
        cards: (input.carousel?.cards || []).map(c => Object.assign(
            { header_format: 'IMAGE', header_example_url: '', body_text: '', buttons: [] },
            c,
            { buttons: (c.buttons || []).map(b => Object.assign({ type: 'QUICK_REPLY', text: '', url: '' }, b)) }
        )),
        variableMap: { body: Object.assign({}, input.variable_map?.body || {}) },
        mediaBroken: false,

        init() {
            // A new URL or format deserves a fresh attempt at loading.
            this.$watch('header.example_url', () => { this.mediaBroken = false; });
            this.$watch('header.format', () => { this.mediaBroken = false; });
        },

        fileName(url) {
            try { return decodeURIComponent(new URL(url).pathname.split('/').pop()) || 'document'; }
            catch { return 'document'; }
        },

        fileExtension(url) {
            const name = this.fileName(url);
            return name.includes('.') ? name.split('.').pop().toUpperCase().slice(0, 4) : 'FILE';
        },

        variables() {
            const found = [...this.body.text.matchAll(/\{\{(\d+)\}\}/g)].map(m => parseInt(m[1], 10));
            const unique = [...new Set(found)].sort((a, b) => a - b);
            unique.forEach(i => { if (!this.variableMap.body[i]) this.variableMap.body[i] = 'customer.first_name'; });
            return unique;
        },

        addVariable() {
            const next = (this.variables().at(-1) || 0) + 1;
            this.body.text += ' ' + this.label(next);
        },

        label(index) {
            return '{{' + index + '}}';
        },

        addButton() {
            this.buttons.push({ type: 'QUICK_REPLY', text: '', url: '', phone_number: '' });
        },

        addCard() {
            this.cards.push({ header_format: 'IMAGE', header_example_url: '', body_text: '', buttons: [{ type: 'QUICK_REPLY', text: '', url: '' }] });
        },

        addCardButton(card) {
            card.buttons.push({ type: 'QUICK_REPLY', text: '', url: '' });
        },

        sampleFor(index) {
            const rule = this.variableMap.body[index] || 'customer.first_name';
            if (rule.startsWith('static:')) return rule.slice(7) || 'Sample';
            return { 'customer.first_name': 'Priya', 'customer.name': 'Priya Nair', 'customer.phone': '919812345678', 'customer.email': 'priya@example.com' }[rule] || 'Sample';
        },

        categoryHint() {
            return {
                MARKETING: 'Promotions and offers. Needs opt-in, billed at the highest rate.',
                UTILITY: 'Order updates, reminders, receipts. Cheaper than marketing.',
                AUTHENTICATION: 'One-time passcodes only.',
            }[this.category];
        },

        render(text) {
            const escaped = (text || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            return escaped
                .replace(/\{\{(\d+)\}\}/g, (_, i) => '<span class="rounded bg-jade-50 px-1 text-jade-700">' + this.sampleFor(parseInt(i, 10)) + '</span>')
                .replace(/\*(.+?)\*/gs, '<strong>$1</strong>')
                .replace(/_(.+?)_/gs, '<em>$1</em>')
                .replace(/\n/g, '<br>');
        },
    };
}
</script>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/templates/form.blade.php ENDPATH**/ ?>