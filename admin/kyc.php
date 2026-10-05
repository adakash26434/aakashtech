<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/kyc-view.php';

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $clientId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
    $decision = isset($_POST['decision']) ? (string) $_POST['decision'] : '';
    $error = billing_kyc_decide($conn, $clientId, $decision, isset($_POST['admin_note']) ? $_POST['admin_note'] : '');
    if ($error === '') {
        $notice = $decision === 'approve' ? 'Identity approved. The client can no longer edit it.' : 'Sent back to the client with your note. They can update it and send it again.';
    }
}
$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$tab = isset($_GET['tab']) && in_array($_GET['tab'], array('pending', 'approved', 'rejected', 'all'), true) ? (string) $_GET['tab'] : 'pending';
$counts = billing_kyc_counts($conn);
if ($tab === 'pending' && $counts['pending'] === 0 && !isset($_GET['tab']) && $counts['all'] > 0) {
    $tab = 'all';
}
$rows = array();
try {
    $rows = billing_kyc_queue($conn, $find, $tab === 'all' ? '' : $tab);
} catch (Throwable $exception) {
    error_log('Identity queue could not be loaded: ' . $exception->getMessage());
}
$reasons = array(
    'The photo of the document is blurry. Please take it again in good light.',
    'Part of the document is cut off. All four corners must be visible.',
    'The name or number you typed does not match the document.',
    'The document has expired. Please use one that is valid today.',
    'The registration or PAN certificate is missing or unreadable.',
    'Your face photo is unclear. Please take a clear photo without sunglasses.'
);
?>
<div class="mb-6">
    <h1 class="text-2xl font-heading font-bold text-white">Identity checks</h1>
    <p class="text-slate-400 text-sm mt-1">Open a person to see every detail and document, then approve or send it back with a reason.</p>
</div>
<?php if ($notice): ?><div class="kyc-note is-ok" role="status"><?= kyc_e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="kyc-note is-bad" role="alert"><?= kyc_e($error) ?></div><?php endif; ?>

<nav class="kyc-tabs" aria-label="Status">
    <?php foreach (array('pending' => 'Waiting', 'approved' => 'Verified', 'rejected' => 'Sent back', 'all' => 'All') as $key => $label): ?>
        <a href="kyc.php?tab=<?= kyc_e($key) ?><?= $find !== '' ? '&amp;q=' . rawurlencode($find) : '' ?>" class="<?= $tab === $key ? 'is-on' : '' ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>><?= kyc_e($label) ?><span><?= (int) $counts[$key] ?></span></a>
    <?php endforeach; ?>
</nav>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="<?= kyc_e($tab) ?>">
    <input type="search" name="q" value="<?= kyc_e($find) ?>" class="form-input max-w-sm" placeholder="Name, email, phone, document or PAN number">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>

<?php if (!$rows): ?>
    <div class="dash-panel"><div class="p-6 text-slate-400 text-sm"><?= $find === '' ? ($tab === 'pending' ? 'Nothing is waiting. All identities are checked.' : 'Nothing here yet.') : 'No identity matches that search.' ?></div></div>
<?php endif; ?>

<div class="kyc-queue">
<?php foreach ($rows as $index => $row): ?>
    <?php
    $kind = $row['account_kind'] === 'organization' ? 'organization' : 'individual';
    $clientId = (int) $row['client_id'];
    $status = (string) $row['status'];
    $values = billing_kyc_values($row);
    $name = $kind === 'organization' ? $row['org_name'] : $row['full_name'];
    $flags = billing_kyc_flags($conn, $row);
    ?>
    <details class="kyc-review" id="c<?= $clientId ?>" <?= ($status === 'pending' && $index === 0) || count($rows) === 1 ? 'open' : '' ?>>
        <summary>
            <div class="kyc-who"><b><?= kyc_e($name !== '' ? $name : $row['account_name']) ?></b><small><?= $kind === 'organization' ? 'Organization' : 'Personal' ?> · <?= kyc_e($row['email']) ?><?= $row['submitted_at'] ? ' · sent ' . kyc_e(date('j M Y, H:i', strtotime($row['submitted_at']))) : '' ?></small></div>
            <?php foreach ($flags as $flag): if ($flag['tone'] === 'bad' || $flag['tone'] === 'warn'): ?><span class="kyc-pill is-<?= $flag['tone'] === 'bad' ? 'bad' : 'wait' ?>"><?= $flag['tone'] === 'bad' ? 'Check' : 'Look' ?></span><?php break; endif; endforeach; ?>
            <?= kyc_status_pill($status) ?>
        </summary>
        <div class="kyc-body">
            <div class="kyc-flags">
                <?php foreach ($flags as $flag): ?>
                    <div class="kyc-flag is-<?= kyc_e($flag['tone']) ?>"><?= !empty($flag['html']) ? $flag['text'] : kyc_e($flag['text']) ?></div>
                <?php endforeach; ?>
            </div>
            <?php if ($row['details'] !== '' && $row['details'] !== null): ?>
                <?= kyc_render_summary($kind, $values) ?>
            <?php else: ?>
                <?= kyc_render_legacy($kind, $row) ?>
            <?php endif; ?>
            <div>
                <h2 class="kyc-h2" style="margin-top:0">Documents</h2>
                <?= kyc_render_gallery($kind, $row, 'kyc-file.php', 'client=' . $clientId . '&') ?>
            </div>
            <?php if ($status === 'pending'): ?>
                <form method="POST" class="kyc-decide">
                    <input type="hidden" name="csrf_token" value="<?= kyc_e(csrf_token()) ?>">
                    <input type="hidden" name="client_id" value="<?= $clientId ?>">
                    <label for="note-<?= $clientId ?>" class="block"><b>If something needs to change, say what</b> (the client sees this)</label>
                    <div class="kyc-reasons" role="group" aria-label="Common reasons">
                        <?php foreach ($reasons as $reason): ?><button type="button" data-reason="<?= kyc_e($reason) ?>" data-target="note-<?= $clientId ?>"><?= kyc_e(substr($reason, 0, strpos($reason, '.') ?: 40)) ?></button><?php endforeach; ?>
                    </div>
                    <textarea id="note-<?= $clientId ?>" name="admin_note" rows="3" maxlength="400" class="form-input" placeholder="Needed only when you send it back"></textarea>
                    <div class="kyc-decide-row">
                        <button type="submit" name="decision" value="approve" class="kyc-approve" onclick="return confirm('Approve this identity? The client will not be able to edit it afterwards.')">Approve</button>
                        <button type="submit" name="decision" value="reject" class="kyc-reject">Send back to the client</button>
                    </div>
                </form>
            <?php elseif ($status === 'rejected' && $row['admin_note'] !== ''): ?>
                <div class="kyc-flag is-warn"><b>Sent back:</b> <?= kyc_e($row['admin_note']) ?></div>
            <?php elseif ($status === 'approved'): ?>
                <p class="kyc-sub">Approved<?= $row['reviewed_at'] ? ' on ' . kyc_e(date('j M Y, H:i', strtotime($row['reviewed_at']))) : '' ?>.</p>
            <?php endif; ?>
        </div>
    </details>
<?php endforeach; ?>
</div>
<script defer src="../assets/js/kyc.js"></script>
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-reason]');
    if (!b) { return; }
    var box = document.getElementById(b.getAttribute('data-target'));
    if (box) { box.value = (box.value ? box.value.trim() + ' ' : '') + b.getAttribute('data-reason'); box.focus(); }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
