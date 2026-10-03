<?php
if (isset($_GET['export']) && (string) $_GET['export'] === '1') {
    require_once __DIR__ . '/../config.php';
    require_client();
    $cid = (int) get_client_id();
    $exportStatus = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $exportSearch = isset($_GET['q']) ? (string) $_GET['q'] : '';
    $exportSend = isset($_GET['send']) ? (int) $_GET['send'] : 0;
    $export = sms_message_page($conn, $cid, $exportStatus, $exportSearch, $exportSend, 1, 2000);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sms-logs.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('When (Nepal)', 'To', 'Message', 'Credits', 'From', 'Status'), ',', '"', '\\');
    foreach ($export['rows'] as $exportRow) {
        fputcsv($out, array(
            sms_csv_cell(sms_format_time($exportRow['created_at'])),
            sms_csv_cell($exportRow['recipient']),
            sms_csv_cell($exportRow['message_text']),
            (int) $exportRow['parts'],
            $exportRow['source'] === 'api' ? 'API' : 'Dashboard',
            sms_csv_cell($exportRow['status'])
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
$log = sms_message_page($conn, $cid, $status, $search, $sendId, $page);
$rows = $log['rows'];
$status = $log['status'];
$search = $log['search'];
$page = $log['page'];
$sends = sms_recent_sends($conn, $cid);
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
    return '';
}
?>
<div class="mb-8 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">SMS logs</h1>
        <p class="text-slate-500 text-sm">Every SMS from the dashboard or an API token. Sent means the line accepted it. A failed number did not leave this account, and its credits came back.</p>
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
    ?>
    <div class="flex gap-4 text-sm">
        <a href="sms-logs.php?<?= e(http_build_query($download)) ?>" class="text-slate-400" title="Latest 2,000 rows for this filter">Download CSV</a>
        <a href="sms-portal.php" class="text-brand-400">Send SMS</a>
    </div>
</div>

<?php if ($sends): ?>
    <div class="dash-panel overflow-hidden mb-6">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Sends</h3></div>
        <div class="divide-y divide-slate-800">
            <?php foreach ($sends as $sendRow): ?>
                <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-white text-sm"><?= e($sendRow['campaign_name']) ?> <span class="text-slate-500 text-xs"><?= e(ucfirst($sendRow['status'])) ?></span></p>
                        <p class="text-slate-500 text-xs"><?= e(sms_format_time($sendRow['created_at'])) ?> · <?= number_format((int) $sendRow['sent_count']) ?> sent<?php if ((int) $sendRow['failed_count'] > 0): ?> · <?= number_format((int) $sendRow['failed_count']) ?> failed<?php endif; ?></p>
                    </div>
                    <div class="flex gap-3 text-xs">
                        <a href="sms-logs.php?send=<?= (int) $sendRow['id'] ?>" class="text-slate-400">Numbers</a>
                        <a href="sms-portal.php?send=<?= (int) $sendRow['id'] ?>" class="text-brand-400">Use again</a>
                        <?php if ((int) $sendRow['failed_count'] > 0): ?>
                            <a href="sms-portal.php?retry=<?= (int) $sendRow['id'] ?>" class="text-brand-400">Retry failed</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($sendId > 0): ?>
    <p class="mb-4 text-sm text-slate-400"><?= $sendName !== '' ? e($sendName) : 'This send' ?> only. <a href="sms-logs.php" class="text-brand-400">Show all numbers</a></p>
<?php endif; ?>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <?php if ($sendId > 0): ?><input type="hidden" name="send" value="<?= (int) $sendId ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= e($search) ?>" inputmode="numeric" maxlength="10" placeholder="Search a number" class="form-input max-w-xs">
    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm rounded-xl">Search</button>
</form>

<div class="flex flex-wrap gap-2 mb-4 text-sm">
    <?php
    $filters = array('' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'queued' => 'Queued');
    foreach ($filters as $key => $label):
        $query = array();
        if ($key !== '') {
            $query['status'] = $key;
        }
        if ($search !== '') {
            $query['q'] = $search;
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
                            <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap"><?= e(date('M j, H:i', strtotime($row['created_at']))) ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= e($row['recipient']) ?></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><div class="max-w-xs truncate"><?= e($row['message_text']) ?></div></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><?= (int) $row['parts'] ?></td>
                            <td class="px-4 py-3 text-slate-400 text-xs"><?= e($row['source'] === 'api' ? 'API' : 'Dashboard') ?></td>
                            <td class="px-4 py-3 text-sm">
                                <span class="text-white"><?= e(ucfirst($row['status'])) ?></span>
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
