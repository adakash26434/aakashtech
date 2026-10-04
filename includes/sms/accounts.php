<?php
/**
 * SMS: Who may send: identity gate, sender names, credits and admin credit tools.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
 */

function sms_office_credit($conn, $clientId)
{
    sms_credit_columns($conn);
    $clientId = (int) $clientId;
    $bought = 'Bought from the wallet';
    $stmt = $conn->prepare('SELECT id FROM sms_credit_notes WHERE client_id = ? AND credits > 0 AND note <> ? AND (reversed_at IS NULL OR reversed_at = \'\') LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('is', $clientId, $bought);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return (bool) $row;
}

function sms_client_gate($conn, $clientId, $identityRequired = false)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT status FROM client_users WHERE id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || (string) $row['status'] !== 'active') {
        return 'This account cannot send SMS.';
    }
    if ($identityRequired && !billing_kyc_approved($conn, $clientId)) {
        return 'Identity has to be approved before an API token can be created.';
    }
    return '';
}

function sms_unverified_cap()
{
    return 100;
}

function sms_unverified_used($conn, $clientId)
{
    $clientId = (int) $clientId;
    $used = 0;
    $stmt = $conn->prepare('SELECT COALESCE(SUM(parts), 0) AS used_credits FROM sms_messages WHERE client_id = ? AND status IN (\'sent\', \'queued\', \'sending\', \'scheduled\')');
    if ($stmt) {
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        $used += $row ? (int) $row['used_credits'] : 0;
    }
    $waiting = $conn->prepare('SELECT c.message_content, c.recipients_count FROM sms_campaigns c WHERE c.client_id = ? AND c.channel = \'sms\' AND c.status = \'scheduled\' AND NOT EXISTS (SELECT 1 FROM sms_messages m WHERE m.campaign_id = c.id AND m.status IN (\'queued\', \'sending\', \'scheduled\', \'sent\'))');
    if ($waiting) {
        $waiting->bind_param('i', $clientId);
        $waiting->execute();
        foreach (db_fetch_all($waiting) as $job) {
            $used += sms_message_parts((string) $job['message_content']) * (int) $job['recipients_count'];
        }
        $waiting->close();
    }
    return $used;
}

function sms_unverified_block($conn, $clientId, $credits)
{
    $clientId = (int) $clientId;
    $credits = (int) $credits;
    if ($credits < 1 || billing_kyc_approved($conn, $clientId)) {
        return '';
    }
    $cap = sms_unverified_cap();
    $used = sms_unverified_used($conn, $clientId);
    if ($used + $credits <= $cap) {
        return '';
    }
    $room = $cap - $used;
    if ($room < 0) {
        $room = 0;
    }
    return 'More than ' . number_format($cap) . ' SMS needs KYC. ' . number_format($room) . ' are still open on this account. Please update KYC, then try again.';
}

function sms_client_route($conn, $clientId)
{
    $line = sms_line($conn);
    $choose = $line['connected'] && $line['provider'] === 'sparrow' && $line['sender_mode'] === 'approved';
    return array(
        'connected' => $line['connected'],
        'sender' => $line['sender'],
        'choose_sender' => $choose,
        'approved' => $choose ? sms_approved_senders($conn, $clientId) : array()
    );
}

function sms_resolve_sender($conn, $clientId, $requested)
{
    $route = sms_client_route($conn, $clientId);
    if (!$route['connected']) {
        return array('sender' => '', 'error' => 'SMS sending is not open yet. The team is still connecting the line.');
    }
    if (!$route['choose_sender']) {
        $line = sms_line($conn);
        if ($line['provider'] === 'sparrow' && $route['sender'] === '') {
            return array('sender' => '', 'error' => 'The name on the phone is not set yet.');
        }
        return array('sender' => $route['sender'], 'error' => '');
    }
    $requested = strtoupper(billing_plain_line($requested, 11));
    if (!in_array($requested, $route['approved'], true)) {
        return array('sender' => '', 'error' => 'Choose a sender name that has been approved.');
    }
    return array('sender' => $requested, 'error' => '');
}

