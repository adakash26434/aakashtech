<?php
/**
 * SMS: Send history, delivery counts and the SMS log pages.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
 */

function sms_recent_sends($conn, $clientId)
{
    $clientId = (int) $clientId;
    $channel = 'sms';
    $draft = 'draft';
    $stmt = $conn->prepare('SELECT c.id, c.campaign_name, c.status, c.created_at, COALESCE(SUM(CASE WHEN m.status = \'sent\' THEN 1 ELSE 0 END), 0) AS sent_count, COALESCE(SUM(CASE WHEN m.status = \'failed\' THEN 1 ELSE 0 END), 0) AS failed_count, COALESCE(SUM(CASE WHEN m.status = \'sent\' THEN m.parts ELSE 0 END), 0) AS sent_credits, COALESCE(SUM(CASE WHEN m.status = \'failed\' THEN m.parts ELSE 0 END), 0) AS failed_credits FROM sms_campaigns c LEFT JOIN sms_messages m ON m.campaign_id = c.id AND m.client_id = c.client_id WHERE c.client_id = ? AND c.channel = ? AND c.status <> ? GROUP BY c.id, c.campaign_name, c.status, c.created_at ORDER BY c.id DESC LIMIT 20');
    $stmt->bind_param('iss', $clientId, $channel, $draft);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    return $rows;
}

