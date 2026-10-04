<?php
// Run: php tests/pages-test.php
// Renders every client page as a logged-in client (office view, which skips 2FA) on a throw-away
// SQLite file and fails if any page throws a PHP error or prints no HTML.
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-pages-test.sqlite');
@unlink(sys_get_temp_dir() . '/aakash-pages-test.sqlite');
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['SCRIPT_NAME'] = '/client/index.php';
chdir(dirname(__DIR__));
ob_start(); require 'config.php'; ob_end_clean();
$conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('Test Client','page@example.com','x','active')");
$cid = (int) $conn->insert_id;
$conn->query("INSERT INTO admin_users (name,email,password) VALUES ('Office','office@example.com','x')");
$aid = (int) $conn->insert_id;
$_SESSION['client_id'] = $cid; $_SESSION['admin_id'] = $aid; $_SESSION['client_view_admin'] = $aid;
set_error_handler(function ($no, $msg, $file, $line) { throw new ErrorException($msg, 0, $no, $file, $line); });
$__pages = array('index', 'sms-portal', 'sms-logs', 'sms-report', 'sms-api', 'campaigns', 'wallet', 'shop', 'services', 'domains', 'kyc', 'profile', 'support', 'manual');
$__fail = 0;
foreach ($__pages as $__name) {
    $_SERVER['SCRIPT_NAME'] = '/client/' . $__name . '.php';
    ob_start();
    try {
        include dirname(__DIR__) . '/client/' . $__name . '.php';
        $__html = ob_get_clean();
        $__ok = strlen($__html) > 400;
        echo ($__ok ? 'PASS ' : 'FAIL ') . "client/$__name.php (" . strlen($__html) . " bytes)\n";
        if (!$__ok) { $__fail++; }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "FAIL client/$__name.php: " . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
        $__fail++;
    }
}
exit($__fail ? 1 : 0);
