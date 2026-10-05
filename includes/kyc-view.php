<?php
/** Identity (KYC) screens shared by the client and admin portals. Everything printed is escaped. */

function kyc_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function kyc_status_pill($status)
{
    $map = array('' => array('Not submitted', 'is-none'), 'pending' => array('In review', 'is-wait'), 'approved' => array('Verified', 'is-ok'), 'rejected' => array('Needs a change', 'is-bad'));
    $m = isset($map[$status]) ? $map[$status] : $map[''];
    return '<span class="kyc-pill ' . $m[1] . '">' . kyc_e($m[0]) . '</span>';
}

function kyc_input_attrs($f, $values)
{
    $id = 'f-' . $f['key'];
    $val = isset($values[$f['key']]) ? (string) $values[$f['key']] : '';
    return array($id, $val);
}

function kyc_render_field($f, $values, $errors)
{
    list($id, $val) = kyc_input_attrs($f, $values);
    $key = $f['key'];
    $req = !empty($f['required']);
    $err = isset($errors[$key]) ? $errors[$key] : '';
    $mark = $req ? '<span class="req-mark" aria-hidden="true">*</span>' : '';
    $describe = ($err !== '' ? $id . '-err ' : '') . (!empty($f['hint']) ? $id . '-hint' : '');
    $common = ' id="' . kyc_e($id) . '" name="' . kyc_e($key) . '"' . ($req ? ' required' : '') . ($err !== '' ? ' aria-invalid="true"' : '') . ($describe !== '' ? ' aria-describedby="' . kyc_e(trim($describe)) . '"' : '');
    $out = '<div class="kyc-field' . ($err !== '' ? ' has-error' : '') . ($f['type'] === 'textarea' ? ' is-wide' : '') . '"><label for="' . kyc_e($id) . '">' . kyc_e($f['label']) . ' ' . $mark . '</label>';
    switch ($f['type']) {
        case 'select':
            $out .= '<select' . $common . '><option value="">Choose</option>';
            foreach ($f['options'] as $k => $label) {
                $out .= '<option value="' . kyc_e($k) . '"' . ($val === (string) $k ? ' selected' : '') . '>' . kyc_e($label) . '</option>';
            }
            $out .= '</select>';
            break;
        case 'province':
            $out .= '<select' . $common . ' data-role="province"><option value="">Choose</option>';
            foreach (array_keys(kyc_provinces()) as $p) {
                $out .= '<option value="' . kyc_e($p) . '"' . ($val === $p ? ' selected' : '') . '>' . kyc_e($p) . '</option>';
            }
            $out .= '</select>';
            break;
        case 'district':
            $out .= '<select' . $common . ' data-role="district"><option value="">Choose</option>';
            foreach (kyc_provinces() as $province => $districts) {
                foreach ($districts as $d) {
                    $out .= '<option value="' . kyc_e($d) . '" data-province="' . kyc_e($province) . '"' . ($val === $d ? ' selected' : '') . '>' . kyc_e($d) . '</option>';
                }
            }
            $out .= '</select>';
            break;
        case 'date':
            $cal = isset($values[$key . '_cal']) && $values[$key . '_cal'] === 'AD' ? 'AD' : 'BS';
            $out .= '<div class="kyc-date"><input' . $common . ' type="text" inputmode="numeric" autocomplete="off" placeholder="' . ($cal === 'BS' ? '2056-04-12' : '1999-07-28') . '" pattern="\d{4}-\d{1,2}-\d{1,2}" maxlength="10" value="' . kyc_e($val) . '">'
                . '<select name="' . kyc_e($key) . '_cal" aria-label="Calendar for ' . kyc_e($f['label']) . '"><option value="BS"' . ($cal === 'BS' ? ' selected' : '') . '>BS (Nepali)</option><option value="AD"' . ($cal === 'AD' ? ' selected' : '') . '>AD</option></select></div>';
            break;
        case 'textarea':
            $out .= '<textarea' . $common . ' rows="3" maxlength="' . (int) $f['max'] . '">' . kyc_e($val) . '</textarea>';
            break;
        case 'mobile':
            $out .= '<input' . $common . ' type="tel" inputmode="tel" autocomplete="tel" placeholder="98XXXXXXXX" maxlength="18" value="' . kyc_e($val) . '">';
            break;
        case 'phone':
            $out .= '<input' . $common . ' type="tel" inputmode="tel" placeholder="061-520000" maxlength="20" value="' . kyc_e($val) . '">';
            break;
        case 'email':
            $out .= '<input' . $common . ' type="email" inputmode="email" autocomplete="email" maxlength="120" value="' . kyc_e($val) . '">';
            break;
        case 'ward':
            $out .= '<input' . $common . ' type="text" inputmode="numeric" maxlength="2" placeholder="1 to 35" value="' . kyc_e($val) . '">';
            break;
        case 'idnumber':
        case 'reference':
            $out .= '<input' . $common . ' type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40" value="' . kyc_e($val) . '">';
            break;
        default:
            $out .= '<input' . $common . ' type="text" maxlength="' . (int) (isset($f['max']) ? $f['max'] : 120) . '" value="' . kyc_e($val) . '">';
    }
    if (!empty($f['hint'])) {
        $out .= '<small class="field-hint" id="' . kyc_e($id) . '-hint">' . kyc_e($f['hint']) . '</small>';
    }
    if ($err !== '') {
        $out .= '<small class="field-error" id="' . kyc_e($id) . '-err" role="alert">' . kyc_e($err) . '</small>';
    }
    return $out . '</div>';
}

