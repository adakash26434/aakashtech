<?php
// Run: php tests/sms-balance-test.php
// Proves a client is never charged for an SMS that did not go out, whatever fails.
// A fake SMS line lets each failure be made on purpose.
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-balance-test.sqlite');
@unlink(sys_get_temp_dir() . '/aakash-balance-test.sqlite');
$_SERVER['HTTP_HOST'] = 'localhost';
chdir(dirname(__DIR__));
ob_start(); require 'config.php'; ob_end_clean();
error_reporting(E_ALL & ~E_NOTICE);
$fail = 0;
function check($name, $ok) { global $fail; echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$ok) { $fail++; } }
function credits($conn, $id) { return (int) billing_unit_balances($conn, $id)['sms']; }
function states($conn, $campaignId) {
    $r = $conn->query("SELECT status, COUNT(*) AS n FROM sms_messages WHERE campaign_id = $campaignId GROUP BY status");
    $out = array(); while ($r && ($row = $r->fetch_assoc())) { $out[$row['status']] = (int) $row['n']; } return $out;
}

$conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('Bal','bal@example.com','x','active')");
$cid = (int) $conn->insert_id;
billing_set_setting($conn, 'sms_line_provider', 'aakash'); billing_set_setting($conn, 'sms_line_token', 'TEST-TOKEN');
$audiences = array_keys(billing_audiences()); $purposes = array_keys(billing_purposes());
function job($numbers, $text = 'Hello from the test') {
    global $audiences, $purposes;
    return array('text' => $text, 'numbers' => $numbers, 'audience' => $audiences[0], 'purpose' => $purposes[0], 'sender' => '', 'name' => 'T');
}
$sentToLine = array();
$GLOBALS['SMS_TEST_VENDOR'] = function ($numbers, $text, $sender) use (&$sentToLine) { $sentToLine = array_merge($sentToLine, $numbers); return array('code' => '', 'rejected' => array()); };

// 1. Normal send: charged once per number
billing_add_units($conn, $cid, 'sms', 100);
$res = sms_send($conn, $cid, job("9841000001\n9841000002\n9841000003"));
check('a normal send works', $res['ok'] === true);
check('normal send charges one credit per number', credits($conn, $cid) === 97);
check('all three messages are marked sent', (states($conn, $res['campaign_id'])['sent'] ?? 0) === 3);

// 2. The SMS line refuses everything: nothing charged
$GLOBALS['SMS_TEST_VENDOR'] = function () { return array('code' => 'line-off', 'rejected' => array()); };
$before = credits($conn, $cid);
$res = sms_send($conn, $cid, job("9841000011\n9841000012"));
check('line down: the client is told it failed', $res['ok'] === false);
check('line down: every credit is returned', credits($conn, $cid) === $before);
check('line down: messages are marked failed with a reason', (states($conn, $res['campaign_id'])['failed'] ?? 0) === 2);

// 3. The line refuses some numbers: only those are returned
$GLOBALS['SMS_TEST_VENDOR'] = function ($n) { return array('code' => '', 'rejected' => array($n[1])); };
$before = credits($conn, $cid);
$res = sms_send($conn, $cid, job("9841000021\n9841000022\n9841000023"));
check('partial refusal: the send still succeeds', $res['ok'] === true);
check('partial refusal: only the two accepted numbers are charged', credits($conn, $cid) === $before - 2);

// 4. The line crashes (an exception): nothing charged, no error page, work continues
$GLOBALS['SMS_TEST_VENDOR'] = function () { throw new RuntimeException('connection reset'); };
$before = credits($conn, $cid);
$res = sms_send($conn, $cid, job("9841000031\n9841000032"));
check('line exception: handled, the client gets a clear answer', is_array($res) && $res['ok'] === false);
check('line exception: credits returned', credits($conn, $cid) === $before);
$mix = states($conn, $res['campaign_id']);
check('line exception: messages are failed, none stuck', ($mix['failed'] ?? 0) === 2 && empty($mix['sending']) && empty($mix['queued']));

// 5. Not enough credits: nothing recorded, nothing sent
$GLOBALS['SMS_TEST_VENDOR'] = function ($n) use (&$sentToLine) { $sentToLine = array_merge($sentToLine, $n); return array('code' => '', 'rejected' => array()); };
$sentToLine = array(); $before = credits($conn, $cid);
$many = implode("\n", array_map(function ($i) { return '98410' . str_pad((string) (50000 + $i), 5, '0', STR_PAD_LEFT); }, range(1, $before + 5)));
$res = sms_send($conn, $cid, job($many));
check('too few credits: refused', $res['ok'] === false);
check('too few credits: balance untouched and nothing sent', credits($conn, $cid) === $before && $sentToLine === array());

