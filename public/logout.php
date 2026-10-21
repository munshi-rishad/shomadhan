<?php
// public/logout.php  ->  http://localhost/shomadhan/public/logout.php
// Logs this browser out (POST from the logout button), then goes to the login page.
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AuthController.php';

(new AuthController())->logout();
