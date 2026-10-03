<?php

function website_plans()
{
    return array('web-company', 'web-portfolio', 'web-sahakari', 'web-restaurant', 'web-school', 'web-hotel', 'web-news');
}

function training_plans()
{
    return array('train-directors', 'train-staff', 'train-members', 'train-field-day');
}

function delivery_blocked_host($host)
{
    $host = strtolower((string) $host);
    foreach (array('zoho', 'protoa', 'mercantile', 'cpanel', 'sparrow', 'aakashsms', 'register.com.np') as $word) {
        if (strpos($host, $word) !== false) {
            return true;
        }
    }
    return false;
}

function website_url_normalize($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (!preg_match('#^https://#i', $value)) {
        $value = 'https://' . $value;
    }
    $parts = parse_url($value);
    if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        return '';
    }
    $host = hosting_panel_normalize($parts['host']);
    if ($host === '' || delivery_blocked_host($host)) {
        return '';
    }
    $path = isset($parts['path']) ? (string) $parts['path'] : '/';
    if ($path === '') {
        $path = '/';
    }
    if (!preg_match('#^[A-Za-z0-9._~!$&\'()*+,;=:@/%-]*$#', $path)) {
        return '';
    }
    $url = 'https://' . $host . ($path === '/' ? '/' : $path);
    if (strlen($url) > 253) {
        return '';
    }
    return $url;
}

function delivery_brief_value($row, $key)
{
    if (!is_array($row) || empty($row['order_brief'])) {
        return '';
    }
    $decoded = json_decode((string) $row['order_brief'], true);
    if (!is_array($decoded) || !isset($decoded[$key])) {
        return '';
    }
    return billing_plain_line($decoded[$key], 180);
}

function delivery_merge_brief($row, $key, $value)
{
    $brief = array();
    if (is_array($row) && !empty($row['order_brief'])) {
        $decoded = json_decode((string) $row['order_brief'], true);
        if (is_array($decoded)) {
            $brief = $decoded;
        }
    }
    $value = billing_plain_line($value, 180);
    if ($value === '') {
        unset($brief[$key]);
    } else {
        $brief[$key] = $value;
    }
    $json = json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json === false ? '' : $json;
}

function website_url($row)
{
    if (!is_array($row)) {
        return '';
    }
    return website_url_normalize(isset($row['panel_host']) ? $row['panel_host'] : '');
}

function website_ready($row)
{
    if (!is_array($row) || (string) $row['panel_user'] !== 'open') {
        return false;
    }
    if (!in_array((string) $row['plan_code'], website_plans(), true)) {
        return false;
    }
    if ((string) $row['status'] !== 'active' && (string) $row['status'] !== 'booked') {
        return false;
    }
    return website_url($row) !== '';
}

function website_for_client($conn, $clientId, $serviceId)
{
    $clientId = (int) $clientId;
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE id = ? AND client_id = ?');
    $stmt->bind_param('ii', $serviceId, $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !website_ready($row)) {
        return null;
    }
    return $row;
}

function website_save($conn, $serviceId, $url, $note = '')
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], website_plans(), true)) {
        return 'A website link is only for a booked website.';
    }
    if ((string) $row['status'] !== 'booked' && (string) $row['status'] !== 'active') {
        return 'A website link is only for a booked website.';
    }
    $url = website_url_normalize($url);
    if ($url === '') {
        return 'Enter the website address on the client’s own domain, starting with https://.';
    }
    $briefJson = delivery_merge_brief($row, 'Note', $note);
    if ($briefJson === '') {
        return 'The website note could not be saved.';
    }
    $user = 'open';
    $pass = '';
    $status = 'active';
    $update = $conn->prepare('UPDATE client_services SET panel_user = ?, panel_pass = ?, panel_host = ?, status = ?, order_brief = ? WHERE id = ?');
    $update->bind_param('sssssi', $user, $pass, $url, $status, $briefJson, $serviceId);
    $update->execute();
    $update->close();
    billing_mail_client_event($conn, (int) $row['client_id'], 'website-live', array(
        'service' => (string) $row['service_name'],
        'url' => $url,
        'note' => billing_plain_line($note, 180)
    ));
    return '';
}

function website_clear($conn, $serviceId)
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT id, plan_code FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], website_plans(), true)) {
        return false;
    }
    $user = '';
    $pass = '';
    $host = '';
    $status = 'booked';
    $update = $conn->prepare('UPDATE client_services SET panel_user = ?, panel_pass = ?, panel_host = NULLIF(?, \'\'), status = ? WHERE id = ?');
    $update->bind_param('ssssi', $user, $pass, $host, $status, $serviceId);
    $update->execute();
    $update->close();
    return true;
}

