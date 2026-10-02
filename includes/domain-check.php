<?php

function domain_label($raw)
{
    $raw = strtolower(trim((string) $raw));
    $raw = preg_replace('#^https?://#', '', $raw);
    $raw = preg_replace('#^www\.#', '', $raw);
    $raw = preg_replace('#\.(com\.np|com)$#', '', $raw);
    $raw = trim((string) $raw, '.');
    if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $raw)) {
        return '';
    }
    return $raw;
}

function domain_tld($raw)
{
    return $raw === 'com.np' ? 'com.np' : 'com';
}

function domain_full_name($label, $tld)
{
    return domain_label($label) . '.' . domain_tld($tld);
}

function domain_check_result($label, $tld)
{
    $label = domain_label($label);
    $tld = domain_tld($tld);
    if ($label === '') {
        return array('status' => 'invalid', 'domain' => '');
    }
    $domain = $label . '.' . $tld;
    $status = $tld === 'com.np' ? domain_check_np($label) : domain_check_com($domain);
    return array('status' => $status, 'domain' => $domain, 'tld' => $tld, 'label' => $label);
}

function domain_curl($url, $post, $cookieFile, $follow, $headOnly = false)
{
    if (!function_exists('curl_init')) {
        return array('code' => 0, 'body' => '', 'location' => '');
    }
    $ch = curl_init($url);
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'AakashTechnologies/1.0',
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => $headOnly
    );
    if (defined('CURLPROTO_HTTPS')) {
        $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
    }
    if (is_array($post)) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    if (!is_string($raw)) {
        return array('code' => 0, 'body' => '', 'location' => '');
    }
    $headers = substr($raw, 0, $headerSize);
    $location = '';
    if (preg_match('/^Location:\s*(\S+)/mi', $headers, $match)) {
        $location = trim($match[1]);
    }
    return array('code' => $code, 'body' => substr($raw, $headerSize), 'location' => $location);
}

function domain_check_np($label)
{
    $cookie = tempnam(sys_get_temp_dir(), 'npdomain');
    if ($cookie === false) {
        return 'unknown';
    }
    $home = domain_curl('https://register.com.np/', null, $cookie, true);
    $token = '';
    if (preg_match('/name="_token" value="([^"]+)"/', $home['body'], $match)) {
        $token = $match[1];
    }
    if ($token === '') {
        @unlink($cookie);
        return 'unknown';
    }
    $check = domain_curl('https://register.com.np/checkdomain', array(
        '_token' => $token,
        'domainName' => $label,
        'domainExtension' => '.com.np'
    ), $cookie, false);
    @unlink($cookie);
    if (strpos($check['location'], 'domainavailable') !== false) {
        return 'available';
    }
    if (strpos($check['location'], 'domainnotavailable') !== false) {
        return 'taken';
    }
    return 'unknown';
}

function domain_check_com($domain)
{
    $cookie = tempnam(sys_get_temp_dir(), 'comdomain');
    $url = 'https://rdap.verisign.com/com/v1/domain/' . rawurlencode($domain);
    $response = domain_curl($url, null, $cookie ? $cookie : '', false, true);
    if ($cookie) {
        @unlink($cookie);
    }
    if ($response['code'] === 404) {
        return 'available';
    }
    if ($response['code'] === 200) {
        return 'taken';
    }
    return 'unknown';
}

function domain_request_open($conn, $domain)
{
    $stmt = $conn->prepare("SELECT id FROM domain_requests WHERE domain_name = ? AND status IN ('requested', 'active') LIMIT 1");
    $stmt->bind_param('s', $domain);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return (bool) $row;
}

