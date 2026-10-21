<?php
// public/receipt.php?id=N  ->  printable receipt of a finished booking (the user's own, or any for the office)
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/BookingController.php';

(new BookingController())->receipt();
