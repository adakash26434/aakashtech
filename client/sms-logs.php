<?php
if (isset($_GET['export']) && (string) $_GET['export'] === '1') {
    require_once __DIR__ . '/../config.php';
    require_client();
    $cid = (int) get_client_id();
    $exportStatus = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $exportSearch = isset($_GET['q']) ? (string) $_GET['q'] : '';
    $exportSend = isset($_GET['send']) ? (int) $_GET['send'] : 0;
    $exportFrom = isset($_GET['from']) ? (string) $_GET['from'] : '';
    $exportTo = isset($_GET['to']) ? (string) $_GET['to'] : '';
    $exportSource = isset($_GET['source']) ? (string) $_GET['source'] : '';
    $export = sms_message_page($conn, $cid, $exportStatus, $exportSearch, $exportSend, 1, 8000, $exportFrom, $exportTo, $exportSource);
    $exportName = 'sms-report';
    if ($export['from'] !== '' || $export['to'] !== '') {
        $exportName .= '-' . ($export['from'] !== '' ? $export['from'] : 'start') . '-to-' . ($export['to'] !== '' ? $export['to'] : 'today');
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $exportName . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('When (Nepal)', 'Send', 'To', 'Message', 'Credits', 'From', 'Status', 'Result'), ',', '"', '\\');
    foreach ($export['rows'] as $exportRow) {
        fputcsv($out, array(
            sms_csv_cell(sms_format_time($exportRow['created_at'])),
            sms_csv_cell(isset($exportRow['campaign_name']) ? $exportRow['campaign_name'] : ''),
            sms_csv_cell($exportRow['recipient']),
            sms_csv_cell($exportRow['message_text']),
            (int) $exportRow['parts'],
            $exportRow['source'] === 'api' ? 'API' : 'Dashboard',
            sms_csv_cell($exportRow['status']),
            sms_csv_cell(isset($exportRow['error_text']) ? $exportRow['error_text'] : '')
        ), ',', '"', '\\');
    }
    fclose($out);
    exit;
}
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$status = isset($_GET['status']) ? (string) $_GET['status'] : '';
$search = isset($_GET['q']) ? (string) $_GET['q'] : '';
$sendId = isset($_GET['send']) ? (int) $_GET['send'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$from = isset($_GET['from']) ? (string) $_GET['from'] : '';
$to = isset($_GET['to']) ? (string) $_GET['to'] : '';
$source = isset($_GET['source']) ? (string) $_GET['source'] : '';
$log = sms_message_page($conn, $cid, $status, $search, $sendId, $page, 50, $from, $to, $source);
$rows = $log['rows'];
$status = $log['status'];
$search = $log['search'];
$from = $log['from'];
$to = $log['to'];
$source = $log['source'];
$lookupNumber = $log['number'];
$page = $log['page'];
$sends = sms_recent_sends($conn, $cid);
$totals = sms_log_totals($conn, $cid, $status, $search, $sendId, $from, $to, $source);
$sendName = '';
if ($sendId > 0) {
    $nameStmt = $conn->prepare('SELECT campaign_name FROM sms_campaigns WHERE id = ? AND client_id = ? AND channel = ?');
    $nameChannel = 'sms';
    $nameStmt->bind_param('iis', $sendId, $cid, $nameChannel);
    $nameStmt->execute();
    $nameRow = db_fetch_assoc($nameStmt);
    $nameStmt->close();
    if ($nameRow) {
        $sendName = (string) $nameRow['campaign_name'];
    }
}

function sms_log_reason($code)
{
    if ($code === 'credits') {
        return 'Not enough credits';
    }
    if ($code === 'not-accepted') {
        return 'Number was not accepted. Credits were returned.';
    }
    if ($code === 'line-off' || $code === 'line-rejected' || $code === 'line-empty' || $code === 'sender-rejected') {
        return 'Not delivered. Credits were returned.';
    }
    if ($code === 'prohibited') {
        return 'Not sent. Credits were returned.';
    }
    if ($code === 'cancelled') {
        return 'Cancelled before it sent. Credits were returned.';
    }
    return '';
}
?>
<div class="mb-8 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">SMS logs</h1>
        <p class="text-slate-500 text-sm">Sent means the line accepted the number. Failed means it did not go out, and those credits came back. Open a send to see each number.</p>
    </div>
    <?php
    $download = array('export' => '1');
    if ($status !== '') {
        $download['status'] = $status;
    }
    if ($search !== '') {
        $download['q'] = $search;
    }
    if ($sendId > 0) {
        $download['send'] = $sendId;
    }
    if ($from !== '') {
        $download['from'] = $from;
    }
    if ($to !== '') {
        $download['to'] = $to;
    }
    if ($source !== '') {
        $download['source'] = $source;
    }
    ?>
    <div class="flex gap-4 text-sm">
        <a href="sms-logs.php?<?= e(http_build_query($download)) ?>" class="text-brand-400" title="Opens in Excel. Up to 8,000 rows for these dates and this number.">Download Excel</a>
        <a href="sms-portal.php" class="text-brand-400">Send SMS</a>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="dash-stat-card"><div class="dash-stat-value"><?= number_format((int) $totals['sent']) ?></div><div class="dash-stat-label">Sent</div></div>
    <div class="dash-stat-card"><div class="dash-stat-value"><?= number_format((int) $totals['sent_credits']) ?></div><div class="dash-stat-label">Credits used</div></div>
    <div class="dash-stat-card"><div class="dash-stat-value"><?= number_format((int) $totals['failed']) ?></div><div class="dash-stat-label">Failed</div></div>
    <div class="dash-stat-card"><div class="dash-stat-value"><?= number_format((int) $totals['failed_credits']) ?></div><div class="dash-stat-label">Credits returned</div></div>
</div>

<?php if ($sends): ?>
    <div class="dash-panel overflow-hidden mb-6">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Each send</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Send</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Sent</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Failed</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Credits</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($sends as $sendRow): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-white text-sm"><?= e($sendRow['campaign_name']) ?></p>
                                <p class="text-slate-500 text-xs"><?= e(sms_format_time($sendRow['created_at'])) ?> · <?= e(ucfirst((string) $sendRow['status'])) ?></p>
                            </td>
                            <td class="px-4 py-3 text-sm text-green-400"><?= number_format((int) $sendRow['sent_count']) ?></td>
                            <td class="px-4 py-3 text-sm <?= (int) $sendRow['failed_count'] > 0 ? 'text-red-400' : 'text-slate-500' ?>"><?= number_format((int) $sendRow['failed_count']) ?></td>
                            <td class="px-4 py-3 text-slate-300 text-xs"><?= number_format((int) $sendRow['sent_credits']) ?> used<?php if ((int) $sendRow['failed_credits'] > 0): ?><br><?= number_format((int) $sendRow['failed_credits']) ?> returned<?php endif; ?></td>
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                <a href="sms-logs.php?send=<?= (int) $sendRow['id'] ?>" class="text-slate-300">All</a>
                                <?php if ((int) $sendRow['failed_count'] > 0): ?>
                                    · <a href="sms-logs.php?send=<?= (int) $sendRow['id'] ?>&status=failed" class="text-red-300">Failed</a>
                                    · <a href="sms-portal.php?retry=<?= (int) $sendRow['id'] ?>" class="text-brand-400">Retry</a>
                                <?php endif; ?>
                                · <a href="sms-portal.php?send=<?= (int) $sendRow['id'] ?>" class="text-brand-400">Use again</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($sendId > 0): ?>
    <p class="mb-4 text-sm text-slate-400"><?= $sendName !== '' ? e($sendName) : 'This send' ?> only. <a href="sms-logs.php" class="text-brand-400">Show all numbers</a></p>
<?php endif; ?>

<form method="GET" class="flex flex-wrap items-end gap-2 mb-4">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <?php if ($sendId > 0): ?><input type="hidden" name="send" value="<?= (int) $sendId ?>"><?php endif; ?>
    <div>
        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="log-number">Mobile number</label>
        <input id="log-number" type="text" name="q" value="<?= e($search) ?>" inputmode="numeric" maxlength="14" placeholder="98XXXXXXXX" class="form-input max-w-xs">
    </div>
    <div>
        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="log-from">From</label>
        <input id="log-from" type="date" name="from" value="<?= e($from) ?>" class="form-input">
    </div>
    <div>
        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="log-to">To</label>
        <input id="log-to" type="date" name="to" value="<?= e($to) ?>" class="form-input">
    </div>
    <div>
        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="log-source">Sent from</label>
        <select id="log-source" name="source" class="form-input">
            <option value="">Dashboard and API</option>
            <option value="dashboard" <?= $source === 'dashboard' ? 'selected' : '' ?>>Dashboard</option>
            <option value="api" <?= $source === 'api' ? 'selected' : '' ?>>API / OTP</option>
        </select>
    </div>
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm rounded-xl">Find</button>
    <?php
    $quickRanges = array(
        'Today' => array(date('Y-m-d'), date('Y-m-d')),
        'Last 7 days' => array(date('Y-m-d', strtotime('-6 days')), date('Y-m-d')),
        'This month' => array(date('Y-m-01'), date('Y-m-d'))
    );
    ?>
    <div class="flex flex-wrap items-center gap-1 text-xs pb-2">
        <?php foreach ($quickRanges as $rangeLabel => $range): ?>
            <?php
            $rangeQuery = array('from' => $range[0], 'to' => $range[1]);
            if ($status !== '') { $rangeQuery['status'] = $status; }
            if ($search !== '') { $rangeQuery['q'] = $search; }
            if ($source !== '') { $rangeQuery['source'] = $source; }
            if ($sendId > 0) { $rangeQuery['send'] = $sendId; }
            $rangeOn = $from === $range[0] && $to === $range[1];
            ?>
            <a href="sms-logs.php?<?= e(http_build_query($rangeQuery)) ?>" class="px-2.5 py-1.5 rounded-lg <?= $rangeOn ? 'bg-brand-500/15 text-brand-400' : 'text-slate-400 hover:text-white' ?>"><?= e($rangeLabel) ?></a>
        <?php endforeach; ?>
        <?php if ($from !== '' || $to !== '' || $search !== '' || $source !== ''): ?>
            <a href="sms-logs.php<?= $sendId > 0 ? '?send=' . (int) $sendId : '' ?>" class="px-2.5 py-1.5 text-slate-500">Clear</a>
        <?php endif; ?>
    </div>
</form>
<?php if ($lookupNumber !== ''): ?>
    <?php $latest = $rows ? $rows[0] : null; ?>
    <div class="mb-4 p-4 rounded-2xl border <?= ((int) $totals['sent'] > 0) ? 'border-green-500/30 bg-green-500/10 text-green-300' : (((int) $totals['failed'] > 0) ? 'border-red-500/30 bg-red-500/10 text-red-300' : 'border-slate-700 bg-slate-800/40 text-slate-300') ?> text-sm">
        <?php if ((int) $totals['sent'] > 0): ?>
            <?= e($lookupNumber) ?> received an SMS. <?= number_format((int) $totals['sent']) ?> went out<?php if ($page === 1 && $latest && $latest['status'] === 'sent'): ?> · last on <?= e(sms_format_time($latest['created_at'])) ?><?php endif; ?>.
        <?php elseif ((int) $totals['failed'] > 0): ?>
            An SMS to <?= e($lookupNumber) ?> was tried and did not go out. <?= number_format((int) $totals['failed']) ?> failed<?php if ($page === 1 && $latest): ?> · last on <?= e(sms_format_time($latest['created_at'])) ?><?php endif; ?>. Those credits came back.
        <?php else: ?>
            No SMS for <?= e($lookupNumber) ?><?= ($from !== '' || $to !== '') ? ' in these dates' : '' ?>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="flex flex-wrap gap-2 mb-4 text-sm">
    <?php
    $filters = array('' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'queued' => 'Queued', 'scheduled' => 'Scheduled');
    foreach ($filters as $key => $label):
        $query = array();
        if ($key !== '') {
            $query['status'] = $key;
        }
        if ($search !== '') {
            $query['q'] = $search;
        }
        if ($from !== '') {
            $query['from'] = $from;
        }
        if ($to !== '') {
            $query['to'] = $to;
        }
        if ($source !== '') {
            $query['source'] = $source;
        }
        if ($sendId > 0) {
            $query['send'] = $sendId;
        }
        $href = 'sms-logs.php' . ($query ? '?' . http_build_query($query) : '');
        $on = $status === $key;
    ?>
        <a href="<?= e($href) ?>" class="px-3 py-1.5 rounded-lg <?= $on ? 'bg-brand-500/15 text-brand-400' : 'text-slate-400 hover:text-white' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="dash-panel overflow-hidden">
    <?php if ($rows): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">When</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Send</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">To</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Message</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Credits</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">From</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($rows as $row): ?>
                        <?php $reason = sms_log_reason((string) $row['error_text']); ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap"><?= e(sms_format_time($row['created_at'])) ?></td>
                            <td class="px-4 py-3 text-slate-400 text-xs"><?= e(isset($row['campaign_name']) ? $row['campaign_name'] : '') ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= e($row['recipient']) ?></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><div class="max-w-xs truncate"><?= e($row['message_text']) ?></div></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><?= (int) $row['parts'] ?></td>
                            <td class="px-4 py-3 text-slate-400 text-xs"><?= e($row['source'] === 'api' ? 'API' : 'Dashboard') ?></td>
                            <td class="px-4 py-3 text-sm">
                                <span class="sms-state sms-state-<?= e(in_array($row['status'], array('sent', 'failed'), true) ? $row['status'] : 'wait') ?>" style="margin-left:0"><?= e(ucfirst($row['status'])) ?></span>
                                <?php if ($row['status'] === 'sent' && isset($row['delivery']) && $row['delivery'] === 'delivered'): ?><span class="pill pill--ok" style="margin-top:4px">Delivered</span><?php elseif ($row['status'] === 'sent' && isset($row['delivery']) && $row['delivery'] === 'failed'): ?><span class="pill pill--bad" style="margin-top:4px" title="The phone network could not deliver this message">Not delivered</span><?php endif; ?>
                                <?php if ($reason !== ''): ?><span class="block text-slate-500 text-xs"><?= e($reason) ?></span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap"><a href="sms-portal.php?reuse=<?= (int) $row['id'] ?>" class="text-brand-400 text-xs">Send again</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm"><?= $search !== '' ? 'No SMS for that number.' : 'No SMS logs yet.' ?></p>
    <?php endif; ?>
    <?php if ($page > 1 || $log['has_more']): ?>
        <?php
        $pager = array();
        if ($status !== '') {
            $pager['status'] = $status;
        }
        if ($search !== '') {
            $pager['q'] = $search;
        }
        if ($from !== '') {
            $pager['from'] = $from;
        }
        if ($to !== '') {
            $pager['to'] = $to;
        }
        if ($source !== '') {
            $pager['source'] = $source;
        }
        if ($sendId > 0) {
            $pager['send'] = $sendId;
        }
        ?>
        <div class="px-4 py-3 flex gap-4 text-sm border-t border-slate-800">
            <?php if ($page > 1): ?>
                <?php $newer = $pager; $newer['page'] = $page - 1; ?>
                <a href="sms-logs.php?<?= e(http_build_query($newer)) ?>" class="text-brand-400">Newer</a>
            <?php endif; ?>
            <?php if ($log['has_more']): ?>
                <?php $older = $pager; $older['page'] = $page + 1; ?>
                <a href="sms-logs.php?<?= e(http_build_query($older)) ?>" class="text-brand-400">Older</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
