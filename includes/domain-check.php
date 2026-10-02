<?php

function domain_np_tlds()
{
    return array('com.np', 'edu.np', 'gov.np', 'net.np', 'org.np', 'info.np', 'mil.np', 'name.np', 'coop.np');
}

function domain_is_np($tld)
{
    return in_array((string) $tld, domain_np_tlds(), true);
}

function domain_label($raw)
{
    $raw = strtolower(trim((string) $raw));
    $raw = preg_replace('#^https?://#', '', $raw);
    $raw = preg_replace('#^www\.#', '', $raw);
    $raw = trim((string) $raw, '.');
    $endings = domain_np_tlds();
    $endings[] = 'com';
    usort($endings, function ($left, $right) {
        return strlen($right) - strlen($left);
    });
    foreach ($endings as $ending) {
        $suffix = '.' . $ending;
        $suffixLength = strlen($suffix);
        if (strlen($raw) > $suffixLength && substr($raw, -$suffixLength) === $suffix) {
            $raw = substr($raw, 0, -$suffixLength);
            break;
        }
    }
    if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $raw)) {
        return '';
    }
    return $raw;
}

function domain_tld($raw)
{
    $raw = strtolower(trim((string) $raw));
    $raw = ltrim($raw, '.');
    if ($raw === 'com' || domain_is_np($raw)) {
        return $raw;
    }
    return '';
}

