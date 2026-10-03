<?php

function mail_login_plans()
{
    return array('email-1', 'email-5', 'email-10');
}

function mail_login_blocked($host)
{
    return strpos(strtolower((string) $host), 'zoho') !== false;
}

function mail_login_domain($row)
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
    $label = trim((string) (isset($row['detail_label']) ? $row['detail_label'] : ''));
    if (strpos($label, '·') !== false) {
        $label = trim(strstr($label, '·', true));
    }
    return hosting_panel_normalize($label);
}

function mail_login_host($row)
{
    if (!is_array($row)) {
        return '';
    }
    if (!empty($row['panel_host'])) {
        $host = hosting_panel_normalize($row['panel_host']);
        if ($host !== '' && !mail_login_blocked($host)) {
            return $host;
        }
    }
    $domain = mail_login_domain($row);
    if ($domain === '') {
        return '';
    }
    return hosting_panel_normalize('mail.' . $domain);
}

function mail_login_ready($row)
{
    if (!is_array($row) || (string) $row['status'] !== 'active') {
        return false;
    }
    if (!in_array((string) $row['plan_code'], mail_login_plans(), true)) {
        return false;
    }
    if ((string) $row['panel_user'] !== 'open') {
        return false;
    }
    return mail_login_host($row) !== '';
}

function mail_client_logins($conn, $clientId)
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
        if (mail_login_ready($row)) {
            $ready[] = $row;
        }
    }
    return $ready;
}

function mail_login_for_client($conn, $clientId, $serviceId)
{
    $clientId = (int) $clientId;
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE id = ? AND client_id = ?');
    $stmt->bind_param('ii', $serviceId, $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !mail_login_ready($row)) {
        return null;
    }
    return $row;
}

function mail_login_save($conn, $serviceId, $host)
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT id, plan_code FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], mail_login_plans(), true)) {
        return 'Email login is only for an active mailbox plan.';
    }
    $host = trim((string) $host);
    if ($host !== '') {
        $host = hosting_panel_normalize($host);
        if ($host === '' || mail_login_blocked($host)) {
            return 'Leave the address blank so the inbox opens on mail.the-client-domain. A provider address cannot be saved.';
        }
    }
    $user = 'open';
    $pass = '';
    $update = $conn->prepare('UPDATE client_services SET panel_user = ?, panel_pass = ?, panel_host = NULLIF(?, \'\') WHERE id = ?');
    $update->bind_param('sssi', $user, $pass, $host, $serviceId);
    $update->execute();
    $update->close();
    return '';
}

function mail_login_clear($conn, $serviceId)
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT id, plan_code FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], mail_login_plans(), true)) {
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

function mail_admin_rows($conn, $find = '')
{
    $one = 'email-1';
    $five = 'email-5';
    $ten = 'email-10';
    $status = 'active';
    $base = 'SELECT s.*, c.name, c.email FROM client_services s JOIN client_users c ON c.id = s.client_id WHERE s.status = ? AND s.plan_code IN (?, ?, ?) ';
    $find = admin_find_text($find);
    if ($find !== '') {
        $like = '%' . $find . '%';
        $stmt = $conn->prepare($base . 'AND (c.name LIKE ? OR c.email LIKE ? OR s.service_name LIKE ? OR s.detail_label LIKE ?) ORDER BY s.id DESC LIMIT 40');
        $stmt->bind_param('ssssssss', $status, $one, $five, $ten, $like, $like, $like, $like);
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
        return $rows;
    }
    $rows = array();
    $seen = array();
    foreach (array(
        $base . 'AND (s.panel_user IS NULL OR s.panel_user <> \'open\') ORDER BY s.id DESC',
        $base . 'AND s.panel_user = \'open\' ORDER BY s.id DESC LIMIT 40'
    ) as $sql) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $status, $one, $five, $ten);
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

function mail_login_scrub($conn)
{
    if (billing_setting($conn, 'mail_names_scrubbed') === '1') {
        return;
    }
    $names = array(
        'email-1' => '1 mailbox',
        'email-5' => '5 mailboxes',
        'email-10' => '10 mailboxes'
    );
    $like = '%Zoho%';
    foreach ($names as $code => $name) {
        $stmt = $conn->prepare('UPDATE client_services SET service_name = ? WHERE plan_code = ? AND service_name LIKE ?');
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('sss', $name, $code, $like);
        $stmt->execute();
        $stmt->close();
    }
    $description = 'Mailboxes on your own domain, managed in Nepal.';
    $features = 'Your domain,Mailboxes,Auto-renew';
    $slug = 'professional-email';
    $stmt = $conn->prepare('UPDATE services SET description = ?, features = ? WHERE slug = ? AND (description LIKE ? OR features LIKE ?)');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('sssss', $description, $features, $slug, $like, $like);
    $stmt->execute();
    $stmt->close();
    billing_set_setting($conn, 'mail_names_scrubbed', '1');
}
