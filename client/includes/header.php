<?php
require_once __DIR__ . '/../../config.php';
require_client();
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/portal-polish.css">
    <script defer src="https://unpkg.com/lucide@0.383.0"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="../assets/js/portal.js"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { heading: ['Space Grotesk', 'sans-serif'], body: ['Inter', 'sans-serif'] },
            colors: {
                brand: { 50:'#e5f5f0',100:'#d2eee6',200:'#a8dfd0',300:'#79cdb7',400:'#43b39a',500:'#0b8b7a',600:'#087365',700:'#075e54' },
                dark: { 200:'#536b63',300:'#344b44',700:'#e5f5f0',800:'#d2eee6',900:'#ffffff',950:'#f4f8f6' }
            }
        }}}
    </script>
</head>
<body class="portal-shell font-body bg-dark-950 text-slate-300 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen=false" style="display:none;"></div>
