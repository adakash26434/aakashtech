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
    foreach (array('doc_identity_back', 'doc_clearance', 'doc_photo') as $name) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE client_kyc ADD COLUMN ' . $name . ' ' . $definition);
    }
    if (!isset($present['details'])) {
        // Every detailed answer (gender, family, addresses, dates ...) as JSON; see kyc-fields.php.
        billing_exec($conn, 'ALTER TABLE client_kyc ADD COLUMN details TEXT' . (DB_DRIVER === 'sqlite' ? " DEFAULT ''" : ''));
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
        'doc_photo' => '',
        'details' => '',
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
    return array_key_exists((string) $value, kyc_id_kinds()) ? (string) $value : 'citizenship';
}

function billing_kyc_id_label($value)
{
    $kinds = kyc_id_kinds();
    return $kinds[billing_kyc_id_kind($value)];
}

function billing_kyc_reference($value, $max)
{
    $value = strtoupper(billing_plain_line($value, $max));
    $value = preg_replace('/[^A-Z0-9\/-]/', '', $value);
    return is_string($value) ? $value : '';
}

/**
 * Where new identity documents are kept. This folder sits outside the web root (on cPanel,
 * one level above public_html), so a file can never be fetched by URL. Documents are read
 * only through kyc-file.php, which checks the signed-in role first.
 */
function billing_kyc_root()
{
    if (defined('KYC_STORAGE_DIR') && (string) KYC_STORAGE_DIR !== '') {
        return rtrim((string) KYC_STORAGE_DIR, '/');
    }
    return dirname(__DIR__, 3) . '/private-kyc';
}

