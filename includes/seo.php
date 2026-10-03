<?php

function site_request_origin()
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower(trim((string) $_SERVER['HTTP_HOST'])) : '';
    if (!preg_match('/^[a-z0-9.-]+(?::\d+)?$/', $host)) {
        return 'http://aakashtechnologies.com.np';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
}

function site_absolute_url($path)
{
    $path = (string) $path;
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return rtrim(site_request_origin(), '/') . '/' . ltrim($path, '/');
}

function site_seo_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function site_seo_phrases()
{
    return array(
        'bulk-sms' => array(
            'title' => 'Bulk SMS provider in Nepal',
            'description' => 'Bulk SMS provider in Nepal for cooperatives, companies, parties, and personal notices. The volume rate is on this page. Buy the credits, then send from your dashboard or API.'
        ),
        'bulk-voice' => array(
            'title' => 'Bulk voice calls in Nepal',
            'description' => 'Bulk voice calls in Nepal for an AGM, program, event, election, or festival. Choose Nepali or English. The volume rate is on this page.'
        ),
        'domain-registration' => array(
            'title' => 'Domain registration in Nepal',
            'description' => 'Register a .com name, or a Nepal name such as .com.np or .coop.np. The yearly price is on this page and can renew from your wallet. Hosting and email are separate.'
        ),
        'hosting-server' => array(
            'title' => 'Hosting provider in Nepal',
            'description' => 'Hosting provider in Nepal for one website with SSL and routine care, or monthly server management. The price is on this page. Domain and design are separate.'
        ),
        'professional-email' => array(
            'title' => 'Professional email on your domain in Nepal',
            'description' => 'Professional email on your own domain in Nepal, for a cooperative or organization. Choose 1, 5, or 10 mailboxes. The yearly price is on this page.'
        ),
        'custom-websites' => array(
            'title' => 'Custom websites in Nepal',
            'description' => 'Custom websites in Nepal for a company, cooperative, school, hotel, restaurant, or news portal. The package price and the brief are on this page.'
        ),
        'cyber-security' => array(
            'title' => 'Cyber security training in Nepal',
            'description' => 'On-site cyber security training in Nepal for cooperative directors, staff, and members. The trainer comes to your address. Book the session on this page.'
        )
    );
}

function site_seo_faqs($slug)
{
    $faqs = array(
        'bulk-sms' => array(
            array('Who is this bulk SMS service for?', 'Cooperatives, companies, parties, schools, and personal use in Nepal. Typical notices are an AGM, program, event, election, festival, a school notice, or a general notice.'),
            array('Which numbers can receive it?', '10-digit Nepal mobile numbers, including Nepal Telecom and Ncell. The quantity you buy is the number of credits.'),
            array('How is the bulk SMS rate calculated?', 'The row that contains your quantity is the price per SMS. A smaller quantity costs more per message. A larger quantity costs less.'),
            array('Where are the messages sent?', 'This website adds the credits after payment. After identity is approved, you send from the SMS dashboard in the client account, or with an API token from your own website.')
        ),
        'bulk-voice' => array(
            array('Can I send bulk voice calls in Nepal?', 'Yes. You buy a quantity of recorded calls, in Nepali or English, for an AGM, program, event, election, festival, a school notice, or a general notice. The numbers are 10-digit Nepal mobiles, including Nepal Telecom and Ncell.'),
            array('How is the voice-call rate calculated?', 'The row that contains your quantity is the price per call. A quantity outside the table cannot be ordered.')
        ),
        'domain-registration' => array(
            array('Which domains can I register?', '.com, and Nepal endings such as .com.np and .coop.np. The Domain registration page checks the name, then you request it and pay the yearly bill from the wallet.'),
            array('Does the site check if the name is free?', 'Yes. The name is checked before you request it. WHOIS check up shows the public record for a Nepal name or a .com name. The team registers a paid request, then marks it active. If the name cannot be registered, the amount returns to the wallet.')
        ),
        'hosting-server' => array(
            array('What does this hosting provider include?', 'Yearly hosting is one website, SSL, and routine care. Monthly managed server is for a site or mail server that needs a person watching it. After it is active, cPanel opens from the client account.'),
            array('Does hosting include a new website design?', 'No. Hosting keeps a site online. A custom website is a separate booking, and the domain is registered separately.')
        ),
        'professional-email' => array(
            array('Can I get email on my own domain?', 'Yes. You choose 1, 5, or 10 names, such as info@yourdomain. The domain must already be yours. The team creates the mailboxes and the domain records that let mail arrive there. Open email appears in the client account after that.'),
            array('Is old mail moved across?', 'No. This package creates the new mailboxes. Mail already sitting in Gmail, Yahoo, or another inbox stays where it is.')
        ),
        'custom-websites' => array(
            array('What kinds of websites can I book?', 'Company, personal portfolio, bank or cooperative, restaurant, school, hotel, and news portal. Each package names the pages included, and the layout works on a phone and a computer.'),
            array('What is not included?', 'Domain, hosting, and email are separate. A member login, an eSewa or Khalti checkout, and a live booking calendar are not in these packages. The hotel package is a booking enquiry. The website is paid once.')
        ),
        'cyber-security' => array(
            array('Where does the cyber training happen?', 'At your address. The trainer comes for one visit. The date you pick is the date you are asking for.'),
            array('Who can attend?', 'Directors up to 25, staff up to 40, members up to 100, or one field day for those groups together, up to 120 people.')
        )
    );
    return isset($faqs[$slug]) ? $faqs[$slug] : array();
}

