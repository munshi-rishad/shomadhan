<?php
// public/forgot-password.php  ->  http://localhost/shomadhan/public/forgot-password.php
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/PasswordController.php';

(new PasswordController())->forgot();
