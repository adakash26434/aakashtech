<?php
/**
 * Billing: identity (KYC) fields. One list describes every section and field, and it drives the
 * form the client fills, the checks on the server, and the read-only views for client and admin.
 * Detailed answers are stored as JSON in client_kyc.details; name, ID number and the like also
 * stay in their own columns so search and duplicate checks keep working.
 */

function kyc_provinces()
{
    return array(
        'Koshi' => array('Bhojpur', 'Dhankuta', 'Ilam', 'Jhapa', 'Khotang', 'Morang', 'Okhaldhunga', 'Panchthar', 'Sankhuwasabha', 'Solukhumbu', 'Sunsari', 'Taplejung', 'Terhathum', 'Udayapur'),
        'Madhesh' => array('Bara', 'Dhanusha', 'Mahottari', 'Parsa', 'Rautahat', 'Saptari', 'Sarlahi', 'Siraha'),
        'Bagmati' => array('Bhaktapur', 'Chitwan', 'Dhading', 'Dolakha', 'Kathmandu', 'Kavrepalanchok', 'Lalitpur', 'Makwanpur', 'Nuwakot', 'Ramechhap', 'Rasuwa', 'Sindhuli', 'Sindhupalchok'),
        'Gandaki' => array('Baglung', 'Gorkha', 'Kaski', 'Lamjung', 'Manang', 'Mustang', 'Myagdi', 'Nawalpur', 'Parbat', 'Syangja', 'Tanahun'),
        'Lumbini' => array('Arghakhanchi', 'Banke', 'Bardiya', 'Dang', 'Eastern Rukum', 'Gulmi', 'Kapilvastu', 'Palpa', 'Parasi', 'Pyuthan', 'Rolpa', 'Rupandehi'),
        'Karnali' => array('Dailekh', 'Dolpa', 'Humla', 'Jajarkot', 'Jumla', 'Kalikot', 'Mugu', 'Salyan', 'Surkhet', 'Western Rukum'),
        'Sudurpashchim' => array('Achham', 'Baitadi', 'Bajhang', 'Bajura', 'Dadeldhura', 'Darchula', 'Doti', 'Kailali', 'Kanchanpur')
    );
}

function kyc_districts()
{
    $all = array();
    foreach (kyc_provinces() as $districts) {
        $all = array_merge($all, $districts);
    }
    sort($all);
    return $all;
}

function kyc_local_types()
{
    return array(
        'metropolitan' => 'Metropolitan City',
        'sub-metropolitan' => 'Sub-Metropolitan City',
        'municipality' => 'Municipality',
        'rural' => 'Rural Municipality'
    );
}

function kyc_id_kinds()
{
    return array(
        'citizenship' => 'Citizenship certificate',
        'national_id' => 'National Identity Card',
        'passport' => 'Passport',
        'driving_license' => 'Driving licence'
    );
}

function kyc_genders()
{
    return array('male' => 'Male', 'female' => 'Female', 'other' => 'Other');
}

function kyc_occupations()
{
    return array(
        'business' => 'Business owner', 'service' => 'Job / service', 'government' => 'Government service', 'teacher' => 'Teacher / education',
        'student' => 'Student', 'agriculture' => 'Agriculture', 'professional' => 'Doctor, engineer, lawyer or other professional',
        'it' => 'IT / freelancer', 'ngo' => 'NGO / social work', 'homemaker' => 'Homemaker', 'other' => 'Other'
    );
}

function kyc_org_types()
{
    return array(
        'private' => 'Private limited company', 'public' => 'Public limited company', 'partnership' => 'Partnership firm',
        'proprietorship' => 'Proprietorship / sole firm', 'cooperative' => 'Cooperative', 'ngo' => 'NGO / INGO / association',
        'school' => 'School / college / training institute', 'government' => 'Government body', 'other' => 'Other'
    );
}

