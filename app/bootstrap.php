<?php
// app/bootstrap.php
// Every page in public/ loads this one file. It loads settings, helpers,
// the database and the login helpers, then logs the user in from the
// "Remember me" cookie if there is one.

// ---- Load the basics (order matters: settings first, auth helpers need the account model) ----
require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/models/AccountModel.php';
require_once APP_PATH . '/helpers/auth.php';

// ---- "Remember me": log back in when the session ended but the cookie is still valid ----
auth_from_remember_cookie();

// ---- Clean the address-bar values ----
// Address-bar values (?q=, ?sort=, ?p= ...) are always plain text on this site.
// Someone typing ?q[]=x would otherwise turn a text into a list and break a page.
foreach ($_GET as $key => $value) {
    if (!is_string($value)) {
        unset($_GET[$key]);
    }
}
