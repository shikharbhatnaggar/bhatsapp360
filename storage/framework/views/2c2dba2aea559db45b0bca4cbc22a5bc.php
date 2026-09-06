<?php $__env->startSection('title', 'Templates — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'Templates'); ?>
<?php $__env->startSection('subheading', 'Every template WhatsApp has reviewed, and the ones still waiting.'); ?>

<?php $__env->startSection('actions'); ?>
    <form method="POST" action="<?php echo e(route('templates.refresh')); ?>">
        <?php echo csrf_field(); ?>
        <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Check review status</button>
    </form>
    <a href="<?php echo e(route('templates.create')); ?>" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">New template</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="<?php echo e(request('q')); ?>" placeholder="Search by name"
           class="w-56 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    <select name="category" class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
        <option value="">All categories</option>
        <?php $__currentLoopData = config('whatsapp.categories'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($value); ?>" <?php if(request('category') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select name="status" class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
        <option value="">Any status</option>
        <?php $__currentLoopData = config('whatsapp.statuses'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($status); ?>" <?php if(request('status') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst(strtolower($status))); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Filter</button>
</form>

<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Name</th>
                <th class="px-3 py-3 font-normal">Category</th>
                <th class="px-3 py-3 font-normal">Status</th>
                <th class="px-3 py-3 font-normal">Version</th>
                <th class="px-3 py-3 font-normal">Last change</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        <?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-ink-50">
                <td class="px-5 py-3">
                    <a href="<?php echo e(route('templates.show', $template)); ?>" class="hover:underline underline-offset-2"><?php echo e($template->name); ?></a>
                    <p class="text-xs text-ink-500">
                        <?php echo e($template->language); ?><?php if($template->isCarousel()): ?> · media card carousel <?php endif; ?>
                    </p>
                </td>
                <td class="px-3 py-3 text-ink-500"><?php echo e(ucfirst(strtolower($template->category))); ?></td>
                <td class="px-3 py-3">
                    <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs <?php echo e($template->statusColor()); ?>">
                        <?php echo e(ucfirst(strtolower($template->status))); ?>

                    </span>
                    <?php if($template->rejected_reason): ?>
                        <p class="mt-1 text-xs text-alert-600"><?php echo e($template->rejected_reason); ?></p>
                    <?php endif; ?>
                </td>
                <td class="num px-3 py-3 text-ink-500">v<?php echo e($template->version); ?></td>
                <td class="px-3 py-3 text-ink-500"><?php echo e($template->updated_at->diffForHumans()); ?></td>
                <td class="px-5 py-3 text-right">
                    <?php if($template->isSendable()): ?>
                        <a href="<?php echo e(route('campaigns.create', ['template_id' => $template->id])); ?>" class="text-jade-700 underline underline-offset-2">Send</a>
                    <?php else: ?>
                        <a href="<?php echo e(route('templates.edit', $template)); ?>" class="text-jade-700 underline underline-offset-2">Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="px-5 py-10 text-center text-ink-500">
                    No templates yet. <a href="<?php echo e(route('templates.create')); ?>" class="text-jade-700 underline underline-offset-2">Build your first one</a> — WhatsApp reviews it before you can send.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4"><?php echo e($templates->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/templates/index.blade.php ENDPATH**/ ?>