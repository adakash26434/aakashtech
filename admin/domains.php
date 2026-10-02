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
        $notice = $decision === 'active' ? 'Marked active. A paid year now shows in the client account and renews from the wallet.' : 'The request was declined. If it was already paid, that amount is back in the wallet.';
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
    <p class="text-slate-500 text-sm">Register a paid name yourself at the registry, then mark it active. That starts the year on the client account. Declining a paid request returns the amount to the wallet. The site does not register the name.</p>
</div>
<?php if ($notice): ?><div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div><?php endif; ?>
<?php if (!$rows): ?><div class="dash-panel"><div class="p-6 text-slate-400 text-sm">No domain request yet.</div></div><?php endif; ?>
<?php foreach ($rows as $row): ?>
    <?php
    $price = isset($row['price']) ? (float) $row['price'] : 0;
    $ready = $row['status'] === 'paid' || ($row['status'] === 'requested' && $price <= 0);
    $fileNote = $row['document_path'] !== '' ? domain_registry_file_note((int) $row['client_id'], $row['document_path']) : '';
    ?>
    <article class="dash-panel mb-4">
        <div class="p-6">
            <div class="flex flex-wrap justify-between gap-3 mb-3">
                <h2 class="font-heading font-semibold text-white text-lg"><?= e($row['domain_name']) ?></h2>
                <span class="text-sm text-slate-300"><?php
                    if ($row['status'] === 'requested' && $price > 0) {
                        echo 'Waiting for payment';
                    } elseif ($row['status'] === 'paid') {
                        echo 'Paid';
                    } else {
                        echo e(ucfirst((string) $row['status']));
                    }
                ?></span>
            </div>
            <p class="text-slate-300 text-sm mb-2"><?= e($row['account_name']) ?> · <?= e($row['email']) ?><?php if (!empty($row['phone'])): ?> · <?= e($row['phone']) ?><?php endif; ?></p>
            <p class="text-slate-400 text-sm mb-2"><?= (isset($row['holder_kind']) && $row['holder_kind'] === 'organization') ? 'Organization' : 'Individual' ?> · <?= e($row['holder_name']) ?></p>
            <?php if (!empty($row['holder_address'])): ?>
                <p class="text-slate-400 text-sm mb-2"><?= e($row['holder_address']) ?></p>
            <?php endif; ?>
            <?php if ($price > 0): ?>
                <p class="text-slate-300 text-sm mb-3">Yearly bill <?= e(billing_money_label($price)) ?>, already including 13% VAT.</p>
            <?php endif; ?>
            <?php if ($row['tld'] === 'com.np'): ?>
                <p class="text-slate-400 text-sm mb-3">Register this name at <a class="text-brand-400" href="https://register.com.np/" target="_blank" rel="noopener">register.com.np</a> with the holder, address, and document below.</p>
            <?php else: ?>
                <p class="text-slate-400 text-sm mb-3">Register this .com name at the registrar you use, with the holder and address below.</p>
            <?php endif; ?>
            <?php if ($row['document_path'] !== ''): ?>
                <p class="mb-2"><a class="text-brand-400 text-sm" href="domain-file.php?id=<?= (int) $row['id'] ?>">Open the .com.np document</a></p>
                <?php if ($fileNote !== ''): ?><p class="text-yellow-200 text-sm mb-3"><?= e($fileNote) ?></p><?php endif; ?>
            <?php endif; ?>
            <?php if ($row['status'] === 'requested' && $price > 0): ?>
                <p class="text-slate-400 text-sm mb-3">Waiting for the client to pay the yearly bill. Decline it if this name should be released.</p>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="note-<?= (int) $row['id'] ?>">Why it was not registered</label>
                        <textarea id="note-<?= (int) $row['id'] ?>" name="admin_note" rows="2" maxlength="400" class="form-input resize-none"></textarea>
                    </div>
                    <button type="submit" name="decision" value="declined" class="px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300">Decline</button>
                </form>
            <?php elseif ($ready): ?>
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
