<?php

function billing_service_definitions()
{
    return array(
        'bulk-sms' => array(
            'title' => 'Bulk SMS',
            'summary' => 'One notice, sent as a text. Type a quantity and see the bill.',
            'icon' => 'message-square-text',
            'tags' => array('AGM', 'Election', 'Festival'),
            'contact' => 'Bulk SMS Service',
            'action' => 'Buy credits'
        ),
        'bulk-voice' => array(
            'title' => 'Bulk auto voice calls',
            'summary' => 'The same notice, heard as a recorded call in Nepali or English.',
            'icon' => 'phone-call',
            'tags' => array('Auto call', 'Nepali or English', 'Volume slabs'),
            'contact' => 'Bulk Voice Call',
            'action' => 'Buy calls'
        ),
        'domain-registration' => array(
            'title' => 'Domain registration',
            'summary' => 'Check a .com name, or a Nepal name such as .com.np or .coop.np, then request the year.',
            'icon' => 'globe',
            'tags' => array('.com', '.com.np', '.coop.np'),
            'contact' => 'Domain Registration',
            'action' => 'Register'
        ),
        'hosting-server' => array(
            'title' => 'Hosting & server management',
            'summary' => 'Keep one website online with SSL, or have a person watch the server each month.',
            'icon' => 'server',
            'tags' => array('Hosting', 'SSL', 'Server care'),
            'contact' => 'Domain Hosting & Server Management',
            'action' => 'Buy hosting'
        ),
        'professional-email' => array(
            'title' => 'Domain email',
            'summary' => 'Open info@yourdomain. Choose 1, 5, or 10 mailboxes.',
            'icon' => 'mail',
            'tags' => array('Your domain', 'Mailboxes', 'Auto-renew'),
            'contact' => 'Professional Email',
            'action' => 'Buy mailboxes'
        ),
        'custom-websites' => array(
            'title' => 'Custom websites',
            'summary' => 'Pick the kind of site. The pages and the price are on the package.',
            'icon' => 'panels-top-left',
            'tags' => array('Company', 'School', 'Hotel', 'News'),
            'contact' => 'Custom Website',
            'action' => 'Book a website'
        ),
        'cyber-security' => array(
            'title' => 'On-site cyber training',
            'summary' => 'The trainer comes to your address for directors, staff, or members.',
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
        array('code' => 'domain-np', 'service_slug' => 'domain-registration', 'name' => '.np domain', 'summary' => 'One Nepal name, such as .com.np or .coop.np, for a year. Renewed from the wallet.', 'billing_cycle' => 'yearly', 'price' => 1500, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'domain', 'sort_order' => 40),
        array('code' => 'hosting-business', 'service_slug' => 'hosting-server', 'name' => 'Website hosting', 'summary' => 'Yearly hosting with SSL for one website, plus routine server care.', 'billing_cycle' => 'yearly', 'price' => 4800, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'hosting', 'sort_order' => 50),
        array('code' => 'hosting-managed', 'service_slug' => 'hosting-server', 'name' => 'Managed server', 'summary' => 'Monthly server management for a site or mail server that needs a person watching it.', 'billing_cycle' => 'monthly', 'price' => 8500, 'unit_kind' => '', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'hosting', 'sort_order' => 60),
        array('code' => 'email-1', 'service_slug' => 'professional-email', 'name' => '1 mailbox', 'summary' => 'One name@yourdomain.com mailbox, renewed yearly.', 'billing_cycle' => 'yearly', 'price' => 1800, 'unit_kind' => 'mailbox', 'unit_quantity' => 1, 'auto_renew_default' => 1, 'needs_detail' => 'email', 'sort_order' => 70),
        array('code' => 'email-5', 'service_slug' => 'professional-email', 'name' => '5 mailboxes', 'summary' => 'Five organization mailboxes on your domain, renewed yearly.', 'billing_cycle' => 'yearly', 'price' => 7500, 'unit_kind' => 'mailbox', 'unit_quantity' => 5, 'auto_renew_default' => 1, 'needs_detail' => 'email', 'sort_order' => 80),
        array('code' => 'email-10', 'service_slug' => 'professional-email', 'name' => '10 mailboxes', 'summary' => 'Ten organization mailboxes on your domain, renewed yearly.', 'billing_cycle' => 'yearly', 'price' => 14000, 'unit_kind' => 'mailbox', 'unit_quantity' => 10, 'auto_renew_default' => 1, 'needs_detail' => 'email', 'sort_order' => 90),
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
        'school' => 'School',
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
        'notice' => 'General notice',
        'otp' => 'OTP or account alert'
    );
}

function billing_page_copy()
{
    return array(
        'bulk-sms' => array(
            'kicker' => 'Bulk SMS provider in Nepal',
            'lead' => 'One notice, sent as a text to the mobiles you choose. Type a quantity and this page shows the bill, including 13% VAT.',
            'points' => array(
                'For a cooperative, a company, a party, a school, or personal use. Typical notices are an AGM, program, event, election, festival, a school notice, or a general notice.',
                'Type a quantity that sits inside one row of the rate table. That row is the price per SMS. The homepage Starts from label is only the row chosen for the front page.',
                'The sender name is 3 to 11 letters or numbers, such as Sahakari. Write the exact message people should receive.',
                'A number list is optional. If you paste one, each line must be one 10-digit Nepal mobile, including Nepal Telecom and Ncell, and the count must equal the quantity. Leave it empty and add the numbers when you send.',
                'Before payment you accept a declaration: the message will not be used for anything the Government of Nepal or prevailing law prohibits, and not to deceive or defraud. Misuse is your responsibility under that law.'
            ),
            'after' => array(
                'The SMS count is added to your client account as soon as the wallet payment succeeds.',
                'Send from the SMS dashboard in this same client account, or create an API token there and call it from your own website for an OTP or alert.',
                'Credits fall only when a message is sent. A longer message, or Nepali text, can use more than one credit per number.'
            ),
            'examples' => array(
                array('AGM', 'Namaste. The annual general meeting of [cooperative] is on [date] at [time], [place]. Please attend.'),
                array('School', 'Namaste. [School] will remain closed on [date]. Classes resume on [date].'),
                array('Festival', 'Namaste. [Organization] wishes you a happy [festival]. The office reopens on [date].')
            )
        ),
        'bulk-voice' => array(
            'kicker' => 'Bulk voice calls in Nepal',
            'lead' => 'The same kind of notice, spoken in a recorded call. Choose Nepali or English, type the words, and this page shows the bill with 13% VAT.',
            'points' => array(
                'For a cooperative, a company, a party, a school, or personal use. Typical notices are an AGM, program, event, election, festival, a school notice, or a general notice.',
                'Type a quantity that sits inside one row of the rate table. That row is the price per call. The homepage Starts from label is only the row chosen for the front page.',
                'Write the exact script people should hear, and choose Nepali or English. Add a send date if you already know it.',
                'A number list is optional. If you paste one, each line must be one 10-digit Nepal mobile, including Nepal Telecom and Ncell, and the count must equal the quantity. Leave it empty and keep the script under Messages.',
                'Before payment you accept a declaration: the call will not be used for anything the Government of Nepal or prevailing law prohibits, and not to deceive or defraud. Misuse is your responsibility under that law.'
            ),
            'after' => array(
                'The call count is added to your client account as soon as the wallet payment succeeds.',
                'This website keeps the script and the number list. The team places the call, and the voice credits are used then.',
                'Voice credits stay on the account until the calls are placed.'
            ),
            'examples' => array(
                array('AGM', 'Namaste. This is a notice from [cooperative]. The annual general meeting is on [date] at [time], [place]. Please attend.'),
                array('School', 'Namaste. This is a notice from [school]. The school will remain closed on [date]. Classes resume on [date].')
            )
        ),
        'domain-registration' => array(
            'kicker' => 'Domain registration in Nepal',
            'lead' => 'Check whether the name is free, then request .com or a Nepal ending such as .com.np or .coop.np for a year. The year starts when the team marks it active.',
            'points' => array(
                '.com is one yearly price. Every Nepal ending uses the other yearly price: .com.np, .edu.np, .gov.np, .net.np, .org.np, .info.np, .mil.np, .name.np, and .coop.np. The team confirms who can hold .edu.np, .gov.np, and .mil.np.',
                'Check the name on the Domain registration page first.',
                'If the name is free, send the request with the holder and address. A Nepal request also includes the required document. Pay the yearly bill from the wallet after that. The team registers the name only once it is paid, then marks it active.',
                'Hosting and email are separate. Requesting the name does not put a website or mailboxes online.'
            ),
            'after' => array(
                'The client portal shows the request, the payment, and Active after the team finishes the registration.',
                'The paid year starts when the request is marked active, then renews from the wallet. You can turn auto-renew off from the client panel. If the name cannot be registered, the amount returns to the wallet.',
                'You still buy hosting, email, or a website separately if you need them.'
            )
        ),
        'hosting-server' => array(
            'kicker' => 'Hosting provider in Nepal',
            'lead' => 'Keep a website online. Yearly hosting is one site and SSL. Monthly care is for a server that needs a person watching it. This is not a new website design.',
            'points' => array(
                'Website hosting is one site, SSL, and routine care for a year.',
                'Managed server is monthly care when a person needs to watch the server, for a website or for website plus email.',
                'Type the domain, the organization, and whether this is a new website, an existing website, or a website plus email.',
                'The domain name is separate. Register it on the domain page if you do not already have it. A custom design is the website service.'
            ),
            'after' => array(
                'The team sets up the hosting or server care from the domain and the use you selected. When it is active, cPanel opens from My Services.',
                'A yearly hosting plan and a monthly managed-server plan renew from the wallet on the due date.',
                'Turning auto-renew off stops the next charge. It does not refund the period already paid.'
            )
        ),
        'professional-email' => array(
            'kicker' => 'Domain email for organizations',
            'lead' => 'An address on your own domain, such as info@yourcoop.com.np. Choose 1, 5, or 10 mailboxes for the year.',
            'points' => array(
                'For a cooperative or any organization that should send mail from its own domain, such as info@yourcoop.com.np, not from a free Gmail or Yahoo address.',
                'Choose 1, 5, or 10 mailboxes. Type exactly that many names, one per line, without @. info becomes info@yourdomain.',
                'The domain must already be yours. If it is not, buy it on the domain page first. The team creates the mailboxes and the domain records that let mail arrive there. If the domain is registered somewhere else, the team sends you those records to add.',
                'The yearly price renews from the wallet. Mail already sitting in another inbox is not moved in this package.'
            ),
            'after' => array(
                'The team creates the mailboxes from the names you typed and looks after them.',
                'When the mailboxes are ready, Open email appears in My Services. Sign in there with the mailbox name and the password the team sends.',
                'The year renews from the wallet. Turn auto-renew off in the client panel if the mailboxes should stop at the end of the year.'
            )
        ),
        'custom-websites' => array(
            'kicker' => 'Websites built for a specific organization',
            'lead' => 'Choose the kind of site. Each package names the pages and the full price. The booking form is the brief.',
            'points' => array(
                'Company, personal portfolio, bank or cooperative, restaurant, school, hotel, and news portal. The pages are laid out for a phone and a computer.',
                'Each package names the pages included, such as home, about, services, and contact. A page that is not named is not included.',
                'A member login, an eSewa or Khalti checkout, and a live booking calendar are not in these packages. The hotel package is a booking enquiry.',
                'You write what the site must do, the about text, the public phone and email, a preferred domain, a deadline, and the business address. Photos and extra words go in that same brief.',
                'Domain, hosting, and email are separate purchases. This price is the website itself, paid once.'
            ),
            'after' => array(
                'The saved form is the brief the team builds from. Photos and extra words can be added in that same brief.',
                'The date you enter is the deadline you are asking for. The booking is the request to start.',
                'This is a one-time booking. It does not renew from the wallet.'
            )
        ),
        'cyber-security' => array(
            'kicker' => 'Field cyber training',
            'lead' => 'The trainer comes to your address. Pick the group, the topics, and the date. One visit, paid once.',
            'points' => array(
                'Directors (sanchalak) up to 25 people. Staff (karmachari) up to 40. Members (sadasya) up to 100. The full field day is one day for those groups together, up to 120 people.',
                'Topics you can choose: safe use of phones, email, and online tools; risk from sharing passwords, OTPs, or links; fake messages, fraud calls, and payment traps; and what to do when an account or payment looks wrong.',
                'Type the organization, the venue address, the district, the headcount, and a preferred date. Add a note if the room, the language mix, or one topic needs more time.',
                'The headcount cannot be higher than the package. Choose the larger session if more people will attend.'
            ),
            'after' => array(
                'The booking is the request for the trainer to come to that address. The date you pick is the date you are asking for.',
                'The team uses the topics and the note you saved. There is no separate briefing call.',
                'This is one visit, paid once. It does not renew from the wallet.'
            )
        )
    );
}

function site_hero_title_html($text)
{
    $lines = preg_split('/\r\n|\r|\n/', trim((string) $text));
    if (!is_array($lines)) {
        $lines = array();
    }
    $clean = array();
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $clean[] = $line;
        }
    }
    if (!$clean) {
        return '';
    }
    $last = count($clean) - 1;
    $html = array();
    foreach ($clean as $index => $line) {
        $safe = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($index === $last && preg_match('/^(.*\s)(\S+)$/u', $line, $match)) {
            $safe = htmlspecialchars($match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<span>' . htmlspecialchars($match[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        }
        $html[] = $safe;
    }
    return implode('<br>', $html);
}

function billing_public_page($conn, $slug)
{
    $pages = billing_page_copy();
    $page = isset($pages[$slug]) ? $pages[$slug] : array('kicker' => '', 'lead' => '', 'points' => array(), 'after' => array(), 'examples' => array());
    if (!$conn) {
        return $page;
    }
    $kicker = trim(billing_setting($conn, 'service_kicker_' . $slug));
    $lead = trim(billing_setting($conn, 'service_lead_' . $slug));
    $points = trim(billing_setting($conn, 'service_points_' . $slug));
    if ($kicker !== '') {
        $page['kicker'] = $kicker;
    }
    if ($lead !== '') {
        $page['lead'] = $lead;
    }
    if ($points !== '') {
        $lines = array();
        foreach (preg_split('/\r\n|\r|\n/', $points) as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        if ($lines) {
            $page['points'] = $lines;
            $page['points_saved'] = true;
        }
    }
    return $page;
}

function billing_service_guide()
{
    return array(
        'bulk-sms' => array(
            'includes' => array(
                'The price for your quantity, with 13% VAT shown before you pay',
                'A sender name of 3 to 11 letters or numbers',
                'SMS credits added when the wallet payment succeeds',
                'Sending from the SMS dashboard after identity is approved',
                'An API token for OTP and alerts from your own website'
            ),
            'steps' => array(
                'Type the quantity and read the bill.',
                'Pay that bill from the wallet.',
                'Send the notice from the SMS dashboard, or from your own system with an API token.'
            ),
            'notes' => array(
                'A smaller quantity costs more per SMS. A larger quantity costs less. The row that contains your number is the rate.',
                'Each number receives the same notice. If you paste a list, the count must match the quantity.',
                'Before payment you accept that the message will not be used for a purpose prohibited by the Government of Nepal or prevailing law, and not to deceive or defraud.'
            ),
            'plans' => array(
                'sms-slab' => 'Any quantity inside the rate table'
            ),
            'next' => array(
                array('bulk-voice', 'Say the same notice in a call'),
                array('domain-registration', 'Give the organization its own web name')
            )
        ),
        'bulk-voice' => array(
            'includes' => array(
                'The price per call, with 13% VAT shown before you pay',
                'Nepali or English, using the script you write',
                'Call credits added when the wallet payment succeeds',
                'The team places the call from the script saved under Messages'
            ),
            'steps' => array(
                'Type the quantity and read the bill.',
                'Write the script and choose Nepali or English.',
                'Pay from the wallet, then save the script under Messages.'
            ),
            'notes' => array(
                'A smaller quantity costs more per call. A larger quantity costs less.',
                'Each number hears the same script. If you paste a list, the count must match the quantity.',
                'Before payment you accept that the call will not be used for a purpose prohibited by the Government of Nepal or prevailing law, and not to deceive or defraud.'
            ),
            'plans' => array(
                'voice-slab' => 'Any quantity inside the rate table'
            ),
            'next' => array(
                array('bulk-sms', 'Send the same notice as a text'),
                array('cyber-security', 'Train the people who receive these notices')
            )
        ),
        'domain-registration' => array(
            'includes' => array(
                '.com and Nepal endings such as .com.np and .coop.np, checked on this site',
                'One year, then renewal from the wallet',
                'A Nepal request includes the required document',
                'The team registers a paid name, then the portal shows Active'
            ),
            'steps' => array(
                'Check the name.',
                'Send the request and pay the year from the wallet.',
                'The team registers it and marks it Active. The year starts then.'
            ),
            'notes' => array(
                'The site cannot register the name by itself. If it cannot be registered, the amount returns to the wallet.',
                'Hosting, email, and a website are separate.'
            ),
            'plans' => array(
                'domain-com' => 'A .com name for one year',
                'domain-np' => 'A Nepal name, such as .com.np or .coop.np, for one year'
            ),
            'next' => array(
                array('hosting-server', 'Keep a site online'),
                array('professional-email', 'Open info@this name'),
                array('custom-websites', 'Book the website')
            )
        ),
        'hosting-server' => array(
            'includes' => array(
                'Yearly hosting for one website, with SSL',
                'Or monthly care when a person needs to watch the server',
                'You type the domain and whether it is a new site, an existing site, or a site plus email',
                'The same bill renews from the wallet'
            ),
            'steps' => array(
                'Use a domain you have, or check a name first.',
                'Choose yearly hosting or monthly server care.',
                'Pay from the wallet. The team sets it up from that form.'
            ),
            'notes' => array(
                'This is hosting or server care. A new design is the website service.',
                'Turning auto-renew off stops the next charge. It does not refund the period already paid.'
            ),
            'plans' => array(
                'hosting-business' => 'One website for a year',
                'hosting-managed' => 'A person watches the server each month'
            ),
            'next' => array(
                array('domain-registration', 'Check the name first'),
                array('professional-email', 'Open domain email'),
                array('custom-websites', 'Book the website design')
            )
        ),
        'professional-email' => array(
            'includes' => array(
                'Addresses on your domain, such as info@yourcoop.com.np',
                '1, 5, or 10 mailboxes on your domain',
                'The team creates the mailboxes and the records that let mail arrive',
                'The year renews from the wallet'
            ),
            'steps' => array(
                'Use a domain you already have, or check a name first.',
                'Choose 1, 5, or 10 names, such as info and accounts.',
                'Pay the year. The team creates the mailboxes.'
            ),
            'notes' => array(
                'The domain must already be yours. If it is registered somewhere else, the team sends you the records to add.',
                'Mail already sitting in another inbox is not moved in this package.',
                'This website records the order. Open email appears in the client account after the team finishes the mailboxes.'
            ),
            'plans' => array(
                'email-1' => 'One address, such as info@',
                'email-5' => 'A small office',
                'email-10' => 'A larger office'
            ),
            'next' => array(
                array('domain-registration', 'Check the name first'),
                array('hosting-server', 'Host the website'),
                array('custom-websites', 'Book the website')
            )
        ),
        'custom-websites' => array(
            'includes' => array(
                'The pages named on the package, for a phone and a computer',
                'The full price on the package, paid once',
                'Your words, photos, phone, email, and deadline in the booking form',
                'The saved form is the brief the team builds from'
            ),
            'steps' => array(
                'Pick the kind of site.',
                'Write what it must do, the about text, and the public contact details.',
                'Pay once. The date you enter is the deadline you are asking for.'
            ),
            'notes' => array(
                'A page that is not named is not included. A member login, an eSewa or Khalti checkout, and a live booking calendar are not in these packages.',
                'The domain, the hosting, and the email are separate.'
            ),
            'plans' => array(
                'web-company' => 'Home, about, services, contact',
                'web-portfolio' => 'Home, work, about, contact',
                'web-sahakari' => 'Home, services, notices, team, contact',
                'web-restaurant' => 'Home, menu, location, contact',
                'web-school' => 'Home, academics, admissions, notices, contact',
                'web-hotel' => 'Home, rooms, gallery, a booking enquiry',
                'web-news' => 'Home, categories, articles, contact'
            ),
            'next' => array(
                array('domain-registration', 'Check the web name'),
                array('hosting-server', 'Host the finished site'),
                array('professional-email', 'Open domain email')
            )
        ),
        'cyber-security' => array(
            'includes' => array(
                'The trainer comes to the address you write',
                'A headcount limit on each package',
                'Topics you choose: phones and email, passwords and OTPs, fake messages and fraud calls, and what to do when something looks wrong',
                'One visit, paid once'
            ),
            'steps' => array(
                'Pick the group: directors, staff, members, or a full day.',
                'Write the venue, the district, the headcount, and the date you want.',
                'Pay once. That form is the brief. There is no separate briefing call.'
            ),
            'notes' => array(
                'Directors up to 25. Staff up to 40. Members up to 100. A full day is those groups together, up to 120.',
                'The headcount cannot be higher than the package.'
            ),
            'plans' => array(
                'train-directors' => 'Up to 25 directors or board members',
                'train-staff' => 'Up to 40 staff',
                'train-members' => 'Up to 100 members',
                'train-field-day' => 'One day, up to 120 people'
            ),
            'next' => array(
                array('bulk-sms', 'Tell people the session date by SMS'),
                array('bulk-voice', 'Tell people the session date by call'),
                array('professional-email', 'Open a proper domain inbox')
            )
        )
    );
}

function billing_catalog_rows()
{
    return array(
        array('Bulk SMS Service', 'bulk-sms', 'Informational SMS for cooperatives, companies, parties, and personal use, priced by volume.', 'message-square-text', 'AGM,Election,Festival', 1),
        array('Bulk Voice Call', 'bulk-voice', 'Auto voice calls for the same notices, priced by volume.', 'phone-call', 'Auto call,Volume slabs', 2),
        array('Domain Registration', 'domain-registration', 'Register a .com name, or a Nepal name such as .com.np or .coop.np, and renew it from the wallet.', 'globe', '.com,.com.np,.coop.np,Auto-renew', 3),
        array('Domain Hosting & Server Management', 'hosting-server', 'Website hosting and server management in Nepal.', 'server', 'Hosting,SSL,Server care', 4),
        array('Professional Email', 'professional-email', 'Mailboxes on your own domain, managed in Nepal.', 'mail', 'Your domain,Mailboxes,Auto-renew', 5),
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

function billing_active_offer($regular, $offer)
{
    $regular = (float) $regular;
    $offer = (float) $offer;
    if ($offer > 0 && $offer < $regular) {
        return $offer;
    }
    return 0.0;
}

function billing_selling_price($regular, $offer)
{
    $active = billing_active_offer($regular, $offer);
    return $active > 0 ? $active : (float) $regular;
}

function billing_rate_markup($regular, $offer, $each = false, $suffix = '')
{
    $regular = (float) $regular;
    $active = billing_active_offer($regular, $offer);
    $suffix = (string) $suffix;
    $format = $each ? 'billing_unit_label' : 'billing_money_label';
    $current = $format($active > 0 ? $active : $regular) . $suffix;
    if ($active <= 0) {
        return htmlspecialchars($current, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    $was = $format($regular) . $suffix;
    return '<s class="rate-was">' . htmlspecialchars($was, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</s> <span class="rate-now">' . htmlspecialchars($current, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span> <span class="rate-offer-tag">Offer</span>';
}

function billing_parse_offer($raw, $regular)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return array('ok' => true, 'price' => '0.00');
    }
    if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw) || (float) $raw <= 0 || (float) $raw >= (float) $regular) {
        return array('ok' => false, 'price' => '0.00');
    }
    return array('ok' => true, 'price' => billing_money($raw));
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

function billing_ensure_index($conn, $table, $name, $columns)
{
    $tables = array(
        'sms_messages' => true,
        'sms_campaigns' => true,
        'sms_api_hits' => true,
        'client_services' => true,
        'wallet_entries' => true,
        'domain_requests' => true,
        'client_kyc' => true,
        'login_attempts' => true,
        'client_users' => true,
        'inquiries' => true
    );
    if (!isset($tables[$table]) || !preg_match('/^[a-z0-9_]+$/', $name)) {
        return;
    }
    $list = array();
    foreach ($columns as $column) {
        if (!preg_match('/^[a-z0-9_]+$/', (string) $column)) {
            return;
        }
        $list[] = $column;
    }
    if (!$list) {
        return;
    }
    $columnSql = implode(', ', $list);
    try {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'CREATE INDEX IF NOT EXISTS ' . $name . ' ON ' . $table . ' (' . $columnSql . ')');
            return;
        }
        $found = $conn->query('SHOW INDEX FROM `' . $table . "` WHERE Key_name = '" . $name . "'");
        if ($found && $found->fetch_assoc()) {
            return;
        }
        billing_exec($conn, 'CREATE INDEX ' . $name . ' ON ' . $table . ' (' . $columnSql . ')');
    } catch (Throwable $exception) {
        error_log('Database index could not be added.');
    }
}

function billing_ensure_indexes($conn)
{
    billing_ensure_index($conn, 'sms_messages', 'idx_sms_msg_client_status', array('client_id', 'status', 'created_at'));
    billing_ensure_index($conn, 'sms_messages', 'idx_sms_msg_sent_day', array('client_id', 'status', 'sent_at'));
    billing_ensure_index($conn, 'sms_campaigns', 'idx_sms_campaign_due', array('channel', 'status', 'scheduled_at'));
    billing_ensure_index($conn, 'sms_campaigns', 'idx_sms_campaign_client_status', array('client_id', 'channel', 'status'));
    billing_ensure_index($conn, 'sms_campaigns', 'idx_sms_campaign_created', array('created_at'));
    billing_ensure_index($conn, 'client_services', 'idx_service_client_status', array('client_id', 'status'));
    billing_ensure_index($conn, 'client_services', 'idx_service_renew', array('auto_renew', 'status', 'next_renewal'));
    billing_ensure_index($conn, 'wallet_entries', 'idx_wallet_kind_status', array('kind', 'status'));
    billing_ensure_index($conn, 'domain_requests', 'idx_domain_open', array('domain_name', 'status'));
    billing_ensure_index($conn, 'client_kyc', 'idx_kyc_status', array('status'));
    billing_ensure_index($conn, 'client_users', 'idx_client_created', array('created_at'));
    billing_ensure_index($conn, 'login_attempts', 'idx_attempt_lookup', array('scope', 'ip', 'attempted_at'));
    billing_ensure_index($conn, 'sms_api_hits', 'idx_sms_hit_token', array('token_id', 'created_at'));
}

function billing_table_columns($conn, $table)
{
    $allowed = array(
        'client_services' => true,
        'sms_campaigns' => true,
        'client_users' => true,
        'domain_requests' => true,
        'support_tickets' => true,
        'client_kyc' => true,
        'sms_credit_notes' => true,
        'sms_number_lists' => true
    );
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
    $row = db_fetch_assoc($stmt);
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
        $exists = db_fetch_assoc($check);
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

function billing_mail_ok($email)
{
    $email = trim((string) $email);
    if ($email === '' || strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return (bool) preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $email);
}

function site_official_email()
{
    return 'info@aakashtechnologies.com.np';
}

function site_sender_email()
{
    return 'noreply@aakashtechnologies.com.np';
}

function site_sender_name()
{
    return 'Aakash Tech';
}

function site_email_or_official($email)
{
    $email = strtolower(trim((string) $email));
    if ($email === '' || $email === 'info@aakashtechnologies.com') {
        return site_official_email();
    }
    return $email;
}

function billing_notify_address($conn)
{
    $key = 'notify_email';
    $stmt = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    if (!$stmt) {
        return site_official_email();
    }
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return site_official_email();
    }
    $email = site_email_or_official($row['setting_value']);
    if (trim((string) $row['setting_value']) === '') {
        return '';
    }
    return billing_mail_ok($email) ? $email : '';
}

function billing_mail_from_address($conn)
{
    $saved = strtolower(trim(billing_setting($conn, 'mail_from')));
    if (billing_mail_ok($saved)) {
        return $saved;
    }
    return site_sender_email();
}

function billing_mail_reply_address($conn)
{
    $public = site_public_settings($conn);
    $email = isset($public['site_email']) ? (string) $public['site_email'] : '';
    $email = site_email_or_official($email);
    return billing_mail_ok($email) ? $email : site_official_email();
}

function billing_notify_clip($value, $max)
{
    $value = trim(str_replace(array("\r", "\n"), ' ', (string) $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function billing_notify_client_label($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT name, email, phone FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return 'Client ' . $clientId;
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return 'Client ' . $clientId;
    }
    return trim($row['name'] . ' · ' . $row['email'] . ($row['phone'] !== '' ? ' · ' . $row['phone'] : ''));
}

function billing_mail_send($to, $subject, $body, $from, $fromName, $replyTo = '')
{
    $to = trim((string) $to);
    $from = trim((string) $from);
    $replyTo = trim((string) $replyTo);
    if (!billing_mail_ok($replyTo)) {
        $replyTo = $from;
    }
    if (!billing_mail_ok($to) || !billing_mail_ok($from)) {
        return array('ok' => false, 'error' => 'Save a notification email and a sending address first.');
    }
    $subject = billing_notify_clip($subject, 140);
    if ($subject === '') {
        $subject = 'New request';
    }
    $body = str_replace("\r", '', (string) $body);
    $fromName = trim(str_replace(array("\r", "\n", '"'), '', (string) $fromName));
    $fromHeader = $fromName === '' ? $from : '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: " . $fromHeader . "\r\nReply-To: " . $replyTo;
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $sent = @mail($to, $encodedSubject, $body, $headers, '-f' . $from);
    if (!$sent) {
        return array('ok' => false, 'error' => 'This server did not accept the email. On cPanel, noreply@aakashtechnologies.com.np should be a mailbox on this domain.');
    }
    return array('ok' => true, 'error' => '');
}

function billing_notify_send($conn, $subject, $lines, $isTest)
{
    $to = billing_notify_address($conn);
    if ($to === '') {
        return array('ok' => false, 'error' => 'No notification email is saved.');
    }
    $from = billing_mail_from_address($conn);
    $replyTo = billing_mail_reply_address($conn);
    $fromName = site_sender_name();
    $body = is_array($lines) ? implode("\n", $lines) : (string) $lines;
    $result = billing_mail_send($to, $subject, $body, $from, $fromName, $replyTo);
    $stamp = date('Y-m-d H:i');
    if ($isTest) {
        billing_set_setting($conn, 'notify_test_at', $stamp);
        billing_set_setting($conn, 'notify_test_result', $result['ok'] ? 'accepted' : 'failed');
        billing_set_setting($conn, 'notify_test_error', $result['error']);
    } else {
        billing_set_setting($conn, 'notify_last_at', $stamp);
        billing_set_setting($conn, 'notify_last_result', $result['ok'] ? 'accepted' : 'failed');
        billing_set_setting($conn, 'notify_last_error', $result['error']);
    }
    return $result;
}

function billing_notify($conn, $subject, $lines)
{
    try {
        return billing_notify_send($conn, $subject, $lines, false);
    } catch (Throwable $exception) {
        error_log('Request email could not be sent.');
        return array('ok' => false, 'error' => 'The email could not be sent.');
    }
}

function billing_client_email($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT email FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    $email = $row ? trim((string) $row['email']) : '';
    return billing_mail_ok($email) ? $email : '';
}

function billing_mail_person($conn, $to, $subject, $lines)
{
    try {
        $from = billing_mail_from_address($conn);
        $replyTo = billing_mail_reply_address($conn);
        $fromName = site_sender_name();
        $body = is_array($lines) ? implode("\n", $lines) : (string) $lines;
        return billing_mail_send($to, $subject, $body, $from, $fromName, $replyTo);
    } catch (Throwable $exception) {
        error_log('Client email could not be sent.');
        return array('ok' => false, 'error' => 'The email could not be sent.');
    }
}

function billing_mail_catalog()
{
    return array(
        'account' => array(
            'when' => 'Someone registers, or you create the account in Clients',
            'subject' => 'Your account is ready',
            'lines' => array(
                'Your account is ready.',
                'Sign in to the client portal with this email address.',
                'Use the password you chose, or the password the team gave you. This email does not contain the password.'
            )
        ),
        'paid' => array(
            'when' => 'The client pays from the wallet and the service starts now',
            'subject' => 'Payment received: {service}',
            'lines' => array(
                'Your payment of NPR {amount} is complete.',
                'Service: {service}',
                'It is active on your account. Open My Services in the client portal.'
            )
        ),
        'booked' => array(
            'when' => 'The client pays for a website or a training visit',
            'subject' => 'Booking received: {service}',
            'lines' => array(
                'Your booking is saved.',
                'Payment of NPR {amount} is complete.',
                'Service: {service}',
                'The team will confirm the next step. Open My Services to see the date, place, or website link when it is ready.'
            )
        ),
        'sms-ready' => array(
            'when' => 'You add SMS credits on a client account',
            'subject' => 'SMS credits are on your account',
            'lines' => array(
                '{credits} SMS credits are on your account.',
                'Open Send SMS in the client portal and write the message.',
                'Note: {note}'
            )
        ),
        'office-service' => array(
            'when' => 'You add a service that was sold at the office',
            'subject' => 'Added to your account: {service}',
            'lines' => array(
                'The team added this to your account.',
                'Service: {service}',
                'Status: {status}',
                'Payment was taken outside the wallet.',
                'Open My Services in the client portal.'
            )
        ),
        'topup-waiting' => array(
            'when' => 'The client submits a wallet top-up',
            'subject' => 'Wallet payment received',
            'lines' => array(
                'We received a wallet top-up of NPR {amount}.',
                'It stays pending until the payment reference is confirmed.',
                'The amount is not in the wallet yet.'
            )
        ),
        'topup-done' => array(
            'when' => 'You confirm that top-up',
            'subject' => 'Wallet payment confirmed',
            'lines' => array(
                'NPR {amount} is now in your wallet.',
                'You can pay for a service from the client portal.'
            )
        ),
        'office-wallet' => array(
            'when' => 'You record a cash or office payment on the wallet',
            'subject' => 'Wallet payment recorded',
            'lines' => array(
                'NPR {amount} was added to your wallet.',
                'Note: {note}'
            )
        ),
        'topup-rejected' => array(
            'when' => 'You reject a wallet top-up',
            'subject' => 'Wallet payment was not added',
            'lines' => array(
                'The wallet top-up of NPR {amount} was not confirmed.',
                'It was not added to the wallet.',
                'If the money already left your account, reply to this email.'
            )
        ),
        'domain-request' => array(
            'when' => 'A domain name is requested',
            'subject' => 'Domain request received: {domain}',
            'lines' => array(
                'We saved the request for {domain}.',
                'The yearly bill is NPR {amount}.',
                'Pay it from the wallet when the balance covers the year. Registration starts after that payment is confirmed.'
            )
        ),
        'domain-paid' => array(
            'when' => 'The client pays the domain year from the wallet',
            'subject' => 'Domain payment received: {domain}',
            'lines' => array(
                'NPR {amount} was taken from the wallet for {domain}.',
                'The name is waiting to be registered. You will get another email when it is active.'
            )
        ),
        'domain-active' => array(
            'when' => 'You mark a domain active',
            'subject' => 'Domain active: {domain}',
            'lines' => array(
                '{domain} is marked Active.',
                'The paid year starts now and renews from the wallet.',
                'See it under My domains in the client panel.'
            )
        ),
        'domain-closed' => array(
            'when' => 'You close a domain request without registering it',
            'subject' => 'Domain request closed: {domain}',
            'lines' => array(
                'The domain request for {domain} was not registered.',
                '{refund}',
                'Note: {note}'
            )
        ),
        'renewed' => array(
            'when' => 'A monthly or yearly service renews from the wallet',
            'subject' => 'Renewal paid: {service}',
            'lines' => array(
                'NPR {amount} was taken from the wallet.',
                'Service: {service}',
                'It continues through {next}.'
            )
        ),
        'renewal-waiting' => array(
            'when' => 'A renewal cannot be paid because the wallet is short',
            'subject' => 'Renewal is waiting: {service}',
            'lines' => array(
                '{service} could not renew today.',
                'Add at least NPR {amount} to the wallet.',
                'It tries again automatically. After the grace period the service pauses until the wallet can cover it.'
            )
        ),
        'password-office' => array(
            'when' => 'You set a new password for a client',
            'subject' => 'Your password was changed',
            'lines' => array(
                'The team set a new password on your account.',
                'Sign in with the password they gave you. It is not written in this email.',
                'After you sign in, you can change it from Profile.'
            )
        ),
        'password' => array(
            'when' => 'The client asks to reset a password',
            'subject' => 'Reset your password',
            'lines' => array(
                'Use the link in the email to choose a new password. It works for 30 minutes.',
                'If you did not ask for this, ignore the email. The current password stays in place.'
            )
        ),
        'website-live' => array(
            'when' => 'You publish a booked website',
            'subject' => 'Your website is live: {service}',
            'lines' => array(
                'Your website is published.',
                'Open it from My Services, or go to {url}.',
                '{note}'
            )
        ),
        'training-set' => array(
            'when' => 'You confirm or complete a training visit',
            'subject' => 'Visit update: {service}',
            'lines' => array(
                'Your visit is {status}.',
                'Date: {date}',
                '{place}'
            )
        ),
        'hosting-ready' => array(
            'when' => 'You turn on a hosting login',
            'subject' => 'Hosting login is ready',
            'lines' => array(
                'The hosting login is ready in My Services.',
                'Username: {user}',
                'The password is the one the team sent. It is not written in this email.'
            )
        ),
        'mailbox-ready' => array(
            'when' => 'You turn on mailbox login',
            'subject' => 'Mailbox login is ready',
            'lines' => array(
                'Your mailbox login is ready.',
                'Addresses: {boxes}',
                'Open the inbox from My Services. The password is the one the team sent. It is not written in this email.'
            )
        ),
        'suspended' => array(
            'when' => 'A service pauses because the wallet stayed short',
            'subject' => 'Service paused: {service}',
            'lines' => array(
                '{service} is paused.',
                'The wallet did not cover NPR {amount} before the grace period ended.',
                'Add funds and it resumes on the next renewal check.'
            )
        ),
        'refund' => array(
            'when' => 'A domain order is returned to the wallet',
            'subject' => 'Amount returned to your wallet',
            'lines' => array(
                'NPR {amount} was returned to your wallet.',
                'Reason: {note}'
            )
        ),
        'enquiry' => array(
            'when' => 'A visitor sends the public contact form',
            'subject' => 'We received your message',
            'lines' => array(
                'We received your message from the website.',
                'A reply will come to this email address.'
            )
        ),
        'ticket-opened' => array(
            'when' => 'The client opens a support ticket',
            'subject' => 'Support ticket received',
            'lines' => array(
                'We received your support ticket: {subject}',
                'A reply will come by email and under Support in the client portal.'
            )
        ),
        'ticket-reply' => array(
            'when' => 'You reply to a support ticket',
            'subject' => 'Reply on your support ticket',
            'lines' => array(
                'There is a reply on: {subject}',
                '{reply}',
                'Open Support in the client portal to read it.'
            )
        ),
        'kyc-received' => array(
            'when' => 'The client submits identity details',
            'subject' => 'Identity details received',
            'lines' => array(
                'We received your identity details.',
                'SMS and voice stay closed until they are approved. You will get an email when that decision is made.'
            )
        ),
        'kyc-approved' => array(
            'when' => 'You approve identity details',
            'subject' => 'Identity approved',
            'lines' => array(
                'Your identity is approved.',
                'You can send SMS from the SMS dashboard. A voice job is saved under Messages, and the team places the call.'
            )
        ),
        'kyc-change' => array(
            'when' => 'You send identity details back for a change',
            'subject' => 'Identity needs a change',
            'lines' => array(
                'The identity submission was sent back.',
                'Note: {note}',
                'Update it in the client panel and submit again.'
            )
        ),
        'contact-email' => array(
            'when' => 'You change the client sign-in email',
            'subject' => 'Sign-in email changed',
            'lines' => array(
                'The team changed the sign-in email for this account.',
                'Sign in with {email}.',
                'The password stays the same. This email does not contain it.'
            )
        ),
        'contact-phone' => array(
            'when' => 'You change the client mobile number',
            'subject' => 'Mobile number changed',
            'lines' => array(
                'The team changed the mobile number on your account to {phone}.'
            )
        ),
        'login' => array(
            'when' => 'The client finishes signing in',
            'subject' => 'Sign-in on your account',
            'lines' => array(
                'A sign-in to your account was just completed.',
                'User ID: {id}',
                'Email: {email}',
                'IP address: {ip}',
                'Nepal time: {when}',
                'If you did not sign in, contact {contact} or open a support ticket. This email does not contain the password.'
            )
        )
    );
}

function billing_mail_fill($text, $map)
{
    $text = (string) $text;
    if (!is_array($map)) {
        return $text;
    }
    foreach ($map as $key => $value) {
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    }
    return $text;
}

function billing_mail_to_client($conn, $clientId, $subject, $lines)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT name, email FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return array('ok' => false, 'error' => 'The client could not be read.');
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    $email = $row ? trim((string) $row['email']) : '';
    if (!billing_mail_ok($email)) {
        return array('ok' => false, 'error' => 'That client has no email address.');
    }
    $name = $row && trim((string) $row['name']) !== '' ? trim((string) $row['name']) : 'there';
    $site = site_sender_name();
    $body = array('Hello ' . $name . ',', '');
    foreach ((array) $lines as $line) {
        $body[] = (string) $line;
    }
    $body[] = '';
    $body[] = $site;
    return billing_mail_person($conn, $email, $subject, $body);
}

function billing_mail_client_event($conn, $clientId, $key, $map = array())
{
    $catalog = billing_mail_catalog();
    if (!isset($catalog[$key])) {
        return array('ok' => false, 'error' => 'That email is not defined.');
    }
    $item = $catalog[$key];
    $lines = array();
    foreach ($item['lines'] as $line) {
        $filled = trim(billing_mail_fill($line, $map));
        if ($filled !== '') {
            $lines[] = $filled;
        }
    }
    return billing_mail_to_client($conn, $clientId, billing_mail_fill($item['subject'], $map), $lines);
}

function billing_client_login_notice($conn, $clientId)
{
    $clientId = (int) $clientId;
    if ($clientId < 1) {
        return;
    }
    $stmt = $conn->prepare('SELECT id, email, login_notice_at, login_notice_ip FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return;
    }
    $ip = function_exists('auth_client_ip') ? auth_client_ip() : '';
    if ($ip === '') {
        $ip = 'unknown';
    }
    $previousAt = isset($row['login_notice_at']) ? strtotime((string) $row['login_notice_at']) : false;
    $previousIp = isset($row['login_notice_ip']) ? (string) $row['login_notice_ip'] : '';
    if ($previousAt && $previousIp === $ip && (time() - $previousAt) < 1800) {
        return;
    }
    $when = new DateTime('now', new DateTimeZone('Asia/Kathmandu'));
    $stamp = $when->format('Y-m-d H:i');
    $public = site_public_settings($conn);
    $contact = isset($public['site_email']) && trim((string) $public['site_email']) !== '' ? trim((string) $public['site_email']) : site_official_email();
    billing_mail_client_event($conn, $clientId, 'login', array(
        'id' => (string) $clientId,
        'email' => (string) $row['email'],
        'ip' => $ip,
        'when' => $stamp,
        'contact' => $contact
    ));
    $savedAt = $when->format('Y-m-d H:i:s');
    $update = $conn->prepare('UPDATE client_users SET login_notice_at = ?, login_notice_ip = ? WHERE id = ?');
    if ($update) {
        $update->bind_param('ssi', $savedAt, $ip, $clientId);
        $update->execute();
        $update->close();
    }
}

function billing_mail_named_event($conn, $email, $name, $key, $map = array())
{
    $catalog = billing_mail_catalog();
    if (!isset($catalog[$key]) || !billing_mail_ok($email)) {
        return array('ok' => false, 'error' => 'That email could not be addressed.');
    }
    $item = $catalog[$key];
    $lines = array();
    foreach ($item['lines'] as $line) {
        $filled = trim(billing_mail_fill($line, $map));
        if ($filled !== '') {
            $lines[] = $filled;
        }
    }
    $who = trim((string) $name) !== '' ? trim((string) $name) : 'there';
    $body = array('Hello ' . $who . ',', '');
    foreach ($lines as $line) {
        $body[] = $line;
    }
    $body[] = '';
    $body[] = site_sender_name();
    return billing_mail_person($conn, $email, billing_mail_fill($item['subject'], $map), $body);
}

function billing_notify_test($conn)
{
    $to = billing_notify_address($conn);
    return billing_notify_send($conn, 'Test: request emails are reaching this inbox', array(
        'This is a test from the Aakash Technologies admin panel.',
        'If this message is in the inbox, new requests can be sent to ' . $to . '.',
        'Requests covered: contact messages, service orders, domain requests, paid domains, wallet top-ups, identity checks, and support tickets.',
        'Sent at ' . date('Y-m-d H:i') . '.'
    ), true);
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
            offer_price NUMERIC DEFAULT 0,
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
            sort_order INTEGER DEFAULT 0,
            is_start INTEGER DEFAULT 0,
            offer_price NUMERIC DEFAULT 0
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS site_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT DEFAULT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT NOT NULL,
            icon TEXT DEFAULT NULL,
            features TEXT DEFAULT NULL,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
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
        offer_price DECIMAL(12,2) NOT NULL DEFAULT 0,
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
        is_start TINYINT(1) NOT NULL DEFAULT 0,
        offer_price DECIMAL(12,2) NOT NULL DEFAULT 0,
        INDEX idx_slab_service (service_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT NOT NULL,
        icon VARCHAR(100) DEFAULT NULL,
        features TEXT DEFAULT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_active (is_active),
        INDEX idx_sort (sort_order)
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
            'order_brief' => 'TEXT DEFAULT NULL',
            'panel_user' => 'TEXT DEFAULT NULL',
            'panel_pass' => 'TEXT DEFAULT NULL',
            'panel_host' => 'TEXT DEFAULT NULL'
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
            'order_brief' => 'TEXT DEFAULT NULL',
            'panel_user' => 'VARCHAR(32) DEFAULT NULL',
            'panel_pass' => 'TEXT DEFAULT NULL',
            'panel_host' => 'VARCHAR(253) DEFAULT NULL'
        );
    }

    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE client_services ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_ensure_portal_tables($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            service_name TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            status TEXT DEFAULT 'active',
            start_date TEXT DEFAULT NULL,
            end_date TEXT DEFAULT NULL,
            price NUMERIC DEFAULT 0,
            plan_code TEXT DEFAULT NULL,
            billing_cycle TEXT DEFAULT 'one_time',
            auto_renew INTEGER DEFAULT 0,
            next_renewal TEXT DEFAULT NULL,
            detail_label TEXT DEFAULT NULL,
            order_brief TEXT DEFAULT NULL,
            unit_kind TEXT DEFAULT '',
            unit_quantity INTEGER DEFAULT 0,
            grace_until TEXT DEFAULT NULL,
            last_attempt_on TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            campaign_name TEXT NOT NULL,
            sender_id TEXT DEFAULT NULL,
            message_content TEXT NOT NULL,
            recipients_count INTEGER DEFAULT 0,
            channel TEXT DEFAULT 'sms',
            audience TEXT DEFAULT '',
            purpose TEXT DEFAULT '',
            recipients_list TEXT DEFAULT '',
            declaration_text TEXT DEFAULT '',
            status TEXT DEFAULT 'draft',
            scheduled_at TEXT DEFAULT NULL,
            sent_at TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            description TEXT NOT NULL,
            priority TEXT DEFAULT 'medium',
            status TEXT DEFAULT 'open',
            admin_reply TEXT DEFAULT NULL,
            client_followup TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS inquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            service TEXT DEFAULT NULL,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'new',
            admin_notes TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        return;
    }

    billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        service_name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'active',
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        price DECIMAL(10,2) DEFAULT 0,
        plan_code VARCHAR(80) DEFAULT NULL,
        billing_cycle VARCHAR(20) DEFAULT 'one_time',
        auto_renew TINYINT(1) DEFAULT 0,
        next_renewal DATE DEFAULT NULL,
        detail_label VARCHAR(255) DEFAULT NULL,
        order_brief TEXT DEFAULT NULL,
        unit_kind VARCHAR(40) DEFAULT '',
        unit_quantity INT DEFAULT 0,
        grace_until DATE DEFAULT NULL,
        last_attempt_on DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        campaign_name VARCHAR(255) NOT NULL,
        sender_id VARCHAR(20) DEFAULT NULL,
        message_content TEXT NOT NULL,
        recipients_count INT DEFAULT 0,
        channel VARCHAR(20) DEFAULT 'sms',
        audience VARCHAR(40) DEFAULT '',
        purpose VARCHAR(40) DEFAULT '',
        recipients_list TEXT,
        declaration_text TEXT,
        status VARCHAR(20) DEFAULT 'draft',
        scheduled_at DATETIME DEFAULT NULL,
        sent_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        priority VARCHAR(20) DEFAULT 'medium',
        status VARCHAR(20) DEFAULT 'open',
        admin_reply TEXT DEFAULT NULL,
        client_followup TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        service VARCHAR(100) DEFAULT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'new',
        admin_notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function billing_ensure($conn)
{
    billing_ensure_portal_tables($conn);
    billing_create_tables($conn);
    billing_add_missing_columns($conn);
    $version = (int) billing_setting($conn, 'billing_schema_version');
    if ($version < 3 && DB_DRIVER !== 'sqlite') {
        billing_exec($conn, "ALTER TABLE client_services MODIFY status ENUM('active','expired','suspended','pending','past_due','booked','refunded') DEFAULT 'active'");
    }
    if ($version < 3) {
        billing_set_setting($conn, 'billing_schema_version', '3');
    }
    billing_add_campaign_columns($conn);
    billing_add_ticket_columns($conn);
    billing_drop_portal_columns($conn);
    billing_ensure_kyc_table($conn);
    billing_ensure_domain_requests($conn);
    billing_seed_plans($conn);
    billing_refresh_plans($conn);
    billing_seed_slabs($conn);
    billing_ensure_slab_start($conn);
    billing_ensure_offer_prices($conn);
    billing_sync_catalog($conn);
    if (function_exists('mail_login_scrub')) {
        mail_login_scrub($conn);
    }
    site_ensure_public_settings($conn);
    if (function_exists('sms_ensure_tables')) {
        sms_ensure_tables($conn);
    }
    billing_ensure_indexes($conn);
    if (function_exists('panel_pass_migrate')) {
        panel_pass_migrate($conn);
    }
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
        'site_email' => site_email_or_official(defined('SITE_EMAIL') ? SITE_EMAIL : ''),
        'site_phone' => '',
        'whatsapp_number' => '',
        'viber_number' => '',
        'messenger_url' => '',
        'site_location' => defined('SITE_LOCATION') ? SITE_LOCATION : 'Kathmandu, Nepal',
        'notice_enabled' => '0',
        'notice_title' => '',
        'notice_body' => '',
        'notice_link' => '',
        'notice_link_label' => '',
        'notice_image' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'youtube_url' => '',
        'tiktok_url' => '',
        'linkedin_url' => '',
        'footer_tagline' => 'Practical technology for businesses ready to grow.',
        'footer_text' => 'Designed and built in Nepal.',
        'home_eyebrow' => 'For cooperatives, companies, parties, and personal work',
        'home_title' => "Bulk SMS.\nVoice calls.\nHosting and servers.",
        'home_lede' => 'Send the AGM, election, school, or festival notice, then keep the domain, the mail, and the website with the same company. The rate is on the page. The bill adds 13% VAT before you pay.',
        'home_ribbon' => 'Buy what you need. Recurring plans renew themselves.',
        'home_services_kicker' => 'What we do',
        'home_services_heading' => 'Read the rate. Buy it, or book it.',
        'home_services_text' => 'Open a service and see the rate before you create an account. Check a domain name, or open WHOIS check up to see who holds it. SMS and voice let you type a quantity and see the bill with 13% VAT. The same account covers hosting, domain email, the website, and field training.',
        'home_about_heading' => 'The rate is on the page. The order is the brief.',
        'home_about_text' => 'A cooperative can send the notice, register the name, host the site, open domain email, and train the people from one account. Companies, parties, schools, and personal use follow the same path.',
        'home_process_heading' => 'Buy it, then let it renew.',
        'home_process_text' => 'Create a client account, add wallet funds, and choose a plan. The first top-up is confirmed once. After that, checkout and renewals use the wallet.',
        'home_contact_heading' => 'Rates and orders are already online.',
        'privacy_policy' => '',
        'cookie_policy' => '',
        'logo_path' => '',
        'esewa_id' => defined('ESEWA_ID') ? ESEWA_ID : '',
        'khalti_id' => defined('KHALTI_ID') ? KHALTI_ID : '',
        'bank_details' => defined('BANK_DETAILS') ? BANK_DETAILS : ''
    );
}

function site_apply_mail_addresses($conn)
{
    $current = strtolower(trim(billing_setting($conn, 'site_email')));
    if ($current === '' || $current === 'info@aakashtechnologies.com') {
        billing_set_setting($conn, 'site_email', site_official_email());
    }
    $key = 'notify_email';
    $stmt = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    if ($stmt) {
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        if (!$row) {
            billing_set_setting($conn, 'notify_email', site_official_email());
        } else {
            $notify = strtolower(trim((string) $row['setting_value']));
            if ($notify === 'info@aakashtechnologies.com') {
                billing_set_setting($conn, 'notify_email', site_official_email());
            }
        }
    }
    $from = strtolower(trim(billing_setting($conn, 'mail_from')));
    if (!billing_mail_ok($from)) {
        billing_set_setting($conn, 'mail_from', site_sender_email());
    }
}

function site_ensure_public_settings($conn)
{
    site_apply_mail_addresses($conn);
    foreach (site_public_defaults() as $key => $value) {
        $stmt = $conn->prepare('SELECT id FROM site_settings WHERE setting_key = ?');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $exists = db_fetch_assoc($stmt);
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
    foreach ($settings as $key => $value) {
        if (strpos($key, 'home_') !== 0) {
            continue;
        }
        if (isset($stored[$key]) && trim($stored[$key]) !== '') {
            $settings[$key] = $stored[$key];
        }
    }
    $managed = isset($stored['public_details_managed']) && $stored['public_details_managed'] === '1';
    if (!$managed) {
        if (!empty($stored['logo_path'])) {
            $settings['logo_path'] = $stored['logo_path'];
        }
        foreach (array('privacy_policy', 'cookie_policy') as $legalKey) {
            if (isset($stored[$legalKey]) && trim($stored[$legalKey]) !== '') {
                $settings[$legalKey] = $stored[$legalKey];
            }
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

function site_chat_digits($number)
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
    return $digits;
}

function site_whatsapp_href($number)
{
    $digits = site_chat_digits($number);
    if ($digits === '') {
        return '';
    }
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode('Hello, I have a query.');
}

function site_viber_href($number)
{
    $digits = site_chat_digits($number);
    if ($digits === '') {
        return '';
    }
    return 'viber://chat?number=%2B' . $digits;
}

function site_chat_channels($settings)
{
    $settings = is_array($settings) ? $settings : array();
    $icons = array(
        'whatsapp' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 3.5A11 11 0 0 0 2.1 17.2L1 23l5.9-1.1A11 11 0 0 0 20.5 3.5zM12 20.3a8.3 8.3 0 0 1-4.2-1.1l-.3-.2-3.5.7.7-3.4-.2-.3A8.3 8.3 0 1 1 12 20.3zm4.6-6.2c-.3-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.6.1a6.8 6.8 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.5.2-.3a.5.5 0 0 0 0-.5c-.1-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.5 4 4.2 4.2 0 0 0 3 .4 2.5 2.5 0 0 0 1.6-1.2 2 2 0 0 0 .1-1.2c-.1-.1-.3-.2-.6-.3z"/></svg>',
        'viber' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.2 3C7.6 3 4.4 5.2 4 9.4c-.2 2.2.4 4 1.5 5.4L4.6 19l4.1-1.1c1 .5 2.1.8 3.3.8 4.7 0 8-2.4 8.2-6.7.2-4.2-3.2-9-8-9zm3.8 10.2c-.2.5-1 .9-1.6 1-.4.1-.9.2-2.6-.6-2.2-1-3.6-3.3-3.7-3.5-.1-.2-.9-1.2-.9-2.3s.6-1.6.8-1.8.4-.3.6-.3h.4c.1 0 .3 0 .5.4.2.5.6 1.6.7 1.7.1.1.1.3 0 .5-.1.2-.2.3-.3.5l-.2.3c-.1.1-.2.2-.1.4.1.2.6 1 1.3 1.6.9.8 1.6 1 1.8 1.1.2.1.4.1.5-.1.1-.2.6-.7.8-.9.2-.2.3-.2.5-.1.2.1 1.4.7 1.6.8.2.1.4.2.4.3.1.3 0 .8-.2 1.1z"/></svg>',
        'messenger' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3C6.8 3 3 6.6 3 11.2c0 2.6 1.3 4.9 3.3 6.4V21l3-1.6c.9.2 1.8.4 2.7.4 5.2 0 9-3.6 9-8.2S17.2 3 12 3zm1 11.1-2.3-2.4-4.4 2.4 4.8-5.1 2.3 2.4 4.4-2.4-4.8 5.1z"/></svg>'
    );
    $channels = array();
    $whatsapp = site_whatsapp_href(isset($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '');
    if ($whatsapp !== '') {
        $channels[] = array('key' => 'whatsapp', 'label' => 'WhatsApp', 'note' => 'No account needed', 'href' => $whatsapp, 'icon' => $icons['whatsapp']);
    }
    $viber = site_viber_href(isset($settings['viber_number']) ? $settings['viber_number'] : '');
    if ($viber !== '') {
        $channels[] = array('key' => 'viber', 'label' => 'Viber', 'note' => 'Stays open', 'href' => $viber, 'icon' => $icons['viber']);
    }
    $messenger = site_public_url(isset($settings['messenger_url']) ? $settings['messenger_url'] : '');
    if ($messenger !== '') {
        $channels[] = array('key' => 'messenger', 'label' => 'Messenger', 'note' => 'No account needed', 'href' => $messenger, 'icon' => $icons['messenger']);
    }
    return $channels;
}

function site_guest_chats($settings)
{
    $chats = array();
    foreach (site_chat_channels($settings) as $channel) {
        if ($channel['key'] === 'whatsapp' || $channel['key'] === 'messenger') {
            $chats[] = $channel;
        }
    }
    return $chats;
}

function site_public_file($path, $pattern)
{
    $path = str_replace('\\', '/', (string) $path);
    if (!preg_match($pattern, $path)) {
        return '';
    }
    $full = dirname(__DIR__) . '/' . $path;
    return is_file($full) ? $path : '';
}

function site_logo_file($path)
{
    return site_public_file($path, '/^uploads\/site-logo\.(png|jpe?g|webp|gif)$/');
}

function site_notice_file($path)
{
    return site_public_file($path, '/^uploads\/site-notice\.(png|jpe?g|webp|gif)$/');
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

function site_store_notice_image($file)
{
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The notice image could not be uploaded.');
    }
    if ((int) $file['size'] > 2097152) {
        return array('ok' => false, 'error' => 'Use a notice image smaller than 2 MB.');
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
        return array('ok' => false, 'error' => 'The notice image must be a PNG, JPG, WEBP, or GIF.');
    }
    if ((int) $image[0] < 1 || (int) $image[1] < 1 || (int) $image[0] > 4000 || (int) $image[1] > 4000) {
        return array('ok' => false, 'error' => 'Use a notice image no larger than 4000 pixels on a side.');
    }
    $types[$mime] = $imageTypes[$image[2]];
    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The uploads folder could not be created.');
    }
    $relative = 'uploads/site-notice.' . $types[$mime];
    $target = dirname(__DIR__) . '/' . $relative;
    foreach (glob($dir . '/site-notice.*') as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return array('ok' => false, 'error' => 'The notice image could not be saved.');
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
            'declaration_text' => "TEXT DEFAULT ''",
            'language' => "TEXT DEFAULT ''",
            'updated_at' => 'TEXT DEFAULT CURRENT_TIMESTAMP'
        );
    } else {
        $columns = array(
            'channel' => "VARCHAR(20) DEFAULT 'sms'",
            'audience' => "VARCHAR(40) DEFAULT ''",
            'purpose' => "VARCHAR(40) DEFAULT ''",
            'recipients_list' => 'TEXT',
            'declaration_text' => 'TEXT',
            'language' => "VARCHAR(20) DEFAULT ''",
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
        );
    }
    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE sms_campaigns ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_add_ticket_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'support_tickets'));
    if (isset($present['client_followup'])) {
        return;
    }
    $definition = DB_DRIVER === 'sqlite' ? 'TEXT DEFAULT NULL' : 'TEXT DEFAULT NULL';
    billing_exec($conn, 'ALTER TABLE support_tickets ADD COLUMN client_followup ' . $definition);
}

function billing_drop_portal_columns($conn)
{
    if (billing_setting($conn, 'portal_login_removed') === '1') {
        return;
    }
    $present = array_flip(billing_table_columns($conn, 'client_users'));
    $ok = true;
    foreach (array('sms_portal_username', 'sms_portal_password') as $name) {
        if (!isset($present[$name])) {
            continue;
        }
        try {
            billing_exec($conn, 'ALTER TABLE client_users DROP COLUMN ' . $name);
        } catch (Throwable $exception) {
            $ok = false;
            error_log('Old portal login column could not be removed.');
        }
    }
    if ($ok) {
        billing_set_setting($conn, 'portal_login_removed', '1');
    }
}

function billing_ensure_domain_requests($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS domain_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            domain_name TEXT NOT NULL,
            tld TEXT NOT NULL,
            holder_kind TEXT DEFAULT 'individual',
            holder_name TEXT DEFAULT '',
            document_path TEXT DEFAULT '',
            status TEXT DEFAULT 'requested',
            admin_note TEXT DEFAULT '',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            holder_address TEXT DEFAULT '',
            price TEXT DEFAULT '0.00',
            service_id INTEGER DEFAULT 0,
            activated_at TEXT DEFAULT NULL
        )");
    } else {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS domain_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            domain_name VARCHAR(190) NOT NULL,
            tld VARCHAR(20) NOT NULL,
            holder_kind VARCHAR(20) DEFAULT 'individual',
            holder_name VARCHAR(200) DEFAULT '',
            holder_address VARCHAR(200) DEFAULT '',
            document_path VARCHAR(255) DEFAULT '',
            price DECIMAL(12,2) DEFAULT 0,
            service_id INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'requested',
            admin_note TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activated_at DATETIME DEFAULT NULL,
            INDEX idx_domain_client (client_id),
            INDEX idx_domain_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    billing_ensure_domain_columns($conn);
}

function billing_ensure_domain_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'domain_requests'));
    if (DB_DRIVER === 'sqlite') {
        $columns = array(
            'holder_address' => "TEXT DEFAULT ''",
            'price' => "TEXT DEFAULT '0.00'",
            'service_id' => "INTEGER DEFAULT 0"
        );
    } else {
        $columns = array(
            'holder_address' => "VARCHAR(200) DEFAULT ''",
            'price' => "DECIMAL(12,2) DEFAULT 0",
            'service_id' => "INT DEFAULT 0"
        );
    }
    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE domain_requests ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_ensure_kyc_table($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_kyc (
            client_id INTEGER PRIMARY KEY,
            account_kind TEXT DEFAULT '',
            status TEXT DEFAULT '',
            purpose TEXT DEFAULT '',
            full_name TEXT DEFAULT '',
            id_kind TEXT DEFAULT '',
            id_number TEXT DEFAULT '',
            address TEXT DEFAULT '',
            org_name TEXT DEFAULT '',
            registration_number TEXT DEFAULT '',
            tax_number TEXT DEFAULT '',
            contact_name TEXT DEFAULT '',
            contact_id_kind TEXT DEFAULT '',
            contact_id_number TEXT DEFAULT '',
            doc_identity TEXT DEFAULT '',
            doc_registration TEXT DEFAULT '',
            doc_tax TEXT DEFAULT '',
            doc_authority TEXT DEFAULT '',
            doc_identity_back TEXT DEFAULT '',
            doc_clearance TEXT DEFAULT '',
            admin_note TEXT DEFAULT '',
            submitted_at TEXT DEFAULT NULL,
            reviewed_at TEXT DEFAULT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_kyc_columns($conn);
        return;
    }
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_kyc (
        client_id INT NOT NULL PRIMARY KEY,
        account_kind VARCHAR(20) DEFAULT '',
        status VARCHAR(20) DEFAULT '',
        purpose TEXT,
        full_name VARCHAR(160) DEFAULT '',
        id_kind VARCHAR(40) DEFAULT '',
        id_number VARCHAR(80) DEFAULT '',
        address TEXT,
        org_name VARCHAR(200) DEFAULT '',
        registration_number VARCHAR(80) DEFAULT '',
        tax_number VARCHAR(80) DEFAULT '',
        contact_name VARCHAR(160) DEFAULT '',
        contact_id_kind VARCHAR(40) DEFAULT '',
        contact_id_number VARCHAR(80) DEFAULT '',
        doc_identity VARCHAR(255) DEFAULT '',
        doc_registration VARCHAR(255) DEFAULT '',
        doc_tax VARCHAR(255) DEFAULT '',
        doc_authority VARCHAR(255) DEFAULT '',
        doc_identity_back VARCHAR(255) DEFAULT '',
        doc_clearance VARCHAR(255) DEFAULT '',
        admin_note TEXT,
        submitted_at DATETIME DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_kyc_columns($conn);
}

function billing_kyc_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'client_kyc'));
    if (!$present) {
        return;
    }
    $definition = DB_DRIVER === 'sqlite' ? "TEXT DEFAULT ''" : "VARCHAR(255) DEFAULT ''";
    foreach (array('doc_identity_back', 'doc_clearance') as $name) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE client_kyc ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_kyc_blank()
{
    return array(
        'client_id' => 0,
        'account_kind' => 'individual',
        'status' => '',
        'purpose' => '',
        'full_name' => '',
        'id_kind' => 'citizenship',
        'id_number' => '',
        'address' => '',
        'org_name' => '',
        'registration_number' => '',
        'tax_number' => '',
        'contact_name' => '',
        'contact_id_kind' => 'citizenship',
        'contact_id_number' => '',
        'doc_identity' => '',
        'doc_registration' => '',
        'doc_tax' => '',
        'doc_authority' => '',
        'doc_identity_back' => '',
        'doc_clearance' => '',
        'admin_note' => '',
        'submitted_at' => '',
        'reviewed_at' => ''
    );
}

