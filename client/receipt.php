<?php
require_once __DIR__ . '/../config.php';
require_client();

$cid = (int) get_client_id();
$entryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$entry = null;
$client = null;
if ($entryId > 0) {
    $stmt = $conn->prepare('SELECT * FROM wallet_entries WHERE id = ? AND client_id = ? AND status = \'completed\' LIMIT 1');
    $stmt->bind_param('ii', $entryId, $cid);
    $stmt->execute();
    $entry = db_fetch_assoc($stmt);
    $stmt->close();
    $clientStmt = $conn->prepare('SELECT name, email, phone, company FROM client_users WHERE id = ? LIMIT 1');
    $clientStmt->bind_param('i', $cid);
    $clientStmt->execute();
    $client = db_fetch_assoc($clientStmt);
    $clientStmt->close();
}
if (!$entry || !$client) {
    header('Location: wallet.php');
    exit;
}
$identity = site_portal_identity($conn);
$publicSite = site_public_settings($conn);
$isCredit = $entry['direction'] === 'credit';
$title = $isCredit ? 'Payment receipt' : 'Wallet charge';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> #<?= (int) $entry['id'] ?> — <?= e($identity['name']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { font-family: Inter, Arial, sans-serif; color: #1f3a32; background: #f4f8f6; margin: 0; padding: 32px 16px; }
        .receipt { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #d6e4dd; border-radius: 16px; padding: 32px; }
        .top { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; border-bottom: 1px solid #e3ede8; padding-bottom: 16px; margin-bottom: 20px; }
        .brand { font-size: 20px; font-weight: 700; }
        .muted { color: #5b7169; font-size: 13px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        dl { display: grid; grid-template-columns: 160px 1fr; gap: 10px 16px; margin: 0; font-size: 14px; }
        dt { color: #5b7169; }
        dd { margin: 0; }
        .amount { font-size: 26px; font-weight: 700; color: <?= $isCredit ? '#08734f' : '#1f3a32' ?>; margin: 20px 0; }
        .note { margin-top: 24px; font-size: 12px; color: #5b7169; border-top: 1px solid #e3ede8; padding-top: 12px; }
        .actions { max-width: 640px; margin: 16px auto 0; display: flex; gap: 8px; }
        .actions a, .actions button { font: inherit; font-size: 14px; padding: 8px 16px; border-radius: 10px; border: 1px solid #c9dad2; background: #fff; color: #075e54; cursor: pointer; text-decoration: none; }
        .actions button { background: #097a6d; color: #fff; border-color: #097a6d; }
        @media print { body { background: #fff; padding: 0; } .receipt { border: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="top">
            <div>
                <div class="brand"><?= e($identity['name']) ?></div>
                <?php if (trim((string) $publicSite['site_location']) !== ''): ?><div class="muted"><?= e($publicSite['site_location']) ?></div><?php endif; ?>
                <?php if (trim((string) $publicSite['site_email']) !== ''): ?><div class="muted"><?= e($publicSite['site_email']) ?></div><?php endif; ?>
            </div>
            <div style="text-align:right">
                <h1><?= e($title) ?></h1>
                <div class="muted">No. W-<?= str_pad((string) (int) $entry['id'], 6, '0', STR_PAD_LEFT) ?></div>
                <div class="muted"><?= e(date('M d, Y H:i', strtotime($entry['created_at']))) ?></div>
            </div>
        </div>
        <dl>
            <dt>Account</dt>
            <dd><?= e($client['name']) ?><?= trim((string) $client['company']) !== '' ? ' · ' . e($client['company']) : '' ?></dd>
            <dt>Email</dt>
            <dd><?= e($client['email']) ?></dd>
            <dt>Type</dt>
            <dd><?= e(ucfirst((string) $entry['kind'])) ?><?= trim((string) $entry['method']) !== '' ? ' · ' . e(ucfirst((string) $entry['method'])) : '' ?></dd>
            <?php if (trim((string) $entry['reference_note']) !== ''): ?>
                <dt>Reference</dt>
                <dd><?= e($entry['reference_note']) ?></dd>
            <?php endif; ?>
            <dt>Status</dt>
            <dd>Completed</dd>
        </dl>
        <div class="amount"><?= $isCredit ? 'Received ' : 'Charged ' ?><?= e(billing_money_label($entry['amount'])) ?></div>
        <p class="note">This is a record of a wallet entry on the client portal. It is not a tax invoice.</p>
    </div>
    <div class="actions">
        <button type="button" onclick="window.print()">Print or save as PDF</button>
        <a href="wallet.php">Back to wallet</a>
    </div>
</body>
</html>
