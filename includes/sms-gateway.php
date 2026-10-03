<?php
/**
 * Aakash Technologies SMS dashboard.
 * Clients send and create API tokens here. The upstream line token stays in admin settings.
 */

function sms_ensure_tables($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_api_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            label TEXT NOT NULL,
            token_hash TEXT NOT NULL UNIQUE,
            token_prefix TEXT NOT NULL,
            status TEXT DEFAULT 'active',
            allowed_ips TEXT DEFAULT '',
            last_used_at TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_api_hits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token_id INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            campaign_id INTEGER DEFAULT 0,
            token_id INTEGER DEFAULT 0,
            source TEXT DEFAULT 'dashboard',
            sender_id TEXT DEFAULT '',
            recipient TEXT NOT NULL,
            message_text TEXT NOT NULL,
            parts INTEGER DEFAULT 1,
            status TEXT DEFAULT 'queued',
            error_text TEXT DEFAULT '',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            sent_at TEXT DEFAULT NULL
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_sender_names (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            sender_name TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            admin_note TEXT DEFAULT '',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_sms_token_client ON sms_api_tokens(client_id)');
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_sms_hit_token ON sms_api_hits(token_id, created_at)');
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_sms_msg_client ON sms_messages(client_id, created_at)');
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_sms_msg_campaign ON sms_messages(campaign_id)');
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_sms_sender_client ON sms_sender_names(client_id, status)');
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            label TEXT NOT NULL,
            message_text TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_number_lists (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            label TEXT NOT NULL,
            numbers_text TEXT NOT NULL,
            list_kind TEXT DEFAULT 'program',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_sms_template_client ON sms_templates(client_id)');
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_sms_list_client ON sms_number_lists(client_id)');
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_credit_notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            credits INTEGER NOT NULL,
            note TEXT DEFAULT '',
            reversed_at TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        sms_credit_columns($conn);
        sms_list_columns($conn);
        return;
    }

    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_api_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        label VARCHAR(80) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        token_prefix VARCHAR(16) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        allowed_ips VARCHAR(255) DEFAULT '',
        last_used_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_sms_token_hash (token_hash),
        INDEX idx_sms_token_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_api_hits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sms_hit_token (token_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        campaign_id INT DEFAULT 0,
        token_id INT DEFAULT 0,
        source VARCHAR(20) DEFAULT 'dashboard',
        sender_id VARCHAR(20) DEFAULT '',
        recipient VARCHAR(20) NOT NULL,
        message_text TEXT NOT NULL,
        parts INT DEFAULT 1,
        status VARCHAR(20) DEFAULT 'queued',
        error_text VARCHAR(40) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME DEFAULT NULL,
        INDEX idx_sms_msg_client (client_id, created_at),
        INDEX idx_sms_msg_campaign (campaign_id),
        INDEX idx_sms_msg_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_sender_names (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        sender_name VARCHAR(20) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        admin_note VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sms_sender_client (client_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        label VARCHAR(80) NOT NULL,
        message_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sms_template_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_number_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        label VARCHAR(80) NOT NULL,
        numbers_text TEXT NOT NULL,
        list_kind VARCHAR(20) DEFAULT 'program',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sms_list_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_credit_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        credits INT NOT NULL,
        note VARCHAR(160) DEFAULT '',
        reversed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sms_credit_client (client_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    sms_credit_columns($conn);
    sms_list_columns($conn);
}

function sms_credit_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'sms_credit_notes'));
    if (!$present || isset($present['reversed_at'])) {
        return;
    }
    $definition = DB_DRIVER === 'sqlite' ? 'TEXT DEFAULT NULL' : 'DATETIME DEFAULT NULL';
    billing_exec($conn, 'ALTER TABLE sms_credit_notes ADD COLUMN reversed_at ' . $definition);
}

function sms_list_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'sms_number_lists'));
    if (!$present || isset($present['list_kind'])) {
        return;
    }
    $definition = DB_DRIVER === 'sqlite' ? "TEXT DEFAULT 'program'" : "VARCHAR(20) DEFAULT 'program'";
    billing_exec($conn, 'ALTER TABLE sms_number_lists ADD COLUMN list_kind ' . $definition);
}

function sms_sample_messages()
{
    return array(
        array('id' => 'sample-otp-np', 'label' => 'OTP · नेपाली', 'text' => 'नमस्कार। [सहकारी] को तपाईंको कोड [कोड] हो। यो कोड अरूलाई नदिनुहोस्।'),
        array('id' => 'sample-otp-en', 'label' => 'OTP · English', 'text' => 'Namaste. Your [cooperative] code is [code]. Do not share this code.'),
        array('id' => 'sample-agm-np', 'label' => 'AGM · नेपाली', 'text' => 'नमस्कार। [सहकारी] को वार्षिक साधारण सभा मिति [मिति], [समय] बजे [स्थान] मा हुनेछ। सबै शेयर सदस्यलाई उपस्थित हुन अनुरोध छ।'),
        array('id' => 'sample-agm-en', 'label' => 'AGM · English', 'text' => 'Namaste. The annual general meeting of [cooperative] is on [date] at [time], [place]. All share members are requested to attend.'),
        array('id' => 'sample-board-np', 'label' => 'सञ्चालक बैठक · नेपाली', 'text' => 'नमस्कार। [सहकारी] को सञ्चालक समितिको बैठक मिति [मिति], [समय] बजे [स्थान] मा बस्नेछ। सबै सञ्चालकलाई समयमै उपस्थित हुन अनुरोध छ।'),
        array('id' => 'sample-board-en', 'label' => 'Board meeting · English', 'text' => 'Namaste. The board meeting of [cooperative] is on [date] at [time], [place]. All directors are requested to attend on time.'),
        array('id' => 'sample-rate-save-np', 'label' => 'बचत ब्याजदर · नेपाली', 'text' => 'नमस्कार। [सहकारी] को बचत ब्याजदर मिति [मिति] देखि [नयाँ दर] कायम हुनेछ। जानकारीका लागि कार्यालयमा सम्पर्क गर्नुहोस्।'),
        array('id' => 'sample-rate-save-en', 'label' => 'Savings rate · English', 'text' => 'Namaste. From [date], the savings interest rate of [cooperative] will be [new rate]. Please contact the office for details.'),
        array('id' => 'sample-rate-loan-np', 'label' => 'ऋण ब्याजदर · नेपाली', 'text' => 'नमस्कार। [सहकारी] को ऋण ब्याजदर मिति [मिति] देखि [नयाँ दर] कायम हुनेछ। जानकारीका लागि कार्यालयमा सम्पर्क गर्नुहोस्।'),
        array('id' => 'sample-rate-loan-en', 'label' => 'Loan rate · English', 'text' => 'Namaste. From [date], the loan interest rate of [cooperative] will be [new rate]. Please contact the office for details.'),
        array('id' => 'sample-dividend-np', 'label' => 'लाभांश · नेपाली', 'text' => 'नमस्कार। [सहकारी] को लाभांश वितरण मिति [मिति] देखि [स्थान] मा सुरु हुन्छ। शेयर प्रमाणपत्र साथमा ल्याउनुहोस्।'),
        array('id' => 'sample-dividend-en', 'label' => 'Dividend · English', 'text' => 'Namaste. [cooperative] will distribute dividends from [date] at [place]. Please bring your share certificate.'),
        array('id' => 'sample-installment-np', 'label' => 'ऋण किस्ता · नेपाली', 'text' => 'नमस्कार। [सहकारी] मा तपाईंको ऋण किस्ता मिति [मिति] भित्र बुझाउनुहोस्। ढिला भएमा जरिवाना लाग्न सक्छ।'),
        array('id' => 'sample-installment-en', 'label' => 'Loan installment · English', 'text' => 'Namaste. Please pay your loan installment at [cooperative] by [date]. A delay may add a penalty.'),
        array('id' => 'sample-share-np', 'label' => 'शेयर संकलन · नेपाली', 'text' => 'नमस्कार। [सहकारी] मा नयाँ शेयर संकलन मिति [मिति] सम्म खुला छ। इच्छुक सदस्यले कार्यालयमा सम्पर्क गर्नुहोस्।'),
        array('id' => 'sample-share-en', 'label' => 'Share collection · English', 'text' => 'Namaste. New share collection at [cooperative] is open until [date]. Please contact the office.'),
        array('id' => 'sample-closed-np', 'label' => 'कार्यालय बिदा · नेपाली', 'text' => 'नमस्कार। [सहकारी] को कार्यालय मिति [मिति] मा बिदा रहनेछ। अर्को कार्यदिनदेखि सेवा खुल्नेछ।'),
        array('id' => 'sample-closed-en', 'label' => 'Office closed · English', 'text' => 'Namaste. The office of [cooperative] will remain closed on [date]. Service resumes the next working day.')
    );
}

