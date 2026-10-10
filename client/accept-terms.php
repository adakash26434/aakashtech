<?php
require_once __DIR__ . '/../config.php';
// Deliberately does not include the client header: the header is what sends a client here.
require_client();

$clientId = (int) get_client_id();
if (terms_client_has_accepted($conn, $clientId)) {
    $next = isset($_SESSION['client_next']) ? client_safe_next($_SESSION['client_next']) : 'index.php';
    unset($_SESSION['client_next']);
    header('Location: ' . $next);
    exit;
}

$publicSite = site_public_settings($conn);
$siteName = $publicSite['site_name'];
$termsParagraphs = preg_split("/\n{2,}/", site_legal_text($publicSite, 'terms_of_service'));
if (!is_array($termsParagraphs)) {
    $termsParagraphs = array();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!isset($_POST['accept_terms']) || $_POST['accept_terms'] !== '1') {
        $error = 'Tick the box to accept the Terms and Conditions before you continue.';
    } elseif (!terms_record_acceptance($conn, $clientId)) {
        $error = 'Your acceptance could not be saved. Try again.';
    } else {
        $next = isset($_SESSION['client_next']) ? client_safe_next($_SESSION['client_next']) : 'index.php';
        unset($_SESSION['client_next']);
        header('Location: ' . $next);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept the Terms and Conditions | <?= e($siteName) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/site.css">
    <link rel="stylesheet" href="../assets/css/ui-shared.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<main class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="font-heading text-2xl font-bold mb-2">Please read and accept the Terms and Conditions</h1>
    <p class="text-sm text-slate-600 mb-6">The portal can be used after you accept the current terms. Your acceptance is saved on your account with the date, time, and network address, and it is the record that you agreed.</p>

    <?php if ($error !== ''): ?>
        <div role="alert" class="mb-4 p-3 rounded-xl border border-red-300 bg-red-50 text-red-700 text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <section aria-labelledby="terms-title" class="bg-white border border-slate-200 rounded-2xl p-5 mb-6">
        <h2 id="terms-title" class="font-semibold text-lg mb-3">Terms and Conditions</h2>
        <div class="max-h-[55vh] overflow-y-auto pr-2 text-sm leading-relaxed space-y-4" tabindex="0" role="region" aria-labelledby="terms-title">
            <?php foreach ($termsParagraphs as $paragraph): ?>
                <p><?= e($paragraph) ?></p>
            <?php endforeach; ?>
        </div>
        <p class="text-sm mt-4">
            <a href="../terms.php" target="_blank" rel="noopener" class="text-brand-500 underline">Open the full page in a new tab</a>
        </p>
    </section>

    <form method="POST" action="" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-5">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label class="flex items-start gap-3 text-sm cursor-pointer">
            <input type="checkbox" name="accept_terms" value="1" required class="mt-1 h-5 w-5 shrink-0 accent-brand-500">
            <span>I have read the Terms and Conditions and I accept them. This tick is my digital signature on this account.</span>
        </label>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-3 bg-brand-500 hover:bg-brand-400 text-white font-semibold rounded-xl">Accept and continue</button>
        </div>
    </form>

    <form method="POST" action="logout.php" class="mt-6 text-sm">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="text-slate-600">If you do not accept, you cannot use the portal. <button type="submit" class="text-brand-500 underline bg-transparent border-0 cursor-pointer p-0">Sign out</button></p>
    </form>
</main>
</body>
</html>
