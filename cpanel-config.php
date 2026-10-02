<?php
/**
 * cPanel को public_html भित्र यो फाइल राख्नुहोस् र File Manager बाट खोलेर भर्नुहोस्।
 * Browser ले यो फाइल खोल्दैन। Database र admin login यहीँ लेखिन्छ।
 *
 * cPanel → MySQL Databases मा database र user बनाउनुहोस्, user लाई database मा All Privileges दिनुहोस्,
 * अनि phpMyAdmin बाट database.sql एक पटक import गर्नुहोस्।
 */
return array(
    'db_host' => 'localhost',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',

    'admin_email' => 'admin@aakashtechnologies.com',
    'admin_password' => '',

    'site_email' => 'info@aakashtechnologies.com.np',
    'site_phone' => '',
    'site_location' => 'Kathmandu, Nepal',

    'esewa_id' => '',
    'khalti_id' => '',
    'bank_details' => '',
    'cron_key' => ''
);
