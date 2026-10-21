<?php
// public/wallet.php  ->  user: my wallet (balance, add money, transactions, coupons, points)
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/controllers/WalletController.php';

(new WalletController())->index();