function sms_templates($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT id, label, message_text FROM sms_templates WHERE client_id = ? ORDER BY id DESC');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    return $rows;
}

function sms_save_template($conn, $clientId, $label, $text)
{
    $clientId = (int) $clientId;
    $gate = sms_client_gate($conn, $clientId);
    if ($gate !== '') {
        return $gate;
    }
    $label = billing_plain_line($label, 60);
    $text = billing_plain_block($text, 1000);
    if ($label === '' || $text === '') {
        return 'Name the saved message and write the text first.';
    }
    $blocked = sms_prohibited_notice($text);
    if ($blocked !== '') {
        return $blocked;
    }
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM sms_templates WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $count = db_fetch_assoc($stmt);
    $stmt->close();
    if ($count && (int) $count['c'] >= 20) {
        return 'Delete a saved message before adding another. Twenty is the limit.';
    }
    $stmt = $conn->prepare('INSERT INTO sms_templates (client_id, label, message_text) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $clientId, $label, $text);
    $stmt->execute();
    $stmt->close();
    return '';
}

function sms_delete_template($conn, $clientId, $templateId)
{
    $clientId = (int) $clientId;
    $templateId = (int) $templateId;
    $stmt = $conn->prepare('DELETE FROM sms_templates WHERE id = ? AND client_id = ?');
    $stmt->bind_param('ii', $templateId, $clientId);
    $stmt->execute();
    $changed = (int) $conn->affected_rows > 0;
    $stmt->close();
    return $changed;
}

function sms_number_lists($conn, $clientId)
{
    $clientId = (int) $clientId;
    sms_list_columns($conn);
    $stmt = $conn->prepare('SELECT id, label, numbers_text, list_kind FROM sms_number_lists WHERE client_id = ? ORDER BY list_kind ASC, label ASC');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    return $rows;
}

function sms_save_number_list($conn, $clientId, $label, $raw, $kind = 'program')
{
    $clientId = (int) $clientId;
    $gate = sms_client_gate($conn, $clientId);
    if ($gate !== '') {
        return $gate;
    }
    $label = billing_plain_line($label, 60);
    if ($label === '') {
        return 'Name this number list, such as AGM members or Monthly interest.';
    }
    $kind = $kind === 'regular' ? 'regular' : 'program';
    $parsed = sms_collect_numbers($raw);
    if (!$parsed['ok']) {
        return $parsed['error'];
    }
    sms_list_columns($conn);
    $numbers = implode("\n", $parsed['numbers']);
    $existing = $conn->prepare('SELECT id FROM sms_number_lists WHERE client_id = ? AND label = ? AND list_kind = ? LIMIT 1');
    $existing->bind_param('iss', $clientId, $label, $kind);
    $existing->execute();
    $found = db_fetch_assoc($existing);
    $existing->close();
    if ($found) {
        $id = (int) $found['id'];
        $stmt = $conn->prepare('UPDATE sms_number_lists SET numbers_text = ? WHERE id = ? AND client_id = ?');
        $stmt->bind_param('sii', $numbers, $id, $clientId);
        $stmt->execute();
        $stmt->close();
        return '';
    }
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM sms_number_lists WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $count = db_fetch_assoc($stmt);
    $stmt->close();
    if ($count && (int) $count['c'] >= 40) {
        return 'Delete a number list before adding another. Forty is the limit.';
    }
    $stmt = $conn->prepare('INSERT INTO sms_number_lists (client_id, label, numbers_text, list_kind) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $clientId, $label, $numbers, $kind);
    $stmt->execute();
    $stmt->close();
    return '';
}

function sms_delete_number_list($conn, $clientId, $listId)
{
    $clientId = (int) $clientId;
    $listId = (int) $listId;
    $stmt = $conn->prepare('DELETE FROM sms_number_lists WHERE id = ? AND client_id = ?');
    $stmt->bind_param('ii', $listId, $clientId);
    $stmt->execute();
    $changed = (int) $conn->affected_rows > 0;
    $stmt->close();
    return $changed;
}

function sms_message_parts($text)
{
    $text = (string) $text;
    $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    if ($length < 1) {
        return 0;
    }
    $unicode = preg_match('/[^\x0A\x0D\x20-\x7E]/', $text) === 1;
    if ($unicode) {
        return $length <= 70 ? 1 : (int) ceil($length / 67);
    }
    return $length <= 160 ? 1 : (int) ceil($length / 153);
}

function sms_result($ok, $error, $extra = array())
{
    return array_merge(array(
        'ok' => $ok,
        'error' => $error,
        'message' => $ok ? $error : '',
        'count' => 0,
        'credits' => 0,
        'balance' => 0,
        'campaign_id' => 0
    ), $extra, array('ok' => $ok, 'error' => $ok ? '' : $error));
}

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

function sms_line($conn)
{
    $provider = billing_setting($conn, 'sms_line_provider');
    if ($provider !== 'aakash' && $provider !== 'sparrow') {
        $provider = '';
    }
    $mode = billing_setting($conn, 'sms_line_sender_mode');
    if ($mode !== 'approved') {
        $mode = 'fixed';
    }
    $sender = strtoupper(billing_plain_line(billing_setting($conn, 'sms_line_sender'), 11));
    if (!preg_match('/^[A-Z0-9]{3,11}$/', $sender)) {
        $sender = '';
    }
    return array(
        'provider' => $provider,
        'sender' => $sender,
        'sender_mode' => $mode,
        'connected' => $provider !== '' && billing_setting($conn, 'sms_line_token') !== ''
    );
}

function sms_line_secret($conn)
{
    $line = sms_line($conn);
    $endpoint = trim(billing_setting($conn, 'sms_line_endpoint'));
    if ($endpoint !== '' && !preg_match('#^https?://[A-Za-z0-9.-]+(?::\d+)?/[A-Za-z0-9_./-]*$#', $endpoint)) {
        $endpoint = '';
    }
    $line['token'] = billing_setting($conn, 'sms_line_token');
    $line['endpoint'] = $endpoint;
    return $line;
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

function sms_contact_name($value)
{
    $value = trim((string) $value);
    $value = preg_replace('/[^\p{L}\p{M} .\'-]/u', '', $value);
    if (!is_string($value)) {
        return '';
    }
    $value = preg_replace('/\s+/u', ' ', trim($value));
    if (!is_string($value)) {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, 40);
    }
    return substr($value, 0, 40);
}

function sms_personalize($text, $name)
{
    $text = (string) $text;
    if (strpos($text, '{name}') === false) {
        return $text;
    }
    $name = trim((string) $name);
    $text = str_replace('{name}', $name, $text);
    $text = preg_replace('/\s+,/u', ',', $text);
    $text = preg_replace('/^[\s,]+/u', '', (string) $text);
    $text = preg_replace('/[ ]{2,}/', ' ', (string) $text);
    return is_string($text) ? trim($text) : '';
}

function sms_collect_contacts($raw)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $contacts = array();
    if (!is_array($lines)) {
        return array('ok' => false, 'error' => 'Add at least one mobile number.', 'contacts' => array());
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = preg_split('/[\s,;]+/', $line);
        if (!is_array($parts)) {
            continue;
        }
        $numbers = array();
        $words = array();
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (!preg_match('/\d/', $part)) {
                $words[] = $part;
                continue;
            }
            $digits = auth_mobile_number($part);
            if (!preg_match('/^9[78]\d{8}$/', $digits)) {
                return array('ok' => false, 'error' => 'Each number has to be a 10-digit Nepal mobile, Nepal Telecom or Ncell.', 'contacts' => array());
            }
            $numbers[$digits] = $digits;
        }
        $name = sms_contact_name(implode(' ', $words));
        foreach ($numbers as $digits) {
            if (!isset($contacts[$digits])) {
                $contacts[$digits] = array('number' => $digits, 'name' => $name);
            }
        }
    }
    $contacts = array_values($contacts);
    if (count($contacts) < 1) {
        return array('ok' => false, 'error' => 'Add at least one mobile number.', 'contacts' => array());
    }
    if (count($contacts) > 500) {
        return array('ok' => false, 'error' => 'Send at most 500 numbers at a time.', 'contacts' => array());
    }
    return array('ok' => true, 'error' => '', 'contacts' => $contacts);
}

function sms_collect_numbers($raw)
{
    $parsed = sms_collect_contacts($raw);
    $numbers = array();
    if (!empty($parsed['ok'])) {
        foreach ($parsed['contacts'] as $contact) {
            $numbers[] = $contact['number'];
        }
    }
    return array(
        'ok' => !empty($parsed['ok']),
        'error' => isset($parsed['error']) ? $parsed['error'] : '',
        'numbers' => $numbers
    );
}

function sms_import_cell($value)
{
    $value = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8'));
    $value = strtr($value, array(
        '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
        '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9'
    ));
    return trim($value);
}