// 6. A run that stopped while handing messages to the line: no double send, credits returned
$before = credits($conn, $cid);
billing_tx($conn, 'begin'); sms_take_credits($conn, $cid, 2);
$campaign = sms_insert_campaign($conn, $cid, 'Crashed', 'Hello', '', 2, 'sending', '', 'x', 'y', sms_store_contacts(sms_collect_contacts("9841000041\n9841000042")['contacts']));
$ids = sms_insert_rendered($conn, $cid, $campaign, 0, 'dashboard', '', sms_render_messages('Hello', sms_collect_contacts("9841000041\n9841000042")['contacts'])['messages']);
billing_tx($conn, 'commit');
sms_mark_messages($conn, $ids, 'sending', '');   // as if the process died inside the line call
$sentToLine = array();
sms_deliver_campaign($conn, $campaign);
check('interrupted send: nothing is sent a second time', $sentToLine === array());
check('interrupted send: credits are returned', credits($conn, $cid) === $before);
check('interrupted send: marked "unconfirmed", not sent', (states($conn, $campaign)['failed'] ?? 0) === 2);
$why = $conn->query("SELECT error_text FROM sms_messages WHERE campaign_id = $campaign LIMIT 1")->fetch_assoc();
check('interrupted send: the reason is recorded', $why['error_text'] === 'unconfirmed');

// 7. The phone network says "not delivered": credits come back once, and only once
$GLOBALS['SMS_TEST_VENDOR'] = function () { return array('code' => '', 'rejected' => array()); };
$res = sms_send($conn, $cid, job('9841000051'));
$afterSend = credits($conn, $cid);
check('network failure: first the credit is used', $res['ok'] === true);
check('network failure report returns the credit', sms_dlr_apply($conn, '9841000051', 'failed') === true && credits($conn, $cid) === $afterSend + 1);
check('the same report again returns nothing more', sms_dlr_apply($conn, '9841000051', 'failed') === false && credits($conn, $cid) === $afterSend + 1);
$res = sms_send($conn, $cid, job('9841000052'));
$afterSend = credits($conn, $cid);
sms_dlr_apply($conn, '9841000052', 'delivered');
check('a delivered message keeps its charge', credits($conn, $cid) === $afterSend);
billing_set_setting($conn, 'sms_refund_undelivered', '0');
$res = sms_send($conn, $cid, job('9841000053'));
$afterSend = credits($conn, $cid);
sms_dlr_apply($conn, '9841000053', 'failed');
check('with the switch off, a network failure is not refunded', credits($conn, $cid) === $afterSend);
billing_set_setting($conn, 'sms_refund_undelivered', '1');

// 8. A scheduled send is charged now and cancelled for a full return
$before = credits($conn, $cid);
$job = job("9841000061\n9841000062"); $job['scheduled_at'] = date('Y-m-d H:i:s', strtotime('+2 days', time() + 5 * 3600 + 45 * 60));
$res = sms_send($conn, $cid, $job);
check('scheduled: credits are held', $res['ok'] === true && credits($conn, $cid) === $before - 2);
$cancel = sms_cancel_scheduled($conn, $cid, (int) $res['campaign_id']);
check('scheduled: cancelling returns every credit', credits($conn, $cid) === $before);

// 9. The books balance: every credit bought is either still there or paid for a message that left
$left = credits($conn, $cid);
$used = (int) $conn->query("SELECT COALESCE(SUM(parts),0) AS n FROM sms_messages WHERE client_id = $cid AND status IN ('sent','queued','sending') AND NOT (delivery = 'failed' AND error_text = 'refunded')")->fetch_assoc()['n'];
check('books balance: 100 bought = credits left + messages that left (' . $left . ' + ' . $used . ')', 100 === $left + $used);
// Timeouts are unconfirmed (never retried), real refusals keep their own code
check('vendor: no answer means unconfirmed, a refusal keeps its code', sms_unsure_code(array('ok' => false, 'status' => 0, 'body' => ''), 'line-rejected') === 'unconfirmed' && sms_unsure_code(array('ok' => false, 'status' => 500, 'body' => ''), 'line-rejected') === 'line-rejected');
check('retry: unconfirmed rows are excluded from the retry query', strpos(file_get_contents(__DIR__ . '/../includes/sms/logs.php'), "error_text <> 'unconfirmed'") !== false);
// client_ref: only one request can hold a ref at a time; releasing it frees it for a retry
check('client_ref: the first request reserves the ref', sms_api_reserve_ref($conn, 7, 'order-42') === true);
check('client_ref: a second request for the same ref is refused while the first is in progress', sms_api_reserve_ref($conn, 7, 'order-42') === false);
check('client_ref: the same ref on another token is a different reservation', sms_api_reserve_ref($conn, 8, 'order-42') === true);
sms_api_release_ref($conn, 7, 'order-42');
check('client_ref: after a release the ref can be sent again', sms_api_reserve_ref($conn, 7, 'order-42') === true);
echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