function sms_approved_senders($conn, $clientId)
{
    $clientId = (int) $clientId;
    $status = 'approved';
    $stmt = $conn->prepare('SELECT sender_name FROM sms_sender_names WHERE client_id = ? AND status = ? ORDER BY sender_name');
    $stmt->bind_param('is', $clientId, $status);
    $stmt->execute();
    $names = array();
    foreach (db_fetch_all($stmt) as $row) {
        $names[] = (string) $row['sender_name'];
    }
    $stmt->close();
    return $names;
}

function sms_request_sender($conn, $clientId, $name)
{
    $clientId = (int) $clientId;
    $gate = sms_client_gate($conn, $clientId);
    if ($gate !== '') {
        return $gate;
    }
    $name = strtoupper(billing_plain_line($name, 11));
    if (!preg_match('/^[A-Z0-9]{3,11}$/', $name)) {
        return 'Use 3 to 11 letters or numbers, such as SAHAKARI.';
    }
    $stmt = $conn->prepare('SELECT id, status FROM sms_sender_names WHERE client_id = ? AND sender_name = ?');
    $stmt->bind_param('is', $clientId, $name);
    $stmt->execute();
    $existing = db_fetch_assoc($stmt);
    $stmt->close();
    if ($existing) {
        if ((string) $existing['status'] === 'rejected') {
            $id = (int) $existing['id'];
            $pending = 'pending';
            $note = '';
            $stmt = $conn->prepare('UPDATE sms_sender_names SET status = ?, admin_note = ? WHERE id = ?');
            $stmt->bind_param('ssi', $pending, $note, $id);
            $stmt->execute();
            $stmt->close();
            return '';
        }
        return 'That sender name is already on your account.';
    }
    $pending = 'pending';
    $note = '';
    $stmt = $conn->prepare('INSERT INTO sms_sender_names (client_id, sender_name, status, admin_note) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $clientId, $name, $pending, $note);
    $stmt->execute();
    $stmt->close();
    return '';
}

function sms_take_credits($conn, $clientId, $credits)
{
    $credits = (int) $credits;
    $clientId = (int) $clientId;
    $kind = 'sms';
    if ($credits < 1 || $clientId < 1) {
        return false;
    }
    $stmt = $conn->prepare('UPDATE client_units SET balance = balance - ? WHERE client_id = ? AND unit_kind = ? AND balance >= ?');
    $stmt->bind_param('iisi', $credits, $clientId, $kind, $credits);
    $stmt->execute();
    $taken = (int) $conn->affected_rows > 0;
    $stmt->close();
    if ($taken && function_exists('billing_low_sms_alert')) {
        billing_low_sms_alert($conn, $clientId);
    }
    return $taken;
}

function sms_admin_client_figures($conn, $ids)
{
    $clean = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $clean[$id] = $id;
        }
    }
    $figures = array();
    foreach ($clean as $id) {
        $figures[$id] = array('used' => 0, 'left' => 0);
    }
    if (!$figures) {
        return $figures;
    }
    $marks = implode(',', $figures ? array_keys($figures) : array(0));
    $used = $conn->query('SELECT client_id, SUM(parts) AS used_credits FROM sms_messages WHERE status = \'sent\' AND client_id IN (' . $marks . ') GROUP BY client_id');
    if ($used) {
        while ($row = $used->fetch_assoc()) {
            $figures[(int) $row['client_id']]['used'] = (int) $row['used_credits'];
        }
    }
    $left = $conn->query('SELECT client_id, balance FROM client_units WHERE unit_kind = \'sms\' AND client_id IN (' . $marks . ')');
    if ($left) {
        while ($row = $left->fetch_assoc()) {
            $figures[(int) $row['client_id']]['left'] = (int) $row['balance'];
        }
    }
    return $figures;
}

function sms_clients_holding($conn)
{
    $kind = 'sms';
    $stmt = $conn->prepare('SELECT COALESCE(SUM(balance), 0) AS held FROM client_units WHERE unit_kind = ?');
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $kind);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row ? (int) $row['held'] : 0;
}

