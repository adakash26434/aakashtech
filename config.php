<?php
/**
 * Aakash Technologies — Database Configuration & Auth
 * Compatible with PHP 7.4+ and PHP 8.x
 * Supports MySQL and MariaDB
 */

$cpanelFile = __DIR__ . '/cpanel-config.php';
$cpanel = array();
if (is_file($cpanelFile)) {
    $loadedCpanel = require $cpanelFile;
    if (is_array($loadedCpanel)) {
        $cpanel = $loadedCpanel;
    }
}

function cpanel_setting($cpanel, $key, $envName, $fallback)
{
    if (isset($cpanel[$key]) && trim((string) $cpanel[$key]) !== '') {
        return trim((string) $cpanel[$key]);
    }
    $fromEnv = getenv($envName);
    if ($fromEnv !== false && $fromEnv !== '') {
        return $fromEnv;
    }
    return $fallback;
}

$cpanelDbName = cpanel_setting($cpanel, 'db_name', 'DB_NAME', '');
if ($cpanelDbName !== '') {
    $cpanelDriver = 'mysql';
    $cpanelDbHost = cpanel_setting($cpanel, 'db_host', 'DB_HOST', 'localhost');
    $cpanelDbUser = cpanel_setting($cpanel, 'db_user', 'DB_USER', '');
    $cpanelDbPass = isset($cpanel['db_pass']) && (string) $cpanel['db_pass'] !== '' ? (string) $cpanel['db_pass'] : (getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
} else {
    $cpanelDriver = strtolower(cpanel_setting($cpanel, 'db_driver', 'DB_DRIVER', 'mysql'));
    $cpanelDbHost = cpanel_setting($cpanel, 'db_host', 'DB_HOST', 'localhost');
    $cpanelDbUser = cpanel_setting($cpanel, 'db_user', 'DB_USER', 'root');
    $cpanelDbPass = isset($cpanel['db_pass']) && (string) $cpanel['db_pass'] !== '' ? (string) $cpanel['db_pass'] : (getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
    $cpanelDbName = cpanel_setting($cpanel, 'db_name', 'DB_NAME', 'aakash_tech');
}

// ====== DATABASE CONFIGURATION ======
define('DB_DRIVER', $cpanelDriver);
define('DB_HOST', $cpanelDbHost);
define('DB_USER', $cpanelDbUser);
define('DB_PASS', $cpanelDbPass);
define('DB_NAME', $cpanelDbName);
define('DB_CHARSET', 'utf8mb4');

// ====== CONNECT TO DATABASE ======
if (DB_DRIVER === 'sqlite') {
    require_once __DIR__ . '/includes/sqlite-mysqli-compat.php';
    $sqlitePath = getenv('SQLITE_PATH') ?: sys_get_temp_dir() . '/aakash-technologies.sqlite';
    $conn = new AakashSqliteConnection($sqlitePath, __DIR__ . '/database.sqlite.sql');
} elseif (DB_DRIVER === 'mysql' || DB_DRIVER === 'mariadb') {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new RuntimeException('Database connection failed. Check the database settings.');
    }
    $conn->set_charset(DB_CHARSET);
} else {
    throw new InvalidArgumentException('DB_DRIVER must be mysql, mariadb, or sqlite.');
}

require_once __DIR__ . '/includes/billing.php';
try {
    auth_ensure_client_table($conn);
} catch (Throwable $exception) {
    error_log('Client account table could not be created.');
}
try {
    billing_ensure($conn);
} catch (Throwable $exception) {
    error_log('Billing setup could not be completed.');
}
try {
    auth_ensure_attempts($conn);
} catch (Throwable $exception) {
    error_log('Login attempt log could not be created.');
}
try {
    auth_ensure_admin_table($conn);
} catch (Throwable $exception) {
    error_log('Admin account table could not be created.');
}
try {
    cpanel_apply_admin($conn, cpanel_setting($cpanel, 'admin_email', 'ADMIN_EMAIL', ''), isset($cpanel['admin_password']) ? (string) $cpanel['admin_password'] : (getenv('ADMIN_PASSWORD') !== false ? (string) getenv('ADMIN_PASSWORD') : ''));
} catch (Throwable $exception) {
    error_log('Admin account could not be updated from cpanel-config.php.');
}

// ====== SITE CONFIGURATION ======
define('SITE_NAME', cpanel_setting($cpanel, 'site_name', 'SITE_NAME', 'Aakash Technologies'));
define('SITE_EMAIL', cpanel_setting($cpanel, 'site_email', 'SITE_EMAIL', 'info@aakashtechnologies.com'));
define('SITE_PHONE', cpanel_setting($cpanel, 'site_phone', 'SITE_PHONE', '+977 98XXXXXXXX'));
define('SITE_LOCATION', cpanel_setting($cpanel, 'site_location', 'SITE_LOCATION', 'Kathmandu, Nepal'));
define('ESEWA_ID', cpanel_setting($cpanel, 'esewa_id', 'ESEWA_ID', ''));
define('KHALTI_ID', cpanel_setting($cpanel, 'khalti_id', 'KHALTI_ID', ''));
define('BANK_DETAILS', cpanel_setting($cpanel, 'bank_details', 'BANK_DETAILS', ''));
define('CRON_KEY', cpanel_setting($cpanel, 'cron_key', 'CRON_KEY', ''));
define('ADMIN_EMAIL', cpanel_setting($cpanel, 'admin_email', 'ADMIN_EMAIL', ''));

require_once __DIR__ . '/includes/session.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ====== AUTH HELPERS ======

function cpanel_apply_admin($conn, $email, $password)
{
    $email = trim((string) $email);
    $password = (string) $password;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        return;
    }
    $stamp = hash('sha256', $email . "\0" . $password);
    $saved = '';
    try {
        $saved = (string) billing_setting($conn, 'cpanel_admin_stamp');
    } catch (Throwable $exception) {
        $saved = '';
    }
    if ($saved !== '' && hash_equals($saved, $stamp)) {
        return;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $name = 'Admin';
    $role = 'super_admin';
    $stmt = $conn->prepare('SELECT id FROM admin_users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Admin account could not be read.');
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $existing = db_fetch_assoc($stmt);
    $stmt->close();
    if ($existing) {
        $id = (int) $existing['id'];
        $stmt = $conn->prepare('UPDATE admin_users SET password = ?, is_active = 1 WHERE id = ?');
        $stmt->bind_param('si', $hash, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare('INSERT INTO admin_users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $email, $hash, $role);
        $stmt->execute();
        $stmt->close();
    }
    billing_set_setting($conn, 'cpanel_admin_stamp', $stamp);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function is_client_logged_in() {
    return isset($_SESSION['client_id']) && !empty($_SESSION['client_id']);
}

function require_admin() {
    if (!is_admin_logged_in() || !auth_account_is_active('admin')) {
        auth_drop_role('admin');
        $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $prefix = strpos($script, '/admin/') !== false ? '' : 'admin/';
        header('Location: ' . $prefix . 'login.php');
        exit;
    }
}

function require_client() {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $prefix = strpos($script, '/client/') !== false ? '' : 'client/';
    if (!is_client_logged_in()) {
        header('Location: ' . $prefix . 'login.php');
        exit;
    }
    if (!auth_account_is_active('client')) {
        auth_drop_role('client');
        flash('login_error', 'Your account is suspended. Contact support.');
        header('Location: ' . $prefix . 'login.php');
        exit;
    }
}

function admin_logout() {
    auth_end_session();
    header('Location: login.php');
    exit;
}

function client_logout() {
    auth_end_session();
    header('Location: login.php');
    exit;
}

function get_admin_name() {
    return $_SESSION['admin_name'] ?? 'Admin';
}

function get_client_name() {
    return $_SESSION['client_name'] ?? 'Client';
}

function get_client_id() {
    return (int) ($_SESSION['client_id'] ?? 0);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid() {
    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $saved = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
    return $saved !== '' && hash_equals($saved, $token);
}

function verify_csrf() {
    if (!csrf_is_valid()) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function auth_fresh_session() {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function auth_client_ip() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    return substr($ip, 0, 45);
}

function auth_ensure_admin_table($conn) {
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'admin',
            is_active INTEGER DEFAULT 1,
            last_login TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        return;
    }
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT "admin",
        is_active TINYINT(1) DEFAULT 1,
        last_login DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function auth_mobile_number($value)
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    if (!is_string($digits)) {
        return '';
    }
    if (strlen($digits) === 13 && substr($digits, 0, 3) === '977') {
        $digits = substr($digits, 3);
    }
    if (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
        $digits = substr($digits, 1);
    }
    if (strlen($digits) !== 10) {
        return '';
    }
    return $digits;
}

function auth_ensure_client_table($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            phone TEXT DEFAULT NULL,
            company TEXT DEFAULT NULL,
            address TEXT DEFAULT NULL,
            status TEXT DEFAULT 'active',
            avatar_color TEXT DEFAULT '#06b6d4',
            sms_portal_username TEXT DEFAULT '',
            sms_portal_password TEXT DEFAULT '',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $columns = array(
            'phone' => 'TEXT DEFAULT NULL',
            'company' => 'TEXT DEFAULT NULL',
            'address' => 'TEXT DEFAULT NULL',
            'status' => "TEXT DEFAULT 'active'",
            'avatar_color' => "TEXT DEFAULT '#06b6d4'"
        );
    } else {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            company VARCHAR(255) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            avatar_color VARCHAR(20) DEFAULT '#06b6d4',
            sms_portal_username VARCHAR(80) DEFAULT '',
            sms_portal_password VARCHAR(80) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $columns = array(
            'phone' => 'VARCHAR(50) DEFAULT NULL',
            'company' => 'VARCHAR(255) DEFAULT NULL',
            'address' => 'TEXT DEFAULT NULL',
            'status' => "VARCHAR(20) DEFAULT 'active'",
            'avatar_color' => "VARCHAR(20) DEFAULT '#06b6d4'"
        );
    }

    $present = array();
    try {
        $present = array_flip(billing_table_columns($conn, 'client_users'));
    } catch (Throwable $exception) {
        $present = array();
    }
    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        try {
            billing_exec($conn, 'ALTER TABLE client_users ADD COLUMN ' . $name . ' ' . $definition);
        } catch (Throwable $exception) {
            error_log('Client account column could not be added.');
        }
    }
}

function auth_ensure_attempts($conn) {
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            scope TEXT NOT NULL,
            ip TEXT NOT NULL,
            attempted_at TEXT NOT NULL
        )');
        return;
    }
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scope VARCHAR(20) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        attempted_at DATETIME NOT NULL,
        INDEX idx_attempt_lookup (scope, ip, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function db_fetch_all($stmt)
{
    if (!is_object($stmt)) {
        return array();
    }
    if (method_exists($stmt, 'get_result')) {
        try {
            $result = $stmt->get_result();
            if ($result) {
                $rows = array();
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                return $rows;
            }
        } catch (Throwable $exception) {
            error_log('Falling back while reading database rows.');
        }
    }
    $meta = $stmt->result_metadata();
    if (!$meta) {
        return array();
    }
    $fields = $meta->fetch_fields();
    $meta->free();
    $row = array();
    $refs = array();
    foreach ($fields as $index => $field) {
        $row[$field->name] = null;
        $refs[$index] = &$row[$field->name];
    }
    call_user_func_array(array($stmt, 'bind_result'), $refs);
    $rows = array();
    while ($stmt->fetch()) {
        $copy = array();
        foreach ($row as $key => $value) {
            $copy[$key] = $value;
        }
        $rows[] = $copy;
    }
    return $rows;
}

function db_fetch_assoc($stmt) {
    if (!is_object($stmt)) {
        return null;
    }
    if (method_exists($stmt, 'get_result')) {
        try {
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                return $row ? $row : null;
            }
        } catch (Throwable $exception) {
            error_log('Falling back while reading a database row.');
        }
    }
    $meta = $stmt->result_metadata();
    if (!$meta) {
        return null;
    }
    $fields = $meta->fetch_fields();
    $meta->free();
    $row = array();
    $refs = array();
    foreach ($fields as $index => $field) {
        $row[$field->name] = null;
        $refs[$index] = &$row[$field->name];
    }
    call_user_func_array(array($stmt, 'bind_result'), $refs);
    if (!$stmt->fetch()) {
        return null;
    }
    $copy = array();
    foreach ($row as $key => $value) {
        $copy[$key] = $value;
    }
    return $copy;
}

function auth_attempt_blocked($conn, $scope, $limit, $windowSeconds) {
    try {
        $scope = substr((string) $scope, 0, 20);
        $ip = auth_client_ip();
        $since = date('Y-m-d H:i:s', time() - (int) $windowSeconds);
        $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM login_attempts WHERE scope = ? AND ip = ? AND attempted_at >= ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sss', $scope, $ip, $since);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        return $row && (int) $row['c'] >= (int) $limit;
    } catch (Throwable $exception) {
        return false;
    }
}

function auth_note_attempt($conn, $scope) {
    try {
        $scope = substr((string) $scope, 0, 20);
        $ip = auth_client_ip();
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare('INSERT INTO login_attempts (scope, ip, attempted_at) VALUES (?, ?, ?)');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $scope, $ip, $now);
        $stmt->execute();
        $stmt->close();
        $cut = date('Y-m-d H:i:s', time() - 86400);
        $stmt = $conn->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $cut);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $exception) {
        error_log('Sign-in attempt could not be recorded.');
    }
}

