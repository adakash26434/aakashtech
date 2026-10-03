<?php
require_once __DIR__ . '/../config.php';

if (is_client_logged_in()) {
    header('Location: index.php');
    exit;
}

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? (string) $_POST['email'] : '';
    $honeypot = trim((string) (isset($_POST['website']) ? $_POST['website'] : ''));
    $mathError = auth_math_verify('forgot-password', isset($_POST['human_check']) ? $_POST['human_check'] : '');
    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif ($mathError !== '') {
        $error = $mathError;
    } elseif ($honeypot !== '') {
        $sent = true;
    } elseif (auth_attempt_blocked($conn, 'password-reset', 5, 1800)) {
        $error = 'Too many reset requests from this network. Please wait and try again.';
    } else {
        auth_note_attempt($conn, 'password-reset');
        password_reset_request($conn, $email);
        auth_math_clear('forgot-password');
        $sent = true;
    }
}

$identity = array('name' => 'Aakash Technologies', 'logo' => '', 'letter' => 'A');
try {
    $identity = site_portal_identity($conn);
} catch (Throwable $exception) {
    error_log('Password reset could not load the site name.');
}
$mathPrompt = auth_math_prompt('forgot-password');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password — <?= e($identity['name']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
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
<body class="portal-shell portal-auth font-body bg-dark-950 text-slate-300 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <a href="../index.php" class="font-heading font-bold text-white text-xl"><?= e($identity['name']) ?></a>
            <h1 class="font-heading font-bold text-white text-2xl mt-4">Reset password</h1>
        </div>
        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($sent): ?>
            <div class="bg-dark-900 border border-dark-800 rounded-2xl p-8">
                <p class="text-slate-300 text-sm">If that email has an active account, a reset link is on its way. The link works for 30 minutes.</p>
                <p class="text-center mt-6"><a href="login.php" class="text-brand-400 text-sm">Back to sign in</a></p>
            </div>
        <?php else: ?>
            <form method="POST" class="bg-dark-900 border border-dark-800 rounded-2xl p-8 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div style="position:absolute;left:-9999px;height:0;overflow:hidden" aria-hidden="true">
                    <label>Website</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="email">Email</label>
                    <input id="email" type="email" name="email" required autofocus autocomplete="username" class="form-input" placeholder="you@example.com">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="human_check">What is <?= e($mathPrompt) ?>?</label>
                    <input id="human_check" type="text" name="human_check" required inputmode="numeric" class="form-input" autocomplete="off">
                </div>
                <button type="submit" class="w-full py-3 bg-brand-500 hover:bg-brand-400 text-white font-semibold rounded-xl">Send the reset link</button>
            </form>
            <p class="text-center mt-6"><a href="login.php" class="text-slate-500 text-sm">Back to sign in</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
