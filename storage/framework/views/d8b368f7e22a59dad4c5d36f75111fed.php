<?php $__env->startSection('title', 'Sign in — Bhatsapp'); ?>

<?php $__env->startSection('form'); ?>
    <h1 class="text-2xl tracking-tight">Sign in</h1>
    <p class="mt-1.5 text-sm text-ink-500">Pick up where your workspace left off.</p>

    <?php if($errors->any()): ?>
        <div class="mt-5 rounded-lg border border-alert-200 bg-alert-50 px-3.5 py-2.5 text-sm text-alert-700">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('login')); ?>" class="mt-6 space-y-4">
        <?php echo csrf_field(); ?>
        <div>
            <label for="email" class="block text-sm text-ink-700">Work email</label>
            <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" required autofocus
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <div>
            <label for="password" class="block text-sm text-ink-700">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-500">
            <input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-jade-600 focus:ring-jade-600">
            Keep me signed in
        </label>
        <button class="w-full rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700 focus:outline-none focus:ring-2 focus:ring-jade-600 focus:ring-offset-2">
            Sign in
        </button>
    </form>

    <p class="mt-6 text-sm text-ink-500">
        New here? <a href="<?php echo e(route('register')); ?>" class="text-jade-700 underline underline-offset-2">Create a workspace</a>
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/auth/login.blade.php ENDPATH**/ ?>