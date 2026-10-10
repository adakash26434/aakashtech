<?php
/**
 * SMS: The public SMS API: tokens, rules and endpoints.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
 */

function sms_api_origin()
{
    return site_canonical_origin();
}

function sms_create_token($conn, $clientId, $label, $allowedIps, $code)
{
    $clientId = (int) $clientId;
    $gate = sms_client_gate($conn, $clientId, false);
    if ($gate !== '') {
        return array('ok' => false, 'error' => $gate, 'token' => '');
    }
    $balance = billing_unit_balances($conn, $clientId);
    if ((int) $balance['sms'] < 1 && !billing_kyc_approved($conn, $clientId)) {
        return array('ok' => false, 'error' => 'Buy SMS credit before creating a token. The API spends the credit on this account.', 'token' => '');
    }
    $label = billing_plain_line($label, 60);
    if ($label === '') {
        return array('ok' => false, 'error' => 'Name the token, for example Website OTP.', 'token' => '');
    }
    $active = 'active';
    $countStmt = $conn->prepare('SELECT COUNT(*) AS c FROM sms_api_tokens WHERE client_id = ? AND status = ?');
    $countStmt->bind_param('is', $clientId, $active);
    $countStmt->execute();
    $countRow = db_fetch_assoc($countStmt);
    $countStmt->close();
    if ($countRow && (int) $countRow['c'] >= 5) {
        return array('ok' => false, 'error' => 'Revoke an old token before creating another. Five active tokens is the limit.', 'token' => '');
    }
    $ips = array();
    foreach (preg_split('/[\s,]+/', trim((string) $allowedIps)) as $ip) {
        if ($ip === '') {
            continue;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return array('ok' => false, 'error' => 'Each allowed address has to be one IP address, or leave the list empty.', 'token' => '');
        }
        $ips[$ip] = $ip;
    }
    $allowed = implode(',', array_values($ips));
    if (strlen($allowed) > 255) {
        return array('ok' => false, 'error' => 'The allowed address list is too long.', 'token' => '');
    }
    $account = totp_account($conn, 'client', $clientId);
    if (!$account || trim((string) $account['totp_secret']) === '') {
        return array('ok' => false, 'error' => 'Set up Google Authenticator before a token can be created.', 'token' => '');
    }
    $match = totp_match($account['totp_secret'], $code, (int) $account['totp_last_step']);
    if (!empty($match['replay'])) {
        return array('ok' => false, 'error' => 'That authenticator code was already used. Wait for the next code.', 'token' => '');
    }
    if (empty($match['ok'])) {
        return array('ok' => false, 'error' => 'Enter the current 6-digit code from Google Authenticator.', 'token' => '');
    }
    totp_set_step($conn, 'client', $clientId, $match['step']);
    $token = 'at_' . bin2hex(random_bytes(20));
    $hash = hash('sha256', $token);
    $prefix = substr($token, 0, 10);
    $stmt = $conn->prepare('INSERT INTO sms_api_tokens (client_id, label, token_hash, token_prefix, status, allowed_ips) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssss', $clientId, $label, $hash, $prefix, $active, $allowed);
    $stmt->execute();
    $stmt->close();
    return array('ok' => true, 'error' => '', 'token' => $token);
}

function sms_revoke_token($conn, $clientId, $tokenId)
{
    $clientId = (int) $clientId;
    $tokenId = (int) $tokenId;
    $revoked = 'revoked';
    $active = 'active';
    $stmt = $conn->prepare('UPDATE sms_api_tokens SET status = ? WHERE id = ? AND client_id = ? AND status = ?');
    $stmt->bind_param('siis', $revoked, $tokenId, $clientId, $active);
    $stmt->execute();
    $changed = (int) $conn->affected_rows > 0;
    $stmt->close();
    return $changed;
}

function sms_find_token($conn, $plain)
{
    $plain = trim((string) $plain);
    if (!preg_match('/^at_[a-f0-9]{40}$/', $plain)) {
        return null;
    }
    $hash = hash('sha256', $plain);
    $active = 'active';
    $stmt = $conn->prepare('SELECT t.*, c.status AS client_status FROM sms_api_tokens t JOIN client_users c ON c.id = t.client_id WHERE t.token_hash = ? AND t.status = ?');
    $stmt->bind_param('ss', $hash, $active);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row ? $row : null;
}