function kyc_render_doc_slot($spec, $existing, $error, $fileUrl)
{
    $slot = $spec['slot'];
    $has = $existing !== '';
    $isPdf = $has && strtolower(pathinfo($existing, PATHINFO_EXTENSION)) === 'pdf';
    $out = '<div class="kyc-doc' . ($error !== '' ? ' has-error' : '') . '" data-slot="' . kyc_e($slot) . '"><div class="kyc-doc-head"><b>' . kyc_e($spec['label']) . '</b>' . (!empty($spec['required']) ? ' <span class="req-mark" aria-hidden="true">*</span>' : ' <em>optional</em>') . '</div>'
        . '<p class="kyc-doc-hint">' . kyc_e($spec['hint']) . '</p>'
        . '<div class="kyc-doc-body"><div class="kyc-thumb" data-thumb>';
    if ($has && !$isPdf) {
        $out .= '<img src="' . kyc_e($fileUrl . '?slot=' . $slot) . '" alt="' . kyc_e($spec['label']) . ' already uploaded" loading="lazy">';
    } elseif ($has) {
        $out .= '<span class="kyc-thumb-pdf">PDF</span>';
    } else {
        $out .= '<span class="kyc-thumb-empty" aria-hidden="true"><i data-lucide="image-plus"></i></span>';
    }
    $out .= '</div><div class="kyc-doc-actions">'
        . '<label class="kyc-btn is-camera" tabindex="0"><i data-lucide="camera"></i> Take photo<input type="file" name="cam_' . kyc_e($slot) . '" accept="image/*" capture="' . kyc_e($spec['camera']) . '" class="kyc-file"></label>'
        . '<label class="kyc-btn" tabindex="0"><i data-lucide="upload"></i> Choose file<input type="file" name="doc_' . kyc_e($slot) . '" accept="image/jpeg,image/png,image/webp,application/pdf" class="kyc-file"></label>'
        . '<p class="kyc-doc-status" data-status aria-live="polite">' . ($has ? 'Uploaded. Add a new one to replace it.' : 'Nothing added yet.') . '</p></div></div>';
    if ($error !== '') {
        $out .= '<small class="field-error" role="alert">' . kyc_e($error) . '</small>';
    }
    return $out . '</div>';
}

function kyc_render_form($kind, $values, $errors, $row, $fileUrl, $csrf)
{
    $idKind = $kind === 'organization' ? (isset($values['contact_id_kind']) ? $values['contact_id_kind'] : 'citizenship') : (isset($values['id_kind']) ? $values['id_kind'] : 'citizenship');
    $sections = kyc_spec($kind);
    $docs = kyc_doc_spec($kind, $idKind);
    $columns = array('identity' => 'doc_identity', 'identity_back' => 'doc_identity_back', 'registration' => 'doc_registration', 'tax' => 'doc_tax', 'authority' => 'doc_authority', 'clearance' => 'doc_clearance', 'photo' => 'doc_photo');
    $out = '<form method="POST" enctype="multipart/form-data" class="kyc-form" id="kyc-form" data-kind="' . kyc_e($kind) . '" data-client="' . (int) $row['client_id'] . '" novalidate>'
        . '<input type="hidden" name="csrf_token" value="' . kyc_e($csrf) . '"><input type="hidden" name="account_kind" value="' . kyc_e($kind) . '">';
    $step = 0;
    $total = count($sections) + 1;
    foreach ($sections as $section) {
        $step++;
        $same = !empty($section['same']);
        $out .= '<fieldset class="kyc-section" id="s-' . kyc_e($section['id']) . '" data-step="' . $step . '"><legend><span class="kyc-step">' . $step . '</span> ' . kyc_e($section['title']) . '</legend>';
        if (!empty($section['help'])) {
            $out .= '<p class="kyc-help">' . kyc_e($section['help']) . '</p>';
        }
        if ($same) {
            $checked = !empty($values['temp_same']) ? ' checked' : '';
            $out .= '<label class="kyc-same"><input type="checkbox" name="temp_same" value="1" id="temp-same"' . $checked . '> <span>Same as ' . ($kind === 'organization' ? 'the registered address' : 'my permanent address') . '</span></label>';
        }
        $out .= '<div class="kyc-grid' . ($same ? ' kyc-temp' : '') . '">';
        foreach ($section['fields'] as $f) {
            $out .= kyc_render_field($f, $values, $errors);
        }
        $out .= '</div></fieldset>';
    }
    $step++;
    $out .= '<fieldset class="kyc-section" id="s-documents" data-step="' . $step . '"><legend><span class="kyc-step">' . $step . '</span> Documents and photos</legend>'
        . '<p class="kyc-help">On a phone, press <b>Take photo</b> to use the camera, or <b>Choose file</b> to pick a photo or PDF you already have. Photos are made smaller automatically. Each file can be up to 5 MB.</p><div class="kyc-docs">';
    foreach ($docs as $spec) {
        $existing = isset($row[$columns[$spec['slot']]]) ? (string) $row[$columns[$spec['slot']]] : '';
        $err = isset($errors['doc_' . $spec['slot']]) ? $errors['doc_' . $spec['slot']] : '';
        $out .= kyc_render_doc_slot($spec, $existing, $err, $fileUrl);
    }
    $out .= '</div></fieldset>'
        . '<div class="kyc-submit"><p class="kyc-privacy"><i data-lucide="lock"></i> Your documents are private. Only you and our verification team can open them.</p>'
        . '<button type="submit" class="kyc-send">Send for approval</button></div></form>';
    return $out;
}

