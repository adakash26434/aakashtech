<?php
require_once __DIR__ . '/../config.php';

$token = isset($_GET['token']) ? (string) $_GET['token'] : '';
$clientId = email_verify_consume($conn, $token);
if ($clientId > 0) {
    // The welcome notice goes out only once the address is confirmed.
    billing_mail_client_event($conn, $clientId, 'account');
    flash('login_notice', 'Email confirmed. You can sign in now.');
    header('Location: login.php');
    exit;
}
header('Location: resend-verify.php?expired=1');
exit;
