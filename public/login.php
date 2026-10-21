<?php
// public/login.php  ->  http://localhost/shomadhan/public/login.php
// Login form for admins, employees and users.
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AuthController.php';

(new AuthController())->login();
