<?php
// public/logout.php  ->  http://localhost/shomadhan/public/logout.php
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AuthController.php';

(new AuthController())->logout();