function auth_clear_attempts($conn, $scope) {
    try {
        $scope = substr((string) $scope, 0, 20);
        $ip = auth_client_ip();
        $stmt = $conn->prepare('DELETE FROM login_attempts WHERE scope = ? AND ip = ?');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ss', $scope, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $exception) {
        error_log('Sign-in attempts could not be cleared.');
    }
}

function auth_account_is_active($role) {
    global $conn;
    if (!$conn) {
        return false;
    }
    if ($role === 'admin') {
        $id = (int) ($_SESSION['admin_id'] ?? 0);
        $stmt = $conn->prepare('SELECT is_active FROM admin_users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row && (int) $row['is_active'] === 1;
    }
    $id = (int) ($_SESSION['client_id'] ?? 0);
    $stmt = $conn->prepare('SELECT status FROM client_users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && $row['status'] === 'active';
}

function auth_drop_role($role) {
    if ($role === 'admin') {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
        return;
    }
    unset($_SESSION['client_id'], $_SESSION['client_name'], $_SESSION['client_email'], $_SESSION['client_next']);
}

function auth_end_session() {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $cookie = array(
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly']
        );
        if (isset($params['samesite'])) {
            $cookie['samesite'] = $params['samesite'];
        }
        setcookie(session_name(), '', $cookie);
    }
    session_destroy();
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function flash($key, $msg = null) {
    if ($msg !== null) {
        $_SESSION['flash_' . $key] = $msg;
    } else {
        $val = $_SESSION['flash_' . $key] ?? '';
        unset($_SESSION['flash_' . $key]);
        return $val;
    }
}
