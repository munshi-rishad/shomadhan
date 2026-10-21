<?php
// app/controllers/WalletController.php
// CONTROLLER for the user's wallet (wallet.php).
//   wallet.php              balance, coupons, reward points, recent transactions
//   wallet.php#add          add money: user sends bKash / Nagad / Rocket to the office number and types the transaction ID
//   wallet.php#history      all transactions      #coupons  my coupons      #points  reward points
//   POST action=topup       saves the request as 'pending'; an admin / employee checks it (Admin -> টাকা যোগের অনুরোধ)

require_once APP_PATH . '/controllers/UserHomeController.php';
require_once APP_PATH . '/models/BookingModel.php';
require_once APP_PATH . '/models/WalletModel.php';

class WalletController
{
    // ---- The wallet page ----
    public function index()
    {
        $user = require_role('user');
        list($lang, $t) = lang_texts();
        $site = HomeContentModel::site();

        // only the ways the admin gave a number for (Admin -> সাইট তথ্য)
        $methods = array_values(array_filter(WalletModel::METHODS, function ($method) use ($site) {
            return trim((string)($site['pay_' . $method] ?? '')) !== '';
        }));
        $errors = [];
        $old = ['method' => $methods[0] ?? '', 'amount' => '', 'trx_id' => ''];

        if (is_post() && ($_POST['action'] ?? '') === 'topup') {
            $old = [
                'method' => (string)($_POST['method'] ?? ''),
                'amount' => trim((string)($_POST['amount'] ?? '')),
                'trx_id' => strtoupper(preg_replace('/\s+/', '', (string)($_POST['trx_id'] ?? ''))),
            ];
            $errors = self::saveTopup($user, $old, $methods, $t);     // redirects when saved
        }

        $coupons = WalletModel::forUser($user['id']);
        $data = UserHomeController::frame($user, $lang, $t, 'wallet');
        $usable = array_filter($coupons, function ($coupon) {
            return $coupon['state'] === 'usable';
        });
        $data += [
            'errors'     => $errors,
            'old'        => $old,
            'methods'    => $methods,
            'balance'    => WalletModel::balance($user['id']),
            'waiting'    => WalletModel::pendingTopupsOf($user['id']),
            'history'    => WalletModel::history($user['id']),
            'coupons'    => $coupons,
            'usable'     => count($usable),
            'points'     => BookingModel::points($user['id']),
            'pointsList' => BookingModel::pointsList($user['id']),
            'min'        => WalletModel::MIN_TOPUP,
            'max'        => WalletModel::MAX_TOPUP,
        ];
        view('user/home', $data);
    }

    // ---- Add-money request ----
    // Checks the form and saves the request as 'pending' -> redirect. Otherwise returns the errors.
    private static function saveTopup(array $user, array $old, array $methods, array $t)
    {
        if (!csrf_check($_POST['csrf'] ?? '')) {
            return ['form' => $t['err_csrf']];
        }
        if (too_many('topup', 5, 600)) {                    // 5 requests in 10 minutes is enough for anyone
            return ['form' => $t['err_too_many']];
        }

        $errors = [];
        if (!in_array($old['method'], $methods, true)) {
            $errors['method'] = $t['wl_err_method'];
        }
        $amount = (int)$old['amount'];
        if (!preg_match('/^\d{2,5}$/', $old['amount']) || $amount < WalletModel::MIN_TOPUP || $amount > WalletModel::MAX_TOPUP) {
            $errors['amount'] = sprintf($t['wl_err_amount'], WalletModel::MIN_TOPUP, number_format(WalletModel::MAX_TOPUP));
        }
        if (!preg_match('/^[A-Z0-9]{8,20}$/', $old['trx_id'])) {
            $errors['trx_id'] = $t['wl_err_trx'];
        }
        if ($errors) {
            return $errors;
        }

        try {
            WalletModel::requestTopup($user['id'], $old['method'], $amount, $old['trx_id']);
            flash_set('notice', $t['wl_sent']);
            redirect('wallet.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                return ['trx_id' => $t['wl_err_trx_used']];      // same transaction ID was sent before
            }
            app_log('error', 'topup: ' . $ex->getMessage());
            return ['form' => $t['err_server']];
        }
    }
}
