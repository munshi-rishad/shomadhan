<?php
// app/controllers/UserHomeController.php
// CONTROLLER for the user home page (user-dashboard.php). Everything on it comes from what the admin set:
// banners, popup, services, popular ranking, newest sub-services and offers (bell icon).
//   user-dashboard.php                 home page
//   user-dashboard.php?service=3       all sub-services of one service
//   user-dashboard.php?list=popular    all popular / ?list=new all newest / ?list=services all services
//   user-dashboard.php?q=ac            search
//   POST action=seen                   user opened the bell -> red count becomes 0 (offers + account changes)

require_once APP_PATH . '/models/HomeContentModel.php';
require_once APP_PATH . '/models/AccountChangeModel.php';

class UserHomeController
{
    public function index()
    {
        $user = require_role('user');
        list($lang, $t) = lang_texts();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            if (($_POST['action'] ?? '') === 'seen' && csrf_check($_POST['csrf'] ?? '')) {
                HomeContentModel::markNotificationsSeen($user['id']);
                AccountChangeModel::markSeen('user', $user['id']);
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(403);
                echo json_encode(['ok' => false]);
            }
            return;
        }

        $data = [
            'lang'          => $lang,
            't'             => $t,
            'user'          => $user,
            'notice'        => flash_get('notice'),
            'services'      => HomeContentModel::services(true),
            'notifications' => HomeContentModel::notifications(true, 20),
            'changes'       => AccountChangeModel::forAccount('user', $user['id'], 10),   // what the admin changed in this account
            'unread'        => HomeContentModel::unreadCount($user['id']) + AccountChangeModel::unseen('user', $user['id']),
            'q'             => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60, 'UTF-8'),
            'mode'          => 'home',
        ];

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
        } elseif ($list === 'popular' || $list === 'new') {
            $data['mode']  = 'list';
            $data['title'] = $t[$list === 'popular' ? 'home_popular' : 'home_new'];
            $data['items'] = $list === 'popular' ? HomeContentModel::popular() : HomeContentModel::newest();
        } elseif ($list === 'services') {
            $data['mode']  = 'services';
        } else {
            $data['banners'] = HomeContentModel::banners(true);
            // popup: shows once per login (not again on every refresh)
            $popup = HomeContentModel::activePopup();
            if ($popup && ($_SESSION['popup_seen'] ?? 0) !== (int)$popup['id']) {
                $_SESSION['popup_seen'] = (int)$popup['id'];
                $data['popup'] = $popup;
            }
            $data['popular'] = HomeContentModel::popular(8);
            $data['newest']  = HomeContentModel::newest(8);
        }
        view('user/home', $data);
    }
}
