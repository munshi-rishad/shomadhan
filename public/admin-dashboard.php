<?php
// public/admin-dashboard.php  ->  http://localhost/shomadhan/public/admin-dashboard.php
// Admin sets the user home page here (banners, services, popular ranking, offers).
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/AdminController.php';

(new AdminController())->handle();
