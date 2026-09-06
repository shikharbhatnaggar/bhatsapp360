<?php $__env->startSection('title', $campaign->name.' — Bhatsapp'); ?>
<?php $__env->startSection('heading', $campaign->name); ?>
<?php $__env->startSection('subheading', 'Template '.($campaign->template?->name ?? '—').' · started '.($campaign->started_at?->format('d M Y, g:i A') ?? 'not yet')); ?>

<?php $__env->startSection('actions'); ?>
    <?php if(config('whatsapp.sandbox') && in_array($campaign->status, ['sending', 'completed'])): ?>
        <form method="POST" action="<?php echo e(route('sandbox.advance', $campaign)); ?>">
            <?php echo csrf_field(); ?>
            <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Simulate delivery receipts</button>
        </form>
    <?php endif; ?>
    <a href="<?php echo e(route('campaigns.create')); ?>" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">New send</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $cards = [
        ['label' => 'Queued', 'value' => $counts['queued'], 'tone' => 'text-ink-500'],
        ['label' => 'Sent', 'value' => $counts['sent'], 'tone' => 'text-ink-900'],
        ['label' => 'Delivered', 'value' => $counts['delivered'], 'tone' => 'text-jade-700'],
        ['label' => 'Read', 'value' => $counts['read'], 'tone' => 'text-jade-700'],
        ['label' => 'Failed', 'value' => $counts['failed'], 'tone' => 'text-alert-600'],
    ];
?>

<section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
    <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="rounded-xl border border-ink-200 bg-white p-4">
            <p class="text-sm text-ink-500"><?php echo e($card['label']); ?></p>
            <p class="num mt-1.5 text-2xl tracking-tight <?php echo e($card['tone']); ?>"><?php echo e(number_format($card['value'])); ?></p>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <div class="rounded-xl border border-ink-200 bg-white p-4">
        <p class="text-sm text-ink-500">Billed</p>
        <p class="num mt-1.5 text-2xl tracking-tight"><?php echo app(\App\Services\PricingService::class)->format(...[(float) ($campaign->actual_cost ?: $campaign->estimated_cost), $campaign->currency]); ?></p>
        <p class="mt-1 text-xs text-ink-500">est. <?php echo app(\App\Services\PricingService::class)->format(...[(float) $campaign->estimated_cost, $campaign->currency]); ?></p>
    </div>
</section>

<div class="mt-4 flex flex-wrap gap-2 text-sm">
    <a href="<?php echo e(route('campaigns.show', $campaign)); ?>"
       class="rounded-lg border px-3 py-1.5 <?php echo e(request('status') ? 'border-ink-200 bg-white' : 'border-jade-600 bg-jade-50 text-jade-700'); ?>">All</a>
    <?php $__currentLoopData = ['queued', 'sent', 'delivered', 'read', 'failed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('campaigns.show', ['campaign' => $campaign, 'status' => $status])); ?>"
           class="rounded-lg border px-3 py-1.5 <?php echo e(request('status') === $status ? 'border-jade-600 bg-jade-50 text-jade-700' : 'border-ink-200 bg-white'); ?>">
            <?php echo e(ucfirst($status)); ?>

        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="mt-3 overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Recipient</th>
                <th class="px-3 py-3 font-normal">Status</th>
                <th class="px-3 py-3 font-normal">Receipt trail</th>
                <th class="px-3 py-3 font-normal">Message ID</th>
                <th class="px-5 py-3 text-right font-normal">Price</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="align-top hover:bg-ink-50">
                <td class="px-5 py-3">
                    <?php echo e($message->customer?->name ?? 'Unknown'); ?>

                    <p class="num text-xs text-ink-500">+<?php echo e($message->customer?->phone); ?></p>
                    <p class="mt-1 max-w-md text-xs text-ink-500"><?php echo e(Str::limit($message->body_preview, 90)); ?></p>
                </td>
                <td class="px-3 py-3">
                    <?php
                        $tone = match ($message->status) {
                            'delivered', 'read' => 'text-jade-700 bg-jade-50 border-jade-200',
                            'failed' => 'text-alert-700 bg-alert-50 border-alert-200',
                            'queued' => 'text-ink-500 bg-ink-50 border-ink-200',
                            default => 'text-signal-700 bg-signal-50 border-signal-200',
                        };
                    ?>
                    <span class="inline-flex rounded-md border px-2 py-0.5 text-xs <?php echo e($tone); ?>"><?php echo e(ucfirst($message->status)); ?></span>
                    <?php if($message->error): ?>
                        <p class="mt-1 text-xs text-alert-600"><?php echo e(data_get($message->error, 'message', data_get($message->error, 'title'))); ?></p>
                    <?php endif; ?>
                </td>
                <td class="px-3 py-3 text-xs text-ink-500">
                    <?php $__empty_2 = true; $__currentLoopData = $message->statusEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                        <span class="mr-2 whitespace-nowrap"><?php echo e($event->status); ?> <?php echo e($event->occurred_at->format('H:i:s')); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                        <span class="text-ink-300">no receipts yet</span>
                    <?php endif; ?>
                </td>
                <td class="px-3 py-3 text-xs text-ink-500"><?php echo e(Str::limit($message->wamid, 22)); ?></td>
                <td class="num px-5 py-3 text-right"><?php echo app(\App\Services\PricingService::class)->format(...[(float) $message->price, $message->currency]); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5" class="px-5 py-10 text-center text-ink-500">No messages match this filter.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4"><?php echo e($messages->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/campaigns/show.blade.php ENDPATH**/ ?>