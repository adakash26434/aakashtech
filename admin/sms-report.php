<?php
require_once __DIR__ . '/../config.php';
require_admin();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/sms-report-view.php';

$from = isset($_GET['from']) ? (string) $_GET['from'] : '';
$to = isset($_GET['to']) ? (string) $_GET['to'] : '';
$report = sms_report($conn, 0, $from, $to, 0);
?>
<div class="mb-6">
    <h1 class="text-2xl font-heading font-bold text-white">SMS delivery report</h1>
    <p class="text-slate-400 text-sm mt-1">All clients together: delivery, speed, networks and the reasons messages did not go.</p>
</div>
<?php
sms_report_render($report, array('self' => 'sms-report.php', 'logs' => '../client/sms-logs.php', 'admin' => true, 'clients' => sms_report_by_client($conn, $report['from'], $report['to'], 8)));
?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
