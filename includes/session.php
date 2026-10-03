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
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

aakash_start_session();

function auth_math_key($key)
{
    $key = preg_replace('/[^a-z0-9_-]/', '', (string) $key);
    return $key === '' ? 'form' : $key;
}

function auth_math_issue($key)
{
    $key = auth_math_key($key);
    if (!isset($_SESSION['math_check']) || !is_array($_SESSION['math_check'])) {
        $_SESSION['math_check'] = array();
    }
    $current = isset($_SESSION['math_check'][$key]) ? $_SESSION['math_check'][$key] : null;
    if (is_array($current) && isset($current['a'], $current['b'], $current['at']) && (time() - (int) $current['at']) < 1800) {
        return $current;
    }
    $row = array('a' => random_int(2, 9), 'b' => random_int(1, 8), 'at' => time());
    $_SESSION['math_check'][$key] = $row;
    return $row;
}

function auth_math_prompt($key)
{
    $row = auth_math_issue($key);
    return (int) $row['a'] . ' + ' . (int) $row['b'];
}

function auth_math_verify($key, $answer)
{
    $key = auth_math_key($key);
    $stored = (isset($_SESSION['math_check'][$key]) && is_array($_SESSION['math_check'][$key])) ? $_SESSION['math_check'][$key] : null;
    if (!is_array($stored) || !isset($stored['a'], $stored['b'])) {
        return 'The check expired. Reload the page and try again.';
    }
    $given = trim((string) $answer);
    $expected = (int) $stored['a'] + (int) $stored['b'];
    if (!preg_match('/^\d{1,2}$/', $given) || (int) $given !== $expected) {
        unset($_SESSION['math_check'][$key]);
        return 'The answer to the sum is not right. Try the new sum.';
    }
    return '';
}

function auth_math_clear($key)
{
    $key = auth_math_key($key);
    if (isset($_SESSION['math_check'][$key])) {
        unset($_SESSION['math_check'][$key]);
    }
}
