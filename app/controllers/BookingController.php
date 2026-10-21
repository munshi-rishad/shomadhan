<?php
// app/controllers/BookingController.php
// CONTROLLER for the user's bookings.
//   book.php?id=3          book a service: date, time, area, address, note, coupon, pay by cash or wallet
//   bookings.php?tab=...   my bookings: pending / ongoing / completed / cancelled
//     POST action=cancel       cancel a pending booking (wallet money comes back, coupon can be used again)
//     POST action=reschedule   new date + time for a pending booking
// Both pages are drawn inside the user frame (UserHomeController::frame) by views/user/home.php.

require_once APP_PATH . '/controllers/UserHomeController.php';
require_once APP_PATH . '/models/BookingModel.php';
require_once APP_PATH . '/models/WalletModel.php';
require_once APP_PATH . '/models/AreaModel.php';

class BookingController
{
    const DAYS_AHEAD = 30;                       // a booking can be up to 30 days from today
    const SLOTS = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];

    // ------------------------------------------------------------ book a service
    public function book()
    {
        $user = require_role('user');
        list($lang, $t) = lang_texts();

        // the sub-service and its service must both exist and be switched on
        $sub = HomeContentModel::subService((int)($_GET['id'] ?? 0));
        $service = $sub ? HomeContentModel::service($sub['service_id']) : null;
        if (!$sub || !$sub['is_active'] || !$service || !$service['is_active']) {
            flash_set('notice', $t['bk_err_service']);
            redirect('user-dashboard.php');
        }

        $errors = [];
        $old = [
            'date'    => '',
            'time'    => '',
            'area'    => $user['area'],
            'address' => (string)($user['address'] ?? ''),
            'note'    => '',
            'coupon'  => '',
            'payment' => 'cash',
        ];
        if (is_post()) {
            $typed = array_map(function ($value) {
                return is_string($value) ? trim($value) : '';
            }, $_POST);
            $old = array_merge($old, $typed);
            $errors = self::saveBooking($old, $user, $sub, $t);   // redirects when saved
        }

        $data = UserHomeController::frame($user, $lang, $t, 'book');
        $data += [
            'sub'     => $sub,
            'errors'  => $errors,
            'old'     => $old,
            'areas'   => AreaModel::getAll(),
            'slots'   => self::SLOTS,
            'minDate' => date('Y-m-d'),
            'maxDate' => self::lastDay(),
            'balance' => WalletModel::balance($user['id']),
            'coupons' => self::couponsFor($user, $sub),
        ];
        view('user/home', $data);
    }

    // ---- Check the booking form and save it ----
    // Saved -> redirect to "my bookings". Otherwise returns the errors for the form.
    private static function saveBooking(array $old, array $user, array $sub, array $t)
    {
        if (!csrf_check($_POST['csrf'] ?? '')) {
            return ['form' => $t['err_csrf']];
        }
        $errors = [];
        $when = self::when($old['date'], $old['time'], $t, $errors);
        if (!in_array($old['area'], AreaModel::getAll(), true)) {
            $errors['area'] = $t['err_area'];
        }
        $len = mb_strlen($old['address'], 'UTF-8');
        if ($len < 5 || $len > 200) {
            $errors['address'] = $t['bk_err_address'];
        }
        if (mb_strlen($old['note'], 'UTF-8') > 255) {
            $errors['note'] = $t['bk_err_note'];
        }
        if (!in_array($old['payment'], ['cash', 'wallet'], true)) {
            $errors['payment'] = $t['bk_err_payment'];
        }
        $coupon = null;
        if ($old['coupon'] !== '') {
            $coupon = WalletModel::coupon((int)$old['coupon']);
            if (!$coupon || !WalletModel::couponUsable($coupon, $user['id'], $sub['price'])) {
                $errors['coupon'] = $t['bk_err_coupon'];
            }
        }
        if ($errors) {
            return $errors;
        }

        try {
            $id = BookingModel::create([
                'user_id'        => $user['id'],
                'sub_service_id' => $sub['id'],
                'scheduled_at'   => $when,
                'area'           => $old['area'],
                'address'        => $old['address'],
                'note'           => $old['note'] !== '' ? $old['note'] : null,
                'price'          => (int)$sub['price'],
                'coupon'         => $coupon,
                'payment'        => $old['payment'],
            ]);
            flash_set('notice', sprintf($t['bk_done'], BookingModel::code($id)));
            redirect('bookings.php?tab=pending');
        } catch (PDOException $ex) {
            // database problem (PDOException is also a RuntimeException, so it must be caught first)
            app_log('error', 'booking: ' . $ex->getMessage());
            return ['form' => $t['err_server']];
        } catch (RuntimeException $ex) {
            // the model throws 'wallet' (balance too low now) or a coupon problem (used a moment ago)
            if ($ex->getMessage() === 'wallet') {
                return ['payment' => $t['bk_err_wallet']];
            }
            return ['coupon' => $t['bk_err_coupon']];
        }
    }

    // The user's coupons that can be used on this sub-service (usable now and the price reaches the minimum order)
    private static function couponsFor(array $user, array $sub)
    {
        $usable = array_filter(WalletModel::forUser($user['id']), function ($coupon) use ($sub) {
            return $coupon['state'] === 'usable' && (int)$sub['price'] >= (int)$coupon['min_order'];
        });
        return array_values($usable);
    }

    // ------------------------------------------------------------ my bookings
    public function index()
    {
        $user = require_role('user');
        list($lang, $t) = lang_texts();
        $tab = in_array($_GET['tab'] ?? '', BookingModel::STATUSES, true) ? $_GET['tab'] : 'pending';
        $errors = is_post() ? self::bookingAction($user, $t) : [];

        $data = UserHomeController::frame($user, $lang, $t, 'bookings');
        $data += [
            'tab'        => $tab,
            'counts'     => BookingModel::countsForUser($user['id']),
            'bookings'   => BookingModel::forUser($user['id'], $tab),
            'errors'     => $errors,
            'reschedule' => (int)($_GET['reschedule'] ?? 0),
            'slots'      => self::SLOTS,
            'minDate'    => date('Y-m-d'),
            'maxDate'    => self::lastDay(),
        ];
        view('user/home', $data);
    }

    // ---- Cancel or reschedule one of my pending bookings ----
    // Done -> redirect to the right tab. Otherwise returns the errors for the page.
    private static function bookingAction(array $user, array $t)
    {
        $booking = BookingModel::findForUser((int)($_POST['id'] ?? 0), $user['id']);
        $action  = (string)($_POST['action'] ?? '');
        if (!csrf_check($_POST['csrf'] ?? '')) {
            return ['form' => $t['err_csrf']];
        }
        if (!$booking || $booking['status'] !== 'pending') {
            return ['form' => $t['bk_err_not_pending']];      // already taken by the office, or not mine
        }

        $errors = [];
        try {
            // cancel: wallet money and coupon come back (BookingModel::cancel)
            if ($action === 'cancel') {
                if (!BookingModel::cancel($booking, 'user')) {         // the office took it a moment ago
                    throw new DomainException('not pending');
                }
                flash_set('notice', sprintf($t['bk_cancelled'], BookingModel::code($booking['id'])));
                redirect('bookings.php?tab=cancelled');
            }

            // reschedule: new date + time slot
            if ($action === 'reschedule') {
                $date = trim((string)($_POST['date'] ?? ''));
                $time = trim((string)($_POST['time'] ?? ''));
                $when = self::when($date, $time, $t, $errors);
                if (!$errors) {
                    if (!BookingModel::reschedule($booking['id'], $user['id'], $when)) {
                        throw new DomainException('not pending');
                    }
                    flash_set('notice', sprintf($t['bk_rescheduled'], BookingModel::code($booking['id'])));
                    redirect('bookings.php?tab=pending');
                }
                $_GET['reschedule'] = $booking['id'];          // keep the form open with the error
            }
        } catch (DomainException $ex) {
            $errors['form'] = $t['bk_err_not_pending'];
        } catch (PDOException $ex) {
            app_log('error', 'booking ' . $action . ': ' . $ex->getMessage());
            $errors['form'] = $t['err_server'];
        }
        return $errors;
    }

    // ------------------------------------------------------------ shared helpers
    // The last day a booking can be on ('Y-m-d', DAYS_AHEAD days from today)
    private static function lastDay()
    {
        return date('Y-m-d', strtotime('+' . self::DAYS_AHEAD . ' days'));
    }

    // date + time slot -> 'Y-m-d H:i:00' (must be in the next 30 days and at least 1 hour from now).
    // Errors go to $errors and null is returned.
    public static function when($date, $time, array $t, array &$errors)
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', (string)$date);
        $realDate = $parsed && $parsed->format('Y-m-d') === $date;
        if (!$realDate || $date < date('Y-m-d') || $date > self::lastDay()) {
            $errors['date'] = sprintf($t['bk_err_date'], self::DAYS_AHEAD);
            return null;
        }
        if (!in_array($time, self::SLOTS, true)) {
            $errors['time'] = $t['bk_err_time'];
            return null;
        }
        $when = $date . ' ' . $time . ':00';
        if (strtotime($when) < time() + 3600) {
            $errors['time'] = $t['bk_err_soon'];
            return null;
        }
        return $when;
    }
}
