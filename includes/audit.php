<?php
/**
 * Admin audit log: who did what, and when, from the admin panel.
 *
 * Every admin form submission is recorded by its action names and the ids it touched. Values
 * are never recorded: passwords, authenticator codes, and secrets are dropped before saving.
 */

function audit_ensure_table($conn)
{
    static $ready = false;
    if ($ready || !$conn) {
        return;
    }
    $ready = true;
    $sql = DB_DRIVER === 'sqlite'
        ? 'CREATE TABLE IF NOT EXISTS admin_audit (id INTEGER PRIMARY KEY AUTOINCREMENT, admin_id INTEGER NOT NULL, admin_role TEXT DEFAULT \'\', action TEXT NOT NULL, target TEXT DEFAULT \'\', ip TEXT DEFAULT \'\', created_at TEXT DEFAULT CURRENT_TIMESTAMP)'
        : 'CREATE TABLE IF NOT EXISTS admin_audit (id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, admin_role VARCHAR(20) NOT NULL DEFAULT \'\', action VARCHAR(120) NOT NULL, target VARCHAR(255) NOT NULL DEFAULT \'\', ip VARCHAR(45) NOT NULL DEFAULT \'\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB';
    try {
        billing_exec($conn, $sql);
    } catch (Throwable $exception) {
        error_log('Admin audit table could not be created.');
    }
}

function audit_log($conn, $action, $target = '')
{
    audit_ensure_table($conn);
    $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
    if ($adminId < 1) {
        return;
    }
    $role = substr((string) (isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : ''), 0, 20);
    $action = substr((string) $action, 0, 120);
    $target = substr((string) $target, 0, 255);
    $ip = substr((string) auth_client_ip(), 0, 45);
    $stmt = $conn->prepare('INSERT INTO admin_audit (admin_id, admin_role, action, target, ip) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('issss', $adminId, $role, $action, $target, $ip);
    $stmt->execute();
    $stmt->close();
}

/** Keys whose values must never reach the log. */
function audit_sensitive_key($key)
{
    return (bool) preg_match('/pass|confirm|secret|token|csrf|authenticator|code|cipher|key/i', (string) $key);
}

/** Records one admin form submission: the action names and the ids it refers to. */
function audit_log_post($conn)
{
    if (!isset($_POST) || !is_array($_POST)) {
        return;
    }
    $actions = array();
    $ids = array();
    foreach ($_POST as $key => $value) {
        $key = (string) $key;
        if (audit_sensitive_key($key)) {
            continue;
        }
        if (preg_match('/(^|_)(id|client_id|entry_id|service_id|campaign_id|token_id|ticket_id)$/', $key)) {
            $ids[] = $key . '=' . substr((string) $value, 0, 20);
        } elseif (is_string($value) && $value === '1') {
            $actions[] = $key;
        } elseif (is_string($value) && $value !== '' && in_array($key, array('decision', 'target', 'status'), true)) {
            $actions[] = $key . ':' . substr($value, 0, 20);
        }
    }
    if (!$actions) {
        return;
    }
    audit_log($conn, implode(',', $actions), implode(' ', $ids));
}

/** The most recent entries, newest first. */
function audit_recent($conn, $limit = 200)
{
    audit_ensure_table($conn);
    $limit = max(1, min(500, (int) $limit));
    $result = $conn->query('SELECT a.id, a.action, a.target, a.admin_role, a.ip, a.created_at, u.name AS admin_name FROM admin_audit a LEFT JOIN admin_users u ON u.id = a.admin_id ORDER BY a.id DESC LIMIT ' . $limit);
    $rows = array();
    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }
    return $rows;
}
