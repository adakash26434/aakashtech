<?php
require_once __DIR__ . '/../../config.php';
require_admin();
ob_start();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Aakash Technologies</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/portal-polish.css">
    <link rel="stylesheet" href="../assets/css/ui-shared.css">
    <script defer src="https://unpkg.com/lucide@0.383.0"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="../assets/js/portal.js"></script>
    <script defer src="../assets/js/forms.js"></script>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
</head>
<body class="portal-shell font-body bg-dark-950 text-slate-300 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen=false" style="display:none;"></div>
