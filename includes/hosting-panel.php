<?php

function panel_cipher_key($conn)
{
    $key = (string) billing_setting($conn, 'panel_cipher_key');
    if (!preg_match('/^[a-f0-9]{64}$/', $key)) {
        $key = bin2hex(random_bytes(32));
        billing_set_setting($conn, 'panel_cipher_key', $key);
    }
    $raw = hex2bin($key);
    return is_string($raw) ? $raw : '';
}

function panel_pass_widen($conn)
{
    if (DB_DRIVER === 'sqlite' || billing_setting($conn, 'panel_pass_wide') === '1') {
        return;
    }
    try {
        billing_exec($conn, 'ALTER TABLE client_services MODIFY panel_pass TEXT NULL');
        billing_set_setting($conn, 'panel_pass_wide', '1');
    } catch (Throwable $exception) {
        error_log('cPanel password column could not be widened.');
    }
}

function panel_pass_seal($conn, $plain)
{
    $plain = (string) $plain;
    if ($plain === '' || strpos($plain, 'enc1:') === 0 || !function_exists('openssl_encrypt')) {
        return $plain;
    }
    $key = panel_cipher_key($conn);
    if ($key === '') {
        return $plain;
    }
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if (!is_string($cipher) || $cipher === '') {
        return $plain;
    }
    return 'enc1:' . base64_encode($iv . $cipher);
}

function panel_pass_open($conn, $stored)
{
    $stored = (string) $stored;
    if (strpos($stored, 'enc1:') !== 0) {
        return $stored;
    }
    if (!function_exists('openssl_decrypt')) {
        return '';
    }
    $raw = base64_decode(substr($stored, 5), true);
    if (!is_string($raw) || strlen($raw) < 17) {
        return '';
    }
    $plain = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', panel_cipher_key($conn), OPENSSL_RAW_DATA, substr($raw, 0, 16));
    return is_string($plain) ? $plain : '';
}

function panel_pass_migrate($conn)
{
    panel_pass_widen($conn);
    if (billing_setting($conn, 'panel_pass_sealed') === '1') {
        return;
    }
    $result = $conn->query("SELECT id, panel_pass FROM client_services WHERE panel_pass IS NOT NULL AND panel_pass <> '' AND panel_pass NOT LIKE 'enc1:%'");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sealed = panel_pass_seal($conn, (string) $row['panel_pass']);
            if (strpos($sealed, 'enc1:') !== 0) {
                return;
            }
            $id = (int) $row['id'];
            $update = $conn->prepare('UPDATE client_services SET panel_pass = ? WHERE id = ?');
            $update->bind_param('si', $sealed, $id);
            $update->execute();
            $update->close();
        }
    }
    billing_set_setting($conn, 'panel_pass_sealed', '1');
}

function hosting_panel_plans()
{
    return array('hosting-business', 'hosting-managed');
}

function hosting_panel_normalize($value)
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('#^https?://#', '', $value);
    if (!is_string($value)) {
        return '';
    }
    $value = preg_replace('#[:/].*$#', '', $value);
    if (!is_string($value) || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/', $value)) {
        return '';
    }
    return $value;
}

function hosting_panel_domain($row)
{
    if (!is_array($row)) {
        return '';
    }
    if (!empty($row['panel_host'])) {
        $host = hosting_panel_normalize($row['panel_host']);
        if ($host !== '') {
            return $host;
        }
    }
    $brief = array();
    if (!empty($row['order_brief'])) {
        $decoded = json_decode((string) $row['order_brief'], true);
        if (is_array($decoded)) {
            $brief = $decoded;
        }
    }
    if (!empty($brief['Domain'])) {
        $host = hosting_panel_normalize($brief['Domain']);
        if ($host !== '') {
            return $host;
        }
    }
    return hosting_panel_normalize(isset($row['detail_label']) ? $row['detail_label'] : '');
}

function hosting_panel_label($row)
{
    if (!is_array($row)) {
        return '';
    }
    $brief = array();
    if (!empty($row['order_brief'])) {
        $decoded = json_decode((string) $row['order_brief'], true);
        if (is_array($decoded)) {
            $brief = $decoded;
        }
    }
    if (!empty($brief['Domain'])) {
        $host = hosting_panel_normalize($brief['Domain']);
        if ($host !== '') {
            return $host;
        }
    }
    return hosting_panel_normalize(isset($row['detail_label']) ? $row['detail_label'] : '');
}

