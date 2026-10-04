<?php
/**
 * SMS: Delivery reports. The provider calls api/sms-dlr.php when the phone network
 * says a message arrived or failed. Works with any provider that can call a URL:
 * set the callback in the provider panel to
 *   https://YOUR-SITE/api/sms-dlr.php?key=YOUR_DLR_KEY
 */

/** On unless the admin switched it off on the SMS line page. */
function sms_refund_undelivered_on($conn)
{
    return billing_setting($conn, 'sms_refund_undelivered') !== '0';
}

function sms_dlr_normalise_status($raw)
{
    $raw = strtolower(trim((string) $raw));
    if ($raw === '') {
        return '';
    }
    foreach (array('undeliv', 'fail', 'reject', 'expire', 'error', 'blocked', 'invalid', 'dnd', 'barred') as $bad) {
        if (strpos($raw, $bad) !== false) {
            return 'failed';
        }
    }
    foreach (array('deliv', 'success', 'received', 'delivrd', 'ok') as $good) {
        if ($raw === $good || strpos($raw, $good) !== false) {
            return 'delivered';
        }
    }
    return '';
}

function sms_dlr_digits($number)
{
    $digits = preg_replace('/\D+/', '', (string) $number);
    if (strlen($digits) > 10) {
        $digits = substr($digits, -10);
    }
    return $digits;
}

/**
 * Record one report. Returns true when a sent message was updated.
 * A report never changes the sent/failed status or the credits already used.
 */
function sms_dlr_apply($conn, $number, $status, $ref = '')
{
    sms_delivery_columns($conn);
    $state = sms_dlr_normalise_status($status);
    $digits = sms_dlr_digits($number);
    if ($state === '' || strlen($digits) < 8) {
        return false;
    }
    $ref = substr(preg_replace('/[^A-Za-z0-9._-]/', '', (string) $ref), 0, 64);
    $id = 0;
    if ($ref !== '') {
        $stmt = $conn->prepare('SELECT id FROM sms_messages WHERE provider_ref = ? ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('s', $ref);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        $id = $row ? (int) $row['id'] : 0;
    }
    if ($id === 0) {
        $like = '%' . $digits;
        $since = date('Y-m-d H:i:s', time() - 3 * 86400);
        $sent = 'sent';
        $stmt = $conn->prepare('SELECT id FROM sms_messages WHERE recipient LIKE ? AND status = ? AND (delivery IS NULL OR delivery = \'\') AND sent_at >= ? ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('sss', $like, $sent, $since);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        $id = $row ? (int) $row['id'] : 0;
    }
    if ($id < 1) {
        return false;
    }
    $now = date('Y-m-d H:i:s');
    billing_tx($conn, 'begin');
    try {
        // The (delivery is empty) test makes this change happen once, so credits can never be returned twice.
        $stmt = $conn->prepare('UPDATE sms_messages SET delivery = ?, delivery_at = ?, provider_ref = CASE WHEN provider_ref = \'\' OR provider_ref IS NULL THEN ? ELSE provider_ref END WHERE id = ? AND status = \'sent\' AND (delivery IS NULL OR delivery = \'\')');
        $stmt->bind_param('sssi', $state, $now, $ref, $id);
        $stmt->execute();
        $done = billing_affected($conn) === 1;
        $stmt->close();
        if ($done && $state === 'failed' && sms_refund_undelivered_on($conn)) {
            $stmt = $conn->prepare('SELECT client_id, parts FROM sms_messages WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = db_fetch_assoc($stmt);
            $stmt->close();
            if ($row && (int) $row['parts'] > 0) {
                billing_add_units($conn, (int) $row['client_id'], 'sms', (int) $row['parts']);
                sms_remember_credit($conn, (int) $row['client_id'], (int) $row['parts'], 'Returned: the phone network could not deliver a message');
                $stmt = $conn->prepare("UPDATE sms_messages SET error_text = 'refunded' WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Throwable $exception) {
        billing_tx($conn, 'rollback');
        error_log('Delivery report could not be saved: ' . $exception->getMessage());
        return false;
    }
    billing_tx($conn, 'commit');
    return $done;
}

function sms_dlr_first($source, $names)
{
    foreach ($names as $name) {
        if (isset($source[$name]) && is_scalar($source[$name]) && trim((string) $source[$name]) !== '') {
            return (string) $source[$name];
        }
    }
    return '';
}

/** Endpoint: accepts GET, form POST or a JSON body (one report, or a list of reports). */
function sms_dlr_handle($conn)
{
    header('Content-Type: application/json; charset=utf-8');
    $expected = defined('SMS_DLR_KEY') ? (string) SMS_DLR_KEY : '';
    $given = isset($_GET['key']) ? (string) $_GET['key'] : '';
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        echo json_encode(array('ok' => false, 'message' => 'Forbidden'));
        return;
    }
    sms_delivery_columns($conn);
    $reports = array();
    $raw = file_get_contents('php://input');
    $json = $raw !== '' ? json_decode($raw, true) : null;
    if (is_array($json)) {
        $reports = isset($json[0]) ? $json : (isset($json['reports']) && is_array($json['reports']) ? $json['reports'] : array($json));
    } else {
        $reports = array(array_merge($_GET, $_POST));
    }
    $updated = 0;
    $seen = 0;
    foreach (array_slice($reports, 0, 500) as $report) {
        if (!is_array($report)) {
            continue;
        }
        $seen++;
        $number = sms_dlr_first($report, array('to', 'mobile', 'msisdn', 'number', 'recipient', 'phone'));
        $status = sms_dlr_first($report, array('status', 'state', 'dlr', 'delivery_status', 'dlr_status'));
        $ref = sms_dlr_first($report, array('id', 'message_id', 'msgid', 'ref', 'reference'));
        if (sms_dlr_apply($conn, $number, $status, $ref)) {
            $updated++;
        }
    }
    echo json_encode(array('ok' => true, 'received' => $seen, 'updated' => $updated));
}
