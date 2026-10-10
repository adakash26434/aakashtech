<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['totp_gate']);

if (isset($_GET['next'])) {
    $_SESSION['client_next'] = client_safe_next($_GET['next']);
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['client_next']);
}

if (is_client_logged_in()) {
    $next = isset($_SESSION['client_next']) ? client_safe_next($_SESSION['client_next']) : 'index.php';
    unset($_SESSION['client_next']);
    header('Location: ' . $next);
    exit;
}

$error = '';
$showRegister = isset($_GET['action']) && $_GET['action'] === 'register';
$registerValues = array(
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => ''
);

$flashError = flash('login_error');
if ($error === '' && $flashError !== '') {
    $error = $flashError;
}
$loginNotice = flash('login_notice');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (auth_attempt_blocked($conn, 'client', 8, 900)) {
        $error = 'Too many sign-in attempts. Wait 15 minutes and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password, status FROM client_users WHERE LOWER(email) = ? LIMIT 1");
            if (!$stmt) {
                throw new RuntimeException('Client lookup failed.');
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $client = db_fetch_assoc($stmt);
            $stmt->close();
        } catch (Throwable $exception) {
            error_log('Client sign-in could not read the account.');
            $client = null;
            $error = 'Sign-in could not be completed. Try again in a moment.';
        }
        $storedPassword = ($error === '' && $client) ? (string) $client['password'] : '';
        $passwordMatches = $error === '' && auth_password_matches($storedPassword, $password);
        if ($passwordMatches && $client && $client['status'] === 'active') {
            // Failures are cleared only after the second factor passes (totp.php), not here.
            $next = isset($_SESSION['client_next']) ? client_safe_next($_SESSION['client_next']) : 'index.php';
            totp_open_gate($conn, 'client', array(
                'id' => (int) $client['id'],
                'name' => $client['name'],
                'email' => $client['email']
            ), $next);
            header('Location: two-factor.php');
            exit;
        } elseif ($error === '') {
            auth_note_attempt($conn, 'client');
            if ($client && $client['status'] === 'pending' && $passwordMatches) {
                $error = 'Confirm your email address first. Open the link we sent you. Use Send it again below if it did not arrive.';
            } else {
                $error = ($client && $client['status'] !== 'active')
                    ? 'Your account is suspended. Contact support.'
                    : 'Invalid email or password.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_account'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!csrf_is_valid()) {
        echo json_encode(array('ok' => false, 'message' => 'The form expired. Refresh the page and try again.'));
        exit;
    }
    if (auth_attempt_blocked($conn, 'register-check', 40, 900)) {
        echo json_encode(array('ok' => false, 'message' => 'Wait a moment, then check again.'));
        exit;
    }
    auth_note_attempt($conn, 'register-check');
    $field = isset($_POST['field']) ? (string) $_POST['field'] : '';
    $value = isset($_POST['value']) ? (string) $_POST['value'] : '';
    $email = '';
    $phone = '';
    $company = '';
    if ($field === 'email') {
        $email = strtolower(trim($value));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array('ok' => false, 'message' => 'Please enter a valid email.'));
            exit;
        }
    } elseif ($field === 'phone') {
        $phone = auth_mobile_number($value);
        if ($phone === '') {
            echo json_encode(array('ok' => false, 'message' => 'Enter a 10-digit mobile number.'));
            exit;
        }
    } elseif ($field === 'company') {
        $company = billing_plain_line($value, 120);
        if ($company === '') {
            echo json_encode(array('ok' => true, 'message' => ''));
            exit;
        }
    } else {
        echo json_encode(array('ok' => true, 'message' => ''));
        exit;
    }
    $taken = billing_client_taken($conn, $email, $phone, $company, 0);
    echo json_encode(array('ok' => $taken === '', 'message' => $taken === '' ? 'Available.' : $taken));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $showRegister = true;
    $name = substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
    $email = strtolower(substr(trim((string) ($_POST['email'] ?? '')), 0, 120));
    $phoneInput = trim((string) ($_POST['phone'] ?? ''));
    $phone = auth_mobile_number($phoneInput);
    $company = billing_plain_line(isset($_POST['company']) ? $_POST['company'] : '', 120);
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $honeypot = trim((string) ($_POST['website'] ?? ''));
    $registerValues = array(
        'name' => $name,
        'email' => $email,
        'phone' => substr($phoneInput, 0, 16),
        'company' => $company
    );

    $mathError = auth_math_verify('register', isset($_POST['human_check']) ? $_POST['human_check'] : '');
    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif ($mathError !== '') {
        $error = $mathError;
    } elseif ($honeypot !== '') {
        header('Location: login.php');
        exit;
    } elseif (auth_attempt_blocked($conn, 'register', 5, 1800)) {
        $error = 'Too many new accounts from this network. Please wait and try again.';
    } elseif ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } elseif ($phone === '') {
        $error = 'Enter a 10-digit mobile number.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > 72) {
        // bcrypt ignores everything after 72 bytes, so a longer password would be silently cut.
        $error = 'Password must be 72 characters or fewer.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_POST['accept_terms']) || $_POST['accept_terms'] !== '1') {
        $error = 'Read the Terms and Conditions and tick the box to create an account.';
    } else {
        try {
            $taken = billing_client_taken($conn, $email, $phone, $company, 0);
            if ($taken !== '') {
                auth_note_attempt($conn, 'register');
                $error = $taken;
            } else {
                $colors = array('#0e7490', '#6d28d9', '#be185d', '#b45309', '#047857', '#b91c1c');
                $avatar_color = $colors[array_rand($colors)];
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO client_users (name, email, password, phone, company, avatar_color) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new RuntimeException('Client insert failed.');
                }
                $stmt->bind_param("ssssss", $name, $email, $hash, $phone, $company, $avatar_color);
                $stmt->execute();
                $stmt->close();
                auth_note_attempt($conn, 'register');
                auth_math_clear('register');
                $newId = (int) $conn->insert_id;
                if ($newId < 1) {
                    $error = 'The account could not be created. Refresh the page and try again.';
                } else {
                    terms_record_acceptance($conn, $newId);
                    // The account waits for the link sent to this address. It cannot sign in before that.
                    $waiting = 'pending';
                    $markPending = $conn->prepare('UPDATE client_users SET status = ? WHERE id = ?');
                    $markPending->bind_param('si', $waiting, $newId);
                    $markPending->execute();
                    $markPending->close();
                    email_verify_issue($conn, $newId, $email);
                    flash('login_notice', 'Account created. We sent a confirmation link to ' . $email . '. Open it to activate the account, then sign in.');
                    header('Location: login.php');
                    exit;
                }
            }
        } catch (Throwable $exception) {
            error_log('Client registration failed: ' . $exception->getMessage());
            $message = $exception->getMessage();
            if (stripos($message, 'Duplicate') !== false || stripos($message, 'UNIQUE') !== false) {
                $error = 'An account with this email already exists.';
            } else {
                $error = 'The account could not be created. Refresh the page and try again.';
            }
        }
    }
}

