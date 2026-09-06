<?php $__env->startSection('title', 'Replies — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'Replies'); ?>
<?php $__env->startSection('subheading', 'Messages customers have sent back to your number.'); ?>

<?php $__env->startSection('actions'); ?>
    <?php if(config('whatsapp.sandbox')): ?>
        <form method="POST" action="<?php echo e(route('sandbox.inbound')); ?>">
            <?php echo csrf_field(); ?>
            <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Simulate an incoming reply</button>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="grid gap-4 overflow-hidden rounded-xl border border-ink-200 bg-white lg:grid-cols-[280px_1fr] lg:gap-0">
    <div class="lg:border-r lg:border-ink-100">
        <p class="border-b border-ink-100 px-4 py-3 text-sm text-ink-500"><?php echo e($threads->count()); ?> conversations</p>
        <ul class="max-h-[32rem] divide-y divide-ink-100 overflow-y-auto">
            <?php $__empty_1 = true; $__currentLoopData = $threads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $thread): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <li>
                    <a href="<?php echo e(route('inbox.index', ['customer' => $thread->id])); ?>"
                       class="block px-4 py-3 hover:bg-ink-50 <?php echo e($active?->id === $thread->id ? 'bg-jade-50' : ''); ?>">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm"><?php echo e($thread->name); ?></span>
                            <span class="shrink-0 text-xs text-ink-500"><?php echo e($thread->last_inbound_at?->diffForHumans(null, true)); ?></span>
                        </div>
                        <p class="num mt-0.5 text-xs text-ink-500">+<?php echo e($thread->phone); ?></p>
                        <?php if($thread->serviceWindowOpen()): ?>
                            <p class="mt-1 text-xs text-jade-700">Reply window open</p>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="px-4 py-8 text-center text-sm text-ink-500">
                    No replies yet. They land here the moment a customer writes back.
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="flex min-h-[32rem] flex-col">
        <?php if($active): ?>
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-3">
                <div>
                    <p class="text-sm"><?php echo e($active->name); ?></p>
                    <p class="num text-xs text-ink-500">+<?php echo e($active->phone); ?> · <?php echo e(ucfirst($active->type)); ?></p>
                </div>
                <a href="<?php echo e(route('customers.edit', $active)); ?>" class="text-sm text-jade-700 underline underline-offset-2">Contact details</a>
            </div>

            <div class="chat-canvas flex-1 space-y-2 overflow-y-auto p-5">
                <?php $__currentLoopData = $conversation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex <?php echo e($message->direction === 'inbound' ? 'justify-start' : 'justify-end'); ?>">
                        <div class="max-w-[70%] rounded-lg px-3 py-2 text-sm shadow-sm <?php echo e($message->direction === 'inbound' ? 'bg-white' : 'bg-[#D9FDD3]'); ?>">
                            <p class="whitespace-pre-line leading-relaxed"><?php echo e($message->body_preview); ?></p>
                            <p class="mt-1 text-right text-[11px] text-ink-500">
                                <?php echo e($message->created_at->format('d M, g:i A')); ?>

                                <?php if($message->direction === 'outbound'): ?> · <?php echo e($message->status); ?> <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="border-t border-ink-100 p-4">
                <?php if($active->serviceWindowOpen()): ?>
                    <form method="POST" action="<?php echo e(route('inbox.reply', $active)); ?>" class="flex gap-2">
                        <?php echo csrf_field(); ?>
                        <input name="body" required placeholder="Write a reply" autocomplete="off"
                               class="flex-1 rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                        <button class="rounded-lg bg-jade-600 px-4 py-2 text-sm font-medium text-white hover:bg-jade-700">Send</button>
                    </form>
                    <p class="mt-2 text-xs text-ink-500">
                        Free-form replies are allowed until <?php echo e($active->last_inbound_at->addDay()->format('d M, g:i A')); ?> — 24 hours after their last message.
                    </p>
                <?php else: ?>
                    <p class="rounded-lg bg-signal-50 px-3.5 py-2.5 text-sm text-signal-700">
                        The 24-hour reply window has closed. Send an approved template to reopen the conversation.
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid flex-1 place-items-center p-10 text-center text-sm text-ink-500">
                <p>Pick a conversation on the left to read it.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/inbox/index.blade.php ENDPATH**/ ?>