<?php
/**
 * Send SMS page: reads the form posts (send, schedule, templates, lists, sender requests)
 * and prepares the values the page shows. Included by client/sms-portal.php, so it shares
 * that page's variables ($conn, $cid, $msg, $err ...).
 */


$cid = (int) get_client_id();
sms_run_queue($conn, 5, 15);
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
                if (empty($result['background'])) {
                    $msg .= ' See who received it in SMS logs.';
                } else {
                    sms_start_background($conn, (int) $result['campaign_id']);
                }
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
