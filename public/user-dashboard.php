<?php
// public/user-dashboard.php  ->  http://localhost/shomadhan/public/user-dashboard.php
// The user home page (banners, services, popular, new services, offers).
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/UserHomeController.php';

(new UserHomeController())->index();