function sms_outcome_counts($conn, $clientId, $campaigns)
{
    $clientId = (int) $clientId;
    $ids = array();
    foreach ($campaigns as $campaign) {
        if (!isset($campaign['id']) || (isset($campaign['channel']) && (string) $campaign['channel'] !== 'sms')) {
            continue;
        }
        $ids[] = (int) $campaign['id'];
    }
    if (!$ids) {
        return array();
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare('SELECT campaign_id, SUM(CASE WHEN status = \'sent\' THEN 1 ELSE 0 END) AS sent_count, SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) AS failed_count FROM sms_messages WHERE client_id = ? AND campaign_id IN (' . $marks . ') GROUP BY campaign_id');
    $types = 'i' . str_repeat('i', count($ids));
    $params = array_merge(array($clientId), $ids);
    $bind = array($types);
    foreach ($params as $key => $unused) {
        $bind[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    $counts = array();
    foreach ($rows as $row) {
        $counts[(int) $row['campaign_id']] = array(
            'sent' => (int) $row['sent_count'],
            'failed' => (int) $row['failed_count']
        );
    }
    return $counts;
}

function sms_load_send($conn, $clientId, $campaignId, $failedOnly)
{
    $clientId = (int) $clientId;
    $campaignId = (int) $campaignId;
    $channel = 'sms';
    $stmt = $conn->prepare('SELECT campaign_name, message_content, sender_id, audience, purpose, recipients_list FROM sms_campaigns WHERE id = ? AND client_id = ? AND channel = ?');
    $stmt->bind_param('iis', $campaignId, $clientId, $channel);
    $stmt->execute();
    $campaign = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$campaign) {
        return array('ok' => false, 'error' => '');
    }
    if ($failedOnly) {
        $failed = 'failed';
        // Unconfirmed sends may already have reached the phone, so they are never retried.
        $stmt = $conn->prepare("SELECT recipient FROM sms_messages WHERE campaign_id = ? AND client_id = ? AND status = ? AND error_text <> 'unconfirmed' ORDER BY id ASC");
        $stmt->bind_param('iis', $campaignId, $clientId, $failed);
    } else {
        $stmt = $conn->prepare('SELECT recipient FROM sms_messages WHERE campaign_id = ? AND client_id = ? ORDER BY id ASC');
        $stmt->bind_param('ii', $campaignId, $clientId);
    }
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    $numbers = array();
    foreach ($rows as $row) {
        $numbers[] = (string) $row['recipient'];
    }
    if (!$numbers && !$failedOnly) {
        $fallback = sms_collect_numbers((string) $campaign['recipients_list']);
        if ($fallback['ok']) {
            $numbers = $fallback['numbers'];
        }
    }
    if ($failedOnly && !$numbers) {
        return array('ok' => false, 'error' => 'That send has no failed numbers.');
    }
    if (!$numbers) {
        return array('ok' => false, 'error' => '');
    }
    return array(
        'ok' => true,
        'error' => '',
        'name' => (string) $campaign['campaign_name'],
        'text' => (string) $campaign['message_content'],
        'sender' => (string) $campaign['sender_id'],
        'audience' => (string) $campaign['audience'],
        'purpose' => (string) $campaign['purpose'],
        'numbers' => implode("\n", $numbers)
    );
}

function sms_log_bounds($from, $to)
{
    $from = trim((string) $from);
    $to = trim((string) $to);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $from = '';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $to = '';
    }
    if ($from !== '' && $to !== '' && $from > $to) {
        $swap = $from;
        $from = $to;
        $to = $swap;
    }
    $nepal = new DateTimeZone('Asia/Kathmandu');
    $store = new DateTimeZone(date_default_timezone_get());
    $start = '';
    $end = '';
    if ($from !== '') {
        $parsed = DateTime::createFromFormat('!Y-m-d', $from, $nepal);
        if ($parsed) {
            $parsed->setTimezone($store);
            $start = $parsed->format('Y-m-d H:i:s');
        } else {
            $from = '';
        }
    }
    if ($to !== '') {
        $parsed = DateTime::createFromFormat('!Y-m-d', $to, $nepal);
        if ($parsed) {
            $parsed->modify('+1 day');
            $parsed->setTimezone($store);
            $end = $parsed->format('Y-m-d H:i:s');
        } else {
            $to = '';
        }
    }
    return array('from' => $from, 'to' => $to, 'start' => $start, 'end' => $end);
}

function sms_log_where($clientId, $status, $search, $campaignId, $from, $to, $source = '')
{
    $clientId = (int) $clientId;
    $campaignId = (int) $campaignId;
    $allowed = array('sent', 'failed', 'queued', 'sending', 'scheduled');
    if (!in_array($status, $allowed, true)) {
        $status = '';
    }
    if ($source !== 'api' && $source !== 'dashboard') {
        $source = '';
    }
    $raw = preg_replace('/\D+/', '', (string) $search);
    if (!is_string($raw)) {
        $raw = '';
    }
    $number = auth_mobile_number($raw);
    if (!preg_match('/^9[78]\d{8}$/', $number)) {
        $number = '';
    }
    $bounds = sms_log_bounds($from, $to);
    $where = array('client_id = ?');
    $types = 'i';
    $params = array($clientId);
    if ($status !== '') {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $status;
    }
    if ($number !== '') {
        $where[] = 'recipient IN (?, ?, ?)';
        $types .= 'sss';
        $params[] = $number;
        $params[] = '977' . $number;
        $params[] = '0' . $number;
        $search = $number;
    } elseif (strlen($raw) >= 4 && strlen($raw) <= 13) {
        $where[] = 'recipient LIKE ?';
        $types .= 's';
        $params[] = '%' . $raw . '%';
        $search = $raw;
    } else {
        $search = '';
    }
    if ($bounds['start'] !== '') {
        $where[] = 'created_at >= ?';
        $types .= 's';
        $params[] = $bounds['start'];
    }
    if ($bounds['end'] !== '') {
        $where[] = 'created_at < ?';
        $types .= 's';
        $params[] = $bounds['end'];
    }
    if ($campaignId > 0) {
        $where[] = 'campaign_id = ?';
        $types .= 'i';
        $params[] = $campaignId;
    }
    if ($source !== '') {
        $where[] = 'source = ?';
        $types .= 's';
        $params[] = $source;
    }
    return array(
        'where' => $where,
        'types' => $types,
        'params' => $params,
        'status' => $status,
        'search' => $search,
        'from' => $bounds['from'],
        'to' => $bounds['to'],
        'number' => $number,
        'source' => $source
    );
}

function sms_message_page($conn, $clientId, $status, $search, $campaignId, $page, $perPage = 50, $from = '', $to = '', $source = '')
{
    $page = (int) $page;
    $perPage = (int) $perPage;
    if ($page < 1) {
        $page = 1;
    }
    if ($page > 40) {
        $page = 40;
    }
    if ($perPage < 1) {
        $perPage = 50;
    }
    if ($perPage > 8000) {
        $perPage = 8000;
    }
    $filter = sms_log_where($clientId, $status, $search, $campaignId, $from, $to, $source);
    $where = $filter['where'];
    $types = $filter['types'];
    $params = $filter['params'];
    $status = $filter['status'];
    $search = $filter['search'];
    $source = $filter['source'];
    $offset = ($page - 1) * $perPage;
    $aliased = array();
    foreach ($where as $piece) {
        $aliased[] = 'm.' . $piece;
    }
    $stmt = $conn->prepare('SELECT m.id, m.recipient, m.sender_id, m.message_text, m.parts, m.status, m.source, m.error_text, m.delivery, m.created_at, m.sent_at, c.campaign_name FROM sms_messages m LEFT JOIN sms_campaigns c ON c.id = m.campaign_id WHERE ' . implode(' AND ', $aliased) . ' ORDER BY m.created_at DESC, m.id DESC LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset);
    $bind = array($types);
    foreach ($params as $key => $unused) {
        $bind[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    $hasMore = count($rows) > $perPage;
    if ($hasMore) {
        $rows = array_slice($rows, 0, $perPage);
    }
    return array(
        'rows' => $rows,
        'page' => $page,
        'has_more' => $hasMore,
        'status' => $status,
        'search' => $search,
        'from' => $filter['from'],
        'to' => $filter['to'],
        'number' => $filter['number'],
        'source' => $source
    );
}

function sms_log_totals($conn, $clientId, $status, $search, $campaignId, $from = '', $to = '', $source = '')
{
    $filter = sms_log_where($clientId, $status, $search, $campaignId, $from, $to, $source);
    $where = $filter['where'];
    $types = $filter['types'];
    $params = $filter['params'];
    $empty = array('sent' => 0, 'failed' => 0, 'sent_credits' => 0, 'failed_credits' => 0);
    $stmt = $conn->prepare('SELECT COALESCE(SUM(CASE WHEN status = \'sent\' THEN 1 ELSE 0 END), 0) AS sent_count, COALESCE(SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END), 0) AS failed_count, COALESCE(SUM(CASE WHEN status = \'sent\' THEN parts ELSE 0 END), 0) AS sent_credits, COALESCE(SUM(CASE WHEN status = \'failed\' THEN parts ELSE 0 END), 0) AS failed_credits FROM sms_messages WHERE ' . implode(' AND ', $where));
    if (!$stmt) {
        return $empty;
    }
    $bind = array($types);
    foreach ($params as $key => $unused) {
        $bind[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return $empty;
    }
    return array(
        'sent' => (int) $row['sent_count'],
        'failed' => (int) $row['failed_count'],
        'sent_credits' => (int) $row['sent_credits'],
        'failed_credits' => (int) $row['failed_credits']
    );
}
