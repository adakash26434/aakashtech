<?php
/**
 * Billing: Identity (KYC) submission, storage and review.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

function billing_ensure_kyc_table($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_kyc (
            client_id INTEGER PRIMARY KEY,
            account_kind TEXT DEFAULT '',
            status TEXT DEFAULT '',
            purpose TEXT DEFAULT '',
            full_name TEXT DEFAULT '',
            id_kind TEXT DEFAULT '',
            id_number TEXT DEFAULT '',
            address TEXT DEFAULT '',
            org_name TEXT DEFAULT '',
            registration_number TEXT DEFAULT '',
            tax_number TEXT DEFAULT '',
            contact_name TEXT DEFAULT '',
            contact_id_kind TEXT DEFAULT '',
            contact_id_number TEXT DEFAULT '',
            doc_identity TEXT DEFAULT '',
            doc_registration TEXT DEFAULT '',
            doc_tax TEXT DEFAULT '',
            doc_authority TEXT DEFAULT '',
            doc_identity_back TEXT DEFAULT '',
            doc_clearance TEXT DEFAULT '',
            admin_note TEXT DEFAULT '',
            submitted_at TEXT DEFAULT NULL,
            reviewed_at TEXT DEFAULT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_kyc_columns($conn);
        return;
    }
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_kyc (
        client_id INT NOT NULL PRIMARY KEY,
        account_kind VARCHAR(20) DEFAULT '',
        status VARCHAR(20) DEFAULT '',
        purpose TEXT,
        full_name VARCHAR(160) DEFAULT '',
        id_kind VARCHAR(40) DEFAULT '',
        id_number VARCHAR(80) DEFAULT '',
        address TEXT,
        org_name VARCHAR(200) DEFAULT '',
        registration_number VARCHAR(80) DEFAULT '',
        tax_number VARCHAR(80) DEFAULT '',
        contact_name VARCHAR(160) DEFAULT '',
        contact_id_kind VARCHAR(40) DEFAULT '',
        contact_id_number VARCHAR(80) DEFAULT '',
        doc_identity VARCHAR(255) DEFAULT '',
        doc_registration VARCHAR(255) DEFAULT '',
        doc_tax VARCHAR(255) DEFAULT '',
        doc_authority VARCHAR(255) DEFAULT '',
        doc_identity_back VARCHAR(255) DEFAULT '',
        doc_clearance VARCHAR(255) DEFAULT '',
        admin_note TEXT,
        submitted_at DATETIME DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_kyc_columns($conn);
}

function billing_kyc_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'client_kyc'));
    if (!$present) {
        return;
    }
    $definition = DB_DRIVER === 'sqlite' ? "TEXT DEFAULT ''" : "VARCHAR(255) DEFAULT ''";
    foreach (array('doc_identity_back', 'doc_clearance') as $name) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE client_kyc ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_kyc_blank()
{
    return array(
        'client_id' => 0,
        'account_kind' => 'individual',
        'status' => '',
        'purpose' => '',
        'full_name' => '',
        'id_kind' => 'citizenship',
        'id_number' => '',
        'address' => '',
        'org_name' => '',
        'registration_number' => '',
        'tax_number' => '',
        'contact_name' => '',
        'contact_id_kind' => 'citizenship',
        'contact_id_number' => '',
        'doc_identity' => '',
        'doc_registration' => '',
        'doc_tax' => '',
        'doc_authority' => '',
        'doc_identity_back' => '',
        'doc_clearance' => '',
        'admin_note' => '',
        'submitted_at' => '',
        'reviewed_at' => ''
    );
}

function billing_kyc_load($conn, $clientId)
{
    $clientId = (int) $clientId;
    $blank = billing_kyc_blank();
    $blank['client_id'] = $clientId;
    $stmt = $conn->prepare('SELECT * FROM client_kyc WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return $blank;
    }
    foreach ($blank as $key => $value) {
        if (isset($row[$key]) && $row[$key] !== null) {
            $blank[$key] = $row[$key];
        }
    }
    if ($blank['account_kind'] !== 'organization') {
        $blank['account_kind'] = 'individual';
    }
    return $blank;
}

function billing_kyc_approved($conn, $clientId)
{
    $row = billing_kyc_load($conn, $clientId);
    return $row['status'] === 'approved';
}

function billing_kyc_id_kind($value)
{
    return $value === 'national_id' ? 'national_id' : 'citizenship';
}

function billing_kyc_id_label($value)
{
    return billing_kyc_id_kind($value) === 'national_id' ? 'National Identity Card' : 'Citizenship certificate';
}

function billing_kyc_reference($value, $max)
{
    $value = strtoupper(billing_plain_line($value, $max));
    $value = preg_replace('/[^A-Z0-9\/-]/', '', $value);
    return is_string($value) ? $value : '';
}

function billing_kyc_safe_path($clientId, $relative)
{
    $clientId = (int) $clientId;
    $relative = str_replace('\\', '/', (string) $relative);
    if (!preg_match('#^uploads/kyc/' . $clientId . '/[a-f0-9]{32}\.(pdf|jpg|png|webp)$#', $relative)) {
        return '';
    }
    $full = dirname(__DIR__) . '/' . $relative;
    return is_file($full) ? $full : '';
}

function billing_kyc_unlink($clientId, $relative)
{
    $full = billing_kyc_safe_path($clientId, $relative);
    if ($full !== '') {
        unlink($full);
    }
}

function billing_kyc_store_file($clientId, $slot, $file)
{
    $clientId = (int) $clientId;
    $slots = array('identity' => true, 'identity_back' => true, 'registration' => true, 'tax' => true, 'authority' => true, 'clearance' => true);
    if (!isset($slots[$slot])) {
        return array('ok' => false, 'error' => 'That document is not accepted.');
    }
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The document could not be uploaded.');
    }
    if ((int) $file['size'] > 5242880) {
        return array('ok' => false, 'error' => 'Each document must be smaller than 5 MB.');
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
    $dir = dirname(__DIR__) . '/uploads/kyc/' . $clientId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The document folder could not be created.');
    }
    $guard = $dir . '/.htaccess';
    if (!is_file($guard)) {
        file_put_contents($guard, "Require all denied\nDeny from all\n");
    }
    $relative = 'uploads/kyc/' . $clientId . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $relative)) {
        return array('ok' => false, 'error' => 'The document could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function billing_kyc_write($conn, $clientId, $status, $kind, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs, $note, $submitted)
{
    $clientId = (int) $clientId;
    $probe = $conn->prepare('SELECT client_id FROM client_kyc WHERE client_id = ?');
    if (!$probe) {
        return false;
    }
    $probe->bind_param('i', $clientId);
    $probe->execute();
    $exists = (bool) db_fetch_assoc($probe);
    $probe->close();
    if ($exists) {
        $stmt = $conn->prepare('UPDATE client_kyc SET account_kind = ?, status = ?, purpose = ?, full_name = ?, id_kind = ?, id_number = ?, address = ?, org_name = ?, registration_number = ?, tax_number = ?, contact_name = ?, contact_id_kind = ?, contact_id_number = ?, doc_identity = ?, doc_registration = ?, doc_tax = ?, doc_authority = ?, doc_identity_back = ?, doc_clearance = ?, admin_note = ?, submitted_at = NULLIF(?, \'\') WHERE client_id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sssssssssssssssssssssi', $kind, $status, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs['identity'], $docs['registration'], $docs['tax'], $docs['authority'], $docs['identity_back'], $docs['clearance'], $note, $submitted, $clientId);
    } else {
        $stmt = $conn->prepare('INSERT INTO client_kyc (client_id, account_kind, status, purpose, full_name, id_kind, id_number, address, org_name, registration_number, tax_number, contact_name, contact_id_kind, contact_id_number, doc_identity, doc_registration, doc_tax, doc_authority, doc_identity_back, doc_clearance, admin_note, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, \'\'))');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('isssssssssssssssssssss', $clientId, $kind, $status, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs['identity'], $docs['registration'], $docs['tax'], $docs['authority'], $docs['identity_back'], $docs['clearance'], $note, $submitted);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function billing_kyc_submit($conn, $clientId, $post, $files)
{
    $clientId = (int) $clientId;
    $current = billing_kyc_load($conn, $clientId);
    if ($current['status'] === 'approved') {
        return 'This identity is approved. It can no longer be changed from the client portal.';
    }
    $kind = isset($post['account_kind']) && $post['account_kind'] === 'organization' ? 'organization' : 'individual';
    $purpose = billing_plain_block(isset($post['purpose']) ? $post['purpose'] : '', 500);
    $address = billing_plain_block(isset($post['address']) ? $post['address'] : '', 300);
    if (strlen($purpose) < 12) {
        return 'Write what the SMS and voice calls are for.';
    }
    if (strlen($address) < 8) {
        return 'Enter the address.';
    }
    $fullName = '';
    $idKind = 'citizenship';
    $idNumber = '';
    $orgName = '';
    $registration = '';
    $tax = '';
    $contactName = '';
    $contactKind = 'citizenship';
    $contactNumber = '';
    $docs = array(
        'identity' => (string) $current['doc_identity'],
        'identity_back' => (string) $current['doc_identity_back'],
        'registration' => (string) $current['doc_registration'],
        'tax' => (string) $current['doc_tax'],
        'authority' => (string) $current['doc_authority'],
        'clearance' => (string) $current['doc_clearance']
    );
    if ($kind === 'individual') {
        $fullName = billing_plain_line(isset($post['full_name']) ? $post['full_name'] : '', 160);
        $idKind = 'national_id';
        $idNumber = billing_kyc_reference(isset($post['id_number']) ? $post['id_number'] : '', 40);
        if (strlen($fullName) < 3) {
            return 'Enter the name as it appears on the citizenship certificate.';
        }
        if (strlen($idNumber) < 5) {
            return 'Enter the National Identity Card number.';
        }
    } else {
        $orgName = billing_plain_line(isset($post['org_name']) ? $post['org_name'] : '', 200);
        $registration = billing_kyc_reference(isset($post['registration_number']) ? $post['registration_number'] : '', 40);
        $tax = billing_kyc_reference(isset($post['tax_number']) ? $post['tax_number'] : '', 40);
        if (strlen($orgName) < 2) {
            return 'Enter the company name.';
        }
        if (strlen($registration) < 3) {
            return 'Enter the company registration number.';
        }
        if (strlen($tax) < 3) {
            return 'Enter the PAN number.';
        }
    }
    $needed = $kind === 'individual' ? array('identity', 'identity_back') : array('registration', 'tax', 'clearance');
    $labels = array(
        'identity' => 'citizenship certificate, front',
        'identity_back' => 'citizenship certificate, back',
        'registration' => 'company registration certificate',
        'tax' => 'PAN certificate',
        'clearance' => 'latest tax clearance'
    );
    $stop = '';
    foreach ($needed as $slot) {
        $stored = billing_kyc_store_file($clientId, $slot, isset($files['doc_' . $slot]) ? $files['doc_' . $slot] : array());
        if (!$stored['ok']) {
            $stop = $stored['error'];
            break;
        }
        if ($stored['path']) {
            if ($docs[$slot] !== '' && $docs[$slot] !== $stored['path']) {
                billing_kyc_unlink($clientId, $docs[$slot]);
            }
            $docs[$slot] = $stored['path'];
        }
        if ($docs[$slot] === '' || billing_kyc_safe_path($clientId, $docs[$slot]) === '') {
            $stop = 'Upload the ' . $labels[$slot] . '.';
            break;
        }
    }
    if ($stop !== '') {
        billing_kyc_write($conn, $clientId, $current['status'], $kind, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs, (string) $current['admin_note'], (string) $current['submitted_at']);
        return $stop;
    }
    $drop = $kind === 'individual' ? array('registration', 'tax', 'authority', 'clearance') : array('identity', 'identity_back');
    foreach ($drop as $slot) {
        if ($docs[$slot] !== '') {
            billing_kyc_unlink($clientId, $docs[$slot]);
            $docs[$slot] = '';
        }
    }
    $status = 'pending';
    $note = '';
    $submitted = date('Y-m-d H:i:s');
    if (!billing_kyc_write($conn, $clientId, $status, $kind, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs, $note, $submitted)) {
        return 'The identity could not be saved. The details you typed are still in the form.';
    }
    $who = $kind === 'individual' ? $fullName : $orgName;
    billing_mail_client_event($conn, $clientId, 'kyc-received');
    billing_notify($conn, 'Identity waiting for approval', array(
        'A client submitted identity details.',
        'Account: ' . ($kind === 'individual' ? 'Individual' : 'Organization'),
        'Name: ' . $who,
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Open Admin → Identity.'
    ));
    return '';
}

function billing_kyc_decide($conn, $clientId, $decision, $note)
{
    $clientId = (int) $clientId;
    $current = billing_kyc_load($conn, $clientId);
    if ($current['status'] === '') {
        return 'That identity has not been submitted.';
    }
    $reviewed = date('Y-m-d H:i:s');
    if ($decision === 'approve') {
        if ($current['status'] === 'approved') {
            return '';
        }
        if ($current['status'] !== 'pending') {
            return 'Only a submitted identity can be approved.';
        }
        $status = 'approved';
        $note = '';
    } elseif ($decision === 'reject') {
        $note = billing_plain_block($note, 400);
        if (strlen($note) < 5) {
            return 'Write why it needs a change.';
        }
        $status = 'rejected';
    } else {
        return 'That decision is not available.';
    }
    $stmt = $conn->prepare('UPDATE client_kyc SET status = ?, admin_note = ?, reviewed_at = ? WHERE client_id = ?');
    $stmt->bind_param('sssi', $status, $note, $reviewed, $clientId);
    $stmt->execute();
    $stmt->close();
    if ($decision === 'approve') {
        billing_mail_client_event($conn, $clientId, 'kyc-approved');
    } else {
        billing_mail_client_event($conn, $clientId, 'kyc-change', array('note' => $note));
    }
    return '';
}

function billing_kyc_queue($conn, $find = '')
{
    $base = 'SELECT k.*, c.name AS account_name, c.email, c.phone FROM client_kyc k JOIN client_users c ON c.id = k.client_id ';
    $find = admin_find_text($find);
    if ($find !== '') {
        $like = '%' . $find . '%';
        $stmt = $conn->prepare($base . 'WHERE c.name LIKE ? OR c.email LIKE ? OR k.full_name LIKE ? OR k.org_name LIKE ? ORDER BY k.submitted_at DESC LIMIT 50');
        $stmt->bind_param('ssss', $like, $like, $like, $like);
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
        return $rows;
    }
    $rows = array();
    $seen = array();
    foreach (array(
        $base . "WHERE k.status = 'pending' ORDER BY k.submitted_at DESC",
        $base . 'ORDER BY k.submitted_at DESC LIMIT 80'
    ) as $sql) {
        $result = $conn->query($sql);
        if (!$result) {
            continue;
        }
        while ($row = $result->fetch_assoc()) {
            $id = (int) $row['client_id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $rows[] = $row;
        }
    }
    return $rows;
}

function billing_kyc_send($conn, $clientId, $slot)
{
    $map = array(
        'identity' => 'doc_identity',
        'identity_back' => 'doc_identity_back',
        'registration' => 'doc_registration',
        'tax' => 'doc_tax',
        'authority' => 'doc_authority',
        'clearance' => 'doc_clearance'
    );
    if (!isset($map[$slot])) {
        http_response_code(404);
        exit;
    }
    $row = billing_kyc_load($conn, (int) $clientId);
    $full = billing_kyc_safe_path((int) $clientId, isset($row[$map[$slot]]) ? $row[$map[$slot]] : '');
    if ($full === '') {
        http_response_code(404);
        exit;
    }
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $types = array('pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
    if (!isset($types[$ext])) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $types[$ext]);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Content-Disposition: ' . ($ext === 'pdf' ? 'attachment' : 'inline') . '; filename="identity-document.' . $ext . '"');
    header('Content-Length: ' . (string) filesize($full));
    readfile($full);
    exit;
}

function billing_client_has_messaging($conn, $clientId)
{
    $clientId = (int) $clientId;
    $balances = billing_unit_balances($conn, $clientId);
    if ($balances['sms'] > 0 || $balances['voice_calls'] > 0 || $balances['voice_minutes'] > 0) {
        return true;
    }
    $stmt = $conn->prepare("SELECT id FROM client_services WHERE client_id = ? AND status = 'active' AND unit_kind IN ('sms', 'voice_calls', 'voice_minutes') LIMIT 1");
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return (bool) $row;
}
