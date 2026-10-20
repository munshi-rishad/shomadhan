<?php
// public/profile.php  ->  your profile + change password (user and employee; admin opens it inside the admin dashboard)
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/ProfileController.php';

(new ProfileController())->show();
