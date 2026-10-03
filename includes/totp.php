<?php

function totp_issuer()
{
    $name = defined('SITE_NAME') ? (string) SITE_NAME : 'Aakash Technologies';
    $name = trim(str_replace(array(':', "\r", "\n"), ' ', $name));
    if ($name === '') {
        $name = 'Aakash Technologies';
    }
    return substr($name, 0, 40);
}

function totp_columns($conn, $table)
{
    if ($table !== 'admin_users' && $table !== 'client_users') {
        return array();
    }
    try {
        $names = array();
        if (DB_DRIVER === 'sqlite') {
            $result = $conn->query('PRAGMA table_info(' . $table . ')');
            if (!$result) {
                return array();
            }
            while ($row = $result->fetch_assoc()) {
                $names[] = (string) $row['name'];
            }
            return $names;
        }
        $result = $conn->query('SHOW COLUMNS FROM `' . $table . '`');
        if (!$result) {
            return array();
        }
        while ($row = $result->fetch_assoc()) {
            $names[] = (string) $row['Field'];
        }
        return $names;
    } catch (Throwable $exception) {
        return array();
    }
}

function totp_ensure($conn)
{
    try {
        if (DB_DRIVER === 'sqlite') {
            $conn->query("CREATE TABLE IF NOT EXISTS auth_recovery_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                account_kind TEXT NOT NULL,
                account_id INTEGER NOT NULL,
                code_hash TEXT NOT NULL,
                used_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )");
            $conn->query('CREATE INDEX IF NOT EXISTS idx_recovery_account ON auth_recovery_codes (account_kind, account_id)');
            $definitions = array(
                'totp_secret' => "TEXT DEFAULT ''",
                'totp_last_step' => 'INTEGER DEFAULT 0'
            );
        } else {
            $conn->query("CREATE TABLE IF NOT EXISTS auth_recovery_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                account_kind VARCHAR(10) NOT NULL,
                account_id INT NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                used_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_recovery_account (account_kind, account_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $definitions = array(
                'totp_secret' => "VARCHAR(64) NOT NULL DEFAULT ''",
                'totp_last_step' => 'INT NOT NULL DEFAULT 0'
            );
        }
        foreach (array('admin_users', 'client_users') as $table) {
            $present = array_flip(totp_columns($conn, $table));
            if (!$present) {
                continue;
            }
            foreach ($definitions as $name => $definition) {
                if (isset($present[$name])) {
                    continue;
                }
                $conn->query('ALTER TABLE ' . $table . ' ADD COLUMN ' . $name . ' ' . $definition);
            }
        }
    } catch (Throwable $exception) {
        error_log('Authenticator storage could not be prepared.');
    }
}

function totp_base32_encode($data)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    $length = strlen($data);
    for ($i = 0; $i < $length; $i++) {
        $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }
    $secret = '';
    $chunks = str_split($binary, 5);
    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $secret .= $alphabet[bindec($chunk)];
    }
    return $secret;
}

function totp_base32_decode($secret)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string) $secret));
    if ($secret === '') {
        return '';
    }
    $binary = '';
    $length = strlen($secret);
    for ($i = 0; $i < $length; $i++) {
        $pos = strpos($alphabet, $secret[$i]);
        if ($pos === false) {
            return '';
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    $chunks = str_split($binary, 8);
    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 8) {
            break;
        }
        $bytes .= chr(bindec($chunk));
    }
    return $bytes;
}

function totp_random_secret()
{
    return totp_base32_encode(random_bytes(20));
}

function totp_key_groups($secret)
{
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string) $secret));
    return trim(chunk_split($secret, 4, ' '));
}

function totp_uri($issuer, $email, $secret)
{
    $issuer = totp_issuer_clean($issuer);
    $email = trim((string) $email);
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string) $secret));
    $label = rawurlencode($issuer) . ':' . rawurlencode($email);
    return 'otpauth://totp/' . $label
        . '?secret=' . rawurlencode($secret)
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}

function totp_issuer_clean($issuer)
{
    $issuer = trim(str_replace(array(':', "\r", "\n"), ' ', (string) $issuer));
    if ($issuer === '') {
        $issuer = 'Aakash Technologies';
    }
    return substr($issuer, 0, 40);
}

