<?php
/**
 * Client acceptance of the Terms and Conditions.
 *
 * A client ticks the box at sign-up, or on the first page after sign-in when the terms have
 * changed. The record kept on the account is the terms version, the date and time (Nepal
 * time), the network address and the browser. Bump TERMS_VERSION whenever the terms text
 * changes, so every client accepts the new text before using the portal again.
 */
if (!defined('TERMS_VERSION')) {
    define('TERMS_VERSION', '2026-10-10');
}

function terms_ensure_columns($conn)
{
    static $checked = false;
    if ($checked || !$conn) {
        return;
    }
    $checked = true;
    $present = array_flip(billing_table_columns($conn, 'client_users'));
    if (DB_DRIVER === 'sqlite') {
        $definitions = array(
            'terms_version' => "TEXT DEFAULT ''",
            'terms_accepted_at' => 'TEXT DEFAULT NULL',
            'terms_accepted_ip' => "TEXT DEFAULT ''",
            'terms_user_agent' => "TEXT DEFAULT ''"
        );
    } else {
        $definitions = array(
            'terms_version' => "VARCHAR(20) NOT NULL DEFAULT ''",
            'terms_accepted_at' => 'DATETIME NULL DEFAULT NULL',
            'terms_accepted_ip' => "VARCHAR(45) NOT NULL DEFAULT ''",
            'terms_user_agent' => "VARCHAR(255) NOT NULL DEFAULT ''"
        );
    }
    foreach ($definitions as $name => $definition) {
        if (!isset($present[$name])) {
            billing_exec($conn, 'ALTER TABLE client_users ADD COLUMN ' . $name . ' ' . $definition);
        }
    }
}

/** Saves the acceptance on the account. Returns true when the row was updated. */
function terms_record_acceptance($conn, $clientId)
{
    terms_ensure_columns($conn);
    $id = (int) $clientId;
    $version = TERMS_VERSION;
    $when = date('Y-m-d H:i:s');
    $ip = substr((string) auth_client_ip(), 0, 45);
    $agent = substr((string) (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''), 0, 255);
    $stmt = $conn->prepare('UPDATE client_users SET terms_version = ?, terms_accepted_at = ?, terms_accepted_ip = ?, terms_user_agent = ? WHERE id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ssssi', $version, $when, $ip, $agent, $id);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function terms_client_has_accepted($conn, $clientId)
{
    terms_ensure_columns($conn);
    $id = (int) $clientId;
    $stmt = $conn->prepare('SELECT terms_version FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row && (string) $row['terms_version'] === TERMS_VERSION;
}

/**
 * Called from the client header on every portal page. A client who has not accepted the
 * current terms is sent to accept-terms.php first. Staff in office view are not checked.
 */
function terms_gate_client($conn, $clientId)
{
    if (function_exists('client_office_view') && client_office_view()) {
        return;
    }
    if (!terms_client_has_accepted($conn, $clientId)) {
        header('Location: accept-terms.php');
        exit;
    }
}
