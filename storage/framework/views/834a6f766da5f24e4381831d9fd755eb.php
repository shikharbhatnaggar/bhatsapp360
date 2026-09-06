<?php $__env->startSection('title', 'Sends — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'Sends'); ?>
<?php $__env->startSection('subheading', 'Every batch you have sent, with how it landed.'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('campaigns.create')); ?>" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">New send</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Send</th>
                <th class="px-3 py-3 font-normal">Template</th>
                <th class="px-3 py-3 font-normal">Status</th>
                <th class="px-3 py-3 text-right font-normal">Recipients</th>
                <th class="px-3 py-3 text-right font-normal">Delivered</th>
                <th class="px-3 py-3 text-right font-normal">Failed</th>
                <th class="px-5 py-3 text-right font-normal">Cost</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        <?php $__empty_1 = true; $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-ink-50">
                <td class="px-5 py-3">
                    <a href="<?php echo e(route('campaigns.show', $campaign)); ?>" class="hover:underline underline-offset-2"><?php echo e($campaign->name); ?></a>
                    <p class="text-xs text-ink-500"><?php echo e($campaign->created_at->format('d M Y, g:i A')); ?></p>
                </td>
                <td class="px-3 py-3 text-ink-500"><?php echo e($campaign->template?->name ?? '—'); ?></td>
                <td class="px-3 py-3">
                    <span class="text-<?php echo e($campaign->status === 'completed' ? 'jade-700' : ($campaign->status === 'failed' ? 'alert-600' : 'signal-700')); ?>">
                        <?php echo e(ucfirst($campaign->status)); ?>

                    </span>
                </td>
                <td class="num px-3 py-3 text-right"><?php echo e(number_format($campaign->recipients_count)); ?></td>
                <td class="num px-3 py-3 text-right text-jade-700"><?php echo e(number_format($campaign->delivered_count)); ?></td>
                <td class="num px-3 py-3 text-right <?php echo e($campaign->failed_count ? 'text-alert-600' : 'text-ink-300'); ?>"><?php echo e(number_format($campaign->failed_count)); ?></td>
                <td class="num px-5 py-3 text-right"><?php echo app(\App\Services\PricingService::class)->format(...[(float) ($campaign->actual_cost ?: $campaign->estimated_cost), $campaign->currency]); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-ink-500">
                    Nothing sent yet. <a href="<?php echo e(route('campaigns.create')); ?>" class="text-jade-700 underline underline-offset-2">Start your first send</a>.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4"><?php echo e($campaigns->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/campaigns/index.blade.php ENDPATH**/ ?>