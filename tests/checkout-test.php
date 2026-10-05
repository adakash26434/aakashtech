<?php
// Run: php tests/checkout-test.php
// Opens the real checkout page as a client: short wallet, paying once, the same page sent twice, a missing order token, the SMS price hook.
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-checkout-test.sqlite'); @unlink(sys_get_temp_dir() . '/aakash-checkout-test.sqlite');
$_SERVER['HTTP_HOST']='localhost'; $_SERVER['REQUEST_METHOD']='GET'; chdir(dirname(__DIR__));
ob_start(); require 'config.php'; ob_end_clean();
set_error_handler(function($n,$m,$f,$l){ throw new ErrorException($m,0,$n,$f,$l); });
$conn->query("INSERT INTO admin_users (name,email,password,totp_secret) VALUES ('O','o@e.com','x','S')"); $aid=(int)$conn->insert_id;
$conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('Co Client','co@e.com','x','active')"); $cid=(int)$conn->insert_id;
$_SESSION['admin_id']=$aid; $_SESSION['client_id']=$cid; $_SESSION['client_view_admin']=$aid; $_SESSION['admin_role']='admin';
function bal($c,$id){ return billing_balance($c,$id); }
function run($file,$get,$post=[]){ global $conn; $_GET=$get; $_POST=$post; $_REQUEST=array_merge($get,$post); $_SERVER['REQUEST_METHOD']=$post?'POST':'GET'; $_SERVER['SCRIPT_NAME']='/'.$file; ob_start(); try { include $file; } catch (Throwable $e) { ob_end_clean(); throw $e; } return ob_get_clean(); }
function txt($h){ preg_match('#<div class="co-head">.*?(?=<script|\z)#s',$h,$m); return trim(preg_replace('/\s+/',' ',strip_tags(preg_replace('#</(h1|h2|h3|p|li|dt|dd|b|a|div|span)>#','$0 | ',$m[0]??$h)))); }
function token($h){ preg_match('/name="order_token" value="([a-f0-9]+)"/',$h,$m); return $m[1]??''; }
$fail=0; function check($n,$ok){ global $fail; echo ($ok?'PASS ':'FAIL ').$n."\n"; if(!$ok)$fail++; }

// 1. email plan, wallet too small
$h=run('client/checkout.php',['plan'=>'email-5']);
check('page shows steps, summary and the short-wallet alert', strpos($h,'co-steps')!==false && strpos($h,'Order summary')!==false && strpos($h,'is short')!==false || strpos($h,'short.')!==false);
// 2. fund the wallet and pay twice with the same page (double tap)
billing_wallet_credit($conn,$cid,20000);
$h=run('client/checkout.php',['plan'=>'email-5']); $tok=token($h);
$post=['plan'=>'email-5','csrf_token'=>csrf_token(),'order_token'=>$tok,'confirm_purchase'=>'1','domain'=>'himal.com.np','organization'=>'Himal','mailboxes'=>"info\nsales\nhr\naccounts\nsupport"];
$before=bal($conn,$cid);
try { run('client/checkout.php',[ 'plan'=>'email-5'],$post); $first='no redirect'; } catch (Throwable $e) { $first=get_class($e); }
$after1=bal($conn,$cid);
$second=run('client/checkout.php',['plan'=>'email-5'],$post);
$after2=bal($conn,$cid);
check('the first payment is charged once (bill = 7,500 + 13% VAT)', abs(($before-$after1)-8475)<0.01);
check('the same page sent again is refused and charges nothing more', $after2==$after1 && strpos($second,'already sent')!==false);
// 3. a forged / missing token cannot pay
$h=run('client/checkout.php',['plan'=>'email-1']); 
$post2=['plan'=>'email-1','csrf_token'=>csrf_token(),'confirm_purchase'=>'1','domain'=>'acme.com.np','organization'=>'Acme Traders','mailboxes'=>'info'];
$b=bal($conn,$cid); $r=run('client/checkout.php',['plan'=>'email-1'],$post2);
check('a payment without the order token is refused and says why', bal($conn,$cid)==$b && strpos($r,'already sent')!==false);
// 4. SMS plan: quantity estimate hook and slab data are on the page
$h=run('client/checkout.php',['plan'=>'sms-slab']);
check('SMS checkout has the estimate line, the rate data and the script', strpos($h,'id="co-estimate"')!==false && strpos($h,'id="co-slabs"')!==false && strpos($h,'checkout.js')!==false);
echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
