<?php

function billing_service_definitions()
{
    return array(
        'bulk-sms' => array(
            'title' => 'Bulk SMS',
            'summary' => 'Informational SMS for cooperatives, companies, parties, and personal use. A smaller send costs more per message. A larger send costs less.',
            'icon' => 'message-square-text',
            'tags' => array('AGM', 'Election', 'Festival'),
            'contact' => 'Bulk SMS Service',
            'action' => 'Buy credits'
        ),
        'bulk-voice' => array(
            'title' => 'Bulk auto voice calls',
            'summary' => 'The same notices as a recorded voice call: AGM, program, event, election, or festival. Volume slabs apply.',
            'icon' => 'phone-call',
            'tags' => array('Auto call', 'Nepali or English', 'Volume slabs'),
            'contact' => 'Bulk Voice Call',
            'action' => 'Buy calls'
        ),
        'domain-registration' => array(
            'title' => 'Domain registration',
            'summary' => 'Register a .com or .com.np domain in Nepal and let it renew before it expires.',
            'icon' => 'globe',
            'tags' => array('.com', '.com.np', 'Auto-renew'),
            'contact' => 'Domain Registration',
            'action' => 'Register'
        ),
        'hosting-server' => array(
            'title' => 'Hosting & server management',
            'summary' => 'Website hosting and hands-on server management for sites that need to stay online in Nepal.',
            'icon' => 'server',
            'tags' => array('Hosting', 'SSL', 'Server care'),
            'contact' => 'Domain Hosting & Server Management',
            'action' => 'Buy hosting'
        ),
        'professional-email' => array(
            'title' => 'Domain email on Zoho',
            'summary' => 'name@yourdomain.com for a cooperative or any organization. Managed by Aakash Technologies, a Zoho authorized partner in Nepal.',
            'icon' => 'mail',
            'tags' => array('Zoho', 'Your domain', 'Auto-renew'),
            'contact' => 'Professional Email',
            'action' => 'Buy mailboxes'
        ),
        'custom-websites' => array(
            'title' => 'Custom websites',
            'summary' => 'A finished website for a company, portfolio, bank or cooperative, restaurant, school, hotel, or news portal. Book the type and send the brief online.',
            'icon' => 'panels-top-left',
            'tags' => array('Company', 'School', 'Hotel', 'News'),
            'contact' => 'Custom Website',
            'action' => 'Book a website'
        ),
        'cyber-security' => array(
            'title' => 'On-site cyber training',
            'summary' => 'Field training for cooperative directors, staff, and members: how to use today’s tools, what misuse can cause, and how to stay safe from cyber attacks.',
            'icon' => 'shield-check',
            'tags' => array('Directors', 'Staff', 'Members'),
            'contact' => 'Cyber Security Training',
            'action' => 'Book training'
        )
    );
}

function billing_default_plans()
{
    return array(
        array('code' => 'sms-slab', 'service_slug' => 'bulk-sms', 'name' => 'SMS by volume', 'summary' => 'Pay the slab rate for the exact number of informational SMS you need.', 'billing_cycle' => 'one_time', 'price' => 0, 'unit_kind' => 'sms', 'unit_quantity' => 0, 'auto_renew_default' => 0, 'needs_detail' => 'sms', 'sort_order' => 10),
        array('code' => 'voice-slab', 'service_slug' => 'bulk-voice', 'name' => 'Auto voice calls by volume', 'summary' => 'Pay the slab rate for the exact number of auto voice calls you need.', 'billing_cycle' => 'one_time', 'price' => 0, 'unit_kind' => 'voice_calls', 'unit_quantity' => 0, 'auto_renew_default' => 0, 'needs_detail' => 'voice', 'sort_order' => 20),
        array('code' => 'domain-com', 'service_slug' => 'domain-registration', 'name' => '.com domain', 'summary' => 'One .com domain for a year, renewed from your wallet.', 'billing_cycle' => 'yearly', 'price' => 2400, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'domain', 'sort_order' => 30),
        array('code' => 'domain-np', 'service_slug' => 'domain-registration', 'name' => '.com.np domain', 'summary' => 'One .com.np domain for a year, renewed from your wallet.', 'billing_cycle' => 'yearly', 'price' => 1500, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'domain', 'sort_order' => 40),
        array('code' => 'hosting-business', 'service_slug' => 'hosting-server', 'name' => 'Website hosting', 'summary' => 'Yearly hosting with SSL for one website, plus routine server care.', 'billing_cycle' => 'yearly', 'price' => 4800, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'hosting', 'sort_order' => 50),
        array('code' => 'hosting-managed', 'service_slug' => 'hosting-server', 'name' => 'Managed server', 'summary' => 'Monthly server management for a site or mail server that needs a person watching it.', 'billing_cycle' => 'monthly', 'price' => 8500, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'hosting', 'sort_order' => 60),
        array('code' => 'email-1', 'service_slug' => 'professional-email', 'name' => '1 Zoho mailbox', 'summary' => 'One name@yourdomain.com mailbox on Zoho, renewed yearly.', 'billing_cycle' => 'yearly', 'price' => 1800, 'unit_kind' => 'mailbox', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'email', 'sort_order' => 70),
        array('code' => 'email-5', 'service_slug' => 'professional-email', 'name' => '5 Zoho mailboxes', 'summary' => 'Five organization mailboxes on your domain, renewed yearly.', 'billing_cycle' => 'yearly', 'price' => 7500, 'unit_kind' => 'mailbox', 'unit_quantity' => 5, 'auto_renew_default' => 1, 'needs_detail' => 'email', 'sort_order' => 80),
        array('code' => 'email-10', 'service_slug' => 'professional-email', 'name' => '10 Zoho mailboxes', 'summary' => 'Ten organization mailboxes on your domain, renewed yearly.', 'billing_cycle' => 'yearly', 'price' => 14000, 'unit_kind' => 'mailbox', 'unit_quantity' => 10, 'auto_renew_default' => 1, 'needs_detail' => 'email', 'sort_order' => 90),
        array('code' => 'web-company', 'service_slug' => 'custom-websites', 'name' => 'Company website', 'summary' => 'Home, about, services, and contact. Mobile layout and an enquiry form. You supply the words and photos in the booking.', 'billing_cycle' => 'one_time', 'price' => 45000, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 0, 'needs_detail' => 'website', 'sort_order' => 100),
        array('code' => 'web-portfolio', 'service_slug' => 'custom-websites', 'name' => 'Personal portfolio', 'summary' => 'Home, selected work, about, and contact for one person.', 'billing_cycle' => 'one_time', 'price' => 25000, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 0, 'needs_detail' => 'website', 'sort_order' => 110),
        array('code' => 'web-sahakari', 'service_slug' => 'custom-websites', 'name' => 'Bank or cooperative website', 'summary' => 'Home, services, notices, team, member information, and contact.', 'billing_cycle' => 'one_time', 'price' => 65000, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 0, 'needs_detail' => 'website', 'sort_order' => 120),
        array('code' => 'web-restaurant', 'service_slug' => 'custom-websites', 'name' => 'Restaurant website', 'summary' => 'Home, menu, location, and contact.', 'billing_cycle' => 'one_time', 'price' => 35000, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 0, 'needs_detail' => 'website', 'sort_order' => 130),
        array('code' => 'web-school', 'service_slug' => 'custom-websites', 'name' => 'School website', 'summary' => 'Home, academics, admissions, notices, and contact.', 'billing_cycle' => 'one_time', 'price' => 70000, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 0, 'needs_detail' => 'website', 'sort_order' => 140),
        array('code' => 'web-hotel', 'service_slug' => 'custom-websites', 'name' => 'Hotel website', 'summary' => 'Home, rooms, gallery, location, and a booking enquiry.', 'billing_cycle' => 'one_time', 'price' => 55000, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 0, 'needs_detail' => 'website', 'sort_order' => 150),
        array('code' => 'web-news', 'service_slug' => 'custom-websites', 'name' => 'News portal', 'summary' => 'Home, categories, article pages, and contact. You supply the first stories.', 'billing_cycle' => 'one_time', 'price' => 90000, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 0, 'needs_detail' => 'website', 'sort_order' => 160),
        array('code' => 'train-directors', 'service_slug' => 'cyber-security', 'name' => 'Directors session', 'summary' => 'On-site session for up to 25 directors or board members. Safe use of phones, email, and banking tools, plus common cyber attacks.', 'billing_cycle' => 'one_time', 'price' => 28000, 'unit_kind' => '', 'unit_quantity' => 25, 'auto_renew_default' => 0, 'needs_detail' => 'training', 'sort_order' => 170),
        array('code' => 'train-staff', 'service_slug' => 'cyber-security', 'name' => 'Staff session', 'summary' => 'On-site session for up to 40 staff. Everyday technology, risky habits, and what to do when something looks wrong.', 'billing_cycle' => 'one_time', 'price' => 32000, 'unit_kind' => '', 'unit_quantity' => 40, 'auto_renew_default' => 0, 'needs_detail' => 'training', 'sort_order' => 180),
        array('code' => 'train-members', 'service_slug' => 'cyber-security', 'name' => 'Members session', 'summary' => 'On-site awareness for up to 100 members. Practical warnings about fraud, password sharing, and unsafe links.', 'billing_cycle' => 'one_time', 'price' => 40000, 'unit_kind' => '', 'unit_quantity' => 100, 'auto_renew_default' => 0, 'needs_detail' => 'training', 'sort_order' => 190),
        array('code' => 'train-field-day', 'service_slug' => 'cyber-security', 'name' => 'Full field day', 'summary' => 'One day at your address for directors, staff, and members together, up to 120 people.', 'billing_cycle' => 'one_time', 'price' => 60000, 'unit_kind' => '', 'unit_quantity' => 120, 'auto_renew_default' => 0, 'needs_detail' => 'training', 'sort_order' => 200)
    );
}

