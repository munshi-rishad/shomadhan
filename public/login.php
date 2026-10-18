<?php
// public/login.php  ->  http://localhost/shomadhan/public/login.php
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AuthController.php';

(new AuthController())->login();
