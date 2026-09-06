<?php $__env->startSection('title', $template->name.' — Bhatsapp'); ?>
<?php $__env->startSection('heading', $template->name); ?>
<?php $__env->startSection('subheading', ucfirst(strtolower($template->category)).' · '.$template->language.' · version '.$template->version); ?>

<?php $__env->startSection('actions'); ?>
    <?php if($template->isSendable()): ?>
        <a href="<?php echo e(route('campaigns.create', ['template_id' => $template->id])); ?>"
           class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">Send this</a>
    <?php endif; ?>
    <a href="<?php echo e(route('templates.edit', $template)); ?>" class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Edit</a>
    <?php if(in_array($template->status, ['DRAFT', 'REJECTED'])): ?>
        <form method="POST" action="<?php echo e(route('templates.submit', $template)); ?>">
            <?php echo csrf_field(); ?>
            <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Submit for review</button>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="grid gap-4 lg:grid-cols-[1fr_360px]">
    <div class="space-y-4">
        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-md border px-2.5 py-1 text-sm <?php echo e($template->statusColor()); ?>">
                    <?php echo e(ucfirst(strtolower($template->status))); ?>

                </span>
                <?php if($template->submitted_at): ?>
                    <span class="text-sm text-ink-500">Submitted <?php echo e($template->submitted_at->diffForHumans()); ?></span>
                <?php endif; ?>
                <?php if($template->approved_at): ?>
                    <span class="text-sm text-ink-500">Approved <?php echo e($template->approved_at->diffForHumans()); ?></span>
                <?php endif; ?>
                <?php if($template->whatsapp_template_id): ?>
                    <span class="num text-sm text-ink-500">Meta ID <?php echo e($template->whatsapp_template_id); ?></span>
                <?php endif; ?>
            </div>

            <?php if($template->status === 'PENDING'): ?>
                <p class="mt-4 rounded-lg bg-signal-50 px-3.5 py-2.5 text-sm text-signal-700">
                    Waiting on WhatsApp. Use “Check review status” on the templates list to pull the decision.
                </p>
            <?php endif; ?>
            <?php if($template->rejected_reason): ?>
                <p class="mt-4 rounded-lg bg-alert-50 px-3.5 py-2.5 text-sm text-alert-700">
                    WhatsApp rejected this: <?php echo e($template->rejected_reason); ?>

                </p>
            <?php endif; ?>
        </div>

        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Review history</h2>
            <p class="mt-1 text-sm text-ink-500">Every edit is submitted to WhatsApp as a new approval event.</p>
            <ol class="mt-4 space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $template->versions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="flex gap-3 border-l-2 border-ink-100 pl-4">
                        <div class="min-w-0">
                            <p class="text-sm">
                                Version <?php echo e($version->version); ?> — <?php echo e($version->action); ?>

                                <span class="text-ink-500">· <?php echo e(strtolower($version->status)); ?></span>
                            </p>
                            <p class="text-xs text-ink-500">
                                <?php echo e($version->created_at->format('d M Y, g:i A')); ?>

                                <?php if($version->submitter): ?> · <?php echo e($version->submitter->name); ?> <?php endif; ?>
                            </p>
                            <?php if($version->review_note): ?>
                                <p class="mt-1 text-xs text-alert-600"><?php echo e($version->review_note); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="text-sm text-ink-500">Not submitted yet.</li>
                <?php endif; ?>
            </ol>
        </div>

        <details class="rounded-xl border border-ink-200 bg-white p-5">
            <summary class="cursor-pointer text-base">Component JSON sent to WhatsApp</summary>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-ink-900 p-4 text-xs leading-relaxed text-ink-100"><?php echo e(json_encode($template->components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
        </details>
    </div>

    <aside>
        <div class="sticky top-6">
            <h2 class="mb-2 text-sm text-ink-500">Preview</h2>
            <?php echo $__env->make('partials.phone-preview', ['components' => $template->components], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </aside>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/templates/show.blade.php ENDPATH**/ ?>