<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
// The audit log itself is for the owner.
if (admin_is_staff()) {
    header('Location: billing.php');
    exit;
}
$rows = audit_recent($conn, 200);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Audit log</h1>
    <p class="text-slate-500 text-sm">The last 200 changes made from the admin panel: who, what, which record, and from which network address. Values such as passwords are never stored here.</p>
</div>
<div class="dash-panel overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="text-slate-400 text-xs uppercase">
            <tr>
                <th scope="col" class="px-4 py-3">When</th>
                <th scope="col" class="px-4 py-3">Who</th>
                <th scope="col" class="px-4 py-3">Action</th>
                <th scope="col" class="px-4 py-3">Record</th>
                <th scope="col" class="px-4 py-3">Address</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="px-4 py-2 whitespace-nowrap"><?= e($row['created_at']) ?></td>
                    <td class="px-4 py-2"><?= e($row['admin_name'] ?: 'Admin') ?> <span class="text-slate-500 text-xs">(<?= e($row['admin_role'] ?: 'admin') ?>)</span></td>
                    <td class="px-4 py-2 font-mono text-xs"><?= e($row['action']) ?></td>
                    <td class="px-4 py-2 text-xs"><?= e($row['target']) ?></td>
                    <td class="px-4 py-2 text-xs"><?= e($row['ip']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="5" class="px-4 py-6 text-slate-500">No admin changes recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
