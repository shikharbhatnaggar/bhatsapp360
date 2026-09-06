<?php $__env->startSection('title', 'Activity — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'Activity'); ?>
<?php $__env->startSection('subheading', 'Every contact change, template review, send and delivery receipt.'); ?>

<?php $__env->startSection('content'); ?>
<form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
    <div>
        <label for="event" class="block text-xs text-ink-500">Area</label>
        <select id="event" name="event" class="mt-1 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
            <option value="">Everything</option>
            <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $total): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($group); ?>" <?php if(request('event') === $group): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_', ' ', $group))); ?> (<?php echo e($total); ?>)</option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <div>
        <label for="from" class="block text-xs text-ink-500">From</label>
        <input id="from" type="date" name="from" value="<?php echo e(request('from')); ?>" class="mt-1 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    </div>
    <div>
        <label for="to" class="block text-xs text-ink-500">To</label>
        <input id="to" type="date" name="to" value="<?php echo e(request('to')); ?>" class="mt-1 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    </div>
    <input name="q" value="<?php echo e(request('q')); ?>" placeholder="Search descriptions"
           class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Filter</button>
</form>

<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">When</th>
                <th class="px-3 py-3 font-normal">Event</th>
                <th class="px-3 py-3 font-normal">What happened</th>
                <th class="px-3 py-3 font-normal">Who</th>
                <th class="px-5 py-3 font-normal">Subject</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="align-top hover:bg-ink-50">
                <td class="whitespace-nowrap px-5 py-3 text-ink-500"><?php echo e($log->created_at->format('d M, H:i:s')); ?></td>
                <td class="px-3 py-3">
                    <code class="rounded bg-ink-50 px-1.5 py-0.5 text-xs text-ink-700"><?php echo e($log->event); ?></code>
                </td>
                <td class="px-3 py-3">
                    <?php echo e($log->description); ?>

                    <?php if($log->properties): ?>
                        <details class="mt-1">
                            <summary class="cursor-pointer text-xs text-ink-500">details</summary>
                            <pre class="mt-1 max-w-lg overflow-x-auto rounded bg-ink-50 p-2 text-[11px] leading-relaxed"><?php echo e(json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                        </details>
                    <?php endif; ?>
                </td>
                <td class="px-3 py-3 text-ink-500"><?php echo e($log->user?->name ?? 'System'); ?></td>
                <td class="px-5 py-3 text-ink-500"><?php echo e($log->subject_type); ?><?php echo e($log->subject_id ? ' #'.$log->subject_id : ''); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5" class="px-5 py-10 text-center text-ink-500">No activity matches these filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4"><?php echo e($logs->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/logs/index.blade.php ENDPATH**/ ?>