function totp_at($secret, $step)
{
    $key = totp_base32_decode($secret);
    if ($key === '' || strlen($key) < 10) {
        return '';
    }
    $step = (int) $step;
    if ($step < 0) {
        return '';
    }
    $counter = pack('N2', ($step >> 32) & 0xffffffff, $step & 0xffffffff);
    $hash = hash_hmac('sha1', $counter, $key, true);
    if (!is_string($hash) || strlen($hash) < 20) {
        return '';
    }
    $offset = ord($hash[19]) & 0x0f;
    $binary = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    );
    return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
}

function totp_match($secret, $code, $lastStep)
{
    $code = preg_replace('/\D/', '', (string) $code);
    $result = array('ok' => false, 'replay' => false, 'step' => 0);
    if (!preg_match('/^[0-9]{6}$/', $code)) {
        return $result;
    }
    $now = (int) floor(time() / 30);
    $lastStep = (int) $lastStep;
    for ($step = $now - 1; $step <= $now + 1; $step++) {
        if ($step < 0) {
            continue;
        }
        $expected = totp_at($secret, $step);
        if ($expected === '' || !hash_equals($expected, $code)) {
            continue;
        }
        if ($step <= $lastStep) {
            $result['replay'] = true;
            continue;
        }
        return array('ok' => true, 'replay' => false, 'step' => $step);
    }
    return $result;
}

function totp_account($conn, $kind, $id)
{
    totp_ensure($conn);
    $id = (int) $id;
    if ($id < 1 || ($kind !== 'admin' && $kind !== 'client')) {
        return null;
    }
    try {
        if ($kind === 'admin') {
            $stmt = $conn->prepare('SELECT id, name, email, password, role, is_active, totp_secret, totp_last_step FROM admin_users WHERE id = ? LIMIT 1');
        } else {
            $stmt = $conn->prepare('SELECT id, name, email, password, status, totp_secret, totp_last_step FROM client_users WHERE id = ? LIMIT 1');
        }
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        return $row ? $row : null;
    } catch (Throwable $exception) {
        error_log('Authenticator account could not be read.');
        return null;
    }
}

function totp_account_open($account, $kind)
{
    if (!is_array($account)) {
        return false;
    }
    if ($kind === 'admin') {
        return (int) $account['is_active'] === 1;
    }
    return isset($account['status']) && $account['status'] === 'active';
}

function totp_store_secret($conn, $kind, $id, $secret, $step)
{
    if ($kind !== 'admin' && $kind !== 'client') {
        return;
    }
    $table = $kind === 'admin' ? 'admin_users' : 'client_users';
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string) $secret));
    $step = (int) $step;
    $id = (int) $id;
    $stmt = $conn->prepare('UPDATE ' . $table . ' SET totp_secret = ?, totp_last_step = ? WHERE id = ?');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('sii', $secret, $step, $id);
    $stmt->execute();
    $stmt->close();
}

function totp_set_step($conn, $kind, $id, $step)
{
    if ($kind !== 'admin' && $kind !== 'client') {
        return;
    }
    $table = $kind === 'admin' ? 'admin_users' : 'client_users';
    $step = (int) $step;
    $id = (int) $id;
    $stmt = $conn->prepare('UPDATE ' . $table . ' SET totp_last_step = ? WHERE id = ?');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('ii', $step, $id);
    $stmt->execute();
    $stmt->close();
}

function totp_clear($conn, $kind, $id)
{
    totp_ensure($conn);
    if ($kind !== 'admin' && $kind !== 'client') {
        return;
    }
    $table = $kind === 'admin' ? 'admin_users' : 'client_users';
    $empty = '';
    $step = 0;
    $id = (int) $id;
    $stmt = $conn->prepare('UPDATE ' . $table . ' SET totp_secret = ?, totp_last_step = ? WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('sii', $empty, $step, $id);
        $stmt->execute();
        $stmt->close();
    }
    $delete = $conn->prepare('DELETE FROM auth_recovery_codes WHERE account_kind = ? AND account_id = ?');
    if ($delete) {
        $delete->bind_param('si', $kind, $id);
        $delete->execute();
        $delete->close();
    }
}

