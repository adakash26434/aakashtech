<?php
// Run: php tests/money-test.php   (uses a throw-away SQLite file; exits non-zero on failure)
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-money-test.sqlite'); putenv('ESEWA_ID=9800000000');
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
// Typed numbers with spaces
$spaced = sms_collect_contacts("+977 9841000002\n984 100 0004\nRam Thapa 98410 00005\n9841000001 9841000009");
check('server joins numbers typed with spaces', $spaced['ok'] && count($spaced['contacts']) === 5);
check('server keeps the name with its number', $spaced['contacts'][2]['name'] === 'Ram Thapa' && $spaced['contacts'][2]['number'] === '9841000005');
check('server still refuses a real bad number', sms_collect_contacts("12345 678")['ok'] === false);
// Delivery report engine
$conn->query("DELETE FROM sms_messages");
$t = date('Y-m-d H:i:s'); $t2 = date('Y-m-d H:i:s', time() + 40);
$rows = array(
  array('9841000021', 'sent', 'delivered', $t, $t2, ''), array('9851000022', 'sent', 'delivered', $t, $t2, ''), array('9811000023', 'sent', 'failed', $t, null, ''),
  array('9801000024', 'sent', '', $t, null, ''), array('9841000025', 'failed', '', $t, null, 'not-accepted'), array('9841000026', 'failed', '', $t, null, 'not-accepted'),
);
foreach ($rows as $r) {
    $da = $r[4] === null ? 'NULL' : "'" . $r[4] . "'";
    $conn->query("INSERT INTO sms_messages (client_id, recipient, message_text, parts, status, delivery, sent_at, delivery_at, error_text) VALUES ($cid, '{$r[0]}', 'x', 1, '{$r[1]}', '{$r[2]}', '{$r[3]}', $da, '{$r[5]}')");
}
$rep = sms_report($conn, $cid, date('Y-m-d'), date('Y-m-d'));
check('report funnel counts every state', $rep['funnel']['total'] === 6 && $rep['funnel']['delivered'] === 2 && $rep['funnel']['undelivered'] === 1 && $rep['funnel']['awaiting'] === 1 && $rep['funnel']['rejected'] === 2);
check('delivery rate counts only confirmed messages', $rep['rate'] === 67);
check('median delivery time is read from the timestamps', $rep['median'] === 40);
check('carriers are split by prefix', count($rep['carriers']) === 2 && $rep['carriers'][0]['name'] === 'NTC' && $rep['carriers'][0]['total'] === 4);
check('failure reasons are explained in plain words', $rep['reasons'][0]['count'] === 2 && $rep['reasons'][0]['title'] === 'Number not accepted');
check('another client sees an empty report', sms_report($conn, $cid + 999, date('Y-m-d'), date('Y-m-d'))['funnel']['total'] === 0);
$conn->query("UPDATE sms_messages SET delivery = '' WHERE delivery <> ''");
$none = sms_report($conn, $cid, date('Y-m-d'), date('Y-m-d'));
check('with no confirmations the rate is empty and the page explains it', $none['rate'] === null && $none['insights'][0]['title'] === 'Waiting for network confirmation');
check('prefix to carrier map', sms_carrier_for('9779841000001') === 'NTC' && sms_carrier_for('9811000000') === 'Ncell' && sms_carrier_for('9611000000') === 'Smart Cell');
// Shop helpers
require_once $root . '/includes/shop-view.php';
$plans = billing_load_plans($conn);
$byCode = array(); foreach ($plans as $pl) { $byCode[$pl['code']] = $pl; }
$f = shop_plan_facts($byCode['email-5'], 10000);
check('shop: the VAT bill is price plus 13%', abs($f['bill']['total'] - 7500 * 1.13) < 0.01);
check('shop: wallet that covers the bill is said to cover it', $f['covered'] === true && $f['short_by'] == 0.0);
$f = shop_plan_facts($byCode['email-5'], 1000);
check('shop: a short wallet says by how much', $f['covered'] === false && abs($f['short_by'] - (7500 * 1.13 - 1000)) < 0.01);
check('shop: mailbox plans show the price per mailbox', shop_plan_facts($byCode['email-10'], 0)['per_unit'] === 1400.0 && shop_plan_facts($byCode['email-1'], 0)['per_unit'] === 1800.0);
$emailPlans = array($byCode['email-1'], $byCode['email-5'], $byCode['email-10']);
check('shop: best value is the cheapest per mailbox', shop_best_value_code($emailPlans) === 'email-10');
check('shop: no best-value badge when there is nothing to compare', shop_best_value_code(array($byCode['web-company'], $byCode['web-news'])) === '');
$offerPlan = $byCode['domain-com']; $offerPlan['offer_price'] = 1800;
check('shop: an offer shows the real saving', shop_plan_facts($offerPlan, 0)['save_percent'] === 25 && shop_plan_facts($offerPlan, 0)['selling'] == 1800.0);
check('shop: slab services have no flat price', shop_plan_facts($byCode['sms-slab'], 0)['priced'] === false);
check('shop: identity note appears for SMS until approved', shop_identity_note($conn, $cid, array('bulk-sms')) !== '' && shop_identity_note($conn, $cid, array('domain-registration')) === '');
// My Services helpers
$rowsSvc = array(
  array('service_name' => 'Domain A', 'status' => 'active', 'auto_renew' => 1, 'next_renewal' => date('Y-m-d', strtotime('+10 days')), 'price' => 2034),
  array('service_name' => 'Email B', 'status' => 'past_due', 'auto_renew' => 1, 'next_renewal' => date('Y-m-d', strtotime('-2 days')), 'price' => 8475),
  array('service_name' => 'Site C', 'status' => 'active', 'auto_renew' => 1, 'next_renewal' => date('Y-m-d', strtotime('+90 days')), 'price' => 50000),
  array('service_name' => 'Hosting D', 'status' => 'active', 'auto_renew' => 0, 'next_renewal' => date('Y-m-d', strtotime('+5 days')), 'price' => 1000),
  array('service_name' => 'Old E', 'status' => 'cancelled', 'auto_renew' => 1, 'next_renewal' => date('Y-m-d', strtotime('+3 days')), 'price' => 700),
);
$ren = service_renewal_summary($rowsSvc, 5000);
check('renewals: only auto-renewing active or overdue services within 30 days count', count($ren['items']) === 2 && $ren['total'] === 10509.0);
check('renewals: the wallet shortfall is worked out', $ren['short_by'] === 5509.0 && service_renewal_summary($rowsSvc, 20000)['short_by'] === 0.0);
check('renewals: the overdue one is first and negative days', $ren['items'][0]['name'] === 'Email B' && $ren['items'][0]['days'] === -2);
check('status words are plain', service_status_info('past_due', true)['label'] === 'Needs funds' && service_status_info('booked', false)['tone'] === 'info' && service_group('suspended') === 'attention' && service_group('cancelled') === 'ended');
// Wallet page helpers
$walletRows = array(
  array('kind' => 'topup', 'direction' => 'credit', 'status' => 'completed', 'amount' => 3000, 'reference_note' => 'ESW1'),
  array('kind' => 'purchase', 'direction' => 'debit', 'status' => 'completed', 'amount' => 2034, 'reference_note' => 'Domain'),
  array('kind' => 'topup', 'direction' => 'credit', 'status' => 'pending', 'amount' => 5000, 'reference_note' => 'BANK'),
  array('kind' => 'topup', 'direction' => 'credit', 'status' => 'rejected', 'amount' => 700, 'reference_note' => 'BAD'),
);
$wt = wallet_totals($walletRows);
check('wallet totals: only finished rows are money in or out, pending is waiting', $wt['in'] === 3000.0 && $wt['out'] === 2034.0 && $wt['waiting'] === 5000.0);
check('wallet words: a waiting top-up and a refused one are told apart', wallet_entry_words($walletRows[2])['state'] === 'Waiting for confirmation' && wallet_entry_words($walletRows[3])['tone'] === 'bad' && wallet_entry_words($walletRows[1])['in'] === false);
$chips = wallet_quick_amounts(5475.5);
check('wallet chips: the exact renewal sum comes first, rounded up', $chips[0]['amount'] === 5476 && $chips[0]['note'] !== '' && count($chips) === 6);
check('wallet chips: round amounts only when nothing is due', count(wallet_quick_amounts(0)) === 5 && wallet_quick_amounts(0)[0]['amount'] === 1000);
// Admin: needs your attention
require_once $root . '/includes/admin-queue.php';
$nowT = strtotime('2026-10-10 12:00:00');
check('queue: nothing waiting gives an empty list', admin_attention_items(array()) === array());
$q = admin_attention_items(array('inquiries' => 4, 'tickets' => 1, 'kyc' => 2, 'topups' => 3, 'sms_short' => 500, 'sms_holding' => 9000, 'renewals' => 1), array('topups' => '2026-10-07 09:00:00', 'kyc' => '2026-10-10 08:00:00'), $nowT);
check('queue: money and blocked clients come before inquiries', array_map(function ($i) { return $i['key']; }, $q) === array('sms_short', 'topups', 'kyc', 'tickets', 'inquiries', 'renewals'));
check('queue: how long the oldest has waited is said in days', $q[1]['age'] === 'oldest 3 days' && $q[2]['age'] === 'since today' && $q[4]['age'] === '');
check('queue: singular and plural wording', $q[3]['title'] === '1 support ticket waiting' && $q[2]['title'] === '2 identities to check' && admin_attention_items(array('kyc' => 1))[0]['title'] === '1 identity to check');
check('queue: the SMS stock row says how short and for how many', $q[0]['tone'] === 'bad' && strpos($q[0]['help'], '9,000') !== false);
// Company facts in the footer
check('company facts: only what was filled in is listed', site_company_facts(array()) === array() && count(site_company_facts(array('company_pan' => ' 601234567 ', 'company_registration' => '', 'office_hours' => 'Sun to Fri'))) === 2 && site_company_facts(array('company_pan' => '601234567'))[0]['value'] === '601234567');
// Support wording
require_once $root . '/includes/support-view.php';
check('support: topic and service go on top of what the client wrote', support_compose_description('sms', 'Domain acme.com.np (#1)', ' It failed ') === "About: SMS or voice\nService: Domain acme.com.np (#1)\n\nIt failed");
check('support: an unknown topic is ignored and no service adds no line', support_compose_description('nope', '', 'Hello') === 'Hello');
check('support: status is in plain words and groups into open or done', support_status_words('open')[0] === 'Waiting for us' && support_status_words('resolved')[1] === 'ok' && support_group('in_progress') === 'open' && support_group('closed') === 'done');
check('support: urgency choices explain themselves', count(support_urgency()) === 4 && strpos(support_urgency()['high'][0], 'blocked') !== false);
// Profile
require_once $root . '/includes/profile-view.php';
$none = profile_checklist(array('address' => '', 'company' => ''), '');
check('profile: a new account is 0 of 3 and tells what to do first', $none['done'] === 0 && $none['percent'] === 0 && $none['items'][0]['title'] === 'Verify your identity');
$full = profile_checklist(array('address' => 'Pokhara', 'company' => 'Himal'), 'approved');
check('profile: a complete account is 3 of 3', $full['done'] === 3 && $full['percent'] === 100);
check('profile: a pending identity is not counted as done', profile_checklist(array('address' => 'x', 'company' => ''), 'pending')['done'] === 1);
check('profile: password score rises with length and variety', profile_password_score('abc') === 0 && profile_password_score('abcdefgh') === 1 && profile_password_score('Himal2083') === 3 && profile_password_score('Correct-Horse-9!') === 4);
// Renewal claim: two requests for the same period must not both charge
$conn->query("INSERT INTO client_services (client_id, service_name, next_renewal) VALUES ($cid, 'Renew test', '2026-01-01')");
$renewId = (int) $conn->insert_id;
check('renewal claim: the first request takes the period', billing_claim_renewal($conn, $renewId, '2026-01-01', '2026-02-01') === true);
check('renewal claim: a second request for the same period is refused', billing_claim_renewal($conn, $renewId, '2026-01-01', '2026-02-01') === false);
// Domain refund: a repeated "Return to wallet" must credit the wallet once
$conn->query("INSERT INTO client_services (client_id, service_name, plan_code, status, price, detail_label) VALUES ($cid, 'Domain test', 'domain-com', 'active', 2000, 'acme.com')");
$domId = (int) $conn->insert_id;
$before = bal($conn, $cid);
$firstRefund = billing_refund_domain($conn, $domId);
$secondRefund = billing_refund_domain($conn, $domId);
check('domain refund: the first return credits the wallet once', $firstRefund === '' && bal($conn, $cid) == $before + 2000.0);
check('domain refund: a second return is refused and credits nothing', $secondRefund !== '' && bal($conn, $cid) == $before + 2000.0);
// Top-up: one payment reference cannot be sent twice (case and spaces ignored)
$topMethods = billing_payment_methods($conn);
$topMethod = $topMethods[0]['code'];
$firstTop = billing_request_topup($conn, $cid, 500, $topMethod, 'REF 98765');
$dupeTop = billing_request_topup($conn, $cid, 500, $topMethod, ' ref98765 ');
check('top-up: the first reference is accepted', $firstTop === '');
check('top-up: the same reference again is refused, even with other case or spaces', $dupeTop !== '');
// Terms: a new client has not accepted; recording the tick makes the gate open
$conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('Terms','terms@example.com','x','active')");
$termsId = (int) $conn->insert_id;
check('terms: a new client has not accepted yet', terms_client_has_accepted($conn, $termsId) === false);
check('terms: the tick is recorded with the current version', terms_record_acceptance($conn, $termsId) === true && terms_client_has_accepted($conn, $termsId) === true);
$stored = $conn->query("SELECT terms_version, terms_accepted_at FROM client_users WHERE id = $termsId")->fetch_assoc();
check('terms: version and time are stored on the account', $stored['terms_version'] === TERMS_VERSION && $stored['terms_accepted_at'] !== '' && $stored['terms_accepted_at'] !== null);