function sms_import_header_kind($cell)
{
    $cell = strtolower(sms_import_cell($cell));
    $cell = str_replace(array('_', '-'), ' ', $cell);
    if (in_array($cell, array('mobile', 'phone', 'number', 'contact', 'मोबाइल', 'नम्बर', 'सम्पर्क'), true)) {
        return 'mobile';
    }
    if (in_array($cell, array('firstname', 'first name', 'fname', 'नाम'), true)) {
        return 'first';
    }
    if (in_array($cell, array('lastname', 'last name', 'lname', 'surname', 'थर'), true)) {
        return 'last';
    }
    if (in_array($cell, array('name', 'full name', 'पूरा नाम'), true)) {
        return 'name';
    }
    return '';
}

function sms_import_lines($rows)
{
    $cleanRows = array();
    foreach ($rows as $cells) {
        if (!is_array($cells)) {
            continue;
        }
        $clean = array();
        foreach ($cells as $cell) {
            $clean[] = sms_import_cell($cell);
        }
        $cleanRows[] = $clean;
    }
    $headerAt = -1;
    $map = array();
    foreach ($cleanRows as $index => $cells) {
        $kinds = array();
        foreach ($cells as $position => $cell) {
            $kind = sms_import_header_kind($cell);
            if ($kind !== '') {
                $kinds[$kind] = $position;
            }
        }
        if (isset($kinds['mobile'])) {
            $headerAt = $index;
            $map = $kinds;
            break;
        }
    }
    $lines = array();
    $seen = array();
    foreach ($cleanRows as $index => $cells) {
        if ($index === $headerAt) {
            continue;
        }
        $number = '';
        $name = '';
        if ($map) {
            $mobileCell = isset($cells[$map['mobile']]) ? $cells[$map['mobile']] : '';
            $digits = auth_mobile_number($mobileCell);
            if (preg_match('/^9[78]\d{8}$/', $digits)) {
                $number = $digits;
            }
            $first = isset($map['first'], $cells[$map['first']]) ? $cells[$map['first']] : '';
            $last = isset($map['last'], $cells[$map['last']]) ? $cells[$map['last']] : '';
            $full = isset($map['name'], $cells[$map['name']]) ? $cells[$map['name']] : '';
            $name = trim($first . ' ' . $last);
            if ($name === '') {
                $name = $full;
            }
        } else {
            $words = array();
            foreach ($cells as $cell) {
                if ($cell === '' || sms_import_header_kind($cell) !== '') {
                    continue;
                }
                $digits = auth_mobile_number($cell);
                if (preg_match('/^9[78]\d{8}$/', $digits)) {
                    $number = $digits;
                    continue;
                }
                if (!preg_match('/\d/', $cell)) {
                    $words[] = $cell;
                }
            }
            $name = implode(' ', $words);
        }
        if ($number === '' || isset($seen[$number])) {
            continue;
        }
        $seen[$number] = true;
        $name = sms_contact_name($name);
        $lines[] = $name === '' ? $number : $name . ', ' . $number;
        if (count($lines) >= 500) {
            break;
        }
    }
    return implode("\n", $lines);
}

function sms_sample_workbook($kind)
{
    if ($kind === 'names') {
        $rows = array(
            array('firstname', 'lastname', 'mobile'),
            array('Ram', 'Bahadur', '9801000001'),
            array('Sita', 'Devi', '9801000002')
        );
        $filename = 'sms-names.xlsx';
    } else {
        $rows = array(
            array('mobile'),
            array('9801000001'),
            array('9801000002')
        );
        $filename = 'sms-mobile.xlsx';
    }
    $shared = array();
    $sheetRows = array();
    $rowNumber = 1;
    foreach ($rows as $row) {
        $cells = array();
        $column = 0;
        foreach ($row as $value) {
            $letter = chr(65 + $column);
            $index = array_search($value, $shared, true);
            if ($index === false) {
                $shared[] = $value;
                $index = count($shared) - 1;
            }
            $cells[] = '<c r="' . $letter . $rowNumber . '" t="s"><v>' . $index . '</v></c>';
            $column++;
        }
        $sheetRows[] = '<row r="' . $rowNumber . '">' . implode('', $cells) . '</row>';
        $rowNumber++;
    }
    $stringItems = array();
    foreach ($shared as $value) {
        $stringItems[] = '<si><t>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</t></si>';
    }
    $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">' . implode('', $stringItems) . '</sst>';
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . implode('', $sheetRows) . '</sheetData></worksheet>';
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>';
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>';
    $tmp = tempnam(sys_get_temp_dir(), 'smsx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();
    $binary = file_get_contents($tmp);
    unlink($tmp);
    return array('filename' => $filename, 'body' => is_string($binary) ? $binary : '');
}

function sms_import_text_rows($text)
{
    $text = preg_replace('/^\xEF\xBB\xBF/', '', (string) $text);
    $rows = array();
    $handle = fopen('php://temp', 'w+');
    if (!$handle) {
        return $rows;
    }
    fwrite($handle, $text);
    rewind($handle);
    while (($cells = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if (is_array($cells) && count($cells) === 1 && strpos((string) $cells[0], "\t") !== false) {
            $cells = explode("\t", (string) $cells[0]);
        }
        $rows[] = is_array($cells) ? $cells : array();
    }
    fclose($handle);
    return $rows;
}

function sms_import_xlsx_rows($path)
{
    if (!class_exists('ZipArchive')) {
        return null;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return null;
    }
    $shared = array();
    $stringXml = $zip->getFromName('xl/sharedStrings.xml');
    if (is_string($stringXml) && preg_match_all('/<si\b[^>]*>(.*?)<\/si>/s', $stringXml, $items)) {
        foreach ($items[1] as $item) {
            $text = '';
            if (preg_match_all('/<t\b[^>]*>(.*?)<\/t>/s', $item, $texts)) {
                $text = html_entity_decode(implode('', $texts[1]), ENT_QUOTES, 'UTF-8');
            }
            $shared[] = $text;
        }
    }
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!is_string($sheet)) {
        return array();
    }
    $rows = array();
    if (!preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s', $sheet, $rowMatches)) {
        return $rows;
    }
    foreach ($rowMatches[1] as $rowXml) {
        $placed = array();
        if (preg_match_all('/<c\b([^>]*)>(.*?)<\/c>/s', $rowXml, $cellMatches, PREG_SET_ORDER)) {
            $fallback = 0;
            foreach ($cellMatches as $cell) {
                $value = '';
                if (preg_match('/<v>(.*?)<\/v>/s', $cell[2], $raw)) {
                    $value = $raw[1];
                } elseif (preg_match('/<t\b[^>]*>(.*?)<\/t>/s', $cell[2], $raw)) {
                    $value = html_entity_decode($raw[1], ENT_QUOTES, 'UTF-8');
                }
                if (strpos($cell[1], 't="s"') !== false) {
                    $index = (int) $value;
                    $value = isset($shared[$index]) ? $shared[$index] : '';
                }
                $column = $fallback;
                if (preg_match('/\br="([A-Z]+)\d+"/', $cell[1], $ref)) {
                    $column = 0;
                    $letters = $ref[1];
                    $length = strlen($letters);
                    for ($i = 0; $i < $length; $i++) {
                        $column = ($column * 26) + (ord($letters[$i]) - 64);
                    }
                    $column--;
                }
                $placed[$column] = $value;
                $fallback = $column + 1;
            }
        }
        if ($placed) {
            ksort($placed);
            $max = max(array_keys($placed));
            $cells = array();
            for ($i = 0; $i <= $max; $i++) {
                $cells[] = isset($placed[$i]) ? $placed[$i] : '';
            }
        } else {
            $cells = array();
        }
        if ($cells) {
            $rows[] = $cells;
        }
        if (count($rows) > 2000) {
            break;
        }
    }
    return $rows;
}

function sms_import_file($path, $name)
{
    $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
    if ($ext === 'xlsx') {
        $rows = sms_import_xlsx_rows($path);
        if ($rows === null) {
            return array('ok' => false, 'error' => 'This server cannot read Excel files. Save the sheet as CSV and upload that.', 'numbers' => '', 'count' => 0);
        }
    } elseif ($ext === 'csv' || $ext === 'txt') {
        $text = file_get_contents($path);
        $rows = is_string($text) ? sms_import_text_rows($text) : array();
    } else {
        return array('ok' => false, 'error' => 'Upload an Excel .xlsx file or a .csv file. Old .xls sheets need to be saved as .xlsx.', 'numbers' => '', 'count' => 0);
    }
    $numbers = sms_import_lines($rows);
    if ($numbers === '') {
        return array('ok' => false, 'error' => 'No 10-digit Nepal mobile was found. Put numbers in the first sheet, one row each.', 'numbers' => '', 'count' => 0);
    }
    $count = substr_count($numbers, "\n") + 1;
    return array('ok' => true, 'error' => '', 'numbers' => $numbers, 'count' => $count);
}

function sms_store_contacts($contacts)
{
    $lines = array();
    foreach ($contacts as $contact) {
        $name = isset($contact['name']) ? (string) $contact['name'] : '';
        $number = (string) $contact['number'];
        $lines[] = $name === '' ? $number : $name . "\t" . $number;
    }
    return implode("\n", $lines);
}

function sms_contacts_from_stored($raw)
{
    $contacts = array();
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    if (!is_array($lines)) {
        return $contacts;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $name = '';
        $number = $line;
        if (strpos($line, "\t") !== false) {
            $pair = explode("\t", $line, 2);
            $name = sms_contact_name($pair[0]);
            $number = $pair[1];
        }
        $digits = auth_mobile_number($number);
        if (!preg_match('/^9[78]\d{8}$/', $digits) || isset($contacts[$digits])) {
            continue;
        }
        $contacts[$digits] = array('number' => $digits, 'name' => $name);
    }
    return array_values($contacts);
}

function sms_render_messages($template, $contacts)
{
    $messages = array();
    $credits = 0;
    foreach ($contacts as $contact) {
        $text = sms_personalize($template, isset($contact['name']) ? $contact['name'] : '');
        if ($text === '') {
            return array('ok' => false, 'error' => 'The message is empty after the name is added.', 'messages' => array(), 'credits' => 0);
        }
        $parts = sms_message_parts($text);
        $credits += $parts;
        $messages[] = array(
            'number' => $contact['number'],
            'text' => $text,
            'parts' => $parts
        );
    }
    if (!$messages) {
        return array('ok' => false, 'error' => 'Add at least one mobile number.', 'messages' => array(), 'credits' => 0);
    }
    return array('ok' => true, 'error' => '', 'messages' => $messages, 'credits' => $credits);
}

function sms_parse_schedule($when)
{
    $when = trim((string) $when);
    if ($when === '') {
        return array('ok' => true, 'future' => false, 'stored' => '', 'label' => '', 'error' => '');
    }
    $kathmandu = new DateTimeZone('Asia/Kathmandu');
    $parsed = DateTime::createFromFormat('!Y-m-d H:i:s', $when, $kathmandu);
    $dateErrors = DateTime::getLastErrors();
    $dateBroken = !$parsed || (is_array($dateErrors) && (($dateErrors['warning_count'] > 0) || ($dateErrors['error_count'] > 0)));
    if ($dateBroken) {
        return array('ok' => false, 'future' => false, 'stored' => '', 'label' => '', 'error' => 'That send time is not valid.');
    }
    if ($parsed->getTimestamp() + 30 < time()) {
        return array('ok' => false, 'future' => false, 'stored' => '', 'label' => '', 'error' => 'That time has passed. Leave it empty to send now, or pick a later Nepal time.');
    }
    $label = $parsed->format('M j, H:i');
    $future = $parsed->getTimestamp() > time() + 30;
    $parsed->setTimezone(new DateTimeZone(date_default_timezone_get()));
    return array(
        'ok' => true,
        'future' => $future,
        'stored' => $parsed->format('Y-m-d H:i:s'),
        'label' => $label,
        'error' => ''
    );
}

function sms_csv_cell($value)
{
    $value = (string) $value;
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;
    }
    return $value;
}

