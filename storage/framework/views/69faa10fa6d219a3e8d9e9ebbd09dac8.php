<?php $__env->startSection('title', 'Confirm send — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'Confirm this send'); ?>
<?php $__env->startSection('subheading', 'This is exactly what goes out, and what it costs. Nothing is sent until you confirm.'); ?>

<?php $__env->startSection('content'); ?>
<div class="grid gap-4 lg:grid-cols-[1fr_360px]">
    <div class="space-y-4">
        <section class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Cost</h2>
            <table class="mt-4 w-full text-sm">
                <thead class="text-left text-ink-500">
                    <tr>
                        <th class="pb-2 font-normal">Country</th>
                        <th class="pb-2 text-right font-normal">Recipients</th>
                        <th class="pb-2 text-right font-normal">Per message</th>
                        <th class="pb-2 text-right font-normal">Line total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                <?php $__currentLoopData = $quote['breakdown']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="py-2.5"><?php echo e($line['country_code']); ?></td>
                        <td class="num py-2.5 text-right"><?php echo e(number_format($line['count'])); ?></td>
                        <td class="num py-2.5 text-right"><?php echo app(\App\Services\PricingService::class)->format(...[$line['unit_price'], $line['currency']]); ?></td>
                        <td class="num py-2.5 text-right"><?php echo app(\App\Services\PricingService::class)->format(...[$line['line_total'], $line['currency']]); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot>
                    <tr class="border-t border-ink-200">
                        <td class="pt-3"><?php echo e(ucfirst(strtolower($template->category))); ?> rate</td>
                        <td class="num pt-3 text-right"><?php echo e(number_format($quote['count'])); ?></td>
                        <td class="num pt-3 text-right"><?php echo app(\App\Services\PricingService::class)->format(...[$quote['unit_price'], $quote['currency']]); ?></td>
                        <td class="num pt-3 text-right text-base"><?php echo app(\App\Services\PricingService::class)->format(...[$quote['total'], $quote['currency']]); ?></td>
                    </tr>
                </tfoot>
            </table>
            <p class="mt-3 text-xs leading-relaxed text-ink-500">
                WhatsApp charges per delivered message. Messages that fail are not billed, so the final amount on the send page may be lower than this estimate.
            </p>
        </section>

        <section class="rounded-xl border border-ink-200 bg-white">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-3.5">
                <h2 class="text-base">Recipients</h2>
                <p class="num text-sm text-ink-500"><?php echo e($recipients->count()); ?> selected</p>
            </div>
            <div class="max-h-72 overflow-y-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-ink-100">
                    <?php $__currentLoopData = $recipients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="px-5 py-2.5"><?php echo e($customer->name); ?></td>
                            <td class="num py-2.5 text-ink-500">+<?php echo e($customer->phone); ?></td>
                            <td class="px-5 py-2.5 text-right text-xs <?php echo e($customer->opted_in ? 'text-ink-300' : 'text-signal-700'); ?>">
                                <?php echo e($customer->opted_in ? 'opted in' : 'no opt-in on record'); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </section>

        <details class="rounded-xl border border-ink-200 bg-white p-5">
            <summary class="cursor-pointer text-base">API payload for the first recipient</summary>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-ink-900 p-4 text-xs leading-relaxed text-ink-100"><?php echo e(json_encode($samplePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
        </details>

        <form method="POST" action="<?php echo e(route('campaigns.store')); ?>" class="flex flex-wrap items-center gap-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="name" value="<?php echo e($name); ?>">
            <input type="hidden" name="message_template_id" value="<?php echo e($template->id); ?>">
            <?php $__currentLoopData = $recipients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="customer_ids[]" value="<?php echo e($customer->id); ?>">
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <button class="rounded-lg bg-jade-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-jade-700">
                Send to <?php echo e($recipients->count()); ?> <?php echo e(Str::plural('contact', $recipients->count())); ?> · <?php echo app(\App\Services\PricingService::class)->format(...[$quote['total'], $quote['currency']]); ?>
            </button>
            <a href="<?php echo e(url()->previous()); ?>" class="text-sm text-ink-500 underline underline-offset-2">Back to selection</a>
        </form>
    </div>

    <aside>
        <div class="sticky top-6">
            <h2 class="mb-2 text-sm text-ink-500">
                Preview <?php if($sample): ?> as <?php echo e($sample->name); ?> sees it <?php endif; ?>
            </h2>
            <?php echo $__env->make('partials.phone-preview', ['components' => $template->components, 'sample' => $sample], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="mt-3 rounded-lg border border-ink-200 bg-white p-3.5 text-xs leading-relaxed text-ink-500">
                Template <span class="text-ink-900"><?php echo e($template->name); ?></span> · <?php echo e($template->language); ?> ·
                approved <?php echo e($template->approved_at?->diffForHumans() ?? 'recently'); ?>.
            </div>
        </div>
    </aside>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/campaigns/preview.blade.php ENDPATH**/ ?>