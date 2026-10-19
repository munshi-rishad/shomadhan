<?php
// app/controllers/AdminController.php
// CONTROLLER for the admin dashboard. The admin sets what users see on their home page:
//   ?page=banners       pictures at the top (slider)
//   ?page=welcome       coupon picture on the welcome page
//   ?page=popup         picture that pops up when the user opens the home page
//   ?page=services      service categories with a PNG logo
//   ?page=sub-services  services inside a category (name, photo, price, rating ...)
//   ?page=popular       ranking for "জনপ্রিয় সেবা"
//   ?page=offers        notifications / latest offers sent to users
// Every form uses POST + CSRF token. After saving, the page reloads (refresh won't save twice).

require_once APP_PATH . '/models/HomeContentModel.php';
require_once APP_PATH . '/helpers/upload.php';

class AdminController
{
    const PAGES  = ['home', 'welcome', 'banners', 'popup', 'services', 'sub-services', 'popular', 'offers'];
    const COLORS = ['sand', 'sky', 'mint', 'rose', 'lilac', 'gray'];

    private $t;
    private $errors = [];

    public function handle()
    {
        $account = require_role('admin');
        list($lang, $t) = lang_texts();
        $this->t = $t;
        $page = in_array($_GET['page'] ?? 'home', self::PAGES, true) ? ($_GET['page'] ?? 'home') : 'home';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!csrf_check($_POST['csrf'] ?? '')) {
                $this->errors['form'] = $t['err_csrf'];
            } else {
                try {
                    $this->save($page, (string)($_POST['action'] ?? ''));
                } catch (PDOException $ex) {
                    app_log('error', 'admin ' . $page . ': ' . $ex->getMessage());
                    $this->errors['form'] = $t['err_server'];
                }
            }
        }

        $data = [
            'lang'    => $lang,
            't'       => $t,
            'account' => $account,
            'page'    => $page,
            'errors'  => $this->errors,
            'notice'  => flash_get('notice'),
            'colors'  => self::COLORS,
            'old'     => $this->errors ? $_POST : [],
        ];
        $editId = (int)($_GET['edit'] ?? 0);
        switch ($page) {
            case 'banners':
                $data['banners'] = HomeContentModel::banners();
                break;
            case 'welcome':
                $data['welcome'] = HomeContentModel::setting('welcome_banner', HomeContentModel::DEFAULT_WELCOME_BANNER);
                $data['default'] = HomeContentModel::DEFAULT_WELCOME_BANNER;
                break;
            case 'popup':
                $data['popups'] = HomeContentModel::popups();
                break;
            case 'services':
                $data['services'] = HomeContentModel::services();
                $data['edit'] = $editId ? HomeContentModel::service($editId) : null;
                break;
            case 'sub-services':
                $data['services'] = HomeContentModel::services();
                $data['filter']   = (int)($_GET['service'] ?? 0);
                $data['items']    = HomeContentModel::subServices($data['filter']);
                $data['edit']     = $editId ? HomeContentModel::subService($editId) : null;
                break;
            case 'popular':
                $data['items'] = HomeContentModel::subServices();
                usort($data['items'], function ($a, $b) {   // ranked first (1, 2, 3 ...), then the rest
                    $ra = $a['popular_rank'] === null ? PHP_INT_MAX : (int)$a['popular_rank'];
                    $rb = $b['popular_rank'] === null ? PHP_INT_MAX : (int)$b['popular_rank'];
                    return $ra <=> $rb ?: $a['id'] <=> $b['id'];
                });
                break;
            case 'offers':
                $data['offers'] = HomeContentModel::notifications();
                break;
            default:
                $data['counts'] = [
                    'banners'  => count(HomeContentModel::banners()),
                    'popups'   => count(HomeContentModel::popups()),
                    'services' => count(HomeContentModel::services()),
                    'subs'     => count(HomeContentModel::subServices()),
                    'popular'  => count(HomeContentModel::popular()),
                    'offers'   => count(HomeContentModel::notifications()),
                ];
        }
        view('admin/layout', $data);
    }

    // ------------------------------------------------------------ SAVE (POST)
    private function save($page, $action)
    {
        $t  = $this->t;
        $id = (int)($_POST['id'] ?? 0);

        // ---- banners
        if ($page === 'banners' && $action === 'add') {
            $up = save_image_upload('image', 'banners', ['png', 'jpg', 'webp'], $t);
            if ($up['error']) { $this->errors['image'] = $up['error']; return; }
            HomeContentModel::addBanner($up['path'], $this->text('title', 100), (int)($_POST['sort_order'] ?? 0));
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'banners' && $action === 'update' && HomeContentModel::banner($id)) {
            HomeContentModel::updateBanner($id, $this->text('title', 100), (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0);
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'banners' && $action === 'delete' && ($b = HomeContentModel::banner($id))) {
            HomeContentModel::deleteBanner($id);
            delete_upload($b['image']);
            return $this->done($page, $t['adm_deleted']);
        }

        // ---- welcome page coupon picture (new upload replaces the old one; "default" puts back the built-in picture)
        if ($page === 'welcome' && ($action === 'save' || $action === 'default')) {
            $old = HomeContentModel::setting('welcome_banner', HomeContentModel::DEFAULT_WELCOME_BANNER);
            $new = HomeContentModel::DEFAULT_WELCOME_BANNER;
            if ($action === 'save') {
                $up = save_image_upload('image', 'welcome', ['png', 'jpg', 'webp'], $t);
                if ($up['error']) { $this->errors['image'] = $up['error']; return; }
                $new = $up['path'];
            }
            HomeContentModel::setSetting('welcome_banner', $new);
            if ($old !== $new) { delete_upload($old); }       // only deletes uploaded files, never the built-in one
            return $this->done($page, $t['adm_saved']);
        }

        // ---- popup
        if ($page === 'popup' && $action === 'add') {
            $up = save_image_upload('image', 'popups', ['png', 'jpg', 'webp'], $t);
            if ($up['error']) { $this->errors['image'] = $up['error']; return; }
            HomeContentModel::addPopup($up['path'], $this->text('title', 100));
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'popup' && $action === 'toggle' && ($p = HomeContentModel::popup($id))) {
            HomeContentModel::setPopupActive($id, $p['is_active'] ? 0 : 1);
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'popup' && $action === 'delete' && ($p = HomeContentModel::popup($id))) {
            HomeContentModel::deletePopup($id);
            delete_upload($p['image']);
            return $this->done($page, $t['adm_deleted']);
        }

        // ---- services
        if ($page === 'services' && $action === 'save') {
            $old = $id ? HomeContentModel::service($id) : null;
            if ($id && !$old) { return $this->done($page, ''); }
            $d = [
                'name_bn'    => $this->text('name_bn', 60),
                'name_en'    => $this->text('name_en', 60),
                'color'      => in_array($_POST['color'] ?? '', self::COLORS, true) ? $_POST['color'] : 'mint',
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'is_active'  => isset($_POST['is_active']) ? 1 : 0,
                'icon'       => $old['icon'] ?? '',
            ];
            if ($d['name_bn'] === '') { $this->errors['name_bn'] = $t['adm_err_required']; }
            if ($d['name_en'] === '') { $this->errors['name_en'] = $t['adm_err_required']; }
            $up = save_image_upload('icon', 'services', ['png'], $t, !$old);     // logo: PNG only, required for a new service
            if ($up['error']) { $this->errors['icon'] = $up['error']; }
            if ($this->errors) { delete_upload($up['path']); return; }
            if ($up['path']) {
                if ($old) { delete_upload($old['icon']); }
                $d['icon'] = $up['path'];
            }
            HomeContentModel::saveService($id, $d);
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'services' && $action === 'delete' && ($s = HomeContentModel::service($id))) {
            foreach (HomeContentModel::subServices($id) as $x) { delete_upload($x['image']); }
            HomeContentModel::deleteService($id);
            delete_upload($s['icon']);
            return $this->done($page, $t['adm_deleted']);
        }

        // ---- sub-services
        if ($page === 'sub-services' && $action === 'save') {
            $old = $id ? HomeContentModel::subService($id) : null;
            if ($id && !$old) { return $this->done($page, ''); }
            $rank = trim((string)($_POST['popular_rank'] ?? ''));
            $d = [
                'service_id'   => (int)($_POST['service_id'] ?? 0),
                'name_bn'      => $this->text('name_bn', 80),
                'name_en'      => $this->text('name_en', 80),
                'detail_bn'    => $this->text('detail_bn', 120) ?: null,
                'detail_en'    => $this->text('detail_en', 120) ?: null,
                'price'        => max(0, (int)($_POST['price'] ?? 0)),
                'rating'       => min(5, max(0, round((float)($_POST['rating'] ?? 0), 1))),
                'rating_count' => max(0, (int)($_POST['rating_count'] ?? 0)),
                'popular_rank' => ctype_digit($rank) && (int)$rank > 0 ? (int)$rank : null,
                'is_active'    => isset($_POST['is_active']) ? 1 : 0,
                'image'        => $old['image'] ?? null,
            ];
            if (!HomeContentModel::service($d['service_id'])) { $this->errors['service_id'] = $t['adm_err_required']; }
            if ($d['name_bn'] === '') { $this->errors['name_bn'] = $t['adm_err_required']; }
            if ($d['name_en'] === '') { $this->errors['name_en'] = $t['adm_err_required']; }
            $up = save_image_upload('image', 'sub-services', ['png', 'jpg', 'webp'], $t, false);
            if ($up['error']) { $this->errors['image'] = $up['error']; }
            if ($this->errors) { delete_upload($up['path']); return; }
            if ($up['path']) {
                if ($old) { delete_upload($old['image']); }
                $d['image'] = $up['path'];
            }
            HomeContentModel::saveSubService($id, $d);
            return $this->done($page . '&service=' . (int)($_GET['service'] ?? 0), $t['adm_saved']);
        }
        if ($page === 'sub-services' && $action === 'delete' && ($x = HomeContentModel::subService($id))) {
            HomeContentModel::deleteSubService($id);
            delete_upload($x['image']);
            return $this->done($page . '&service=' . (int)($_GET['service'] ?? 0), $t['adm_deleted']);
        }

        // ---- popular ranking: rank[ID] = number (empty = not popular)
        if ($page === 'popular' && $action === 'save') {
            foreach ((array)($_POST['rank'] ?? []) as $subId => $rank) {
                $rank = trim((string)$rank);
                HomeContentModel::setPopularRank((int)$subId, ctype_digit($rank) && (int)$rank > 0 ? (int)$rank : null);
            }
            return $this->done($page, $t['adm_saved']);
        }

        // ---- offers / notifications
        if ($page === 'offers' && $action === 'add') {
            $title = $this->text('title', 100);
            $msg   = $this->text('message', 500);
            if ($title === '') { $this->errors['title'] = $t['adm_err_required']; }
            if ($msg === '')   { $this->errors['message'] = $t['adm_err_required']; }
            $up = save_image_upload('image', 'offers', ['png', 'jpg', 'webp'], $t, false);
            if ($up['error']) { $this->errors['image'] = $up['error']; }
            if ($this->errors) { delete_upload($up['path']); return; }
            HomeContentModel::addNotification($title, $msg, $up['path']);
            return $this->done($page, $t['adm_offer_sent']);
        }
        if ($page === 'offers' && $action === 'toggle' && ($n = HomeContentModel::notification($id))) {
            HomeContentModel::setNotificationActive($id, $n['is_active'] ? 0 : 1);
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'offers' && $action === 'delete' && ($n = HomeContentModel::notification($id))) {
            HomeContentModel::deleteNotification($id);
            delete_upload($n['image']);
            return $this->done($page, $t['adm_deleted']);
        }
    }

    private function text($field, $max)
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($_POST[$field] ?? ''))), 0, $max, 'UTF-8');
    }

    private function done($page, $message)
    {
        if ($message !== '') {
            flash_set('notice', $message);
        }
        redirect('admin-dashboard.php?page=' . $page);
    }
}