function sms_remember_credit($conn, $clientId, $credits, $note)
{
    $clientId = (int) $clientId;
    $credits = (int) $credits;
    $note = billing_plain_line($note, 160);
    if ($clientId < 1 || $credits < 1 || $note === '') {
        return 0;
    }
    $insert = $conn->prepare('INSERT INTO sms_credit_notes (client_id, credits, note) VALUES (?, ?, ?)');
    if (!$insert) {
        return 0;
    }
    $insert->bind_param('iis', $clientId, $credits, $note);
    $insert->execute();
    $id = (int) $conn->insert_id;
    $insert->close();
    return $id;
}

function sms_admin_reverse($conn, $clientId, $noteId)
{
    sms_credit_columns($conn);
    $clientId = (int) $clientId;
    $noteId = (int) $noteId;
    $empty = array('error' => 'That SMS top-up was not found.', 'message' => '');
    if ($clientId < 1 || $noteId < 1) {
        return $empty;
    }
    $stmt = $conn->prepare('SELECT id, credits, reversed_at FROM sms_credit_notes WHERE id = ? AND client_id = ?');
    if (!$stmt) {
        return $empty;
    }
    $stmt->bind_param('ii', $noteId, $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return $empty;
    }
    if (trim((string) $row['reversed_at']) !== '') {
        return array('error' => 'That SMS top-up was already taken back.', 'message' => '');
    }
    $credits = (int) $row['credits'];
    if ($credits < 1) {
        return array('error' => 'That row is not an SMS top-up.', 'message' => '');
    }
    $left = (int) billing_unit_balances($conn, $clientId)['sms'];
    $take = $left < $credits ? $left : $credits;
    if ($take < 1) {
        return array('error' => 'Those SMS were already sent. Nothing is left to take back.', 'message' => '');
    }
    $now = date('Y-m-d H:i:s');
    $mark = $conn->prepare('UPDATE sms_credit_notes SET reversed_at = ? WHERE id = ? AND client_id = ? AND (reversed_at IS NULL OR reversed_at = \'\')');
    if (!$mark) {
        return array('error' => 'That SMS top-up could not be taken back.', 'message' => '');
    }
    $mark->bind_param('sii', $now, $noteId, $clientId);
    $mark->execute();
    $marked = billing_affected($conn) === 1;
    $mark->close();
    if (!$marked) {
        return array('error' => 'That SMS top-up was already taken back.', 'message' => '');
    }
    if (!billing_take_units($conn, $clientId, 'sms', $take)) {
        $blank = null;
        $undo = $conn->prepare('UPDATE sms_credit_notes SET reversed_at = ? WHERE id = ? AND client_id = ?');
        if ($undo) {
            $undo->bind_param('sii', $blank, $noteId, $clientId);
            $undo->execute();
            $undo->close();
        }
        return array('error' => 'Those SMS could not be taken back. The balance changed while this was saving.', 'message' => '');
    }
    if ($take < $credits) {
        return array(
            'error' => '',
            'message' => number_format($credits) . ' SMS were added. ' . number_format($credits - $take) . ' were already sent, so ' . number_format($take) . ' were taken back.'
        );
    }
    return array('error' => '', 'message' => number_format($take) . ' SMS taken back. The client can no longer send them.');
}

