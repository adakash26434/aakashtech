<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
sms_run_queue($conn, 5);
$notice = flash('billing');
$msg = '';
$err = '';
$balances = billing_unit_balances($conn, $cid);
$kycReady = billing_kyc_approved($conn, $cid);
$route = sms_client_route($conn, $cid);
$audiences = billing_audiences();
$purposes = billing_purposes();
$values = array(
    'campaign_name' => '',
    'audience' => '',
    'purpose' => '',
    'sender_id' => '',
    'message_content' => '',
    'numbers' => '',
    'scheduled_at' => ''
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $retryId = isset($_GET['retry']) ? (int) $_GET['retry'] : 0;
    $sendId = isset($_GET['send']) ? (int) $_GET['send'] : 0;
    $reuseId = isset($_GET['reuse']) ? (int) $_GET['reuse'] : 0;
    if ($retryId > 0 || $sendId > 0) {
        $loaded = sms_load_send($conn, $cid, $retryId > 0 ? $retryId : $sendId, $retryId > 0);
        if (!empty($loaded['ok'])) {
            $values['campaign_name'] = $retryId > 0 ? 'Retry failed' : (string) $loaded['name'];
            $values['message_content'] = (string) $loaded['text'];
            $values['numbers'] = (string) $loaded['numbers'];
            $values['audience'] = (string) $loaded['audience'];
            $values['purpose'] = (string) $loaded['purpose'];
            $values['sender_id'] = (string) $loaded['sender'];
        } elseif ($loaded['error'] !== '') {
            $err = $loaded['error'];
        }
    } elseif ($reuseId > 0) {
        $reuseStmt = $conn->prepare('SELECT message_text, recipient FROM sms_messages WHERE id = ? AND client_id = ?');
        $reuseStmt->bind_param('ii', $reuseId, $cid);
        $reuseStmt->execute();
        $reuseRow = db_fetch_assoc($reuseStmt);
        $reuseStmt->close();
        if ($reuseRow) {
            $values['campaign_name'] = 'Send again';
            $values['message_content'] = (string) $reuseRow['message_text'];
            $values['numbers'] = (string) $reuseRow['recipient'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_campaign'])) {
    verify_csrf();
    $cancelId = isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : 0;
    if (sms_cancel_scheduled($conn, $cid, $cancelId)) {
        $msg = 'Scheduled SMS cancelled. No credits were used.';
    } else {
        $err = 'That SMS could not be cancelled.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    verify_csrf();
    $values['message_content'] = isset($_POST['message_content']) ? (string) $_POST['message_content'] : '';
    $values['numbers'] = isset($_POST['numbers']) ? (string) $_POST['numbers'] : '';
    $saved = sms_save_template($conn, $cid, isset($_POST['template_label']) ? $_POST['template_label'] : '', $values['message_content']);
    if ($saved === '') {
        $msg = 'Message saved. Choose it again from Saved messages.';
    } else {
        $err = $saved;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_template'])) {
    verify_csrf();
    $templateId = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
    if (sms_delete_template($conn, $cid, $templateId)) {
        $msg = 'Saved message deleted.';
    } else {
        $err = 'That saved message could not be deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_list'])) {
    verify_csrf();
    $values['message_content'] = isset($_POST['message_content']) ? (string) $_POST['message_content'] : '';
    $values['numbers'] = isset($_POST['numbers']) ? (string) $_POST['numbers'] : '';
    $saved = sms_save_number_list($conn, $cid, isset($_POST['list_label']) ? $_POST['list_label'] : '', $values['numbers']);
    if ($saved === '') {
        $msg = 'Number list saved. Duplicates were kept once.';
    } else {
        $err = $saved;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_list'])) {
    verify_csrf();
    $listId = isset($_POST['list_id']) ? (int) $_POST['list_id'] : 0;
    if (sms_delete_number_list($conn, $cid, $listId)) {
        $msg = 'Number list deleted.';
    } else {
        $err = 'That number list could not be deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_sender'])) {
    verify_csrf();
    $requestError = sms_request_sender($conn, $cid, isset($_POST['requested_sender']) ? $_POST['requested_sender'] : '');
    if ($requestError === '') {
        $msg = 'Sender name requested. It can be used after it is approved.';
    } else {
        $err = $requestError;
    }
    $route = sms_client_route($conn, $cid);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_sms'])) {
    verify_csrf();
    foreach ($values as $key => $unused) {
        $values[$key] = isset($_POST[$key]) ? (string) $_POST[$key] : '';
    }
    $guardError = billing_form_guard_check('send-sms', $_POST);
    if ($guardError !== '') {
        $err = $guardError;
    } elseif (billing_posted($_POST, 'legal_accept') !== '1') {
        $err = 'Accept the declaration before this can be sent.';
    } else {
        $scheduled = trim($values['scheduled_at']);
        if ($scheduled !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $scheduled)) {
            $scheduled = '';
        }
        $scheduledValue = $scheduled !== '' ? str_replace('T', ' ', $scheduled) . ':00' : '';
        $result = sms_send($conn, $cid, array(
            'name' => $values['campaign_name'],
            'text' => $values['message_content'],
            'numbers' => $values['numbers'],
            'audience' => $values['audience'],
            'purpose' => $values['purpose'],
            'sender' => $values['sender_id'],
            'scheduled_at' => $scheduledValue,
            'source' => 'dashboard'
        ));
        if (!empty($result['ok'])) {
            $msg = $result['message'];
            $balances = billing_unit_balances($conn, $cid);
            $values = array(
                'campaign_name' => '',
                'audience' => '',
                'purpose' => '',
                'sender_id' => '',
                'message_content' => '',
                'numbers' => '',
                'scheduled_at' => ''
            );
        } else {
            $err = $result['error'];
        }
    }
}

$today = date('Y-m-d 00:00:00');
$sentTodayStmt = $conn->prepare('SELECT COUNT(*) AS c FROM sms_messages WHERE client_id = ? AND status = ? AND sent_at >= ?');
$sentStatus = 'sent';
$sentTodayStmt->bind_param('iss', $cid, $sentStatus, $today);
$sentTodayStmt->execute();
$sentTodayRow = db_fetch_assoc($sentTodayStmt);
$sentTodayStmt->close();
$sentToday = $sentTodayRow ? (int) $sentTodayRow['c'] : 0;

$tokenStmt = $conn->prepare('SELECT COUNT(*) AS c FROM sms_api_tokens WHERE client_id = ? AND status = ?');
$tokenStatus = 'active';
$tokenStmt->bind_param('is', $cid, $tokenStatus);
$tokenStmt->execute();
$tokenRow = db_fetch_assoc($tokenStmt);
$tokenStmt->close();
$tokenCount = $tokenRow ? (int) $tokenRow['c'] : 0;

$recentStmt = $conn->prepare('SELECT id, recipient, message_text, parts, status, source, created_at FROM sms_messages WHERE client_id = ? ORDER BY id DESC LIMIT 8');
$recentStmt->bind_param('i', $cid);
$recentStmt->execute();
$recent = db_fetch_all($recentStmt);
$recentStmt->close();

$schedChannel = 'sms';
$schedStatus = 'scheduled';
$schedStmt = $conn->prepare('SELECT id, campaign_name, recipients_count, scheduled_at FROM sms_campaigns WHERE client_id = ? AND channel = ? AND status = ? ORDER BY scheduled_at ASC');
$schedStmt->bind_param('iss', $cid, $schedChannel, $schedStatus);
$schedStmt->execute();
$scheduledRows = db_fetch_all($schedStmt);
$schedStmt->close();

$templates = $kycReady ? sms_templates($conn, $cid) : array();
$numberLists = $kycReady ? sms_number_lists($conn, $cid) : array();
$composer = array(
    'text' => $values['message_content'],
    'numbers' => $values['numbers'],
    'balance' => (int) $balances['sms'],
    'templates' => array(),
    'lists' => array()
);
foreach ($templates as $templateRow) {
    $composer['templates'][] = array(
        'id' => (int) $templateRow['id'],
        'label' => (string) $templateRow['label'],
        'text' => (string) $templateRow['message_text']
    );
}
foreach ($numberLists as $listRow) {
    $composer['lists'][] = array(
        'id' => (int) $listRow['id'],
        'label' => (string) $listRow['label'],
        'numbers' => (string) $listRow['numbers_text']
    );
}
$senderRequests = array();
if ($route['choose_sender']) {
    $senderStmt = $conn->prepare('SELECT sender_name, status FROM sms_sender_names WHERE client_id = ? ORDER BY id DESC');
    $senderStmt->bind_param('i', $cid);
    $senderStmt->execute();
    $senderRequests = db_fetch_all($senderStmt);
    $senderStmt->close();
}

$phoneName = $route['choose_sender'] ? '' : $route['sender'];
?>
<div class="mb-8 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Send SMS</h1>
        <p class="text-slate-500 text-sm">Credits you buy are sent from this page. The same account can also create an API token for OTP and alerts.</p>
    </div>
    <a href="shop.php?service=bulk-sms" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Buy SMS credit</a>
</div>

<?php if ($notice): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= number_format($balances['sms']) ?></div>
        <div class="dash-stat-label">SMS credits</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= number_format($sentToday) ?></div>
        <div class="dash-stat-label">Sent today</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= number_format($tokenCount) ?></div>
        <div class="dash-stat-label">Active API tokens</div>
    </div>
</div>

<?php if (!$kycReady): ?>
    <div class="dash-panel mb-6">
        <div class="p-6">
            <h2 class="font-heading font-semibold text-white text-lg mb-1">Identity first</h2>
            <p class="text-slate-400 text-sm mb-4">SMS credit can be bought before identity is approved. Sending, logs of new messages, and API tokens stay closed until then.</p>
            <a href="kyc.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</a>
        </div>
    </div>
<?php else: ?>
    <?php if (!$route['connected']): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">SMS credits stay on this account. Sending opens when the line is connected.</div>
    <?php elseif ((int) $balances['sms'] < 1): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">This account has no SMS credits. <a href="shop.php?service=bulk-sms" class="text-brand-300">Buy credit</a> before sending.</div>
    <?php endif; ?>
    <div class="grid lg:grid-cols-5 gap-6 mb-6">
        <div class="dash-panel lg:col-span-3 min-w-0">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New SMS</h3></div>
            <form id="sms-send" method="POST" class="p-5 space-y-4" x-data='smsComposer(<?= json_encode($composer, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' @submit="if (sending) { $event.preventDefault() } else { sending = true }">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Name</label>
                    <input type="text" name="campaign_name" required maxlength="120" value="<?= e($values['campaign_name']) ?>" placeholder="Annual general meeting notice" class="form-input">
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5">Who it is for</label>
                        <select name="audience" required class="form-input">
                            <option value="">Select</option>
                            <?php foreach ($audiences as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $values['audience'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5">Why</label>
                        <select name="purpose" required class="form-input">
                            <option value="">Select</option>
                            <?php foreach ($purposes as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $values['purpose'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Sender name</label>
                    <?php if ($route['choose_sender']): ?>
                        <select name="sender_id" required class="form-input">
                            <option value="">Select an approved name</option>
                            <?php foreach ($route['approved'] as $senderName): ?>
                                <option value="<?= e($senderName) ?>" <?= strtoupper($values['sender_id']) === $senderName ? 'selected' : '' ?>><?= e($senderName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" class="form-input" value="<?= e($phoneName !== '' ? $phoneName : 'Set when the line is connected') ?>" readonly>
                        <input type="hidden" name="sender_id" value="<?= e($phoneName) ?>">
                        <p class="text-slate-500 text-xs mt-1">Recipients see this name. It is the name registered for this SMS line.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1.5">
                        <label class="block text-slate-400 text-xs font-medium">Message</label>
                        <select class="text-xs bg-transparent text-brand-400 max-w-[11rem]" @change="pickTemplate($event.target.value); $event.target.selectedIndex = 0">
                            <option value="">Saved messages</option>
                            <?php foreach ($templates as $templateRow): ?>
                                <option value="<?= (int) $templateRow['id'] ?>"><?= e($templateRow['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <textarea name="message_content" x-model="text" required rows="4" maxlength="1000" class="form-input" placeholder="The exact text people should receive"><?= e($values['message_content']) ?></textarea>
                    <p class="text-xs mt-1" :class="estimate().short ? 'text-red-400' : 'text-slate-500'" x-text="estimate().label"></p>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1.5">
                        <label class="block text-slate-400 text-xs font-medium">Numbers</label>
                        <div class="flex items-center gap-3">
                            <select class="text-xs bg-transparent text-brand-400 max-w-[9rem]" @change="pickList($event.target.value); $event.target.selectedIndex = 0">
                                <option value="">Saved lists</option>
                            <?php foreach ($numberLists as $listRow): ?>
                                <?php $listCount = $listRow['numbers_text'] === '' ? 0 : substr_count(trim((string) $listRow['numbers_text']), "\n") + 1; ?>
                                <option value="<?= (int) $listRow['id'] ?>"><?= e($listRow['label']) ?> (<?= (int) $listCount ?>)</option>
                            <?php endforeach; ?>
                            </select>
                            <label class="text-brand-400 text-xs cursor-pointer">Upload .txt or .csv
                            <input type="file" accept=".txt,.csv,text/plain" class="hidden" @change="
                                const file = $event.target.files && $event.target.files[0];
                                if (!file) return;
                                const reader = new FileReader();
                                reader.onload = () => { numbers = String(reader.result || ''); };
                                reader.readAsText(file);
                                $event.target.value = '';
                            ">
                            </label>
                        </div>
                    </div>
                    <textarea name="numbers" x-model="numbers" required rows="6" class="form-input" placeholder="9800000001 or Name, 9800000001"><?= e($values['numbers']) ?></textarea>
                    <p class="text-slate-500 text-xs mt-1">Up to 500 numbers. A name or a heading on the same line is skipped. Duplicates are sent once. Nepal Telecom and Ncell only.</p>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Send later, optional, Nepal time</label>
                    <input type="datetime-local" name="scheduled_at" value="<?= e($values['scheduled_at']) ?>" class="form-input">
                </div>
                <?php
                $guardKey = 'send-sms';
                $declarationAccepted = isset($_POST['send_sms'], $_POST['legal_accept']) && $_POST['legal_accept'] === '1' && $err !== '';
                require __DIR__ . '/../includes/use-declaration.php';
                ?>
                <button type="submit" name="send_sms" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition disabled:opacity-60" :disabled="sending" x-text="sending ? 'Sending…' : (estimate().credits > 0 ? ('Send ' + estimate().credits + ' SMS') : 'Send SMS')">Send SMS</button>
            </form>
            <div class="px-5 pb-5 grid sm:grid-cols-2 gap-3">
                <form method="POST" class="flex gap-2" onsubmit="var send=document.getElementById('sms-send'); this.message_content.value=send.message_content.value; this.numbers.value=send.numbers.value;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="message_content" value="">
                    <input type="hidden" name="numbers" value="">
                    <input type="text" name="template_label" maxlength="60" required class="form-input" placeholder="Save message as">
                    <button type="submit" name="save_template" class="shrink-0 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs rounded-xl">Save</button>
                </form>
                <form method="POST" class="flex gap-2" onsubmit="var send=document.getElementById('sms-send'); this.message_content.value=send.message_content.value; this.numbers.value=send.numbers.value;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="message_content" value="">
                    <input type="hidden" name="numbers" value="">
                    <input type="text" name="list_label" maxlength="60" required class="form-input" placeholder="Save numbers as">
                    <button type="submit" name="save_list" class="shrink-0 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs rounded-xl">Save</button>
                </form>
            </div>
            <?php if ($templates || $numberLists): ?>
                <div class="px-5 pb-5 flex flex-wrap gap-2">
                    <?php foreach ($templates as $templateRow): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="template_id" value="<?= (int) $templateRow['id'] ?>">
                            <button type="submit" name="delete_template" class="text-xs text-slate-500 hover:text-red-400 bg-transparent border-0 cursor-pointer" onclick="return confirm('Delete this saved message?')">Delete <?= e($templateRow['label']) ?></button>
                        </form>
                    <?php endforeach; ?>
                    <?php foreach ($numberLists as $listRow): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="list_id" value="<?= (int) $listRow['id'] ?>">
                            <button type="submit" name="delete_list" class="text-xs text-slate-500 hover:text-red-400 bg-transparent border-0 cursor-pointer" onclick="return confirm('Delete this number list?')">Delete <?= e($listRow['label']) ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="lg:col-span-2 space-y-6 min-w-0">
            <?php if ($route['choose_sender']): ?>
                <div class="dash-panel">
                    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Sender name</h3></div>
                    <form method="POST" class="p-5 space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <p class="text-slate-400 text-sm">Request the name people should see. It works after approval, and only if that name is registered on the SMS line.</p>
                        <input type="text" name="requested_sender" maxlength="11" placeholder="SAHAKARI" class="form-input" required>
                        <button type="submit" name="request_sender" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm rounded-xl transition">Request name</button>
                        <?php if ($senderRequests): ?>
                            <ul class="text-sm space-y-1">
                                <?php foreach ($senderRequests as $senderRow): ?>
                                    <li class="flex justify-between gap-3"><span class="text-white"><?= e($senderRow['sender_name']) ?></span><span class="text-slate-500"><?= e(ucfirst($senderRow['status'])) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
            <a href="sms-api.php" class="dash-panel block p-5 hover:border-brand-500/40 transition">
                <h3 class="font-heading font-semibold text-white mb-1">API token</h3>
                <p class="text-slate-400 text-sm">Use the token from your own website or app, the same way an OTP is sent. <?= number_format($tokenCount) ?> active.</p>
            </a>
        </div>
    </div>
    <?php if ($scheduledRows): ?>
        <div class="dash-panel overflow-hidden mb-6">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Scheduled</h3></div>
            <div class="divide-y divide-slate-800">
                <?php foreach ($scheduledRows as $sched): ?>
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white text-sm"><?= e($sched['campaign_name']) ?></p>
                            <p class="text-slate-500 text-xs"><?= e(sms_format_time($sched['scheduled_at'])) ?> Nepal time · <?= (int) $sched['recipients_count'] ?> numbers · credits are used when it sends</p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="campaign_id" value="<?= (int) $sched['id'] ?>">
                            <button type="submit" name="cancel_campaign" class="text-red-400 text-xs bg-transparent border-0 cursor-pointer">Cancel</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="dash-panel overflow-hidden mb-6">
        <div class="dash-panel-header flex items-center justify-between">
            <h3 class="font-heading font-semibold text-white">Recent SMS</h3>
            <a href="sms-logs.php" class="text-brand-400 text-xs">All logs</a>
        </div>
        <?php if ($recent): ?>
            <div class="divide-y divide-slate-800">
                <?php foreach ($recent as $row): ?>
                    <div class="px-5 py-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white text-sm"><?= e($row['recipient']) ?> <span class="text-slate-500 text-xs uppercase"><?= e($row['status']) ?></span></p>
                            <p class="sms-log-clip text-slate-500 text-xs mt-1"><?= e($row['message_text']) ?></p>
                        </div>
                        <a href="sms-portal.php?reuse=<?= (int) $row['id'] ?>" class="shrink-0 text-brand-400 text-xs">Send again</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="p-5 text-slate-500 text-sm">No SMS sent from this account yet.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
<style>
.sms-log-clip { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
</style>
<script>
function smsNepalDigits(part) {
    var digits = String(part || '').replace(/\D/g, '');
    if (digits.length === 13 && digits.slice(0, 3) === '977') digits = digits.slice(3);
    if (digits.length === 11 && digits.charAt(0) === '0') digits = digits.slice(1);
    return /^9[78]\d{8}$/.test(digits) ? digits : '';
}
function smsComposer(seed) {
    return {
        text: seed.text || '',
        numbers: seed.numbers || '',
        balance: seed.balance || 0,
        sending: false,
        templates: seed.templates || [],
        lists: seed.lists || [],
        pickTemplate: function (id) {
            var found = this.templates.filter(function (item) { return String(item.id) === String(id); })[0];
            if (found) this.text = found.text;
        },
        pickList: function (id) {
            var found = this.lists.filter(function (item) { return String(item.id) === String(id); })[0];
            if (found) this.numbers = found.numbers;
        },
        estimate: function () {
            var text = String(this.text || '');
            var chars = Array.from(text);
            var unicode = /[^\n\r\x20-\x7E]/.test(text);
            var parts = 0;
            if (chars.length) {
                parts = unicode ? (chars.length <= 70 ? 1 : Math.ceil(chars.length / 67)) : (chars.length <= 160 ? 1 : Math.ceil(chars.length / 153));
            }
            var seen = {};
            var count = 0;
            var bad = 0;
            String(this.numbers || '').split(/[\s,;]+/).forEach(function (part) {
                if (!part || !/\d/.test(part)) return;
                var digits = smsNepalDigits(part);
                if (!digits) {
                    bad += 1;
                    return;
                }
                if (!seen[digits]) {
                    seen[digits] = 1;
                    count += 1;
                }
            });
            var credits = parts * count;
            var label = chars.length
                ? chars.length + ' characters · ' + parts + ' credit' + (parts === 1 ? '' : 's') + ' per number'
                : 'English up to 160 characters is 1 SMS. Nepali up to 70 characters is 1 SMS.';
            if (count) {
                label += ' · ' + count + ' number' + (count === 1 ? '' : 's') + ' · ' + credits + ' credit' + (credits === 1 ? '' : 's');
            }
            if (bad) {
                label += ' · ' + bad + ' line' + (bad === 1 ? '' : 's') + ' not a Nepal mobile';
            }
            if (credits > this.balance) {
                label += ' · not enough credits (' + this.balance + ' left)';
            }
            return { parts: parts, count: count, credits: credits, short: credits > this.balance && credits > 0, label: label };
        }
    };
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
