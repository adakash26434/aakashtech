<?php
require_once __DIR__ . '/../config.php';
require_client();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function sms_import_json($ok, $error, $numbers, $count)
{
    echo json_encode(array(
        'ok' => $ok,
        'error' => $error,
        'numbers' => $numbers,
        'count' => $count
    ));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sms_import_json(false, 'Upload the file again.', '', 0);
}
verify_csrf();
if (!isset($_FILES['sheet']) || !is_array($_FILES['sheet'])) {
    sms_import_json(false, 'Choose an Excel or CSV file.', '', 0);
}
$file = $_FILES['sheet'];
if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
    sms_import_json(false, 'That file did not upload. Try a smaller Excel or CSV file.', '', 0);
}
if ((int) $file['size'] > 2097152) {
    sms_import_json(false, 'Keep the file under 2 MB.', '', 0);
}
$imported = sms_import_file($file['tmp_name'], isset($file['name']) ? $file['name'] : '');
sms_import_json(!empty($imported['ok']), $imported['error'], $imported['numbers'], (int) $imported['count']);