function totp_issue_recovery($conn, $kind, $id)
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $plain = array();
    $id = (int) $id;
    $delete = $conn->prepare('DELETE FROM auth_recovery_codes WHERE account_kind = ? AND account_id = ?');
    if ($delete) {
        $delete->bind_param('si', $kind, $id);
        $delete->execute();
        $delete->close();
    }
    $insert = $conn->prepare('INSERT INTO auth_recovery_codes (account_kind, account_id, code_hash) VALUES (?, ?, ?)');
    if (!$insert) {
        return array();
    }
    for ($n = 0; $n < 8; $n++) {
        $raw = '';
        for ($i = 0; $i < 8; $i++) {
            $raw .= $alphabet[random_int(0, 31)];
        }
        $plain[] = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
        $hash = password_hash($raw, PASSWORD_DEFAULT);
        $insert->bind_param('sis', $kind, $id, $hash);
        $insert->execute();
    }
    $insert->close();
    return $plain;
}

function totp_use_recovery($conn, $kind, $id, $code)
{
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    if (strlen($code) !== 8 || ($kind !== 'admin' && $kind !== 'client')) {
        return false;
    }
    $id = (int) $id;
    $stmt = $conn->prepare('SELECT id, code_hash FROM auth_recovery_codes WHERE account_kind = ? AND account_id = ? AND used_at IS NULL');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('si', $kind, $id);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    foreach ($rows as $row) {
        if (!password_verify($code, (string) $row['code_hash'])) {
            continue;
        }
        $codeId = (int) $row['id'];
        $now = date('Y-m-d H:i:s');
        $update = $conn->prepare('UPDATE auth_recovery_codes SET used_at = ? WHERE id = ? AND used_at IS NULL');
        if (!$update) {
            return false;
        }
        $update->bind_param('si', $now, $codeId);
        $update->execute();
        $changed = (int) $conn->affected_rows;
        $update->close();
        return $changed > 0;
    }
    return false;
}

function totp_scope($kind)
{
    return $kind === 'admin' ? 'admin-otp' : 'client-otp';
}

function totp_gate()
{
    $gate = (isset($_SESSION['totp_gate']) && is_array($_SESSION['totp_gate'])) ? $_SESSION['totp_gate'] : null;
    if (!$gate || empty($gate['id']) || empty($gate['expires']) || (int) $gate['expires'] < time()) {
        unset($_SESSION['totp_gate']);
        return null;
    }
    if ($gate['kind'] !== 'admin' && $gate['kind'] !== 'client') {
        unset($_SESSION['totp_gate']);
        return null;
    }
    return $gate;
}

function totp_open_gate($conn, $kind, $user, $next)
{
    totp_ensure($conn);
    $id = isset($user['id']) ? (int) $user['id'] : 0;
    if ($id < 1 || ($kind !== 'admin' && $kind !== 'client')) {
        return;
    }
    $account = totp_account($conn, $kind, $id);
    $secret = '';
    $stage = 'code';
    if (!$account || trim((string) $account['totp_secret']) === '') {
        $secret = totp_random_secret();
        $stage = 'setup';
    }
    $safeNext = 'index.php';
    if ($kind === 'client') {
        $safeNext = client_safe_next(isset($next) ? $next : 'index.php');
    }
    $_SESSION['totp_gate'] = array(
        'kind' => $kind,
        'id' => $id,
        'name' => substr(isset($user['name']) ? (string) $user['name'] : '', 0, 80),
        'email' => substr(isset($user['email']) ? (string) $user['email'] : '', 0, 120),
        'admin_role' => substr(isset($user['admin_role']) ? (string) $user['admin_role'] : 'admin', 0, 20),
        'next' => $safeNext,
        'expires' => time() + 1200,
        'setup_secret' => $secret,
        'stage' => $stage,
        'recovery_plain' => array()
    );
    auth_fresh_session();
}