function site_seo_business($publicSite, $sameAs)
{
    $name = trim((string) $publicSite['site_name']);
    $business = array(
        '@type' => 'ProfessionalService',
        '@id' => site_absolute_url('/') . '#business',
        'name' => $name,
        'url' => site_absolute_url('/'),
        'description' => 'Bulk SMS provider, bulk voice calls, and hosting provider in Nepal, with domains, domain email, custom websites, and on-site cyber training.',
        'areaServed' => array('@type' => 'Country', 'name' => 'Nepal'),
        'address' => array(
            '@type' => 'PostalAddress',
            'addressLocality' => trim((string) $publicSite['site_location']) !== '' ? trim((string) $publicSite['site_location']) : 'Kathmandu, Nepal',
            'addressCountry' => 'NP'
        )
    );
    $email = trim((string) $publicSite['site_email']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $business['email'] = $email;
    }
    $logo = trim((string) $publicSite['logo_path']);
    if ($logo !== '' && strpos($logo, '..') === false) {
        $business['image'] = site_absolute_url($logo);
    }
    if ($sameAs) {
        $business['sameAs'] = array_values($sameAs);
    }
    return $business;
}

function site_seo_print($title, $description, $path, $graph, $imagePath)
{
    $canonical = site_absolute_url($path);
    $title = trim((string) $title);
    $description = trim((string) $description);
    echo '<title>' . site_seo_escape($title) . '</title>' . "\n";
    echo '    <meta name="description" content="' . site_seo_escape($description) . '">' . "\n";
    echo '    <link rel="canonical" href="' . site_seo_escape($canonical) . '">' . "\n";
    echo '    <meta name="robots" content="index, follow">' . "\n";
    echo '    <meta property="og:title" content="' . site_seo_escape($title) . '">' . "\n";
    echo '    <meta property="og:description" content="' . site_seo_escape($description) . '">' . "\n";
    echo '    <meta property="og:type" content="website">' . "\n";
    echo '    <meta property="og:url" content="' . site_seo_escape($canonical) . '">' . "\n";
    echo '    <meta property="og:locale" content="en_NP">' . "\n";
    $imagePath = trim((string) $imagePath);
    if ($imagePath !== '' && strpos($imagePath, '..') === false) {
        echo '    <meta property="og:image" content="' . site_seo_escape(site_absolute_url($imagePath)) . '">' . "\n";
    }
    if ($graph) {
        $json = json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (is_string($json)) {
            echo '    <script type="application/ld+json">' . $json . '</script>' . "\n";
        }
    }
}

function site_seo_home_graph($publicSite, $sameAs)
{
    $services = function_exists('billing_service_definitions') ? billing_service_definitions() : array();
    $phrases = site_seo_phrases();
    $items = array();
    $position = 1;
    foreach ($services as $slug => $service) {
        $phrase = isset($phrases[$slug]['title']) ? $phrases[$slug]['title'] : $service['title'];
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $phrase,
            'url' => site_absolute_url('service.php?slug=' . rawurlencode($slug))
        );
        $position++;
    }
    return array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            site_seo_business($publicSite, $sameAs),
            array(
                '@type' => 'WebSite',
                '@id' => site_absolute_url('/') . '#website',
                'url' => site_absolute_url('/'),
                'name' => trim((string) $publicSite['site_name']),
                'publisher' => array('@id' => site_absolute_url('/') . '#business'),
                'inLanguage' => 'en'
            ),
            array(
                '@type' => 'ItemList',
                'name' => 'Services',
                'itemListElement' => $items
            )
        )
    );
}

function site_seo_service_graph($publicSite, $sameAs, $slug, $description, $price, $unitText)
{
    $phrases = site_seo_phrases();
    $name = isset($phrases[$slug]['title']) ? $phrases[$slug]['title'] : $slug;
    $url = site_absolute_url('service.php?slug=' . rawurlencode($slug));
    $service = array(
        '@type' => 'Service',
        'name' => $name,
        'serviceType' => $name,
        'description' => $description,
        'url' => $url,
        'areaServed' => array('@type' => 'Country', 'name' => 'Nepal'),
        'provider' => array('@id' => site_absolute_url('/') . '#business')
    );
    if ($price !== null && (float) $price > 0) {
        $offer = array(
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => 'NPR',
            'price' => number_format((float) $price, 2, '.', ''),
            'availability' => 'https://schema.org/InStock'
        );
        if ($unitText !== '') {
            $offer['priceSpecification'] = array(
                '@type' => 'UnitPriceSpecification',
                'price' => number_format((float) $price, 2, '.', ''),
                'priceCurrency' => 'NPR',
                'unitText' => $unitText
            );
        }
        $service['offers'] = $offer;
    }
    $crumbs = array(
        array('@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => site_absolute_url('/')),
        array('@type' => 'ListItem', 'position' => 2, 'name' => $name, 'item' => $url)
    );
    $graph = array(
        site_seo_business($publicSite, $sameAs),
        $service,
        array('@type' => 'BreadcrumbList', 'itemListElement' => $crumbs)
    );
    $faqs = site_seo_faqs($slug);
    if ($faqs) {
        $questions = array();
        foreach ($faqs as $faq) {
            $questions[] = array(
                '@type' => 'Question',
                'name' => $faq[0],
                'acceptedAnswer' => array('@type' => 'Answer', 'text' => $faq[1])
            );
        }
        $graph[] = array(
            '@type' => 'FAQPage',
            'mainEntity' => $questions
        );
    }
    return array('@context' => 'https://schema.org', '@graph' => $graph);
}
