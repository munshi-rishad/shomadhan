<?php
// config/config.php
// Project-wide settings. Every other PHP file loads this first.

define('APP_NAME', 'Shomadhan');            // Website name
define('ROOT_PATH', dirname(__DIR__));      // Path of the project folder
define('APP_PATH',  ROOT_PATH . '/app');    // Path of the app folder (controllers, models, views)

// Session = browser memory for the visitor. We use it to remember the chosen language.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