$identity = array('name' => 'Aakash Technologies', 'logo' => '', 'letter' => 'A');
try {
    $identity = site_portal_identity($conn);
} catch (Throwable $exception) {
    error_log('Client login could not load the site name.');
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal — Aakash Technologies</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preload" href="../assets/fonts/inter-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="../assets/fonts/space-grotesk-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <?= isset($conn) ? site_favicon_html($conn, '../') : '' ?>
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

    <div class="relative z-10 w-full <?= $showRegister ? 'max-w-xl' : 'max-w-md' ?> mx-auto px-4">
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
            <h1 class="font-heading font-bold text-white text-2xl">Client Portal</h1>
            <p class="text-slate-500 text-sm mt-1"><?= $showRegister ? 'Create your account, then add Google Authenticator' : 'Password, then a Google Authenticator code' ?></p>
        </div>

        <?php if ($loginNotice): ?>
            <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($loginNotice) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div role="alert" class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$showRegister): ?>
            <!-- Login Form -->
            <form method="POST" action="" class="bg-dark-900/70 backdrop-blur-xl border border-dark-800 rounded-2xl p-8 space-y-5">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label for="login-email" class="block text-slate-300 text-sm font-medium mb-2">Email Address</label>
                    <input type="email" id="login-email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus autocomplete="username" class="form-input" placeholder="you@example.com">
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label for="login-password" class="block text-slate-300 text-sm font-medium">Password</label>
                        <a href="forgot-password.php" class="text-brand-400 text-sm">Forgot password?</a>
                    </div>
                    <input type="password" id="login-password" name="password" required autocomplete="current-password" class="form-input" placeholder="••••••••">
                    <p class="text-slate-500 text-xs mt-2">Reset sends a link to this email. The link works for 30 minutes.</p>
                </div>
                <button type="submit" name="login" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/25 hover:-translate-y-0.5">
                    Sign In
                </button>
                <p class="text-slate-500 text-xs">The first sign-in adds this account in Google Authenticator. After that, every sign-in asks for the 6-digit code.</p>
                <p class="text-slate-500 text-xs"><a href="resend-verify.php" class="text-brand-400 underline">Did not get the confirmation email? Send it again</a></p>
            </form>
            <p class="text-center text-slate-600 text-sm mt-6">
                Don't have an account? <a href="?action=register" class="text-brand-400 hover:text-brand-300 font-medium">Register here</a><br>
                <a href="../index.php" class="text-slate-500 hover:text-brand-400 text-xs mt-2 inline-block">← Back to Website</a>
            </p>
        <?php else: ?>
            <!-- Register Form -->
            <form id="register-form" method="POST" action="" class="bg-dark-900/70 backdrop-blur-xl border border-dark-800 rounded-2xl p-8 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div style="position:absolute;left:-9999px;height:0;overflow:hidden" aria-hidden="true">
                    <label>Website</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2">Full Name *</label>
                    <input type="text" name="name" required class="form-input" placeholder="John Doe" value="<?= e($registerValues['name']) ?>">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="reg-email">Email *</label>
                    <input id="reg-email" type="email" name="email" required autocomplete="email" class="form-input" placeholder="you@example.com" value="<?= e($registerValues['email']) ?>">
                    <p id="reg-email-hint" class="field-hint" aria-live="polite"></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2" for="reg-phone">Mobile *</label>
                        <input id="reg-phone" type="tel" name="phone" required inputmode="tel" maxlength="16" autocomplete="tel" class="form-input" placeholder="10-digit mobile" value="<?= e($registerValues['phone']) ?>">
                        <p id="reg-phone-hint" class="field-hint" aria-live="polite"></p>
                    </div>
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2" for="reg-company">Company</label>
                        <input id="reg-company" type="text" name="company" class="form-input" placeholder="Company Ltd" value="<?= e($registerValues['company']) ?>">
                        <p id="reg-company-hint" class="field-hint" aria-live="polite"></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2" for="reg-password">Password *</label>
                        <input id="reg-password" type="password" name="password" required minlength="8" autocomplete="new-password" class="form-input" placeholder="Min 8 characters">
                        <p id="reg-password-hint" class="field-hint" aria-live="polite"></p>
                    </div>
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2" for="reg-confirm">Confirm *</label>
                        <input id="reg-confirm" type="password" name="confirm_password" required minlength="8" autocomplete="new-password" class="form-input" placeholder="Repeat">
                        <p id="reg-confirm-hint" class="field-hint" aria-live="polite"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2" for="human_check">What is <?= e(auth_math_prompt('register')) ?>? *</label>
                    <input id="human_check" name="human_check" type="text" inputmode="numeric" maxlength="2" required autocomplete="off" class="form-input" placeholder="Answer">
                </div>
                <label class="flex items-start gap-3 text-sm text-slate-300 cursor-pointer">
                    <input type="checkbox" name="accept_terms" value="1" required class="mt-1 h-5 w-5 shrink-0 accent-brand-500">
                    <span>I have read the <a href="../terms.php" target="_blank" rel="noopener" class="text-brand-400 underline">Terms and Conditions</a> and the <a href="../privacy.php" target="_blank" rel="noopener" class="text-brand-400 underline">privacy policy</a>, and I accept them. This tick is my digital signature on this account.</span>
                </label>
                <button type="submit" name="register" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/25 hover:-translate-y-0.5">
                    Create Account
                </button>
            </form>
            <p class="text-center text-slate-600 text-sm mt-6">
                Already have an account? <a href="login.php" class="text-brand-400 hover:text-brand-300 font-medium">Sign in</a><br>
                <a href="../index.php" class="text-slate-500 hover:text-brand-400 text-xs mt-2 inline-block">← Back to Website</a>
            </p>
            <script src="../assets/js/client-login.js"></script>
        <?php endif; ?>
    </div>
</body>
</html>