function kyc_org_authorities()
{
    return array(
        'company-registrar' => 'Office of the Company Registrar', 'cottage' => 'Department of Cottage and Small Industries',
        'cooperative' => 'Department of Cooperatives', 'dao' => 'District Administration Office', 'swc' => 'Social Welfare Council',
        'education' => 'Education authority', 'other' => 'Other'
    );
}

/** The address block, repeated for permanent, temporary, registered and operating addresses. */
function kyc_address_fields($prefix, $required)
{
    return array(
        array('key' => $prefix . '_province', 'label' => 'Province', 'type' => 'province', 'required' => $required),
        array('key' => $prefix . '_district', 'label' => 'District', 'type' => 'district', 'required' => $required),
        array('key' => $prefix . '_local_type', 'label' => 'Local level', 'type' => 'select', 'options' => kyc_local_types(), 'required' => $required),
        array('key' => $prefix . '_local', 'label' => 'Name of the municipality / rural municipality', 'type' => 'text', 'max' => 80, 'min' => 3, 'required' => $required, 'hint' => 'Example: Pokhara Metropolitan, Machhapuchchhre Rural Municipality'),
        array('key' => $prefix . '_ward', 'label' => 'Ward no.', 'type' => 'ward', 'required' => $required),
        array('key' => $prefix . '_tole', 'label' => 'Tole / street / house no.', 'type' => 'text', 'max' => 120, 'min' => 2, 'required' => false)
    );
}