// Email verification: a pending account opens with its link once, and not after it expires
email_verify_ensure_columns($conn);
$emailToken = str_repeat('ab', 32);
$emailHash = hash('sha256', $emailToken);
$future = date('Y-m-d H:i:s', time() + 3600);
$past = date('Y-m-d H:i:s', time() - 3600);
$conn->query("INSERT INTO client_users (name,email,password,status,email_verify_hash,email_verify_expires) VALUES ('Mail','mail@example.com','x','pending','$emailHash','$future')");
$mailId = (int) $conn->insert_id;
check('email: a pending account is opened by its link', email_verify_consume($conn, $emailToken) === $mailId);
check('email: the same link does not open it again', email_verify_consume($conn, $emailToken) === 0);
check('email: the account is active after the link', (string) $conn->query("SELECT status FROM client_users WHERE id = $mailId")->fetch_assoc()['status'] === 'active');
$oldToken = str_repeat('cd', 32);
$conn->query("INSERT INTO client_users (name,email,password,status,email_verify_hash,email_verify_expires) VALUES ('Old','old@example.com','x','pending','" . hash('sha256', $oldToken) . "','$past')");
check('email: an expired link does not open the account', email_verify_consume($conn, $oldToken) === 0);
check('email: a malformed token is refused', email_verify_consume($conn, 'not-a-token') === 0);

