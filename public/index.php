<?php
// public/index.php  ->  http://localhost/shomadhan/public/
// The welcome page (the first page visitors see): coupon picture, service icons, register / login buttons.

require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/HomeController.php';

(new HomeController())->index();