function totp_complete($conn)
{
    $gate = totp_gate();
    if (!$gate) {
        header('Location: login.php');
        exit;
    }
    $kind = $gate['kind'];
    $account = totp_account($conn, $kind, (int) $gate['id']);
    if (!$account || trim((string) $account['totp_secret']) === '' || !totp_account_open($account, $kind)) {
        unset($_SESSION['totp_gate']);
        header('Location: login.php');
        exit;
    }
    if ($kind === 'admin') {
        $_SESSION['admin_id'] = (int) $account['id'];
        $_SESSION['admin_name'] = $account['name'];
        $_SESSION['admin_email'] = $account['email'];
        $_SESSION['admin_role'] = $account['role'];
        try {
            $adminId = (int) $account['id'];
            $stamp = date('Y-m-d H:i:s');
            $touch = $conn->prepare('UPDATE admin_users SET last_login = ? WHERE id = ?');
            if ($touch) {
                $touch->bind_param('si', $stamp, $adminId);
                $touch->execute();
                $touch->close();
            }
        } catch (Throwable $exception) {
            error_log('Admin last login could not be saved.');
        }
        $dest = 'index.php';
    } else {
        $_SESSION['client_id'] = (int) $account['id'];
        $_SESSION['client_name'] = $account['name'];
        $_SESSION['client_email'] = $account['email'];
        $dest = client_safe_next($gate['next']);
        try {
            billing_client_login_notice($conn, (int) $account['id']);
        } catch (Throwable $exception) {
            error_log('Sign-in notice could not be sent.');
        }
    }
    if (isset($account['password'])) {
        auth_remember_password($kind, $account['password']);
    }
    unset($_SESSION['totp_gate'], $_SESSION['totp_rekey']);
    if (isset($_SESSION['client_next'])) {
        unset($_SESSION['client_next']);
    }
    auth_fresh_session();
    header('Location: ' . $dest);
    exit;
}

function totp_gate_post($conn, $postedCode, $acknowledged)
{
    $gate = totp_gate();
    if (!$gate) {
        return 'This sign-in step expired. Start again.';
    }
    $kind = $gate['kind'];
    $scope = totp_scope($kind);
    if (auth_attempt_blocked($conn, $scope, 8, 900)) {
        return 'Too many codes. Wait 15 minutes and try again.';
    }
    if ($gate['stage'] === 'codes') {
        $hasCodes = !empty($gate['recovery_plain']) && is_array($gate['recovery_plain']);
        if ($hasCodes && (string) $acknowledged !== '1') {
            return 'Save the backup codes, then tick the box.';
        }
        totp_complete($conn);
    }
    if ($gate['stage'] === 'setup') {
        $match = totp_match(isset($gate['setup_secret']) ? $gate['setup_secret'] : '', $postedCode, 0);
        if (empty($match['ok'])) {
            auth_note_attempt($conn, $scope);
            return 'That code does not match. In Google Authenticator, use the current 6-digit code. The phone time should be set automatically.';
        }
        totp_store_secret($conn, $kind, (int) $gate['id'], $gate['setup_secret'], $match['step']);
        $plain = totp_issue_recovery($conn, $kind, (int) $gate['id']);
        $_SESSION['totp_gate']['stage'] = 'codes';
        $_SESSION['totp_gate']['setup_secret'] = '';
        $_SESSION['totp_gate']['recovery_plain'] = $plain;
        auth_clear_attempts($conn, $scope);
        return '';
    }
    $account = totp_account($conn, $kind, (int) $gate['id']);
    if (!$account || trim((string) $account['totp_secret']) === '' || !totp_account_open($account, $kind)) {
        unset($_SESSION['totp_gate']);
        return 'Sign in again.';
    }
    $input = trim((string) $postedCode);
    $digits = preg_replace('/\D/', '', $input);
    if (preg_match('/^[0-9]{6}$/', $digits)) {
        $match = totp_match($account['totp_secret'], $digits, (int) $account['totp_last_step']);
        if (!empty($match['replay'])) {
            auth_note_attempt($conn, $scope);
            return 'That code was already used. Wait for the next code in the app.';
        }
        if (!empty($match['ok'])) {
            totp_set_step($conn, $kind, (int) $gate['id'], $match['step']);
            auth_clear_attempts($conn, $scope);
            totp_complete($conn);
        }
    } else {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
        if (strlen($normalized) === 8 && totp_use_recovery($conn, $kind, (int) $gate['id'], $normalized)) {
            auth_clear_attempts($conn, $scope);
            totp_complete($conn);
        }
    }
    auth_note_attempt($conn, $scope);
    return 'That code is not right. Use the 6-digit code from Google Authenticator, or one backup code.';
}

