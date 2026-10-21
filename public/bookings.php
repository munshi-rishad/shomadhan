<?php
// public/bookings.php  ->  user: my bookings (pending / ongoing / completed / cancelled)
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/BookingController.php';

(new BookingController())->index();
