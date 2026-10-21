<?php
// public/about.php  ->  http://localhost/shomadhan/public/about.php
// Open info page "About us" (story + mission + numbers).
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/PageController.php';

(new PageController())->show('about');