function billing_kyc_safe_path($clientId, $relative)
{
    $clientId = (int) $clientId;
    $relative = str_replace('\\', '/', (string) $relative);
    $ext = '(pdf|jpg|png|webp)';
    if (preg_match('#^kyc/' . $clientId . '/[a-f0-9]{32}\.' . $ext . '$#', $relative)) {
        $full = billing_kyc_root() . '/' . $relative;
    } elseif (preg_match('#^uploads/kyc/' . $clientId . '/[a-f0-9]{32}\.' . $ext . '$#', $relative)) {
        // Documents saved before the move. They stay readable until they are moved across.
        $full = dirname(__DIR__, 2) . '/' . $relative;
    } else {
        return '';
    }
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
    $slots = array('identity' => true, 'identity_back' => true, 'registration' => true, 'tax' => true, 'authority' => true, 'clearance' => true, 'photo' => true);
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
    $dir = billing_kyc_root() . '/kyc/' . $clientId;
    if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
        return array('ok' => false, 'error' => 'The document folder could not be created.');
    }
    $relative = 'kyc/' . $clientId . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
    $target = billing_kyc_root() . '/' . $relative;
    $moved = !empty($GLOBALS['KYC_TEST_UPLOADS']) ? copy($file['tmp_name'], $target) : move_uploaded_file($file['tmp_name'], $target);
    if (!$moved) {
        return array('ok' => false, 'error' => 'The document could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

/** All answers for one client as one flat list: the saved details, then the plain columns. */
function billing_kyc_values($row)
{
    $values = array();
    $decoded = isset($row['details']) && $row['details'] !== '' ? json_decode((string) $row['details'], true) : null;
    if (is_array($decoded)) {
        $values = $decoded;
    }
    foreach (array('full_name', 'id_kind', 'id_number', 'org_name', 'registration_number', 'tax_number', 'contact_name', 'contact_id_kind', 'contact_id_number', 'purpose') as $key) {
        if (!isset($values[$key]) && isset($row[$key]) && $row[$key] !== '') {
            $values[$key] = (string) $row[$key];
        }
    }
    return $values;
}

/** Writes the named columns for one client (insert or update). Column names come from this file only. */
function billing_kyc_save($conn, $clientId, $columns)
{
    $clientId = (int) $clientId;
    $allowed = array('account_kind', 'status', 'purpose', 'full_name', 'id_kind', 'id_number', 'address', 'org_name', 'registration_number', 'tax_number', 'contact_name', 'contact_id_kind', 'contact_id_number',
        'doc_identity', 'doc_registration', 'doc_tax', 'doc_authority', 'doc_identity_back', 'doc_clearance', 'doc_photo', 'details', 'admin_note', 'submitted_at', 'reviewed_at');
    $columns = array_intersect_key($columns, array_flip($allowed));
    if (!$columns) {
        return false;
    }
    $probe = $conn->prepare('SELECT client_id FROM client_kyc WHERE client_id = ?');
    $probe->bind_param('i', $clientId);
    $probe->execute();
    $exists = (bool) db_fetch_assoc($probe);
    $probe->close();
    $names = array_keys($columns);
    $values = array_values($columns);
    if ($exists) {
        $stmt = $conn->prepare('UPDATE client_kyc SET ' . implode(' = ?, ', $names) . ' = ? WHERE client_id = ?');
        $types = str_repeat('s', count($values)) . 'i';
        $values[] = $clientId;
    } else {
        $stmt = $conn->prepare('INSERT INTO client_kyc (client_id, ' . implode(', ', $names) . ') VALUES (?' . str_repeat(', ?', count($names)) . ')');
        $types = 'i' . str_repeat('s', count($values));
        array_unshift($values, $clientId);
    }
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param($types, ...$values);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool) $ok;
}

/**
 * Checks and saves a submission. Returns array('ok' => bool, 'errors' => field => message, 'message' => text, 'values' => cleaned answers).
 * Answers are saved even when something is missing, so nothing typed is lost; the status only becomes
 * "pending" when every answer and document is complete.
 */
function billing_kyc_submit_detailed($conn, $clientId, $post, $files)
{
    $clientId = (int) $clientId;
    $current = billing_kyc_load($conn, $clientId);
    if ($current['status'] === 'approved') {
        return array('ok' => false, 'errors' => array(), 'values' => array(), 'message' => 'This identity is approved. It can no longer be changed from the client portal. Contact support for a change.');
    }
    $kind = isset($post['account_kind']) && $post['account_kind'] === 'organization' ? 'organization' : 'individual';
    $check = kyc_validate($kind, $post);
    $values = $check['values'];
    $errors = $check['errors'];
    $idKind = $kind === 'organization' ? $check['contact_kind'] : $check['id_kind'];

    $map = array('identity' => 'doc_identity', 'identity_back' => 'doc_identity_back', 'registration' => 'doc_registration', 'tax' => 'doc_tax', 'authority' => 'doc_authority', 'clearance' => 'doc_clearance', 'photo' => 'doc_photo');
    $docs = array();
    foreach ($map as $slot => $column) {
        $docs[$slot] = (string) $current[$column];
    }
    $wanted = array();
    foreach (kyc_doc_spec($kind, $idKind) as $spec) {
        $wanted[$spec['slot']] = $spec;
    }
    $docErrors = array();
    foreach ($wanted as $slot => $spec) {
        // A photo taken with the camera and a file chosen from the phone use different inputs; either one counts.
        $upload = isset($files['doc_' . $slot]) ? $files['doc_' . $slot] : array();
        if ((!is_array($upload) || !isset($upload['error']) || (int) $upload['error'] === UPLOAD_ERR_NO_FILE) && isset($files['cam_' . $slot])) {
            $upload = $files['cam_' . $slot];
        }
        $stored = billing_kyc_store_file($clientId, $slot, $upload);
        if (!$stored['ok']) {
            $docErrors['doc_' . $slot] = $stored['error'];
            continue;
        }
        if ($stored['path']) {
            if ($docs[$slot] !== '' && $docs[$slot] !== $stored['path']) {
                billing_kyc_unlink($clientId, $docs[$slot]);
            }
            $docs[$slot] = $stored['path'];
        }
        if (!empty($spec['required']) && ($docs[$slot] === '' || billing_kyc_safe_path($clientId, $docs[$slot]) === '')) {
            $docErrors['doc_' . $slot] = 'Add the ' . strtolower($spec['label']) . '.';
        }
    }
    // Documents that the chosen account type does not use are removed.
    foreach ($map as $slot => $column) {
        if (!isset($wanted[$slot]) && $docs[$slot] !== '') {
            billing_kyc_unlink($clientId, $docs[$slot]);
            $docs[$slot] = '';
        }
    }
    $allErrors = array_merge($errors, $docErrors);
    $complete = !$allErrors;

    $columns = array(
        'account_kind' => $kind,
        'status' => $complete ? 'pending' : ($current['status'] === 'rejected' ? 'rejected' : ($current['status'] === 'pending' ? 'pending' : '')),
        'purpose' => isset($values['purpose']) ? $values['purpose'] : '',
        'full_name' => $kind === 'individual' && isset($values['full_name']) ? $values['full_name'] : '',
        'id_kind' => $kind === 'individual' ? $check['id_kind'] : 'citizenship',
        'id_number' => $kind === 'individual' && isset($values['id_number']) ? $values['id_number'] : '',
        'address' => kyc_address_line($values, 'perm'),
        'org_name' => $kind === 'organization' && isset($values['org_name']) ? $values['org_name'] : '',
        'registration_number' => $kind === 'organization' && isset($values['registration_number']) ? $values['registration_number'] : '',
        'tax_number' => $kind === 'organization' && isset($values['tax_number']) ? $values['tax_number'] : '',
        'contact_name' => $kind === 'organization' && isset($values['contact_name']) ? $values['contact_name'] : '',
        'contact_id_kind' => $kind === 'organization' ? $check['contact_kind'] : 'citizenship',
        'contact_id_number' => $kind === 'organization' && isset($values['contact_id_number']) ? $values['contact_id_number'] : '',
        'doc_identity' => $docs['identity'], 'doc_identity_back' => $docs['identity_back'], 'doc_registration' => $docs['registration'], 'doc_tax' => $docs['tax'],
        'doc_authority' => $docs['authority'], 'doc_clearance' => $docs['clearance'], 'doc_photo' => $docs['photo'],
        'details' => json_encode($values, JSON_UNESCAPED_UNICODE)
    );
    if ($complete) {
        $columns['submitted_at'] = date('Y-m-d H:i:s');
        $columns['admin_note'] = '';
    }
    if (!billing_kyc_save($conn, $clientId, $columns)) {
        return array('ok' => false, 'errors' => $allErrors, 'values' => $values, 'message' => 'The identity could not be saved. Please try again.');
    }
    if (!$complete) {
        $first = reset($allErrors);
        return array('ok' => false, 'errors' => $allErrors, 'values' => $values, 'message' => 'Some details need attention (' . count($allErrors) . '). What you entered is saved: ' . $first);
    }
    $who = $kind === 'individual' ? $values['full_name'] : $values['org_name'];
    billing_mail_client_event($conn, $clientId, 'kyc-received');
    billing_notify($conn, 'Identity waiting for approval', array(
        'A client submitted identity details.',
        'Account: ' . ($kind === 'individual' ? 'Individual' : 'Organization'),
        'Name: ' . $who,
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Open Admin → Identity.'
    ));
    return array('ok' => true, 'errors' => array(), 'values' => $values, 'message' => '');
}

/** Older callers: the first message, or an empty string when all went well. */
function billing_kyc_submit($conn, $clientId, $post, $files)
{
    $result = billing_kyc_submit_detailed($conn, $clientId, $post, $files);
    return $result['ok'] ? '' : $result['message'];
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

function billing_kyc_queue($conn, $find = '', $status = '')
{
    $base = 'SELECT k.*, c.name AS account_name, c.email, c.phone FROM client_kyc k JOIN client_users c ON c.id = k.client_id ';
    $find = admin_find_text($find);
    $where = array("k.status <> ''");
    $types = '';
    $values = array();
    if (in_array($status, array('pending', 'approved', 'rejected'), true)) {
        $where[] = 'k.status = ?';
        $types .= 's';
        $values[] = $status;
    }
    if ($find !== '') {
        $like = '%' . $find . '%';
        $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR k.full_name LIKE ? OR k.org_name LIKE ? OR k.id_number LIKE ? OR k.registration_number LIKE ? OR k.tax_number LIKE ? OR k.details LIKE ?)';
        $types .= 'sssssssss';
        for ($n = 0; $n < 9; $n++) {
            $values[] = $like;
        }
    }
    $stmt = $conn->prepare($base . 'WHERE ' . implode(' AND ', $where) . " ORDER BY CASE WHEN k.status = 'pending' THEN 0 ELSE 1 END, k.submitted_at DESC LIMIT 80");
    if ($values) {
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $rows = db_fetch_all($stmt) ?: array();
    $stmt->close();
    return $rows;
}

/** How many identities are in each state (for the tabs). */
function billing_kyc_counts($conn)
{
    $counts = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
    $result = $conn->query("SELECT status, COUNT(*) AS n FROM client_kyc WHERE status <> '' GROUP BY status");
    while ($result && ($row = $result->fetch_assoc())) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int) $row['n'];
        }
    }
    $counts['all'] = array_sum($counts);
    return $counts;
}

