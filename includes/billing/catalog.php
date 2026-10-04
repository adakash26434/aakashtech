<?php
/**
 * Billing: Services, plans, rate slabs, offers and public pricing pages.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

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
