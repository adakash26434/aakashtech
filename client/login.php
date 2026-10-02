<?php
require_once __DIR__ . '/../config.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (auth_attempt_blocked($conn, 'client', 8, 900)) {
        $error = 'Too many sign-in attempts. Wait 15 minutes and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password, status FROM client_users WHERE email = ? LIMIT 1");
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
        if ($error === '' && $client && $client['status'] === 'active' && password_verify($password, (string) $client['password'])) {
            auth_clear_attempts($conn, 'client');
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_name'] = $client['name'];
            $_SESSION['client_email'] = $client['email'];
            $next = isset($_SESSION['client_next']) ? client_safe_next($_SESSION['client_next']) : 'index.php';
            unset($_SESSION['client_next']);
            auth_fresh_session();
            header('Location: ' . $next);
            exit;
        } elseif ($error === '') {
            auth_note_attempt($conn, 'client');
            $error = ($client && $client['status'] !== 'active')
                ? 'Your account is suspended. Contact support.'
                : 'Invalid email or password.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $showRegister = true;
    $name = substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
    $email = substr(trim((string) ($_POST['email'] ?? '')), 0, 120);
    $phoneInput = trim((string) ($_POST['phone'] ?? ''));
    $phone = auth_mobile_number($phoneInput);
    $company = substr(trim((string) ($_POST['company'] ?? '')), 0, 120);
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $honeypot = trim((string) ($_POST['website'] ?? ''));
    $registerValues = array(
        'name' => $name,
        'email' => $email,
        'phone' => substr($phoneInput, 0, 16),
        'company' => $company
    );

    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
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
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $check = $conn->prepare("SELECT id FROM client_users WHERE email = ? LIMIT 1");
            if (!$check) {
                throw new RuntimeException('Client lookup failed.');
            }
            $check->bind_param("s", $email);
            $check->execute();
            $existing = db_fetch_assoc($check);
            $check->close();
            if ($existing) {
                auth_note_attempt($conn, 'register');
                $error = 'An account with this email already exists.';
            } else {
                $colors = array('#06b6d4', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444');
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
                $_SESSION['client_id'] = $conn->insert_id;
                $_SESSION['client_name'] = $name;
                $_SESSION['client_email'] = $email;
                $next = isset($_SESSION['client_next']) ? client_safe_next($_SESSION['client_next']) : 'index.php';
                unset($_SESSION['client_next']);
                auth_fresh_session();
                header('Location: ' . $next);
                exit;
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
<body class="portal-shell portal-auth font-body bg-dark-950 text-slate-300 min-h-screen flex items-center justify-center relative overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-gradient-to-b from-dark-950 via-dark-900 to-dark-950"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-brand-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md mx-4">
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
            <p class="text-slate-500 text-sm mt-1"><?= $showRegister ? 'Create your account' : 'Sign in to your account' ?></p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$showRegister): ?>
            <!-- Login Form -->
            <form method="POST" action="" class="bg-dark-900/70 backdrop-blur-xl border border-dark-800 rounded-2xl p-8 space-y-5">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2">Email Address</label>
                    <input type="email" name="email" required autofocus autocomplete="username" class="form-input" placeholder="you@example.com">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2">Password</label>
                    <input type="password" name="password" required autocomplete="current-password" class="form-input" placeholder="••••••••">
                </div>
                <button type="submit" name="login" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/25 hover:-translate-y-0.5">
                    Sign In
                </button>
            </form>
            <p class="text-center text-slate-600 text-sm mt-6">
                Don't have an account? <a href="?action=register" class="text-brand-400 hover:text-brand-300 font-medium">Register here</a><br>
                <a href="../index.php" class="text-slate-500 hover:text-brand-400 text-xs mt-2 inline-block">← Back to Website</a>
            </p>
        <?php else: ?>
            <!-- Register Form -->
            <form method="POST" action="" class="bg-dark-900/70 backdrop-blur-xl border border-dark-800 rounded-2xl p-8 space-y-4">
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
                    <label class="block text-slate-300 text-sm font-medium mb-2">Email *</label>
                    <input type="email" name="email" required autocomplete="email" class="form-input" placeholder="you@example.com" value="<?= e($registerValues['email']) ?>">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2">Mobile *</label>
                        <input type="tel" name="phone" required inputmode="tel" maxlength="16" autocomplete="tel" class="form-input" placeholder="98XXXXXXXX" value="<?= e($registerValues['phone']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2">Company</label>
                        <input type="text" name="company" class="form-input" placeholder="Company Ltd" value="<?= e($registerValues['company']) ?>">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2">Password *</label>
                        <input type="password" name="password" required autocomplete="new-password" class="form-input" placeholder="Min 6 chars">
                    </div>
                    <div>
                        <label class="block text-slate-300 text-sm font-medium mb-2">Confirm *</label>
                        <input type="password" name="confirm_password" required autocomplete="new-password" class="form-input" placeholder="Repeat">
                    </div>
                </div>
                <button type="submit" name="register" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/25 hover:-translate-y-0.5">
                    Create Account
                </button>
            </form>
            <p class="text-center text-slate-600 text-sm mt-6">
                Already have an account? <a href="login.php" class="text-brand-400 hover:text-brand-300 font-medium">Sign in</a><br>
                <a href="../index.php" class="text-slate-500 hover:text-brand-400 text-xs mt-2 inline-block">← Back to Website</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
