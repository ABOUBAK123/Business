<?php $__env->startSection('title', 'Réunions'); ?>
<?php $__env->startSection('page-title', 'Réunions'); ?>
<?php $__env->startSection('page-subtitle', 'Planification et suivi des réunions'); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('meetings._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
    <form method="GET" class="flex gap-2">
        <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Rechercher une réunion..."
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
        <button class="px-3 py-2 rounded-lg text-sm font-semibold bg-[#2453d6] text-white hover:bg-[#1f47bb]">Chercher</button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Titre</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Salle</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Date</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Organisateur</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Statut</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Validation</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $meetings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $meeting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $statusLabels = [
                    'planned' => 'Planifiee',
                ];
                $workflowLabels = [
                    'draft' => 'Brouillon',
                ];
            ?>
            <tr class="border-b border-gray-100">
                <td class="px-4 py-3 font-medium text-gray-800"><?php echo e($meeting->title); ?></td>
                <td class="px-4 py-3 text-gray-600"><?php echo e($meeting->room?->name); ?></td>
                <td class="px-4 py-3 text-gray-600"><?php echo e($meeting->starts_at?->format('d/m/Y H:i')); ?></td>
                <td class="px-4 py-3 text-gray-600"><?php echo e($meeting->organizer?->name); ?></td>
                <td class="px-4 py-3 text-gray-600"><?php echo e($statusLabels[$meeting->status] ?? $meeting->status); ?></td>
                <td class="px-4 py-3 text-gray-600"><?php echo e($workflowLabels[$meeting->workflow_status ?? 'draft'] ?? ($meeting->workflow_status ?? 'draft')); ?></td>
                <td class="px-4 py-3">
                    <a href="<?php echo e(route('meetings.show', $meeting)); ?>" class="text-[#2453d6] font-semibold hover:underline">Ouvrir</a>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">Aucune réunion trouvée.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if($meetings->hasPages()): ?>
<div class="mt-4"><?php echo e($meetings->links()); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\e-administration_laravel\resources\views/meetings/index.blade.php ENDPATH**/ ?>