function sms_api_allow($conn, $tokenRow, $ip)
{
    $allowed = trim((string) $tokenRow['allowed_ips']);
    if ($allowed !== '') {
        $list = array();
        foreach (explode(',', $allowed) as $allowedIp) {
            $allowedIp = trim($allowedIp);
            if ($allowedIp !== '') {
                $list[] = $allowedIp;
            }
        }
        if (!in_array($ip, $list, true)) {
            return 'This token is not allowed from this address.';
        }
    }
    $tokenId = (int) $tokenRow['id'];
    $since = date('Y-m-d H:i:s', time() - 60);
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM sms_api_hits WHERE token_id = ? AND created_at >= ?');
    $stmt->bind_param('is', $tokenId, $since);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if ($row && (int) $row['c'] >= 30) {
        return 'Too many requests. Wait a minute and try again.';
    }
    $hit = $conn->prepare('INSERT INTO sms_api_hits (token_id) VALUES (?)');
    $hit->bind_param('i', $tokenId);
    $hit->execute();
    $hit->close();
    $cut = date('Y-m-d H:i:s', time() - 86400);
    $drop = $conn->prepare('DELETE FROM sms_api_hits WHERE token_id = ? AND created_at < ?');
    $drop->bind_param('is', $tokenId, $cut);
    $drop->execute();
    $drop->close();
    $now = date('Y-m-d H:i:s');
    $touch = $conn->prepare('UPDATE sms_api_tokens SET last_used_at = ? WHERE id = ?');
    $touch->bind_param('si', $now, $tokenId);
    $touch->execute();
    $touch->close();
    return '';
}

function sms_api_input()
{
    $input = array();
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
    // Only the POST body and a JSON body are read. The URL query string is ignored, so a
    // message or token in a link never reaches access logs, referrers or prefetchers.
    foreach ($_POST as $key => $value) {
        if (!isset($input[$key])) {
            $input[$key] = $value;
        }
    }
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = (string) $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if ($header !== '' && preg_match('/Bearer\s+(\S+)/i', $header, $match) && empty($input['auth_token'])) {
        $input['auth_token'] = $match[1];
    }
    if (empty($input['auth_token']) && !empty($_SERVER['HTTP_AUTH_TOKEN'])) {
        $input['auth_token'] = (string) $_SERVER['HTTP_AUTH_TOKEN'];
    }
    if (empty($input['auth_token']) && isset($input['token'])) {
        $input['auth_token'] = (string) $input['token'];
    }
    if (empty($input['text']) && isset($input['message'])) {
        $input['text'] = $input['message'];
    }
    if (empty($input['to']) && isset($input['mobile'])) {
        $input['to'] = $input['mobile'];
    }
    return $input;
}

function sms_api_json($status, $payload)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload);
    exit;
}

function sms_api_texts($value)
{
    if (is_array($value)) {
        $texts = array();
        foreach ($value as $item) {
            if (!is_array($item)) {
                $texts[] = trim((string) $item);
            }
        }
        return $texts;
    }
    if (!is_scalar($value)) {
        return array();
    }
    return array(trim((string) $value));
}

function sms_api_mobiles($value)
{
    $chunks = array();
    if (is_array($value)) {
        foreach ($value as $item) {
            if (!is_array($item)) {
                $chunks[] = (string) $item;
            }
        }
    } elseif (is_scalar($value)) {
        $chunks[] = (string) $value;
    }
    $pieces = array();
    foreach ($chunks as $chunk) {
        $split = preg_split('/[\s,;]+/', trim($chunk));
        if (!is_array($split)) {
            continue;
        }
        foreach ($split as $piece) {
            if ($piece !== '') {
                $pieces[] = $piece;
            }
        }
    }
    return $pieces;
}

function sms_api_balance_message($error)
{
    $error = (string) $error;
    if (stripos($error, 'not enough') !== false) {
        return 'Not enough balance.';
    }
    return $error;
}