function billing_default_slabs()
{
    return array(
        array('service_slug' => 'bulk-sms', 'min_qty' => 500, 'max_qty' => 2000, 'unit_price' => 1.10, 'sort_order' => 1),
        array('service_slug' => 'bulk-sms', 'min_qty' => 2001, 'max_qty' => 10000, 'unit_price' => 0.85, 'sort_order' => 2),
        array('service_slug' => 'bulk-sms', 'min_qty' => 10001, 'max_qty' => 50000, 'unit_price' => 0.65, 'sort_order' => 3),
        array('service_slug' => 'bulk-sms', 'min_qty' => 50001, 'max_qty' => 200000, 'unit_price' => 0.50, 'sort_order' => 4),
        array('service_slug' => 'bulk-voice', 'min_qty' => 200, 'max_qty' => 1000, 'unit_price' => 3.50, 'sort_order' => 1),
        array('service_slug' => 'bulk-voice', 'min_qty' => 1001, 'max_qty' => 5000, 'unit_price' => 2.80, 'sort_order' => 2),
        array('service_slug' => 'bulk-voice', 'min_qty' => 5001, 'max_qty' => 20000, 'unit_price' => 2.20, 'sort_order' => 3),
        array('service_slug' => 'bulk-voice', 'min_qty' => 20001, 'max_qty' => 100000, 'unit_price' => 1.80, 'sort_order' => 4)
    );
}

function billing_audiences()
{
    return array(
        'cooperative' => 'Cooperative (Sahakari)',
        'company' => 'Company',
        'party' => 'Party',
        'personal' => 'Personal'
    );
}

function billing_purposes()
{
    return array(
        'agm' => 'AGM',
        'program' => 'Program',
        'event' => 'Event',
        'election' => 'Election',
        'festival' => 'Festival',
        'notice' => 'General notice'
    );
}

function billing_page_copy()
{
    return array(
        'bulk-sms' => array(
            'kicker' => 'Bulk SMS in Nepal',
            'lead' => 'Send an informational text to members, customers, voters, or your own list. The rate on this page is the rate you pay. A short list costs more per SMS. A long list costs less.',
            'points' => array(
                'For cooperatives, companies, parties, and personal messages.',
                'Typical uses: AGM, program, event, election, festival, and general notices.',
                'You choose the sender name, write the message, and set how many SMS you need before you pay.',
                'Credits are added as soon as the wallet payment succeeds. The client panel then shows the SMS portal login. Sending is done at sms.aakashtechnologies.com.np with that username and password.',
                'Before payment you accept a declaration: the message will not be used for anything the Government of Nepal or prevailing law prohibits, and not to deceive or defraud. Misuse is your responsibility under that law.'
            )
        ),
        'bulk-voice' => array(
            'kicker' => 'Bulk auto voice calls in Nepal',
            'lead' => 'A recorded voice call carries the same kind of notice as an SMS when people are more likely to listen than to read. You write the script on this form.',
            'points' => array(
                'Same audiences: cooperative, company, party, or personal.',
                'Same occasions: AGM, program, event, election, festival, or a general notice.',
                'Choose Nepali or English and the date the calls should go out.',
                'The price is per call and drops as the volume rises. There is no separate quote call. An active voice or SMS purchase also opens the SMS portal login in the client panel.',
                'Before payment you accept a declaration: the call will not be used for anything the Government of Nepal or prevailing law prohibits, and not to deceive or defraud. Misuse is your responsibility under that law.'
            )
        ),
        'domain-registration' => array(
            'kicker' => 'Domain registration in Nepal',
            'lead' => 'Reserve the name your cooperative, company, or project will use online. The yearly price renews from your wallet unless you turn auto-renew off.',
            'points' => array(
                '.com and .com.np are listed with the price.',
                'Enter the exact domain and the organization name. That is the whole order.',
                'Registration is requested after payment. This site does not check the registrar live. If the name is already taken, the team returns the amount to your wallet.'
            )
        ),
        'hosting-server' => array(
            'kicker' => 'Hosting and server management',
            'lead' => 'Keep a website online, with SSL and someone responsible for the server. This is yearly hosting or monthly server management, not a custom design project.',
            'points' => array(
                'Website hosting covers one site, SSL, and routine care for a year.',
                'Managed server is monthly care when the server itself needs watching.',
                'Tell us the domain and what the server is for. Renewal continues from the wallet.'
            )
        ),
        'professional-email' => array(
            'kicker' => 'Domain email for organizations',
            'lead' => 'Mailboxes such as info@yourcoop.com.np, run on Zoho and looked after by Aakash Technologies, a Zoho authorized partner in Nepal.',
            'points' => array(
                'For cooperatives and any organization that should not use a free personal address.',
                'Choose 1, 5, or 10 mailboxes and type the names you want, such as info or chairperson.',
                'The domain must be yours, or buy the domain on this site first.',
                'The yearly fee renews from your wallet.'
            )
        ),
        'custom-websites' => array(
            'kicker' => 'Websites built for a specific organization',
            'lead' => 'Pick the kind of site you need. The price is the full booking price for the pages named on that package. The form collects the brief, so the project can start without a discovery call.',
            'points' => array(
                'Company, personal portfolio, bank or cooperative, restaurant, school, hotel, and news portal.',
                'Each package lists the pages that are included.',
                'You add what the site must do, the about text, the public phone and email, a preferred domain, a deadline, and the business address.',
                'A different page count is a different package, chosen here. It is not arranged over the phone.'
            )
        ),
        'cyber-security' => array(
            'kicker' => 'Field cyber training',
            'lead' => 'The trainer comes to your address. Directors, staff, and members learn how to use current technology, how the wrong use creates risk, and how to reduce cyber attacks.',
            'points' => array(
                'Separate sessions for directors (sanchalak), staff (karmachari), and members (sadasya), plus a full field day.',
                'Topics stay practical: phones, email, passwords, fake links, payment fraud, and what to do when an account looks compromised.',
                'You enter the venue address, district, headcount, preferred date, and the topics to spend time on. That booking is the request to come to you.',
                'Headcount cannot be higher than the package. Choose the larger session if the room is bigger.'
            )
        )
    );
}

function billing_catalog_rows()
{
    return array(
        array('Bulk SMS Service', 'bulk-sms', 'Informational SMS for cooperatives, companies, parties, and personal use, priced by volume.', 'message-square-text', 'AGM,Election,Festival', 1),
        array('Bulk Voice Call', 'bulk-voice', 'Auto voice calls for the same notices, priced by volume.', 'phone-call', 'Auto call,Volume slabs', 2),
        array('Domain Registration', 'domain-registration', 'Register a .com or .com.np domain and renew it automatically.', 'globe', '.com,.com.np,Auto-renew', 3),
        array('Domain Hosting & Server Management', 'hosting-server', 'Website hosting and server management in Nepal.', 'server', 'Hosting,SSL,Server care', 4),
        array('Professional Email', 'professional-email', 'Zoho mailboxes on your own domain, managed in Nepal.', 'mail', 'Zoho,Mailboxes,Auto-renew', 5),
        array('Custom Websites', 'custom-websites', 'Company, portfolio, cooperative, restaurant, school, hotel, and news websites.', 'panels-top-left', 'Company,School,Hotel,News', 6),
        array('Cyber Security Training', 'cyber-security', 'On-site training for directors, staff, and members.', 'shield-check', 'Directors,Staff,Members', 7)
    );
}

