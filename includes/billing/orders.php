<?php
/**
 * Billing: Purchases, refunds, renewals and admin order actions.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

function billing_prepare_order($conn, $plan, $post)
{
    $needs = (string) $plan['needs_detail'];
    $brief = array();
    $detail = '';
    $price = billing_selling_price($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0);
    if ($needs !== 'sms' && $needs !== 'voice' && billing_active_offer($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0) > 0) {
        $brief['Regular price'] = billing_money_label($plan['price']);
        $brief['Offer price'] = billing_money_label($price);
    }
    $quantity = (int) $plan['unit_quantity'];
    $unitKind = (string) $plan['unit_kind'];
    $status = ($needs === 'website' || $needs === 'training') ? 'booked' : 'active';

    if ($needs === 'sms' || $needs === 'voice') {
        $guardError = billing_form_guard_check('order-' . $plan['code'], $post);
        if ($guardError !== '') {
            return array('ok' => false, 'error' => $guardError);
        }
        if (billing_posted($post, 'legal_accept') !== '1') {
            return array('ok' => false, 'error' => 'Accept the declaration before this order can continue.');
        }
        $quantity = (int) billing_posted($post, 'quantity');
        $audiences = billing_audiences();
        $purposes = billing_purposes();
        $audience = billing_posted($post, 'audience');
        $purpose = billing_posted($post, 'purpose');
        if (!isset($audiences[$audience]) || !isset($purposes[$purpose])) {
            return array('ok' => false, 'error' => 'Choose who the message is for and why it is being sent.');
        }
        $slabs = billing_slabs_for($conn, $plan['service_slug']);
        $slab = billing_slab_for_quantity($slabs, $quantity);
        if (!$slab) {
            $floor = $slabs ? (int) $slabs[0]['min_qty'] : 1;
            $ceiling = $slabs ? (int) $slabs[count($slabs) - 1]['max_qty'] : 1;
            return array('ok' => false, 'error' => 'Enter a quantity between ' . number_format($floor) . ' and ' . number_format($ceiling) . '.');
        }
        $message = billing_plain_block(billing_posted($post, 'message'), $needs === 'sms' ? 480 : 1500);
        if (strlen($message) < 5) {
            return array('ok' => false, 'error' => $needs === 'sms' ? 'Write the SMS people should receive.' : 'Write the voice script people should hear.');
        }
        $schedule = billing_posted($post, 'schedule_date');
        if ($schedule !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule)) {
            return array('ok' => false, 'error' => 'Choose a valid send date.');
        }
        if ($schedule !== '' && $schedule < date('Y-m-d')) {
            return array('ok' => false, 'error' => 'The send date cannot be in the past.');
        }
        $numbers = billing_plain_block(billing_posted($post, 'numbers'), 20000);
        $parsedNumbers = array();
        if ($numbers !== '') {
            $parsed = billing_parse_numbers($numbers);
            if (empty($parsed['ok'])) {
                return array('ok' => false, 'error' => $parsed['error']);
            }
            if (count($parsed['numbers']) !== $quantity) {
                return array('ok' => false, 'error' => 'The list has ' . number_format(count($parsed['numbers'])) . ' numbers and this order is for ' . number_format($quantity) . '. Leave the list empty to add numbers when you send, or make the counts match.');
            }
            $parsedNumbers = $parsed['numbers'];
        }
        $unitPrice = billing_selling_price($slab['unit_price'], isset($slab['offer_price']) ? $slab['offer_price'] : 0);
        $price = round($unitPrice * $quantity, 2);
        $brief['Audience'] = $audiences[$audience];
        $brief['Purpose'] = $purposes[$purpose];
        $brief['Quantity'] = number_format($quantity);
        $brief['Rate'] = billing_unit_label($unitPrice) . ' each';
        if (billing_active_offer($slab['unit_price'], isset($slab['offer_price']) ? $slab['offer_price'] : 0) > 0) {
            $brief['Regular rate'] = billing_unit_label($slab['unit_price']) . ' each';
        }
        if ($needs === 'sms') {
            $sender = billing_plain_line(billing_posted($post, 'sender_id'), 11);
            if (!preg_match('/^[A-Za-z0-9]{3,11}$/', $sender)) {
                return array('ok' => false, 'error' => 'Enter a sender name of 3 to 11 letters or numbers.');
            }
            $brief['Sender name'] = $sender;
            $unitKind = 'sms';
        } else {
            $language = billing_posted($post, 'language');
            if (!in_array($language, array('Nepali', 'English'), true)) {
                return array('ok' => false, 'error' => 'Choose Nepali or English.');
            }
            $brief['Language'] = $language;
            $unitKind = 'voice_calls';
        }
        $brief[$needs === 'sms' ? 'Message' : 'Script'] = $message;
        if ($schedule !== '') {
            $brief['Send date'] = $schedule;
        }
        if ($parsedNumbers) {
            $brief['Number list'] = implode("\n", $parsedNumbers);
        }
        $brief['Declaration'] = billing_use_declaration();
        $brief['Declaration accepted'] = date('Y-m-d H:i');
        $detail = $brief['Audience'] . ' · ' . $brief['Purpose'] . ' · ' . number_format($quantity);
        return billing_order_ready($detail, $brief, $price, $quantity, $unitKind, $status);
    }

    if ($needs === 'domain' || $needs === 'hosting' || $needs === 'email') {
        $domain = strtolower(billing_plain_line(billing_posted($post, 'domain'), 253));
        $organization = billing_plain_line(billing_posted($post, 'organization'), 120);
        if (!billing_valid_domain($domain)) {
            return array('ok' => false, 'error' => 'Enter a valid domain name, such as yourcoop.com.np.');
        }
        if (strlen($organization) < 2) {
            return array('ok' => false, 'error' => 'Enter the organization or person this order is for.');
        }
        $brief['Domain'] = $domain;
        $brief['Organization'] = $organization;
        $detail = $domain;
        if ($needs === 'hosting') {
            $uses = array('New website', 'Existing website', 'Website and email');
            $use = billing_posted($post, 'server_use');
            if (!in_array($use, $uses, true)) {
                return array('ok' => false, 'error' => 'Choose what this server will host.');
            }
            $brief['Will host'] = $use;
        }
        if ($needs === 'email') {
            $lines = preg_split('/\n/', billing_plain_block(billing_posted($post, 'mailboxes'), 800));
            $names = array();
            foreach ($lines as $line) {
                $line = strtolower(trim($line));
                if ($line === '') {
                    continue;
                }
                if (!preg_match('/^[a-z0-9._-]{1,40}$/', $line)) {
                    return array('ok' => false, 'error' => 'Mailbox names can use letters, numbers, dots, and hyphens only. Write one name per line, without the @.');
                }
                $names[] = $line;
            }
            $expected = (int) $plan['unit_quantity'];
            if (count($names) !== $expected) {
                return array('ok' => false, 'error' => 'This package includes ' . $expected . ' mailbox' . ($expected === 1 ? '' : 'es') . '. Enter exactly that many names.');
            }
            $brief['Mailboxes'] = implode(', ', $names);
            $detail = $domain . ' · ' . $expected . ' mailbox' . ($expected === 1 ? '' : 'es');
        }
        return billing_order_ready($detail, $brief, $price, $quantity > 0 ? $quantity : 1, $unitKind, $status);
    }

    if ($needs === 'website') {
        $organization = billing_plain_line(billing_posted($post, 'organization'), 120);
        $goal = billing_plain_block(billing_posted($post, 'goal'), 1500);
        $domain = strtolower(billing_plain_line(billing_posted($post, 'domain'), 253));
        $deadline = billing_posted($post, 'deadline');
        $address = billing_plain_line(billing_posted($post, 'address'), 180);
        $publicPhone = billing_plain_line(billing_posted($post, 'public_phone'), 30);
        $publicEmail = billing_plain_line(billing_posted($post, 'public_email'), 120);
        $about = billing_plain_block(billing_posted($post, 'about'), 1200);
        if (strlen($organization) < 2 || strlen($goal) < 15 || strlen($address) < 8 || strlen($about) < 20) {
            return array('ok' => false, 'error' => 'Add the organization, what the site must do, a short about paragraph, and the business address.');
        }
        if (!preg_match('/^[0-9+()\\-\\s]{7,30}$/', $publicPhone)) {
            return array('ok' => false, 'error' => 'Enter the phone number that should appear on the website.');
        }
        if (!filter_var($publicEmail, FILTER_VALIDATE_EMAIL)) {
            return array('ok' => false, 'error' => 'Enter the email address that should appear on the website.');
        }
        if ($domain !== '' && !billing_valid_domain($domain)) {
            return array('ok' => false, 'error' => 'The preferred domain is not a valid domain name. Leave it blank if you do not have one yet.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline) || $deadline < date('Y-m-d')) {
            return array('ok' => false, 'error' => 'Choose a deadline that is today or later.');
        }
        $brief['Website'] = $plan['name'];
        $brief['Organization'] = $organization;
        $brief['What it must do'] = $goal;
        $brief['About text'] = $about;
        $brief['Public phone'] = $publicPhone;
        $brief['Public email'] = $publicEmail;
        $brief['Preferred domain'] = $domain !== '' ? $domain : 'Not chosen yet';
        $brief['Deadline'] = $deadline;
        $brief['Address'] = $address;
        return billing_order_ready($organization, $brief, $price, 1, '', $status);
    }

    if ($needs === 'training') {
        $organization = billing_plain_line(billing_posted($post, 'organization'), 120);
        $headcount = (int) billing_posted($post, 'headcount');
        $address = billing_plain_line(billing_posted($post, 'address'), 180);
        $district = billing_plain_line(billing_posted($post, 'district'), 80);
        $preferred = billing_posted($post, 'preferred_date');
        $note = billing_plain_block(billing_posted($post, 'note'), 800);
        $topicCatalog = billing_training_topics();
        $chosenTopics = array();
        $submittedTopics = isset($post['topics']) && is_array($post['topics']) ? $post['topics'] : array();
        foreach ($submittedTopics as $topic) {
            if (is_string($topic) && isset($topicCatalog[$topic])) {
                $chosenTopics[$topic] = $topicCatalog[$topic];
            }
        }
        $maxPeople = max(1, (int) $plan['unit_quantity']);
        if (strlen($organization) < 2 || strlen($address) < 8 || strlen($district) < 2) {
            return array('ok' => false, 'error' => 'Enter the organization, the venue address, and the district.');
        }
        if (!$chosenTopics) {
            return array('ok' => false, 'error' => 'Choose at least one training topic.');
        }
        if ($headcount < 1 || $headcount > $maxPeople) {
            return array('ok' => false, 'error' => 'Headcount must be between 1 and ' . $maxPeople . ' for this session.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $preferred) || $preferred < date('Y-m-d')) {
            return array('ok' => false, 'error' => 'Choose a preferred date that is today or later.');
        }
        $brief['Session'] = $plan['name'];
        $brief['Organization'] = $organization;
        $brief['People'] = (string) $headcount;
        $brief['Venue address'] = $address;
        $brief['District'] = $district;
        $brief['Preferred date'] = $preferred;
        $brief['Topics'] = implode('; ', array_values($chosenTopics));
        if ($note !== '') {
            $brief['Note'] = $note;
        }
        return billing_order_ready($organization . ' · ' . $district, $brief, $price, $headcount, '', $status);
    }

    return billing_order_ready('', array(), $price, $quantity > 0 ? $quantity : 1, $unitKind, 'active');
}

function billing_order_ready($detail, $brief, $price, $quantity, $unitKind, $status)
{
    if ($price <= 0) {
        return array('ok' => false, 'error' => 'This order does not have a price yet.');
    }
    $bill = billing_vat_bill($price);
    $brief['Service amount'] = billing_money_label($bill['net']);
    $brief['VAT 13%'] = billing_money_label($bill['vat']);
    $brief['Total'] = billing_money_label($bill['total']);
    return array(
        'ok' => true,
        'error' => '',
        'detail' => $detail,
        'brief' => $brief,
        'net' => $bill['net'],
        'vat' => $bill['vat'],
        'price' => $bill['total'],
        'quantity' => (int) $quantity,
        'unit_kind' => $unitKind,
        'status' => $status
    );
}

function billing_client_taken($conn, $email, $phone, $company, $exceptId = 0)
{
    $exceptId = (int) $exceptId;
    $email = strtolower(trim((string) $email));
    if ($email !== '') {
        $stmt = $conn->prepare('SELECT id FROM client_users WHERE LOWER(email) = ? AND id != ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('si', $email, $exceptId);
            $stmt->execute();
            $row = db_fetch_assoc($stmt);
            $stmt->close();
            if ($row) {
                return 'An account with this email already exists.';
            }
        }
    }
    $phone = function_exists('auth_mobile_number') ? auth_mobile_number($phone) : '';
    if ($phone !== '') {
        $withCountry = '977' . $phone;
        $withZero = '0' . $phone;
        $stmt = $conn->prepare('SELECT id FROM client_users WHERE id != ? AND phone IN (?, ?, ?) LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('isss', $exceptId, $phone, $withCountry, $withZero);
            $stmt->execute();
            $row = db_fetch_assoc($stmt);
            $stmt->close();
            if ($row) {
                return 'An account with this mobile number already exists.';
            }
        }
    }
    $company = billing_plain_line($company, 120);
    if ($company !== '') {
        $key = strtolower($company);
        $stmt = $conn->prepare('SELECT id FROM client_users WHERE id != ? AND LOWER(company) = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('is', $exceptId, $key);
            $stmt->execute();
            $row = db_fetch_assoc($stmt);
            $stmt->close();
            if ($row) {
                return 'An account with this company name already exists.';
            }
        }
    }
    return '';
}

function billing_admin_create_client($conn, $name, $email, $phone, $company, $password)
{
    $name = billing_plain_line($name, 80);
    $email = strtolower(trim((string) $email));
    $phoneInput = trim((string) $phone);
    $phone = $phoneInput === '' ? '' : (function_exists('auth_mobile_number') ? auth_mobile_number($phoneInput) : '');
    $company = billing_plain_line($company, 120);
    $password = (string) $password;
    if ($name === '' || $email === '' || $password === '') {
        return array('ok' => false, 'error' => 'Name, email, and a password are required.', 'id' => 0);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return array('ok' => false, 'error' => 'Enter a valid email.', 'id' => 0);
    }
    if ($phone === '') {
        return array('ok' => false, 'error' => 'Enter a 10-digit mobile number.', 'id' => 0);
    }
    if (strlen($password) < 8) {
        return array('ok' => false, 'error' => 'Password must be at least 8 characters.', 'id' => 0);
    }
    $taken = billing_client_taken($conn, $email, $phone, $company, 0);
    if ($taken !== '') {
        return array('ok' => false, 'error' => $taken, 'id' => 0);
    }
    $colors = array('#06b6d4', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444');
    $avatar = $colors[array_rand($colors)];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO client_users (name, email, password, phone, company, avatar_color) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $name, $email, $hash, $phone, $company, $avatar);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();
    if ($id < 1) {
        return array('ok' => false, 'error' => 'The account could not be created.', 'id' => 0);
    }
    billing_mail_client_event($conn, $id, 'account');
    return array('ok' => true, 'error' => '', 'id' => $id);
}

function billing_client_save_profile($conn, $clientId, $name, $company, $address)
{
    $clientId = (int) $clientId;
    $name = billing_plain_line($name, 80);
    $company = billing_plain_line($company, 120);
    $address = billing_plain_block($address, 300);
    if ($clientId < 1 || $name === '') {
        return 'Name is required.';
    }
    $taken = billing_client_taken($conn, '', '', $company, $clientId);
    if ($taken !== '') {
        return $taken;
    }
    $stmt = $conn->prepare('UPDATE client_users SET name = ?, company = ?, address = ? WHERE id = ?');
    if (!$stmt) {
        return 'Failed to update profile.';
    }
    $stmt->bind_param('sssi', $name, $company, $address, $clientId);
    $stmt->execute();
    $stmt->close();
    return '';
}

function billing_admin_set_contact($conn, $clientId, $email, $phone)
{
    $clientId = (int) $clientId;
    $email = strtolower(trim((string) $email));
    $phoneInput = trim((string) $phone);
    $phone = function_exists('auth_mobile_number') ? auth_mobile_number($phoneInput) : '';
    if ($clientId < 1) {
        return 'Choose a client.';
    }
    if (!billing_mail_ok($email)) {
        return 'Enter a valid email.';
    }
    if ($phone === '') {
        return 'Enter a 10-digit mobile number.';
    }
    $stmt = $conn->prepare('SELECT email, phone FROM client_users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return 'That client was not found.';
    }
    $taken = billing_client_taken($conn, $email, $phone, '', $clientId);
    if ($taken !== '') {
        return $taken;
    }
    $oldEmail = strtolower(trim((string) $row['email']));
    $oldPhone = (string) $row['phone'];
    if ($oldEmail === $email && $oldPhone === $phone) {
        return 'That email and mobile are already saved.';
    }
    $update = $conn->prepare('UPDATE client_users SET email = ?, phone = ? WHERE id = ?');
    $update->bind_param('ssi', $email, $phone, $clientId);
    $update->execute();
    $update->close();
    if ($oldEmail !== $email) {
        if (function_exists('password_reset_clear')) {
            password_reset_clear($conn, $clientId);
        }
        if (billing_mail_ok($oldEmail)) {
            billing_mail_named_event($conn, $oldEmail, '', 'contact-email', array('email' => $email));
        }
        billing_mail_client_event($conn, $clientId, 'contact-email', array('email' => $email));
    }
    if ($oldPhone !== $phone) {
        billing_mail_client_event($conn, $clientId, 'contact-phone', array('phone' => $phone));
    }
    return '';
}

function billing_admin_add_service($conn, $clientId, $planCode, $quantity, $detail)
{
    $clientId = (int) $clientId;
    $plan = billing_find_plan($conn, (string) $planCode);
    if ($clientId < 1 || !$plan) {
        return 'Choose a client and a service.';
    }
    $check = $conn->prepare('SELECT id FROM client_users WHERE id = ?');
    $check->bind_param('i', $clientId);
    $check->execute();
    $client = db_fetch_assoc($check);
    $check->close();
    if (!$client) {
        return 'That client was not found.';
    }
    $needs = (string) $plan['needs_detail'];
    $unitKind = (string) $plan['unit_kind'];
    $quantity = (int) $quantity;
    $unitQuantity = (int) $plan['unit_quantity'];
    if ($needs === 'sms' || $needs === 'voice') {
        if ($quantity < 1 || $quantity > 500000) {
            return 'Enter how many SMS or voice calls to add, up to 500,000.';
        }
        $unitKind = $needs === 'sms' ? 'sms' : 'voice_calls';
        $unitQuantity = $quantity;
    }
    $services = billing_service_definitions();
    $service = isset($services[$plan['service_slug']]) ? $services[$plan['service_slug']] : array('title' => 'Service');
    $today = date('Y-m-d');
    $cycle = (string) $plan['billing_cycle'];
    $autoRenew = $cycle === 'one_time' ? 0 : (int) $plan['auto_renew_default'];
    $nextRenewal = $autoRenew ? billing_add_cycle($today, $cycle) : '';
    $status = ($needs === 'website' || $needs === 'training') ? 'booked' : 'active';
    $name = $service['title'] . ' — ' . $plan['name'];
    $description = (string) $plan['summary'];
    $detail = billing_plain_line($detail, 180);
    $price = ($needs === 'sms' || $needs === 'voice') ? '0.00' : billing_money(billing_selling_price($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0));
    $brief = array('Added by' => 'the team', 'Payment' => 'Taken outside the wallet');
    if ($detail !== '') {
        $brief['Detail'] = $detail;
    }
    $briefJson = json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($briefJson === false) {
        $briefJson = '';
    }
    $planCode = (string) $plan['code'];
    $stmt = $conn->prepare('INSERT INTO client_services (client_id, service_name, description, status, start_date, end_date, price, plan_code, billing_cycle, auto_renew, next_renewal, detail_label, order_brief, unit_kind, unit_quantity) VALUES (?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?)');
    $stmt->bind_param('issssssssissssi', $clientId, $name, $description, $status, $today, $nextRenewal, $price, $planCode, $cycle, $autoRenew, $nextRenewal, $detail, $briefJson, $unitKind, $unitQuantity);
    $ok = $stmt->execute();
    $serviceId = (int) $conn->insert_id;
    $stmt->close();
    if (!$ok || $serviceId < 1) {
        return 'The service could not be added.';
    }
    if ($unitKind === 'sms' && $unitQuantity > 0) {
        billing_add_units($conn, $clientId, 'sms', $unitQuantity);
        if (function_exists('sms_remember_credit')) {
            $noteId = (int) sms_remember_credit($conn, $clientId, $unitQuantity, 'Added by the team');
            if ($noteId > 0) {
                $brief['Credit note'] = (string) $noteId;
                $linked = json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (is_string($linked) && $linked !== '') {
                    $link = $conn->prepare('UPDATE client_services SET order_brief = ? WHERE id = ? AND client_id = ?');
                    if ($link) {
                        $link->bind_param('sii', $linked, $serviceId, $clientId);
                        $link->execute();
                        $link->close();
                    }
                }
            }
        }
    } elseif ($unitKind === 'voice_calls' && $unitQuantity > 0) {
        billing_add_units($conn, $clientId, 'voice_calls', $unitQuantity);
    }
    billing_mail_client_event($conn, $clientId, 'office-service', array(
        'service' => $name,
        'status' => $status === 'booked' ? 'Booked, waiting for the team' : 'Active'
    ));
    return '';
}

function billing_purchase($conn, $clientId, $plan, $post)
{
    if (!is_array($plan) || empty($plan['code'])) {
        return array('ok' => false, 'error' => 'That service is not available.');
    }
    $order = billing_prepare_order($conn, $plan, is_array($post) ? $post : array());
    if (empty($order['ok'])) {
        return $order;
    }

    $clientId = (int) $clientId;
    $price = billing_money($order['price']);
    billing_tx($conn, 'begin');
    if (!billing_wallet_debit($conn, $clientId, $price)) {
        billing_tx($conn, 'rollback');
        return array('ok' => false, 'error' => 'Your wallet does not have enough for this order. Add funds, then confirm again. Nothing else is required by phone.');
    }

    $services = billing_service_definitions();
    $service = isset($services[$plan['service_slug']]) ? $services[$plan['service_slug']] : array('title' => 'Service');
    $today = date('Y-m-d');
    $cycle = $plan['billing_cycle'];
    $autoRenew = $cycle === 'one_time' ? 0 : (int) $plan['auto_renew_default'];
    $nextRenewal = $autoRenew ? billing_add_cycle($today, $cycle) : null;
    $endDate = $nextRenewal;
    $status = $order['status'];
    $name = $service['title'] . ' — ' . $plan['name'];
    $description = $plan['summary'];
    $detail = $order['detail'];
    $briefJson = json_encode($order['brief'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($briefJson === false) {
        $briefJson = '';
    }
    $planCode = $plan['code'];
    $unitKind = $order['unit_kind'];
    $unitQuantity = (int) $order['quantity'];
    if ($endDate === null) {
        $endDate = '';
    }
    if ($nextRenewal === null) {
        $nextRenewal = '';
    }
    $stmt = $conn->prepare('INSERT INTO client_services (client_id, service_name, description, status, start_date, end_date, price, plan_code, billing_cycle, auto_renew, next_renewal, detail_label, order_brief, unit_kind, unit_quantity) VALUES (?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?)');
    $stmt->bind_param('issssssssissssi', $clientId, $name, $description, $status, $today, $endDate, $price, $planCode, $cycle, $autoRenew, $nextRenewal, $detail, $briefJson, $unitKind, $unitQuantity);
    $ok = $stmt->execute();
    $serviceId = (int) $conn->insert_id;
    $stmt->close();

    if (!$ok || $serviceId <= 0) {
        billing_tx($conn, 'rollback');
        return array('ok' => false, 'error' => 'The purchase could not be saved. Your wallet was not charged.');
    }

    try {
        billing_record_entry($conn, $clientId, $price, 'debit', 'purchase', 'completed', 'wallet', $name, $serviceId);
        billing_add_units($conn, $clientId, $unitKind, $unitQuantity);
        if ($unitKind === 'sms' && $unitQuantity > 0 && function_exists('sms_remember_credit')) {
            sms_remember_credit($conn, $clientId, $unitQuantity, 'Bought from the wallet');
        }
    } catch (Throwable $exception) {
        billing_tx($conn, 'rollback');
        return array('ok' => false, 'error' => 'The purchase could not be finished. Your wallet was not charged.');
    }
    billing_tx($conn, 'commit');
    billing_notify($conn, 'New order: ' . $name, array(
        'A client bought or booked a service.',
        'Service: ' . $name,
        'Status: ' . ($status === 'booked' ? 'Booked, waiting for the team' : 'Paid'),
        'Amount: NPR ' . $price,
        'Detail: ' . billing_notify_clip($detail, 200),
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Open the admin panel.'
    ));
    billing_mail_client_event($conn, $clientId, $status === 'booked' ? 'booked' : 'paid', array(
        'service' => $name,
        'amount' => $price
    ));
    if ($plan['needs_detail'] === 'sms' || $plan['needs_detail'] === 'voice') {
        billing_form_guard_clear('order-' . $plan['code']);
    }
    return array('ok' => true, 'service_id' => $serviceId, 'status' => $status, 'price' => (float) $price);
}

function billing_refund_domain($conn, $serviceId)
{
    $serviceId = (int) $serviceId;
    if ($serviceId < 1) {
        return 'Choose a domain order.';
    }
    $stmt = $conn->prepare('SELECT id, client_id, price, status, plan_code, detail_label FROM client_services WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return 'That domain order could not be read.';
    }
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || ($row['plan_code'] !== 'domain-com' && $row['plan_code'] !== 'domain-np')) {
        return 'Only a domain order can be returned to the wallet.';
    }
    if ($row['status'] !== 'active') {
        return 'That domain order is no longer active.';
    }
    $price = billing_money($row['price']);
    if ((float) $price <= 0) {
        return 'That order has no amount to return.';
    }
    $clientId = (int) $row['client_id'];
    billing_wallet_credit($conn, $clientId, $price);
    $note = 'Domain not available' . ($row['detail_label'] !== '' ? ': ' . $row['detail_label'] : '');
    billing_record_entry($conn, $clientId, $price, 'credit', 'refund', 'completed', 'wallet', $note, $serviceId);
    $status = 'refunded';
    $update = $conn->prepare("UPDATE client_services SET status = ?, auto_renew = 0, next_renewal = NULL WHERE id = ? AND status = 'active'");
    $update->bind_param('si', $status, $serviceId);
    $update->execute();
    $saved = billing_affected($conn) === 1;
    $update->close();
    if (!$saved) {
        return 'The wallet was credited, but the order status could not be changed. Check this order before trying again.';
    }
    billing_mail_client_event($conn, $clientId, 'refund', array(
        'amount' => $price,
        'note' => $note
    ));
    return '';
}

function billing_admin_take_service($conn, $clientId, $serviceId)
{
    $clientId = (int) $clientId;
    $serviceId = (int) $serviceId;
    if ($clientId < 1 || $serviceId < 1) {
        return array('error' => 'That service was not found.', 'message' => '');
    }
    $stmt = $conn->prepare('SELECT id, status, order_brief, unit_kind, unit_quantity FROM client_services WHERE id = ? AND client_id = ?');
    if (!$stmt) {
        return array('error' => 'That service could not be read.', 'message' => '');
    }
    $stmt->bind_param('ii', $serviceId, $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return array('error' => 'That service was not found.', 'message' => '');
    }
    if ($row['status'] !== 'active' && $row['status'] !== 'booked') {
        return array('error' => 'That service is already closed.', 'message' => '');
    }
    $brief = json_decode((string) $row['order_brief'], true);
    if (!is_array($brief) || !isset($brief['Added by']) || $brief['Added by'] !== 'the team') {
        return array('error' => 'This service was paid from the wallet. It is not an office add, so it stays.', 'message' => '');
    }
    $extra = '';
    $creditNote = isset($brief['Credit note']) ? (int) $brief['Credit note'] : 0;
    $kind = (string) $row['unit_kind'];
    $quantity = (int) $row['unit_quantity'];
    if ($creditNote > 0 && function_exists('sms_admin_reverse')) {
        $reversed = sms_admin_reverse($conn, $clientId, $creditNote);
        if ($reversed['error'] !== '' && strpos($reversed['error'], 'already sent') === false && strpos($reversed['error'], 'already taken') === false) {
            return array('error' => $reversed['error'], 'message' => '');
        }
        $extra = $reversed['error'] !== '' ? $reversed['error'] : $reversed['message'];
    } elseif (($kind === 'sms' || $kind === 'voice_calls') && $quantity > 0) {
        $left = (int) billing_unit_balances($conn, $clientId)[$kind];
        $take = $left < $quantity ? $left : $quantity;
        if ($take > 0 && !billing_take_units($conn, $clientId, $kind, $take)) {
            return array('error' => 'The credits could not be taken back. The balance changed while this was saving.', 'message' => '');
        }
        if ($take < $quantity) {
            $extra = number_format($quantity - $take) . ' were already used, so ' . number_format($take) . ' were taken back.';
        }
    }
    $closed = 'expired';
    $update = $conn->prepare('UPDATE client_services SET status = ?, auto_renew = 0, next_renewal = NULL WHERE id = ? AND client_id = ? AND status IN (\'active\', \'booked\')');
    if (!$update) {
        return array('error' => 'The service could not be closed.', 'message' => '');
    }
    $update->bind_param('sii', $closed, $serviceId, $clientId);
    $update->execute();
    $closedOk = billing_affected($conn) === 1;
    $update->close();
    if (!$closedOk) {
        return array('error' => 'That service is already closed.', 'message' => '');
    }
    $message = 'Service taken back. It will not renew.';
    if ($extra !== '') {
        $message .= ' ' . $extra;
    }
    return array('error' => '', 'message' => $message);
}

function billing_set_auto_renew($conn, $clientId, $serviceId, $enabled)
{
    $clientId = (int) $clientId;
    $serviceId = (int) $serviceId;
    $enabled = $enabled ? 1 : 0;
    $stmt = $conn->prepare("UPDATE client_services SET auto_renew = ? WHERE id = ? AND client_id = ? AND billing_cycle IN ('monthly', 'yearly')");
    $stmt->bind_param('iii', $enabled, $serviceId, $clientId);
    $stmt->execute();
    $changed = billing_affected($conn) === 1;
    $stmt->close();
    return $changed;
}

function billing_log_renewal($conn, $serviceId, $clientId, $amount, $result, $note)
{
    $serviceId = (int) $serviceId;
    $clientId = (int) $clientId;
    $amount = billing_money($amount);
    $created = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO renewal_events (client_service_id, client_id, amount, result, note, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iissss', $serviceId, $clientId, $amount, $result, $note, $created);
    $stmt->execute();
    $stmt->close();
}

// Moves next_renewal forward only if it still holds the value we read. Returns false when
// another request already renewed this service for the same period.
function billing_claim_renewal($conn, $serviceId, $expectedNext, $newNext)
{
    $stmt = $conn->prepare('UPDATE client_services SET next_renewal = ? WHERE id = ? AND next_renewal = ?');
    $serviceId = (int) $serviceId;
    $stmt->bind_param('sis', $newNext, $serviceId, $expectedNext);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function billing_process_renewals($conn, $clientId = null)
{
    $today = date('Y-m-d');
    $stats = array('renewed' => 0, 'waiting' => 0, 'suspended' => 0);
    if ($clientId) {
        $clientId = (int) $clientId;
        $stmt = $conn->prepare("SELECT * FROM client_services WHERE client_id = ? AND auto_renew = 1 AND billing_cycle IN ('monthly', 'yearly') AND status IN ('active', 'past_due', 'suspended') AND next_renewal IS NOT NULL AND next_renewal != '' AND next_renewal <= ?");
        $stmt->bind_param('is', $clientId, $today);
    } else {
        $stmt = $conn->prepare("SELECT * FROM client_services WHERE auto_renew = 1 AND billing_cycle IN ('monthly', 'yearly') AND status IN ('active', 'past_due', 'suspended') AND next_renewal IS NOT NULL AND next_renewal != '' AND next_renewal <= ?");
        $stmt->bind_param('s', $today);
    }
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();

    foreach ($rows as $row) {
        $status = (string) $row['status'];
        $lastAttempt = (string) ($row['last_attempt_on'] ?? '');
        if (($status === 'past_due' || $status === 'suspended') && $lastAttempt === $today) {
            continue;
        }

        $serviceId = (int) $row['id'];
        $ownerId = (int) $row['client_id'];
        $amount = (float) $row['price'];
        $cycle = (string) $row['billing_cycle'];
        $base = (string) $row['next_renewal'];
        if ($base < $today) {
            $base = $today;
        }
        $next = billing_add_cycle($base, $cycle);

        // Claim the period and debit the wallet in one transaction. If another tab or cron run
        // already moved next_renewal, the claim fails and nothing is charged twice.
        billing_tx($conn, 'begin');
        $claimed = billing_claim_renewal($conn, $serviceId, (string) $row['next_renewal'], $next);
        if ($claimed && billing_wallet_debit($conn, $ownerId, $amount)) {
            $active = 'active';
            $update = $conn->prepare('UPDATE client_services SET status = ?, end_date = ?, grace_until = NULL, last_attempt_on = ? WHERE id = ?');
            $update->bind_param('sssi', $active, $next, $today, $serviceId);
            $update->execute();
            $update->close();
            $renewKind = (string) ($row['unit_kind'] ?? '');
            $renewQty = (int) ($row['unit_quantity'] ?? 0);
            billing_add_units($conn, $ownerId, $renewKind, $renewQty);
            if ($renewKind === 'sms' && $renewQty > 0 && function_exists('sms_remember_credit')) {
                sms_remember_credit($conn, $ownerId, $renewQty, 'Renewed from the wallet');
            }
            billing_record_entry($conn, $ownerId, $amount, 'debit', 'renewal', 'completed', 'wallet', (string) $row['service_name'], $serviceId);
            billing_log_renewal($conn, $serviceId, $ownerId, $amount, 'renewed', 'Renewed through ' . $next);
            billing_tx($conn, 'commit');
            billing_mail_client_event($conn, $ownerId, 'renewed', array(
                'service' => (string) $row['service_name'],
                'amount' => billing_money($amount),
                'next' => $next
            ));
            $stats['renewed']++;
            continue;
        }
        billing_tx($conn, 'rollback');
        if (!$claimed) {
            continue;
        }

        $grace = (string) ($row['grace_until'] ?? '');
        if ($status === 'active' || $grace === '') {
            $grace = date('Y-m-d', strtotime($today . ' +7 days'));
            $pastDue = 'past_due';
            $update = $conn->prepare('UPDATE client_services SET status = ?, grace_until = ?, last_attempt_on = ? WHERE id = ?');
            $update->bind_param('sssi', $pastDue, $grace, $today, $serviceId);
            $update->execute();
            $update->close();
            billing_log_renewal($conn, $serviceId, $ownerId, $amount, 'waiting', 'Waiting for wallet funds until ' . $grace);
            billing_mail_client_event($conn, $ownerId, 'renewal-waiting', array(
                'service' => (string) $row['service_name'],
                'amount' => billing_money($amount)
            ));
            $stats['waiting']++;
            continue;
        }

        if ($grace < $today) {
            $suspended = 'suspended';
            $update = $conn->prepare('UPDATE client_services SET status = ?, last_attempt_on = ? WHERE id = ?');
            $update->bind_param('ssi', $suspended, $today, $serviceId);
            $update->execute();
            $update->close();
            if ($status !== 'suspended') {
                billing_log_renewal($conn, $serviceId, $ownerId, $amount, 'suspended', 'Suspended after the grace period. It resumes automatically when the wallet can cover renewal.');
                billing_mail_client_event($conn, $ownerId, 'suspended', array(
                    'service' => (string) $row['service_name'],
                    'amount' => billing_money($amount)
                ));
                $stats['suspended']++;
            }
            continue;
        }

        $update = $conn->prepare('UPDATE client_services SET last_attempt_on = ? WHERE id = ?');
        $update->bind_param('si', $today, $serviceId);
        $update->execute();
        $update->close();
        billing_log_renewal($conn, $serviceId, $ownerId, $amount, 'waiting', 'Still waiting for wallet funds.');
        $stats['waiting']++;
    }

    return $stats;
}
