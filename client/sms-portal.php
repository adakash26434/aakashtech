<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
sms_run_queue($conn, 5);
$notice = flash('billing');
$msg = '';
$err = '';
$sentLog = 0;
$balances = billing_unit_balances($conn, $cid);
$kycReady = billing_kyc_approved($conn, $cid);
$sendOpen = true;
$unverifiedRoom = $kycReady ? null : max(0, sms_unverified_cap() - sms_unverified_used($conn, $cid));
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
    $cancelled = sms_cancel_scheduled($conn, $cid, $cancelId);
    if ($cancelled === 'refunded') {
        $msg = 'Scheduled SMS cancelled. The held credits are back on this account.';
        $balances = billing_unit_balances($conn, $cid);
    } elseif ($cancelled === 'released') {
        $msg = 'Scheduled SMS cancelled. No credits were held.';
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
    $saved = sms_save_number_list($conn, $cid, isset($_POST['list_label']) ? $_POST['list_label'] : '', $values['numbers'], isset($_POST['list_kind']) ? $_POST['list_kind'] : 'program');
    if ($saved === '') {
        $msg = 'Number list saved. Choose it again from Saved lists the next time this program or regular notice is sent. A repeated name replaces that list.';
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
    $guardError = billing_form_guard_check('send-sms', $_POST, false);
    if ($guardError !== '') {
        $err = $guardError;
    } elseif (billing_posted($_POST, 'legal_accept') !== '1') {
        $err = 'Accept the declaration before this can be sent.';
    } else {
        $_SESSION['sms_declared'] = $cid;
        $scheduled = trim($values['scheduled_at']);
        if ($scheduled !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $scheduled)) {
            $scheduled = '';
        }
        $scheduledValue = $scheduled !== '' ? str_replace('T', ' ', $scheduled) . ':00' : '';
        if (preg_match('/\[[^\]\r\n]{1,40}\]/u', $values['message_content'])) {
            $err = 'Replace the words in brackets, such as [मिति] or [code], with your own details before sending.';
        }
        if ($err === '') {
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
            if (!empty($result['campaign_id'])) {
                $msg .= ' See who received it in SMS logs.';
                $sentLog = (int) $result['campaign_id'];
            }
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

$recentStmt = $conn->prepare('SELECT id, recipient, message_text, parts, status, source, created_at FROM sms_messages WHERE client_id = ? ORDER BY created_at DESC, id DESC LIMIT 8');
$recentStmt->bind_param('i', $cid);
$recentStmt->execute();
$recent = db_fetch_all($recentStmt);
$recentStmt->close();

$creditNoteStmt = $conn->prepare('SELECT credits, note, created_at, reversed_at FROM sms_credit_notes WHERE client_id = ? ORDER BY id DESC LIMIT 8');
$creditNoteStmt->bind_param('i', $cid);
$creditNoteStmt->execute();
$creditNotes = db_fetch_all($creditNoteStmt);
$creditNoteStmt->close();

$schedChannel = 'sms';
$schedStatus = 'scheduled';
$schedStmt = $conn->prepare('SELECT id, campaign_name, recipients_count, scheduled_at FROM sms_campaigns WHERE client_id = ? AND channel = ? AND status = ? ORDER BY scheduled_at ASC');
$schedStmt->bind_param('iss', $cid, $schedChannel, $schedStatus);
$schedStmt->execute();
$scheduledRows = db_fetch_all($schedStmt);
$schedStmt->close();

$templates = $sendOpen ? sms_templates($conn, $cid) : array();
$numberLists = $sendOpen ? sms_number_lists($conn, $cid) : array();
$composer = array(
    'text' => $values['message_content'],
    'numbers' => $values['numbers'],
    'balance' => (int) $balances['sms'],
    'when' => (string) $values['scheduled_at'],
    'csrf' => csrf_token(),
    'templates' => array(),
    'lists' => array()
);
$sampleMessages = sms_sample_messages();
foreach ($sampleMessages as $sampleMessage) {
    $composer['templates'][] = $sampleMessage;
}
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
<div class="mb-6 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-2">Send SMS</h1>
        <div class="sms-head-strip">
            <span class="sms-head-pill is-main"><strong><?= number_format((int) $balances['sms']) ?></strong> credits left</span>
            <span class="sms-head-pill"><strong><?= number_format($sentToday) ?></strong> sent today</span>
            <a class="sms-head-link" href="sms-logs.php">SMS logs</a>
            <a class="sms-head-link" href="sms-api.php">API token</a>
            <a class="sms-head-link" href="manual.php#sms">नेपाली चरण</a>
        </div>
    </div>
    <a href="shop.php?service=bulk-sms" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-semibold rounded-xl transition">Buy SMS credit</a>
</div>

<?php if ($notice): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?><?php if ($sentLog > 0): ?> <a class="text-brand-300" href="sms-logs.php?send=<?= (int) $sentLog ?>">Open this send</a><?php endif; ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<?php if (!$sendOpen): ?>
    <div class="dash-panel mb-6">
        <div class="p-6">
            <h2 class="font-heading font-semibold text-white text-lg mb-1">Identity first</h2>
            <p class="text-slate-400 text-sm mb-4">This account can send <?= number_format((int) $unverifiedRoom) ?> more SMS before identity is approved. A send over 100 SMS in total needs identity, so a new account cannot be used for a large blast.</p>
            <a href="kyc.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</a>
        </div>
    </div>
<?php else: ?>
    <?php if (!$kycReady): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">More than 100 SMS needs KYC. <?= number_format((int) $unverifiedRoom) ?> SMS are still open on this account. Please update KYC. <a href="kyc.php" class="text-brand-300">Update KYC</a></div>
    <?php endif; ?>
    <?php if (!$route['connected']): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">SMS credits stay on this account. Sending opens when the line is connected.</div>
    <?php elseif ((int) $balances['sms'] < 1): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">This account has no SMS credits. <a href="shop.php?service=bulk-sms" class="text-brand-300">Buy credit</a> before sending.</div>
    <?php elseif ((int) $balances['sms'] < 100): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm"><?= number_format((int) $balances['sms']) ?> SMS credits left. <a href="shop.php?service=bulk-sms" class="text-brand-300">Buy more</a> before a larger send is refused.</div>
    <?php endif; ?>
    <div class="grid lg:grid-cols-5 gap-6 mb-6">
        <div class="dash-panel lg:col-span-5 min-w-0">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New SMS</h3></div>
            <form id="sms-send" method="POST" autocomplete="off" class="p-5 space-y-5" x-data='smsComposer(<?= json_encode($composer, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' @submit="if (sending) { $event.preventDefault() } else { sending = true }">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php
                $audiencePick = $values['audience'] !== '' ? $values['audience'] : 'cooperative';
                $purposePick = $values['purpose'] !== '' ? $values['purpose'] : 'notice';
                if (!isset($audiences[$audiencePick])) {
                    $audiencePick = (string) key($audiences);
                }
                if (!isset($purposes[$purposePick])) {
                    $purposePick = (string) key($purposes);
                }
                ?>
                <?php if ($route['choose_sender']): ?>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Sender name</label>
                    <select name="sender_id" required class="form-input">
                        <option value="">Select an approved name</option>
                        <?php foreach ($route['approved'] as $senderName): ?>
                            <option value="<?= e($senderName) ?>" <?= strtoupper($values['sender_id']) === $senderName ? 'selected' : '' ?>><?= e($senderName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="sender_id" value="<?= e($phoneName) ?>">
                <?php endif; ?>
                <div class="grid lg:grid-cols-2 gap-6">
                <div class="sms-step">
                    <div class="sms-step-head">
                        <span class="sms-step-no">1</span>
                        <label class="sms-step-title" for="sms-numbers">Who gets it</label>
                        <span class="sms-count-chip" :class="estimate().count ? 'is-on' : ''" x-text="estimate().count ? (estimate().count + ' numbers') : 'Up to 500'"></span>
                    </div>
                    <div class="sms-tools">
                        <button type="button" class="sms-upload-open" @click="uploadOpen = true; importOk = false; importNote = ''">&#8679; Upload Excel or CSV</button>
                        <select class="form-input sms-tool-select" @change="pickList($event.target.value); $event.target.selectedIndex = 0">
                            <option value="">Saved lists</option>
                                <?php
                                $listGroups = array('program' => 'Program', 'regular' => 'Regular');
                                foreach ($listGroups as $listKind => $listHeading):
                                    $groupLists = array();
                                    foreach ($numberLists as $listRow) {
                                        $rowKind = (isset($listRow['list_kind']) && $listRow['list_kind'] === 'regular') ? 'regular' : 'program';
                                        if ($rowKind === $listKind) {
                                            $groupLists[] = $listRow;
                                        }
                                    }
                                    if (!$groupLists) {
                                        continue;
                                    }
                                ?>
                                    <optgroup label="<?= e($listHeading) ?>">
                                        <?php foreach ($groupLists as $listRow): ?>
                                            <?php $listCount = $listRow['numbers_text'] === '' ? 0 : substr_count(trim((string) $listRow['numbers_text']), "\n") + 1; ?>
                                            <option value="<?= (int) $listRow['id'] ?>"><?= e($listRow['label']) ?> (<?= (int) $listCount ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        <button type="button" class="sms-clear" x-show="String(numbers || '').trim() !== ''" x-cloak @click="if (confirm('Clear all numbers?')) { numbers = ''; reviewing = false; importNote = ''; }">Clear</button>
                    </div>
                    <input type="text" inputmode="numeric" autocomplete="off" class="form-input mb-2" placeholder="Type one mobile, then press Enter" @keydown.enter.prevent="addNumber($event.target)">
                    <textarea id="sms-numbers" name="numbers" x-model="numbers" required rows="9" autocomplete="off" class="form-input sms-numbers" placeholder="9800000001&#10;Ram, 9800000002" @input="reviewing = false; reviewNote = ''"><?= e($values['numbers']) ?></textarea>
                    <p class="text-xs mt-2" :class="importOk ? 'text-emerald-600' : 'text-slate-500'" x-show="importNote && !uploadOpen" x-text="importNote"></p>
                    <div class="sms-upload-shade" x-show="uploadOpen" x-cloak @click.self="uploadOpen = false" @keydown.escape.window="uploadOpen = false">
                        <div class="sms-upload-box" role="dialog" aria-modal="true" aria-labelledby="sms-upload-title">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p id="sms-upload-title" class="sms-upload-title">Upload numbers</p>
                                    <p class="text-slate-500 text-xs mt-1">Excel .xlsx or .csv, up to 2 MB and 500 numbers.</p>
                                </div>
                                <button type="button" class="sms-upload-close" @click="uploadOpen = false" aria-label="Close">&times;</button>
                            </div>
                            <label class="sms-drop" :class="{ 'is-over': dragging, 'is-busy': importing }" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; importDropped($event)">
                                <input type="file" accept=".xlsx,.csv,.txt,text/csv,text/plain" class="hidden" @change="importFile($event)" :disabled="importing">
                                <span class="sms-drop-icon" aria-hidden="true">&#8679;</span>
                                <span class="sms-drop-main" x-text="importing ? 'Reading the file…' : 'Choose a file, or drop it here'"></span>
                                <span class="text-slate-500 text-xs">.xlsx · .csv</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 mt-3" x-show="String(numbers || '').trim() !== ''">
                                <input type="checkbox" x-model="keepTyped">
                                Keep the numbers already in the box
                            </label>
                            <p class="text-xs mt-3" :class="importOk ? 'text-emerald-600' : 'text-rose-600'" x-show="importNote && !importing" x-text="importNote"></p>
                            <ul class="sms-upload-tips">
                                <li>Put a heading <b>mobile</b> on the number column. To use {name}, add <b>firstname</b> and <b>lastname</b>, or <b>name</b>.</li>
                                <li>Nepali headings मोबाइल, नाम, थर and Nepali digits ९८०… are read too.</li>
                                <li>98…, 977 98… and 098… all work. Repeated numbers are kept once and wrong ones are skipped.</li>
                            </ul>
                            <div class="sms-upload-samples">
                                <span>Sample files:</span>
                                <a href="sms-sample.php?type=mobile">Mobile only</a>
                                <a href="sms-sample.php?type=names">First name, last name, mobile</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sms-step">
                    <div class="sms-step-head">
                        <span class="sms-step-no">2</span>
                        <label class="sms-step-title" for="sms-text">Message</label>
                        <select class="form-input sms-tool-select ml-auto" @change="pickTemplate($event.target.value); $event.target.selectedIndex = 0">
                            <option value="">Insert a sample</option>
                            <optgroup label="सहकारी नमूना">
                                <?php foreach ($sampleMessages as $sampleMessage): ?>
                                    <option value="<?= e($sampleMessage['id']) ?>"><?= e($sampleMessage['label']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php if ($templates): ?>
                                <optgroup label="Saved messages">
                                    <?php foreach ($templates as $templateRow): ?>
                                        <option value="<?= (int) $templateRow['id'] ?>"><?= e($templateRow['label']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    <textarea id="sms-text" name="message_content" x-model="text" required rows="9" maxlength="1000" autocomplete="off" class="form-input" placeholder="The exact text people should receive" @input="reviewing = false; reviewNote = ''"><?= e($values['message_content']) ?></textarea>
                    <label class="flex items-center gap-2 text-sm text-slate-600 mt-2" x-show="smsHasNames(numbers) || nameFirst()" x-cloak>
                        <input type="checkbox" :checked="nameFirst()" @change="setNameFirst($event.target.checked)">
                        Start each SMS with the person's name
                    </label>
                    <p class="sms-credit-bar" :class="estimate().short ? 'is-short' : ''" x-text="estimate().label"></p>
                    <p class="text-slate-500 text-xs mt-2" x-show="estimate().brackets">Replace the words in [brackets] before this can send. <button type="button" class="text-brand-400 bg-transparent border-0 cursor-pointer p-0" @click="text += (text && !text.endsWith(' ') ? ' ' : '') + '{name}'">Insert {name}</button></p>
                    <div class="sms-preview" x-show="text" x-cloak>
                        <p class="sms-preview-label">People will read</p>
                        <p x-text="estimate().preview"></p>
                    </div>
                </div>
                </div>
                <details class="sms-more text-sm text-slate-400" <?= ($err !== '' || $values['campaign_name'] !== '' || trim((string) $values['scheduled_at']) !== '') ? 'open' : '' ?>>
                    <summary class="sms-more-head">
                        <span class="sms-step-no is-soft">3</span>
                        <span class="sms-step-title">Name, who it is for, and send later</span>
                        <span class="sms-more-hint" x-text="when ? ('Sends ' + when.replace('T', ' ')) : 'Optional · sends now'"></span>
                    </summary>
                    <div class="grid md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5">Name this send</label>
                            <input type="text" name="campaign_name" maxlength="120" autocomplete="off" value="<?= e($values['campaign_name']) ?>" placeholder="AGM notice" class="form-input">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5">Who it is for</label>
                            <select name="audience" required class="form-input">
                                <?php foreach ($audiences as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $audiencePick === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5">Why</label>
                            <select name="purpose" required class="form-input">
                                <?php foreach ($purposes as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $purposePick === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="max-w-sm mt-3">
                        <label class="block text-slate-400 text-xs font-medium mb-1.5">Nepal time</label>
                        <input type="datetime-local" name="scheduled_at" value="<?= e($values['scheduled_at']) ?>" class="form-input" x-model="when" @input="reviewing = false; reviewNote = ''">
                        <p class="text-slate-500 text-xs mt-1">Leave this empty to send now. Credits stay held until it sends.</p>
                    </div>
                </details>
                <?php
                $guardKey = 'send-sms';
                $declarationAccepted = (isset($_POST['send_sms'], $_POST['legal_accept']) && $_POST['legal_accept'] === '1' && $err !== '')
                    || (isset($_SESSION['sms_declared']) && (int) $_SESSION['sms_declared'] === $cid);
                $guardMath = false;
                require __DIR__ . '/../includes/use-declaration.php';
                ?>
                <div class="sms-review" x-show="reviewing" x-cloak>
                    <p class="sms-preview-label">Check once</p>
                    <dl class="sms-review-grid">
                        <div><dt>To</dt><dd x-text="estimate().count + ' numbers'"></dd></div>
                        <div><dt>Credits</dt><dd x-text="estimate().credits + ' · ' + (balance - estimate().credits).toLocaleString('en-IN') + ' left after'"></dd></div>
                        <div><dt>When</dt><dd x-text="when ? when.replace('T', ' ') + ' Nepal time' : 'Right now'"></dd></div>
                    </dl>
                    <p class="mt-3 whitespace-pre-wrap" x-text="estimate().preview"></p>
                </div>
                <div class="sms-actionbar">
                    <div class="sms-actionbar-sum">
                        <span><strong x-text="estimate().count"></strong> numbers</span>
                        <span><strong x-text="estimate().credits"></strong> credits</span>
                        <span class="sms-actionbar-left" :class="estimate().short ? 'is-short' : ''" x-text="(balance - estimate().credits).toLocaleString('en-IN') + ' left after'"></span>
                        <span class="sms-actionbar-note" x-show="reviewNote && !reviewing" x-cloak x-text="reviewNote"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="px-4 py-3 text-slate-400 text-sm" x-show="reviewing" x-cloak @click="reviewing = false">Back</button>
                        <button type="button" class="px-8 py-3 bg-brand-500 hover:bg-brand-400 text-white text-sm font-semibold rounded-xl" x-show="!reviewing" @click="openReview()">Review</button>
                        <button type="submit" name="send_sms" class="px-8 py-3 bg-brand-500 hover:bg-brand-400 text-white text-sm font-semibold rounded-xl disabled:opacity-60" x-show="reviewing" x-cloak :disabled="sending || !reviewing" x-text="sending ? 'Sending…' : (when ? ('Schedule ' + estimate().credits + ' SMS') : ('Send ' + estimate().credits + ' SMS'))">Send SMS</button>
                    </div>
                </div>
            </form>
            <details class="px-5 pb-5">
                <summary class="cursor-pointer text-sm text-slate-400">Save this message or number list for next time</summary>
            <div class="grid sm:grid-cols-2 gap-3 mt-3">
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
                    <select name="list_kind" class="form-input max-w-[9rem]">
                        <option value="program">Program</option>
                        <option value="regular">Regular</option>
                    </select>
                    <input type="text" name="list_label" maxlength="60" required class="form-input" placeholder="AGM members, or Monthly interest">
                    <button type="submit" name="save_list" class="shrink-0 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs rounded-xl">Save list</button>
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
            </details>
        </div>
        <?php if ($route['choose_sender']): ?>
        <div class="lg:col-span-2 space-y-6 min-w-0">
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
        </div>
        <?php endif; ?>
    </div>
    <?php if ($scheduledRows): ?>
        <div class="dash-panel overflow-hidden mb-6">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Scheduled</h3></div>
            <div class="divide-y divide-slate-800">
                <?php foreach ($scheduledRows as $sched): ?>
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white text-sm"><?= e($sched['campaign_name']) ?></p>
                            <p class="text-slate-500 text-xs"><?= e(sms_format_time($sched['scheduled_at'])) ?> Nepal time · <?= (int) $sched['recipients_count'] ?> numbers · credits stay held until this sends. Cancel returns them.</p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="campaign_id" value="<?= (int) $sched['id'] ?>">
                            <button type="submit" name="cancel_campaign" class="text-red-400 text-xs bg-transparent border-0 cursor-pointer" onclick="return confirm('Cancel this scheduled SMS?')">Cancel</button>
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
                            <p class="text-white text-sm"><?= e($row['recipient']) ?> <span class="sms-state sms-state-<?= e(in_array($row['status'], array('sent', 'failed'), true) ? $row['status'] : 'wait') ?>"><?= e(ucfirst((string) $row['status'])) ?></span></p>
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
    <?php if ($creditNotes): ?>
        <details class="dash-panel overflow-hidden mb-6">
            <summary class="dash-panel-header cursor-pointer"><h3 class="font-heading font-semibold text-white">Credits added</h3></summary>
            <div class="divide-y divide-slate-800">
                <?php foreach ($creditNotes as $creditNote): ?>
                    <div class="p-4 flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-slate-300 text-sm"><?= e($creditNote['note']) ?></p>
                        <p class="text-white text-sm"><?= number_format((int) $creditNote['credits']) ?> SMS<?= trim((string) $creditNote['reversed_at']) !== '' ? ' · Taken back' : '' ?> · <?= e(sms_format_time($creditNote['created_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
<?php endif; ?>
<style>
.sms-log-clip { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
.sms-credit-bar { margin-top: 8px; padding: 10px 12px; border-radius: 10px; background: #e7f6f3; color: #075e54; font-size: 13px; font-weight: 600; }
.sms-credit-bar.is-short { background: #fde8e8; color: #9f1239; }
.sms-preview { margin-top: 10px; max-width: 280px; padding: 14px 14px 12px; border-radius: 18px; background: #f4f8f6; border: 1px solid #dfe9e4; }
.sms-preview-label { margin: 0 0 6px; color: #4d675f; font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.sms-preview p:last-child { margin: 0; color: #183b31; font-size: 14px; line-height: 1.45; white-space: pre-wrap; }
.sms-review { margin-bottom: 12px; padding: 14px; border-radius: 16px; background: #e7f6f3; color: #183b31; }
.sms-review p { margin: 0; }
.sms-head-strip { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.sms-head-pill { display: inline-flex; align-items: baseline; gap: 5px; padding: 5px 12px; border: 1px solid #dfe9e4; border-radius: 999px; background: #fff; color: #4d675f; font-size: 13px; }
.sms-head-pill strong { color: #183b31; font-size: 15px; }
.sms-head-pill.is-main { background: #e7f6f3; border-color: #c4e6dd; }
.sms-head-pill.is-main strong { color: #075e54; }
.sms-head-link { padding: 5px 4px; color: #075e54; font-size: 13px; font-weight: 600; }
.sms-step { min-width: 0; }
.sms-step-head { display: flex; align-items: center; gap: 10px; min-height: 44px; margin-bottom: 10px; }
.sms-step-no { display: inline-flex; flex-shrink: 0; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: #0b8b7a; color: #fff; font-size: 13px; font-weight: 700; }
.sms-step-no.is-soft { background: #d7efe9; color: #075e54; }
.sms-step-title { color: #183b31; font-size: 15px; font-weight: 700; }
.sms-count-chip { margin-left: auto; padding: 3px 10px; border-radius: 999px; background: #f1f6f3; color: #4d675f; font-size: 12px; font-weight: 600; }
.sms-count-chip.is-on { background: #dcf3e8; color: #08734f; }
.sms-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; }
.portal-shell .sms-tool-select { width: auto; min-width: 12rem; max-width: 16rem; min-height: 42px; font-size: 14px; }
.sms-clear { margin-left: auto; border: 0; background: transparent; color: #b42318; font-size: 13px; font-weight: 600; cursor: pointer; }
.portal-shell textarea.sms-numbers { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 15px; line-height: 1.6; }
.sms-more { border: 1px solid #dfe9e4; border-radius: 16px; padding: 12px 16px; background: #fbfdfc; }
.sms-more[open] { padding-bottom: 16px; }
.sms-more-head { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; cursor: pointer; list-style: none; }
.sms-more-head::-webkit-details-marker { display: none; }
.sms-more-head .sms-step-title { flex: 1 1 0; min-width: 0; }
.sms-more-hint { color: #4d675f; font-size: 13px; }
@media (max-width: 640px) {
    .sms-more-hint { flex-basis: 100%; padding-left: 36px; }
    .portal-shell .sms-tool-select { min-width: 0; flex: 1 1 8rem; }
    .sms-actionbar { margin: 0 -20px -20px; padding: 12px 16px; }
    .sms-actionbar > div:last-child { width: 100%; }
    .sms-actionbar > div:last-child button[type="submit"], .sms-actionbar > div:last-child button.bg-brand-500 { flex: 1; }
}
.sms-actionbar { position: sticky; bottom: 0; z-index: 20; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin: 0 -20px -20px; padding: 14px 20px; border-top: 1px solid #dfe9e4; border-radius: 0 0 16px 16px; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(6px); box-shadow: 0 -8px 20px rgba(24, 59, 49, 0.05); }
.sms-actionbar-sum { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 16px; color: #4d675f; font-size: 14px; }
.sms-actionbar-sum strong { color: #183b31; font-size: 17px; }
.sms-actionbar-left.is-short { color: #b42318; font-weight: 700; }
.sms-actionbar-note { flex-basis: 100%; color: #b42318; font-size: 13px; font-weight: 600; }
.sms-review-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 0; }
.sms-review-grid dt { color: #4d675f; font-size: 12px; }
.sms-review-grid dd { margin: 2px 0 0; color: #183b31; font-size: 15px; font-weight: 700; }
.sms-upload-open { display: inline-flex; align-items: center; gap: 6px; min-height: 42px; padding: 0 14px; border: 1px solid #b7cdc4; border-radius: 12px; background: #f7fbf9; color: #075e54; font-size: 14px; font-weight: 700; cursor: pointer; }
.sms-upload-open:hover { background: #e7f6f3; }
.sms-upload-shade { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 32, 27, 0.55); }
.sms-upload-box { width: 100%; max-width: 520px; max-height: calc(100vh - 32px); overflow-y: auto; padding: 22px; border-radius: 20px; background: #fff; color: #183b31; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25); }
.sms-upload-title { margin: 0; font-size: 18px; font-weight: 700; color: #183b31; }
.sms-upload-close { border: 0; background: transparent; color: #4d675f; font-size: 26px; line-height: 1; cursor: pointer; }
.sms-drop { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; margin-top: 16px; padding: 30px 16px; border: 2px dashed #b7cdc4; border-radius: 16px; background: #f7fbf9; text-align: center; cursor: pointer; transition: background 0.15s, border-color 0.15s; }
.sms-drop:hover, .sms-drop.is-over { border-color: #128c7e; background: #e7f6f3; }
.sms-drop.is-busy { opacity: 0.7; cursor: progress; }
.sms-drop-icon { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 50%; background: #d7efe9; color: #075e54; font-size: 22px; }
.sms-drop-main { color: #075e54; font-size: 15px; font-weight: 700; }
.sms-upload-tips { margin: 14px 0 0; padding-left: 18px; color: #4d675f; font-size: 12px; line-height: 1.55; list-style: disc; }
.sms-upload-samples { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #e3ece8; font-size: 13px; color: #4d675f; }
.sms-upload-samples a { color: #075e54; font-weight: 700; }
</style>
<script>
function smsNepalDigits(part) {
    var digits = String(part || '').replace(/\D/g, '');
    if (digits.length === 13 && digits.slice(0, 3) === '977') digits = digits.slice(3);
    if (digits.length === 11 && digits.charAt(0) === '0') digits = digits.slice(1);
    return /^9[78]\d{8}$/.test(digits) ? digits : '';
}
function smsFillName(text, person) {
    return String(text).split('{name}').join(person || '').replace(/\s+,/g, ',').replace(/^[\s,]+/, '').replace(/ {2,}/g, ' ').trim();
}
function smsHasNames(numbers) {
    return String(numbers || '').split(/\r?\n/).some(function (line) {
        return String(line).split(/[\s,;]+/).some(function (part) {
            return part !== '' && !/\d/.test(part);
        });
    });
}
function smsComposer(seed) {
    return {
        text: seed.text || '',
        numbers: seed.numbers || '',
        balance: seed.balance || 0,
        when: seed.when || '',
        sending: false,
        reviewing: false,
        importing: false,
        importNote: '',
        importOk: false,
        uploadOpen: false,
        dragging: false,
        keepTyped: true,
        reviewNote: '',
        nameFirst: function () {
            return String(this.text || '').indexOf('{name}') === 0;
        },
        setNameFirst: function (on) {
            var text = String(this.text || '');
            if (on && text.indexOf('{name}') === -1) {
                this.text = '{name}, ' + text.replace(/^\s+/, '');
            } else if (!on && text.indexOf('{name}') === 0) {
                this.text = text.replace(/^\{name\}[\s,]*/, '');
            }
            this.reviewing = false;
        },
        importDropped: function (event) {
            var file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
            if (file) this.sendFile(file);
        },
        addNumber: function (input) {
            var raw = String(input.value || '').trim();
            if (!raw) return;
            var digits = smsNepalDigits(raw);
            if (!digits) {
                this.importNote = 'Use a 10-digit Nepal mobile, such as 9800000001.';
                return;
            }
            var lines = String(this.numbers || '').split(/\r?\n/);
            var already = false;
            lines.forEach(function (line) {
                if (String(line).indexOf(digits) !== -1) already = true;
            });
            if (already) {
                this.importNote = digits + ' is already in the list.';
                input.value = '';
                return;
            }
            var existing = String(this.numbers || '').trim();
            this.numbers = existing ? existing + '\n' + digits : digits;
            this.importNote = digits + ' added.';
            this.reviewing = false;
            input.value = '';
        },
        openReview: function () {
            var form = document.getElementById('sms-send');
            if (form && !form.reportValidity()) return;
            var estimate = this.estimate();
            if (!estimate.count) {
                this.reviewNote = 'Add at least one Nepal mobile.';
                return;
            }
            if (estimate.brackets) {
                this.reviewNote = 'Replace the words in brackets first.';
                return;
            }
            if (estimate.short) {
                this.reviewNote = 'There are not enough credits for this send.';
                return;
            }
            this.reviewNote = '';
            this.reviewing = true;
            this.$nextTick(function () {
                var box = document.querySelector('.sms-review');
                if (box) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },
        templates: seed.templates || [],
        lists: seed.lists || [],
        importFile: function (event) {
            var file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (file) this.sendFile(file);
        },
        sendFile: function (file) {
            var lower = String(file.name || '').toLowerCase();
            this.importOk = false;
            if (/\.xls$/.test(lower)) {
                this.importNote = 'Open the file in Excel and save it as .xlsx or .csv, then upload again.';
                return;
            }
            if (!/\.(xlsx|csv|txt)$/.test(lower)) {
                this.importNote = 'Upload an Excel .xlsx file or a .csv file.';
                return;
            }
            var self = this;
            self.importing = true;
            self.importNote = 'Reading the file…';
            var data = new FormData();
            data.append('csrf_token', seed.csrf || '');
            data.append('sheet', file);
            fetch('sms-import.php', { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (body) {
                    self.importing = false;
                    if (body && body.numbers) {
                        var existing = String(self.numbers || '').trim();
                        var added = body.count;
                        if (self.keepTyped && existing) {
                            var have = {};
                            existing.split(/\r?\n/).forEach(function (line) {
                                String(line).split(/[\s,;]+/).forEach(function (part) {
                                    var digits = smsNepalDigits(part);
                                    if (digits) have[digits] = true;
                                });
                            });
                            var fresh = String(body.numbers).split('\n').filter(function (line) {
                                var parts = String(line).split(/[\s,;]+/);
                                return !have[smsNepalDigits(parts[parts.length - 1])];
                            });
                            added = fresh.length;
                            self.numbers = fresh.length ? existing + '\n' + fresh.join('\n') : existing;
                        } else {
                            self.numbers = body.numbers;
                        }
                        self.importOk = true;
                        self.reviewing = false;
                        self.importNote = added + ' numbers added from ' + file.name + '.';
                        if (smsHasNames(body.numbers) && String(self.text || '').indexOf('{name}') === -1) {
                            self.setNameFirst(true);
                            self.importNote += ' Each SMS will start with that person\'s name.';
                        }
                        self.uploadOpen = false;
                    } else {
                        self.importNote = body && body.error ? body.error : 'That file could not be read.';
                    }
                })
                .catch(function () {
                    self.importing = false;
                    self.importNote = 'That file could not be read.';
                });
        },
        pickTemplate: function (id) {
            var found = this.templates.filter(function (item) { return String(item.id) === String(id); })[0];
            if (!found) return;
            var keepName = this.nameFirst() && String(found.text).indexOf('{name}') === -1;
            this.text = found.text;
            if (keepName) this.setNameFirst(true);
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
            var credits = 0;
            var previewName = '';
            var usesName = text.indexOf('{name}') !== -1;
            var creditParts = function (body) {
                var bodyChars = Array.from(body);
                if (!bodyChars.length) return 0;
                var bodyUnicode = /[^\n\r\x20-\x7E]/.test(body);
                return bodyUnicode ? (bodyChars.length <= 70 ? 1 : Math.ceil(bodyChars.length / 67)) : (bodyChars.length <= 160 ? 1 : Math.ceil(bodyChars.length / 153));
            };
            String(this.numbers || '').split(/\r?\n/).forEach(function (line) {
                var bits = String(line || '').trim().split(/[\s,;]+/);
                var nums = [];
                var words = [];
                bits.forEach(function (part) {
                    if (!part) return;
                    if (!/\d/.test(part)) {
                        words.push(part);
                        return;
                    }
                    var digits = smsNepalDigits(part);
                    if (!digits) {
                        bad += 1;
                        return;
                    }
                    if (!seen[digits]) {
                        seen[digits] = 1;
                        nums.push(digits);
                    }
                });
                var person = words.join(' ');
                if (!previewName && person) previewName = person;
                nums.forEach(function () {
                    count += 1;
                    var body = usesName ? smsFillName(text, person) : text;
                    credits += body ? creditParts(body) : parts;
                });
            });
            if (!usesName) {
                credits = parts * count;
            }
            var language = unicode ? 'Nepali' : 'English';
            var label = chars.length
                ? language + ' · ' + chars.length + ' characters · ' + parts + ' credit' + (parts === 1 ? '' : 's') + ' per number'
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
            var brackets = /\[[^\]\r\n]{1,40}\]/.test(text);
            if (brackets) {
                label += ' · replace the words in brackets';
            }
            var preview = usesName ? smsFillName(text, previewName) : text;
            var previewChars = Array.from(preview);
            if (previewChars.length > 180) {
                preview = previewChars.slice(0, 180).join('') + '…';
            }
            return { parts: parts, count: count, credits: credits, short: (credits > this.balance && credits > 0) || brackets, label: label, preview: preview, brackets: brackets };
        }
    };
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