function domain_check_result($label, $tld)
{
    $label = domain_label($label);
    $tld = domain_tld($tld);
    if ($label === '' || $tld === '') {
        return array('status' => 'invalid', 'domain' => '', 'tld' => $tld, 'label' => $label);
    }
    $domain = $label . '.' . $tld;
    $status = domain_is_np($tld) ? domain_check_np($label, $tld) : domain_check_com($domain);
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

function domain_check_np($label, $tld)
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
    $extension = domain_tld($tld);
    if (!domain_is_np($extension)) {
        @unlink($cookie);
        return 'unknown';
    }
    $check = domain_curl('https://register.com.np/checkdomain', array(
        '_token' => $token,
        'domainName' => $label,
        'domainExtension' => '.' . $extension
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

function domain_keep_first_request($conn, $domain, $requestId)
{
    $requestId = (int) $requestId;
    $stmt = $conn->prepare("SELECT id FROM domain_requests WHERE domain_name = ? AND status IN ('requested', 'paid', 'active') AND id < ? LIMIT 1");
    $stmt->bind_param('si', $domain, $requestId);
    $stmt->execute();
    $earlier = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$earlier) {
        return true;
    }
    $drop = $conn->prepare("DELETE FROM domain_requests WHERE id = ? AND status = 'requested'");
    $drop->bind_param('i', $requestId);
    $drop->execute();
    $drop->close();
    return false;
}

function domain_request_open($conn, $domain)
{
    $stmt = $conn->prepare("SELECT id FROM domain_requests WHERE domain_name = ? AND status IN ('requested', 'paid', 'active') LIMIT 1");
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
        return array('ok' => false, 'error' => 'Attach the document for this Nepal name.');
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

function domain_year_bill($conn, $tld)
{
    $tld = domain_tld($tld);
    if ($tld === '') {
        return array('total' => '0.00', 'label' => '');
    }
    $code = domain_is_np($tld) ? 'domain-np' : 'domain-com';
    $plan = billing_find_plan($conn, $code);
    if (!$plan || (float) $plan['price'] <= 0) {
        return array('total' => '0.00', 'label' => '');
    }
    $selling = billing_selling_price($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0);
    $bill = billing_vat_bill($selling);
    return array(
        'total' => billing_money($bill['total']),
        'label' => billing_money_label($bill['total']) . ' with 13% VAT for one year'
    );
}

function domain_registry_file_note($clientId, $relative)
{
    $full = domain_safe_file($clientId, $relative);
    if ($full === '') {
        return '';
    }
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $size = filesize($full);
    if (($ext === 'jpg' || $ext === 'png') && $size !== false && $size <= 819200) {
        return '';
    }
    return 'register.com.np accepts a JPG or PNG up to about 800 KB. Prepare a smaller JPG or PNG before you upload this file there.';
}

function domain_pay_request($conn, $clientId, $requestId)
{
    $clientId = (int) $clientId;
    $requestId = (int) $requestId;
    $stmt = $conn->prepare('SELECT * FROM domain_requests WHERE id = ? AND client_id = ?');
    $stmt->bind_param('ii', $requestId, $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || $row['status'] !== 'requested') {
        return 'That request is not waiting for payment.';
    }
    $price = billing_money($row['price']);
    if ((float) $price <= 0) {
        return 'That request has no yearly bill.';
    }
    $again = domain_check_result(domain_label($row['domain_name']), $row['tld']);
    if ($again['status'] === 'unknown') {
        return 'The registry could not be checked just now. Try the payment again in a moment.';
    }
    if ($again['status'] !== 'available' || $again['domain'] !== $row['domain_name']) {
        $status = 'declined';
        $note = 'The name was no longer free, so it was not charged.';
        $update = $conn->prepare('UPDATE domain_requests SET status = ?, admin_note = ? WHERE id = ? AND status = \'requested\'');
        $update->bind_param('ssi', $status, $note, $requestId);
        $update->execute();
        $update->close();
        return 'That name is no longer free. It was not charged. Check another name.';
    }
    if (!billing_wallet_debit($conn, $clientId, $price)) {
        return 'The wallet does not have enough for this year. Add funds, then pay again.';
    }
    $status = 'paid';
    $update = $conn->prepare('UPDATE domain_requests SET status = ? WHERE id = ? AND client_id = ? AND status = \'requested\'');
    $update->bind_param('sii', $status, $requestId, $clientId);
    $update->execute();
    $saved = billing_affected($conn) === 1;
    $update->close();
    if (!$saved) {
        billing_wallet_credit($conn, $clientId, $price);
        return 'The payment could not be saved. The wallet was not charged.';
    }
    billing_record_entry($conn, $clientId, $price, 'debit', 'purchase', 'completed', 'wallet', 'Domain request ' . $row['domain_name'], 0);
    billing_notify($conn, 'Domain paid: ' . $row['domain_name'], array(
        'A domain request is paid and waiting to be registered.',
        'Domain: ' . $row['domain_name'],
        'Holder: ' . $row['holder_name'],
        'Amount: NPR ' . $price,
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Register the name, then mark it Active in Admin → Domains.'
    ));
    return '';
}

function domain_cancel_request($conn, $clientId, $requestId)
{
    $clientId = (int) $clientId;
    $requestId = (int) $requestId;
    $status = 'declined';
    $note = 'Cancelled before payment.';
    $stmt = $conn->prepare('UPDATE domain_requests SET status = ?, admin_note = ? WHERE id = ? AND client_id = ? AND status = \'requested\'');
    $stmt->bind_param('ssii', $status, $note, $requestId, $clientId);
    $stmt->execute();
    $saved = billing_affected($conn) === 1;
    $stmt->close();
    return $saved ? '' : 'That request can no longer be cancelled.';
}

function domain_start_service($conn, $row)
{
    $clientId = (int) $row['client_id'];
    $price = billing_money($row['price']);
    $today = date('Y-m-d');
    $cycle = 'yearly';
    $next = billing_add_cycle($today, $cycle);
    $auto = 1;
    $status = 'active';
    $planCode = domain_is_np($row['tld']) ? 'domain-np' : 'domain-com';
    $name = 'Domain registration — .' . $row['tld'];
    $description = 'One ' . $row['tld'] . ' domain for a year, renewed from the wallet.';
    $detail = $row['domain_name'];
    $address = isset($row['holder_address']) ? $row['holder_address'] : '';
    $brief = json_encode(array(
        'domain' => $row['domain_name'],
        'holder' => $row['holder_name'],
        'address' => $address
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($brief === false) {
        $brief = '';
    }
    $unitKind = '';
    $quantity = 1;
    $stmt = $conn->prepare('INSERT INTO client_services (client_id, service_name, description, status, start_date, end_date, price, plan_code, billing_cycle, auto_renew, next_renewal, detail_label, order_brief, unit_kind, unit_quantity) VALUES (?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?)');
    $stmt->bind_param('issssssssissssi', $clientId, $name, $description, $status, $today, $next, $price, $planCode, $cycle, $auto, $next, $detail, $brief, $unitKind, $quantity);
    $ok = $stmt->execute();
    $serviceId = (int) $conn->insert_id;
    $stmt->close();
    return ($ok && $serviceId > 0) ? $serviceId : 0;
}

function domain_mark_request($conn, $requestId, $decision, $note)
{
    $requestId = (int) $requestId;
    $stmt = $conn->prepare('SELECT * FROM domain_requests WHERE id = ?');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || ($row['status'] !== 'requested' && $row['status'] !== 'paid')) {
        return 'That request is not waiting for registration.';
    }
    $legacy = $row['status'] === 'requested' && (float) $row['price'] <= 0;
    if ($decision === 'active') {
        if ($row['status'] !== 'paid' && !$legacy) {
            return 'The client has not paid the yearly bill yet.';
        }
        $status = 'active';
        $blank = '';
        $when = date('Y-m-d H:i:s');
        $update = $conn->prepare('UPDATE domain_requests SET status = ?, admin_note = ?, activated_at = ? WHERE id = ? AND status IN (\'paid\', \'requested\')');
        $update->bind_param('sssi', $status, $blank, $when, $requestId);
        $update->execute();
        $saved = billing_affected($conn) === 1;
        $update->close();
        if (!$saved) {
            return 'That request is not waiting for registration.';
        }
        if ((float) $row['price'] <= 0) {
            return '';
        }
        $serviceId = domain_start_service($conn, $row);
        if ($serviceId < 1) {
            $back = $legacy ? 'requested' : 'paid';
            $revert = $conn->prepare('UPDATE domain_requests SET status = ?, activated_at = NULL WHERE id = ? AND status = \'active\'');
            $revert->bind_param('si', $back, $requestId);
            $revert->execute();
            $revert->close();
            return 'The yearly service could not be saved. The request is still waiting.';
        }
        $link = $conn->prepare('UPDATE domain_requests SET service_id = ? WHERE id = ?');
        $link->bind_param('ii', $serviceId, $requestId);
        $link->execute();
        $link->close();
        $email = billing_client_email($conn, (int) $row['client_id']);
        if ($email !== '') {
            billing_mail_person($conn, $email, 'Domain active: ' . $row['domain_name'], array(
                $row['domain_name'] . ' is marked Active.',
                'The paid year starts now and renews from the wallet.',
                'See it under My domains in the client panel.'
            ));
        }
        return '';
    }
    if ($decision === 'declined') {
        $note = billing_plain_block($note, 400);
        if (strlen($note) < 5) {
            return 'Write why this name was not registered.';
        }
        $status = 'declined';
        $update = $conn->prepare('UPDATE domain_requests SET status = ?, admin_note = ? WHERE id = ? AND status IN (\'paid\', \'requested\')');
        $update->bind_param('ssi', $status, $note, $requestId);
        $update->execute();
        $saved = billing_affected($conn) === 1;
        $update->close();
        if (!$saved) {
            return 'That request is not waiting for registration.';
        }
        if ($row['status'] === 'paid' && (float) $row['price'] > 0) {
            billing_wallet_credit($conn, (int) $row['client_id'], $row['price']);
            billing_record_entry($conn, (int) $row['client_id'], $row['price'], 'credit', 'refund', 'completed', 'wallet', 'Domain not registered: ' . $row['domain_name'], 0);
        }
        $email = billing_client_email($conn, (int) $row['client_id']);
        if ($email !== '') {
            $refundLine = ($row['status'] === 'paid' && (float) $row['price'] > 0)
                ? 'The amount was returned to the wallet.'
                : 'It was not charged.';
            billing_mail_person($conn, $email, 'Domain request closed: ' . $row['domain_name'], array(
                'The domain request for ' . $row['domain_name'] . ' was not registered.',
                $refundLine,
                'Note: ' . $note
            ));
        }
        return '';
    }
    return 'That decision is not available.';
}
