<?php
// public/register.php  ->  http://localhost/shomadhan/public/register.php
require_once __DIR__ . '/../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/controllers/AuthController.php';

(new AuthController())->register();
