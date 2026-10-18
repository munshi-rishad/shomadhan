<?php
// public/user-dashboard.php  ->  http://localhost/shomadhan/public/user-dashboard.php
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/DashboardController.php';

(new DashboardController())->user();
