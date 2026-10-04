<?php
require_once __DIR__ . '/../config.php';

if (is_client_logged_in()) {
    header('Location: index.php');
    exit;
}

$token = isset($_POST['token']) ? (string) $_POST['token'] : (isset($_GET['token']) ? (string) $_GET['token'] : '');
$token = strtolower(trim($token));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $token = '';
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';
    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $error = password_reset_consume($conn, $token, $password);
        if ($error === '') {
            flash('login_notice', 'Password saved. Sign in with the new password and the authenticator code.');
            header('Location: login.php');
            exit;
        }
    }
}

$identity = array('name' => 'Aakash Technologies', 'logo' => '', 'letter' => 'A');
try {
    $identity = site_portal_identity($conn);
} catch (Throwable $exception) {
    error_log('Password reset could not load the site name.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose a password — <?= e($identity['name']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preload" href="../assets/fonts/inter-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="../assets/fonts/space-grotesk-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/portal-polish.css">
    <link rel="stylesheet" href="../assets/css/ui-shared.css">
    <script defer src="../assets/js/forms.js"></script>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
</head>
<body class="portal-shell portal-auth font-body bg-dark-950 text-slate-300 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <a href="../index.php" class="font-heading font-bold text-white text-xl"><?= e($identity['name']) ?></a>
            <h1 class="font-heading font-bold text-white text-2xl mt-4">Choose a password</h1>
        </div>
        <?php if ($token === ''): ?>
            <div class="bg-dark-900 border border-dark-800 rounded-2xl p-8">
                <p class="text-slate-300 text-sm">This link has expired. Ask for a new one.</p>
                <p class="text-center mt-6"><a href="forgot-password.php" class="text-brand-400 text-sm">Request a new link</a></p>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="bg-dark-900 border border-dark-800 rounded-2xl p-8 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="password">New password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="form-input" placeholder="At least 8 characters">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="confirm_password">Confirm</label>
                    <input id="confirm_password" type="password" name="confirm_password" required autocomplete="new-password" class="form-input" placeholder="Repeat the password">
                </div>
                <button type="submit" class="w-full py-3 bg-brand-500 hover:bg-brand-400 text-white font-semibold rounded-xl">Save password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
