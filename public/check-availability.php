<?php
// public/check-availability.php  ->  http://localhost/shomadhan/public/check-availability.php
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AuthController.php';

(new AuthController())->checkAvailability();
