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

function domain_whois_blank()
{
    return array('status' => 'invalid', 'domain' => '', 'tld' => '', 'rows' => array());
}

function domain_whois_split($raw)
{
    $raw = strtolower(trim((string) $raw));
    $raw = preg_replace('#^https?://#', '', $raw);
    $raw = preg_replace('#^www\.#', '', (string) $raw);
    $raw = trim((string) $raw, ". \t\n\r\0\x0B");
    $raw = preg_replace('~[/?#].*$~', '', (string) $raw);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $endings = domain_np_tlds();
    $endings[] = 'com';
    usort($endings, function ($left, $right) {
        return strlen($right) - strlen($left);
    });
    foreach ($endings as $ending) {
        $suffix = '.' . $ending;
        $suffixLength = strlen($suffix);
        if (strlen($raw) <= $suffixLength || substr($raw, -$suffixLength) !== $suffix) {
            continue;
        }
        $label = domain_label(substr($raw, 0, -$suffixLength));
        if ($label === '' || strpos($label, '.') !== false) {
            return null;
        }
        return array('label' => $label, 'tld' => $ending, 'domain' => $label . '.' . $ending);
    }
    return null;
}

function domain_whois_target($name, $selectedTld)
{
    $split = domain_whois_split($name);
    if ($split) {
        return $split;
    }
    $label = domain_label($name);
    $tld = domain_tld($selectedTld);
    if ($label === '' || $tld === '' || strpos((string) $name, '.') !== false) {
        return null;
    }
    return array('label' => $label, 'tld' => $tld, 'domain' => $label . '.' . $tld);
}

function domain_whois_cell($value)
{
    $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
    $value = trim(preg_replace('/\s+/', ' ', $value));
    if ($value === '' || strlen($value) > 240) {
        return '';
    }
    if (preg_match('#https?://|www\.#i', $value)) {
        return '';
    }
    return $value;
}

function domain_whois_vcard($entity, $field)
{
    if (!is_array($entity) || empty($entity['vcardArray'][1]) || !is_array($entity['vcardArray'][1])) {
        return '';
    }
    foreach ($entity['vcardArray'][1] as $card) {
        if (is_array($card) && isset($card[0], $card[3]) && $card[0] === $field && is_string($card[3])) {
            return domain_whois_cell($card[3]);
        }
    }
    return '';
}

function domain_whois_date($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    try {
        $date = new DateTime($value);
    } catch (Exception $exception) {
        return domain_whois_cell($value);
    }
    return $date->format('j M Y');
}