function billing_kyc_load($conn, $clientId)
{
    $clientId = (int) $clientId;
    $blank = billing_kyc_blank();
    $blank['client_id'] = $clientId;
    $stmt = $conn->prepare('SELECT * FROM client_kyc WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return $blank;
    }
    foreach ($blank as $key => $value) {
        if (isset($row[$key]) && $row[$key] !== null) {
            $blank[$key] = $row[$key];
        }
    }
    if ($blank['account_kind'] !== 'organization') {
        $blank['account_kind'] = 'individual';
    }
    return $blank;
}

function billing_kyc_approved($conn, $clientId)
{
    $row = billing_kyc_load($conn, $clientId);
    return $row['status'] === 'approved';
}

function billing_kyc_id_kind($value)
{
    return $value === 'national_id' ? 'national_id' : 'citizenship';
}

function billing_kyc_id_label($value)
{
    return billing_kyc_id_kind($value) === 'national_id' ? 'National Identity Card' : 'Citizenship certificate';
}

function billing_kyc_reference($value, $max)
{
    $value = strtoupper(billing_plain_line($value, $max));
    $value = preg_replace('/[^A-Z0-9\/-]/', '', $value);
    return is_string($value) ? $value : '';
}

function billing_kyc_safe_path($clientId, $relative)
{
    $clientId = (int) $clientId;
    $relative = str_replace('\\', '/', (string) $relative);
    if (!preg_match('#^uploads/kyc/' . $clientId . '/[a-f0-9]{32}\.(pdf|jpg|png|webp)$#', $relative)) {
        return '';
    }
    $full = dirname(__DIR__) . '/' . $relative;
    return is_file($full) ? $full : '';
}