function website_admin_rows($conn, $find = '')
{
    $codes = website_plans();
    $base = 'SELECT s.*, c.name, c.email FROM client_services s JOIN client_users c ON c.id = s.client_id WHERE s.status IN (\'booked\', \'active\') AND s.plan_code IN (?, ?, ?, ?, ?, ?, ?) ';
    $find = admin_find_text($find);
    if ($find !== '') {
        $like = '%' . $find . '%';
        $stmt = $conn->prepare($base . 'AND (c.name LIKE ? OR c.email LIKE ? OR s.service_name LIKE ? OR s.detail_label LIKE ?) ORDER BY s.id DESC LIMIT 40');
        $stmt->bind_param('sssssssssss', $codes[0], $codes[1], $codes[2], $codes[3], $codes[4], $codes[5], $codes[6], $like, $like, $like, $like);
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
        $stmt->bind_param('sssssss', $codes[0], $codes[1], $codes[2], $codes[3], $codes[4], $codes[5], $codes[6]);
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

function website_label($row)
{
    $url = website_url($row);
    $parts = parse_url($url);
    return is_array($parts) && !empty($parts['host']) ? (string) $parts['host'] : '';
}

function website_client_sites($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE client_id = ? AND status IN (\'booked\', \'active\') ORDER BY id DESC');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    $ready = array();
    foreach ($rows as $row) {
        if (website_ready($row)) {
            $ready[] = $row;
        }
    }
    return $ready;
}

function training_client_rows($conn, $clientId)
{
    $clientId = (int) $clientId;
    $status = 'booked';
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE client_id = ? AND status = ? ORDER BY id DESC');
    $stmt->bind_param('is', $clientId, $status);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    $ready = array();
    foreach ($rows as $row) {
        $mark = (string) $row['panel_user'];
        if (!in_array((string) $row['plan_code'], training_plans(), true)) {
            continue;
        }
        if (($mark === 'confirmed' || $mark === 'done') && training_date($row) !== '') {
            $ready[] = $row;
        }
    }
    return $ready;
}

function training_date($row)
{
    if (!is_array($row)) {
        return '';
    }
    $date = trim((string) (isset($row['panel_host']) ? $row['panel_host'] : ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return '';
    }
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    $errors = DateTime::getLastErrors();
    if (!$parsed || ($errors && ((int) $errors['warning_count'] > 0 || (int) $errors['error_count'] > 0))) {
        return '';
    }
    return $parsed->format('Y-m-d');
}

function training_save($conn, $serviceId, $date, $done, $place = '')
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT * FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], training_plans(), true) || (string) $row['status'] !== 'booked') {
        return 'A visit date is only for a booked training session.';
    }
    $date = training_date(array('panel_host' => $date));
    if ($date === '') {
        return 'Enter the visit date.';
    }
    $briefJson = delivery_merge_brief($row, 'Place', $place);
    if ($briefJson === '') {
        return 'The visit place could not be saved.';
    }
    $user = $done ? 'done' : 'confirmed';
    $pass = '';
    $update = $conn->prepare('UPDATE client_services SET panel_user = ?, panel_pass = ?, panel_host = ?, order_brief = ? WHERE id = ?');
    $update->bind_param('ssssi', $user, $pass, $date, $briefJson, $serviceId);
    $update->execute();
    $update->close();
    billing_mail_client_event($conn, (int) $row['client_id'], 'training-set', array(
        'service' => (string) $row['service_name'],
        'status' => $done ? 'complete' : 'confirmed',
        'date' => date('M j, Y', strtotime($date)),
        'place' => billing_plain_line($place, 180)
    ));
    return '';
}

function training_clear($conn, $serviceId)
{
    $serviceId = (int) $serviceId;
    $stmt = $conn->prepare('SELECT id, plan_code FROM client_services WHERE id = ?');
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || !in_array((string) $row['plan_code'], training_plans(), true)) {
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

function training_admin_rows($conn, $find = '')
{
    $codes = training_plans();
    $status = 'booked';
    $base = 'SELECT s.*, c.name, c.email FROM client_services s JOIN client_users c ON c.id = s.client_id WHERE s.status = ? AND s.plan_code IN (?, ?, ?, ?) ';
    $find = admin_find_text($find);
    if ($find !== '') {
        $like = '%' . $find . '%';
        $stmt = $conn->prepare($base . 'AND (c.name LIKE ? OR c.email LIKE ? OR s.service_name LIKE ? OR s.detail_label LIKE ?) ORDER BY s.id DESC LIMIT 40');
        $stmt->bind_param('sssssssss', $status, $codes[0], $codes[1], $codes[2], $codes[3], $like, $like, $like, $like);
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
        return $rows;
    }
    $rows = array();
    $seen = array();
    foreach (array(
        $base . 'AND (s.panel_user IS NULL OR s.panel_user NOT IN (\'confirmed\',\'done\')) ORDER BY s.id DESC',
        $base . 'AND s.panel_user IN (\'confirmed\',\'done\') ORDER BY s.id DESC LIMIT 40'
    ) as $sql) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $status, $codes[0], $codes[1], $codes[2], $codes[3]);
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