/** Sections and fields for one account type. Each field: key, label, type, required, options, hint, min, max. */
function kyc_spec($kind)
{
    $idFields = function ($prefix, $who) {
        return array(
            array('key' => $prefix === '' ? 'id_kind' : 'contact_id_kind', 'label' => 'Document type', 'type' => 'select', 'options' => kyc_id_kinds(), 'required' => true),
            array('key' => $prefix === '' ? 'id_number' : 'contact_id_number', 'label' => 'Document number', 'type' => 'idnumber', 'required' => true, 'hint' => 'Copy it exactly as printed on the document.'),
            array('key' => $prefix . 'issue_date', 'label' => 'Issue date', 'type' => 'date', 'required' => true, 'past' => true),
            array('key' => $prefix . 'issue_district', 'label' => 'Issued from (district)', 'type' => 'district', 'required' => true, 'plain' => true)
        );
    };
    if ($kind === 'organization') {
        return array(
            array('id' => 'org', 'title' => 'The organization', 'help' => 'As written on the registration certificate.', 'fields' => array(
                array('key' => 'org_name', 'label' => 'Organization name', 'type' => 'text', 'max' => 200, 'min' => 2, 'required' => true),
                array('key' => 'org_type', 'label' => 'Type', 'type' => 'select', 'options' => kyc_org_types(), 'required' => true),
                array('key' => 'registration_number', 'label' => 'Registration number', 'type' => 'reference', 'required' => true, 'min' => 3),
                array('key' => 'org_authority', 'label' => 'Registered with', 'type' => 'select', 'options' => kyc_org_authorities(), 'required' => true),
                array('key' => 'reg_date', 'label' => 'Registration date', 'type' => 'date', 'required' => true, 'past' => true),
                array('key' => 'tax_number', 'label' => 'PAN / VAT number', 'type' => 'reference', 'required' => true, 'min' => 3),
                array('key' => 'business_nature', 'label' => 'What the organization does', 'type' => 'text', 'max' => 160, 'min' => 4, 'required' => true),
                array('key' => 'org_phone', 'label' => 'Office phone', 'type' => 'phone', 'required' => true),
                array('key' => 'org_email', 'label' => 'Office email', 'type' => 'email', 'required' => true),
                array('key' => 'org_website', 'label' => 'Website (if any)', 'type' => 'text', 'max' => 120, 'required' => false)
            )),
            array('id' => 'reg_address', 'title' => 'Registered address', 'help' => 'The address on the registration certificate.', 'fields' => kyc_address_fields('perm', true)),
            array('id' => 'op_address', 'title' => 'Office address now', 'help' => 'Where the organization works from today.', 'same' => 'temp', 'fields' => kyc_address_fields('temp', true)),
            array('id' => 'person', 'title' => 'Authorized person', 'help' => 'The person who will use the account for the organization.', 'fields' => array_merge(array(
                array('key' => 'contact_name', 'label' => 'Full name', 'type' => 'text', 'max' => 160, 'min' => 3, 'required' => true),
                array('key' => 'designation', 'label' => 'Position', 'type' => 'text', 'max' => 80, 'min' => 2, 'required' => true),
                array('key' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => kyc_genders(), 'required' => true),
                array('key' => 'mobile', 'label' => 'Mobile number', 'type' => 'mobile', 'required' => true),
                array('key' => 'contact_email', 'label' => 'Email', 'type' => 'email', 'required' => true)
            ), $idFields('contact_', 'person'))),
            array('id' => 'purpose', 'title' => 'How you will use it', 'fields' => array(
                array('key' => 'purpose', 'label' => 'What will the SMS and voice calls be for?', 'type' => 'textarea', 'max' => 500, 'min' => 12, 'required' => true, 'hint' => 'Example: fee reminders to parents, delivery updates to customers.')
            ))
        );
    }
    return array(
        array('id' => 'about', 'title' => 'About you', 'help' => 'As written on your identity document.', 'fields' => array(
            array('key' => 'full_name', 'label' => 'Full name', 'type' => 'text', 'max' => 160, 'min' => 3, 'required' => true),
            array('key' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => kyc_genders(), 'required' => true),
            array('key' => 'dob', 'label' => 'Date of birth', 'type' => 'date', 'required' => true, 'past' => true, 'adult' => true),
            array('key' => 'occupation', 'label' => 'Occupation', 'type' => 'select', 'options' => kyc_occupations(), 'required' => true),
            array('key' => 'mobile', 'label' => 'Mobile number', 'type' => 'mobile', 'required' => true),
            array('key' => 'alt_mobile', 'label' => 'Another number (optional)', 'type' => 'mobile', 'required' => false)
        )),
        array('id' => 'document', 'title' => 'Identity document', 'help' => 'Use a document that is valid today.', 'fields' => $idFields('', 'person')),
        array('id' => 'permanent', 'title' => 'Permanent address', 'fields' => kyc_address_fields('perm', true)),
        array('id' => 'temporary', 'title' => 'Address where you live now', 'same' => 'temp', 'fields' => kyc_address_fields('temp', true)),
        array('id' => 'family', 'title' => 'Family', 'help' => 'As written on your citizenship certificate.', 'fields' => array(
            array('key' => 'grandfather_name', 'label' => 'Grandfather\'s name', 'type' => 'text', 'max' => 120, 'min' => 3, 'required' => true),
            array('key' => 'father_name', 'label' => 'Father\'s name', 'type' => 'text', 'max' => 120, 'min' => 3, 'required' => true),
            array('key' => 'mother_name', 'label' => 'Mother\'s name', 'type' => 'text', 'max' => 120, 'min' => 3, 'required' => true)
        )),
        array('id' => 'purpose', 'title' => 'How you will use it', 'fields' => array(
            array('key' => 'purpose', 'label' => 'What will the SMS and voice calls be for?', 'type' => 'textarea', 'max' => 500, 'min' => 12, 'required' => true, 'hint' => 'Example: fee reminders to parents, delivery updates to customers.')
        ))
    );
}

