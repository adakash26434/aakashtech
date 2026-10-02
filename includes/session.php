<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');

function aakash_start_session()
{
    if (session_status() !== PHP_SESSION_NONE) {
        aakash_security_headers();
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ));
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
    }
    session_start();
    aakash_security_headers();
}

function aakash_security_headers()
{
    static $sent = false;
    if ($sent || headers_sent()) {
        return;
    }
    $sent = true;
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'");
}

aakash_start_session();
