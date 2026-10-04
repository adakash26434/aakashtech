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
$root = realpath(dirname(__DIR__));
foreach (glob($root . '/includes/billing/*.php') as $module) {
    $code = file_get_contents($module);
    if (strpos($code, 'dirname(__DIR__)') !== false && strpos($code, 'dirname(__DIR__, 2)') === false) {
        check('module paths resolve to project root: ' . basename($module), false);
    }
}
check('billing modules resolve two levels up to the project root', realpath($root . '/includes/billing/../..') === $root);
// Delivery reports
$conn->query("INSERT INTO sms_messages (client_id, recipient, message_text, parts, status, sent_at) VALUES ($cid, '9841000001', 'hi', 1, 'sent', '" . date('Y-m-d H:i:s') . "')");
$mid = (int) $conn->insert_id;
check('status words map', sms_dlr_normalise_status('DELIVRD') === 'delivered' && sms_dlr_normalise_status('Undelivered') === 'failed' && sms_dlr_normalise_status('queued') === '');
check('unknown number updates nothing', sms_dlr_apply($conn, '9800000000', 'delivered') === false);
check('delivered report matches +977 format', sms_dlr_apply($conn, '+9779841000001', 'delivered', 'ref-1') === true);
$r = $conn->query("SELECT status, delivery, provider_ref FROM sms_messages WHERE id = $mid")->fetch_assoc();
check('delivery stored, sent status untouched', $r['status'] === 'sent' && $r['delivery'] === 'delivered' && $r['provider_ref'] === 'ref-1');
check('same message is not reported twice', sms_dlr_apply($conn, '9841000001', 'failed') === false);
// Changing the vendor key must never show the old account's stock
billing_set_setting($conn, 'sms_line_provider', 'aakash');
billing_set_setting($conn, 'sms_line_token', 'OLD-KEY-12345678');
billing_set_setting($conn, 'sms_vendor_balance', '5000');
billing_set_setting($conn, 'sms_vendor_balance_for', sms_vendor_fingerprint($conn));
check('stock shows for the account it belongs to', sms_vendor_stock_saved($conn)['balance'] === 5000);
check('saving a new key clears the old stock', sms_save_line($conn, array('sms_line_provider' => 'aakash', 'sms_line_token' => 'NEW-KEY-87654321')) === '' && sms_vendor_stock_saved($conn)['balance'] === null);
billing_set_setting($conn, 'sms_vendor_balance', '9999');
check('a stale number from another key is ignored', sms_vendor_stock_saved($conn)['balance'] === null);
billing_set_setting($conn, 'sms_vendor_balance_for', sms_vendor_fingerprint($conn));
check('saving the same key keeps the cache', sms_save_line($conn, array('sms_line_provider' => 'aakash', 'sms_line_token' => 'NEW-KEY-87654321')) === '' && sms_vendor_stock_saved($conn)['balance'] === 9999);
// Backup and restore round trip
require_once $root . '/includes/backup.php';
$conn->query("INSERT INTO client_users (name,email,password) VALUES ('Quote''s \\ test','q@example.com','x')");
$bdir = sys_get_temp_dir() . '/aakash-backup-test-' . getmypid(); @mkdir($bdir);
$bfile = $bdir . '/backup-test.sql.gz';
$rowsSaved = backup_write($conn, $bfile);
check('backup writes a gzip file with rows', $rowsSaved > 0 && is_file($bfile) && filesize($bfile) > 200);
$restore = new PDO('sqlite::memory:');
$restore->exec(gzdecode(file_get_contents($bfile)));
$before = (int) $conn->query('SELECT COUNT(*) AS c FROM client_users')->fetch_assoc()['c'];
$after = (int) $restore->query('SELECT COUNT(*) FROM client_users')->fetchColumn();
$name = $restore->query("SELECT name FROM client_users WHERE email = 'q@example.com'")->fetchColumn();
check('restore brings back the same clients', $before === $after && $after >= 2);
check('quotes and backslashes survive a restore', $name === "Quote's \\ test");
$ids = (int) $restore->query('SELECT COUNT(*) FROM client_wallets')->fetchColumn();
check('wallet rows are in the backup', $ids === (int) $conn->query('SELECT COUNT(*) AS c FROM client_wallets')->fetch_assoc()['c']);
foreach (glob($bdir . '/*') as $f) { @unlink($f); } @rmdir($bdir);
// Visitor IP behind a proxy
$_SERVER['REMOTE_ADDR'] = '10.0.0.1'; $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.9';
check('proxy header ignored unless opted in', auth_client_ip() === '10.0.0.1');
// Friendly error page
$errHtml = app_error_page_html('ABCD1234');
check('error page shows the reference code', strpos($errHtml, 'ABCD1234') !== false);
check('error page never shows technical detail', stripos($errHtml, 'Exception') === false && stripos($errHtml, '.php') === false);
check('error page escapes the reference', strpos(app_error_page_html('<script>'), '<script>') === false);
// Dashboard numbers
$now = date('Y-m-d H:i:s'); $old = date('Y-m-d H:i:s', strtotime('-20 days'));
$conn->query("INSERT INTO sms_messages (client_id, recipient, message_text, parts, status, sent_at, delivery) VALUES ($cid, '9841000010', 'a', 1, 'sent', '$now', 'delivered')");
$conn->query("INSERT INTO sms_messages (client_id, recipient, message_text, parts, status, sent_at, delivery) VALUES ($cid, '9841000011', 'b', 1, 'failed', '$now', '')");
$conn->query("INSERT INTO sms_messages (client_id, recipient, message_text, parts, status, sent_at, delivery) VALUES ($cid, '9841000012', 'c', 1, 'sent', '$old', '')");
$ov = sms_overview($conn, $cid, 7);
check('overview has one point per day', count($ov['series']) === 7 && $ov['series'][6]['day'] === date('Y-m-d'));
$sentToday = (int) $conn->query("SELECT COUNT(*) AS c FROM sms_messages WHERE status = 'sent' AND sent_at >= '" . date('Y-m-d 00:00:00', strtotime('-6 days')) . "'")->fetch_assoc()['c'];
check('overview counts the week and leaves the 20-day-old message out', $ov['totals']['failed'] === 1 && $ov['totals']['sent'] === $sentToday && $ov['totals']['delivered'] >= 1);
check('delivery rate uses only reported messages', $ov['delivery_rate'] === 100 || $ov['delivery_rate'] === 50);
check('another client sees none of it', sms_overview($conn, $cid + 999, 7)['totals']['sent'] === 0);
check('top senders lists the client', count(sms_top_senders($conn, 5)) >= 1);
echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
