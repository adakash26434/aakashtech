<?php
require_once __DIR__ . '/../config.php';
// Standalone page: a waiting account must not reach the portal header.

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) (isset($_POST['email']) ? $_POST['email'] : ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter the email address you signed up with.';
    } elseif (!auth_attempt_reserve($conn, 'verify-resend', 5, 3600)) {
        $error = 'Too many requests from this connection. Try again in an hour.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM client_users WHERE email = ? AND status = ? LIMIT 1');
        $waiting = 'pending';
        $stmt->bind_param('ss', $email, $waiting);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        if ($row) {
            email_verify_issue($conn, (int) $row['id'], $email);
        }
        // The same answer for every address, so this page cannot be used to find accounts.
        $sent = true;
    }
}
$expired = isset($_GET['expired']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm your email | <?= e(site_public_settings($conn)['site_name']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/site.css">
    <link rel="stylesheet" href="../assets/css/ui-shared.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<main class="max-w-md mx-auto px-4 py-12">
    <h1 class="font-heading text-2xl font-bold mb-3">Confirm your email</h1>
    <?php if ($expired && !$sent): ?>
        <div role="alert" class="mb-4 p-3 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 text-sm">That link has expired or was already used. Enter your email below and we will send a new one.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div role="alert" class="mb-4 p-3 rounded-xl border border-red-300 bg-red-50 text-red-700 text-sm"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($sent): ?>
        <div role="status" class="mb-4 p-3 rounded-xl border border-green-300 bg-green-50 text-green-800 text-sm">If that address is waiting for confirmation, a new link is on its way. It works for 48 hours.</div>
        <p class="text-sm"><a href="login.php" class="text-brand-500 underline">Back to sign in</a></p>
    <?php else: ?>
        <form method="POST" action="" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="block text-sm font-medium" for="verify-email">Email address</label>
            <input id="verify-email" type="email" name="email" required autocomplete="username" class="form-input w-full" placeholder="you@example.com">
            <button type="submit" class="w-full py-3 bg-brand-500 hover:bg-brand-400 text-white font-semibold rounded-xl">Send a new link</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