function domain_whois_np($label, $tld)
{
    $cookie = tempnam(sys_get_temp_dir(), 'npwhois');
    if ($cookie === false) {
        return domain_whois_blank();
    }
    $home = domain_curl('https://register.com.np/whois-lookup', null, $cookie, true);
    $token = '';
    if (preg_match('/name="_token" value="([^"]+)"/', $home['body'], $match)) {
        $token = $match[1];
    }
    if ($token === '') {
        @unlink($cookie);
        return array('status' => 'unknown', 'domain' => $label . '.' . $tld, 'tld' => $tld, 'rows' => array());
    }
    $check = domain_curl('https://register.com.np/checkdomain_whois', array(
        '_token' => $token,
        'domainName' => $label,
        'domainExtension' => '.' . $tld
    ), $cookie, false);
    if (strpos($check['location'], 'domainavailable') !== false) {
        @unlink($cookie);
        return array('status' => 'free', 'domain' => $label . '.' . $tld, 'tld' => $tld, 'rows' => array());
    }
    if (strpos($check['location'], 'domainwhoisdetail') === false) {
        @unlink($cookie);
        return array('status' => 'unknown', 'domain' => $label . '.' . $tld, 'tld' => $tld, 'rows' => array());
    }
    $detail = domain_curl('https://register.com.np/domainwhoisdetail', null, $cookie, false);
    @unlink($cookie);
    if (stripos($detail['body'], 'Whoops') !== false || !preg_match_all('/<tr>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>/is', $detail['body'], $matches, PREG_SET_ORDER)) {
        return array('status' => 'unknown', 'domain' => $label . '.' . $tld, 'tld' => $tld, 'rows' => array());
    }
    $allowed = array(
        'domain name' => 'Domain name',
        'first registered date' => 'First registered date',
        'last updated date' => 'Last updated date',
        'primary name server' => 'Primary name server',
        'secondary name server' => 'Secondary name server',
        'registrant email' => 'Registrant email',
        'contact person' => 'Contact person',
        'company name' => 'Company name',
        'administrative email' => 'Administrative email',
        'telephone' => 'Telephone',
        'address' => 'Address'
    );
    $rows = array();
    foreach ($matches as $match) {
        $key = strtolower(trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8'), " :\t"));
        if ($key === '') {
            continue;
        }
        $value = domain_whois_cell($match[2]);
        if ($key === 'first registered date' || $key === 'last updated date') {
            $value = domain_whois_date($value);
        }
        if ($value === '') {
            continue;
        }
        $label = isset($allowed[$key]) ? $allowed[$key] : ucwords($key);
        $rows[] = array('group' => 'domain', 'label' => $label, 'value' => $value);
    }
    if (!$rows) {
        return array('status' => 'unknown', 'domain' => $label . '.' . $tld, 'tld' => $tld, 'rows' => array());
    }
    return array('status' => 'registered', 'domain' => $label . '.' . $tld, 'tld' => $tld, 'rows' => $rows);
}

function domain_whois_com($domain)
{
    $cookie = tempnam(sys_get_temp_dir(), 'comwhois');
    $response = domain_curl('https://rdap.verisign.com/com/v1/domain/' . rawurlencode($domain), null, $cookie ? $cookie : '', false);
    if ($cookie) {
        @unlink($cookie);
    }
    if ($response['code'] === 404) {
        return array('status' => 'free', 'domain' => $domain, 'tld' => 'com', 'rows' => array());
    }
    $data = json_decode($response['body'], true);
    if ($response['code'] !== 200 || !is_array($data)) {
        return array('status' => 'unknown', 'domain' => $domain, 'tld' => 'com', 'rows' => array());
    }
    $rows = array(array('group' => 'domain', 'label' => 'Domain name', 'value' => $domain));
    $dates = array('registration' => 'Registered on', 'expiration' => 'Expires on', 'last changed' => 'Updated on');
    if (!empty($data['events']) && is_array($data['events'])) {
        foreach ($data['events'] as $event) {
            $action = isset($event['eventAction']) ? (string) $event['eventAction'] : '';
            if (!isset($dates[$action])) {
                continue;
            }
            $shown = domain_whois_date(isset($event['eventDate']) ? $event['eventDate'] : '');
            if ($shown !== '') {
                $rows[] = array('group' => 'domain', 'label' => $dates[$action], 'value' => $shown);
            }
        }
    }
    $statuses = array();
    if (!empty($data['status']) && is_array($data['status'])) {
        foreach ($data['status'] as $status) {
            $clean = domain_whois_cell(str_replace('_', ' ', (string) $status));
            if ($clean !== '') {
                $statuses[] = $clean;
            }
        }
    }
    if ($statuses) {
        $rows[] = array('group' => 'domain', 'label' => 'Status', 'value' => implode("\n", $statuses));
    }
    $servers = array();
    if (!empty($data['nameservers']) && is_array($data['nameservers'])) {
        foreach ($data['nameservers'] as $server) {
            $name = domain_whois_cell(isset($server['ldhName']) ? strtolower((string) $server['ldhName']) : '');
            if ($name !== '') {
                $servers[] = $name;
            }
        }
    }
    if ($servers) {
        $rows[] = array('group' => 'domain', 'label' => 'Name servers', 'value' => implode("\n", $servers));
    }
    if (!empty($data['entities']) && is_array($data['entities'])) {
        foreach ($data['entities'] as $entity) {
            $roles = isset($entity['roles']) && is_array($entity['roles']) ? $entity['roles'] : array();
            if (!in_array('registrar', $roles, true)) {
                continue;
            }
            $registrar = domain_whois_vcard($entity, 'fn');
            if ($registrar !== '') {
                $rows[] = array('group' => 'registrar', 'label' => 'Registrar', 'value' => $registrar);
            }
            if (!empty($entity['publicIds']) && is_array($entity['publicIds'])) {
                foreach ($entity['publicIds'] as $publicId) {
                    $kind = isset($publicId['type']) ? (string) $publicId['type'] : '';
                    $identifier = domain_whois_cell(isset($publicId['identifier']) ? $publicId['identifier'] : '');
                    if ($identifier !== '' && stripos($kind, 'IANA') !== false) {
                        $rows[] = array('group' => 'registrar', 'label' => 'IANA ID', 'value' => $identifier);
                    }
                }
            }
            $email = domain_whois_vcard($entity, 'email');
            if ($email !== '') {
                $rows[] = array('group' => 'registrar', 'label' => 'Email', 'value' => $email);
            }
            $nested = isset($entity['entities']) && is_array($entity['entities']) ? $entity['entities'] : array();
            foreach ($nested as $child) {
                $childRoles = isset($child['roles']) && is_array($child['roles']) ? $child['roles'] : array();
                if (!in_array('abuse', $childRoles, true)) {
                    continue;
                }
                $abuseEmail = domain_whois_vcard($child, 'email');
                $abusePhone = domain_whois_vcard($child, 'tel');
                if ($abuseEmail !== '') {
                    $rows[] = array('group' => 'registrar', 'label' => 'Abuse email', 'value' => $abuseEmail);
                }
                if ($abusePhone !== '') {
                    $rows[] = array('group' => 'registrar', 'label' => 'Abuse phone', 'value' => $abusePhone);
                }
            }
        }
    }
    return array('status' => 'registered', 'domain' => $domain, 'tld' => 'com', 'rows' => $rows);
}

function domain_whois_lookup($name, $selectedTld)
{
    $target = domain_whois_target($name, $selectedTld);
    if (!$target) {
        return domain_whois_blank();
    }
    if (domain_is_np($target['tld'])) {
        return domain_whois_np($target['label'], $target['tld']);
    }
    return domain_whois_com($target['domain']);
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
    header('Cache-Control: private, no-store');
    header('Content-Disposition: ' . ($ext === 'pdf' ? 'attachment' : 'inline') . '; filename="domain-document.' . $ext . '"');
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

function domain_request_queue($conn, $find = '')
{
    $base = 'SELECT d.*, c.name AS account_name, c.email, c.phone FROM domain_requests d JOIN client_users c ON c.id = d.client_id ';
    $find = admin_find_text($find);
    if ($find !== '') {
        $like = '%' . $find . '%';
        $stmt = $conn->prepare($base . 'WHERE d.domain_name LIKE ? OR c.name LIKE ? OR c.email LIKE ? ORDER BY d.id DESC LIMIT 50');
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
        return $rows;
    }
    $rows = array();
    $seen = array();
    foreach (array(
        $base . "WHERE d.status IN ('requested','paid') ORDER BY d.id DESC",
        $base . 'ORDER BY d.id DESC LIMIT 80'
    ) as $sql) {
        $result = $conn->query($sql);
        if (!$result) {
            continue;
        }
        while ($row = $result->fetch_assoc()) {
            $id = (int) $row['id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
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
        return 'The name could not be checked just now. Try the payment again in a moment.';
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
    billing_tx($conn, 'begin');
    if (!billing_wallet_debit($conn, $clientId, $price)) {
        billing_tx($conn, 'rollback');
        return 'The wallet does not have enough for this year. Add funds, then pay again.';
    }
    $status = 'paid';
    $update = $conn->prepare('UPDATE domain_requests SET status = ? WHERE id = ? AND client_id = ? AND status = \'requested\'');
    $update->bind_param('sii', $status, $requestId, $clientId);
    $update->execute();
    $saved = billing_affected($conn) === 1;
    $update->close();
    if (!$saved) {
        billing_tx($conn, 'rollback');
        return 'The payment could not be saved. The wallet was not charged.';
    }
    try {
        billing_record_entry($conn, $clientId, $price, 'debit', 'purchase', 'completed', 'wallet', 'Domain request ' . $row['domain_name'], 0);
    } catch (Throwable $exception) {
        billing_tx($conn, 'rollback');
        return 'The payment could not be saved. The wallet was not charged.';
    }
    billing_tx($conn, 'commit');
    billing_notify($conn, 'Domain paid: ' . $row['domain_name'], array(
        'A domain request is paid and waiting to be registered.',
        'Domain: ' . $row['domain_name'],
        'Holder: ' . $row['holder_name'],
        'Amount: NPR ' . $price,
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Register the name, then mark it Active in Admin → Domains.'
    ));
    billing_mail_client_event($conn, $clientId, 'domain-paid', array(
        'domain' => $row['domain_name'],
        'amount' => $price
    ));
    return '';
}

function domain_admin_attach($conn, $clientId, $name)
{
    $clientId = (int) $clientId;
    $split = domain_whois_split($name);
    if ($clientId < 1 || !$split) {
        return 'Choose a client and a .com or Nepal name, such as shop.com.np.';
    }
    $check = $conn->prepare('SELECT id, name FROM client_users WHERE id = ?');
    $check->bind_param('i', $clientId);
    $check->execute();
    $client = db_fetch_assoc($check);
    $check->close();
    if (!$client) {
        return 'That client was not found.';
    }
    if (domain_request_open($conn, $split['domain'])) {
        return 'That name already has a request.';
    }
    $bill = domain_year_bill($conn, $split['tld']);
    if ((float) $bill['total'] <= 0) {
        return 'The yearly domain price is not set yet.';
    }
    $holderKind = 'individual';
    $holderName = billing_plain_line($client['name'], 200);
    $holderAddress = 'Registered at the office';
    $document = '';
    $status = 'paid';
    $price = $bill['total'];
    $domain = $split['domain'];
    $tld = $split['tld'];
    $stmt = $conn->prepare('INSERT INTO domain_requests (client_id, domain_name, tld, holder_kind, holder_name, holder_address, document_path, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssssss', $clientId, $domain, $tld, $holderKind, $holderName, $holderAddress, $document, $price, $status);
    $stmt->execute();
    $requestId = (int) $conn->insert_id;
    $stmt->close();
    if ($requestId < 1) {
        return 'The name could not be saved.';
    }
    $marked = domain_mark_request($conn, $requestId, 'active', '');
    if ($marked !== '') {
        $drop = $conn->prepare('DELETE FROM domain_requests WHERE id = ? AND status = \'paid\'');
        $drop->bind_param('i', $requestId);
        $drop->execute();
        $drop->close();
        return $marked;
    }
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
        billing_mail_client_event($conn, (int) $row['client_id'], 'domain-active', array(
            'domain' => $row['domain_name']
        ));
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
        $refundLine = ($row['status'] === 'paid' && (float) $row['price'] > 0)
            ? 'The amount was returned to the wallet.'
            : 'It was not charged.';
        billing_mail_client_event($conn, (int) $row['client_id'], 'domain-closed', array(
            'domain' => $row['domain_name'],
            'refund' => $refundLine,
            'note' => $note
        ));
        return '';
    }
    return 'That decision is not available.';
}
