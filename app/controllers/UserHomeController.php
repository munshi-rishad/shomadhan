<?php
// app/controllers/UserHomeController.php
// CONTROLLER for the user home page (user-dashboard.php). Everything on it comes from what the admin set:
// banners, popup, services, popular ranking, newest sub-services and offers (bell icon).
//   user-dashboard.php                 home page
//   user-dashboard.php?service=3       all sub-services of one service
//   user-dashboard.php?list=popular    all popular / ?list=new all newest / ?list=services all categories
//   user-dashboard.php?q=ac            search
//   POST action=seen                   user opened the bell -> red count becomes 0 (offers + account changes)
//   POST action=fav, id                heart on a service card: favourite on / off (JSON)
// frame() is the shared page frame for every user page (bookings, wallet, messages, profile use it too).

require_once APP_PATH . '/models/HomeContentModel.php';
require_once APP_PATH . '/models/AccountChangeModel.php';
require_once APP_PATH . '/models/MessageModel.php';
require_once APP_PATH . '/models/BookingModel.php';
require_once APP_PATH . '/models/FavouriteModel.php';

class UserHomeController
{
    // ---- The user home page (and its list / search views) ----
    public function index()
    {
        $user = require_role('user');
        list($lang, $t) = lang_texts();

        if (is_post()) {
            self::ajax($user);
            return;
        }

        // which view: search, one service, all services, popular / new list, or the home page itself
        $data = self::frame($user, $lang, $t, 'home');

        $serviceId = (int)($_GET['service'] ?? 0);
        $list      = (string)($_GET['list'] ?? '');
        if ($data['q'] !== '') {
            $data['mode']  = 'list';
            $data['title'] = sprintf($t['home_search_for'], $data['q']);
            $data['items'] = HomeContentModel::search($data['q']);
        } elseif ($serviceId && ($sv = HomeContentModel::service($serviceId)) && $sv['is_active']) {
            $data['mode']  = 'list';
            $data['title'] = $lang === 'en' ? $sv['name_en'] : $sv['name_bn'];
            $data['items'] = HomeContentModel::inService($serviceId);
        } elseif ($list === 'services') {
            $data['mode']  = 'services';          // every service category as a tile
        } elseif ($list === 'popular' || $list === 'new') {
            $data['mode']  = 'list';
            $data['title'] = $t[$list === 'popular' ? 'home_popular' : 'home_new'];
            $data['items'] = $list === 'popular' ? HomeContentModel::popular() : HomeContentModel::newest();
        } else {
            $data['banners'] = HomeContentModel::banners(true);
            $data['popups']  = self::popupsToShow();
            $data['popular'] = HomeContentModel::popular(8);
            $data['newest']  = HomeContentModel::newest(8);
        }
        view('user/home', $data);
    }

    // ---- AJAX from the home page (JSON answer) ----
    // action=seen: the bell was opened, so offers + account changes are read.
    // action=fav:  heart on a card, favourite on / off.
    private static function ajax(array $user)
    {
        header('Content-Type: application/json; charset=utf-8');
        $action  = $_POST['action'] ?? '';
        $csrfOk  = csrf_check($_POST['csrf'] ?? '');
        if ($action === 'seen' && $csrfOk) {
            HomeContentModel::markNotificationsSeen($user['id']);
            AccountChangeModel::markSeen('user', $user['id']);
            echo json_encode(['ok' => true]);
            return;
        }
        if ($action === 'fav' && $csrfOk && ($sub = HomeContentModel::subService((int)($_POST['id'] ?? 0)))) {
            echo json_encode(['ok' => true, 'on' => FavouriteModel::toggle($user['id'], $sub['id'])]);
            return;
        }
        http_response_code(403);
        echo json_encode(['ok' => false]);
    }

    // ---- Popups not seen yet in this login ----
    // Every active popup, one after another (closing one opens the next); each shows once per login,
    // so the ones returned here are remembered as seen.
    private static function popupsToShow()
    {
        $seen = (array)($_SESSION['popup_seen'] ?? []);
        $popups = array_values(array_filter(HomeContentModel::activePopups(), function ($popup) use ($seen) {
            return !in_array((int)$popup['id'], $seen, true);
        }));
        $shownIds = array_map(function ($popup) {
            return (int)$popup['id'];
        }, $popups);
        $_SESSION['popup_seen'] = array_merge($seen, $shownIds);
        return $popups;
    }

    // ---- Page frame for every user page ----
    // what every user page needs around its content (left menu, top bar, bell). Also used by bookings + wallet pages.
    public static function frame(array $user, $lang, array $t, $mode)
    {
        return [
            'lang'          => $lang,
            't'             => $t,
            'user'          => $user,
            'notice'        => flash_get('notice'),
            'services'      => HomeContentModel::services(true),
            'notifications' => HomeContentModel::notifications(true, 20),
            'changes'       => AccountChangeModel::forAccount('user', $user['id'], 10),   // what the office changed (account, bookings, wallet)
            'unread'        => HomeContentModel::unreadCount($user['id']) + AccountChangeModel::unseen('user', $user['id']),
            'q'             => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60, 'UTF-8'),
            'mode'          => $mode,
            'site'          => HomeContentModel::site(),      // "কাস্টমার কেয়ার" button (Admin -> সাইট তথ্য)
            'favIds'        => FavouriteModel::ids($user['id']),   // hearts on the service cards
        ];
    }
}
