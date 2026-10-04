<?php
/**
 * Billing: Shared helpers: database utilities, settings, text cleaning, form guards.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

function billing_money($amount)
{
    return number_format((float) $amount, 2, '.', '');
}

function billing_money_label($amount)
{
    $value = (float) $amount;
    $formatted = abs($value - round($value)) < 0.001
        ? number_format($value, 0)
        : number_format($value, 2);
    return 'NPR ' . $formatted;
}

function billing_affected($conn)
{
    return isset($conn->affected_rows) ? (int) $conn->affected_rows : 0;
}

function billing_exec($conn, $sql)
{
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Billing query failed.');
    }
    return $result;
}

function billing_ensure_index($conn, $table, $name, $columns)
{
    $tables = array(
        'sms_messages' => true,
        'sms_campaigns' => true,
        'sms_api_hits' => true,
        'client_services' => true,
        'wallet_entries' => true,
        'domain_requests' => true,
        'client_kyc' => true,
        'login_attempts' => true,
        'client_users' => true,
        'inquiries' => true
    );
    if (!isset($tables[$table]) || !preg_match('/^[a-z0-9_]+$/', $name)) {
        return;
    }
    $list = array();
    foreach ($columns as $column) {
        if (!preg_match('/^[a-z0-9_]+$/', (string) $column)) {
            return;
        }
        $list[] = $column;
    }
    if (!$list) {
        return;
    }
    $columnSql = implode(', ', $list);
    try {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'CREATE INDEX IF NOT EXISTS ' . $name . ' ON ' . $table . ' (' . $columnSql . ')');
            return;
        }
        $found = $conn->query('SHOW INDEX FROM `' . $table . "` WHERE Key_name = '" . $name . "'");
        if ($found && $found->fetch_assoc()) {
            return;
        }
        billing_exec($conn, 'CREATE INDEX ' . $name . ' ON ' . $table . ' (' . $columnSql . ')');
    } catch (Throwable $exception) {
        error_log('Database index could not be added.');
    }
}

function billing_ensure_indexes($conn)
{
    billing_ensure_index($conn, 'sms_messages', 'idx_sms_msg_client_status', array('client_id', 'status', 'created_at'));
    billing_ensure_index($conn, 'sms_messages', 'idx_sms_msg_sent_day', array('client_id', 'status', 'sent_at'));
    billing_ensure_index($conn, 'sms_campaigns', 'idx_sms_campaign_due', array('channel', 'status', 'scheduled_at'));
    billing_ensure_index($conn, 'sms_campaigns', 'idx_sms_campaign_client_status', array('client_id', 'channel', 'status'));
    billing_ensure_index($conn, 'sms_campaigns', 'idx_sms_campaign_created', array('created_at'));
    billing_ensure_index($conn, 'client_services', 'idx_service_client_status', array('client_id', 'status'));
    billing_ensure_index($conn, 'client_services', 'idx_service_renew', array('auto_renew', 'status', 'next_renewal'));
    billing_ensure_index($conn, 'wallet_entries', 'idx_wallet_kind_status', array('kind', 'status'));
    billing_ensure_index($conn, 'domain_requests', 'idx_domain_open', array('domain_name', 'status'));
    billing_ensure_index($conn, 'client_kyc', 'idx_kyc_status', array('status'));
    billing_ensure_index($conn, 'client_users', 'idx_client_created', array('created_at'));
    billing_ensure_index($conn, 'login_attempts', 'idx_attempt_lookup', array('scope', 'ip', 'attempted_at'));
    billing_ensure_index($conn, 'sms_api_hits', 'idx_sms_hit_token', array('token_id', 'created_at'));
}

function billing_table_columns($conn, $table)
{
    $allowed = array(
        'client_services' => true,
        'sms_campaigns' => true,
        'client_users' => true,
        'domain_requests' => true,
        'support_tickets' => true,
        'client_kyc' => true,
        'sms_credit_notes' => true,
        'sms_number_lists' => true
    );
    if (!isset($allowed[$table])) {
        throw new InvalidArgumentException('Unknown table.');
    }

    $names = array();
    if (DB_DRIVER === 'sqlite') {
        $result = billing_exec($conn, 'PRAGMA table_info(' . $table . ')');
        while ($row = $result->fetch_assoc()) {
            $names[] = (string) $row['name'];
        }
        return $names;
    }

    $result = billing_exec($conn, 'SHOW COLUMNS FROM `' . $table . '`');
    while ($row = $result->fetch_assoc()) {
        $names[] = (string) $row['Field'];
    }
    return $names;
}

function billing_setting($conn, $key)
{
    $stmt = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row ? (string) $row['setting_value'] : '';
}

function billing_set_setting($conn, $key, $value)
{
    $current = billing_setting($conn, $key);
    if ($current === '' && $current !== '0') {
        $check = $conn->prepare('SELECT id FROM site_settings WHERE setting_key = ?');
        $check->bind_param('s', $key);
        $check->execute();
        $exists = db_fetch_assoc($check);
        $check->close();
        if ($exists) {
            $stmt = $conn->prepare('UPDATE site_settings SET setting_value = ? WHERE setting_key = ?');
        } else {
            $stmt = $conn->prepare('INSERT INTO site_settings (setting_value, setting_key) VALUES (?, ?)');
        }
    } else {
        $stmt = $conn->prepare('UPDATE site_settings SET setting_value = ? WHERE setting_key = ?');
    }
    $stmt->bind_param('ss', $value, $key);
    $stmt->execute();
    $stmt->close();
}

if (!function_exists('site_escape')) {
    function site_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

function billing_training_topics()
{
    return array(
        'safe-use' => 'Safe use of phones, email, and online tools',
        'misuse' => 'Risk from sharing passwords, OTPs, or links',
        'attacks' => 'Cyber attacks: fake messages, fraud calls, and payment traps',
        'response' => 'What to do when an account or payment looks compromised'
    );
}

function billing_use_declaration()
{
    return 'म घोषणा गर्दछु कि मैले यो SMS वा भ्वाइस कल सेवा नेपाल सरकारले वा प्रचलित कानुनले निषेध गरेको कुनै काममा, तथा झुटा वा ठगी गर्ने गरी प्रयोग गर्ने छैन। त्यसो गरेमा वा त्यस्तो प्रयोग प्रमाणित भएमा म प्रचलित कानुनबमोजिम भोग्न तयार छु।';
}

function billing_form_guard_token($key)
{
    $key = (string) $key;
    if (!isset($_SESSION['form_guard']) || !is_array($_SESSION['form_guard'])) {
        $_SESSION['form_guard'] = array();
    }
    $current = isset($_SESSION['form_guard'][$key]) ? $_SESSION['form_guard'][$key] : null;
    if (is_array($current) && isset($current['token'], $current['at']) && (time() - (int) $current['at']) < 7200) {
        return (string) $current['token'];
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['form_guard'][$key] = array('token' => $token, 'at' => time());
    return $token;
}

function billing_form_guard_check($key, $post, $askMath = true)
{
    $honeypot = isset($post['fax_number']) ? trim((string) $post['fax_number']) : '';
    if ($honeypot !== '') {
        return 'The form could not be submitted. Reload the page and try again.';
    }
    if ($askMath) {
        $mathError = auth_math_verify($key, isset($post['human_check']) ? $post['human_check'] : '');
        if ($mathError !== '') {
            return $mathError;
        }
    }
    $token = isset($post['form_guard']) ? (string) $post['form_guard'] : '';
    $stored = (isset($_SESSION['form_guard'][$key]) && is_array($_SESSION['form_guard'][$key])) ? $_SESSION['form_guard'][$key] : null;
    if (!is_array($stored) || !isset($stored['token'], $stored['at']) || !hash_equals((string) $stored['token'], $token)) {
        return 'The form expired. Reload the page and try again.';
    }
    $age = time() - (int) $stored['at'];
    if ($age < 3) {
        return 'Please wait a moment and submit the form again.';
    }
    if ($age > 7200) {
        return 'The form expired. Reload the page and try again.';
    }
    return '';
}

function billing_form_guard_clear($key)
{
    if (isset($_SESSION['form_guard'][$key])) {
        unset($_SESSION['form_guard'][$key]);
    }
    auth_math_clear($key);
}

function client_safe_next($value)
{
    $value = (string) $value;
    $parts = parse_url($value);
    if (!is_array($parts) || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || !isset($parts['path'])) {
        return 'index.php';
    }
    $page = $parts['path'];
    $pages = array('shop.php', 'checkout.php', 'wallet.php', 'services.php', 'index.php', 'campaigns.php', 'sms-portal.php', 'sms-logs.php', 'sms-api.php', 'support.php', 'profile.php', 'kyc.php', 'domains.php');
    if (!in_array($page, $pages, true)) {
        return 'index.php';
    }
    $query = array();
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $keep = array();
    if ($page === 'shop.php' && isset($query['service']) && preg_match('/^[A-Za-z0-9_-]+$/', (string) $query['service'])) {
        $keep['service'] = (string) $query['service'];
    }
    if ($page === 'checkout.php' && isset($query['plan']) && preg_match('/^[A-Za-z0-9_-]+$/', (string) $query['plan'])) {
        $keep['plan'] = (string) $query['plan'];
    }
    if ($page === 'wallet.php' && isset($query['amount']) && preg_match('/^[0-9]{1,7}$/', (string) $query['amount'])) {
        $keep['amount'] = (string) $query['amount'];
        if (isset($query['for']) && (string) $query['for'] === 'domain') {
            $keep['for'] = 'domain';
        }
    }
    if ($page === 'sms-portal.php') {
        foreach (array('send', 'retry', 'reuse') as $key) {
            if (isset($query[$key]) && preg_match('/^[0-9]{1,9}$/', (string) $query[$key])) {
                $keep[$key] = (string) $query[$key];
                break;
            }
        }
    }
    if ($page === 'sms-logs.php') {
        $statuses = array('sent', 'failed', 'queued', 'sending', 'scheduled');
        if (isset($query['status']) && in_array((string) $query['status'], $statuses, true)) {
            $keep['status'] = (string) $query['status'];
        }
        if (isset($query['q']) && preg_match('/^[0-9]{1,10}$/', (string) $query['q'])) {
            $keep['q'] = (string) $query['q'];
        }
        if (isset($query['send']) && preg_match('/^[0-9]{1,9}$/', (string) $query['send'])) {
            $keep['send'] = (string) $query['send'];
        }
        if (isset($query['page']) && preg_match('/^[0-9]{1,2}$/', (string) $query['page'])) {
            $keep['page'] = (string) $query['page'];
        }
    }
    if (!$keep) {
        return $page;
    }
    return $page . '?' . http_build_query($keep);
}

function billing_vat_bill($amount)
{
    $net = round((float) $amount, 2);
    $vat = round($net * 0.13, 2);
    return array(
        'net' => $net,
        'vat' => $vat,
        'total' => round($net + $vat, 2)
    );
}

function billing_valid_domain($value)
{
    return (bool) preg_match('/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i', $value);
}

function billing_column_names($conn, $table)
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return array();
    }
    $names = array();
    if (DB_DRIVER === 'sqlite') {
        $result = billing_exec($conn, 'PRAGMA table_info(' . $table . ')');
        while ($row = $result->fetch_assoc()) {
            $names[] = (string) $row['name'];
        }
        return $names;
    }
    $result = billing_exec($conn, 'SHOW COLUMNS FROM ' . $table);
    while ($row = $result->fetch_assoc()) {
        $names[] = (string) $row['Field'];
    }
    return $names;
}

function billing_plain_line($value, $max)
{
    $value = trim(preg_replace('/\s+/', ' ', (string) $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function billing_plain_block($value, $max)
{
    $value = trim(str_replace("\r", '', (string) $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function billing_parse_numbers($raw)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $numbers = array();
    if (!is_array($lines)) {
        return array('ok' => false, 'error' => 'Paste the phone numbers, one per line.', 'numbers' => array());
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $digits = function_exists('auth_mobile_number') ? auth_mobile_number($line) : '';
        if ($digits === '') {
            return array('ok' => false, 'error' => 'Each line should be one 10-digit mobile number.', 'numbers' => array());
        }
        $numbers[$digits] = $digits;
    }
    return array('ok' => true, 'error' => '', 'numbers' => array_values($numbers));
}

function billing_posted($post, $key)
{
    return isset($post[$key]) && is_string($post[$key]) ? $post[$key] : '';
}
