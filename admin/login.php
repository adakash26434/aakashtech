<?php
require_once __DIR__ . '/../config.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_is_valid()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (auth_attempt_blocked($conn, 'admin', 8, 900)) {
        $error = 'Too many sign-in attempts. Wait 15 minutes and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password, role, is_active FROM admin_users WHERE email = ? LIMIT 1");
            if (!$stmt) {
                throw new RuntimeException('Admin lookup failed.');
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $admin = db_fetch_assoc($stmt);
            $stmt->close();
        } catch (Throwable $exception) {
            error_log('Admin sign-in could not read the account.');
            $admin = null;
            $error = 'The admin account could not be read. In cpanel-config.php set admin_email and a password of at least 6 characters, then open this page again.';
        }

        if ($error === '' && $admin && (int)$admin['is_active'] === 1 && password_verify($password, (string) $admin['password'])) {
            auth_clear_attempts($conn, 'admin');
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            auth_fresh_session();
            try {
                $adminId = (int) $admin['id'];
                $stamp = date('Y-m-d H:i:s');
                $touch = $conn->prepare('UPDATE admin_users SET last_login = ? WHERE id = ?');
                if ($touch) {
                    $touch->bind_param('si', $stamp, $adminId);
                    $touch->execute();
                    $touch->close();
                }
            } catch (Throwable $exception) {
                error_log('Admin last login could not be saved.');
            }
            header('Location: index.php');
            exit;
        } elseif ($error === '') {
            auth_note_attempt($conn, 'admin');
            $error = 'Invalid email or password.';
        }
    }
}

$identity = array('name' => 'Aakash Technologies', 'logo' => '', 'letter' => 'A');
try {
    $identity = site_portal_identity($conn);
} catch (Throwable $exception) {
    error_log('Admin login could not load the site name.');
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Aakash Technologies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { heading: ['Space Grotesk', 'sans-serif'], body: ['Inter', 'sans-serif'] },
                colors: {
                    brand: { 50:'#e5f5f0',100:'#d2eee6',200:'#a8dfd0',300:'#79cdb7',400:'#43b39a',500:'#0b8b7a',600:'#087365',700:'#075e54' },
                    dark: { 200:'#536b63',300:'#344b44',700:'#e5f5f0',800:'#d2eee6',900:'#ffffff',950:'#f4f8f6' }
                }
            }}
        }
    </script>
</head>
<body class="portal-shell portal-auth font-body bg-dark-950 text-dark-200 min-h-screen flex items-center justify-center relative overflow-hidden">
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
            <h1 class="font-heading font-bold text-white text-2xl">Admin Panel</h1>
            <p class="text-slate-500 text-sm mt-1">Sign in to manage your dashboard</p>
        </div>

        <form method="POST" action="" class="bg-dark-900/70 backdrop-blur-xl border border-dark-800 rounded-2xl p-8 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($error): ?>
                <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div>
            <?php endif; ?>

            <div>
                <label class="block text-slate-300 text-sm font-medium mb-2">Email Address</label>
                <input type="email" name="email" required autofocus autocomplete="username" class="form-input" placeholder="admin@aakashtechnologies.com" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-300 text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" required autocomplete="current-password" class="form-input" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-brand-500/25 hover:-translate-y-0.5">
                Sign In
            </button>
        </form>

        <p class="text-center text-slate-600 text-xs mt-6">
            Admin access is for authorized staff.<br>
            <a href="../index.php" class="text-brand-400 hover:text-brand-300">← Back to Website</a>
        </p>
    </div>
</body>
</html>
