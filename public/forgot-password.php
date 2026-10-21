<?php
// public/forgot-password.php  ->  http://localhost/shomadhan/public/forgot-password.php
// Forgot password: email -> OTP -> new password, all on this one page.
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/PasswordController.php';

(new PasswordController())->forgot();
