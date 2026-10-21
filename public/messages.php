<?php
// public/messages.php  ->  user: my chats with the office
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/MessageController.php';

(new MessageController())->index();
