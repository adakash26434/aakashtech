<?php
/**
 * Friendly failure screen. An uncaught error used to end in a blank page; now the visitor sees a
 * short apology with a reference code, and the full detail goes to the server error log under that
 * same code, so "I got error 3FA92C10" is enough to find the cause. Nothing technical is shown.
 */

function app_error_reference()
{
    return strtoupper(bin2hex(random_bytes(4)));
}

function app_error_wants_json()
{
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? (string) $_SERVER['HTTP_ACCEPT'] : '';
    return strpos($uri, '/api/') !== false || stripos($accept, 'application/json') !== false;
}

function app_error_page_html($reference)
{
    $ref = htmlspecialchars($reference, ENT_QUOTES, 'UTF-8');
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>Something went wrong</title>'
        . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f8f6;color:#102b35;font-family:system-ui,-apple-system,"Segoe UI","Noto Sans Devanagari",sans-serif;padding:24px}'
        . 'main{max-width:480px;background:#fff;border:1px solid #dce8e4;border-radius:16px;padding:32px;box-shadow:0 12px 32px rgba(16,43,53,.1)}'
        . 'h1{font-size:24px;margin:0 0 12px}p{line-height:1.7;margin:0 0 12px;color:#25434b}code{background:#f1f7f5;padding:2px 8px;border-radius:6px;font-size:15px}'
        . 'a{display:inline-block;margin-top:8px;min-height:44px;line-height:44px;padding:0 22px;border-radius:12px;background:#097a6d;color:#fff;text-decoration:none;font-weight:600}</style></head>'
        . '<body><main><h1>Something went wrong</h1>'
        . '<p>We could not finish that. Your money and messages were not changed by this error. Please try again in a minute.</p>'
        . '<p>If it keeps happening, tell us this code: <code>' . $ref . '</code></p>'
        . '<a href="javascript:history.back()">Go back</a></main></body></html>';
}

function app_error_report($detail)
{
    $reference = app_error_reference();
    error_log('[ref ' . $reference . '] ' . $detail);
    if (headers_sent()) {
        return $reference;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code(500);
    header('Cache-Control: no-store');
    if (app_error_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => false, 'message' => 'Server error', 'reference' => $reference));
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo app_error_page_html($reference);
    }
    return $reference;
}

function app_install_error_handlers()
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    set_exception_handler(function ($exception) {
        app_error_report(get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    });
    register_shutdown_function(function () {
        $last = error_get_last();
        if ($last && in_array($last['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR), true)) {
            app_error_report('Fatal error: ' . $last['message'] . ' in ' . $last['file'] . ':' . $last['line']);
        }
    });
}
