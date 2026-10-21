<?php
// public/privacy.php  ->  http://localhost/shomadhan/public/privacy.php
// Open info page "Privacy policy".
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/PageController.php';

(new PageController())->show('privacy');
