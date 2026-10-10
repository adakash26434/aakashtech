<?php
/**
 * Email verification for new client sign-ups.
 *
 * A new account starts as 'pending'. It cannot sign in until the person opens the link sent to
 * the address they typed. Accounts created before this change are already active and are not
 * asked again; accounts the team creates are verified by the team.
 */

function email_verify_ensure_columns($conn)
{
    static $checked = false;
    if ($checked || !$conn) {
        return;
    }
    $checked = true;
    $present = array_flip(billing_table_columns($conn, 'client_users'));
    $definitions = DB_DRIVER === 'sqlite'
        ? array('email_verified_at' => 'TEXT DEFAULT NULL', 'email_verify_hash' => "TEXT DEFAULT ''", 'email_verify_expires' => 'TEXT DEFAULT NULL')
        : array('email_verified_at' => 'DATETIME NULL DEFAULT NULL', 'email_verify_hash' => "VARCHAR(64) NOT NULL DEFAULT ''", 'email_verify_expires' => 'DATETIME NULL DEFAULT NULL');
    foreach ($definitions as $name => $definition) {
        if (!isset($present[$name])) {
            billing_exec($conn, 'ALTER TABLE client_users ADD COLUMN ' . $name . ' ' . $definition);
        }
    }
}

function email_verify_link($token)
{
    return rtrim(site_canonical_origin(), '/') . '/client/verify-email.php?token=' . rawurlencode($token);
}

/** Creates a fresh link (48 hours, one use) and mails it. Older links stop working. */
function email_verify_issue($conn, $clientId, $email)
{
    email_verify_ensure_columns($conn);
    $clientId = (int) $clientId;
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 172800);
    $stmt = $conn->prepare('UPDATE client_users SET email_verify_hash = ?, email_verify_expires = ? WHERE id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ssi', $hash, $expires, $clientId);
    $stmt->execute();
    $stmt->close();
    $sent = billing_mail_person($conn, $email, 'Confirm your email address', array(
        'Thank you for opening an account. Confirm this address to start using the portal. The link works for 48 hours and only once.',
        email_verify_link($token),
        'If you did not open an account, ignore this email. Nothing is activated without the link.'
    ));
    if (empty($sent['ok'])) {
        error_log('Email verification email could not be sent.');
        return false;
    }
    return true;
}

/** Returns the client id when the link is valid, and activates that account. */
function email_verify_consume($conn, $token)
{
    email_verify_ensure_columns($conn);
    $token = strtolower(trim((string) $token));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return 0;
    }
    $hash = hash('sha256', $token);
    $now = date('Y-m-d H:i:s');
    $find = $conn->prepare('SELECT id FROM client_users WHERE email_verify_hash = ? AND email_verify_expires >= ? AND status = ? LIMIT 1');
    if (!$find) {
        return 0;
    }
    $pending = 'pending';
    $find->bind_param('sss', $hash, $now, $pending);
    $find->execute();
    $row = db_fetch_assoc($find);
    $find->close();
    if (!$row) {
        return 0;
    }
    $clientId = (int) $row['id'];
    // Claim the link: only the request that clears the hash first activates the account.
    $active = 'active';
    $claim = $conn->prepare("UPDATE client_users SET status = ?, email_verified_at = ?, email_verify_hash = '', email_verify_expires = NULL WHERE id = ? AND email_verify_hash = ?");
    if (!$claim) {
        return 0;
    }
    $claim->bind_param('ssis', $active, $now, $clientId, $hash);
    $claim->execute();
    $won = billing_affected($conn) === 1;
    $claim->close();
    return $won ? $clientId : 0;
}
