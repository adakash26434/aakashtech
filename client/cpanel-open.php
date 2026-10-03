<?php
require_once __DIR__ . '/../config.php';
require_client();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: services.php');
    exit;
}
verify_csrf();
$cid = (int) get_client_id();
$serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
$row = hosting_panel_for_client($conn, $cid, $serviceId);
if (!$row) {
    header('Location: services.php');
    exit;
}
$host = hosting_panel_domain($row);
$action = 'https://' . $host . ':2083/login/';
$user = (string) $row['panel_user'];
$pass = panel_pass_open($conn, (string) $row['panel_pass']);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: base-uri 'none'; form-action https:; frame-ancestors 'none'; object-src 'none'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>cPanel — Aakash Technologies</title>
</head>
<body>
    <p>Opening cPanel…</p>
    <form id="panel-login" method="POST" action="<?= e($action) ?>" autocomplete="off">
        <input type="hidden" name="user" value="<?= e($user) ?>" autocomplete="off">
        <input type="hidden" name="pass" value="<?= e($pass) ?>" autocomplete="off">
        <button type="submit">Continue to cPanel</button>
    </form>
    <script>document.getElementById('panel-login').submit();</script>
</body>
</html>