/** Document slots: label, who needs them, and what the camera should open. */
function kyc_doc_spec($kind, $idKind = 'citizenship')
{
    $idLabel = kyc_id_kinds();
    $idName = isset($idLabel[$idKind]) ? $idLabel[$idKind] : 'identity document';
    $twoSided = $idKind !== 'passport';
    if ($kind === 'organization') {
        $slots = array(
            array('slot' => 'registration', 'label' => 'Registration certificate', 'required' => true, 'camera' => 'environment', 'hint' => 'The whole page, clearly readable.'),
            array('slot' => 'tax', 'label' => 'PAN / VAT certificate', 'required' => true, 'camera' => 'environment', 'hint' => 'The whole page, clearly readable.'),
            array('slot' => 'clearance', 'label' => 'Latest tax clearance', 'required' => true, 'camera' => 'environment', 'hint' => 'The latest one you have.'),
            array('slot' => 'authority', 'label' => 'Letter naming the authorized person', 'required' => false, 'camera' => 'environment', 'hint' => 'On the organization letterhead, signed and stamped (recommended).'),
            array('slot' => 'identity', 'label' => 'Authorized person: ' . $idName . ($twoSided ? ', front' : ''), 'required' => true, 'camera' => 'environment', 'hint' => 'All four corners visible.'),
        );
        if ($twoSided) {
            $slots[] = array('slot' => 'identity_back', 'label' => 'Authorized person: ' . $idName . ', back', 'required' => true, 'camera' => 'environment', 'hint' => 'All four corners visible.');
        }
        $slots[] = array('slot' => 'photo', 'label' => 'Authorized person: photo of the face', 'required' => true, 'camera' => 'user', 'hint' => 'A clear photo of the face, no sunglasses.');
        return $slots;
    }
    $slots = array(
        array('slot' => 'photo', 'label' => 'Your photo', 'required' => true, 'camera' => 'user', 'hint' => 'A clear photo of your face, no sunglasses or hat.'),
        array('slot' => 'identity', 'label' => $idName . ($twoSided ? ', front' : ''), 'required' => true, 'camera' => 'environment', 'hint' => 'All four corners visible, no glare.')
    );
    if ($twoSided) {
        $slots[] = array('slot' => 'identity_back', 'label' => $idName . ', back', 'required' => true, 'camera' => 'environment', 'hint' => 'All four corners visible, no glare.');
    }
    return $slots;
}

/** Calendar rule for a typed date: year-month-day in AD or BS (Nepali documents are mostly BS). */
function kyc_date_check($value, $calendar, $past, $adult)
{
    if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim((string) $value), $m)) {
        return 'Write the date as year-month-day, for example 2056-04-12.';
    }
    $y = (int) $m[1];
    $mo = (int) $m[2];
    $d = (int) $m[3];
    $calendar = $calendar === 'BS' ? 'BS' : 'AD';
    $adYear = $calendar === 'BS' ? $y - 57 : $y;
    if ($calendar === 'BS') {
        if ($y < 1957 || $y > 2100 || $mo < 1 || $mo > 12 || $d < 1 || $d > 32) {
            return 'That Nepali (BS) date is not valid.';
        }
    } else {
        if ($y < 1900 || !checkdate($mo, $d, $y)) {
            return 'That date is not valid.';
        }
    }
    $thisYear = (int) date('Y');
    if ($past && $adYear > $thisYear) {
        return 'This date cannot be in the future.';
    }
    if ($past && $adYear === $thisYear && $calendar === 'AD' && sprintf('%04d-%02d-%02d', $y, $mo, $d) > date('Y-m-d')) {
        return 'This date cannot be in the future.';
    }
    if ($adult && $adYear > $thisYear - 18) {
        return 'You must be at least 18 years old.';
    }
    return '';
}

function kyc_normalise_date($value)
{
    if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim((string) $value), $m)) {
        return '';
    }
    return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
}

