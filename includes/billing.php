<?php
/**
 * Billing entry point. The code now lives in includes/billing/*.php, grouped by job.
 * Every other file still just requires this one.
 */

require_once __DIR__ . '/billing/core.php';
require_once __DIR__ . '/billing/schema.php';
require_once __DIR__ . '/billing/catalog.php';
require_once __DIR__ . '/billing/wallet.php';
require_once __DIR__ . '/billing/kyc.php';
require_once __DIR__ . '/billing/kyc-fields.php';
require_once __DIR__ . '/billing/mail.php';
require_once __DIR__ . '/billing/site.php';
require_once __DIR__ . '/billing/orders.php';

require_once __DIR__ . '/public-ai.php';
