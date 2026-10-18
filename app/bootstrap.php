<?php
// app/bootstrap.php
// Every page in public/ loads this one file. It loads settings, helpers,
// the database and the login helpers, then logs the user in from the
// "Remember me" cookie if there is one.

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/models/AccountModel.php';
require_once APP_PATH . '/helpers/auth.php';

auth_from_remember_cookie();
