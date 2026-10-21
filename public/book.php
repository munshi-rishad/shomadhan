<?php
// public/book.php  ->  user: book a service
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/BookingController.php';

(new BookingController())->book();