function kyc_id_number_check($kind, $value)
{
    $plain = strtoupper(preg_replace('/[\s]/', '', (string) $value));
    if ($kind === 'national_id') {
        return preg_match('/^\d{10}$/', preg_replace('/[-\/]/', '', $plain)) ? '' : 'A National Identity Card number has 10 digits.';
    }
    if ($kind === 'passport') {
        return preg_match('/^[A-Z0-9]{6,12}$/', $plain) ? '' : 'Enter the passport number as printed (letters and digits only).';
    }
    return preg_match('/^[A-Z0-9\/-]{5,25}$/', $plain) ? '' : 'Enter the document number as printed, with at least 5 characters.';
}

function kyc_mobile_normalise($value)
{
    $digits = preg_replace('/\D/', '', (string) $value);
    if (strlen($digits) === 13 && substr($digits, 0, 3) === '977') {
        $digits = substr($digits, 3);
    }
    return preg_match('/^9[78]\d{8}$/', $digits) ? $digits : '';
}

/**
 * Checks one account type's answers. Returns array('values' => cleaned answers, 'errors' => field => message).
 * "Same as permanent" copies the permanent address into the temporary one before checking.
 */
function kyc_validate($kind, $post)
{
    $values = array();
    $errors = array();
    $post = is_array($post) ? $post : array();
    $get = function ($key) use ($post) {
        return isset($post[$key]) && is_string($post[$key]) ? $post[$key] : '';
    };
    $values['temp_same'] = $get('temp_same') === '1' ? '1' : '';
    $idKind = array_key_exists($get('id_kind'), kyc_id_kinds()) ? $get('id_kind') : 'citizenship';
    $contactKind = array_key_exists($get('contact_id_kind'), kyc_id_kinds()) ? $get('contact_id_kind') : 'citizenship';
    foreach (kyc_spec($kind) as $section) {
        foreach ($section['fields'] as $f) {
            $key = $f['key'];
            $raw = $get($key);
            if ($values['temp_same'] === '1' && strpos($key, 'temp_') === 0) {
                $raw = $get('perm_' . substr($key, 5));
            }
            $required = !empty($f['required']);
            $message = '';
            $clean = '';
            switch ($f['type']) {
                case 'select':
                    $clean = array_key_exists($raw, $f['options']) ? $raw : '';
                    if ($clean === '' && $required) { $message = 'Choose one.'; }
                    break;
                case 'province':
                    $clean = isset(kyc_provinces()[$raw]) ? $raw : '';
                    if ($clean === '' && $required) { $message = 'Choose the province.'; }
                    break;
                case 'district':
                    $clean = in_array($raw, kyc_districts(), true) ? $raw : '';
                    if ($clean === '' && $required) { $message = 'Choose the district.'; }
                    break;
                case 'ward':
                    $clean = preg_match('/^\d{1,2}$/', trim($raw)) && (int) $raw >= 1 && (int) $raw <= 35 ? (string) (int) $raw : '';
                    if ($clean === '' && ($required || trim($raw) !== '')) { $message = 'Ward number is between 1 and 35.'; }
                    break;
                case 'date':
                    $calendar = $get($key . '_cal') === 'BS' ? 'BS' : 'AD';
                    $values[$key . '_cal'] = $calendar;
                    if (trim($raw) === '') {
                        if ($required) { $message = 'Enter the date.'; }
                    } else {
                        $message = kyc_date_check($raw, $calendar, !empty($f['past']), !empty($f['adult']));
                        $clean = $message === '' ? kyc_normalise_date($raw) : '';
                    }
                    break;
                case 'mobile':
                    $clean = kyc_mobile_normalise($raw);
                    if ($clean === '' && ($required || trim($raw) !== '')) { $message = 'Enter a 10-digit Nepal mobile number starting with 98 or 97.'; }
                    break;
                case 'phone':
                    $digits = preg_replace('/\D/', '', $raw);
                    $clean = (strlen($digits) >= 7 && strlen($digits) <= 13) ? $digits : '';
                    if ($clean === '' && $required) { $message = 'Enter a phone number (7 to 13 digits).'; }
                    break;
                case 'email':
                    $clean = filter_var(trim($raw), FILTER_VALIDATE_EMAIL) ? strtolower(trim($raw)) : '';
                    if ($clean === '' && $required) { $message = 'Enter a valid email address.'; }
                    break;
                case 'idnumber':
                    $kindForId = $key === 'contact_id_number' ? $contactKind : $idKind;
                    $clean = billing_kyc_reference($raw, 40);
                    $message = $clean === '' ? 'Enter the document number.' : kyc_id_number_check($kindForId, $raw);
                    break;
                case 'reference':
                    $clean = billing_kyc_reference($raw, 40);
                    if (strlen($clean) < (isset($f['min']) ? $f['min'] : 3) && $required) { $message = 'Enter it as printed on the certificate.'; }
                    break;
                case 'textarea':
                    $clean = billing_plain_block($raw, $f['max']);
                    if ($required && strlen($clean) < $f['min']) { $message = 'Write at least ' . $f['min'] . ' characters.'; }
                    break;
                default:
                    $clean = billing_plain_line($raw, isset($f['max']) ? $f['max'] : 120);
                    if ($required && strlen($clean) < (isset($f['min']) ? $f['min'] : 2)) { $message = 'Fill this in.'; }
                    elseif ($clean !== '' && isset($f['min']) && strlen($clean) < $f['min']) { $message = 'That looks too short.'; }
            }
            $values[$key] = $clean;
            if ($message !== '' && !($f['type'] === 'date' && $clean === '' && !$required && trim($raw) === '')) {
                $errors[$key] = $message;
            }
        }
    }
    if (!isset($errors['dob']) && !isset($errors['issue_date']) && !empty($values['dob']) && !empty($values['issue_date']) && ($values['dob_cal'] ?? '') === ($values['issue_date_cal'] ?? '') && $values['issue_date'] <= $values['dob']) {
        $errors['issue_date'] = 'The issue date must be after the date of birth.';
    }
    return array('values' => $values, 'errors' => $errors, 'id_kind' => $idKind, 'contact_kind' => $contactKind);
}

