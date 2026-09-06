<?php $__env->startSection('title', 'WhatsApp settings — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'WhatsApp settings'); ?>
<?php $__env->startSection('subheading', 'Point Bhatsapp at your WhatsApp Business account so it can send on your behalf.'); ?>

<?php $__env->startSection('content'); ?>
<div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
    <form method="POST" action="<?php echo e(route('settings.whatsapp.save')); ?>" class="rounded-xl border border-ink-200 bg-white p-6">
        <?php echo csrf_field(); ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="label" class="block text-sm text-ink-700">Name this number</label>
                <input id="label" name="label" value="<?php echo e(old('label', $account->label ?? 'Primary number')); ?>" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div>
                <label for="waba_id" class="block text-sm text-ink-700">WhatsApp Business Account ID</label>
                <input id="waba_id" name="waba_id" value="<?php echo e(old('waba_id', $account->waba_id ?? '')); ?>" required
                       class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">Templates are created against this account.</p>
            </div>

            <div>
                <label for="phone_number_id" class="block text-sm text-ink-700">Phone number ID</label>
                <input id="phone_number_id" name="phone_number_id" value="<?php echo e(old('phone_number_id', $account->phone_number_id ?? '')); ?>" required
                       class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">The sender. Not the phone number itself.</p>
            </div>

            <div>
                <label for="display_phone_number" class="block text-sm text-ink-700">Display number</label>
                <input id="display_phone_number" name="display_phone_number" value="<?php echo e(old('display_phone_number', $account->display_phone_number ?? '')); ?>"
                       placeholder="+91 90000 00000"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div>
                <label for="graph_version" class="block text-sm text-ink-700">Graph API version</label>
                <input id="graph_version" name="graph_version" value="<?php echo e(old('graph_version', $account->graph_version ?? config('whatsapp.graph_version'))); ?>" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div class="sm:col-span-2">
                <label for="access_token" class="block text-sm text-ink-700">System user access token</label>
                <input id="access_token" name="access_token" type="password" autocomplete="off"
                       placeholder="<?php echo e($account?->access_token ? $account->maskedToken() : 'EAAG...'); ?>"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">Stored encrypted. Leave blank to keep the saved token.</p>
            </div>

            <div>
                <label for="app_id" class="block text-sm text-ink-700">Meta app ID</label>
                <input id="app_id" name="app_id" value="<?php echo e(old('app_id', $account->app_id ?? '')); ?>"
                       class="num mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>

            <div>
                <label for="app_secret" class="block text-sm text-ink-700">App secret</label>
                <input id="app_secret" name="app_secret" type="password" autocomplete="off" placeholder="<?php echo e($account?->app_secret ? '•••••••••••' : ''); ?>"
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
                <p class="mt-1 text-xs text-ink-500">Used to check the signature on incoming callbacks.</p>
            </div>

            <div class="sm:col-span-2">
                <label for="webhook_verify_token" class="block text-sm text-ink-700">Webhook verify token</label>
                <input id="webhook_verify_token" name="webhook_verify_token" value="<?php echo e(old('webhook_verify_token', $account->webhook_verify_token ?? $verifyToken)); ?>" required
                       class="mt-1.5 w-full rounded-lg border-ink-200 text-sm focus:border-jade-600 focus:ring-jade-600">
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-2">
            <button class="rounded-lg bg-jade-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-jade-700">Save settings</button>
            <?php if($account): ?>
                <button form="verify-form" class="rounded-lg border border-ink-200 px-4 py-2.5 text-sm hover:border-ink-300">Test connection</button>
            <?php endif; ?>
        </div>
    </form>

    <?php if($account): ?>
        <form id="verify-form" method="POST" action="<?php echo e(route('settings.whatsapp.verify')); ?>"><?php echo csrf_field(); ?></form>
    <?php endif; ?>

    <aside class="space-y-4">
        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Connection</h2>
            <?php if($account?->verified_at): ?>
                <p class="mt-2 flex items-center gap-2 text-sm text-jade-700">
                    <span class="h-2 w-2 rounded-full bg-jade-400"></span>
                    Verified <?php echo e($account->verified_at->diffForHumans()); ?>

                </p>
                <dl class="mt-3 space-y-1.5 text-sm text-ink-500">
                    <div class="flex justify-between"><dt>Number</dt><dd class="text-ink-900"><?php echo e($account->display_phone_number); ?></dd></div>
                    <div class="flex justify-between"><dt>Quality</dt><dd class="text-ink-900"><?php echo e($account->quality_rating ?? '—'); ?></dd></div>
                    <div class="flex justify-between"><dt>Send limit</dt><dd class="text-ink-900"><?php echo e($account->messaging_limit ?? '—'); ?></dd></div>
                </dl>
            <?php else: ?>
                <p class="mt-2 text-sm text-ink-500">Not verified yet. Save your credentials, then run a connection test.</p>
            <?php endif; ?>
        </div>

        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="text-base">Callback URL</h2>
            <p class="mt-2 text-sm text-ink-500">Paste this into the Webhooks section of your Meta app, subscribe to <span class="text-ink-900">messages</span> and <span class="text-ink-900">message_template_status_update</span>.</p>
            <code class="mt-3 block break-all rounded-lg bg-ink-50 px-3 py-2 text-xs text-ink-700"><?php echo e(url('/webhooks/whatsapp/'.$tenant->id)); ?></code>
        </div>

        <?php if(config('whatsapp.sandbox')): ?>
            <div class="rounded-xl border border-signal-200 bg-signal-50 p-5 text-sm text-signal-700">
                <h2 class="text-base text-signal-700">Sandbox is on</h2>
                <p class="mt-2 leading-relaxed">Graph calls are simulated, so templates approve themselves after <?php echo e(config('whatsapp.sandbox_approval_delay')); ?> seconds and sends return fake message IDs. Set <span class="font-mono text-xs">WHATSAPP_SANDBOX=false</span> to go live.</p>
            </div>
        <?php endif; ?>
    </aside>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/settings/whatsapp.blade.php ENDPATH**/ ?>