function sms_format_time($stored)
{
    $stored = trim((string) $stored);
    if ($stored === '') {
        return '';
    }
    try {
        $parsed = new DateTime($stored, new DateTimeZone(date_default_timezone_get()));
    } catch (Exception $exception) {
        return $stored;
    }
    $parsed->setTimezone(new DateTimeZone('Asia/Kathmandu'));
    return $parsed->format('M j, H:i');
}

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
        $stmt = $conn->prepare('SELECT recipient FROM sms_messages WHERE campaign_id = ? AND client_id = ? AND status = ? ORDER BY id ASC');
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
    $stmt = $conn->prepare('SELECT m.id, m.recipient, m.sender_id, m.message_text, m.parts, m.status, m.source, m.error_text, m.created_at, m.sent_at, c.campaign_name FROM sms_messages m LEFT JOIN sms_campaigns c ON c.id = m.campaign_id WHERE ' . implode(' AND ', $aliased) . ' ORDER BY m.created_at DESC, m.id DESC LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset);
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
    return $taken;
}

function sms_http_form($url, $fields, $timeout = 25)
{
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'status' => 0, 'body' => '');
    }
    $timeout = (int) $timeout;
    if ($timeout < 3) {
        $timeout = 3;
    }
    if ($timeout > 25) {
        $timeout = 25;
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(8, $timeout));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    if (!is_string($body)) {
        $body = '';
    }
    if (strlen($body) > 262144) {
        $body = substr($body, 0, 262144);
    }
    return array(
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => $body
    );
}

function sms_http_json($url, $payload, $headers, $timeout = 25)
{
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'status' => 0, 'body' => '');
    }
    $timeout = (int) $timeout;
    if ($timeout < 3) {
        $timeout = 3;
    }
    if ($timeout > 25) {
        $timeout = 25;
    }
    $bodyIn = json_encode($payload);
    if ($bodyIn === false) {
        $bodyIn = '{}';
    }
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Accept: application/json';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyIn);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(8, $timeout));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    if (!is_string($body)) {
        $body = '';
    }
    if (strlen($body) > 262144) {
        $body = substr($body, 0, 262144);
    }
    return array(
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => $body
    );
}

function sms_aakash_invalid_rows($node, &$rows)
{
    if (!is_array($node)) {
        return;
    }
    if (isset($node['invalid']) && is_array($node['invalid'])) {
        foreach ($node['invalid'] as $item) {
            $rows[] = $item;
        }
    }
    foreach ($node as $item) {
        if (is_array($item)) {
            sms_aakash_invalid_rows($item, $rows);
        }
    }
}

function sms_aakash_v4_problem($json)
{
    if (!is_array($json)) {
        return 'line-rejected';
    }
    $messages = array();
    $failed = !empty($json['error']);
    if (!empty($json['errors']) && is_array($json['errors'])) {
        $failed = true;
        foreach ($json['errors'] as $error) {
            if (is_array($error) && isset($error['message'])) {
                $messages[] = strtolower((string) $error['message']);
            }
        }
    }
    if (isset($json['responses']) && is_array($json['responses'])) {
        foreach ($json['responses'] as $response) {
            if (!is_array($response) || empty($response['error'])) {
                continue;
            }
            $failed = true;
            if (isset($response['message'])) {
                $messages[] = strtolower((string) $response['message']);
            }
            if (!empty($response['errors']) && is_array($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    if (is_array($error) && isset($error['message'])) {
                        $messages[] = strtolower((string) $error['message']);
                    }
                }
            }
        }
    }
    $joined = implode(' ', $messages);
    if (strpos($joined, 'balance') !== false || strpos($joined, 'credit') !== false) {
        return 'line-empty';
    }
    return $failed ? 'line-rejected' : '';
}

function sms_line_mobile($value)
{
    $digits = auth_mobile_number($value);
    if (!preg_match('/^9[78]\d{8}$/', $digits)) {
        return '';
    }
    return $digits;
}

function sms_aakash_rejected($json, $numbers)
{
    if (!is_array($json) || !empty($json['error'])) {
        return null;
    }
    $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : array();
    $invalid = isset($data['invalid']) && is_array($data['invalid']) ? $data['invalid'] : array();
    $valid = isset($data['valid']) && is_array($data['valid']) ? $data['valid'] : array();
    if (!$invalid && !$valid) {
        return array();
    }
    $rejected = array();
    foreach ($invalid as $item) {
        if (!is_array($item)) {
            continue;
        }
        $digits = sms_line_mobile(isset($item['mobile']) ? $item['mobile'] : '');
        if ($digits !== '') {
            $rejected[$digits] = $digits;
        }
    }
    foreach ($valid as $item) {
        if (!is_array($item)) {
            continue;
        }
        $digits = sms_line_mobile(isset($item['mobile']) ? $item['mobile'] : '');
        $status = isset($item['status']) ? strtolower((string) $item['status']) : '';
        if ($digits !== '' && ($status === 'aborted' || $status === 'invalid')) {
            $rejected[$digits] = $digits;
        }
    }
    $asked = array();
    foreach ($numbers as $number) {
        $asked[(string) $number] = true;
    }
    $out = array();
    foreach ($rejected as $digits) {
        if (isset($asked[$digits])) {
            $out[] = $digits;
        }
    }
    return $out;
}

