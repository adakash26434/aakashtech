<?php
/**
 * Admin services: handles the form posts (plans, prices, slabs, public page text) and prepares the values the page shows.
 * Included by admin/services.php, so it shares that page's variables ($conn, $msg, $err ...).
 */
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_public_service'])) {
    verify_csrf();
    $slug = isset($_POST['service_slug']) ? (string) $_POST['service_slug'] : '';
    $saveError = billing_save_public_service(
        $conn,
        $slug,
        isset($_POST['title']) ? $_POST['title'] : '',
        isset($_POST['description']) ? $_POST['description'] : '',
        isset($_POST['features']) ? $_POST['features'] : '',
        isset($_POST['kicker']) ? $_POST['kicker'] : '',
        isset($_POST['lead']) ? $_POST['lead'] : '',
        isset($_POST['points']) ? $_POST['points'] : ''
    );
    if ($saveError !== '') {
        $err = $saveError;
    } else {
        $msg = 'Public service text saved.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_poster'])) {
    verify_csrf();
    $slug = isset($_POST['poster_slug']) ? (string) $_POST['poster_slug'] : '';
    $known = billing_service_definitions();
    if (!isset($known[$slug])) {
        $err = 'That service is not on the public site.';
    } else {
        $poster = site_store_poster(isset($_FILES['poster']) ? $_FILES['poster'] : array(), $slug);
        if (!$poster['ok']) {
            $err = $poster['error'];
        } else {
            if (!empty($_POST['remove_poster']) && $poster['path'] === null) {
                $dir = dirname(__DIR__, 2) . '/uploads';
                foreach (glob($dir . '/service-poster-' . $slug . '.*') as $old) {
                    if (is_file($old)) {
                        unlink($old);
                    }
                }
                billing_set_setting($conn, 'service_poster_' . $slug, '');
            } elseif ($poster['path'] !== null) {
                billing_set_setting($conn, 'service_poster_' . $slug, $poster['path']);
            }
            $msg = 'Service photo saved.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service_slabs']) && isset($_POST['slab']) && is_array($_POST['slab'])) {
    verify_csrf();
    $slabError = billing_save_slabs($conn, $_POST['slab'], isset($_POST['start']) ? $_POST['start'] : array());
    if ($slabError === '') {
        $msg = 'SMS and voice rates saved. The public site and the client shop use them now. Checkout still adds 13% VAT.';
    } else {
        $err = $slabError;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service_prices']) && isset($_POST['price']) && is_array($_POST['price'])) {
    verify_csrf();
    $saved = 0;
    $failed = 0;
    foreach ($_POST['price'] as $code => $value) {
        $offer = isset($_POST['offer'][$code]) ? (string) $_POST['offer'][$code] : '';
        if (billing_update_price($conn, (string) $code, (string) $value, $offer)) {
            $saved++;
        } else {
            $failed++;
        }
    }
    if ($saved > 0 && $failed === 0) {
        $msg = 'Service prices saved. The public site and the client shop use them now. Checkout still adds 13% VAT. A renewal keeps the price from the day it was bought.';
    } else {
        $err = 'Each offer must be lower than its regular price, or left blank. Regular prices must stay above zero.';
    }
}

$posterServices = billing_service_definitions();
$serviceOverrides = billing_catalog_overrides($conn);
$plans = billing_load_plans($conn);
$plansByService = array();
foreach ($plans as $plan) {
    if ((int) $plan['is_active'] !== 1) {
        continue;
    }
    $plansByService[(string) $plan['service_slug']][] = $plan;
}
$smsSlabs = billing_load_slabs($conn, 'bulk-sms');
$voiceSlabs = billing_load_slabs($conn, 'bulk-voice');
$serviceTab = 'prices';
if (isset($_POST['save_poster'])) {
    $serviceTab = 'photos';
} elseif (isset($_POST['save_public_service'])) {
    $serviceTab = 'words';
}
