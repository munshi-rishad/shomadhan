<?php
// public/terms.php  ->  http://localhost/shomadhan/public/terms.php
// Open info page "Terms" (linked from the register form).
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/PageController.php';

(new PageController())->show('terms');