/**
 * What the reviewer should look at: missing documents, repeated numbers, a name that does not
 * match the account, old-style submissions. tone: bad, warn, ok.
 */
function billing_kyc_flags($conn, $row)
{
    $flags = array();
    $clientId = (int) $row['client_id'];
    $kind = $row['account_kind'] === 'organization' ? 'organization' : 'individual';
    $idKind = $kind === 'organization' ? $row['contact_id_kind'] : $row['id_kind'];
    $columns = array('identity' => 'doc_identity', 'identity_back' => 'doc_identity_back', 'registration' => 'doc_registration', 'tax' => 'doc_tax', 'authority' => 'doc_authority', 'clearance' => 'doc_clearance', 'photo' => 'doc_photo');
    $missing = array();
    foreach (kyc_doc_spec($kind, $idKind) as $spec) {
        if (!empty($spec['required']) && billing_kyc_safe_path($clientId, (string) $row[$columns[$spec['slot']]]) === '') {
            $missing[] = $spec['label'];
        }
    }
    if ($missing) {
        $flags[] = array('tone' => 'bad', 'text' => 'Missing: ' . implode(', ', $missing) . '.');
    }
    if ($row['details'] === '' || $row['details'] === null) {
        $flags[] = array('tone' => 'warn', 'text' => 'Submitted with the older, shorter form. Gender, family, issue date and addresses are not on file. Ask the client to update before approving.');
    }
    foreach (array('id_number' => 'document number', 'contact_id_number' => 'authorized person\'s document number', 'registration_number' => 'registration number', 'tax_number' => 'PAN number') as $column => $label) {
        $same = billing_kyc_duplicates($conn, $clientId, $column, (string) $row[$column]);
        if ($same) {
            $names = array();
            foreach ($same as $other) {
                $names[] = '<a href="client.php?id=' . (int) $other['client_id'] . '">' . htmlspecialchars((string) $other['name'], ENT_QUOTES, 'UTF-8') . '</a>';
            }
            $flags[] = array('tone' => 'warn', 'html' => true, 'text' => 'The same ' . $label . ' is also on: ' . implode(', ', $names) . '.');
        }
    }
    $account = strtolower(preg_replace('/[^a-z ]/i', ' ', (string) $row['account_name']));
    $given = strtolower(preg_replace('/[^a-z ]/i', ' ', $kind === 'organization' ? (string) $row['org_name'] . ' ' . (string) $row['contact_name'] : (string) $row['full_name']));
    $shared = array_intersect(array_filter(explode(' ', $account), function ($w) { return strlen($w) > 2; }), explode(' ', $given));
    if (trim($account) !== '' && trim($given) !== '' && !$shared) {
        $flags[] = array('tone' => 'warn', 'text' => 'The name on the account ("' . $row['account_name'] . '") shares no word with the name given here. This can be fine; check the document.');
    }
    $values = billing_kyc_values($row);
    $mobile = isset($values['mobile']) ? (string) $values['mobile'] : '';
    $phone = kyc_mobile_normalise((string) $row['phone']);
    if ($mobile !== '' && $phone !== '' && $mobile !== $phone) {
        $flags[] = array('tone' => 'warn', 'text' => 'The mobile number here (' . $mobile . ') differs from the account phone (' . $phone . ').');
    }
    if (!$flags) {
        $flags[] = array('tone' => 'ok', 'text' => 'All required documents are present and nothing looks repeated or mismatched.');
    }
    return $flags;
}