function billing_money($amount)
{
    return number_format((float) $amount, 2, '.', '');
}

function billing_money_label($amount)
{
    $value = (float) $amount;
    $formatted = abs($value - round($value)) < 0.001
        ? number_format($value, 0)
        : number_format($value, 2);
    return 'NPR ' . $formatted;
}

function billing_unit_label($amount)
{
    $value = (float) $amount;
    $formatted = abs($value - round($value, 2)) < 0.001 && abs($value - round($value)) > 0.001
        ? number_format($value, 2)
        : (abs($value - round($value)) < 0.001 ? number_format($value, 0) : number_format($value, 2));
    return 'NPR ' . $formatted;
}

function billing_cycle_suffix($cycle)
{
    if ($cycle === 'monthly') {
        return '/month';
    }
    if ($cycle === 'yearly') {
        return '/year';
    }
    return '';
}

function billing_cycle_label($cycle)
{
    if ($cycle === 'monthly') {
        return 'Monthly';
    }
    if ($cycle === 'yearly') {
        return 'Yearly';
    }
    return 'One time';
}

function billing_add_cycle($date, $cycle)
{
    $base = new DateTimeImmutable($date);
    if ($cycle === 'monthly') {
        return $base->modify('+1 month')->format('Y-m-d');
    }
    if ($cycle === 'yearly') {
        return $base->modify('+1 year')->format('Y-m-d');
    }
    return $date;
}

function billing_affected($conn)
{
    return isset($conn->affected_rows) ? (int) $conn->affected_rows : 0;
}

function billing_exec($conn, $sql)
{
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Billing query failed.');
    }
    return $result;
}

function billing_table_columns($conn, $table)
{
    $allowed = array('client_services' => true, 'sms_campaigns' => true, 'client_users' => true);
    if (!isset($allowed[$table])) {
        throw new InvalidArgumentException('Unknown table.');
    }

    $names = array();
    if (DB_DRIVER === 'sqlite') {
        $result = billing_exec($conn, 'PRAGMA table_info(' . $table . ')');
        while ($row = $result->fetch_assoc()) {
            $names[] = (string) $row['name'];
        }
        return $names;
    }

    $result = billing_exec($conn, 'SHOW COLUMNS FROM `' . $table . '`');
    while ($row = $result->fetch_assoc()) {
        $names[] = (string) $row['Field'];
    }
    return $names;
}

function billing_setting($conn, $key)
{
    $stmt = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (string) $row['setting_value'] : '';
}

function billing_set_setting($conn, $key, $value)
{
    $current = billing_setting($conn, $key);
    if ($current === '' && $current !== '0') {
        $check = $conn->prepare('SELECT id FROM site_settings WHERE setting_key = ?');
        $check->bind_param('s', $key);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();
        if ($exists) {
            $stmt = $conn->prepare('UPDATE site_settings SET setting_value = ? WHERE setting_key = ?');
        } else {
            $stmt = $conn->prepare('INSERT INTO site_settings (setting_value, setting_key) VALUES (?, ?)');
        }
    } else {
        $stmt = $conn->prepare('UPDATE site_settings SET setting_value = ? WHERE setting_key = ?');
    }
    $stmt->bind_param('ss', $value, $key);
    $stmt->execute();
    $stmt->close();
}