function domain_store_document($clientId, $file)
{
    $clientId = (int) $clientId;
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => false, 'error' => 'Attach the document for this .com.np name.');
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The document could not be uploaded.');
    }
    if ((int) $file['size'] > 2097152) {
        return array('ok' => false, 'error' => 'The document must be smaller than 2 MB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $ext = '';
    if ($mime === 'application/pdf') {
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($head !== '%PDF-') {
            return array('ok' => false, 'error' => 'The PDF could not be read.');
        }
        $ext = 'pdf';
    } else {
        $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp');
        $image = @getimagesize($file['tmp_name']);
        $imageTypes = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg');
        if (defined('IMAGETYPE_WEBP')) {
            $imageTypes[IMAGETYPE_WEBP] = 'webp';
        }
        if (!isset($types[$mime]) || !$image || !isset($imageTypes[$image[2]]) || $types[$mime] !== $imageTypes[$image[2]]) {
            return array('ok' => false, 'error' => 'Use a PDF, JPG, PNG, or WEBP document.');
        }
        $ext = $imageTypes[$image[2]];
    }
    $dir = dirname(__DIR__) . '/uploads/domains/' . $clientId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The document folder could not be created.');
    }
    $guard = dirname(__DIR__) . '/uploads/domains/.htaccess';
    if (!is_file($guard)) {
        file_put_contents($guard, "Require all denied\nDeny from all\n");
    }
    $relative = 'uploads/domains/' . $clientId . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $relative)) {
        return array('ok' => false, 'error' => 'The document could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function domain_safe_file($clientId, $relative)
{
    $clientId = (int) $clientId;
    $relative = str_replace('\\', '/', (string) $relative);
    if (!preg_match('#^uploads/domains/' . $clientId . '/[a-f0-9]{32}\.(pdf|jpg|png|webp)$#', $relative)) {
        return '';
    }
    $full = dirname(__DIR__) . '/' . $relative;
    return is_file($full) ? $full : '';
}

function domain_send_file($conn, $requestId, $clientId)
{
    $requestId = (int) $requestId;
    $stmt = $conn->prepare('SELECT * FROM domain_requests WHERE id = ?');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || ($clientId > 0 && (int) $row['client_id'] !== $clientId)) {
        http_response_code(404);
        exit;
    }
    $full = domain_safe_file((int) $row['client_id'], $row['document_path']);
    if ($full === '') {
        http_response_code(404);
        exit;
    }
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $types = array('pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
    header('Content-Type: ' . $types[$ext]);
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="domain-document.' . $ext . '"');
    header('Content-Length: ' . (string) filesize($full));
    readfile($full);
    exit;
}

function domain_client_requests($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT * FROM domain_requests WHERE client_id = ? ORDER BY id DESC');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    return $rows;
}

function domain_request_queue($conn)
{
    $result = $conn->query('SELECT d.*, c.name AS account_name, c.email, c.phone FROM domain_requests d JOIN client_users c ON c.id = d.client_id ORDER BY CASE d.status WHEN \'requested\' THEN 0 WHEN \'active\' THEN 1 ELSE 2 END, d.id DESC');
    $rows = array();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function domain_mark_request($conn, $requestId, $decision, $note)
{
    $requestId = (int) $requestId;
    $stmt = $conn->prepare('SELECT * FROM domain_requests WHERE id = ?');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || $row['status'] !== 'requested') {
        return 'That request is not waiting for registration.';
    }
    if ($decision === 'active') {
        $status = 'active';
        $note = '';
        $when = date('Y-m-d H:i:s');
        $update = $conn->prepare('UPDATE domain_requests SET status = ?, admin_note = ?, activated_at = ? WHERE id = ?');
        $update->bind_param('sssi', $status, $note, $when, $requestId);
        $update->execute();
        $update->close();
        return '';
    }
    if ($decision === 'declined') {
        $note = billing_plain_block($note, 400);
        if (strlen($note) < 5) {
            return 'Write why this name was not registered.';
        }
        $status = 'declined';
        $update = $conn->prepare('UPDATE domain_requests SET status = ?, admin_note = ? WHERE id = ?');
        $update->bind_param('ssi', $status, $note, $requestId);
        $update->execute();
        $update->close();
        return '';
    }
    return 'That decision is not available.';
}
