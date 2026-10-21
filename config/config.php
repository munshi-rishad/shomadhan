<?php
// config/config.php
// Project-wide settings: name, paths, time zone, error log, safety headers and the session.
// app/bootstrap.php loads this first, before anything else.

// ---- Name and paths ----
define('APP_NAME', 'Shomadhan');            // Website name
define('ROOT_PATH', dirname(__DIR__));      // Path of the project folder
define('APP_PATH',  ROOT_PATH . '/app');    // Path of the app folder (controllers, models, views)

// ---- Time zone (the database uses the same, see core/Database.php) ----
date_default_timezone_set('Asia/Dhaka');

// ---- Errors ----
// Errors are written to storage/logs/php-errors.log, never shown to visitors
// (an error on screen can show file paths or SQL to a hacker). Set to '1' only while you fix a bug.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/storage/logs/php-errors.log');
error_reporting(E_ALL);

// ---- Safety headers on every page ----
if (!headers_sent()) {
    header('X-Frame-Options: DENY');                                  // no one can put our pages inside their site (clickjacking)
    header('X-Content-Type-Options: nosniff');                        // browser must trust our file types
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header_remove('X-Powered-By');                                    // don't tell which PHP version runs
}

// ---- Session ----
// Session = browser memory for the visitor (language, logged-in user, OTP steps).
// The session cookie is HttpOnly (JavaScript can't read it) and SameSite=Lax.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,                     // ends when the browser closes (Remember me keeps you longer)
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('SHOMADHAN_SID');
    session_start();

    // Logged in but nothing done for 2 hours -> logged out ("Remember me" logs back in if it was ticked)
    $idle = 2 * 60 * 60;
    $idleTooLong = isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > $idle;
    if (!empty($_SESSION['auth']) && $idleTooLong) {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = time();
}
