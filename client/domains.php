<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/domain-check.php';

$cid = (int) get_client_id();
$rows = domain_client_requests($conn, $cid);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Domain requests</h1>
    <p class="text-slate-500 text-sm">A request stays here until the team registers the name and marks it active. <a class="text-brand-400" href="../domain.php">Check another name</a></p>
</div>
<?php if (!$rows): ?>
    <div class="dash-panel"><div class="p-6 text-slate-400 text-sm">No domain request yet.</div></div>
<?php endif; ?>
<?php foreach ($rows as $row): ?>
    <?php $status = (string) $row['status']; ?>
    <article class="dash-panel mb-4">
        <div class="p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <h2 class="font-heading font-semibold text-white text-lg"><?= e($row['domain_name']) ?></h2>
                <span class="text-sm <?= $status === 'active' ? 'text-green-400' : ($status === 'declined' ? 'text-red-300' : 'text-yellow-300') ?>"><?= $status === 'requested' ? 'Waiting for registration' : e(ucfirst($status)) ?></span>
            </div>
            <ol class="domain-steps">
                <li class="is-done">Name checked as available</li>
                <li class="is-done">Request sent<?= $row['document_path'] !== '' ? ' with the .com.np document' : '' ?></li>
                <li class="<?= $status === 'requested' ? 'is-current' : ($status === 'active' ? 'is-done' : '') ?>">The team registers this name at the registry</li>
                <li class="<?= $status === 'active' ? 'is-done' : '' ?>">Active</li>
            </ol>
            <?php if ($status === 'declined' && $row['admin_note'] !== ''): ?>
                <p class="text-yellow-200 text-sm mt-4"><?= e($row['admin_note']) ?></p>
            <?php endif; ?>
            <?php if ($row['document_path'] !== ''): ?>
                <p class="mt-4"><a class="text-brand-400 text-sm" href="domain-file.php?id=<?= (int) $row['id'] ?>">View the attached document</a></p>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