function hosting_panel_ready($row)
{
    if (!is_array($row) || (string) $row['status'] !== 'active') {
        return false;
    }
    if (!in_array((string) $row['plan_code'], hosting_panel_plans(), true)) {
        return false;
    }
    if (trim((string) $row['panel_user']) === '' || (string) $row['panel_pass'] === '') {
        return false;
    }
    return hosting_panel_domain($row) !== '';
}

function hosting_client_panels($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE client_id = ? AND status = ? ORDER BY id DESC');
    $status = 'active';
    $stmt->bind_param('is', $clientId, $status);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    $ready = array();
    foreach ($rows as $row) {
        if (hosting_panel_ready($row)) {
            $ready[] = $row;
        }
    }
    return $ready;
}

function hosting_panel_for_client($conn, $clientId, $serviceId)
{
    $clientId = (int) $clientId;
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE id = ? AND client_id = ?');
    $stmt->bind_param('ii', $serviceId, $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !hosting_panel_ready($row)) {
        return null;
    }
    return $row;
}

function hosting_panel_save($conn, $serviceId, $user, $pass, $host)
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT id, plan_code, panel_pass FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], hosting_panel_plans(), true)) {
        return 'cPanel login is only for an active hosting plan.';
    }
    $user = strtolower(trim((string) $user));
    if (!preg_match('/^[a-z][a-z0-9]{0,15}$/', $user)) {
        return 'The cPanel username starts with a letter and uses up to 16 letters or numbers.';
    }
    $pass = str_replace(array("\r", "\n", "\0"), '', (string) $pass);
    if ($pass === '') {
        $pass = panel_pass_open($conn, (string) $row['panel_pass']);
    }
    if ($pass === '' || strlen($pass) > 80) {
        return 'Enter the cPanel password. Leave it blank only after one is already saved.';
    }
    $pass = panel_pass_seal($conn, $pass);
    $host = trim((string) $host);
    if ($host !== '') {
        $host = hosting_panel_normalize($host);
        if ($host === '') {
            return 'The login address has to be a domain name, or leave it blank to use the client’s domain.';
        }
    }
    $update = $conn->prepare('UPDATE client_services SET panel_user = ?, panel_pass = ?, panel_host = NULLIF(?, \'\') WHERE id = ?');
    $update->bind_param('sssi', $user, $pass, $host, $serviceId);
    $update->execute();
    $update->close();
    return '';
}

function hosting_panel_clear($conn, $serviceId)
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT id, plan_code FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], hosting_panel_plans(), true)) {
        return false;
    }
    $user = '';
    $pass = '';
    $host = '';
    $update = $conn->prepare('UPDATE client_services SET panel_user = ?, panel_pass = ?, panel_host = NULLIF(?, \'\') WHERE id = ?');
    $update->bind_param('sssi', $user, $pass, $host, $serviceId);
    $update->execute();
    $update->close();
    return true;
}

function hosting_admin_rows($conn, $find = '')
{
    $business = 'hosting-business';
    $managed = 'hosting-managed';
    $status = 'active';
    $base = 'SELECT s.*, c.name, c.email FROM client_services s JOIN client_users c ON c.id = s.client_id WHERE s.status = ? AND s.plan_code IN (?, ?) ';
    $find = admin_find_text($find);
    if ($find !== '') {
        $like = '%' . $find . '%';
        $stmt = $conn->prepare($base . 'AND (c.name LIKE ? OR c.email LIKE ? OR s.service_name LIKE ? OR s.detail_label LIKE ?) ORDER BY s.id DESC LIMIT 40');
        $stmt->bind_param('sssssss', $status, $business, $managed, $like, $like, $like, $like);
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
        return $rows;
    }
    $rows = array();
    $seen = array();
    foreach (array(
        $base . 'AND (s.panel_user IS NULL OR s.panel_user = \'\' OR s.panel_pass IS NULL OR s.panel_pass = \'\') ORDER BY s.id DESC',
        $base . 'AND s.panel_user <> \'\' AND s.panel_pass <> \'\' ORDER BY s.id DESC LIMIT 40'
    ) as $sql) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $status, $business, $managed);
        $stmt->execute();
        foreach (db_fetch_all($stmt) as $row) {
            $id = (int) $row['id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $rows[] = $row;
        }
        $stmt->close();
    }
    return $rows;
}
