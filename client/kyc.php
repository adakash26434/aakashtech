<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/kyc-view.php';

$cid = (int) get_client_id();
$kyc = billing_kyc_load($conn, $cid);
$msg = '';
$err = '';
$errors = array();
$typed = null;

$asked = isset($_GET['kind']) ? (string) $_GET['kind'] : '';
$kind = $kyc['account_kind'] === 'organization' ? 'organization' : 'individual';
if ($asked === 'organization' || $asked === 'individual') {
    $kind = $asked;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $result = billing_kyc_submit_detailed($conn, $cid, $_POST, $_FILES);
    $kyc = billing_kyc_load($conn, $cid);
    $kind = isset($_POST['account_kind']) && $_POST['account_kind'] === 'organization' ? 'organization' : 'individual';
    if ($result['ok']) {
        $msg = 'Sent. We will check your details and documents, usually within one working day, and email you. Until it is approved you can send up to 100 SMS.';
    } else {
        $err = $result['message'];
        $errors = $result['errors'];
        $typed = $result['values'] + array('temp_same' => isset($_POST['temp_same']) ? '1' : '');
    }
}

$status = $kyc['status'];
$values = $typed !== null ? $typed : billing_kyc_values($kyc);
$editing = $status === 'rejected' || $status === '' || $errors || (isset($_GET['edit']) && $status === 'pending');
if ($kyc['account_kind'] !== $kind && $status !== 'approved') {
    $values = $typed !== null ? $typed : array();
}
$timeline = array('Details and documents sent', 'We check them', 'Verified');
$stage = $status === 'approved' ? 3 : ($status === 'pending' ? 2 : 1);
?>
<div class="kyc-head">
    <div>
        <h1 class="kyc-title">Identity verification</h1>
        <p class="kyc-sub">Verify once to send more than 100 SMS. Your details stay private.</p>
    </div>
    <?= kyc_status_pill($status) ?>
</div>

<?php if ($msg): ?><div class="kyc-note is-ok" role="status"><?= kyc_e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="kyc-note is-bad" role="alert"><?= kyc_e($err) ?></div><?php endif; ?>
<?php if ($status === 'rejected' && $kyc['admin_note'] !== ''): ?>
    <div class="kyc-note is-warn" role="alert"><b>Please change this:</b> <?= kyc_e($kyc['admin_note']) ?></div>
<?php endif; ?>

<ol class="kyc-track" aria-label="Progress">
    <?php foreach ($timeline as $i => $label): ?>
        <li class="<?= $i + 1 < $stage ? 'is-done' : ($i + 1 === $stage ? 'is-now' : '') ?>"><b><?= $i + 1 ?></b><?= kyc_e($label) ?></li>
    <?php endforeach; ?>
</ol>

<?php if ($status === 'approved' || ($status === 'pending' && !$editing)): ?>
    <section class="kyc-card">
        <?php if ($status === 'approved'): ?>
            <p class="kyc-verified"><i data-lucide="badge-check"></i> Verified<?= $kyc['reviewed_at'] ? ' on ' . kyc_e(date('j M Y', strtotime($kyc['reviewed_at']))) : '' ?>. These details are locked. To change them, <a href="support.php">ask Support</a>.</p>
        <?php else: ?>
            <p class="kyc-verified is-wait"><i data-lucide="clock"></i> Sent<?= $kyc['submitted_at'] ? ' on ' . kyc_e(date('j M Y, H:i', strtotime($kyc['submitted_at']))) : '' ?>. We will email you when it is checked. <a href="kyc.php?edit=1">Edit and send again</a></p>
        <?php endif; ?>
        <?= $kyc['details'] !== '' && $kyc['details'] !== null ? kyc_render_summary($kind, billing_kyc_values($kyc)) : kyc_render_legacy($kind, $kyc) ?>
        <h2 class="kyc-h2">Your documents</h2>
        <?= kyc_render_gallery($kind, $kyc, 'kyc-file.php') ?>
    </section>
<?php else: ?>
    <nav class="kyc-kinds" aria-label="Account type">
        <a href="kyc.php?kind=individual" class="<?= $kind === 'individual' ? 'is-on' : '' ?>"<?= $kind === 'individual' ? ' aria-current="page"' : '' ?>><i data-lucide="user"></i><span><b>Personal</b><small>Citizenship, National ID, passport or licence</small></span></a>
        <a href="kyc.php?kind=organization" class="<?= $kind === 'organization' ? 'is-on' : '' ?>"<?= $kind === 'organization' ? ' aria-current="page"' : '' ?>><i data-lucide="building-2"></i><span><b>Organization</b><small>Company, cooperative, school, NGO</small></span></a>
    </nav>
    <div class="kyc-progress" aria-hidden="true"><span id="kyc-bar"></span></div>
    <p class="kyc-progress-text" id="kyc-progress-text" aria-live="polite"></p>
    <?= kyc_render_form($kind, $values, $errors, $kyc, 'kyc-file.php', csrf_token()) ?>
<?php endif; ?>
<script defer src="../assets/js/kyc.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