function totp_require_enrolled($kind)
{
    global $conn;
    if (!$conn || ($kind !== 'admin' && $kind !== 'client')) {
        return;
    }
    $id = $kind === 'admin' ? (int) (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0) : (int) (isset($_SESSION['client_id']) ? $_SESSION['client_id'] : 0);
    if ($id < 1) {
        return;
    }
    try {
        $account = totp_account($conn, $kind, $id);
    } catch (Throwable $exception) {
        error_log('Authenticator status could not be read.');
        return;
    }
    if (!$account || trim((string) $account['totp_secret']) !== '') {
        return;
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
    $next = 'index.php';
    if ($kind === 'client') {
        $page = basename($script);
        $query = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
        $next = client_safe_next($query === '' ? $page : $page . '?' . $query);
    }
    $user = array(
        'id' => $id,
        'name' => $account['name'],
        'email' => $account['email'],
        'admin_role' => isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'admin'
    );
    auth_drop_role($kind);
    totp_open_gate($conn, $kind, $user, $next);
    $prefix = '';
    if ($kind === 'admin' && strpos($script, '/admin/') === false) {
        $prefix = 'admin/';
    }
    if ($kind === 'client' && strpos($script, '/client/') === false) {
        $prefix = 'client/';
    }
    header('Location: ' . $prefix . 'two-factor.php');
    exit;
}

function totp_check_current($conn, $kind, $id, $password, $codeInput)
{
    $scope = totp_scope($kind);
    if (auth_attempt_blocked($conn, $scope, 8, 900)) {
        return 'Too many codes. Wait 15 minutes and try again.';
    }
    $account = totp_account($conn, $kind, $id);
    if (!$account || !password_verify((string) $password, (string) $account['password'])) {
        auth_note_attempt($conn, $scope);
        return 'The password or authenticator code is not right.';
    }
    $secret = trim((string) $account['totp_secret']);
    if ($secret === '') {
        return 'Google Authenticator is not on this account yet. Sign out, then sign in to set it up.';
    }
    $input = trim((string) $codeInput);
    $digits = preg_replace('/\D/', '', $input);
    if (preg_match('/^[0-9]{6}$/', $digits)) {
        $match = totp_match($secret, $digits, (int) $account['totp_last_step']);
        if (!empty($match['replay'])) {
            auth_note_attempt($conn, $scope);
            return 'That code was already used. Wait for the next code in the app.';
        }
        if (!empty($match['ok'])) {
            totp_set_step($conn, $kind, $id, $match['step']);
            auth_clear_attempts($conn, $scope);
            return '';
        }
    }
    $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
    if (strlen($normalized) === 8 && totp_use_recovery($conn, $kind, $id, $normalized)) {
        auth_clear_attempts($conn, $scope);
        return '';
    }
    auth_note_attempt($conn, $scope);
    return 'The password or authenticator code is not right.';
}

function totp_rekey_state($kind, $id)
{
    $row = (isset($_SESSION['totp_rekey']) && is_array($_SESSION['totp_rekey'])) ? $_SESSION['totp_rekey'] : null;
    if (!$row || !isset($row['kind'], $row['id'], $row['secret'], $row['expires'])) {
        return null;
    }
    if ($row['kind'] !== $kind || (int) $row['id'] !== (int) $id || (int) $row['expires'] < time() || $row['secret'] === '') {
        unset($_SESSION['totp_rekey']);
        return null;
    }
    return $row;
}

function totp_manage_view($kind, $id, $email)
{
    $codes = array();
    if (isset($_SESSION['totp_show_codes']) && is_array($_SESSION['totp_show_codes'])) {
        $saved = $_SESSION['totp_show_codes'];
        if (isset($saved['kind'], $saved['id'], $saved['codes']) && $saved['kind'] === $kind && (int) $saved['id'] === (int) $id && is_array($saved['codes'])) {
            $codes = $saved['codes'];
        }
    }
    $rekey = totp_rekey_state($kind, $id);
    $mode = 'idle';
    $uri = '';
    $grouped = '';
    if ($codes) {
        $mode = 'codes';
    } elseif ($rekey) {
        $mode = 'qr';
        $uri = totp_uri(totp_issuer(), $email, $rekey['secret']);
        $grouped = totp_key_groups($rekey['secret']);
    }
    return array(
        'mode' => $mode,
        'uri' => $uri,
        'grouped' => $grouped,
        'codes' => $codes
    );
}

function totp_manage_post($conn, $kind, $id)
{
    $result = array('msg' => '', 'err' => '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['totp_action'])) {
        return $result;
    }
    verify_csrf();
    $id = (int) $id;
    $action = (string) $_POST['totp_action'];
    if ($action === 'dismiss_codes') {
        unset($_SESSION['totp_show_codes']);
        return $result;
    }
    if ($action === 'cancel_rekey') {
        unset($_SESSION['totp_rekey']);
        return $result;
    }
    if ($action === 'confirm_rekey') {
        $rekey = totp_rekey_state($kind, $id);
        if (!$rekey) {
            $result['err'] = 'The new setup expired. Start again.';
            return $result;
        }
        $scope = totp_scope($kind);
        if (auth_attempt_blocked($conn, $scope, 8, 900)) {
            $result['err'] = 'Too many codes. Wait 15 minutes and try again.';
            return $result;
        }
        $match = totp_match($rekey['secret'], isset($_POST['authenticator_code']) ? $_POST['authenticator_code'] : '', 0);
        if (empty($match['ok'])) {
            auth_note_attempt($conn, $scope);
            $result['err'] = 'That code does not match the new setup. Use the current 6-digit code from the new entry.';
            return $result;
        }
        totp_store_secret($conn, $kind, $id, $rekey['secret'], $match['step']);
        $plain = totp_issue_recovery($conn, $kind, $id);
        unset($_SESSION['totp_rekey']);
        $_SESSION['totp_show_codes'] = array('kind' => $kind, 'id' => $id, 'codes' => $plain);
        auth_clear_attempts($conn, $scope);
        $result['msg'] = 'The new authenticator is on. Save the backup codes below. The old phone entry and the old backup codes no longer work.';
        return $result;
    }
    $password = isset($_POST['totp_password']) ? (string) $_POST['totp_password'] : '';
    $code = isset($_POST['authenticator_code']) ? (string) $_POST['authenticator_code'] : '';
    $check = totp_check_current($conn, $kind, $id, $password, $code);
    if ($check !== '') {
        $result['err'] = $check;
        return $result;
    }
    if ($action === 'backup') {
        $plain = totp_issue_recovery($conn, $kind, $id);
        $_SESSION['totp_show_codes'] = array('kind' => $kind, 'id' => $id, 'codes' => $plain);
        $result['msg'] = 'New backup codes are ready. The previous backup codes no longer work.';
        return $result;
    }
    if ($action === 'rekey') {
        $_SESSION['totp_rekey'] = array(
            'kind' => $kind,
            'id' => $id,
            'secret' => totp_random_secret(),
            'expires' => time() + 1200
        );
        unset($_SESSION['totp_show_codes']);
        $result['msg'] = 'Scan this with Google Authenticator, then enter the 6-digit code from that new entry.';
        return $result;
    }
    $result['err'] = 'That action could not be completed.';
    return $result;
}

function totp_qr_html($uri, $elementId)
{
    $elementId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $elementId);
    if ($elementId === '') {
        $elementId = 'totp-qr';
    }
    $json = json_encode((string) $uri, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $idJson = json_encode($elementId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    return '<div id="' . htmlspecialchars($elementId, ENT_QUOTES, 'UTF-8') . '" class="inline-block bg-white p-3 rounded-xl"></div>'
        . '<script src="../assets/js/qrcode.min.js"></script>'
        . '<script>if (window.QRCode) { new QRCode(document.getElementById(' . $idJson . '), { text: ' . $json . ', width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M }); }</script>';
}
