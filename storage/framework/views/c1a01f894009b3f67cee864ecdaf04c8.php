<?php $__env->startSection('title', 'Contacts — Bhatsapp'); ?>
<?php $__env->startSection('heading', 'Contacts'); ?>
<?php $__env->startSection('subheading', 'Customers and leads you can message.'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('customers.create')); ?>" class="rounded-lg bg-jade-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-jade-700">Add contact</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="<?php echo e(request('q')); ?>" placeholder="Search name, phone or email"
           class="w-64 rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
    <select name="type" class="rounded-lg border-ink-200 py-2 text-sm focus:border-jade-600 focus:ring-jade-600">
        <option value="">Everyone</option>
        <option value="customer" <?php if(request('type') === 'customer'): echo 'selected'; endif; ?>>Customers</option>
        <option value="lead" <?php if(request('type') === 'lead'): echo 'selected'; endif; ?>>Leads</option>
    </select>
    <button class="rounded-lg border border-ink-200 bg-white px-3.5 py-2 text-sm hover:border-ink-300">Search</button>
</form>

<div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-ink-100 text-left text-ink-500">
            <tr>
                <th class="px-5 py-3 font-normal">Name</th>
                <th class="px-3 py-3 font-normal">WhatsApp number</th>
                <th class="px-3 py-3 font-normal">Type</th>
                <th class="px-3 py-3 font-normal">Reply window</th>
                <th class="px-3 py-3 font-normal">Added</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-ink-50">
                <td class="px-5 py-3">
                    <?php echo e($customer->name); ?>

                    <?php if($customer->email): ?><p class="text-xs text-ink-500"><?php echo e($customer->email); ?></p><?php endif; ?>
                </td>
                <td class="num px-3 py-3">+<?php echo e($customer->phone); ?></td>
                <td class="px-3 py-3 text-ink-500"><?php echo e(ucfirst($customer->type)); ?></td>
                <td class="px-3 py-3">
                    <?php if($customer->serviceWindowOpen()): ?>
                        <span class="text-jade-700">Open · closes <?php echo e($customer->last_inbound_at->addDay()->diffForHumans()); ?></span>
                    <?php else: ?>
                        <span class="text-ink-300">Closed</span>
                    <?php endif; ?>
                </td>
                <td class="px-3 py-3 text-ink-500"><?php echo e($customer->created_at->format('d M Y')); ?></td>
                <td class="px-5 py-3 text-right">
                    <a href="<?php echo e(route('customers.edit', $customer)); ?>" class="text-jade-700 underline underline-offset-2">Edit</a>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="px-5 py-10 text-center text-ink-500">
                    No contacts yet. <a href="<?php echo e(route('customers.create')); ?>" class="text-jade-700 underline underline-offset-2">Add your first one</a>, or let inbound replies create them for you.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4"><?php echo e($customers->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\projects\bhatsapps\resources\views/customers/index.blade.php ENDPATH**/ ?>