function sms_vendor_send($conn, $numbers, $text, $sender, $timeout = 25)
{
    $line = sms_line_secret($conn);
    if ($line['provider'] === '' || $line['token'] === '') {
        return array('code' => 'line-off', 'rejected' => array());
    }
    $to = implode(',', $numbers);
    if ($line['provider'] === 'aakash') {
        $custom = $line['endpoint'];
        $useV4 = $custom === '' || strpos($custom, '/sms/v4/') !== false;
        if ($useV4) {
            $url = $custom !== '' ? $custom : 'https://sms.aakashsms.com/sms/v4/send-user';
            $response = sms_http_json($url, array(
                'to' => array_values($numbers),
                'text' => array($text)
            ), array('auth-token: ' . $line['token']), $timeout);
            $json = json_decode($response['body'], true);
            $problem = sms_aakash_v4_problem(is_array($json) ? $json : array());
            if ($response['ok'] && $problem === '') {
                $invalid = array();
                sms_aakash_invalid_rows(is_array($json) ? $json : array(), $invalid);
                $rejected = sms_aakash_rejected(array('data' => array('invalid' => $invalid, 'valid' => array())), $numbers);
                return array('code' => '', 'rejected' => is_array($rejected) ? $rejected : array());
            }
            return array('code' => $problem !== '' ? $problem : 'line-rejected', 'rejected' => array());
        }
        $url = $custom !== '' ? $custom : 'https://sms.aakashsms.com/sms/v3/send/';
        $response = sms_http_form($url, array(
            'auth_token' => $line['token'],
            'to' => $to,
            'text' => $text
        ), $timeout);
        $json = json_decode($response['body'], true);
        if ($response['ok'] && is_array($json) && empty($json['error'])) {
            $rejected = sms_aakash_rejected($json, $numbers);
            return array('code' => '', 'rejected' => is_array($rejected) ? $rejected : array());
        }
        $message = is_array($json) && isset($json['message']) ? strtolower((string) $json['message']) : '';
        if (strpos($message, 'balance') !== false || strpos($message, 'credit') !== false) {
            return array('code' => 'line-empty', 'rejected' => array());
        }
        return array('code' => 'line-rejected', 'rejected' => array());
    }
    $url = $line['endpoint'] !== '' ? $line['endpoint'] : 'http://api.sparrowsms.com/v2/sms/';
    $response = sms_http_form($url, array(
        'token' => $line['token'],
        'from' => $sender,
        'to' => $to,
        'text' => $text
    ), $timeout);
    $json = json_decode($response['body'], true);
    $code = is_array($json) && isset($json['response_code']) ? (int) $json['response_code'] : 0;
    if ($response['status'] === 200 && $code === 200) {
        return array('code' => '', 'rejected' => array());
    }
    if ($code === 1008) {
        return array('code' => 'sender-rejected', 'rejected' => array());
    }
    if ($code === 1012 || $code === 1013) {
        return array('code' => 'line-empty', 'rejected' => array());
    }
    return array('code' => 'line-rejected', 'rejected' => array());
}

