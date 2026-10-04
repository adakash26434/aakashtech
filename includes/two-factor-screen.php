<?php
if (!isset($totpPortal) || ($totpPortal !== 'admin' && $totpPortal !== 'client')) {
    http_response_code(404);
    exit;
}

header('Cache-Control: no-store');

if ($totpPortal === 'admin' && is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}
if ($totpPortal === 'client' && is_client_logged_in()) {
    header('Location: index.php');
    exit;
}

$gate = totp_gate();
if (!$gate || $gate['kind'] !== $totpPortal) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
    } else {
        $error = totp_gate_post(
            $conn,
            isset($_POST['authenticator_code']) ? $_POST['authenticator_code'] : '',
            isset($_POST['saved_codes']) ? $_POST['saved_codes'] : ''
        );
        $gate = totp_gate();
        if (!$gate || $gate['kind'] !== $totpPortal) {
            header('Location: login.php');
            exit;
        }
    }
}

$identity = array('name' => 'Aakash Technologies', 'logo' => '', 'letter' => 'A');
try {
    $identity = site_portal_identity($conn);
} catch (Throwable $exception) {
    error_log('Authenticator page could not load the site name.');
}

$stage = isset($gate['stage']) ? (string) $gate['stage'] : 'code';
$email = isset($gate['email']) ? (string) $gate['email'] : '';
$setupSecret = isset($gate['setup_secret']) ? (string) $gate['setup_secret'] : '';
$recovery = (isset($gate['recovery_plain']) && is_array($gate['recovery_plain'])) ? $gate['recovery_plain'] : array();
$qrUri = ($stage === 'setup' && $setupSecret !== '') ? totp_uri(totp_issuer(), $email, $setupSecret) : '';
$heading = $totpPortal === 'admin' ? 'Admin sign-in' : 'Client sign-in';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authenticator — <?= e($identity['name']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/portal-polish.css">
    <link rel="stylesheet" href="../assets/css/ui-shared.css">
    <script defer src="../assets/js/forms.js"></script>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
</head>
<body class="portal-shell portal-auth font-body bg-dark-950 text-slate-300 min-h-screen flex items-center justify-center relative overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-gradient-to-b from-dark-950 via-dark-900 to-dark-950"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-brand-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md mx-4 my-10">
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex items-center justify-center gap-3 mb-6">
                <?php if ($identity['logo'] !== ''): ?>
                    <img src="<?= e($identity['logo']) ?>" alt="<?= e($identity['name']) ?>" class="portal-brand-logo portal-brand-logo--login">
                <?php else: ?>
                    <div class="relative w-12 h-12 flex items-center justify-center">
                        <div class="absolute inset-0 bg-gradient-to-br from-brand-500 to-brand-700 rounded-xl rotate-45"></div>
                        <span class="portal-logo-letter relative font-heading font-bold text-white text-xl z-10"><?= e($identity['letter']) ?></span>
                    </div>
                    <span class="font-heading font-bold text-white text-xl"><?= e($identity['name']) ?></span>
                <?php endif; ?>
            </a>
            <h1 class="font-heading font-bold text-white text-2xl"><?= e($heading) ?></h1>
            <p class="text-slate-500 text-sm mt-1"><?= $stage === 'codes' ? 'Save the backup codes' : 'Google Authenticator' ?></p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="bg-dark-900/70 backdrop-blur-xl border border-dark-800 rounded-2xl p-8 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($stage === 'setup'): ?>
                <p class="text-slate-300 text-sm">This code is required on every sign-in. It cannot be turned off. Install Google Authenticator, tap +, and scan this code. Or choose “Enter a setup key”, time based, 6 digits.</p>
                <div class="text-center"><?= totp_qr_html($qrUri, 'totp-qr') ?></div>
                <div>
                    <p class="text-slate-400 text-xs font-medium mb-1">Setup key</p>
                    <p class="text-white font-mono text-sm tracking-wide break-all"><?= e(totp_key_groups($setupSecret)) ?></p>
                    <p class="text-slate-500 text-xs mt-2">Account <?= e($email) ?>. The phone clock should be set automatically.</p>
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="authenticator_code">6-digit code</label>
                    <input id="authenticator_code" name="authenticator_code" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="form-input tracking-widest" placeholder="000000">
                </div>
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition">Confirm and continue</button>
            <?php elseif ($stage === 'codes'): ?>
                <p class="text-slate-300 text-sm">Each backup code works once if the phone is not available. This list is shown only now. Store it somewhere private.</p>
                <?php if ($recovery): ?>
                    <ul class="grid grid-cols-2 gap-2">
                        <?php foreach ($recovery as $backupCode): ?>
                            <li class="bg-dark-950 border border-dark-800 rounded-lg px-3 py-2 text-white font-mono text-sm tracking-wide text-center"><?= e($backupCode) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <label class="flex items-start gap-3 text-slate-300 text-sm">
                        <input type="checkbox" name="saved_codes" value="1" required class="mt-1">
                        <span>I have saved these codes.</span>
                    </label>
                <?php else: ?>
                    <p class="text-slate-400 text-sm">The codes were not stored in this step. After you enter the portal, create a new set from the profile page.</p>
                <?php endif; ?>
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition">Continue</button>
            <?php else: ?>
                <p class="text-slate-300 text-sm">Open Google Authenticator and enter the current 6-digit code for <?= e($email) ?>. A backup code works once instead.</p>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="authenticator_code">Code</label>
                    <input id="authenticator_code" name="authenticator_code" required autocomplete="one-time-code" maxlength="12" autocapitalize="characters" spellcheck="false" class="form-input tracking-widest" placeholder="6-digit code or backup code">
                </div>
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition">Continue</button>
            <?php endif; ?>
        </form>
        <p class="text-center text-slate-600 text-xs mt-6">
            <a href="login.php" class="text-brand-400 hover:text-brand-300">Use a different account</a>
        </p>
    </div>
</body>
</html>
