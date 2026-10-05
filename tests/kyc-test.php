<?php
// Run: php tests/kyc-test.php   (identity form rules, saving, documents, review)
putenv('DB_DRIVER=sqlite'); putenv('SQLITE_PATH=' . sys_get_temp_dir() . '/aakash-kyc-test.sqlite');
@unlink(sys_get_temp_dir() . '/aakash-kyc-test.sqlite');
$_SERVER['HTTP_HOST'] = 'localhost';
chdir(dirname(__DIR__));
ob_start(); require 'config.php'; ob_end_clean();
$GLOBALS['KYC_TEST_UPLOADS'] = true;
$fail = 0;
function check($name, $ok) { global $fail; echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n"; if (!$ok) { $fail++; } }
function png() { $f = tempnam(sys_get_temp_dir(), 'kyc'); file_put_contents($f, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')); return array('name' => 'a.png', 'type' => 'image/png', 'tmp_name' => $f, 'error' => 0, 'size' => filesize($f)); }
$conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('K','kyc@example.com','x','active')"); $cid = (int) $conn->insert_id;

$person = array('account_kind' => 'individual', 'full_name' => 'Ram Bahadur Thapa', 'gender' => 'male', 'dob' => '2050-05-20', 'dob_cal' => 'BS', 'occupation' => 'business', 'mobile' => '+977 9841234567', 'alt_mobile' => '',
  'id_kind' => 'citizenship', 'id_number' => '27-01-72-12345', 'issue_date' => '2070-03-15', 'issue_date_cal' => 'BS', 'issue_district' => 'Kaski',
  'perm_province' => 'Gandaki', 'perm_district' => 'Kaski', 'perm_local_type' => 'metropolitan', 'perm_local' => 'Pokhara', 'perm_ward' => '8', 'perm_tole' => 'Lakeside',
  'temp_same' => '1', 'grandfather_name' => 'Hari Prasad Thapa', 'father_name' => 'Shyam Bahadur Thapa', 'mother_name' => 'Sita Thapa', 'purpose' => 'Fee reminders to parents of the school.');
$v = kyc_validate('individual', $person);
check('a complete personal form has no errors', $v['errors'] === array());
check('same-as-permanent copies the address', $v['values']['temp_district'] === 'Kaski' && $v['values']['temp_local'] === 'Pokhara');
check('the mobile number is stored as 10 digits', $v['values']['mobile'] === '9841234567');
check('77 districts in 7 provinces', count(kyc_districts()) === 77 && count(kyc_provinces()) === 7);

foreach (array(
  array('gender', '', 'Choose one.'), array('dob', '2050-13-40', 'not valid'), array('dob', '2020-01-01', 'at least 18'), array('mobile', '12345', '98 or 97'),
  array('perm_ward', '99', 'between 1 and 35'), array('perm_district', 'Atlantis', 'Choose the district'), array('father_name', '', 'Fill this in'),
) as $c) {
    $bad = $person; $bad[$c[0]] = $c[1]; if ($c[0] === 'dob' && $c[1] === '2020-01-01') { $bad['dob_cal'] = 'AD'; }
    $r = kyc_validate('individual', $bad);
    check('rejects ' . $c[0] . ' = "' . $c[1] . '"', isset($r['errors'][$c[0]]) && stripos($r['errors'][$c[0]], $c[2]) !== false);
}
$bad = $person; $bad['issue_date'] = '2040-01-01';
check('issue date cannot be before birth', isset(kyc_validate('individual', $bad)['errors']['issue_date']));
$bad = $person; $bad['id_kind'] = 'national_id'; $bad['id_number'] = '12345';
check('a National ID number needs 10 digits', isset(kyc_validate('individual', $bad)['errors']['id_number']));
$bad['id_number'] = '123-456-7890';
check('a National ID number with dashes is accepted', !isset(kyc_validate('individual', $bad)['errors']['id_number']));
$bad = $person; $bad['temp_same'] = ''; 
check('a different current address must be filled in', isset(kyc_validate('individual', $bad)['errors']['temp_district']));

// Saving and documents
$files = array('doc_photo' => png(), 'doc_identity' => png());
$r = billing_kyc_submit_detailed($conn, $cid, $person, $files);
check('missing back of the citizenship is reported, nothing lost', !$r['ok'] && isset($r['errors']['doc_identity_back']));
$row = billing_kyc_load($conn, $cid);
check('an incomplete form is saved as a draft, not sent for review', $row['status'] === '' && billing_kyc_values($row)['father_name'] === 'Shyam Bahadur Thapa');
$files = array('cam_identity_back' => png());
$r = billing_kyc_submit_detailed($conn, $cid, $person, $files);
check('a photo from the camera input counts, earlier uploads are kept', $r['ok'] === true);
$row = billing_kyc_load($conn, $cid);
check('a complete form goes to review', $row['status'] === 'pending' && $row['submitted_at'] !== '');
check('plain columns still carry name, number and address', $row['full_name'] === 'Ram Bahadur Thapa' && $row['id_number'] === '27-01-72-12345' && strpos($row['address'], 'Pokhara') !== false);
check('all three documents are stored privately', billing_kyc_safe_path($cid, $row['doc_photo']) !== '' && billing_kyc_safe_path($cid, $row['doc_identity']) !== '' && billing_kyc_safe_path($cid, $row['doc_identity_back']) !== '');
$summary = kyc_summary('individual', billing_kyc_values($row));
$titles = array_map(function ($s) { return $s['title']; }, $summary);
check('the summary lists every section', $titles === array('About you', 'Identity document', 'Permanent address', 'Address where you live now', 'Family', 'How you will use it'));

// Passport has one side only
$cid2 = (function ($conn) { $conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('P','p2@example.com','x','active')"); return (int) $conn->insert_id; })($conn);
$pass = $person; $pass['id_kind'] = 'passport'; $pass['id_number'] = 'PA1234567';
$r = billing_kyc_submit_detailed($conn, $cid2, $pass, array('doc_photo' => png(), 'doc_identity' => png()));
check('a passport needs one side only', $r['ok'] === true);

// Approved cannot be edited; review works
check('an admin can approve', billing_kyc_decide($conn, $cid, 'approve', '') === '' && billing_kyc_load($conn, $cid)['status'] === 'approved');
$r = billing_kyc_submit_detailed($conn, $cid, $person, array());
check('an approved identity is locked', !$r['ok'] && stripos($r['message'], 'approved') !== false);

// Organization
$cid3 = (function ($conn) { $conn->query("INSERT INTO client_users (name,email,password,status) VALUES ('O','o3@example.com','x','active')"); return (int) $conn->insert_id; })($conn);
$org = array('account_kind' => 'organization', 'org_name' => 'Himal Traders Pvt. Ltd.', 'org_type' => 'private', 'registration_number' => '123456/078/079', 'org_authority' => 'company-registrar', 'reg_date' => '2078-04-01', 'reg_date_cal' => 'BS',
  'tax_number' => '601234567', 'business_nature' => 'Wholesale trading', 'org_phone' => '061-520000', 'org_email' => 'info@himal.example',
  'perm_province' => 'Gandaki', 'perm_district' => 'Kaski', 'perm_local_type' => 'metropolitan', 'perm_local' => 'Pokhara', 'perm_ward' => '9', 'temp_same' => '1',
  'contact_name' => 'Sita Gurung', 'designation' => 'Managing Director', 'gender' => 'female', 'mobile' => '9851234567', 'contact_email' => 'sita@himal.example',
  'contact_id_kind' => 'citizenship', 'contact_id_number' => '27-01-70-54321', 'contact_issue_date' => '2068-02-02', 'contact_issue_date_cal' => 'BS', 'contact_issue_district' => 'Kaski', 'purpose' => 'Delivery updates to customers.');
check('a complete organization form has no errors', kyc_validate('organization', $org)['errors'] === array());
$r = billing_kyc_submit_detailed($conn, $cid3, $org, array('doc_registration' => png(), 'doc_tax' => png(), 'doc_clearance' => png(), 'doc_identity' => png(), 'doc_identity_back' => png(), 'doc_photo' => png()));
check('an organization with all documents goes to review (letter is optional)', $r['ok'] === true);
$orgRow = billing_kyc_load($conn, $cid3);
check('organization name and person are in the plain columns', $orgRow['org_name'] === 'Himal Traders Pvt. Ltd.' && $orgRow['contact_name'] === 'Sita Gurung' && $orgRow['contact_id_number'] === '27-01-70-54321');
check('the organization summary has the organization, both addresses, the person and the purpose', count(kyc_summary('organization', billing_kyc_values($orgRow))) === 5);
check('the same ID number on two accounts is found', count(billing_kyc_duplicates($conn, $cid2, 'id_number', '27-01-72-12345')) === 1 && count(billing_kyc_duplicates($conn, $cid, 'id_number', '27-01-72-12345')) === 0);
echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