function sms_find_balance($value)
{
    $wanted = array('credits_available', 'available_credit', 'credit', 'credits', 'balance', 'available');
    if (!is_array($value)) {
        return null;
    }
    foreach ($wanted as $key) {
        foreach ($value as $name => $item) {
            if (strtolower((string) $name) === $key && is_numeric($item)) {
                return (int) $item;
            }
        }
    }
    foreach ($value as $item) {
        if (is_array($item)) {
            $found = sms_find_balance($item);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

function sms_line_balance($conn)
{
    $line = sms_line_secret($conn);
    if ($line['provider'] === '' || $line['token'] === '') {
        return array('ok' => false, 'error' => 'Save the SMS line first.', 'balance' => null);
    }
    if ($line['provider'] === 'aakash') {
        $response = sms_http_json('https://sms.aakashsms.com/sms/v4/credit', new stdClass(), array('auth-token: ' . $line['token']));
        $json = json_decode($response['body'], true);
        $balance = is_array($json) ? sms_find_balance($json) : null;
        if ($balance === null) {
            $response = sms_http_form('https://sms.aakashsms.com/sms/v1/credit', array('auth_token' => $line['token']));
        }
    } else {
        $response = sms_http_form('http://api.sparrowsms.com/v2/credit/', array('token' => $line['token']));
    }
    $json = json_decode($response['body'], true);
    if (!is_array($json) || !empty($json['error'])) {
        return array('ok' => false, 'error' => 'The SMS line did not accept the token.', 'balance' => null);
    }
    $balance = sms_find_balance($json);
    if ($balance === null) {
        return array('ok' => false, 'error' => 'The SMS line answered, but no balance figure was included.', 'balance' => null);
    }
    return array('ok' => true, 'error' => '', 'balance' => $balance);
}

function sms_vendor_stock($conn, $force = false)
{
    $line = sms_line($conn);
    $label = $line['provider'] === 'aakash' ? 'Aakash SMS' : ($line['provider'] === 'sparrow' ? 'Sparrow SMS' : '');
    $saved = billing_setting($conn, 'sms_vendor_balance');
    $checked = billing_setting($conn, 'sms_vendor_checked');
    $balance = ($saved !== '' && is_numeric($saved)) ? (int) $saved : null;
    $age = $checked !== '' ? time() - (int) strtotime($checked) : 999999;
    if (!$line['connected']) {
        return array('balance' => null, 'checked' => '', 'error' => '', 'label' => '');
    }
    if (!$force && $balance !== null && $age >= 0 && $age < 900) {
        return array('balance' => $balance, 'checked' => $checked, 'error' => '', 'label' => $label);
    }
    $live = sms_line_balance($conn);
    if (!empty($live['ok'])) {
        $now = date('Y-m-d H:i:s');
        billing_set_setting($conn, 'sms_vendor_balance', (string) (int) $live['balance']);
        billing_set_setting($conn, 'sms_vendor_checked', $now);
        return array('balance' => (int) $live['balance'], 'checked' => $now, 'error' => '', 'label' => $label);
    }
    return array(
        'balance' => $balance,
        'checked' => $checked,
        'error' => (string) $live['error'],
        'label' => $label
    );
}

function sms_vendor_stock_saved($conn)
{
    $line = sms_line($conn);
    $saved = billing_setting($conn, 'sms_vendor_balance');
    $label = $line['provider'] === 'aakash' ? 'Aakash SMS' : ($line['provider'] === 'sparrow' ? 'Sparrow SMS' : '');
    if (!$line['connected'] || $saved === '' || !is_numeric($saved)) {
        return array('balance' => null, 'label' => $label);
    }
    return array('balance' => (int) $saved, 'label' => $label);
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

function sms_line_test($conn, $number)
{
    $digits = auth_mobile_number($number);
    if (!preg_match('/^9[78]\d{8}$/', $digits)) {
        return 'Enter one 10-digit Nepal mobile for the check.';
    }
    $line = sms_line($conn);
    if (!$line['connected']) {
        return 'Save the API key first.';
    }
    if ($line['provider'] === 'sparrow' && $line['sender'] === '') {
        return 'Save the sender name on the Sparrow account first.';
    }
    $checked = sms_vendor_send($conn, array($digits), 'Aakash Technologies line check.', $line['sender']);
    if ($checked['code'] !== '' || $checked['rejected']) {
        return 'The line did not accept the check. Confirm the API key and that the bought account still has credit.';
    }
    return '';
}

function sms_save_line($conn, $post)
{
    $provider = isset($post['sms_line_provider']) ? (string) $post['sms_line_provider'] : '';
    if ($provider !== 'aakash' && $provider !== 'sparrow') {
        $provider = '';
    }
    $sender = strtoupper(billing_plain_line(isset($post['sms_line_sender']) ? $post['sms_line_sender'] : '', 11));
    if ($provider === 'sparrow' && !preg_match('/^[A-Z0-9]{3,11}$/', $sender)) {
        return 'Enter the sender name registered on the Sparrow account, 3 to 11 letters or numbers.';
    }
    if ($provider === 'aakash') {
        $sender = '';
    }
    $mode = (isset($post['sms_line_sender_mode']) && $post['sms_line_sender_mode'] === 'approved') ? 'approved' : 'fixed';
    if ($provider !== 'sparrow') {
        $mode = 'fixed';
    }
    $endpoint = trim(isset($post['sms_line_endpoint']) ? (string) $post['sms_line_endpoint'] : '');
    if ($endpoint !== '' && !preg_match('#^https?://[A-Za-z0-9.-]+(?::\d+)?/[A-Za-z0-9_./-]*$#', $endpoint)) {
        return 'The send address has to be an http or https URL, or leave it empty.';
    }
    $token = trim(isset($post['sms_line_token']) ? (string) $post['sms_line_token'] : '');
    $token = str_replace(array("\r", "\n", " "), '', $token);
    if ($provider !== '' && $token === '' && billing_setting($conn, 'sms_line_token') === '') {
        return 'Paste the API key from the account you buy SMS from.';
    }
    if ($token !== '' && (strlen($token) < 8 || strlen($token) > 200)) {
        return 'That API key does not look complete.';
    }
    billing_set_setting($conn, 'sms_line_provider', $provider);
    billing_set_setting($conn, 'sms_line_sender', $sender);
    billing_set_setting($conn, 'sms_line_sender_mode', $mode);
    billing_set_setting($conn, 'sms_line_endpoint', $endpoint);
    if ($token !== '') {
        billing_set_setting($conn, 'sms_line_token', $token);
    }
    if ($provider === '') {
        billing_set_setting($conn, 'sms_line_token', '');
    }
    return '';
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

function sms_insert_campaign($conn, $clientId, $name, $text, $sender, $count, $status, $when, $audience, $purpose, $list)
{
    $channel = 'sms';
    $declaration = billing_use_declaration();
    $when = $when === null ? '' : (string) $when;
    $stmt = $conn->prepare('INSERT INTO sms_campaigns (client_id, campaign_name, message_content, sender_id, recipients_count, status, scheduled_at, channel, audience, purpose, recipients_list, declaration_text) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssisssssss', $clientId, $name, $text, $sender, $count, $status, $when, $channel, $audience, $purpose, $list, $declaration);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();
    return $id;
}

function sms_insert_messages($conn, $clientId, $campaignId, $tokenId, $source, $sender, $text, $parts, $numbers)
{
    $status = 'queued';
    $error = '';
    $recipient = '';
    $stmt = $conn->prepare('INSERT INTO sms_messages (client_id, campaign_id, token_id, source, sender_id, recipient, message_text, parts, status, error_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiissssiss', $clientId, $campaignId, $tokenId, $source, $sender, $recipient, $text, $parts, $status, $error);
    $ids = array();
    foreach ($numbers as $number) {
        $recipient = $number;
        $stmt->execute();
        $ids[] = (int) $conn->insert_id;
    }
    $stmt->close();
    return $ids;
}

function sms_insert_rendered($conn, $clientId, $campaignId, $tokenId, $source, $sender, $messages)
{
    $status = 'queued';
    $error = '';
    $recipient = '';
    $text = '';
    $parts = 1;
    $stmt = $conn->prepare('INSERT INTO sms_messages (client_id, campaign_id, token_id, source, sender_id, recipient, message_text, parts, status, error_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiissssiss', $clientId, $campaignId, $tokenId, $source, $sender, $recipient, $text, $parts, $status, $error);
    $ids = array();
    foreach ($messages as $message) {
        $recipient = $message['number'];
        $text = $message['text'];
        $parts = (int) $message['parts'];
        $stmt->execute();
        $ids[] = (int) $conn->insert_id;
    }
    $stmt->close();
    return $ids;
}

function sms_mark_messages($conn, $ids, $status, $error)
{
    if (!$ids) {
        return;
    }
    $sentAt = $status === 'sent' ? date('Y-m-d H:i:s') : '';
    $id = 0;
    $stmt = $conn->prepare('UPDATE sms_messages SET status = ?, error_text = ?, sent_at = NULLIF(?, \'\') WHERE id = ?');
    $stmt->bind_param('sssi', $status, $error, $sentAt, $id);
    foreach ($ids as $messageId) {
        $id = (int) $messageId;
        $stmt->execute();
    }
    $stmt->close();
}

function sms_prohibited_notice($text)
{
    $text = str_replace(array("\r", "\n", "\t"), ' ', (string) $text);
    $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
    if (!is_string($text)) {
        return '';
    }
    $text = preg_replace('/\s+/u', ' ', $text);
    if (!is_string($text) || trim($text) === '') {
        return '';
    }
    $latin = strtolower($text);
    $latin = strtr($latin, array('@' => 'a', '$' => 's', '0' => 'o', '1' => 'i'));
    $patterns = array(
        '/(?<!do not )(?<!don\'t )\b(send|share|give|forward|reply with|enter|submit|whatsapp|viber)\b.{0,40}\b(otp|pin|password|passwd|cvv|mpin)\b/',
        '/\b(otp|pin|password|cvv|mpin)\b.{0,40}\b(pathau|pathaunu|to this number|to me)\b/',
        '/(ओटीपी|पिन कोड|पासवर्ड).{0,24}(?<!न)(पठाउनुहोस्|पठाउनु|दिनुहोस्|लेख्नुहोस्)/u',
        '/(?<!न)(पठाउनुहोस्|पठाउनुहोला|दिनुहोस्).{0,20}(ओटीपी|पासवर्ड|पिन)/u',
        '/\b(you have won|you\'ve won|you won|lucky winner|claim your prize|lottery winner|prize money)\b/',
        '/(जितेर हात|पुरस्कार पाउनुभयो|ल्याटरी जित)/u',
        '/\b(i will kill|bomb threat|pay or else)\b/',
        '/(मार्दिन्छु|मारिदिन्छु|बम राखेको छ)/u',
        '/\b(call girls?|escort service|child porn|underage sex|sex for money)\b/',
        '/\b(kill all|wipe out all)\b.{0,30}\b(muslims?|hindus?|christians?|dalits?|madhesis?)\b/',
        '/\b(cocaine|heroin|mdma)\b.{0,24}\b(for sale|buy now|price)\b/',
        '/(गाँजा|चरस).{0,16}(बेच्छ|बेच्ने|किन्नुहोस्)/u',
        '/\b(online casino|satta matka|cricket betting id)\b/',
        '/\b(account (is|has been) (suspended|blocked|locked))\b.{0,50}\b(click|http|verify now|link)\b/'
    );
    foreach ($patterns as $pattern) {
        $subject = substr($pattern, -2) === '/u' ? $text : $latin;
        if (preg_match($pattern, $subject)) {
            return 'This text cannot be sent. Nepal law does not allow fraud, a threat, sexual content, hate, or a request for a password, PIN, or OTP.';
        }
    }
    return '';
}

function sms_deliver_campaign($conn, $campaignId)
{
    if (function_exists('set_time_limit')) {
        @set_time_limit(180);
    }
    $campaignId = (int) $campaignId;
    $stmt = $conn->prepare('SELECT * FROM sms_campaigns WHERE id = ? AND channel = ?');
    $channel = 'sms';
    $stmt->bind_param('is', $campaignId, $channel);
    $stmt->execute();
    $campaign = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$campaign) {
        return sms_result(false, 'That SMS was not found.');
    }
    $clientId = (int) $campaign['client_id'];
    $text = (string) $campaign['message_content'];
    $blocked = sms_prohibited_notice($text);
    if ($blocked !== '') {
        $queued = $conn->prepare('SELECT id FROM sms_messages WHERE campaign_id = ? AND status IN (\'queued\', \'sending\')');
        $queued->bind_param('i', $campaignId);
        $queued->execute();
        $queuedRows = db_fetch_all($queued);
        $queued->close();
        $refundIds = array();
        foreach ($queuedRows as $queuedRow) {
            $refundIds[] = (int) $queuedRow['id'];
        }
        if ($refundIds) {
            billing_add_units($conn, $clientId, 'sms', sms_message_parts($text) * count($refundIds));
            sms_mark_messages($conn, $refundIds, 'failed', 'prohibited');
        }
        $failed = 'failed';
        $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $failed, $campaignId);
        $stmt->execute();
        $stmt->close();
        $balance = billing_unit_balances($conn, $clientId);
        return sms_result(false, $blocked, array(
            'balance' => (int) $balance['sms'],
            'campaign_id' => $campaignId
        ));
    }
    $sender = (string) $campaign['sender_id'];
    $parts = sms_message_parts($text);
    $contacts = sms_contacts_from_stored((string) $campaign['recipients_list']);
    $rendered = sms_render_messages($text, $contacts);
    if (!$rendered['ok']) {
        $failed = 'failed';
        $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $failed, $campaignId);
        $stmt->execute();
        $stmt->close();
        return sms_result(false, $rendered['error']);
    }
    $existing = $conn->prepare('SELECT id, recipient, status, message_text, parts FROM sms_messages WHERE campaign_id = ?');
    $existing->bind_param('i', $campaignId);
    $existing->execute();
    $rows = db_fetch_all($existing);
    $existing->close();
    $queue = array();
    if ($rows) {
        foreach ($rows as $row) {
            if ((string) $row['status'] === 'queued' || (string) $row['status'] === 'sending') {
                $queue[] = array(
                    'id' => (int) $row['id'],
                    'number' => (string) $row['recipient'],
                    'text' => (string) $row['message_text'],
                    'parts' => (int) $row['parts']
                );
            }
        }
    } else {
        $credits = (int) $rendered['credits'];
        if (!sms_take_credits($conn, $clientId, $credits)) {
            $failed = 'failed';
            $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $failed, $campaignId);
            $stmt->execute();
            $stmt->close();
            return sms_result(false, 'There are not enough SMS credits for this message.');
        }
        $ids = sms_insert_rendered($conn, $clientId, $campaignId, 0, 'dashboard', $sender, $rendered['messages']);
        foreach ($rendered['messages'] as $index => $message) {
            $queue[] = array(
                'id' => (int) $ids[$index],
                'number' => $message['number'],
                'text' => $message['text'],
                'parts' => (int) $message['parts']
            );
        }
    }
    $numbers = array();
    foreach ($queue as $item) {
        $numbers[] = $item['number'];
    }
    if (!$numbers) {
        $sentCount = 0;
        foreach ($rows as $row) {
            if ((string) $row['status'] === 'sent') {
                $sentCount++;
            }
        }
        $final = $sentCount > 0 ? 'sent' : 'failed';
        $now = $final === 'sent' ? date('Y-m-d H:i:s') : '';
        $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ?, sent_at = NULLIF(?, \'\') WHERE id = ?');
        $stmt->bind_param('ssi', $final, $now, $campaignId);
        $stmt->execute();
        $stmt->close();
        $balance = billing_unit_balances($conn, $clientId);
        if ($final === 'failed') {
            return sms_result(false, 'The message could not be sent. Credits for those numbers were returned.', array(
                'balance' => (int) $balance['sms'],
                'campaign_id' => $campaignId
            ));
        }
        return sms_result(true, '', array(
            'message' => 'Sent.',
            'count' => $sentCount,
            'credits' => 0,
            'balance' => (int) $balance['sms'],
            'campaign_id' => $campaignId
        ));
    }
    $failedNumbers = 0;
    $creditsSent = 0;
    $total = count($queue);
    $groups = array();
    foreach ($queue as $item) {
        $key = $item['text'];
        if (!isset($groups[$key])) {
            $groups[$key] = array('parts' => (int) $item['parts'], 'numbers' => array(), 'ids' => array());
        }
        $groups[$key]['numbers'][] = $item['number'];
        $groups[$key]['ids'][] = $item['id'];
    }
    foreach ($groups as $groupText => $group) {
        $offset = 0;
        $groupTotal = count($group['numbers']);
        while ($offset < $groupTotal) {
            $chunkNumbers = array_slice($group['numbers'], $offset, 100);
            $chunkIds = array_slice($group['ids'], $offset, 100);
            $offset += 100;
            $sentIds = array();
            $failIds = array();
            $result = sms_vendor_send($conn, $chunkNumbers, $groupText, $sender);
            if ($result['code'] !== '') {
                sms_mark_messages($conn, $chunkIds, 'failed', $result['code']);
                billing_add_units($conn, $clientId, 'sms', (int) $group['parts'] * count($chunkNumbers));
                $failedNumbers += count($chunkNumbers);
                continue;
            }
            $rejected = array();
            foreach ($result['rejected'] as $rejectedNumber) {
                $rejected[(string) $rejectedNumber] = true;
            }
            foreach ($chunkNumbers as $index => $number) {
                if (isset($rejected[(string) $number])) {
                    $failIds[] = $chunkIds[$index];
                } else {
                    $sentIds[] = $chunkIds[$index];
                }
            }
            sms_mark_messages($conn, $sentIds, 'sent', '');
            $creditsSent += (int) $group['parts'] * count($sentIds);
            if ($failIds) {
                sms_mark_messages($conn, $failIds, 'failed', 'not-accepted');
                billing_add_units($conn, $clientId, 'sms', (int) $group['parts'] * count($failIds));
                $failedNumbers += count($failIds);
            }
        }
    }
    $status = $failedNumbers === 0 ? 'sent' : ($failedNumbers === $total ? 'failed' : 'sent');
    $now = $failedNumbers === $total ? '' : date('Y-m-d H:i:s');
    $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ?, sent_at = NULLIF(?, \'\') WHERE id = ?');
    $stmt->bind_param('ssi', $status, $now, $campaignId);
    $stmt->execute();
    $stmt->close();
    $balance = billing_unit_balances($conn, $clientId);
    if ($failedNumbers === $total) {
        return sms_result(false, 'The message could not be sent. Credits for those numbers were returned.', array(
            'balance' => (int) $balance['sms'],
            'campaign_id' => $campaignId,
            'count' => $total
        ));
    }
    $message = $failedNumbers === 0
        ? number_format($total) . ' SMS sent.'
        : 'Some numbers could not be sent. Credits for those numbers were returned.';
    return sms_result(true, '', array(
        'message' => $message,
        'count' => $total - $failedNumbers,
        'failed' => $failedNumbers,
        'credits' => $creditsSent,
        'balance' => (int) $balance['sms'],
        'campaign_id' => $campaignId
    ));
}

function sms_send($conn, $clientId, $job)
{
    $clientId = (int) $clientId;
    $source = (isset($job['source']) && $job['source'] === 'api') ? 'api' : 'dashboard';
    $gate = sms_client_gate($conn, $clientId, false);
    if ($gate !== '') {
        return sms_result(false, $gate);
    }
    $text = billing_plain_block(isset($job['text']) ? $job['text'] : '', 1000);
    if ($text === '') {
        return sms_result(false, 'Write the message.');
    }
    $blocked = sms_prohibited_notice($text);
    if ($blocked !== '') {
        return sms_result(false, $blocked);
    }
    $parsed = sms_collect_contacts(isset($job['numbers']) ? $job['numbers'] : '');
    if (!$parsed['ok']) {
        return sms_result(false, $parsed['error']);
    }
    $rendered = sms_render_messages($text, $parsed['contacts']);
    if (!$rendered['ok']) {
        return sms_result(false, $rendered['error']);
    }
    $sender = sms_resolve_sender($conn, $clientId, isset($job['sender']) ? $job['sender'] : '');
    if ($sender['error'] !== '') {
        return sms_result(false, $sender['error']);
    }
    $audience = isset($job['audience']) ? (string) $job['audience'] : '';
    $purpose = isset($job['purpose']) ? (string) $job['purpose'] : '';
    if ($source === 'dashboard') {
        $audiences = billing_audiences();
        $purposes = billing_purposes();
        if (!isset($audiences[$audience]) || !isset($purposes[$purpose])) {
            return sms_result(false, 'Choose who it is for and why it is being sent.');
        }
    }
    $name = billing_plain_line(isset($job['name']) ? $job['name'] : '', 120);
    if ($name === '') {
        $name = 'SMS ' . date('Y-m-d H:i');
    }
    $schedule = sms_parse_schedule(isset($job['scheduled_at']) ? $job['scheduled_at'] : '');
    if (!$schedule['ok']) {
        return sms_result(false, $schedule['error'] !== '' ? $schedule['error'] : 'That send time is not valid.');
    }
    $when = $schedule['stored'];
    $future = $schedule['future'];
    $numbers = array();
    foreach ($rendered['messages'] as $renderedMessage) {
        $numbers[] = $renderedMessage['number'];
    }
    $parts = sms_message_parts($text);
    $credits = (int) $rendered['credits'];
    $identityBlock = sms_unverified_block($conn, $clientId, $credits);
    if ($identityBlock !== '') {
        return sms_result(false, $identityBlock);
    }
    $list = sms_store_contacts($parsed['contacts']);
    if ($future) {
        if (!sms_take_credits($conn, $clientId, $credits)) {
            return sms_result(false, 'There are not enough SMS credits for this message.');
        }
        $campaignId = sms_insert_campaign($conn, $clientId, $name, $text, $sender['sender'], count($numbers), 'scheduled', $when, $audience, $purpose, $list);
        if ($campaignId < 1) {
            billing_add_units($conn, $clientId, 'sms', $credits);
            return sms_result(false, 'That SMS could not be scheduled.');
        }
        sms_insert_rendered($conn, $clientId, $campaignId, 0, $source, $sender['sender'], $rendered['messages']);
        $balances = billing_unit_balances($conn, $clientId);
        return sms_result(true, '', array(
            'message' => 'Scheduled for ' . $schedule['label'] . ' Nepal time. ' . number_format($credits) . ' credits are held until it sends. Cancel returns them.',
            'count' => count($numbers),
            'credits' => $credits,
            'balance' => (int) $balances['sms'],
            'campaign_id' => $campaignId
        ));
    }
    $balances = billing_unit_balances($conn, $clientId);
    if ((int) $balances['sms'] < $credits) {
        return sms_result(false, 'There are not enough SMS credits for this message.');
    }
    $tokenId = (int) (isset($job['token_id']) ? $job['token_id'] : 0);
    $campaignId = sms_insert_campaign($conn, $clientId, $name, $text, $sender['sender'], count($numbers), 'sending', '', $audience, $purpose, $list);
    $messageIds = sms_insert_rendered($conn, $clientId, $campaignId, $tokenId, $source, $sender['sender'], $rendered['messages']);
    if (!sms_take_credits($conn, $clientId, $credits)) {
        sms_mark_messages($conn, $messageIds, 'failed', 'credits');
        $failed = 'failed';
        $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $failed, $campaignId);
        $stmt->execute();
        $stmt->close();
        return sms_result(false, 'There are not enough SMS credits for this message.');
    }
    return sms_deliver_campaign($conn, $campaignId);
}