// Auto-renew: an ended term cannot be switched back on (no surprise charge); a lapsed one expires
$conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('Sub','sub@example.com','x','active')");
$subId = (int) $conn->insert_id;
billing_ensure_wallet($conn, $subId);
$endedDate = date('Y-m-d', strtotime('-10 days'));
$conn->query("INSERT INTO client_services (client_id, service_name, plan_code, status, price, billing_cycle, auto_renew, next_renewal) VALUES ($subId, 'Ended plan', 'email-1', 'active', 2000, 'yearly', 0, '$endedDate')");
$endedId = (int) $conn->insert_id;
check('auto-renew: an ended term cannot be switched on', billing_set_auto_renew($conn, $subId, $endedId, true) === false);
billing_process_renewals($conn, $subId);
check('auto-renew: a lapsed service with auto-renew off is marked expired', (string) $conn->query("SELECT status FROM client_services WHERE id = $endedId")->fetch_assoc()['status'] === 'expired');
$futureDate = date('Y-m-d', strtotime('+60 days'));
$conn->query("INSERT INTO client_services (client_id, service_name, plan_code, status, price, billing_cycle, auto_renew, next_renewal) VALUES ($subId, 'Live plan', 'email-1', 'active', 2000, 'yearly', 0, '$futureDate')");
$liveId = (int) $conn->insert_id;
check('auto-renew: a service still in its term can be switched on', billing_set_auto_renew($conn, $subId, $liveId, true) === true);

