<?php
// public/employee-dashboard.php  ->  http://localhost/shomadhan/public/employee-dashboard.php
// Employee dashboard: same panel as the admin, fewer pages, view only (see AdminController::ACCESS).
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AdminController.php';

(new AdminController())->handle('employee');
