<?php $__env->startSection('title', 'New send — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'New send'); ?>
<?php $__env->startSection('subheading', 'Pick an approved template and the people who should get it.'); ?>

<?php $__env->startSection('content'); ?>
<?php if($templates->isEmpty()): ?>
    <div class="rounded-xl border border-signal-200 bg-signal-50 p-6 text-sm text-signal-700">
        <p class="text-base">No approved templates yet</p>
        <p class="mt-1.5">WhatsApp has to approve a template before you can send it.
            <a href="<?php echo e(route('templates.create')); ?>" class="underline underline-offset-2">Build one now</a>.</p>
    </div>
<?php else: ?>
<form method="POST" action="<?php echo e(route('campaigns.preview')); ?>"
      x-data="sendPicker(<?php echo e(Js::from([
          'rates' => $rates,
          'currency' => $tenant->currency,
          'templates' => $templates->map(fn ($t) => ['id' => $t->id, 'category' => $t->category, 'name' => $t->name]),
          'selected' => $selectedTemplate?->id,
      ])); ?>)"
      class="grid gap-4 lg:grid-cols-[1fr_320px]">
    <?php echo csrf_field(); ?>

    <div class="space-y-4">
        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">What are you sending?</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="campaign_name" class="block text-sm text-ink-700">Name this send</label>
                    <input id="campaign_name" name="name" value="<?php echo e(old('name', 'Send '.now()->format('d M, g:i A'))); ?>" required
                           class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                    <p class="mt-1 text-xs text-ink-500">Only your team sees this.</p>
                </div>
                <div>
                    <label for="message_template_id" class="block text-sm text-ink-700">Template</label>
                    <select id="message_template_id" name="message_template_id" x-model.number="templateId" required
                            class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <option value="">Choose a template</option>
                        <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($template->id); ?>"><?php echo e($template->name); ?> — <?php echo e(ucfirst(strtolower($template->category))); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <p class="mt-1 text-xs text-ink-500" x-text="rateLine()"></p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-ink-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-5 py-3.5">
                <h2 class="text-base">Who gets it?</h2>
                <div class="flex items-center gap-2 text-sm">
                    <button type="button" @click="selectAll(true)" class="text-jade-700 underline underline-offset-2">Select all</button>
                    <span class="text-ink-200">|</span>
                    <button type="button" @click="selectAll(false)" class="text-jade-700 underline underline-offset-2">Clear</button>
                </div>
            </div>

            <div class="border-b border-ink-100 px-5 py-3">
                <input type="search" x-model="search" placeholder="Filter this list by name, number or tag"
                       class="w-full rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div class="max-h-[26rem] overflow-y-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-ink-100">
                    <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-ink-50"
                            x-show="matches(<?php echo e(Js::from(Str::lower($customer->name.' '.$customer->phone.' '.implode(' ', $customer->tags ?? [])))); ?>)">
                            <td class="w-10 px-5 py-2.5">
                                <input type="checkbox" name="customer_ids[]" value="<?php echo e($customer->id); ?>"
                                       @change="count = $el.closest('form').querySelectorAll('input[name=\'customer_ids[]\']:checked').length"
                                       class="rounded border-ink-300 text-jade-600 focus:ring-jade-600">
                            </td>
                            <td class="py-2.5">
                                <?php echo e($customer->name); ?>

                                <span class="ml-1.5 text-xs text-ink-500"><?php echo e($customer->type); ?></span>
                            </td>
                            <td class="num py-2.5 text-ink-500">+<?php echo e($customer->phone); ?></td>
                            <td class="px-5 py-2.5 text-right text-xs text-ink-500">
                                <?php if(! $customer->opted_in): ?>
                                    <span class="text-signal-700">no opt-in on record</span>
                                <?php elseif($customer->tags): ?>
                                    <?php echo e(implode(', ', $customer->tags)); ?>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td class="px-5 py-10 text-center text-ink-500">
                            No contacts to send to. <a href="<?php echo e(route('customers.create')); ?>" class="text-jade-700 underline underline-offset-2">Add one</a>.
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <aside>
        <div class="sticky top-6 rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Estimate</h2>
            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-500">Recipients</dt><dd class="num" x-text="count"></dd></div>
                <div class="flex justify-between"><dt class="text-ink-500">Per message</dt><dd class="num" x-text="money(unitPrice())"></dd></div>
                <div class="flex justify-between border-t border-ink-100 pt-2.5 text-base">
                    <dt>Estimated cost</dt><dd class="num" x-text="money(unitPrice() * count)"></dd>
                </div>
            </dl>

            <p class="mt-3 text-xs leading-relaxed text-ink-500">
                WhatsApp bills per delivered message at the recipient's country rate. The next screen shows the exact breakdown before anything is sent.
            </p>

            <button :disabled="!templateId || count === 0"
                    class="mt-5 w-full rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700 disabled:cursor-not-allowed disabled:bg-ink-200 disabled:text-ink-500">
                Preview and price
            </button>
        </div>
    </aside>
</form>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>

<script>
function sendPicker(seed) {
    return {
        templateId: seed.selected || '',
        count: 0,
        search: '',
        matches(haystack) {
            return !this.search || haystack.includes(this.search.toLowerCase());
        },
        selectAll(state) {
            const boxes = [...this.$el.querySelectorAll("input[name='customer_ids[]']")]
                .filter(box => box.closest('tr').style.display !== 'none');
            boxes.forEach(box => { box.checked = state; });
            this.count = this.$el.querySelectorAll("input[name='customer_ids[]']:checked").length;
        },
        template() {
            return seed.templates.find(t => t.id === Number(this.templateId));
        },
        unitPrice() {
            const t = this.template();
            return t ? Number(seed.rates[t.category] || 0) : 0;
        },
        rateLine() {
            const t = this.template();
            if (!t) return 'Only approved templates appear here.';
            return t.category.charAt(0) + t.category.slice(1).toLowerCase() + ' rate: ' + this.money(this.unitPrice()) + ' per message';
        },
        money(value) {
            const symbols = { INR: '₹', USD: '$', EUR: '€', GBP: '£' };
            return (symbols[seed.currency] || seed.currency + ' ') + Number(value).toFixed(2);
        },
    };
}
</script>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/campaigns/create.blade.php ENDPATH**/ ?>