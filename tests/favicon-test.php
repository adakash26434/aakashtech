<?php
// Run: php tests/favicon-test.php   (admin tab icon: what is accepted, what is refused, what the page head gets)
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-favicon-test.sqlite');
@unlink(sys_get_temp_dir() . '/aakash-favicon-test.sqlite');
$_SERVER['HTTP_HOST'] = 'localhost';
chdir(dirname(__DIR__));
ob_start(); require 'config.php'; ob_end_clean();
$GLOBALS['KYC_TEST_UPLOADS'] = true;
$fail = 0;
function check($name, $ok) { global $fail; echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$ok) { $fail++; } }
function png($w, $h) {
    $raw = '';
    for ($y = 0; $y < $h; $y++) { $raw .= "\x00" . str_repeat("\x11\x88\x7a\xff", $w); }
    $chunk = function ($type, $data) { return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data)); };
    return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 6, 0, 0, 0)) . $chunk('IDAT', gzcompress($raw)) . $chunk('IEND', '');
}
function upload($bytes, $name) { $f = tempnam(sys_get_temp_dir(), 'fav'); file_put_contents($f, $bytes); return array('name' => $name, 'tmp_name' => $f, 'error' => 0, 'size' => strlen($bytes)); }
$target = dirname(__DIR__) . '/uploads/site-favicon.png';
@unlink($target);

$ok = site_store_favicon(upload(png(128, 128), 'icon.png'));
check('a square PNG is accepted and stored', $ok['ok'] && $ok['path'] === 'uploads/site-favicon.png' && is_file($target));
check('a tiny image is refused', !site_store_favicon(upload(png(16, 16), 'a.png'))['ok']);
check('a wide logo is refused (unreadable in a tab)', !site_store_favicon(upload(png(400, 80), 'wide.png'))['ok']);
check('an SVG is refused (it can carry a script)', !site_store_favicon(upload('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><script>alert(1)</script></svg>', 'x.svg'))['ok']);
check('a text file renamed to .png is refused', !site_store_favicon(upload('hello, not an image', 'x.png'))['ok']);
check('a PHP file renamed to .png is refused', !site_store_favicon(upload("<?php echo 1;", 'x.png'))['ok']);
check('a file over 500 KB is refused', !site_store_favicon(array('name' => 'big.png', 'tmp_name' => $target, 'error' => 0, 'size' => 600000))['ok']);
check('a refused upload leaves the earlier icon in place', is_file($target));
$ico = "\x00\x00\x01\x00\x01\x00\x10\x10\x00\x00\x01\x00\x20\x00" . str_repeat("\x00", 40);
$r = site_store_favicon(upload($ico, 'favicon.ico'));
check('no file chosen is fine and changes nothing', site_store_favicon(array('error' => UPLOAD_ERR_NO_FILE))['path'] === null);

billing_set_setting($conn, 'public_details_managed', '1');
billing_set_setting($conn, 'favicon_path', 'uploads/site-favicon.png');
$html = site_favicon_html($conn, '');
check('the page head gets the icon with a version for cache refresh', strpos($html, 'rel="icon"') !== false && strpos($html, 'uploads/site-favicon.png?v=') !== false && strpos($html, 'apple-touch-icon') !== false);
check('portal pages get the path one folder up', strpos(site_favicon_html($conn, '../'), 'href="../uploads/site-favicon.png') !== false);
check('a path outside the allowed pattern is never used', site_favicon_file('uploads/../config.php') === '' && site_favicon_file('uploads/site-favicon.php') === '');
@unlink($target); foreach (glob(dirname(__DIR__) . '/uploads/site-favicon.*') as $f) { @unlink($f); }
echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
