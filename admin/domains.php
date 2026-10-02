<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/domain-check.php';

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
    $decision = isset($_POST['decision']) ? (string) $_POST['decision'] : '';
    $error = domain_mark_request($conn, $requestId, $decision, isset($_POST['admin_note']) ? $_POST['admin_note'] : '');
    if ($error === '') {
        $notice = $decision === 'active' ? 'Marked active. The client can see that status in the portal.' : 'The request was declined.';
    }
}
$rows = array();
try {
    $rows = domain_request_queue($conn);
} catch (Throwable $exception) {
    error_log('Domain requests could not be listed.');
}
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Domain requests</h1>
    <p class="text-slate-500 text-sm">Register the name yourself at the registry, then mark the request active. The site does not register it.</p>
</div>
<?php if ($notice): ?><div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div><?php endif; ?>
<?php if (!$rows): ?><div class="dash-panel"><div class="p-6 text-slate-400 text-sm">No domain request yet.</div></div><?php endif; ?>
<?php foreach ($rows as $row): ?>
    <article class="dash-panel mb-4">
        <div class="p-6">
            <div class="flex flex-wrap justify-between gap-3 mb-3">
                <h2 class="font-heading font-semibold text-white text-lg"><?= e($row['domain_name']) ?></h2>
                <span class="text-sm text-slate-300"><?= e(ucfirst((string) $row['status'])) ?></span>
            </div>
            <p class="text-slate-300 text-sm mb-2"><?= e($row['account_name']) ?> · <?= e($row['email']) ?><?php if (!empty($row['phone'])): ?> · <?= e($row['phone']) ?><?php endif; ?></p>
            <p class="text-slate-400 text-sm mb-3"><?= (isset($row['holder_kind']) && $row['holder_kind'] === 'organization') ? 'Organization' : 'Individual' ?> · <?= e($row['holder_name']) ?></p>
            <?php if ($row['document_path'] !== ''): ?>
                <p class="mb-4"><a class="text-brand-400 text-sm" href="domain-file.php?id=<?= (int) $row['id'] ?>">Open the .com.np document</a></p>
            <?php endif; ?>
            <?php if ($row['status'] === 'requested'): ?>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" name="decision" value="active" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Mark active</button>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="note-<?= (int) $row['id'] ?>">Why it was not registered</label>
                        <textarea id="note-<?= (int) $row['id'] ?>" name="admin_note" rows="2" maxlength="400" class="form-input resize-none"></textarea>
                    </div>
                    <button type="submit" name="decision" value="declined" class="px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300">Decline</button>
                </form>
            <?php elseif ($row['admin_note'] !== ''): ?>
                <p class="text-slate-400 text-sm"><?= e($row['admin_note']) ?></p>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