function billing_kyc_unlink($clientId, $relative)
{
    $full = billing_kyc_safe_path($clientId, $relative);
    if ($full !== '') {
        unlink($full);
    }
}

function billing_kyc_store_file($clientId, $slot, $file)
{
    $clientId = (int) $clientId;
    $slots = array('identity' => true, 'identity_back' => true, 'registration' => true, 'tax' => true, 'authority' => true, 'clearance' => true);
    if (!isset($slots[$slot])) {
        return array('ok' => false, 'error' => 'That document is not accepted.');
    }
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The document could not be uploaded.');
    }
    if ((int) $file['size'] > 5242880) {
        return array('ok' => false, 'error' => 'Each document must be smaller than 5 MB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $ext = '';
    if ($mime === 'application/pdf') {
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($head !== '%PDF-') {
            return array('ok' => false, 'error' => 'The PDF could not be read.');
        }
        $ext = 'pdf';
    } else {
        $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp');
        $image = @getimagesize($file['tmp_name']);
        $imageTypes = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg');
        if (defined('IMAGETYPE_WEBP')) {
            $imageTypes[IMAGETYPE_WEBP] = 'webp';
        }
        if (!isset($types[$mime]) || !$image || !isset($imageTypes[$image[2]]) || $types[$mime] !== $imageTypes[$image[2]]) {
            return array('ok' => false, 'error' => 'Use a PDF, JPG, PNG, or WEBP document.');
        }
        $ext = $imageTypes[$image[2]];
    }
    $dir = dirname(__DIR__) . '/uploads/kyc/' . $clientId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The document folder could not be created.');
    }
    $guard = $dir . '/.htaccess';
    if (!is_file($guard)) {
        file_put_contents($guard, "Require all denied\nDeny from all\n");
    }
    $relative = 'uploads/kyc/' . $clientId . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $relative)) {
        return array('ok' => false, 'error' => 'The document could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function billing_kyc_write($conn, $clientId, $status, $kind, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs, $note, $submitted)
{
    $clientId = (int) $clientId;
    $probe = $conn->prepare('SELECT client_id FROM client_kyc WHERE client_id = ?');
    if (!$probe) {
        return false;
    }
    $probe->bind_param('i', $clientId);
    $probe->execute();
    $exists = (bool) db_fetch_assoc($probe);
    $probe->close();
    if ($exists) {
        $stmt = $conn->prepare('UPDATE client_kyc SET account_kind = ?, status = ?, purpose = ?, full_name = ?, id_kind = ?, id_number = ?, address = ?, org_name = ?, registration_number = ?, tax_number = ?, contact_name = ?, contact_id_kind = ?, contact_id_number = ?, doc_identity = ?, doc_registration = ?, doc_tax = ?, doc_authority = ?, doc_identity_back = ?, doc_clearance = ?, admin_note = ?, submitted_at = NULLIF(?, \'\') WHERE client_id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sssssssssssssssssssssi', $kind, $status, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs['identity'], $docs['registration'], $docs['tax'], $docs['authority'], $docs['identity_back'], $docs['clearance'], $note, $submitted, $clientId);
    } else {
        $stmt = $conn->prepare('INSERT INTO client_kyc (client_id, account_kind, status, purpose, full_name, id_kind, id_number, address, org_name, registration_number, tax_number, contact_name, contact_id_kind, contact_id_number, doc_identity, doc_registration, doc_tax, doc_authority, doc_identity_back, doc_clearance, admin_note, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, \'\'))');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('isssssssssssssssssssss', $clientId, $kind, $status, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs['identity'], $docs['registration'], $docs['tax'], $docs['authority'], $docs['identity_back'], $docs['clearance'], $note, $submitted);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function billing_kyc_submit($conn, $clientId, $post, $files)
{
    $clientId = (int) $clientId;
    $current = billing_kyc_load($conn, $clientId);
    if ($current['status'] === 'approved') {
        return 'This identity is approved. It can no longer be changed from the client portal.';
    }
    $kind = isset($post['account_kind']) && $post['account_kind'] === 'organization' ? 'organization' : 'individual';
    $purpose = billing_plain_block(isset($post['purpose']) ? $post['purpose'] : '', 500);
    $address = billing_plain_block(isset($post['address']) ? $post['address'] : '', 300);
    if (strlen($purpose) < 12) {
        return 'Write what the SMS and voice calls are for.';
    }
    if (strlen($address) < 8) {
        return 'Enter the address.';
    }
    $fullName = '';
    $idKind = 'citizenship';
    $idNumber = '';
    $orgName = '';
    $registration = '';
    $tax = '';
    $contactName = '';
    $contactKind = 'citizenship';
    $contactNumber = '';
    $docs = array(
        'identity' => (string) $current['doc_identity'],
        'identity_back' => (string) $current['doc_identity_back'],
        'registration' => (string) $current['doc_registration'],
        'tax' => (string) $current['doc_tax'],
        'authority' => (string) $current['doc_authority'],
        'clearance' => (string) $current['doc_clearance']
    );
    if ($kind === 'individual') {
        $fullName = billing_plain_line(isset($post['full_name']) ? $post['full_name'] : '', 160);
        $idKind = 'national_id';
        $idNumber = billing_kyc_reference(isset($post['id_number']) ? $post['id_number'] : '', 40);
        if (strlen($fullName) < 3) {
            return 'Enter the name as it appears on the citizenship certificate.';
        }
        if (strlen($idNumber) < 5) {
            return 'Enter the National Identity Card number.';
        }
    } else {
        $orgName = billing_plain_line(isset($post['org_name']) ? $post['org_name'] : '', 200);
        $registration = billing_kyc_reference(isset($post['registration_number']) ? $post['registration_number'] : '', 40);
        $tax = billing_kyc_reference(isset($post['tax_number']) ? $post['tax_number'] : '', 40);
        if (strlen($orgName) < 2) {
            return 'Enter the company name.';
        }
        if (strlen($registration) < 3) {
            return 'Enter the company registration number.';
        }
        if (strlen($tax) < 3) {
            return 'Enter the PAN number.';
        }
    }
    $needed = $kind === 'individual' ? array('identity', 'identity_back') : array('registration', 'tax', 'clearance');
    $labels = array(
        'identity' => 'citizenship certificate, front',
        'identity_back' => 'citizenship certificate, back',
        'registration' => 'company registration certificate',
        'tax' => 'PAN certificate',
        'clearance' => 'latest tax clearance'
    );
    $stop = '';
    foreach ($needed as $slot) {
        $stored = billing_kyc_store_file($clientId, $slot, isset($files['doc_' . $slot]) ? $files['doc_' . $slot] : array());
        if (!$stored['ok']) {
            $stop = $stored['error'];
            break;
        }
        if ($stored['path']) {
            if ($docs[$slot] !== '' && $docs[$slot] !== $stored['path']) {
                billing_kyc_unlink($clientId, $docs[$slot]);
            }
            $docs[$slot] = $stored['path'];
        }
        if ($docs[$slot] === '' || billing_kyc_safe_path($clientId, $docs[$slot]) === '') {
            $stop = 'Upload the ' . $labels[$slot] . '.';
            break;
        }
    }
    if ($stop !== '') {
        billing_kyc_write($conn, $clientId, $current['status'], $kind, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs, (string) $current['admin_note'], (string) $current['submitted_at']);
        return $stop;
    }
    $drop = $kind === 'individual' ? array('registration', 'tax', 'authority', 'clearance') : array('identity', 'identity_back');
    foreach ($drop as $slot) {
        if ($docs[$slot] !== '') {
            billing_kyc_unlink($clientId, $docs[$slot]);
            $docs[$slot] = '';
        }
    }
    $status = 'pending';
    $note = '';
    $submitted = date('Y-m-d H:i:s');
    if (!billing_kyc_write($conn, $clientId, $status, $kind, $purpose, $fullName, $idKind, $idNumber, $address, $orgName, $registration, $tax, $contactName, $contactKind, $contactNumber, $docs, $note, $submitted)) {
        return 'The identity could not be saved. The details you typed are still in the form.';
    }
    $who = $kind === 'individual' ? $fullName : $orgName;
    billing_mail_client_event($conn, $clientId, 'kyc-received');
    billing_notify($conn, 'Identity waiting for approval', array(
        'A client submitted identity details.',
        'Account: ' . ($kind === 'individual' ? 'Individual' : 'Organization'),
        'Name: ' . $who,
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Open Admin → Identity.'
    ));
    return '';
}

function billing_kyc_decide($conn, $clientId, $decision, $note)
{
    $clientId = (int) $clientId;
    $current = billing_kyc_load($conn, $clientId);
    if ($current['status'] === '') {
        return 'That identity has not been submitted.';
    }
    $reviewed = date('Y-m-d H:i:s');
    if ($decision === 'approve') {
        if ($current['status'] === 'approved') {
            return '';
        }
        if ($current['status'] !== 'pending') {
            return 'Only a submitted identity can be approved.';
        }
        $status = 'approved';
        $note = '';
    } elseif ($decision === 'reject') {
        $note = billing_plain_block($note, 400);
        if (strlen($note) < 5) {
            return 'Write why it needs a change.';
        }
        $status = 'rejected';
    } else {
        return 'That decision is not available.';
    }
    $stmt = $conn->prepare('UPDATE client_kyc SET status = ?, admin_note = ?, reviewed_at = ? WHERE client_id = ?');
    $stmt->bind_param('sssi', $status, $note, $reviewed, $clientId);
    $stmt->execute();
    $stmt->close();
    if ($decision === 'approve') {
        billing_mail_client_event($conn, $clientId, 'kyc-approved');
    } else {
        billing_mail_client_event($conn, $clientId, 'kyc-change', array('note' => $note));
    }
    return '';
}

function billing_kyc_queue($conn, $find = '')
{
    $base = 'SELECT k.*, c.name AS account_name, c.email, c.phone FROM client_kyc k JOIN client_users c ON c.id = k.client_id ';
    $find = admin_find_text($find);
    if ($find !== '') {
        $like = '%' . $find . '%';
        $stmt = $conn->prepare($base . 'WHERE c.name LIKE ? OR c.email LIKE ? OR k.full_name LIKE ? OR k.org_name LIKE ? ORDER BY k.submitted_at DESC LIMIT 50');
        $stmt->bind_param('ssss', $like, $like, $like, $like);
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
        return $rows;
    }
    $rows = array();
    $seen = array();
    foreach (array(
        $base . "WHERE k.status = 'pending' ORDER BY k.submitted_at DESC",
        $base . 'ORDER BY k.submitted_at DESC LIMIT 80'
    ) as $sql) {
        $result = $conn->query($sql);
        if (!$result) {
            continue;
        }
        while ($row = $result->fetch_assoc()) {
            $id = (int) $row['client_id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $rows[] = $row;
        }
    }
    return $rows;
}

function billing_kyc_send($conn, $clientId, $slot)
{
    $map = array(
        'identity' => 'doc_identity',
        'identity_back' => 'doc_identity_back',
        'registration' => 'doc_registration',
        'tax' => 'doc_tax',
        'authority' => 'doc_authority',
        'clearance' => 'doc_clearance'
    );
    if (!isset($map[$slot])) {
        http_response_code(404);
        exit;
    }
    $row = billing_kyc_load($conn, (int) $clientId);
    $full = billing_kyc_safe_path((int) $clientId, isset($row[$map[$slot]]) ? $row[$map[$slot]] : '');
    if ($full === '') {
        http_response_code(404);
        exit;
    }
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $types = array('pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
    if (!isset($types[$ext])) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $types[$ext]);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Content-Disposition: ' . ($ext === 'pdf' ? 'attachment' : 'inline') . '; filename="identity-document.' . $ext . '"');
    header('Content-Length: ' . (string) filesize($full));
    readfile($full);
    exit;
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
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return (bool) $row;
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
    $mathError = auth_math_verify($key, isset($post['human_check']) ? $post['human_check'] : '');
    if ($mathError !== '') {
        return $mathError;
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
    auth_math_clear($key);
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
        $row = db_fetch_assoc($check);
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
    $insert = $conn->prepare('INSERT INTO services (title, description, icon, features, sort_order, slug, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    foreach (billing_catalog_rows() as $row) {
        $title = $row[0];
        $slug = $row[1];
        $description = $row[2];
        $icon = $row[3];
        $features = $row[4];
        $sort = (int) $row[5];
        $check = $conn->prepare('SELECT id FROM services WHERE slug = ?');
        $check->bind_param('s', $slug);
        $check->execute();
        $found = db_fetch_assoc($check);
        $check->close();
        if ($found) {
            continue;
        }
        $insert->bind_param('ssssis', $title, $description, $icon, $features, $sort, $slug);
        $insert->execute();
    }
    $insert->close();
    foreach (billing_catalog_rows() as $row) {
        if ($row[1] !== 'domain-registration') {
            continue;
        }
        $oldDescription = 'Register a .com or .com.np domain and renew it automatically.';
        $oldFeatures = '.com,.com.np,Auto-renew';
        $description = $row[2];
        $features = $row[4];
        $slug = $row[1];
        $update = $conn->prepare('UPDATE services SET description = ?, features = ? WHERE slug = ? AND description = ? AND features = ?');
        $update->bind_param('sssss', $description, $features, $slug, $oldDescription, $oldFeatures);
        $update->execute();
        $update->close();
    }
}

function billing_catalog_overrides($conn)
{
    $map = array();
    if (!$conn) {
        return $map;
    }
    try {
        $result = $conn->query('SELECT slug, title, description, features, is_active FROM services');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $map[(string) $row['slug']] = $row;
            }
        }
    } catch (Throwable $exception) {
        return array();
    }
    return $map;
}

function billing_service_view($slug, $override)
{
    $definitions = billing_service_definitions();
    if (!isset($definitions[$slug])) {
        return null;
    }
    $service = $definitions[$slug];
    if (!is_array($override)) {
        return $service;
    }
    $title = trim((string) (isset($override['title']) ? $override['title'] : ''));
    $description = trim((string) (isset($override['description']) ? $override['description'] : ''));
    $features = trim((string) (isset($override['features']) ? $override['features'] : ''));
    if ($title !== '') {
        $service['title'] = $title;
    }
    if ($description !== '') {
        $service['summary'] = $description;
    }
    if ($features !== '') {
        $tags = array();
        foreach (explode(',', $features) as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }
        if ($tags) {
            $service['tags'] = $tags;
        }
    }
    return $service;
}

function billing_save_public_service($conn, $slug, $title, $description, $features, $kicker = '', $lead = '', $points = '')
{
    $known = billing_service_definitions();
    if (!isset($known[$slug])) {
        return 'That service is not on the public site.';
    }
    $title = substr(trim((string) $title), 0, 120);
    $description = substr(trim((string) $description), 0, 500);
    $features = substr(trim((string) $features), 0, 300);
    $kicker = substr(trim((string) $kicker), 0, 160);
    $lead = substr(trim((string) $lead), 0, 600);
    $points = substr(trim((string) $points), 0, 2000);
    if ($title === '') {
        return 'The public title is required.';
    }
    $check = $conn->prepare('SELECT id FROM services WHERE slug = ?');
    $check->bind_param('s', $slug);
    $check->execute();
    $found = db_fetch_assoc($check);
    $check->close();
    if ($found) {
        $stmt = $conn->prepare('UPDATE services SET title = ?, description = ?, features = ?, is_active = 1 WHERE slug = ?');
        $stmt->bind_param('ssss', $title, $description, $features, $slug);
        $stmt->execute();
        $stmt->close();
        billing_set_setting($conn, 'service_text_' . $slug, '1');
        billing_set_setting($conn, 'service_kicker_' . $slug, $kicker);
        billing_set_setting($conn, 'service_lead_' . $slug, $lead);
        billing_set_setting($conn, 'service_points_' . $slug, $points);
        return '';
    }
    $icon = 'code';
    $sort = 0;
    foreach (billing_catalog_rows() as $row) {
        if ($row[1] === $slug) {
            $icon = $row[3];
            $sort = (int) $row[5];
            break;
        }
    }
    $stmt = $conn->prepare('INSERT INTO services (title, description, icon, features, sort_order, slug, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    $stmt->bind_param('ssssis', $title, $description, $icon, $features, $sort, $slug);
    $stmt->execute();
    $stmt->close();
    billing_set_setting($conn, 'service_text_' . $slug, '1');
    billing_set_setting($conn, 'service_kicker_' . $slug, $kicker);
    billing_set_setting($conn, 'service_lead_' . $slug, $lead);
    billing_set_setting($conn, 'service_points_' . $slug, $points);
    return '';
}

function billing_saved_service_view($conn, $slug, $overrides)
{
    $override = null;
    if ($conn && billing_setting($conn, 'service_text_' . $slug) === '1' && isset($overrides[$slug])) {
        $override = $overrides[$slug];
    }
    return billing_service_view($slug, $override);
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
        'offer_price' => isset($row['offer_price']) ? (float) $row['offer_price'] : 0,
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
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row ? billing_normalize_plan($row) : null;
}

function billing_update_price($conn, $code, $raw, $offerRaw = '')
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
    $offer = billing_parse_offer($offerRaw, $price);
    if (empty($offer['ok'])) {
        return false;
    }
    $offerPrice = $offer['price'];
    $stmt = $conn->prepare('UPDATE service_plans SET price = ?, offer_price = ? WHERE code = ?');
    $stmt->bind_param('sss', $price, $offerPrice, $code);
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

    $overrides = billing_catalog_overrides($conn);
    $cards = array();
    foreach (billing_service_definitions() as $slug => $service) {
        if (empty($grouped[$slug])) {
            continue;
        }
        $service = billing_saved_service_view($conn, $slug, $overrides);
        $servicePlans = $grouped[$slug];
        $slabs = ($slug === 'bulk-sms' || $slug === 'bulk-voice') ? billing_slabs_for($conn, $slug) : array();
        $lines = array();
        $amount = '';
        $label = 'Buy or book online';
        if ($slabs) {
            $label = 'Volume rate';
            $startSlab = billing_start_slab($slabs);
            $startOffer = isset($startSlab['offer_price']) ? $startSlab['offer_price'] : 0;
            $amount = 'From ' . billing_unit_label(billing_selling_price($startSlab['unit_price'], $startOffer)) . ' each';
            $was = billing_active_offer($startSlab['unit_price'], $startOffer) > 0 ? billing_unit_label($startSlab['unit_price']) . ' each' : '';
            foreach ($slabs as $slab) {
                $offer = isset($slab['offer_price']) ? $slab['offer_price'] : 0;
                $line = number_format($slab['min_qty']) . '–' . number_format($slab['max_qty']) . ' — ' . billing_unit_label(billing_selling_price($slab['unit_price'], $offer)) . ' each';
                if (billing_active_offer($slab['unit_price'], $offer) > 0) {
                    $line .= ' (was ' . billing_unit_label($slab['unit_price']) . ')';
                }
                $lines[] = $line;
            }
        } else {
            $lowest = null;
            $lowestWas = '';
            $lowestSuffix = '';
            foreach ($servicePlans as $plan) {
                if ((float) $plan['price'] <= 0) {
                    continue;
                }
                $offer = isset($plan['offer_price']) ? $plan['offer_price'] : 0;
                $selling = billing_selling_price($plan['price'], $offer);
                $suffix = billing_cycle_suffix($plan['billing_cycle']);
                if ($lowest === null || $selling < $lowest) {
                    $lowest = $selling;
                    $lowestSuffix = $suffix;
                    $lowestWas = billing_active_offer($plan['price'], $offer) > 0 ? billing_money_label($plan['price']) : '';
                }
                $line = $plan['name'] . ' — ' . billing_money_label($selling) . $suffix;
                if (billing_active_offer($plan['price'], $offer) > 0) {
                    $line .= ' (was ' . billing_money_label($plan['price']) . $suffix . ')';
                }
                $lines[] = $line;
            }
            $amount = ($lowest !== null && count($lines) > 1 ? 'From ' : '') . ($lowest === null ? '' : billing_money_label($lowest));
            if ($lowest !== null && count($lines) === 1) {
                $amount .= $lowestSuffix;
                if ($lowestWas !== '') {
                    $lowestWas .= $lowestSuffix;
                }
            }
            $was = $lowestWas;
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
                'was' => isset($was) ? $was : '',
                'details' => implode("\n", $lines)
            )
        );
    }
    return $cards;
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
    $parts = parse_url($value);
    if (!is_array($parts) || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || !isset($parts['path'])) {
        return 'index.php';
    }
    $page = $parts['path'];
    $pages = array('shop.php', 'checkout.php', 'wallet.php', 'services.php', 'index.php', 'campaigns.php', 'sms-portal.php', 'sms-logs.php', 'sms-api.php', 'support.php', 'profile.php', 'kyc.php', 'domains.php');
    if (!in_array($page, $pages, true)) {
        return 'index.php';
    }
    $query = array();
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $keep = array();
    if ($page === 'shop.php' && isset($query['service']) && preg_match('/^[A-Za-z0-9_-]+$/', (string) $query['service'])) {
        $keep['service'] = (string) $query['service'];
    }
    if ($page === 'checkout.php' && isset($query['plan']) && preg_match('/^[A-Za-z0-9_-]+$/', (string) $query['plan'])) {
        $keep['plan'] = (string) $query['plan'];
    }
    if ($page === 'wallet.php' && isset($query['amount']) && preg_match('/^[0-9]{1,7}$/', (string) $query['amount'])) {
        $keep['amount'] = (string) $query['amount'];
        if (isset($query['for']) && (string) $query['for'] === 'domain') {
            $keep['for'] = 'domain';
        }
    }
    if ($page === 'sms-portal.php') {
        foreach (array('send', 'retry', 'reuse') as $key) {
            if (isset($query[$key]) && preg_match('/^[0-9]{1,9}$/', (string) $query[$key])) {
                $keep[$key] = (string) $query[$key];
                break;
            }
        }
    }
    if ($page === 'sms-logs.php') {
        $statuses = array('sent', 'failed', 'queued', 'sending', 'scheduled');
        if (isset($query['status']) && in_array((string) $query['status'], $statuses, true)) {
            $keep['status'] = (string) $query['status'];
        }
        if (isset($query['q']) && preg_match('/^[0-9]{1,10}$/', (string) $query['q'])) {
            $keep['q'] = (string) $query['q'];
        }
        if (isset($query['send']) && preg_match('/^[0-9]{1,9}$/', (string) $query['send'])) {
            $keep['send'] = (string) $query['send'];
        }
        if (isset($query['page']) && preg_match('/^[0-9]{1,2}$/', (string) $query['page'])) {
            $keep['page'] = (string) $query['page'];
        }
    }
    if (!$keep) {
        return $page;
    }
    return $page . '?' . http_build_query($keep);
}

function billing_vat_bill($amount)
{
    $net = round((float) $amount, 2);
    $vat = round($net * 0.13, 2);
    return array(
        'net' => $net,
        'vat' => $vat,
        'total' => round($net + $vat, 2)
    );
}

function billing_payment_methods($conn = null)
{
    $esewa = (defined('ESEWA_ID') && ESEWA_ID !== '') ? ESEWA_ID : '';
    $khalti = (defined('KHALTI_ID') && KHALTI_ID !== '') ? KHALTI_ID : '';
    $bank = (defined('BANK_DETAILS') && BANK_DETAILS !== '') ? BANK_DETAILS : '';
    if ($conn) {
        $settings = site_public_settings($conn);
        if (isset($settings['esewa_id']) && trim((string) $settings['esewa_id']) !== '') {
            $esewa = trim((string) $settings['esewa_id']);
        }
        if (isset($settings['khalti_id']) && trim((string) $settings['khalti_id']) !== '') {
            $khalti = trim((string) $settings['khalti_id']);
        }
        if (isset($settings['bank_details']) && trim((string) $settings['bank_details']) !== '') {
            $bank = trim((string) $settings['bank_details']);
        }
    }
    $methods = array();
    if ($esewa !== '') {
        $methods[] = array(
            'code' => 'esewa',
            'group' => 'online',
            'label' => 'eSewa',
            'detail' => $esewa,
            'instruction' => 'Pay with eSewa to ' . $esewa . ', then enter the transaction code.'
        );
    }
    if ($khalti !== '') {
        $methods[] = array(
            'code' => 'khalti',
            'group' => 'online',
            'label' => 'Khalti',
            'detail' => $khalti,
            'instruction' => 'Pay with Khalti to ' . $khalti . ', then enter the transaction code.'
        );
    }
    if ($bank !== '') {
        $methods[] = array(
            'code' => 'bank',
            'group' => 'manual',
            'label' => 'Bank transfer',
            'detail' => $bank,
            'instruction' => 'Transfer to this account, then enter the voucher or reference number. ' . $bank
        );
    }
    return $methods;
}

function billing_ensure_wallet($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT client_id FROM client_wallets WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
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
    $row = db_fetch_assoc($stmt);
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
    $row = db_fetch_assoc($stmt);
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

function billing_take_units($conn, $clientId, $kind, $quantity)
{
    if (($kind !== 'sms' && $kind !== 'voice_minutes' && $kind !== 'voice_calls') || (int) $quantity < 1) {
        return false;
    }
    $clientId = (int) $clientId;
    $quantity = (int) $quantity;
    $stmt = $conn->prepare('UPDATE client_units SET balance = balance - ? WHERE client_id = ? AND unit_kind = ? AND balance >= ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iisi', $quantity, $clientId, $kind, $quantity);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function billing_unit_balances($conn, $clientId)
{
    $balances = array('sms' => 0, 'voice_minutes' => 0, 'voice_calls' => 0);
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT unit_kind, balance FROM client_units WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    foreach (db_fetch_all($stmt) as $row) {
        $balances[(string) $row['unit_kind']] = (int) $row['balance'];
    }
    $stmt->close();
    return $balances;
}

function billing_valid_domain($value)
{
    return (bool) preg_match('/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i', $value);
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
    $slabs = array();
    foreach (db_fetch_all($stmt) as $row) {
        $slabs[] = array(
            'id' => (int) $row['id'],
            'service_slug' => (string) $row['service_slug'],
            'min_qty' => (int) $row['min_qty'],
            'max_qty' => (int) $row['max_qty'],
            'unit_price' => (float) $row['unit_price'],
            'offer_price' => isset($row['offer_price']) ? (float) $row['offer_price'] : 0,
            'sort_order' => (int) $row['sort_order'],
            'is_start' => !empty($row['is_start']) ? 1 : 0
        );
    }
    $stmt->close();
    return $slabs;
}

function billing_start_slab($slabs)
{
    foreach ($slabs as $slab) {
        if (!empty($slab['is_start'])) {
            return $slab;
        }
    }
    return $slabs[0];
}

function billing_ensure_slab_start($conn)
{
    $present = array();
    if (DB_DRIVER === 'sqlite') {
        $result = billing_exec($conn, 'PRAGMA table_info(rate_slabs)');
        while ($row = $result->fetch_assoc()) {
            $present[(string) $row['name']] = true;
        }
    } else {
        $result = billing_exec($conn, 'SHOW COLUMNS FROM rate_slabs');
        while ($row = $result->fetch_assoc()) {
            $present[(string) $row['Field']] = true;
        }
    }
    if (!isset($present['is_start'])) {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN is_start INTEGER DEFAULT 0');
        } else {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN is_start TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
    foreach (array('bulk-sms', 'bulk-voice') as $slug) {
        $slabs = billing_load_slabs($conn, $slug);
        $chosen = 0;
        foreach ($slabs as $slab) {
            if (!empty($slab['is_start'])) {
                $chosen = (int) $slab['id'];
                break;
            }
        }
        if ($chosen !== 0 || !$slabs) {
            continue;
        }
        $id = (int) $slabs[0]['id'];
        $stmt = $conn->prepare('UPDATE rate_slabs SET is_start = 1 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}

function billing_column_names($conn, $table)
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return array();
    }
    $names = array();
    if (DB_DRIVER === 'sqlite') {
        $result = billing_exec($conn, 'PRAGMA table_info(' . $table . ')');
        while ($row = $result->fetch_assoc()) {
            $names[] = (string) $row['name'];
        }
        return $names;
    }
    $result = billing_exec($conn, 'SHOW COLUMNS FROM ' . $table);
    while ($row = $result->fetch_assoc()) {
        $names[] = (string) $row['Field'];
    }
    return $names;
}

function billing_ensure_offer_prices($conn)
{
    if (!in_array('offer_price', billing_column_names($conn, 'rate_slabs'), true)) {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN offer_price NUMERIC DEFAULT 0');
        } else {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN offer_price DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
    }
    if (!in_array('offer_price', billing_column_names($conn, 'service_plans'), true)) {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'ALTER TABLE service_plans ADD COLUMN offer_price NUMERIC DEFAULT 0');
        } else {
            billing_exec($conn, 'ALTER TABLE service_plans ADD COLUMN offer_price DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
    }
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
        $digits = function_exists('auth_mobile_number') ? auth_mobile_number($line) : '';
        if ($digits === '') {
            return array('ok' => false, 'error' => 'Each line should be one 10-digit mobile number.', 'numbers' => array());
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

function billing_save_slabs($conn, $posted, $starts = array())
{
    if (!is_array($posted)) {
        return 'No rates were submitted.';
    }
    if (!is_array($starts)) {
        $starts = array();
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
            $offerRaw = isset($row['offer']) ? $row['offer'] : '';
            if ($min < 1 || $max < $min || !preg_match('/^\d{1,5}(\.\d{1,2})?$/', $raw) || (float) $raw <= 0) {
                return 'Each band needs a minimum, a higher maximum, and a rate above zero.';
            }
            $offer = billing_parse_offer($offerRaw, $raw);
            if (empty($offer['ok'])) {
                return 'An offer rate must be lower than the regular rate. Leave it blank when there is no offer.';
            }
            $grouped[$slug][] = array('id' => (int) $slab['id'], 'min' => $min, 'max' => $max, 'price' => billing_money($raw), 'offer' => $offer['price']);
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
    $stmt = $conn->prepare('UPDATE rate_slabs SET min_qty = ?, max_qty = ?, unit_price = ?, offer_price = ? WHERE id = ?');
    foreach ($grouped as $rows) {
        foreach ($rows as $row) {
            $min = $row['min'];
            $max = $row['max'];
            $price = $row['price'];
            $offer = $row['offer'];
            $id = $row['id'];
            $stmt->bind_param('iissi', $min, $max, $price, $offer, $id);
            $stmt->execute();
        }
    }
    $stmt->close();
    $clear = $conn->prepare('UPDATE rate_slabs SET is_start = 0 WHERE service_slug = ?');
    $mark = $conn->prepare('UPDATE rate_slabs SET is_start = 1 WHERE id = ? AND service_slug = ?');
    foreach ($grouped as $slug => $rows) {
        $startId = isset($starts[$slug]) ? (int) $starts[$slug] : 0;
        $allowed = false;
        foreach ($rows as $row) {
            if ((int) $row['id'] === $startId) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $startId = (int) $rows[0]['id'];
        }
        $clear->bind_param('s', $slug);
        $clear->execute();
        $mark->bind_param('is', $startId, $slug);
        $mark->execute();
    }
    $clear->close();
    $mark->close();
    return '';
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
    if ($unitKind === 'sms' && $unitQuantity > 0 && function_exists('sms_remember_credit')) {
        sms_remember_credit($conn, $clientId, $unitQuantity, 'Bought from the wallet');
    }
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

function billing_request_topup($conn, $clientId, $amount, $method, $reference)
{
    $allowed = array();
    foreach (billing_payment_methods($conn) as $methodRow) {
        $allowed[] = $methodRow['code'];
    }
    $amount = (int) $amount;
    $reference = trim((string) $reference);
    if (!in_array($method, $allowed, true)) {
        return 'Choose one of the payment methods saved for this site.';
    }
    if ($amount < 100 || $amount > 1000000) {
        return 'Enter an amount between NPR 100 and NPR 1,000,000.';
    }
    if (strlen($reference) < 4 || strlen($reference) > 80) {
        return 'Enter the payment reference (4–80 characters).';
    }
    billing_record_entry($conn, (int) $clientId, $amount, 'credit', 'topup', 'pending', $method, $reference, 0);
    billing_notify($conn, 'Wallet top-up waiting', array(
        'A wallet top-up is waiting for confirmation.',
        'Amount: NPR ' . number_format($amount),
        'Method: ' . $method,
        'Reference: ' . billing_notify_clip($reference, 80),
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Open Admin → Billing and confirm it. Later renewals do not need this step.'
    ));
    billing_mail_client_event($conn, (int) $clientId, 'topup-waiting', array('amount' => number_format($amount)));
    return '';
}

function billing_approve_topup($conn, $entryId)
{
    $entryId = (int) $entryId;
    $stmt = $conn->prepare("SELECT id, client_id, amount FROM wallet_entries WHERE id = ? AND kind = 'topup' AND status = 'pending'");
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $entry = db_fetch_assoc($stmt);
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
    billing_mail_client_event($conn, (int) $entry['client_id'], 'topup-done', array(
        'amount' => number_format((float) $entry['amount'])
    ));
    return true;
}

function billing_admin_wallet_credit($conn, $clientId, $amount, $note)
{
    $clientId = (int) $clientId;
    $amount = (int) $amount;
    $note = billing_plain_line($note, 160);
    if ($clientId < 1) {
        return 'Choose a client.';
    }
    if ($amount < 1 || $amount > 1000000) {
        return 'Enter an amount from NPR 1 to NPR 1,000,000.';
    }
    if ($note === '') {
        return 'Write where this payment was received, such as cash at the office.';
    }
    $check = $conn->prepare('SELECT id FROM client_users WHERE id = ?');
    $check->bind_param('i', $clientId);
    $check->execute();
    $client = db_fetch_assoc($check);
    $check->close();
    if (!$client) {
        return 'That client was not found.';
    }
    $method = 'office';
    billing_record_entry($conn, $clientId, $amount, 'credit', 'topup', 'completed', $method, $note, 0);
    billing_wallet_credit($conn, $clientId, $amount);
    billing_mail_client_event($conn, $clientId, 'office-wallet', array(
        'amount' => number_format($amount),
        'note' => $note
    ));
    return '';
}

function billing_admin_wallet_reverse($conn, $clientId, $entryId)
{
    $clientId = (int) $clientId;
    $entryId = (int) $entryId;
    if ($clientId < 1 || $entryId < 1) {
        return 'That payment was not found.';
    }
    $stmt = $conn->prepare('SELECT id, amount, direction, kind, status, method FROM wallet_entries WHERE id = ? AND client_id = ?');
    if (!$stmt) {
        return 'That payment could not be read.';
    }
    $stmt->bind_param('ii', $entryId, $clientId);
    $stmt->execute();
    $entry = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$entry || $entry['kind'] !== 'topup' || $entry['method'] !== 'office' || $entry['direction'] !== 'credit' || $entry['status'] !== 'completed') {
        return 'Only an office payment that is still in the wallet can be taken back.';
    }
    $amount = billing_money($entry['amount']);
    if ((float) $amount <= 0) {
        return 'That payment has no amount to take back.';
    }
    $reversed = 'reversed';
    $completed = 'completed';
    $mark = $conn->prepare('UPDATE wallet_entries SET status = ? WHERE id = ? AND client_id = ? AND status = ?');
    if (!$mark) {
        return 'That payment could not be taken back.';
    }
    $mark->bind_param('siis', $reversed, $entryId, $clientId, $completed);
    $mark->execute();
    $marked = billing_affected($conn) === 1;
    $mark->close();
    if (!$marked) {
        return 'That payment was already taken back.';
    }
    if (!billing_wallet_debit($conn, $clientId, $amount)) {
        $restore = $conn->prepare('UPDATE wallet_entries SET status = ? WHERE id = ? AND client_id = ?');
        if ($restore) {
            $restore->bind_param('sii', $completed, $entryId, $clientId);
            $restore->execute();
            $restore->close();
        }
        $left = billing_balance($conn, $clientId);
        return 'The wallet has NPR ' . number_format((float) $left, 2) . ' left. This payment is NPR ' . number_format((float) $amount, 2) . ', and some of it was already spent, so it cannot be taken back.';
    }
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

function billing_reject_topup($conn, $entryId)
{
    $entryId = (int) $entryId;
    $stmt = $conn->prepare("SELECT client_id, amount FROM wallet_entries WHERE id = ? AND kind = 'topup' AND status = 'pending'");
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $entry = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$entry) {
        return false;
    }
    $update = $conn->prepare("UPDATE wallet_entries SET status = 'rejected' WHERE id = ? AND status = 'pending'");
    $update->bind_param('i', $entryId);
    $update->execute();
    $changed = billing_affected($conn) === 1;
    $update->close();
    if ($changed) {
        billing_mail_client_event($conn, (int) $entry['client_id'], 'topup-rejected', array(
            'amount' => number_format((float) $entry['amount'])
        ));
    }
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
            $renewKind = (string) ($row['unit_kind'] ?? '');
            $renewQty = (int) ($row['unit_quantity'] ?? 0);
            billing_add_units($conn, $ownerId, $renewKind, $renewQty);
            if ($renewKind === 'sms' && $renewQty > 0 && function_exists('sms_remember_credit')) {
                sms_remember_credit($conn, $ownerId, $renewQty, 'Renewed from the wallet');
            }
            billing_record_entry($conn, $ownerId, $amount, 'debit', 'renewal', 'completed', 'wallet', (string) $row['service_name'], $serviceId);
            billing_log_renewal($conn, $serviceId, $ownerId, $amount, 'renewed', 'Renewed through ' . $next);
            billing_mail_client_event($conn, $ownerId, 'renewed', array(
                'service' => (string) $row['service_name'],
                'amount' => billing_money($amount),
                'next' => $next
            ));
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

require_once __DIR__ . '/public-ai.php';
