<?php

function auth_ensure_resets($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            token_hash TEXT NOT NULL,
            expires_at TEXT NOT NULL
        )');
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_reset_hash ON password_resets(token_hash)');
        billing_exec($conn, 'CREATE INDEX IF NOT EXISTS idx_reset_client ON password_resets(client_id)');
        return;
    }
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        INDEX idx_reset_hash (token_hash),
        INDEX idx_reset_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function password_reset_link($token)
{
    return rtrim(site_canonical_origin(), '/') . '/client/reset-password.php?token=' . rawurlencode($token);
}

function password_reset_clear($conn, $clientId)
{
    $clientId = (int) $clientId;
    if ($clientId < 1) {
        return;
    }
    $clear = $conn->prepare('DELETE FROM password_resets WHERE client_id = ?');
    if (!$clear) {
        return;
    }
    $clear->bind_param('i', $clientId);
    $clear->execute();
    $clear->close();
}

function password_reset_request($conn, $email)
{
    $email = strtolower(trim((string) $email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $stmt = $conn->prepare('SELECT id, status FROM client_users WHERE LOWER(email) = ? LIMIT 1');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || (string) $row['status'] !== 'active') {
        return;
    }
    $clientId = (int) $row['id'];
    password_reset_clear($conn, $clientId);
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 1800);
    $insert = $conn->prepare('INSERT INTO password_resets (client_id, token_hash, expires_at) VALUES (?, ?, ?)');
    if (!$insert) {
        return;
    }
    $insert->bind_param('iss', $clientId, $hash, $expires);
    $insert->execute();
    $insert->close();
    $link = password_reset_link($token);
    if ($link === '') {
        error_log('Password reset link could not be built.');
        return;
    }
    $sent = billing_mail_person($conn, $email, 'Reset your password', array(
        'Use this link to choose a new password. It works for 30 minutes.',
        $link,
        'If you did not ask for this, ignore the email. The current password stays in place.'
    ));
    if (!$sent['ok']) {
        error_log('Password reset email could not be sent.');
    }
}

function password_reset_consume($conn, $token, $password)
{
    $token = strtolower(trim((string) $token));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return 'This link has expired. Ask for a new one.';
    }
    if (strlen((string) $password) < 8) {
        return 'Use a password of at least 8 characters.';
    }
    $hash = hash('sha256', $token);
    $stmt = $conn->prepare('SELECT client_id, expires_at FROM password_resets WHERE token_hash = ? LIMIT 1');
    if (!$stmt) {
        return 'This link has expired. Ask for a new one.';
    }
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    $now = date('Y-m-d H:i:s');
    if (!$row || (string) $row['expires_at'] < $now) {
        return 'This link has expired. Ask for a new one.';
    }
    $clientId = (int) $row['client_id'];
    $passHash = password_hash((string) $password, PASSWORD_DEFAULT);
    $active = 'active';
    $update = $conn->prepare('UPDATE client_users SET password = ? WHERE id = ? AND status = ?');
    if (!$update) {
        return 'The password could not be saved. Ask for a new link.';
    }
    $update->bind_param('sis', $passHash, $clientId, $active);
    $update->execute();
    $changed = (int) $conn->affected_rows > 0;
    $update->close();
    if ($changed) {
        // Proving the email is enough to reset the password, so it also clears the authenticator
        // that was attached to the account. Someone who only knew the old password, or who
        // enrolled their own phone first, cannot keep the owner out. The owner enrolls again at sign-in.
        $untotp = $conn->prepare("UPDATE client_users SET totp_secret = '', totp_last_step = 0 WHERE id = ?");
        if ($untotp) {
            $untotp->bind_param('i', $clientId);
            $untotp->execute();
            $untotp->close();
        }
    }
    password_reset_clear($conn, $clientId);
    if (!$changed) {
        return 'This link has expired. Ask for a new one.';
    }
    return '';
}

function client_admin_set_password($conn, $clientId, $password)
{
    $clientId = (int) $clientId;
    $password = (string) $password;
    if ($clientId < 1) {
        return 'That client was not found.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        return 'Use a password of 8 to 72 characters.';
    }
    $active = 'active';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE client_users SET password = ? WHERE id = ? AND status = ?');
    if (!$stmt) {
        return 'The password could not be saved.';
    }
    $stmt->bind_param('sis', $hash, $clientId, $active);
    $stmt->execute();
    $changed = (int) $conn->affected_rows > 0;
    $stmt->close();
    if (!$changed) {
        return 'Activate the account before setting a password.';
    }
    password_reset_clear($conn, $clientId);
    if (function_exists('billing_mail_client_event')) {
        billing_mail_client_event($conn, $clientId, 'password-office', array());
    }
    return '';
}
