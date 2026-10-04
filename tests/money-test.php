<?php
// Run: php tests/money-test.php   (uses a throw-away SQLite file; exits non-zero on failure)
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-money-test.sqlite');
@unlink(sys_get_temp_dir() . '/aakash-money-test.sqlite');
$_SERVER['HTTP_HOST'] = 'localhost';
chdir(dirname(__DIR__));
ob_start(); require 'config.php'; ob_end_clean();
$fail = 0;
function check($name, $ok) { global $fail; echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$ok) { $fail++; } }
function bal($conn, $id) { $r = $conn->query("SELECT balance FROM client_wallets WHERE client_id=$id"); $row = $r ? $r->fetch_assoc() : null; return $row ? (float) $row['balance'] : 0.0; }
$conn->query("INSERT INTO client_users (name,email,password) VALUES ('T','t@example.com','x')");
$cid = (int) $conn->insert_id;
@billing_admin_wallet_credit($conn, $cid, 500, 'cash');
check('office credit adds 500', bal($conn, $cid) == 500.0);
check('debit within balance works', billing_wallet_debit($conn, $cid, 200) && bal($conn, $cid) == 300.0);
check('debit beyond balance is refused', !billing_wallet_debit($conn, $cid, 1000) && bal($conn, $cid) == 300.0);
billing_record_entry($conn, $cid, 100, 'credit', 'topup', 'pending', 'esewa', 'ref', 0);
$eid = (int) $conn->insert_id;
check('approve credits once', @billing_approve_topup($conn, $eid) && bal($conn, $cid) == 400.0);
check('second approve does not credit again', !@billing_approve_topup($conn, $eid) && bal($conn, $cid) == 400.0);
billing_tx($conn, 'begin'); billing_wallet_credit($conn, $cid, 999); billing_tx($conn, 'rollback');
check('rollback undoes a credit', bal($conn, $cid) == 400.0);
billing_tx($conn, 'begin'); billing_tx($conn, 'begin'); billing_wallet_credit($conn, $cid, 1); billing_tx($conn, 'commit'); billing_tx($conn, 'commit');
check('nested commit keeps the credit', bal($conn, $cid) == 401.0);
check('units cannot go negative', !billing_take_units($conn, $cid, 'sms', 5));
billing_add_units($conn, $cid, 'sms', 10);
check('take units within balance', billing_take_units($conn, $cid, 'sms', 4) && billing_unit_balances($conn, $cid)['sms'] === 6);
billing_add_units($conn, $cid, 'sms', 50);
check('low-credit alert fires below 100', @billing_low_sms_alert($conn, $cid) === true);
check('low-credit alert only once a day', @billing_low_sms_alert($conn, $cid) === false);
echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
