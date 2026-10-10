<?php
require_once __DIR__ . '/../../config.php';
if (function_exists('sms_lazy_resume') && isset($conn) && $conn) {
    sms_lazy_resume($conn);
}
require_client();
terms_gate_client($conn, (int) get_client_id());
try {
    billing_process_renewals($conn, (int) get_client_id());
} catch (Throwable $exception) {
    error_log('Client renewal check could not run.');
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Panel — Aakash Technologies</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preload" href="../assets/fonts/inter-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="../assets/fonts/space-grotesk-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <?= isset($conn) ? site_favicon_html($conn, '../') : '' ?>
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/portal-polish.css">
    <link rel="stylesheet" href="../assets/css/sms-dash.css">
    <link rel="stylesheet" href="../assets/css/service-ui.css">
    <link rel="stylesheet" href="../assets/css/kyc.css">
    <link rel="stylesheet" href="../assets/css/ui-shared.css">
    <script defer src="../assets/vendor/lucide-0.383.0.min.js"></script>
    <script defer src="../assets/vendor/alpine-3.14.9.min.js"></script>
    <script defer src="../assets/js/portal.js"></script>
    <script defer src="../assets/js/forms.js"></script>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
</head>
<body class="portal-shell font-body bg-dark-950 text-slate-300 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen=false" style="display:none;"></div>