function kyc_render_summary($kind, $values)
{
    $out = '<div class="kyc-summary">';
    foreach (kyc_summary($kind, $values) as $section) {
        $out .= '<section class="kyc-sum-card"><h3>' . kyc_e($section['title']) . '</h3><dl>';
        foreach ($section['rows'] as $r) {
            $out .= '<div><dt>' . kyc_e($r['label']) . '</dt><dd>' . kyc_e($r['value']) . '</dd></div>';
        }
        $out .= '</dl></section>';
    }
    return $out . '</div>';
}

/** Document gallery: each file opens in a viewer. $fileUrl is the page that serves one file. */
function kyc_render_gallery($kind, $row, $fileUrl, $clientParam = '')
{
    $legacy = $row['details'] === '' || $row['details'] === null;
    $columns = array('identity' => 'doc_identity', 'identity_back' => 'doc_identity_back', 'registration' => 'doc_registration', 'tax' => 'doc_tax', 'authority' => 'doc_authority', 'clearance' => 'doc_clearance', 'photo' => 'doc_photo');
    $idKind = $kind === 'organization' ? $row['contact_id_kind'] : $row['id_kind'];
    $out = '<div class="kyc-gallery">';
    foreach (kyc_doc_spec($kind, $idKind) as $spec) {
        $path = isset($row[$columns[$spec['slot']]]) ? (string) $row[$columns[$spec['slot']]] : '';
        $isPdf = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
        $url = $fileUrl . '?' . $clientParam . 'slot=' . $spec['slot'];
        $out .= '<figure class="kyc-shot' . ($path === '' && !$legacy ? ' is-missing' : '') . '"><figcaption>' . kyc_e($spec['label']) . '</figcaption>';
        if ($path === '') {
            $out .= '<div class="kyc-shot-body is-empty">' . (!empty($spec['required']) && !$legacy ? 'Missing' : 'Not provided') . '</div>';
        } else {
            $out .= '<a class="kyc-shot-body" href="' . kyc_e($url . '&inline=1') . '" data-kyc-doc data-title="' . kyc_e($spec['label']) . '" data-type="' . ($isPdf ? 'pdf' : 'image') . '" data-src="' . kyc_e($url . '&inline=1') . '">'
                . ($isPdf ? '<span class="kyc-thumb-pdf">PDF</span>' : '<img src="' . kyc_e($url) . '" alt="' . kyc_e($spec['label']) . '" loading="lazy">') . '<span class="kyc-open">View</span></a>';
        }
        $out .= '</figure>';
    }
    return $out . '</div>';
}

/** The short list shown for identities approved on the older, shorter form. */
function kyc_render_legacy($kind, $row)
{
    $isOrg = $kind === 'organization';
    $rows = array(
        array('Name', $isOrg ? $row['org_name'] : $row['full_name']),
        array('Document', billing_kyc_id_label($isOrg ? $row['contact_id_kind'] : $row['id_kind']) . ' ' . ($isOrg ? $row['contact_id_number'] : $row['id_number']))
    );
    if ($isOrg) {
        $rows[] = array('Registration number', $row['registration_number']);
        $rows[] = array('PAN number', $row['tax_number']);
        $rows[] = array('Authorized person', $row['contact_name']);
    }
    $rows[] = array('Address', $row['address']);
    $rows[] = array('Use', $row['purpose']);
    $out = '<div class="kyc-summary"><section class="kyc-sum-card"><h3>Details on file</h3><dl>';
    foreach ($rows as $r) {
        if (trim((string) $r[1]) !== '') {
            $out .= '<div><dt>' . kyc_e($r[0]) . '</dt><dd>' . kyc_e($r[1]) . '</dd></div>';
        }
    }
    return $out . '</dl></section></div>';
}