function sms_api_receipt($conn, $clientId, $campaignId)
{
    $valid = array();
    $invalid = array();
    $credits = 0;
    $clientId = (int) $clientId;
    $campaignId = (int) $campaignId;
    if ($campaignId < 1) {
        return array('valid' => $valid, 'invalid' => $invalid, 'credits' => 0);
    }
    $stmt = $conn->prepare('SELECT id, recipient, message_text, parts, status FROM sms_messages WHERE campaign_id = ? AND client_id = ? ORDER BY id ASC');
    $stmt->bind_param('ii', $campaignId, $clientId);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    foreach ($rows as $row) {
        $failed = (string) $row['status'] === 'failed';
        $item = array(
            'id' => (int) $row['id'],
            'mobile' => (string) $row['recipient'],
            'text' => (string) $row['message_text'],
            'credit' => $failed ? 0 : (int) $row['parts'],
            'status' => (string) $row['status']
        );
        if ($failed) {
            $invalid[] = $item;
        } else {
            $valid[] = $item;
            $credits += (int) $row['parts'];
        }
    }
    return array('valid' => $valid, 'invalid' => $invalid, 'credits' => $credits);
}

function sms_api_token_row($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sms_api_json(405, array('error' => true, 'message' => 'Use POST. Sending by GET is no longer accepted.', 'data' => array()));
    }
    $input = sms_api_input();
    $token = isset($input['auth_token']) ? trim((string) $input['auth_token']) : '';
    if ($token === '') {
        sms_api_json(400, array('error' => true, 'message' => 'The auth token field is required.', 'data' => array()));
    }
    $row = sms_find_token($conn, $token);
    if (!$row) {
        sms_api_json(401, array('error' => true, 'message' => 'The provided auth token is not valid.', 'data' => array()));
    }
    return array($row, $input);
}

// A client_ref names one send. If the same ref comes again, the earlier receipt is returned
// and nothing is sent or charged. Refs are scoped to the token that sent them.
function sms_api_replay($conn, $clientId, $tokenId, $ref)
{
    $key = (int) $tokenId . ':' . $ref;
    $stmt = $conn->prepare('SELECT id FROM sms_campaigns WHERE client_id = ? AND api_ref = ? ORDER BY id ASC');
    $stmt->bind_param('is', $clientId, $key);
    $stmt->execute();
    $found = db_fetch_all($stmt);
    $stmt->close();
    if (!$found) {
        return null;
    }
    $valid = array();
    $invalid = array();
    $credits = 0;
    foreach ($found as $campaign) {
        $receipt = sms_api_receipt($conn, $clientId, (int) $campaign['id']);
        $valid = array_merge($valid, $receipt['valid']);
        $invalid = array_merge($invalid, $receipt['invalid']);
        $credits += (int) $receipt['credits'];
    }
    $balance = billing_unit_balances($conn, $clientId);
    $balance = (int) $balance['sms'];
    return array(
        'count' => count($valid),
        'failed' => count($invalid),
        'credits_used' => 0,
        'balance' => $balance,
        'available_credit' => $balance,
        'valid' => $valid,
        'invalid' => $invalid,
        'replayed' => true,
        'original_credits_used' => $credits
    );
}

