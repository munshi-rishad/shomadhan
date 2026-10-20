<?php
// public/set-password.php  ->  first login of an admin / employee made by an admin: set your own password
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/PasswordController.php';

(new PasswordController())->firstPassword();