function sms_cancel_scheduled($conn, $clientId, $campaignId)
{
    $clientId = (int) $clientId;
    $campaignId = (int) $campaignId;
    $draft = 'draft';
    $scheduled = 'scheduled';
    $channel = 'sms';
    $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ?, scheduled_at = NULL WHERE id = ? AND client_id = ? AND status = ? AND channel = ?');
    $stmt->bind_param('siiss', $draft, $campaignId, $clientId, $scheduled, $channel);
    $stmt->execute();
    $changed = (int) $conn->affected_rows > 0;
    $stmt->close();
    if (!$changed) {
        return '';
    }
    $queued = 'queued';
    $rows = $conn->prepare('SELECT id, parts FROM sms_messages WHERE campaign_id = ? AND client_id = ? AND status = ?');
    $rows->bind_param('iis', $campaignId, $clientId, $queued);
    $rows->execute();
    $held = db_fetch_all($rows);
    $rows->close();
    if (!$held) {
        return 'released';
    }
    $ids = array();
    $credits = 0;
    foreach ($held as $heldRow) {
        $ids[] = (int) $heldRow['id'];
        $credits += (int) $heldRow['parts'];
    }
    sms_mark_messages($conn, $ids, 'failed', 'cancelled');
    if ($credits > 0) {
        billing_add_units($conn, $clientId, 'sms', $credits);
    }
    return 'refunded';
}

