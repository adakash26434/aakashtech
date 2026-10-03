<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $clientId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
    $decision = isset($_POST['decision']) ? (string) $_POST['decision'] : '';
    $error = billing_kyc_decide($conn, $clientId, $decision, isset($_POST['admin_note']) ? $_POST['admin_note'] : '');
    if ($error === '') {
        $notice = $decision === 'approve' ? 'Identity approved. The client can no longer edit it.' : 'Sent back to the client. They can update it and submit again.';
    }
}
$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$rows = array();
try {
    $rows = billing_kyc_queue($conn, $find);
} catch (Throwable $exception) {
    error_log('Identity queue could not be loaded.');
}
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Identity</h1>
    <p class="text-slate-500 text-sm"><?= $find === '' ? 'Pending checks stay in view. Older decisions are in the latest 80.' : 'Matches for “' . e($find) . '”.' ?> Approve the person or organization before they can send SMS, save a voice job, or create an API token. <a class="text-brand-400" href="manual.php#kyc">नेपाली चरण</a></p>
</div>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Name, organization, or email">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>
<?php if ($notice): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!$rows): ?>
    <div class="dash-panel"><div class="p-6 text-slate-400 text-sm"><?= $find === '' ? 'No identity has been submitted.' : 'No identity matches that search.' ?></div></div>
<?php endif; ?>

<?php foreach ($rows as $row): ?>
    <?php
    $kind = isset($row['account_kind']) && $row['account_kind'] === 'organization' ? 'organization' : 'individual';
    $clientId = (int) $row['client_id'];
    $status = (string) $row['status'];
    ?>
    <article class="dash-panel mb-4">
        <div class="p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-heading font-semibold text-white text-lg"><a class="hover:text-brand-300" href="client.php?id=<?= $clientId ?>"><?= e($row['account_name']) ?></a></h2>
                    <p class="text-slate-500 text-sm"><?php $kycEmail = filter_var($row['email'], FILTER_VALIDATE_EMAIL) ? (string) $row['email'] : ''; ?><?php if ($kycEmail !== ''): ?><a class="text-brand-400 hover:text-brand-300" href="mailto:<?= e($kycEmail) ?>"><?= e($kycEmail) ?></a><?php else: ?><?= e($row['email']) ?><?php endif; ?><?php if (!empty($row['phone'])): ?> · <?php $kycPhone = preg_replace('/[^0-9+]/', '', (string) $row['phone']); ?><?php if ($kycPhone !== ''): ?><a class="text-brand-400 hover:text-brand-300" href="tel:<?= e($kycPhone) ?>"><?= e($row['phone']) ?></a><?php else: ?><?= e($row['phone']) ?><?php endif; ?><?php endif; ?></p>
                </div>
                <span class="text-sm <?= $status === 'approved' ? 'text-green-400' : ($status === 'pending' ? 'text-yellow-300' : 'text-red-300') ?>"><?= e(ucfirst($status)) ?></span>
            </div>
            <dl class="grid sm:grid-cols-2 gap-3 text-sm mb-4">
                <div><dt class="text-slate-500">Account</dt><dd class="text-white"><?= $kind === 'organization' ? 'Organization' : 'Individual' ?></dd></div>
                <?php if ($kind === 'individual'): ?>
                    <div><dt class="text-slate-500">Name</dt><dd class="text-white"><?= e($row['full_name']) ?></dd></div>
                    <div><dt class="text-slate-500"><?= e(billing_kyc_id_label($row['id_kind'])) ?></dt><dd class="text-white"><?= e($row['id_number']) ?></dd></div>
                <?php else: ?>
                    <div><dt class="text-slate-500">Organization</dt><dd class="text-white"><?= e($row['org_name']) ?></dd></div>
                    <div><dt class="text-slate-500">Registration</dt><dd class="text-white"><?= e($row['registration_number']) ?></dd></div>
                    <div><dt class="text-slate-500">PAN or VAT</dt><dd class="text-white"><?= e($row['tax_number']) ?></dd></div>
                    <div><dt class="text-slate-500">Authorized person</dt><dd class="text-white"><?= e($row['contact_name']) ?> · <?= e($row['contact_id_number']) ?></dd></div>
                <?php endif; ?>
                <div class="sm:col-span-2"><dt class="text-slate-500">Address</dt><dd class="text-white"><?= e($row['address']) ?></dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Use</dt><dd class="text-white"><?= e($row['purpose']) ?></dd></div>
            </dl>
            <div class="flex flex-wrap gap-3 text-sm mb-4">
                <?php if ($kind === 'individual' && $row['doc_identity'] !== ''): ?>
                    <a class="text-brand-400" href="kyc-file.php?client=<?= $clientId ?>&slot=identity">Identity document</a>
                <?php endif; ?>
                <?php if ($kind === 'organization' && $row['doc_registration'] !== ''): ?>
                    <a class="text-brand-400" href="kyc-file.php?client=<?= $clientId ?>&slot=registration">Registration</a>
                <?php endif; ?>
                <?php if ($kind === 'organization' && $row['doc_tax'] !== ''): ?>
                    <a class="text-brand-400" href="kyc-file.php?client=<?= $clientId ?>&slot=tax">PAN or VAT</a>
                <?php endif; ?>
                <?php if ($kind === 'organization' && $row['doc_authority'] !== ''): ?>
                    <a class="text-brand-400" href="kyc-file.php?client=<?= $clientId ?>&slot=authority">Authorized person</a>
                <?php endif; ?>
            </div>
            <?php if ($status === 'pending' || $status === 'approved'): ?>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="client_id" value="<?= $clientId ?>">
                    <?php if ($status === 'pending'): ?>
                        <button type="submit" name="decision" value="approve" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Approve</button>
                    <?php endif; ?>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="note-<?= $clientId ?>">Why it needs a change</label>
                        <textarea id="note-<?= $clientId ?>" name="admin_note" rows="2" maxlength="400" class="form-input resize-none"></textarea>
                    </div>
                    <button type="submit" name="decision" value="reject" class="px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300">Send back for a change</button>
                </form>
            <?php elseif ($row['admin_note'] !== ''): ?>
                <p class="text-slate-400 text-sm">Last note: <?= e($row['admin_note']) ?></p>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
