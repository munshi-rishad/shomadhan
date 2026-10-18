<?php
// config/config.php
// Project-wide settings. Every other PHP file loads this first.

define('APP_NAME', 'Shomadhan');            // Website name
define('ROOT_PATH', dirname(__DIR__));      // Path of the project folder
define('APP_PATH',  ROOT_PATH . '/app');    // Path of the app folder (controllers, models, views)

date_default_timezone_set('Asia/Dhaka');

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
}