function sms_api_send($conn)
{
    list($row, $input) = sms_api_token_row($conn);
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $limited = sms_api_allow($conn, $row, $ip);
    if ($limited !== '') {
        sms_api_json(429, array('error' => true, 'message' => $limited, 'data' => array()));
    }
    $mobiles = sms_api_mobiles(isset($input['to']) ? $input['to'] : '');
    $texts = sms_api_texts(isset($input['text']) ? $input['text'] : '');
    if (!$mobiles) {
        sms_api_json(400, array('error' => true, 'message' => 'The to field is required.', 'data' => array()));
    }
    $filled = array();
    foreach ($texts as $text) {
        if ($text !== '') {
            $filled[] = $text;
        }
    }
    if (!$filled) {
        sms_api_json(400, array('error' => true, 'message' => 'The text field is required.', 'data' => array()));
    }
    if (count($texts) > 1 && count($filled) !== count($texts)) {
        sms_api_json(400, array('error' => true, 'message' => 'Each number needs a message.', 'data' => array()));
    }
    if (count($texts) === 1) {
        $texts = $filled;
    }
    $paired = count($texts) > 1;
    if ($paired) {
        $sameText = true;
        foreach ($texts as $text) {
            if ($text !== $texts[0]) {
                $sameText = false;
                break;
            }
        }
        if ($sameText) {
            $texts = array($texts[0]);
            $paired = false;
        }
    }
    if ($paired && count($texts) !== count($mobiles)) {
        sms_api_json(400, array('error' => true, 'message' => 'Use one message for every number, or one message for each number.', 'data' => array()));
    }
    if ($paired && count($texts) > 20) {
        sms_api_json(400, array('error' => true, 'message' => 'One call can send 20 different messages. One message can still go to 500 numbers.', 'data' => array()));
    }
    $jobs = array();
    $invalid = array();
    if (!$paired) {
        $accepted = array();
        foreach ($mobiles as $mobile) {
            $digits = auth_mobile_number($mobile);
            if (!preg_match('/^9[78]\d{8}$/', $digits)) {
                $invalid[] = array('mobile' => $mobile, 'text' => $texts[0], 'credit' => 0, 'status' => 'invalid');
                continue;
            }
            $accepted[$digits] = $digits;
        }
        if ($accepted) {
            $jobs[] = array('text' => $texts[0], 'numbers' => implode("\n", array_values($accepted)));
        }
    } else {
        foreach ($mobiles as $index => $mobile) {
            $digits = auth_mobile_number($mobile);
            $text = $texts[$index];
            if (!preg_match('/^9[78]\d{8}$/', $digits)) {
                $invalid[] = array('mobile' => $mobile, 'text' => $text, 'credit' => 0, 'status' => 'invalid');
                continue;
            }
            $jobs[] = array('text' => $text, 'numbers' => $digits);
        }
    }
    if (!$jobs) {
        sms_api_json(400, array(
            'error' => true,
            'message' => 'No valid recipients.',
            'data' => array('valid' => array(), 'invalid' => $invalid)
        ));
    }
    $clientId = (int) $row['client_id'];
    $tokenId = (int) $row['id'];
    $ref = isset($input['client_ref']) ? trim((string) $input['client_ref']) : '';
    if ($ref !== '') {
        if (!preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $ref)) {
            sms_api_json(400, array('error' => true, 'message' => 'client_ref can use letters, numbers, dot, dash and underscore, up to 64 characters.', 'data' => array()));
        }
        $replay = sms_api_replay($conn, $clientId, $tokenId, $ref);
        if ($replay !== null) {
            sms_api_json(200, array('error' => false, 'message' => 'This client_ref was already sent. Nothing was sent or charged again.', 'data' => $replay));
        }
    }
    $name = (isset($input['name']) && trim((string) $input['name']) !== '') ? (string) $input['name'] : 'API';
    $sender = isset($input['from']) ? (string) $input['from'] : '';
    $valid = array();
    $credits = 0;
    $balance = billing_unit_balances($conn, $clientId);
    $balance = (int) $balance['sms'];
    $lastError = '';
    $stopped = false;
    foreach ($jobs as $job) {
        $jobNumbers = preg_split('/\n/', (string) $job['numbers']);
        if (!is_array($jobNumbers)) {
            $jobNumbers = array();
        }
        if ($stopped) {
            foreach ($jobNumbers as $jobNumber) {
                if ($jobNumber !== '') {
                    $invalid[] = array('mobile' => $jobNumber, 'text' => $job['text'], 'credit' => 0, 'status' => 'not-sent');
                }
            }
            continue;
        }
        $result = sms_send($conn, $clientId, array(
            'name' => $name,
            'text' => $job['text'],
            'numbers' => $job['numbers'],
            'sender' => $sender,
            'source' => 'api',
            'token_id' => $tokenId
        ));
        if ($ref !== '' && !empty($result['campaign_id'])) {
            $refKey = $tokenId . ':' . $ref;
            $tag = $conn->prepare('UPDATE sms_campaigns SET api_ref = ? WHERE id = ? AND client_id = ?');
            $campaignForRef = (int) $result['campaign_id'];
            $tag->bind_param('sii', $refKey, $campaignForRef, $clientId);
            $tag->execute();
            $tag->close();
        }
        if (isset($result['balance'])) {
            $balance = (int) $result['balance'];
        }
        if (!empty($result['campaign_id'])) {
            $receipt = sms_api_receipt($conn, $clientId, (int) $result['campaign_id']);
            $valid = array_merge($valid, $receipt['valid']);
            $invalid = array_merge($invalid, $receipt['invalid']);
            $credits += (int) $receipt['credits'];
        } elseif (empty($result['ok'])) {
            foreach ($jobNumbers as $jobNumber) {
                if ($jobNumber !== '') {
                    $invalid[] = array('mobile' => $jobNumber, 'text' => $job['text'], 'credit' => 0, 'status' => 'not-sent');
                }
            }
        }
        if (empty($result['ok'])) {
            $lastError = sms_api_balance_message($result['error']);
            $stopped = true;
        }
    }
    $data = array(
        'count' => count($valid),
        'failed' => count($invalid),
        'credits_used' => $credits,
        'balance' => $balance,
        'available_credit' => $balance,
        'valid' => $valid,
        'invalid' => $invalid
    );
    if ($valid) {
        $message = count($valid) === 1 ? '1 SMS sent.' : number_format(count($valid)) . ' SMS sent.';
        if ($invalid) {
            $message .= ' Some numbers were not accepted. Those credits were returned.';
        }
        sms_api_json(200, array('error' => false, 'message' => $message, 'data' => $data));
    }
    sms_api_json(400, array(
        'error' => true,
        'message' => $lastError !== '' ? $lastError : 'The message could not be sent.',
        'data' => $lastError === 'Not enough balance.' ? array() : $data
    ));
}

