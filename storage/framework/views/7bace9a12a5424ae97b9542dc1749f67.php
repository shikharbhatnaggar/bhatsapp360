<?php
    $nav = [
        ['route' => 'dashboard', 'label' => 'Overview', 'match' => 'dashboard'],
        ['route' => 'campaigns.index', 'label' => 'Sends', 'match' => 'campaigns.*'],
        ['route' => 'templates.index', 'label' => 'Templates', 'match' => 'templates.*'],
        ['route' => 'customers.index', 'label' => 'Contacts', 'match' => 'customers.*'],
        ['route' => 'inbox.index', 'label' => 'Replies', 'match' => 'inbox.*'],
        ['route' => 'logs.index', 'label' => 'Activity', 'match' => 'logs.*'],
    ];
?>

<?php $__env->startSection('body'); ?>
<div class="min-h-full lg:flex" x-data="{ mobileNav: false }">
    <aside class="lg:w-60 lg:shrink-0 bg-ink-900 text-ink-300 lg:min-h-screen">
        <div class="flex items-center justify-between px-5 py-4 lg:py-6">
            <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-jade-600 text-white font-semibold">B</span>
                <span class="text-white tracking-tight">Bhatsapp</span>
            </a>
            <button type="button" class="lg:hidden rounded p-2 text-ink-300 hover:text-white" @click="mobileNav = !mobileNav" aria-label="Toggle navigation">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M3 5h14v2H3V5zm0 4h14v2H3V9zm0 4h14v2H3v-2z"/></svg>
            </button>
        </div>

        <nav class="px-3 pb-4 lg:block" :class="mobileNav ? 'block' : 'hidden lg:block'">
            <?php $__currentLoopData = $nav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route($item['route'])); ?>"
                   class="block rounded-lg px-3 py-2 text-sm <?php echo e(request()->routeIs($item['match']) ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white'); ?>">
                    <?php echo e($item['label']); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <div class="my-3 border-t border-ink-700"></div>

            <a href="<?php echo e(route('settings.whatsapp')); ?>"
               class="block rounded-lg px-3 py-2 text-sm <?php echo e(request()->routeIs('settings.*') ? 'bg-ink-700 text-white' : 'hover:bg-ink-700/50 hover:text-white'); ?>">
                WhatsApp settings
            </a>

            <div class="mt-6 rounded-lg bg-ink-700/40 px-3 py-3 text-xs leading-relaxed">
                <p class="text-white"><?php echo e($tenant->name); ?></p>
                <?php if($whatsappAccount?->verified_at): ?>
                    <p class="mt-1 flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-jade-400"></span>
                        <?php echo e($whatsappAccount->display_phone_number ?: $whatsappAccount->phone_number_id); ?>

                    </p>
                <?php else: ?>
                    <p class="mt-1 text-signal-200">No number connected</p>
                <?php endif; ?>
                <?php if(config('whatsapp.sandbox')): ?>
                    <p class="mt-2 text-ink-300">Sandbox mode — Graph calls are simulated.</p>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-4">
                <?php echo csrf_field(); ?>
                <button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-ink-700/50 hover:text-white">
                    Sign out, <?php echo e(auth()->user()->name); ?>

                </button>
            </form>
        </nav>
    </aside>

    <main class="flex-1 min-w-0">
        <div class="mx-auto max-w-7xl px-5 py-7 lg:px-10 lg:py-10">
            <header class="mb-7 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl tracking-tight"><?php echo $__env->yieldContent('heading'); ?></h1>
                    <?php if (! empty(trim($__env->yieldContent('subheading')))): ?>
                        <p class="mt-1 text-sm text-ink-500"><?php echo $__env->yieldContent('subheading'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2"><?php echo $__env->yieldContent('actions'); ?></div>
            </header>

            <?php if(session('status')): ?>
                <div class="mb-5 rounded-lg border border-jade-200 bg-jade-50 px-4 py-3 text-sm text-jade-700"><?php echo e(session('status')); ?></div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="mb-5 rounded-lg border border-alert-200 bg-alert-50 px-4 py-3 text-sm text-alert-700"><?php echo e(session('error')); ?></div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="mb-5 rounded-lg border border-alert-200 bg-alert-50 px-4 py-3 text-sm text-alert-700">
                    <p>Fix the following before continuing:</p>
                    <ul class="mt-1.5 list-disc pl-5 space-y-0.5">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/layouts/app.blade.php ENDPATH**/ ?>