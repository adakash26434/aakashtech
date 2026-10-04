<?php
/**
 * SMS: Tables, templates, saved number lists and shared helpers.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
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
        sms_delivery_columns($conn);
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
        numbers_text MEDIUMTEXT NOT NULL,
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
    sms_delivery_columns($conn);
    sms_list_columns($conn);
    sms_widen_list_columns($conn);
}

function sms_widen_list_columns($conn)
{
    if (DB_DRIVER === 'sqlite' || billing_setting($conn, 'sms_lists_widened') === '1') {
        return;
    }
    $targets = array(
        array('sms_campaigns', 'recipients_list', 'MEDIUMTEXT'),
        array('sms_number_lists', 'numbers_text', 'MEDIUMTEXT NOT NULL')
    );
    $ok = true;
    foreach ($targets as $target) {
        try {
            $stmt = $conn->prepare('SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            if (!$stmt) {
                $ok = false;
                continue;
            }
            $stmt->bind_param('ss', $target[0], $target[1]);
            $stmt->execute();
            $row = db_fetch_assoc($stmt);
            $stmt->close();
            if (!$row) {
                $ok = false;
                continue;
            }
            $type = strtolower((string) $row['DATA_TYPE']);
            if ($type !== 'mediumtext' && $type !== 'longtext') {
                billing_exec($conn, 'ALTER TABLE ' . $target[0] . ' MODIFY ' . $target[1] . ' ' . $target[2]);
            }
        } catch (Throwable $exception) {
            $ok = false;
        }
    }
    if ($ok) {
        billing_set_setting($conn, 'sms_lists_widened', '1');
    }
}

/**
 * Delivery report columns. status stays 'sent' / 'failed' (what the provider accepted);
 * delivery records what the phone network later reported: '', 'delivered' or 'failed'.
 */
function sms_delivery_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'sms_messages'));
    if (!$present || isset($present['delivery'])) {
        return;
    }
    $text = 'VARCHAR(12) DEFAULT \'\'';
    $ref = 'VARCHAR(64) DEFAULT \'\'';
    $when = DB_DRIVER === 'sqlite' ? 'TEXT DEFAULT NULL' : 'DATETIME DEFAULT NULL';
    billing_exec($conn, 'ALTER TABLE sms_messages ADD COLUMN delivery ' . $text);
    billing_exec($conn, 'ALTER TABLE sms_messages ADD COLUMN delivery_at ' . $when);
    billing_exec($conn, 'ALTER TABLE sms_messages ADD COLUMN provider_ref ' . $ref);
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

function sms_send_limit()
{
    return 50000;
}

function sms_instant_limit()
{
    return 1000;
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