function billing_kyc_send($conn, $clientId, $slot)
{
    $map = array(
        'identity' => 'doc_identity',
        'identity_back' => 'doc_identity_back',
        'registration' => 'doc_registration',
        'tax' => 'doc_tax',
        'authority' => 'doc_authority',
        'clearance' => 'doc_clearance',
        'photo' => 'doc_photo'
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
    // PDFs can carry active content, so they are always downloaded, never rendered on this site.
    $showHere = $ext !== 'pdf';
    header('Content-Disposition: ' . ($showHere ? 'inline' : 'attachment') . '; filename="identity-document.' . $ext . '"');
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

/** Other accounts that gave the same document, registration or PAN number (a check for the reviewer). */
function billing_kyc_duplicates($conn, $clientId, $column, $value)
{
    $columns = array('id_number', 'registration_number', 'tax_number', 'contact_id_number');
    if (!in_array($column, $columns, true) || trim((string) $value) === '') {
        return array();
    }
    $clientId = (int) $clientId;
    $value = (string) $value;
    $stmt = $conn->prepare('SELECT k.client_id, c.name FROM client_kyc k JOIN client_users c ON c.id = k.client_id WHERE k.' . $column . ' = ? AND k.client_id <> ? LIMIT 5');
    $stmt->bind_param('si', $value, $clientId);
    $stmt->execute();
    $rows = db_fetch_all($stmt) ?: array();
    $stmt->close();
    return $rows;
}