// Roles: staff are limited; owner and the existing admin role keep full access
$_SESSION['admin_role'] = 'staff';
check('roles: a staff admin is recognised', admin_is_staff() === true);
$_SESSION['admin_role'] = 'owner';
check('roles: an owner is not staff', admin_is_staff() === false);
$_SESSION['admin_role'] = 'admin';
check('roles: the existing admin role keeps full access', admin_is_staff() === false);
unset($_SESSION['admin_role']);
check('roles: no role set means full access, as before', admin_is_staff() === false);

// cPanel passwords: sealed with AES-256-GCM, round-trip, and any change to the stored value is refused
$panelSealed = panel_pass_seal($conn, 'Panel#Pass1');
check('panel: a new password is sealed with GCM', strpos($panelSealed, 'enc2:') === 0 && panel_pass_open($conn, $panelSealed) === 'Panel#Pass1');
check('panel: a changed sealed value does not open', panel_pass_open($conn, substr($panelSealed, 0, -3) . (substr($panelSealed, -3) === 'AAA' ? 'BBB' : 'AAA')) === '');

// Audit log: an admin submission is recorded by action and id, never by its values
$_SESSION['admin_id'] = 1; $_SESSION['admin_role'] = 'owner';
$_POST = array('approve_topup' => '1', 'entry_id' => '42', 'new_password' => 'Secret#99', 'csrf_token' => 'abc');
audit_log_post($conn);
$auditRow = audit_recent($conn, 1);
check('audit: the action and record are logged', isset($auditRow[0]) && strpos($auditRow[0]['action'], 'approve_topup') !== false && strpos($auditRow[0]['target'], 'entry_id=42') !== false);
check('audit: passwords and tokens are never logged', isset($auditRow[0]) && strpos($auditRow[0]['action'] . $auditRow[0]['target'], 'Secret') === false && strpos($auditRow[0]['action'] . $auditRow[0]['target'], 'new_password') === false);
$_POST = array();
unset($_SESSION['admin_id'], $_SESSION['admin_role']);

