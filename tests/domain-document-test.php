<?php
// Run: php tests/domain-document-test.php   (domain request documents stay out of the web root)
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-domain-doc-test.sqlite');
@unlink(sys_get_temp_dir() . '/aakash-domain-doc-test.sqlite');
$_SERVER['HTTP_HOST'] = 'localhost';
chdir(dirname(__DIR__));
ob_start(); require 'config.php'; ob_end_clean();
require_once __DIR__ . '/../includes/domain-check.php';
$GLOBALS['KYC_TEST_UPLOADS'] = true;
$fail = 0;
function check($name, $ok) { global $fail; echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$ok) { $fail++; } }
$cid = 4242;
$tmp = tempnam(sys_get_temp_dir(), 'dom');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$res = domain_store_document($cid, array('name' => 'doc.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => 0, 'size' => filesize($tmp)));
check('a document is accepted', !empty($res['ok']));
$rel = isset($res['path']) ? $res['path'] : '';
check('the stored path is in private storage, not uploads/', strpos($rel, 'domains/' . $cid . '/') === 0 && strpos($rel, 'uploads/') !== 0);
check('the file exists in the private folder', $rel !== '' && is_file(billing_kyc_root() . '/' . $rel));
check('the file is not inside the web root', !is_file(dirname(__DIR__) . '/' . $rel) && !is_dir(dirname(__DIR__) . '/uploads/domains/' . $cid));
check('the document can be opened for its own client', domain_safe_file($cid, $rel) !== '');
check('another client cannot open it', domain_safe_file($cid + 1, $rel) === '');
check('a path that climbs out of the folder is refused', domain_safe_file($cid, '../config.php') === '' && domain_safe_file($cid, 'domains/' . $cid . '/../../config.php') === '');
// Older uploads still in uploads/domains/ stay readable.
$legacyDir = dirname(__DIR__) . '/uploads/domains/' . $cid;
@mkdir($legacyDir, 0755, true);
$legacyRel = 'uploads/domains/' . $cid . '/' . str_repeat('a', 32) . '.png';
file_put_contents(dirname(__DIR__) . '/' . $legacyRel, 'x');
check('an older upload in uploads/domains/ is still readable', domain_safe_file($cid, $legacyRel) !== '');
// Clean up what this test wrote.
@unlink(dirname(__DIR__) . '/' . $legacyRel); @rmdir($legacyDir); @rmdir(dirname(__DIR__) . '/uploads/domains');
if ($rel !== '') { @unlink(billing_kyc_root() . '/' . $rel); }
@rmdir(billing_kyc_root() . '/domains/' . $cid);
echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
