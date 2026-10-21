<?php
// public/check-availability.php  ->  http://localhost/shomadhan/public/check-availability.php
// AJAX only: register / profile / admin forms ask "is this mobile or email already used?" (JSON).
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AuthController.php';

(new AuthController())->checkAvailability();