/** The label/value rows a person reads (client and admin views). */
function kyc_summary($kind, $values)
{
    $sections = array();
    foreach (kyc_spec($kind) as $section) {
        $rows = array();
        foreach ($section['fields'] as $f) {
            $v = isset($values[$f['key']]) ? (string) $values[$f['key']] : '';
            if ($v === '') {
                continue;
            }
            switch ($f['type']) {
                case 'select':
                    $v = isset($f['options'][$v]) ? $f['options'][$v] : $v;
                    break;
                case 'date':
                    $cal = isset($values[$f['key'] . '_cal']) ? $values[$f['key'] . '_cal'] : 'AD';
                    $v = $v . ' ' . ($cal === 'BS' ? '(BS)' : '(AD)');
                    break;
                case 'mobile':
                    $v = '+977 ' . $v;
                    break;
            }
            $rows[] = array('label' => $f['label'], 'value' => $v);
        }
        if (!empty($section['same']) && !empty($values['temp_same'])) {
            $rows = array(array('label' => 'Address', 'value' => 'Same as the permanent address'));
        }
        if ($rows) {
            $sections[] = array('title' => $section['title'], 'rows' => $rows);
        }
    }
    return $sections;
}

function kyc_address_line($values, $prefix)
{
    $types = kyc_local_types();
    $parts = array();
    foreach (array('tole' => '', 'local' => '', 'ward' => 'Ward ') as $part => $lead) {
        $v = isset($values[$prefix . '_' . $part]) ? trim((string) $values[$prefix . '_' . $part]) : '';
        if ($v !== '') {
            $parts[] = $lead . $v;
        }
    }
    foreach (array('district', 'province') as $part) {
        $v = isset($values[$prefix . '_' . $part]) ? trim((string) $values[$prefix . '_' . $part]) : '';
        if ($v !== '') {
            $parts[] = $v;
        }
    }
    return implode(', ', $parts);
}