// Rate limit that counts every request: the limit holds even when the requests come in a burst
$burstScope = 'test-burst';
$burstResults = array();
for ($burst = 0; $burst < 7; $burst++) { $burstResults[] = auth_attempt_reserve($conn, $burstScope, 5, 3600); }
check('rate limit: the first five requests go ahead and the sixth is refused', array_sum($burstResults) === 5 && $burstResults[4] === true && $burstResults[5] === false);

// Per-account sign-in limit: failures follow the account, not the address, and a correct password clears them
for ($guess = 0; $guess < 10; $guess++) { auth_note_attempt($conn, 'acct-777', 'account'); }
check('account limit: ten failed passwords block that account from any address', auth_attempt_blocked($conn, 'acct-777', 10, 900, 'account') === true);
auth_clear_attempts($conn, 'acct-777', 'account');
check('account limit: a correct password clears the count', auth_attempt_blocked($conn, 'acct-777', 10, 900, 'account') === false);

// AI assistant keys: sealed at rest, opened on read, and never written into a backup.
$aiSealed = panel_pass_seal($conn, 'AIza-test-key-123');
check('AI key is stored sealed, not as plain text', $aiSealed !== 'AIza-test-key-123' && strpos($aiSealed, 'enc2:') === 0);
check('AI key opens back to the same value', panel_pass_open($conn, $aiSealed) === 'AIza-test-key-123');
$aiRow = backup_redact('site_settings', array('setting_key' => 'ai_gemini_key', 'setting_value' => $aiSealed));
check('backup redacts the Gemini key', $aiRow['setting_value'] === '');
$aiRow = backup_redact('site_settings', array('setting_key' => 'ai_deepseek_key', 'setting_value' => 'sk-plain'));
check('backup redacts the DeepSeek key', $aiRow['setting_value'] === '');
$aiRow = backup_redact('site_settings', array('setting_key' => 'site_name', 'setting_value' => 'Aakash'));
check('backup keeps ordinary settings', $aiRow['setting_value'] === 'Aakash');

echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