function sms_api_credit($conn)
{
    list($row) = sms_api_token_row($conn);
    $clientId = (int) $row['client_id'];
    $balance = billing_unit_balances($conn, $clientId);
    $sent = 'sent';
    $stmt = $conn->prepare('SELECT COUNT(*) AS sent_count, MAX(sent_at) AS last_sent FROM sms_messages WHERE client_id = ? AND status = ?');
    $stmt->bind_param('is', $clientId, $sent);
    $stmt->execute();
    $stats = db_fetch_assoc($stmt);
    $stmt->close();
    $available = (int) $balance['sms'];
    sms_api_json(200, array(
        'error' => false,
        'message' => 'SMS credit balance.',
        'data' => array(
            'available_credit' => $available,
            'balance' => $available,
            'total_sms_sent' => $stats ? (int) $stats['sent_count'] : 0,
            'last_sent' => ($stats && $stats['last_sent']) ? sms_format_time($stats['last_sent']) : ''
        )
    ));
}

function sms_api_report($conn)
{
    list($row, $input) = sms_api_token_row($conn);
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $limited = sms_api_allow($conn, $row, $ip);
    if ($limited !== '') {
        sms_api_json(429, array('error' => true, 'message' => $limited, 'data' => array()));
    }
    $start = isset($input['start_date']) ? trim((string) $input['start_date']) : '';
    $end = isset($input['end_date']) ? trim((string) $input['end_date']) : '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
        sms_api_json(400, array('error' => true, 'message' => 'The start date field is required. Use Y-m-d.', 'data' => array()));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        sms_api_json(400, array('error' => true, 'message' => 'The end date field is required. Use Y-m-d.', 'data' => array()));
    }
    if (abs(strtotime($end) - strtotime($start)) > 62 * 86400) {
        sms_api_json(400, array('error' => true, 'message' => 'Use a date range of 62 days or less.', 'data' => array()));
    }
    $page = isset($input['page']) ? (int) $input['page'] : 1;
    $source = isset($input['source']) ? (string) $input['source'] : '';
    $log = sms_message_page($conn, (int) $row['client_id'], '', '', 0, $page, 100, $start, $end, $source);
    $rows = array();
    foreach ($log['rows'] as $item) {
        $rows[] = array(
            'id' => (int) $item['id'],
            'mobile' => (string) $item['recipient'],
            'text' => (string) $item['message_text'],
            'credit' => (int) $item['parts'],
            'status' => (string) $item['status'],
            'source' => (string) $item['source'] === 'api' ? 'api' : 'dashboard',
            'at' => sms_format_time($item['created_at'])
        );
    }
    sms_api_json(200, array(
        'error' => false,
        'message' => 'SMS report.',
        'data' => array(
            'page' => (int) $log['page'],
            'has_more' => !empty($log['has_more']),
            'start_date' => $log['from'],
            'end_date' => $log['to'],
            'rows' => $rows
        )
    ));
}
