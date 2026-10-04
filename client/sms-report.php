<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/sms-report-view.php';

$sendId = isset($_GET['send']) ? max(0, (int) $_GET['send']) : 0;
$report = sms_report($conn, $cid, isset($_GET['from']) ? $_GET['from'] : '', isset($_GET['to']) ? $_GET['to'] : '', $sendId);

// The send menu lists this client's recent sends, whatever period is on screen.
$sendChoices = array();
$stmt = $conn->prepare('SELECT id, campaign_name FROM sms_campaigns WHERE client_id = ? ORDER BY id DESC LIMIT 30');
$stmt->bind_param('i', $cid);
$stmt->execute();
foreach (db_fetch_all($stmt) ?: array() as $row) {
    $sendChoices[] = array('id' => (int) $row['id'], 'name' => (string) $row['campaign_name'], 'selected' => (int) $row['id'] === $sendId ? 1 : 0);
}
$stmt->close();
?>
<div class="mb-6">
    <h1 class="text-2xl font-heading font-bold text-white">Delivery report</h1>
    <p class="text-slate-400 text-sm mt-1">See how many of your messages reached the phone, how fast, and what to fix.</p>
</div>
<?php
sms_report_render($report, array('self' => 'sms-report.php', 'logs' => 'sms-logs.php', 'sends_filter' => $sendChoices));
?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