function billing_create_tables($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS service_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            service_slug TEXT NOT NULL,
            name TEXT NOT NULL,
            summary TEXT NOT NULL,
            billing_cycle TEXT NOT NULL,
            price NUMERIC NOT NULL,
            unit_kind TEXT DEFAULT "",
            unit_quantity INTEGER DEFAULT 0,
            auto_renew_default INTEGER DEFAULT 0,
            needs_detail TEXT DEFAULT "",
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_wallets (
            client_id INTEGER PRIMARY KEY,
            balance NUMERIC NOT NULL DEFAULT 0
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS wallet_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            amount NUMERIC NOT NULL,
            direction TEXT NOT NULL,
            kind TEXT NOT NULL,
            status TEXT NOT NULL,
            method TEXT DEFAULT "",
            reference_note TEXT DEFAULT "",
            related_service_id INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_units (
            client_id INTEGER NOT NULL,
            unit_kind TEXT NOT NULL,
            balance INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (client_id, unit_kind)
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS renewal_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_service_id INTEGER NOT NULL,
            client_id INTEGER NOT NULL,
            amount NUMERIC NOT NULL,
            result TEXT NOT NULL,
            note TEXT DEFAULT "",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS rate_slabs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_slug TEXT NOT NULL,
            min_qty INTEGER NOT NULL,
            max_qty INTEGER NOT NULL,
            unit_price NUMERIC NOT NULL,
            sort_order INTEGER DEFAULT 0
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS site_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT DEFAULT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        return;
    }

    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS service_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(80) NOT NULL UNIQUE,
        service_slug VARCHAR(80) NOT NULL,
        name VARCHAR(255) NOT NULL,
        summary TEXT NOT NULL,
        billing_cycle VARCHAR(20) NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        unit_kind VARCHAR(40) DEFAULT "",
        unit_quantity INT DEFAULT 0,
        auto_renew_default TINYINT(1) DEFAULT 0,
        needs_detail VARCHAR(20) DEFAULT "",
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_wallets (
        client_id INT PRIMARY KEY,
        balance DECIMAL(12,2) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS wallet_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        direction VARCHAR(10) NOT NULL,
        kind VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL,
        method VARCHAR(40) DEFAULT "",
        reference_note VARCHAR(255) DEFAULT "",
        related_service_id INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_wallet_client (client_id),
        INDEX idx_wallet_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_units (
        client_id INT NOT NULL,
        unit_kind VARCHAR(40) NOT NULL,
        balance INT NOT NULL DEFAULT 0,
        PRIMARY KEY (client_id, unit_kind)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS renewal_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_service_id INT NOT NULL,
        client_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        result VARCHAR(30) NOT NULL,
        note VARCHAR(255) DEFAULT "",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_renewal_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS rate_slabs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_slug VARCHAR(80) NOT NULL,
        min_qty INT NOT NULL,
        max_qty INT NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        sort_order INT DEFAULT 0,
        INDEX idx_slab_service (service_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function billing_add_missing_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'client_services'));
    if (DB_DRIVER === 'sqlite') {
        $columns = array(
            'plan_code' => 'TEXT DEFAULT NULL',
            'billing_cycle' => 'TEXT DEFAULT "one_time"',
            'auto_renew' => 'INTEGER DEFAULT 0',
            'next_renewal' => 'TEXT DEFAULT NULL',
            'detail_label' => 'TEXT DEFAULT NULL',
            'unit_kind' => 'TEXT DEFAULT ""',
            'unit_quantity' => 'INTEGER DEFAULT 0',
            'grace_until' => 'TEXT DEFAULT NULL',
            'last_attempt_on' => 'TEXT DEFAULT NULL',
            'order_brief' => 'TEXT DEFAULT NULL'
        );
    } else {
        $columns = array(
            'plan_code' => 'VARCHAR(80) DEFAULT NULL',
            'billing_cycle' => 'VARCHAR(20) DEFAULT "one_time"',
            'auto_renew' => 'TINYINT(1) DEFAULT 0',
            'next_renewal' => 'DATE DEFAULT NULL',
            'detail_label' => 'VARCHAR(255) DEFAULT NULL',
            'unit_kind' => 'VARCHAR(40) DEFAULT ""',
            'unit_quantity' => 'INT DEFAULT 0',
            'grace_until' => 'DATE DEFAULT NULL',
            'last_attempt_on' => 'DATE DEFAULT NULL',
            'order_brief' => 'TEXT DEFAULT NULL'
        );
    }

    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE client_services ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_ensure($conn)
{
    billing_create_tables($conn);
    billing_add_missing_columns($conn);
    $version = (int) billing_setting($conn, 'billing_schema_version');
    if ($version < 1 && DB_DRIVER !== 'sqlite') {
        billing_exec($conn, "ALTER TABLE client_services MODIFY status ENUM('active','expired','suspended','pending','past_due','booked') DEFAULT 'active'");
    }
    if ($version < 2 && DB_DRIVER !== 'sqlite') {
        billing_exec($conn, "ALTER TABLE client_services MODIFY status ENUM('active','expired','suspended','pending','past_due','booked') DEFAULT 'active'");
    }
    if ($version < 2) {
        billing_set_setting($conn, 'billing_schema_version', '2');
    }
    billing_add_campaign_columns($conn);
    billing_add_portal_columns($conn);
    billing_seed_plans($conn);
    billing_refresh_plans($conn);
    billing_seed_slabs($conn);
    billing_sync_catalog($conn);
    site_ensure_public_settings($conn);
}

if (!function_exists('site_escape')) {
    function site_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

function site_public_defaults()
{
    return array(
        'site_name' => defined('SITE_NAME') ? SITE_NAME : 'Aakash Technologies',
        'site_email' => defined('SITE_EMAIL') ? SITE_EMAIL : 'info@aakashtechnologies.com',
        'site_phone' => defined('SITE_PHONE') ? SITE_PHONE : '',
        'whatsapp_number' => '',
        'site_location' => defined('SITE_LOCATION') ? SITE_LOCATION : 'Kathmandu, Nepal',
        'notice_enabled' => '0',
        'notice_title' => '',
        'notice_body' => '',
        'notice_link' => '',
        'notice_link_label' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'youtube_url' => '',
        'tiktok_url' => '',
        'linkedin_url' => '',
        'footer_tagline' => 'Practical technology for businesses ready to grow.',
        'footer_text' => 'Designed and built in Nepal.',
        'logo_path' => ''
    );
}

function site_ensure_public_settings($conn)
{
    foreach (site_public_defaults() as $key => $value) {
        $stmt = $conn->prepare('SELECT id FROM site_settings WHERE setting_key = ?');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exists) {
            billing_set_setting($conn, $key, $value);
        }
    }
}

function site_public_settings($conn)
{
    $settings = site_public_defaults();
    if (!$conn) {
        return $settings;
    }
    try {
        $stored = array();
        $result = $conn->query('SELECT setting_key, setting_value FROM site_settings');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stored[(string) $row['setting_key']] = (string) $row['setting_value'];
            }
        }
    } catch (Throwable $exception) {
        return $settings;
    }
    $managed = isset($stored['public_details_managed']) && $stored['public_details_managed'] === '1';
    if (!$managed) {
        if (!empty($stored['logo_path'])) {
            $settings['logo_path'] = $stored['logo_path'];
        }
        return $settings;
    }
    foreach ($settings as $key => $value) {
        if (array_key_exists($key, $stored)) {
            $settings[$key] = $stored[$key];
        }
    }
    return $settings;
}

function site_mail_href($email, $siteName)
{
    $email = trim((string) $email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    $subject = 'Query for ' . trim((string) $siteName);
    return 'mailto:' . $email . '?subject=' . rawurlencode($subject);
}

function site_public_url($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (!preg_match('#^https://#i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
        return '';
    }
    return $value;
}

function site_social_links($settings)
{
    $icons = array(
        'facebook_url' => array('Facebook', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H8v3h3v7h3v-7h2.6l.4-3H14v-1c0-.6.4-1 1-1z"/></svg>'),
        'instagram_url' => array('Instagram', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 3h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8a5 5 0 0 1 5-5zm8 2H8a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3zm-4 3.2A3.8 3.8 0 1 1 8.2 12 3.8 3.8 0 0 1 12 8.2zm0 2A1.8 1.8 0 1 0 13.8 12 1.8 1.8 0 0 0 12 10.2zM17.2 6.6a1 1 0 1 1-1 1 1 1 0 0 1 1-1z"/></svg>'),
        'youtube_url' => array('YouTube', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 12.2s0-3.2-.4-4.6a3 3 0 0 0-2.1-2.1C18.9 5 12 5 12 5s-6.9 0-8.5.5a3 3 0 0 0-2.1 2.1C1 9 1 12.2 1 12.2s0 3.2.4 4.6a3 3 0 0 0 2.1 2.1C5.1 19.4 12 19.4 12 19.4s6.9 0 8.5-.5a3 3 0 0 0 2.1-2.1c.4-1.4.4-4.6.4-4.6zM9.8 15.5v-6.6l6.2 3.3z"/></svg>'),
        'tiktok_url' => array('TikTok', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 3h2.2a5.2 5.2 0 0 0 3.6 3.4v2.3a7.4 7.4 0 0 1-3.6-1v6.6a5.7 5.7 0 1 1-5.7-5.7c.3 0 .6 0 .9.1v2.4a3.3 3.3 0 1 0 2.4 3.2V3z"/></svg>'),
        'linkedin_url' => array('LinkedIn', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 9H4V20h2.5zM5.2 4A1.6 1.6 0 1 0 5.2 7.2 1.6 1.6 0 0 0 5.2 4zM20 20h-2.5v-5.6c0-1.6-.6-2.6-2-2.6a2.2 2.2 0 0 0-2 1.5 2 2 0 0 0-.1.9V20H11V9h2.4v1.5a3.4 3.4 0 0 1 3-1.7c2.2 0 3.6 1.4 3.6 4.4z"/></svg>')
    );
    $links = array();
    foreach ($icons as $key => $meta) {
        $href = site_public_url(isset($settings[$key]) ? $settings[$key] : '');
        if ($href === '') {
            continue;
        }
        $links[] = array('label' => $meta[0], 'href' => $href, 'icon' => $meta[1]);
    }
    return $links;
}

function site_whatsapp_href($number)
{
    $raw = trim((string) $number);
    if ($raw === '' || stripos($raw, 'x') !== false) {
        return '';
    }
    $digits = preg_replace('/\D+/', '', $raw);
    if (!is_string($digits) || strlen($digits) < 8 || strlen($digits) > 15) {
        return '';
    }
    if (strlen($digits) === 10) {
        $digits = '977' . $digits;
    }
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode('Hello, I have a query.');
}

function site_logo_file($path)
{
    $path = str_replace('\\', '/', (string) $path);
    if (!preg_match('/^uploads\/site-logo\.(png|jpe?g|webp|gif)$/', $path)) {
        return '';
    }
    $full = dirname(__DIR__) . '/' . $path;
    return is_file($full) ? $path : '';
}

function site_logo_web_path($path)
{
    $path = site_logo_file($path);
    if ($path === '') {
        return '';
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $prefix = (strpos($script, '/admin/') !== false || strpos($script, '/client/') !== false) ? '../' : '';
    return $prefix . $path;
}

function site_portal_identity($conn)
{
    $brand = site_public_settings($conn);
    $name = isset($brand['site_name']) ? trim((string) $brand['site_name']) : '';
    if ($name === '') {
        $name = 'Aakash Technologies';
    }
    $letter = strtoupper(substr($name, 0, 1));
    $logo = '';
    if (isset($brand['logo_path'])) {
        $logo = site_logo_web_path($brand['logo_path']);
    }
    return array(
        'name' => $name,
        'logo' => $logo,
        'letter' => $letter !== '' ? $letter : 'A'
    );
}

function site_poster_file($path)
{
    $path = str_replace('\\', '/', (string) $path);
    if (!preg_match('/^uploads\/service-poster-[a-z0-9-]+\.(png|jpe?g|webp|gif)$/', $path)) {
        return '';
    }
    $full = dirname(__DIR__) . '/' . $path;
    return is_file($full) ? $path : '';
}

function site_poster_web_path($path)
{
    $path = site_poster_file($path);
    if ($path === '') {
        return '';
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $prefix = (strpos($script, '/admin/') !== false || strpos($script, '/client/') !== false) ? '../' : '';
    return $prefix . $path;
}

function site_service_poster($conn, $slug)
{
    $definitions = billing_service_definitions();
    if (!isset($definitions[$slug]) || !$conn) {
        return '';
    }
    try {
        $stored = billing_setting($conn, 'service_poster_' . $slug);
    } catch (Throwable $exception) {
        return '';
    }
    return site_poster_web_path($stored);
}

function site_store_poster($file, $slug)
{
    $definitions = billing_service_definitions();
    if (!isset($definitions[$slug])) {
        return array('ok' => false, 'error' => 'That service is not on the public site.');
    }
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The photo could not be uploaded.');
    }
    if ((int) $file['size'] > 4194304) {
        return array('ok' => false, 'error' => 'Use a photo smaller than 4 MB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif');
    $image = @getimagesize($file['tmp_name']);
    $imageTypes = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) {
        $imageTypes[IMAGETYPE_WEBP] = 'webp';
    }
    if (!isset($types[$mime]) || !$image || !isset($imageTypes[$image[2]]) || $types[$mime] !== $imageTypes[$image[2]]) {
        return array('ok' => false, 'error' => 'The photo must be a PNG, JPG, WEBP, or GIF image.');
    }
    if ((int) $image[0] < 1 || (int) $image[1] < 1 || (int) $image[0] > 4000 || (int) $image[1] > 4000) {
        return array('ok' => false, 'error' => 'Use a photo no larger than 4000 pixels on a side.');
    }
    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The uploads folder could not be created.');
    }
    $relative = 'uploads/service-poster-' . $slug . '.' . $imageTypes[$image[2]];
    $target = dirname(__DIR__) . '/' . $relative;
    foreach (glob($dir . '/service-poster-' . $slug . '.*') as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return array('ok' => false, 'error' => 'The photo could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function site_store_logo($file)
{
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The logo could not be uploaded.');
    }
    if ((int) $file['size'] > 2097152) {
        return array('ok' => false, 'error' => 'Use a logo smaller than 2 MB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif');
    $image = @getimagesize($file['tmp_name']);
    $imageTypes = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) {
        $imageTypes[IMAGETYPE_WEBP] = 'webp';
    }
    if (!isset($types[$mime]) || !$image || !isset($imageTypes[$image[2]]) || $types[$mime] !== $imageTypes[$image[2]]) {
        return array('ok' => false, 'error' => 'Logo must be a PNG, JPG, WEBP, or GIF image.');
    }
    if ((int) $image[0] < 1 || (int) $image[1] < 1 || (int) $image[0] > 4000 || (int) $image[1] > 4000) {
        return array('ok' => false, 'error' => 'Use a logo no larger than 4000 pixels on a side.');
    }
    $types[$mime] = $imageTypes[$image[2]];
    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The uploads folder could not be created.');
    }
    $relative = 'uploads/site-logo.' . $types[$mime];
    $target = dirname(__DIR__) . '/' . $relative;
    foreach (glob($dir . '/site-logo.*') as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return array('ok' => false, 'error' => 'The logo could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function billing_add_campaign_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'sms_campaigns'));
    if (DB_DRIVER === 'sqlite') {
        $columns = array(
            'channel' => "TEXT DEFAULT 'sms'",
            'audience' => "TEXT DEFAULT ''",
            'purpose' => "TEXT DEFAULT ''",
            'recipients_list' => "TEXT DEFAULT ''",
            'declaration_text' => "TEXT DEFAULT ''"
        );
    } else {
        $columns = array(
            'channel' => "VARCHAR(20) DEFAULT 'sms'",
            'audience' => "VARCHAR(40) DEFAULT ''",
            'purpose' => "VARCHAR(40) DEFAULT ''",
            'recipients_list' => 'TEXT',
            'declaration_text' => 'TEXT'
        );
    }
    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE sms_campaigns ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_add_portal_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'client_users'));
    if (DB_DRIVER === 'sqlite') {
        $columns = array(
            'sms_portal_username' => "TEXT DEFAULT ''",
            'sms_portal_password' => "TEXT DEFAULT ''"
        );
    } else {
        $columns = array(
            'sms_portal_username' => "VARCHAR(80) DEFAULT ''",
            'sms_portal_password' => "VARCHAR(80) DEFAULT ''"
        );
    }
    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE client_users ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_sms_portal_url()
{
    return 'http://sms.aakashtechnologies.com.np/';
}

function billing_client_has_messaging($conn, $clientId)
{
    $clientId = (int) $clientId;
    $balances = billing_unit_balances($conn, $clientId);
    if ($balances['sms'] > 0 || $balances['voice_calls'] > 0 || $balances['voice_minutes'] > 0) {
        return true;
    }
    $stmt = $conn->prepare("SELECT id FROM client_services WHERE client_id = ? AND status = 'active' AND unit_kind IN ('sms', 'voice_calls', 'voice_minutes') LIMIT 1");
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool) $row;
}

function billing_portal_login($conn, $clientId)
{
    $clientId = (int) $clientId;
    $empty = array('username' => '', 'password' => '');
    $stmt = $conn->prepare('SELECT sms_portal_username, sms_portal_password FROM client_users WHERE id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return $empty;
    }
    return array(
        'username' => (string) $row['sms_portal_username'],
        'password' => (string) $row['sms_portal_password']
    );
}

function billing_save_portal_login($conn, $clientId, $username, $password)
{
    $clientId = (int) $clientId;
    if ($clientId < 1) {
        return 'Choose a client.';
    }
    $username = billing_plain_line($username, 60);
    $password = str_replace(array("\r", "\n"), '', (string) $password);
    if (strlen($password) > 80) {
        $password = substr($password, 0, 80);
    }
    $exists = $conn->prepare('SELECT id FROM client_users WHERE id = ?');
    $exists->bind_param('i', $clientId);
    $exists->execute();
    $found = $exists->get_result()->fetch_assoc();
    $exists->close();
    if (!$found) {
        return 'That client was not found.';
    }
    $current = billing_portal_login($conn, $clientId);
    if ($username === '') {
        $password = '';
    } elseif ($password === '') {
        $password = $current['password'];
    }
    if ($username !== '' && !preg_match('/^[A-Za-z0-9][A-Za-z0-9._@-]{1,59}$/', $username)) {
        return 'Portal username should be 2 to 60 letters, numbers, dots, or hyphens.';
    }
    if ($username !== '' && strlen($password) < 4) {
        return 'Enter the portal password from the SMS company. Leave it blank only when a password is already saved.';
    }
    $stmt = $conn->prepare('UPDATE client_users SET sms_portal_username = ?, sms_portal_password = ? WHERE id = ?');
    $stmt->bind_param('ssi', $username, $password, $clientId);
    $stmt->execute();
    $stmt->close();
    return '';
}

function billing_spend_units($conn, $clientId, $kind, $quantity)
{
    $clientId = (int) $clientId;
    $quantity = (int) $quantity;
    if ($quantity <= 0 || ($kind !== 'sms' && $kind !== 'voice_calls' && $kind !== 'voice_minutes')) {
        return false;
    }
    $stmt = $conn->prepare('UPDATE client_units SET balance = balance - ? WHERE client_id = ? AND unit_kind = ? AND balance >= ?');
    $stmt->bind_param('iisi', $quantity, $clientId, $kind, $quantity);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function billing_training_topics()
{
    return array(
        'safe-use' => 'Safe use of phones, email, and online tools',
        'misuse' => 'Risk from sharing passwords, OTPs, or links',
        'attacks' => 'Cyber attacks: fake messages, fraud calls, and payment traps',
        'response' => 'What to do when an account or payment looks compromised'
    );
}

function billing_checkout_asks($slug)
{
    $asks = array(
        'bulk-sms' => array('Who it is for', 'Why it is being sent', 'How many SMS', 'Sender name', 'The exact message', 'Send date, if known', 'Number list, or add it later from Messages', 'A signed declaration that the message is lawful'),
        'bulk-voice' => array('Who it is for', 'Why it is being sent', 'How many calls', 'Nepali or English', 'The exact script', 'Send date, if known', 'Number list, or add it later from Messages', 'A signed declaration that the call is lawful'),
        'domain-registration' => array('The exact domain', 'Organization or person name'),
        'hosting-server' => array('Domain', 'Organization', 'Whether it hosts a new site, an existing site, or site plus email'),
        'professional-email' => array('Your domain', 'Organization', 'One mailbox name per line, matching the package'),
        'custom-websites' => array('Organization', 'What the site must do', 'About text, public phone, and public email', 'Preferred domain and deadline', 'Business address'),
        'cyber-security' => array('Organization', 'Headcount within the package limit', 'Venue address and district', 'Preferred date', 'Which topics to spend more time on')
    );
    return isset($asks[$slug]) ? $asks[$slug] : array();
}

function billing_use_declaration()
{
    return 'म घोषणा गर्दछु कि मैले यो SMS वा भ्वाइस कल सेवा नेपाल सरकारले वा प्रचलित कानुनले निषेध गरेको कुनै काममा, तथा झुटा वा ठगी गर्ने गरी प्रयोग गर्ने छैन। त्यसो गरेमा वा त्यस्तो प्रयोग प्रमाणित भएमा म प्रचलित कानुनबमोजिम भोग्न तयार छु।';
}

function billing_form_guard_token($key)
{
    $key = (string) $key;
    if (!isset($_SESSION['form_guard']) || !is_array($_SESSION['form_guard'])) {
        $_SESSION['form_guard'] = array();
    }
    $current = isset($_SESSION['form_guard'][$key]) ? $_SESSION['form_guard'][$key] : null;
    if (is_array($current) && isset($current['token'], $current['at']) && (time() - (int) $current['at']) < 7200) {
        return (string) $current['token'];
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['form_guard'][$key] = array('token' => $token, 'at' => time());
    return $token;
}

function billing_form_guard_check($key, $post)
{
    $honeypot = isset($post['fax_number']) ? trim((string) $post['fax_number']) : '';
    if ($honeypot !== '') {
        return 'The form could not be submitted. Reload the page and try again.';
    }
    $token = isset($post['form_guard']) ? (string) $post['form_guard'] : '';
    $stored = (isset($_SESSION['form_guard'][$key]) && is_array($_SESSION['form_guard'][$key])) ? $_SESSION['form_guard'][$key] : null;
    if (!is_array($stored) || !isset($stored['token'], $stored['at']) || !hash_equals((string) $stored['token'], $token)) {
        return 'The form expired. Reload the page and try again.';
    }
    $age = time() - (int) $stored['at'];
    if ($age < 3) {
        return 'Please wait a moment and submit the form again.';
    }
    if ($age > 7200) {
        return 'The form expired. Reload the page and try again.';
    }
    return '';
}

function billing_form_guard_clear($key)
{
    if (isset($_SESSION['form_guard'][$key])) {
        unset($_SESSION['form_guard'][$key]);
    }
}

function billing_seed_plans($conn)
{
    $existing = array();
    $result = billing_exec($conn, 'SELECT code FROM service_plans');
    while ($row = $result->fetch_assoc()) {
        $existing[(string) $row['code']] = true;
    }

    $stmt = $conn->prepare('INSERT INTO service_plans (code, service_slug, name, summary, billing_cycle, price, unit_kind, unit_quantity, auto_renew_default, needs_detail, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
    foreach (billing_default_plans() as $plan) {
        if (isset($existing[$plan['code']])) {
            continue;
        }
        $price = billing_money($plan['price']);
        $unitKind = (string) $plan['unit_kind'];
        $unitQuantity = (int) $plan['unit_quantity'];
        $autoRenew = (int) $plan['auto_renew_default'];
        $needsDetail = (string) $plan['needs_detail'];
        $sortOrder = (int) $plan['sort_order'];
        $code = $plan['code'];
        $slug = $plan['service_slug'];
        $name = $plan['name'];
        $summary = $plan['summary'];
        $cycle = $plan['billing_cycle'];
        $stmt->bind_param('sssssssiisi', $code, $slug, $name, $summary, $cycle, $price, $unitKind, $unitQuantity, $autoRenew, $needsDetail, $sortOrder);
        $stmt->execute();
    }
    $stmt->close();
}

function billing_refresh_plans($conn)
{
    $codes = array();
    $stmt = $conn->prepare('UPDATE service_plans SET service_slug = ?, name = ?, summary = ?, billing_cycle = ?, unit_kind = ?, unit_quantity = ?, auto_renew_default = ?, needs_detail = ?, is_active = 1, sort_order = ? WHERE code = ?');
    foreach (billing_default_plans() as $plan) {
        $codes[] = $plan['code'];
        $slug = $plan['service_slug'];
        $name = $plan['name'];
        $summary = $plan['summary'];
        $cycle = $plan['billing_cycle'];
        $unitKind = (string) $plan['unit_kind'];
        $unitQuantity = (int) $plan['unit_quantity'];
        $autoRenew = (int) $plan['auto_renew_default'];
        $needsDetail = (string) $plan['needs_detail'];
        $sortOrder = (int) $plan['sort_order'];
        $code = $plan['code'];
        $stmt->bind_param('sssssiisis', $slug, $name, $summary, $cycle, $unitKind, $unitQuantity, $autoRenew, $needsDetail, $sortOrder, $code);
        $stmt->execute();
    }
    $stmt->close();

    $safeCodes = array();
    foreach ($codes as $code) {
        if (preg_match('/^[a-z0-9-]{2,40}$/', $code)) {
            $safeCodes[] = "'" . $code . "'";
        }
    }
    if ($safeCodes) {
        billing_exec($conn, 'UPDATE service_plans SET is_active = 0 WHERE code NOT IN (' . implode(',', $safeCodes) . ')');
    }
}

function billing_seed_slabs($conn)
{
    foreach (array('bulk-sms', 'bulk-voice') as $slug) {
        $check = $conn->prepare('SELECT COUNT(*) AS c FROM rate_slabs WHERE service_slug = ?');
        $check->bind_param('s', $slug);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
        if ($row && (int) $row['c'] > 0) {
            continue;
        }
        $insert = $conn->prepare('INSERT INTO rate_slabs (service_slug, min_qty, max_qty, unit_price, sort_order) VALUES (?, ?, ?, ?, ?)');
        foreach (billing_default_slabs() as $slab) {
            if ($slab['service_slug'] !== $slug) {
                continue;
            }
            $minQty = (int) $slab['min_qty'];
            $maxQty = (int) $slab['max_qty'];
            $unitPrice = billing_money($slab['unit_price']);
            $sortOrder = (int) $slab['sort_order'];
            $insert->bind_param('siisi', $slug, $minQty, $maxQty, $unitPrice, $sortOrder);
            $insert->execute();
        }
        $insert->close();
    }
}

function billing_sync_catalog($conn)
{
    billing_exec($conn, "UPDATE services SET is_active = 0 WHERE slug IN ('website-design', 'domain-hosting')");
    $update = $conn->prepare('UPDATE services SET title = ?, description = ?, icon = ?, features = ?, sort_order = ?, is_active = 1 WHERE slug = ?');
    $insert = $conn->prepare('INSERT INTO services (title, description, icon, features, sort_order, slug, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    foreach (billing_catalog_rows() as $row) {
        $title = $row[0];
        $slug = $row[1];
        $description = $row[2];
        $icon = $row[3];
        $features = $row[4];
        $sort = (int) $row[5];
        $update->bind_param('ssssis', $title, $description, $icon, $features, $sort, $slug);
        $update->execute();
        if (billing_affected($conn) > 0) {
            continue;
        }
        $check = $conn->prepare('SELECT id FROM services WHERE slug = ?');
        $check->bind_param('s', $slug);
        $check->execute();
        $found = $check->get_result()->fetch_assoc();
        $check->close();
        if ($found) {
            continue;
        }
        $insert->bind_param('ssssis', $title, $description, $icon, $features, $sort, $slug);
        $insert->execute();
    }
    $update->close();
    $insert->close();
}

function billing_normalize_plan($row)
{
    return array(
        'id' => (int) $row['id'],
        'code' => (string) $row['code'],
        'service_slug' => (string) $row['service_slug'],
        'name' => (string) $row['name'],
        'summary' => (string) $row['summary'],
        'billing_cycle' => (string) $row['billing_cycle'],
        'price' => (float) $row['price'],
        'unit_kind' => (string) ($row['unit_kind'] ?? ''),
        'unit_quantity' => (int) ($row['unit_quantity'] ?? 0),
        'auto_renew_default' => (int) ($row['auto_renew_default'] ?? 0),
        'needs_detail' => (string) ($row['needs_detail'] ?? ''),
        'is_active' => (int) ($row['is_active'] ?? 1),
        'sort_order' => (int) ($row['sort_order'] ?? 0)
    );
}

function billing_load_plans($conn)
{
    $result = billing_exec($conn, 'SELECT * FROM service_plans ORDER BY sort_order, id');
    $plans = array();
    while ($row = $result->fetch_assoc()) {
        $plans[] = billing_normalize_plan($row);
    }
    return $plans;
}

function billing_find_plan($conn, $code)
{
    $stmt = $conn->prepare('SELECT * FROM service_plans WHERE code = ? AND is_active = 1 LIMIT 1');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? billing_normalize_plan($row) : null;
}

function billing_update_price($conn, $code, $raw)
{
    if (!is_string($code) || !preg_match('/^[a-z0-9-]{2,40}$/', $code)) {
        return false;
    }
    $raw = trim((string) $raw);
    if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw)) {
        return false;
    }
    $price = billing_money($raw);
    if ((float) $price <= 0) {
        return false;
    }
    $stmt = $conn->prepare('UPDATE service_plans SET price = ? WHERE code = ?');
    $stmt->bind_param('ss', $price, $code);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool) $ok;
}

function billing_public_cards($conn)
{
    $plans = billing_default_plans();
    if ($conn) {
        try {
            $loaded = billing_load_plans($conn);
            if ($loaded) {
                $plans = $loaded;
            }
        } catch (Throwable $exception) {
            error_log('Public plan prices could not be loaded.');
        }
    }

    $grouped = array();
    foreach ($plans as $plan) {
        if (isset($plan['is_active']) && (int) $plan['is_active'] !== 1) {
            continue;
        }
        $grouped[$plan['service_slug']][] = $plan;
    }

    $cards = array();
    foreach (billing_service_definitions() as $slug => $service) {
        if (empty($grouped[$slug])) {
            continue;
        }
        $servicePlans = $grouped[$slug];
        $slabs = ($slug === 'bulk-sms' || $slug === 'bulk-voice') ? billing_slabs_for($conn, $slug) : array();
        $lines = array();
        $amount = '';
        $label = 'Buy or book online';
        if ($slabs) {
            $label = 'Volume rate';
            $amount = 'From ' . billing_unit_label($slabs[0]['unit_price']) . ' each';
            foreach ($slabs as $slab) {
                $lines[] = number_format($slab['min_qty']) . '–' . number_format($slab['max_qty']) . ' — ' . billing_unit_label($slab['unit_price']) . ' each';
            }
        } else {
            $lowest = null;
            foreach ($servicePlans as $plan) {
                if ((float) $plan['price'] <= 0) {
                    continue;
                }
                $lowest = $lowest === null ? (float) $plan['price'] : min($lowest, (float) $plan['price']);
                $lines[] = $plan['name'] . ' — ' . billing_money_label($plan['price']) . billing_cycle_suffix($plan['billing_cycle']);
            }
            $amount = ($lowest !== null && count($lines) > 1 ? 'From ' : '') . ($lowest === null ? '' : billing_money_label($lowest));
            if ($lowest !== null && count($lines) === 1) {
                $amount .= billing_cycle_suffix($servicePlans[0]['billing_cycle']);
            }
        }
        $cards[] = array(
            'slug' => $slug,
            'title' => $service['title'],
            'summary' => $service['summary'],
            'icon' => $service['icon'],
            'tags' => $service['tags'],
            'contact' => $service['contact'],
            'action' => isset($service['action']) ? $service['action'] : 'Buy',
            'price' => array(
                'label' => $label,
                'amount' => $amount,
                'details' => implode("\n", $lines)
            )
        );
    }
    return $cards;
}

function billing_buy_href($slug)
{
    $target = 'shop.php?service=' . rawurlencode($slug);
    if (!empty($_SESSION['client_id'])) {
        return 'client/' . $target;
    }
    return 'client/login.php?next=' . rawurlencode($target);
}

function billing_buy_href_plan($code)
{
    $target = 'checkout.php?plan=' . rawurlencode($code);
    if (!empty($_SESSION['client_id'])) {
        return 'client/' . $target;
    }
    return 'client/login.php?next=' . rawurlencode($target);
}

function client_safe_next($value)
{
    $value = (string) $value;
    if (!preg_match('/^(shop|checkout|wallet|services|index|campaigns|sms-portal|support|profile)\.php(\?(service|plan|amount)=[A-Za-z0-9_-]+)?$/', $value)) {
        return 'index.php';
    }
    return $value;
}

function billing_payment_instructions()
{
    return array(
        'esewa' => 'Pay with eSewa to ' . ((defined('ESEWA_ID') && ESEWA_ID !== '') ? ESEWA_ID : '98XXXXXXXX') . ', then enter the transaction code.',
        'khalti' => 'Pay with Khalti to ' . ((defined('KHALTI_ID') && KHALTI_ID !== '') ? KHALTI_ID : '98XXXXXXXX') . ', then enter the transaction code.',
        'bank' => (defined('BANK_DETAILS') && BANK_DETAILS !== '') ? BANK_DETAILS : 'Transfer to the company bank account shared by Aakash Technologies, then enter the voucher or reference number.'
    );
}

function billing_ensure_wallet($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT client_id FROM client_wallets WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        return;
    }
    $stmt = $conn->prepare('INSERT INTO client_wallets (client_id, balance) VALUES (?, 0)');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $stmt->close();
}

function billing_balance($conn, $clientId)
{
    billing_ensure_wallet($conn, $clientId);
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT balance FROM client_wallets WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (float) $row['balance'] : 0.0;
}

function billing_wallet_debit($conn, $clientId, $amount)
{
    billing_ensure_wallet($conn, $clientId);
    $clientId = (int) $clientId;
    $amount = billing_money($amount);
    if ((float) $amount <= 0) {
        return true;
    }
    $stmt = $conn->prepare('UPDATE client_wallets SET balance = balance - ? WHERE client_id = ? AND balance >= ?');
    $stmt->bind_param('sis', $amount, $clientId, $amount);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function billing_wallet_credit($conn, $clientId, $amount)
{
    billing_ensure_wallet($conn, $clientId);
    $clientId = (int) $clientId;
    $amount = billing_money($amount);
    $stmt = $conn->prepare('UPDATE client_wallets SET balance = balance + ? WHERE client_id = ?');
    $stmt->bind_param('si', $amount, $clientId);
    $stmt->execute();
    $stmt->close();
}

function billing_add_units($conn, $clientId, $kind, $quantity)
{
    if (($kind !== 'sms' && $kind !== 'voice_minutes' && $kind !== 'voice_calls') || $quantity <= 0) {
        return;
    }
    $clientId = (int) $clientId;
    $quantity = (int) $quantity;
    $stmt = $conn->prepare('SELECT balance FROM client_units WHERE client_id = ? AND unit_kind = ?');
    $stmt->bind_param('is', $clientId, $kind);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $stmt = $conn->prepare('UPDATE client_units SET balance = balance + ? WHERE client_id = ? AND unit_kind = ?');
        $stmt->bind_param('iis', $quantity, $clientId, $kind);
        $stmt->execute();
        $stmt->close();
        return;
    }
    $stmt = $conn->prepare('INSERT INTO client_units (client_id, unit_kind, balance) VALUES (?, ?, ?)');
    $stmt->bind_param('isi', $clientId, $kind, $quantity);
    $stmt->execute();
    $stmt->close();
}

function billing_unit_balances($conn, $clientId)
{
    $balances = array('sms' => 0, 'voice_minutes' => 0, 'voice_calls' => 0);
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT unit_kind, balance FROM client_units WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $balances[(string) $row['unit_kind']] = (int) $row['balance'];
    }
    $stmt->close();
    return $balances;
}

function billing_valid_domain($value)
{
    return (bool) preg_match('/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i', $value);
}

function billing_clean_detail($plan, $raw)
{
    $raw = trim((string) $raw);
    $needs = $plan['needs_detail'];
    if ($needs === '') {
        return array('', '');
    }
    if ($needs === 'domain') {
        $raw = strtolower($raw);
        if (!billing_valid_domain($raw)) {
            return array(null, 'Enter a valid domain name, such as yourbrand.com.');
        }
        return array($raw, '');
    }
    if (strlen($raw) < 2 || strlen($raw) > 80) {
        return array(null, 'Enter a short name for this service (2–80 characters).');
    }
    return array($raw, '');
}

function billing_detail_prompt($needs)
{
    if ($needs === 'domain') {
        return 'Domain name';
    }
    if ($needs === 'label') {
        return 'Name for this service';
    }
    return '';
}

function billing_record_entry($conn, $clientId, $amount, $direction, $kind, $status, $method, $note, $serviceId)
{
    $clientId = (int) $clientId;
    $amount = billing_money($amount);
    $serviceId = (int) $serviceId;
    $created = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO wallet_entries (client_id, amount, direction, kind, status, method, reference_note, related_service_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssssis', $clientId, $amount, $direction, $kind, $status, $method, $note, $serviceId, $created);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();
    return $id;
}

function billing_load_slabs($conn, $slug)
{
    $slug = (string) $slug;
    $stmt = $conn->prepare('SELECT * FROM rate_slabs WHERE service_slug = ? ORDER BY sort_order, min_qty');
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $slabs = array();
    while ($row = $result->fetch_assoc()) {
        $slabs[] = array(
            'id' => (int) $row['id'],
            'service_slug' => (string) $row['service_slug'],
            'min_qty' => (int) $row['min_qty'],
            'max_qty' => (int) $row['max_qty'],
            'unit_price' => (float) $row['unit_price'],
            'sort_order' => (int) $row['sort_order']
        );
    }
    $stmt->close();
    return $slabs;
}

function billing_slabs_for($conn, $slug)
{
    if ($conn) {
        try {
            $loaded = billing_load_slabs($conn, $slug);
            if ($loaded) {
                return $loaded;
            }
        } catch (Throwable $exception) {
            error_log('Volume rates could not be loaded.');
        }
    }
    $slabs = array();
    foreach (billing_default_slabs() as $slab) {
        if ($slab['service_slug'] === $slug) {
            $slabs[] = $slab;
        }
    }
    return $slabs;
}

function billing_slab_for_quantity($slabs, $quantity)
{
    foreach ($slabs as $slab) {
        if ($quantity >= (int) $slab['min_qty'] && $quantity <= (int) $slab['max_qty']) {
            return $slab;
        }
    }
    return null;
}

function billing_plain_line($value, $max)
{
    $value = trim(preg_replace('/\s+/', ' ', (string) $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function billing_plain_block($value, $max)
{
    $value = trim(str_replace("\r", '', (string) $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function billing_parse_numbers($raw)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $numbers = array();
    if (!is_array($lines)) {
        return array('ok' => false, 'error' => 'Paste the phone numbers, one per line.', 'numbers' => array());
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $digits = preg_replace('/[\s\-()]/', '', $line);
        if (!is_string($digits) || !preg_match('/^\+?[0-9]{7,15}$/', $digits)) {
            return array('ok' => false, 'error' => 'Each line should be one phone number.', 'numbers' => array());
        }
        $numbers[$digits] = $digits;
    }
    return array('ok' => true, 'error' => '', 'numbers' => array_values($numbers));
}

function billing_posted($post, $key)
{
    return isset($post[$key]) && is_string($post[$key]) ? $post[$key] : '';
}

function billing_prepare_order($conn, $plan, $post)
{
    $needs = (string) $plan['needs_detail'];
    $brief = array();
    $detail = '';
    $price = (float) $plan['price'];
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
        $price = round((float) $slab['unit_price'] * $quantity, 2);
        $brief['Audience'] = $audiences[$audience];
        $brief['Purpose'] = $purposes[$purpose];
        $brief['Quantity'] = number_format($quantity);
        $brief['Rate'] = billing_unit_label($slab['unit_price']) . ' each';
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
        if ($numbers !== '') {
            $brief['Number list'] = $numbers;
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
    return array(
        'ok' => true,
        'error' => '',
        'detail' => $detail,
        'brief' => $brief,
        'price' => round((float) $price, 2),
        'quantity' => (int) $quantity,
        'unit_kind' => $unitKind,
        'status' => $status
    );
}

function billing_save_slabs($conn, $posted)
{
    if (!is_array($posted)) {
        return 'No rates were submitted.';
    }
    $grouped = array();
    foreach (array('bulk-sms', 'bulk-voice') as $slug) {
        foreach (billing_load_slabs($conn, $slug) as $slab) {
            $id = (string) $slab['id'];
            if (!isset($posted[$id]) || !is_array($posted[$id])) {
                return 'A volume band was missing. Reload the page and try again.';
            }
            $row = $posted[$id];
            $min = isset($row['min']) ? (int) $row['min'] : 0;
            $max = isset($row['max']) ? (int) $row['max'] : 0;
            $raw = isset($row['price']) ? trim((string) $row['price']) : '';
            if ($min < 1 || $max < $min || !preg_match('/^\d{1,5}(\.\d{1,2})?$/', $raw) || (float) $raw <= 0) {
                return 'Each band needs a minimum, a higher maximum, and a rate above zero.';
            }
            $grouped[$slug][] = array('id' => (int) $slab['id'], 'min' => $min, 'max' => $max, 'price' => billing_money($raw));
        }
    }
    foreach ($grouped as $rows) {
        usort($rows, function ($left, $right) {
            return $left['min'] - $right['min'];
        });
        $previousMax = null;
        foreach ($rows as $row) {
            if ($previousMax !== null && $row['min'] <= $previousMax) {
                return 'Volume bands overlap. Each quantity should fall in only one band.';
            }
            if ($previousMax !== null && $row['min'] !== $previousMax + 1) {
                return 'Leave no gap between bands. The next minimum should be one more than the previous maximum.';
            }
            $previousMax = $row['max'];
        }
    }
    $stmt = $conn->prepare('UPDATE rate_slabs SET min_qty = ?, max_qty = ?, unit_price = ? WHERE id = ?');
    foreach ($grouped as $rows) {
        foreach ($rows as $row) {
            $min = $row['min'];
            $max = $row['max'];
            $price = $row['price'];
            $id = $row['id'];
            $stmt->bind_param('iisi', $min, $max, $price, $id);
            $stmt->execute();
        }
    }
    $stmt->close();
    return '';
}

function billing_update_slab($conn, $id, $minQty, $maxQty, $unitPrice)
{
    $id = (int) $id;
    $minQty = (int) $minQty;
    $maxQty = (int) $maxQty;
    $raw = trim((string) $unitPrice);
    if ($id <= 0 || $minQty < 1 || $maxQty < $minQty || !preg_match('/^\d{1,5}(\.\d{1,2})?$/', $raw)) {
        return false;
    }
    $price = billing_money($raw);
    if ((float) $price <= 0) {
        return false;
    }
    $stmt = $conn->prepare('UPDATE rate_slabs SET min_qty = ?, max_qty = ?, unit_price = ? WHERE id = ?');
    $stmt->bind_param('iisi', $minQty, $maxQty, $price, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool) $ok;
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
    if (!billing_wallet_debit($conn, $clientId, $price)) {
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
        billing_wallet_credit($conn, $clientId, $price);
        return array('ok' => false, 'error' => 'The purchase could not be saved. Your wallet was not charged.');
    }

    billing_record_entry($conn, $clientId, $price, 'debit', 'purchase', 'completed', 'wallet', $name, $serviceId);
    billing_add_units($conn, $clientId, $unitKind, $unitQuantity);
    if ($plan['needs_detail'] === 'sms' || $plan['needs_detail'] === 'voice') {
        billing_form_guard_clear('order-' . $plan['code']);
    }
    return array('ok' => true, 'service_id' => $serviceId, 'status' => $status, 'price' => (float) $price);
}

function billing_request_topup($conn, $clientId, $amount, $method, $reference)
{
    $allowed = array('esewa', 'khalti', 'bank');
    $amount = (int) $amount;
    $reference = trim((string) $reference);
    if (!in_array($method, $allowed, true)) {
        return 'Choose a payment method.';
    }
    if ($amount < 100 || $amount > 1000000) {
        return 'Enter an amount between NPR 100 and NPR 1,000,000.';
    }
    if (strlen($reference) < 4 || strlen($reference) > 80) {
        return 'Enter the payment reference (4–80 characters).';
    }
    billing_record_entry($conn, (int) $clientId, $amount, 'credit', 'topup', 'pending', $method, $reference, 0);
    return '';
}

function billing_approve_topup($conn, $entryId)
{
    $entryId = (int) $entryId;
    $stmt = $conn->prepare("SELECT id, client_id, amount FROM wallet_entries WHERE id = ? AND kind = 'topup' AND status = 'pending'");
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$entry) {
        return false;
    }
    $stmt = $conn->prepare("UPDATE wallet_entries SET status = 'completed' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $changed = billing_affected($conn) === 1;
    $stmt->close();
    if (!$changed) {
        return false;
    }
    billing_wallet_credit($conn, (int) $entry['client_id'], $entry['amount']);
    return true;
}

function billing_reject_topup($conn, $entryId)
{
    $entryId = (int) $entryId;
    $stmt = $conn->prepare("UPDATE wallet_entries SET status = 'rejected' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $changed = billing_affected($conn) === 1;
    $stmt->close();
    return $changed;
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
    $rows = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
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
        if (billing_wallet_debit($conn, $ownerId, $amount)) {
            $base = (string) $row['next_renewal'];
            if ($base < $today) {
                $base = $today;
            }
            $next = billing_add_cycle($base, $cycle);
            $active = 'active';
            $update = $conn->prepare('UPDATE client_services SET status = ?, next_renewal = ?, end_date = ?, grace_until = NULL, last_attempt_on = ? WHERE id = ?');
            $update->bind_param('ssssi', $active, $next, $next, $today, $serviceId);
            $update->execute();
            $update->close();
            billing_add_units($conn, $ownerId, (string) ($row['unit_kind'] ?? ''), (int) ($row['unit_quantity'] ?? 0));
            billing_record_entry($conn, $ownerId, $amount, 'debit', 'renewal', 'completed', 'wallet', (string) $row['service_name'], $serviceId);
            billing_log_renewal($conn, $serviceId, $ownerId, $amount, 'renewed', 'Renewed through ' . $next);
            $stats['renewed']++;
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
