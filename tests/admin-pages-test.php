<?php
// Run: php tests/admin-pages-test.php [--hash]
// Renders every admin page as a signed-in admin on a throw-away SQLite file. Fails on any PHP error.
// With --hash it prints a fingerprint of each page so two versions of the code can be compared.
$root = dirname(__DIR__);
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-admin-test-' . getmypid() . '.sqlite');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_METHOD'] = 'GET';
chdir($root);
ob_start(); require $root . '/config.php'; ob_end_clean();
$conn->query("INSERT INTO admin_users (name,email,password,totp_secret) VALUES ('Office','office@example.com','x','TESTSECRET')");
$__aid = (int) $conn->insert_id;
$conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('Test Client','page@example.com','x','active')");
$__cid = (int) $conn->insert_id;
$_SESSION['admin_id'] = $__aid; $_SESSION['admin_role'] = 'admin';
set_error_handler(function ($no, $msg, $file, $line) { throw new ErrorException($msg, 0, $no, $file, $line); });
$__skip = array('login', 'logout', 'two-factor');
$__hash = in_array('--hash', $argv, true);
$__fail = 0;
foreach (glob($root . '/admin/*.php') as $__file) {
    $__name = basename($__file, '.php');
    if (in_array($__name, $__skip, true) || strpos($__name, 'file') !== false || strpos($__name, 'export') !== false) { continue; }
    $_SERVER['SCRIPT_NAME'] = '/admin/' . $__name . '.php';
    $_GET = array('id' => $__cid, 'client_id' => $__cid); $_REQUEST = $_GET;
    ob_start();
    try {
        include $__file;
        $__html = ob_get_clean();
        $__norm = preg_replace('/[a-f0-9]{32,}/', 'TOKEN', $__html);
        $__ok = strlen($__html) > 300;
        echo ($__ok ? 'PASS ' : 'FAIL ') . "admin/$__name.php (" . strlen($__html) . ' bytes)' . ($__hash ? ' ' . md5($__norm) : '') . "\n";
        if (!$__ok) { $__fail++; }
    } catch (Throwable $e) {
        while (ob_get_level() > 1) { ob_end_clean(); }
        echo "FAIL admin/$__name.php: " . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
        $__fail++;
    }
}
foreach (array_merge(glob($root . '/admin/includes/*.php'), glob($root . '/client/includes/*.php'), glob($root . '/includes/*/*.php')) as $__inc) {
    $__code = file_get_contents($__inc);
    if (preg_match('/dirname\(__DIR__\)(?!, )/', $__code)) {
        echo 'FAIL ' . str_replace($root . '/', '', $__inc) . ": dirname(__DIR__) reaches the wrong folder from a sub-folder (use dirname(__DIR__, 2))\n";
        $__fail++;
    }
}
exit($__fail ? 1 : 0);
