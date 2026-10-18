<?php
// public/index.php
// The first file that runs. Open it in the browser:
// http://localhost/shomadhan/public/

require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/HomeController.php';

$home = new HomeController();
$home->index();