function sms_run_queue($conn, $limit)
{
    $limit = (int) $limit;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 20) {
        $limit = 20;
    }
    $now = date('Y-m-d H:i:s');
    $channel = 'sms';
    $scheduled = 'scheduled';
    $stmt = $conn->prepare('SELECT id FROM sms_campaigns WHERE channel = ? AND status = ? AND scheduled_at IS NOT NULL AND scheduled_at <= ? ORDER BY scheduled_at ASC LIMIT ' . $limit);
    $stmt->bind_param('sss', $channel, $scheduled, $now);
    $stmt->execute();
    $due = db_fetch_all($stmt);
    $stmt->close();
    $sending = 'sending';
    $hasUpdated = false;
    try {
        $campaignColumns = array_flip(billing_table_columns($conn, 'sms_campaigns'));
        $hasUpdated = isset($campaignColumns['updated_at']);
    } catch (Throwable $exception) {
        $hasUpdated = false;
    }
    $claimSql = $hasUpdated
        ? 'UPDATE sms_campaigns SET status = ?, updated_at = ? WHERE id = ? AND status = ?'
        : 'UPDATE sms_campaigns SET status = ? WHERE id = ? AND status = ?';
    $claim = $conn->prepare($claimSql);
    if (!$claim) {
        return;
    }
    foreach ($due as $row) {
        $id = (int) $row['id'];
        if ($hasUpdated) {
            $claimedAt = date('Y-m-d H:i:s');
            $claim->bind_param('ssis', $sending, $claimedAt, $id, $scheduled);
        } else {
            $claim->bind_param('sis', $sending, $id, $scheduled);
        }
        $claim->execute();
        if ((int) $conn->affected_rows > 0) {
            sms_deliver_campaign($conn, $id);
        }
    }
    $claim->close();
    if (!$hasUpdated) {
        return;
    }
    try {
        $cutoff = date('Y-m-d H:i:s', time() - 600);
        $stuck = $conn->prepare('SELECT c.id, c.scheduled_at FROM sms_campaigns c WHERE c.channel = ? AND c.status = ? AND c.updated_at <= ? AND NOT EXISTS (SELECT 1 FROM sms_messages m WHERE m.campaign_id = c.id) LIMIT 20');
        if ($stuck) {
            $stuck->bind_param('sss', $channel, $sending, $cutoff);
            $stuck->execute();
            $abandoned = db_fetch_all($stuck);
            $stuck->close();
            $back = 'scheduled';
            $failed = 'failed';
            $restore = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND status = ?');
            if ($restore) {
                foreach ($abandoned as $abandonedRow) {
                    $abandonedId = (int) $abandonedRow['id'];
                    $next = trim((string) $abandonedRow['scheduled_at']) !== '' ? $back : $failed;
                    $restore->bind_param('sis', $next, $abandonedId, $sending);
                    $restore->execute();
                }
                $restore->close();
            }
        }
        $retry = $conn->prepare('SELECT DISTINCT c.id FROM sms_campaigns c JOIN sms_messages m ON m.campaign_id = c.id WHERE c.channel = ? AND c.status = ? AND m.status = ? AND c.updated_at <= ? LIMIT 5');
        if ($retry) {
            $queued = 'queued';
            $retry->bind_param('ssss', $channel, $sending, $queued, $cutoff);
            $retry->execute();
            $resume = db_fetch_all($retry);
            $retry->close();
            foreach ($resume as $resumeRow) {
                sms_deliver_campaign($conn, (int) $resumeRow['id']);
            }
        }
    } catch (Throwable $exception) {
        error_log('A stuck SMS could not be resumed.');
    }
}

function sms_api_origin()
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
    if (!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) {
        return '';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
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
    foreach (array($_POST, $_GET) as $bag) {
        foreach ($bag as $key => $value) {
            if (!isset($input[$key])) {
                $input[$key] = $value;
            }
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        sms_api_json(405, array('error' => true, 'message' => 'Use POST or GET.', 'data' => array()));
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

function voice_place_job($conn, $campaignId)
{
    $campaignId = (int) $campaignId;
    $stmt = $conn->prepare('SELECT id, client_id, channel, status, recipients_count, message_content FROM sms_campaigns WHERE id = ?');
    $stmt->bind_param('i', $campaignId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || (string) $row['channel'] !== 'voice' || ((string) $row['status'] !== 'draft' && (string) $row['status'] !== 'scheduled')) {
        return 'That voice job cannot be marked placed.';
    }
    $count = (int) $row['recipients_count'];
    $clientId = (int) $row['client_id'];
    if ($count < 1 || $clientId < 1) {
        return 'This voice job has no numbers.';
    }
    $blocked = sms_prohibited_notice((string) $row['message_content']);
    if ($blocked !== '') {
        return $blocked;
    }
    $kind = 'voice_calls';
    $debit = $conn->prepare('UPDATE client_units SET balance = balance - ? WHERE client_id = ? AND unit_kind = ? AND balance >= ?');
    $debit->bind_param('iisi', $count, $clientId, $kind, $count);
    $debit->execute();
    $taken = billing_affected($conn) === 1;
    $debit->close();
    if (!$taken) {
        return 'This account does not have enough voice credits for these numbers.';
    }
    $status = 'sent';
    $mark = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND channel = \'voice\' AND status IN (\'draft\', \'scheduled\')');
    $mark->bind_param('si', $status, $campaignId);
    $mark->execute();
    $marked = billing_affected($conn) === 1;
    $mark->close();
    if (!$marked) {
        billing_add_units($conn, $clientId, $kind, $count);
        return 'That voice job could not be marked placed.';
    }
    return '';
}

function voice_cancel_job($conn, $clientId, $campaignId)
{
    $clientId = (int) $clientId;
    $campaignId = (int) $campaignId;
    $status = 'cancelled';
    $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND client_id = ? AND channel = \'voice\' AND status IN (\'draft\', \'scheduled\')');
    $stmt->bind_param('sii', $status, $campaignId, $clientId);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function voice_reserved_count($conn, $clientId)
{
    $clientId = (int) $clientId;
    $channel = 'voice';
    $draft = 'draft';
    $scheduled = 'scheduled';
    $stmt = $conn->prepare('SELECT COALESCE(SUM(recipients_count), 0) AS held FROM sms_campaigns WHERE client_id = ? AND channel = ? AND status IN (?, ?)');
    $stmt->bind_param('isss', $clientId, $channel, $draft, $scheduled);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row ? (int) $row['held'] : 0;
}

function voice_return_job($conn, $campaignId)
{
    $campaignId = (int) $campaignId;
    $stmt = $conn->prepare('SELECT id, client_id, channel, status, recipients_count, scheduled_at FROM sms_campaigns WHERE id = ?');
    $stmt->bind_param('i', $campaignId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || (string) $row['channel'] !== 'voice' || (string) $row['status'] !== 'sent') {
        return 'Those voice credits cannot be returned.';
    }
    $count = (int) $row['recipients_count'];
    $clientId = (int) $row['client_id'];
    if ($count < 1 || $clientId < 1) {
        return 'Those voice credits cannot be returned.';
    }
    $draft = trim((string) $row['scheduled_at']) !== '' ? 'scheduled' : 'draft';
    $sent = 'sent';
    $channel = 'voice';
    $mark = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND channel = ? AND status = ?');
    $mark->bind_param('siss', $draft, $campaignId, $channel, $sent);
    $mark->execute();
    $marked = billing_affected($conn) === 1;
    $mark->close();
    if (!$marked) {
        return 'Those voice credits cannot be returned.';
    }
    billing_add_units($conn, $clientId, 'voice_calls', $count);
    return '';
}


