<?php
/**
 * SMS entry point. The code lives in includes/sms/*.php, grouped by job.
 * Other files still just require this one.
 */

require_once __DIR__ . '/sms/core.php';
require_once __DIR__ . '/sms/accounts.php';
require_once __DIR__ . '/sms/contacts.php';
require_once __DIR__ . '/sms/vendor.php';
require_once __DIR__ . '/sms/delivery.php';
require_once __DIR__ . '/sms/logs.php';
require_once __DIR__ . '/sms/report.php';
require_once __DIR__ . '/sms/stats.php';
require_once __DIR__ . '/sms/api.php';
require_once __DIR__ . '/sms/voice.php';