function sms_admin_usage($conn, $find = '')
{
    $find = function_exists('admin_find_text') ? admin_find_text($find) : trim((string) $find);
    $from = 'FROM client_users c
        LEFT JOIN client_units u ON u.client_id = c.id AND u.unit_kind = \'sms\'
        LEFT JOIN (
            SELECT client_id, SUM(parts) AS used_credits
            FROM sms_messages
            WHERE status = \'sent\'
            GROUP BY client_id
        ) sent ON sent.client_id = c.id
        WHERE (COALESCE(u.balance, 0) > 0 OR COALESCE(sent.used_credits, 0) > 0)';
    $types = '';
    $params = array();
    if ($find !== '') {
        $from .= ' AND (c.name LIKE ? OR c.email LIKE ? OR EXISTS (SELECT 1 FROM sms_messages m WHERE m.client_id = c.id AND (m.recipient LIKE ? OR m.message_text LIKE ?)))';
        $like = '%' . $find . '%';
        $types = 'ssss';
        $params = array($like, $like, $like, $like);
    }
    $sum = $conn->prepare('SELECT COUNT(*) AS clients, COALESCE(SUM(COALESCE(u.balance, 0)), 0) AS sms_left, COALESCE(SUM(COALESCE(sent.used_credits, 0)), 0) AS sms_used ' . $from);
    $used = 0;
    $left = 0;
    $clients = 0;
    if ($sum) {
        if ($types !== '') {
            $bind = array($types);
            foreach ($params as $index => $unused) {
                $bind[] = &$params[$index];
            }
            call_user_func_array(array($sum, 'bind_param'), $bind);
        }
        $sum->execute();
        $sumRow = db_fetch_assoc($sum);
        $sum->close();
        if ($sumRow) {
            $clients = (int) $sumRow['clients'];
            $used = (int) $sumRow['sms_used'];
            $left = (int) $sumRow['sms_left'];
        }
    }
    $stmt = $conn->prepare('SELECT c.id, c.name, c.email, COALESCE(u.balance, 0) AS sms_left, COALESCE(sent.used_credits, 0) AS sms_used ' . $from . ' ORDER BY sms_used DESC, c.id DESC LIMIT 100');
    $rows = array();
    if ($stmt) {
        if ($types !== '') {
            $bind = array($types);
            foreach ($params as $index => $unused) {
                $bind[] = &$params[$index];
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind);
        }
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
    }
    return array('rows' => $rows, 'clients' => $clients, 'used' => $used, 'left' => $left);
}

function sms_admin_grant($conn, $clientId, $credits, $note)
{
    $clientId = (int) $clientId;
    $credits = (int) $credits;
    $note = function_exists('billing_plain_line') ? billing_plain_line($note, 160) : trim((string) $note);
    if ($clientId < 1 || $credits < 1 || $credits > 500000) {
        return 'Enter a client and between 1 and 500,000 SMS credits.';
    }
    if ($note === '') {
        return 'Write a short reason for this top-up.';
    }
    $stmt = $conn->prepare('SELECT id FROM client_users WHERE id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $client = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$client) {
        return 'That client was not found.';
    }
    billing_add_units($conn, $clientId, 'sms', $credits);
    sms_remember_credit($conn, $clientId, $credits, $note);
    if (function_exists('billing_mail_client_event')) {
        billing_mail_client_event($conn, $clientId, 'sms-ready', array(
            'credits' => number_format($credits),
            'note' => $note
        ));
    }
    return '';
}

function sms_admin_history($conn, $clientId, $find, $status)
{
    $clientId = (int) $clientId;
    $find = function_exists('admin_find_text') ? admin_find_text($find) : trim((string) $find);
    $allowed = array('sent', 'failed', 'queued', 'sending', 'scheduled');
    if (!in_array($status, $allowed, true)) {
        $status = '';
    }
    $sql = 'SELECT m.id, m.client_id, m.recipient, m.message_text, m.parts, m.status, m.source, m.error_text, m.created_at, c.name, c.email
        FROM sms_messages m
        JOIN client_users c ON c.id = m.client_id
        WHERE 1 = 1';
    $types = '';
    $params = array();
    if ($clientId > 0) {
        $sql .= ' AND m.client_id = ?';
        $types .= 'i';
        $params[] = $clientId;
    }
    if ($status !== '') {
        $sql .= ' AND m.status = ?';
        $types .= 's';
        $params[] = $status;
    }
    if ($find !== '') {
        $sql .= ' AND (c.name LIKE ? OR c.email LIKE ? OR m.recipient LIKE ? OR m.message_text LIKE ?)';
        $types .= 'ssss';
        $like = '%' . $find . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' ORDER BY m.id DESC LIMIT 80';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return array();
    }
    if ($types !== '') {
        $bind = array($types);
        foreach ($params as $index => $value) {
            $bind[] = &$params[$index];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind);
    }
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    return $rows;
}
