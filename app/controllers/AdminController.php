<?php
// app/controllers/AdminController.php
// CONTROLLER for the admin AND employee dashboards (same pages, same look).
//   admin    -> admin-dashboard.php     all pages, can add / edit / delete
//   employee -> employee-dashboard.php  only the pages in ACCESS['employee'], read-only (own profile can be edited)
// The admin sets what users see on their home page:
//   ?page=banners       pictures at the top (slider)
//   ?page=welcome       coupon picture on the welcome page
//   ?page=popup         picture that pops up when the user opens the home page
//   ?page=services      service categories with a PNG logo
//   ?page=sub-services  services inside a category (name, photo, price, rating ...)
//   ?page=popular       ranking for "জনপ্রিয় সেবা"
//   ?page=offers        notifications / latest offers sent to users
//   ?page=providers     service providers (electrician, plumber ...): add + edit + delete, linked to sub-services
//   ?page=users / employees / admins / products   lists with search, sort and pages
//                       users: edit + delete; employees and admins: add + edit + delete + move (employee <-> admin)
//                       every account change needs the admin's own password, and the person is told what changed
//   ?page=changes       what an admin changed in MY account (who, when, what)
//   ?page=profile       the logged-in person's info, photo + change password
// Every form uses POST + CSRF token. After saving, the page reloads (refresh won't save twice).

require_once APP_PATH . '/models/HomeContentModel.php';
require_once APP_PATH . '/models/AdminListModel.php';
require_once APP_PATH . '/helpers/upload.php';
require_once APP_PATH . '/models/AreaModel.php';
require_once APP_PATH . '/validators/AccountValidator.php';
require_once APP_PATH . '/controllers/ProfileController.php';
require_once APP_PATH . '/models/AccountChangeModel.php';
require_once APP_PATH . '/models/ProviderModel.php';

class AdminController
{
    const PAGES  = ['welcome', 'banners', 'popup', 'services', 'sub-services', 'providers', 'popular', 'offers', 'users', 'employees', 'admins', 'products', 'changes', 'profile'];
    // which pages each role can open
    // which pages each role can open. The first one opens after login (= first item of the menu; there is no overview page)
    const ACCESS = [
        // admin has no 'products' page: the same sub-services are already on the sub-services page
        'admin'    => ['services', 'sub-services', 'providers', 'popular', 'banners', 'popup', 'offers', 'welcome', 'users', 'employees', 'admins', 'profile', 'changes'],
        'employee' => ['providers', 'products', 'offers', 'users', 'profile', 'changes'],
    ];
    const ROLE_OF = ['users' => 'user', 'employees' => 'employee', 'admins' => 'admin'];   // list page -> account role
    const STATUSES = ['active', 'inactive'];      // inactive = can't log in or do anything (no separate "block")
    const COLORS = ['sand', 'sky', 'mint', 'rose', 'lilac', 'gray'];

    private $t;
    private $me;
    private $errors = [];
    private $base;        // this dashboard's address: admin-dashboard.php or employee-dashboard.php

    public function handle($role = 'admin')
    {
        $account = require_role($role);
        list($lang, $t) = lang_texts();
        $this->t = $t;
        $this->me = $account;
        if ($role === 'admin') { HomeContentModel::$by = (int)$account['id']; }   // saved as created_by on new banners, services ...
        $this->base = dashboard_url($role);
        $pages   = self::ACCESS[$role];
        $canEdit = $role === 'admin';
        $page = in_array($_GET['page'] ?? '', $pages, true) ? $_GET['page'] : $pages[0];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!csrf_check($_POST['csrf'] ?? '')) {
                $this->errors['form'] = $t['err_csrf'];
            } elseif (!$canEdit && $page !== 'profile') {     // employee: read-only (checked here, not only hidden in the page)
                $this->errors['form'] = $t['emp_view_only'];
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
            'panel'   => $role,
            'base'    => $this->base,
            'pages'   => $pages,
            'canEdit' => $canEdit,
            'page'    => $page,
            'errors'  => $this->errors,
            'notice'  => flash_get('notice'),
            'colors'  => self::COLORS,
            'old'     => $this->errors ? $_POST : [],
            'unseen'  => AccountChangeModel::unseen($role, $account['id']),      // red number on the bell (top right)
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
                $data['edit']   = $editId ? HomeContentModel::popup($editId) : null;
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
                $data['providers'] = ProviderModel::choices();
                $data['picked']    = $data['edit'] ? ProviderModel::idsFor($editId) : [];
                $data['pvNames']   = ProviderModel::namesBySub();
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
                $data['offers'] = HomeContentModel::notifications(false, 0, 'id');
                $data['edit']   = $editId ? HomeContentModel::notification($editId) : null;
                break;
            case 'users':
            case 'employees':
            case 'admins':
            case 'providers':
            case 'products':
                $filters = [];
                foreach (AdminListModel::filterKeys($page) as $f) {
                    $filters[$f] = trim((string)($_GET[$f] ?? ''));
                }
                list($rows, $total, $p, $pages, $sort, $dir) = AdminListModel::find(
                    $page, $lang, $_GET['q'] ?? '', $_GET['sort'] ?? 'id', $_GET['dir'] ?? 'asc', $_GET['p'] ?? 1, $filters);
                $data['body'] = 'list';
                if (isset(self::ROLE_OF[$page]) && $canEdit) {
                    $data['role']  = self::ROLE_OF[$page];
                    $data['edit']  = $editId ? AccountModel::find(self::ROLE_OF[$page], $editId) : null;
                }
                if ($page === 'providers' && $canEdit) {
                    $data['role'] = 'provider';
                    $data['edit'] = $editId ? ProviderModel::find($editId) : null;
                }
                $data['list'] = [
                    'rows' => $rows, 'total' => $total, 'p' => $p, 'pages' => $pages,
                    'sort' => $sort, 'dir' => $dir, 'q' => trim((string)($_GET['q'] ?? '')),
                    'cols' => AdminListModel::sortKeys($page, $lang),
                    'filters' => $filters,
                ];
                $data['areas'] = in_array($page, ['users', 'providers'], true) ? AreaModel::getAll() : [];
                $data['services'] = in_array($page, ['products', 'providers'], true) ? HomeContentModel::services() : [];
                break;
            case 'changes':
                $data['changes'] = AccountChangeModel::forAccount($role, $account['id'], 50);
                AccountChangeModel::markSeen($role, $account['id']);        // opened = seen (the list still shows which were new)
                $data['unseen'] = 0;
                break;
            case 'profile':
                $data['box'] = $this->errors ? (($_POST['action'] ?? '') === 'info' ? 'info' : 'password') : '';
                break;
        }
        view('admin/layout', $data);
    }

    // ------------------------------------------------------------ SAVE (POST)
    private function save($page, $action)
    {
        $t  = $this->t;
        $id = (int)($_POST['id'] ?? 0);

        // ---- my profile: change my own password
        if ($page === 'profile' && $action === 'info') {
            $this->errors = ProfileController::saveInfo($this->me, $_POST, $t);
            if (!$this->errors) { return $this->done($page, $t['pf_saved']); }
            return;
        }
        if ($page === 'profile' && ($action === 'photo' || $action === 'photo_remove')) {
            $this->errors = ProfileController::savePhoto($this->me, $action === 'photo_remove', $t);
            if (!$this->errors) { return $this->done($page, $t['ph_saved']); }
            return;
        }
        if ($page === 'providers') {
            return $this->saveProvider($action, $id);
        }
        if ($page === 'profile' && $action === 'password') {
            $this->errors = ProfileController::changePassword($this->me, $_POST, $t);
            if (!$this->errors) { return $this->done($page, $t['cp_done']); }
            return;
        }

        // ---- users / employees / admins
        if (isset(self::ROLE_OF[$page])) {
            return $this->saveAccount($page, self::ROLE_OF[$page], $action, $id);
        }

        // ---- banners
        if ($page === 'banners' && $action === 'add') {
            $up = save_image_upload('image', 'banners', ['png', 'jpg', 'webp'], $t, true, IMG_SIZES['banners']);
            if ($up['error']) { $this->errors['image'] = $up['error']; return; }
            HomeContentModel::addBanner($up['path'], $this->text('title', 100), max(-9999, min(9999, (int)($_POST['sort_order'] ?? 0))));
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'banners' && $action === 'update' && HomeContentModel::banner($id)) {
            HomeContentModel::updateBanner($id, $this->text('title', 100), max(-9999, min(9999, (int)($_POST['sort_order'] ?? 0))), isset($_POST['is_active']) ? 1 : 0);
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
                $up = save_image_upload('image', 'welcome', ['png', 'jpg', 'webp'], $t, true, IMG_SIZES['welcome']);
                if ($up['error']) { $this->errors['image'] = $up['error']; return; }
                $new = $up['path'];
            }
            HomeContentModel::setSetting('welcome_banner', $new);
            if ($old !== $new) { delete_upload($old); }       // only deletes uploaded files, never the built-in one
            return $this->done($page, $t['adm_saved']);
        }

        // ---- popup
        if ($page === 'popup' && $action === 'add') {
            $link = trim((string)($_POST['link'] ?? ''));
            if ($link !== '' && (strlen($link) > 255 || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $link))) {
                $this->errors['link'] = $t['adm_err_link'];
                return;
            }
            $up = save_image_upload('image', 'popups', ['png', 'jpg', 'webp'], $t, true, IMG_SIZES['popups']);
            if ($up['error']) { $this->errors['image'] = $up['error']; return; }
            HomeContentModel::addPopup($up['path'], $this->text('title', 100), $link);
            return $this->done($page, $t['adm_saved']);
        }
        if ($page === 'popup' && $action === 'save' && ($old = HomeContentModel::popup($id))) {     // edit: title, link, new picture (optional)
            $link = trim((string)($_POST['link'] ?? ''));
            if ($link !== '' && (strlen($link) > 255 || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $link))) {
                $this->errors['link'] = $t['adm_err_link'];
                return;
            }
            $up = save_image_upload('image', 'popups', ['png', 'jpg', 'webp'], $t, false, IMG_SIZES['popups']);
            if ($up['error']) { $this->errors['image'] = $up['error']; return; }
            HomeContentModel::updatePopup($id, $up['path'] ?: $old['image'], $this->text('title', 100), $link);
            if ($up['path']) { delete_upload($old['image']); }
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
                'sort_order' => max(-9999, min(9999, (int)($_POST['sort_order'] ?? 0))),
                'is_active'  => isset($_POST['is_active']) ? 1 : 0,
                'icon'       => $old['icon'] ?? '',
            ];
            if ($d['name_bn'] === '') { $this->errors['name_bn'] = $t['adm_err_required']; }
            if ($d['name_en'] === '') { $this->errors['name_en'] = $t['adm_err_required']; }
            $up = save_image_upload('icon', 'services', ['png'], $t, !$old, IMG_SIZES['services']);     // logo: PNG only, required for a new service
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
            // a category with services inside can't be deleted (so no service is lost by one click). Hide it or empty it first.
            if ($n = count(HomeContentModel::subServices($id))) { $this->errors['form'] = sprintf($t['adm_err_service_has_subs'], $n); return; }
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
                'price'        => max(0, min(9999999, (int)($_POST['price'] ?? 0))),
                'rating'       => min(5, max(0, round((float)($_POST['rating'] ?? 0), 1))),
                'rating_count' => max(0, min(9999999, (int)($_POST['rating_count'] ?? 0))),
                'popular_rank' => ctype_digit($rank) && (int)$rank > 0 ? min(9999, (int)$rank) : null,
                'is_active'    => isset($_POST['is_active']) ? 1 : 0,
                'image'        => $old['image'] ?? null,
            ];
            if (!HomeContentModel::service($d['service_id'])) { $this->errors['service_id'] = $t['adm_err_required']; }
            if ($d['name_bn'] === '') { $this->errors['name_bn'] = $t['adm_err_required']; }
            if ($d['name_en'] === '') { $this->errors['name_en'] = $t['adm_err_required']; }
            $up = save_image_upload('image', 'sub-services', ['png', 'jpg', 'webp'], $t, false, IMG_SIZES['sub-services']);
            if ($up['error']) { $this->errors['image'] = $up['error']; }
            if ($this->errors) { delete_upload($up['path']); return; }
            if ($up['path']) {
                if ($old) { delete_upload($old['image']); }
                $d['image'] = $up['path'];
            }
            $subId = HomeContentModel::saveSubService($id, $d);
            ProviderModel::setFor($subId, $d['service_id'], (array)($_POST['providers'] ?? []));   // who gives this service
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
                HomeContentModel::setPopularRank((int)$subId, ctype_digit($rank) && (int)$rank > 0 ? min(9999, (int)$rank) : null);
            }
            return $this->done($page, $t['adm_saved']);
        }

        // ---- offers / notifications
        if ($page === 'offers' && $action === 'add') {
            $title = $this->text('title', 100);
            $msg   = $this->text('message', 500);
            if ($title === '') { $this->errors['title'] = $t['adm_err_required']; }
            if ($msg === '')   { $this->errors['message'] = $t['adm_err_required']; }
            $up = save_image_upload('image', 'offers', ['png', 'jpg', 'webp'], $t, false, IMG_SIZES['offers']);
            if ($up['error']) { $this->errors['image'] = $up['error']; }
            if ($this->errors) { delete_upload($up['path']); return; }
            HomeContentModel::addNotification($title, $msg, $up['path']);
            return $this->done($page, $t['adm_offer_sent']);
        }
        if ($page === 'offers' && $action === 'save' && ($old = HomeContentModel::notification($id))) {   // edit: title, text, picture (new / remove / keep)
            $title = $this->text('title', 100);
            $msg   = $this->text('message', 500);
            if ($title === '') { $this->errors['title'] = $t['adm_err_required']; }
            if ($msg === '')   { $this->errors['message'] = $t['adm_err_required']; }
            $up = save_image_upload('image', 'offers', ['png', 'jpg', 'webp'], $t, false, IMG_SIZES['offers']);
            if ($up['error']) { $this->errors['image'] = $up['error']; }
            if ($this->errors) { delete_upload($up['path']); return; }
            $image = $up['path'] ?: (!empty($_POST['image_remove']) ? null : $old['image']);
            HomeContentModel::updateNotification($id, $title, $msg, $image);
            if ($old['image'] && $image !== $old['image']) { delete_upload($old['image']); }
            return $this->done($page, $t['adm_saved']);
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

    // ------------------------------------------------------------ ACCOUNTS (users, employees, admins)
    // Every change to someone's account (add, edit, role change, delete, restore) needs the admin's OWN password
    // (typed in the "Are you sure?" box). Nobody can see or set another person's password:
    // a new admin / employee gets a one-time password and must set their own on the first login.
    // The person whose account was changed sees who changed what, and when (account_changes).
    const CHANGE_LABELS = [
        'name' => 'adm_name', 'mobile' => 'adm_col_mobile', 'email' => 'adm_col_email',
        'area' => 'adm_col_area', 'gender' => 'adm_col_gender', 'status' => 'adm_status',
    ];

    private function saveAccount($page, $role, $action, $id)
    {
        $t    = $this->t;
        $me   = (int)$this->me['id'];
        $row  = $id ? AccountModel::find($role, $id) : null;
        $self = $row && $role !== 'user' && (int)$row['id'] === $me;      // the admin who is logged in now

        if ($action === 'delete' && $row) {
            if ($self) { $this->errors['form'] = $t['acc_err_self']; return; }
            if ($m = $this->myPasswordError()) { $this->errors['form'] = $m; return; }
            AccountModel::delete($role, $id, $me);                       // soft delete: row stays, marked deleted
            AccountChangeModel::add($role, $id, $me, [['chg_deleted', '', '']]);
            return $this->done($page, sprintf($t['acc_deleted'], $row['name']));
        }

        if ($action === 'restore' && ($gone = AccountModel::findDeleted($role, $id))) {   // undo a delete
            if (AccountModel::takenBy('mobile', $gone['mobile']) || AccountModel::takenBy('email', $gone['email'])) {
                $this->errors['form'] = sprintf($t['acc_err_restore_used'], $gone['name']);
                return;
            }
            if ($m = $this->myPasswordError()) { $this->errors['form'] = $m; return; }
            AccountModel::restore($role, $id);
            AccountChangeModel::add($role, $id, $me, [['chg_restored', '', '']]);
            return $this->done($page, sprintf($t['acc_restored'], $gone['name']));
        }

        if ($action === 'move' && $row && $role !== 'user') {            // employee <-> admin
            $to = $role === 'admin' ? 'employee' : 'admin';
            if ($self) { $this->errors['form'] = $t['acc_err_self']; return; }
            if ($to === 'admin' && (string)$row['email'] === '') { $this->errors['form'] = $t['acc_err_need_email']; return; }
            if ($m = $this->myPasswordError()) { $this->errors['form'] = $m; return; }
            AccountModel::changeRole($id, $to);
            AccountChangeModel::add($role, $id, $me, [['pf_role', $role, $to]]);
            return $this->done($page, sprintf($t['acc_moved_' . $to], $row['name']));
        }

        if ($action !== 'save' || ($id && !$row) || (!$id && $role === 'user')) {   // admin can't add users (they register themselves)
            return;
        }
        list($d, $e) = AccountValidator::info($_POST, $role, $id, $t);          // name, email (+ area, gender)
        $d['mobile'] = trim((string)($_POST['mobile'] ?? ''));
        $d['status'] = (string)($_POST['status'] ?? '');
        if ($m = LoginValidator::mobileError($d['mobile'], $t))                 { $e['mobile'] = $m; }
        elseif (AccountModel::takenBy('mobile', $d['mobile'], $role, $id))      { $e['mobile'] = $t['acc_err_mobile_used']; }
        if (!in_array($d['status'], self::STATUSES, true))                       { $e['status'] = $t['adm_err_required']; }
        elseif ($self && $d['status'] !== 'active')                              { $e['status'] = $t['acc_err_self']; }
        if (!$id) {                                                              // new admin / employee: one-time password
            $d['password'] = (string)($_POST['password'] ?? '');
            if ($m = LoginValidator::passwordError($d['password'], $t))         { $e['password'] = $m; }
        }
        $hasFile = isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;
        if (!$id && !$hasFile && $role !== 'user')                               { $e['photo'] = $t['ph_err_need']; }

        $changes = [];                                                           // edit: what is different from before
        if ($row) {
            foreach (AccountModel::infoColumns($role) as $c) {
                $before = $c === 'email' ? (string)$row[$c] : $row[$c];
                if ((string)$before !== (string)$d[$c]) { $changes[] = [self::CHANGE_LABELS[$c], (string)$before, (string)$d[$c]]; }
            }
            if ($row['status'] !== $d['status']) { $changes[] = ['adm_status', $row['status'], $d['status']]; }
            if ($hasFile) { $changes[] = ['adm_photo', '', '']; }
            if (!$e && !$changes) { return $this->done($page, $t['cm_none']); }
        }
        if ($e) { $this->errors = $e; return; }
        if ($m = $this->myPasswordError()) { $this->errors['form'] = $m; return; }

        $photo = null;
        if ($hasFile) {
            $up = save_image_upload('photo', 'photos', ['png', 'jpg', 'webp'], $t, true, PHOTO);
            if ($up['error']) { $this->errors['photo'] = $up['error']; return; }
            $photo = $up['path'];
        }
        try {
            if ($row) {
                AccountModel::update($role, $id, $d);
                if ($photo) { AccountModel::setPhoto($role, $id, $photo); delete_upload($row['photo']); }
                AccountChangeModel::add($role, $id, $me, $changes);
                return $this->done($page, sprintf($t['acc_saved_changes'], $d['name']));
            }
            AccountModel::createStaff($role, $d + ['photo' => $photo, 'created_by' => $me]);
        } catch (PDOException $ex) {
            delete_upload($photo);
            throw $ex;
        }
        return $this->done($page, sprintf($t['acc_created'], $d['name']));
    }

    // The admin's own password, typed in the "Are you sure?" box. '' = correct. 5 wrong in 10 minutes = wait.
    private function myPasswordError()
    {
        $t = $this->t;
        $wrong = array_filter($_SESSION['confirm_pw_fails'] ?? [], function ($ts) { return $ts > time() - 600; });
        $msg = '';
        if (count($wrong) >= 5) {
            $msg = $t['acc_err_my_password_many'];
        } elseif (!AccountModel::passwordMatches($this->me, (string)($_POST['my_password'] ?? ''))) {
            $wrong[] = time();
            $msg = $t[count($wrong) >= 5 ? 'acc_err_my_password_many' : 'acc_err_my_password'];
        }
        $_SESSION['confirm_pw_fails'] = array_values($wrong);
        return $msg;
    }

    // ------------------------------------------------------------ SERVICE PROVIDERS (electrician, plumber ...)
    private function saveProvider($action, $id)
    {
        $t   = $this->t;
        $me  = (int)$this->me['id'];
        $row = $id ? ProviderModel::find($id) : null;

        if ($action === 'delete' && $row) {
            ProviderModel::delete($id, $me);
            return $this->done('providers', sprintf($t['acc_deleted'], $row['name']));
        }
        if ($action === 'restore' && ($gone = ProviderModel::findDeleted($id))) {
            if (ProviderModel::mobileTaken($gone['mobile'], $id)) {
                $this->errors['form'] = sprintf($t['acc_err_restore_used'], $gone['name']);
                return;
            }
            ProviderModel::restore($id);
            return $this->done('providers', sprintf($t['acc_restored'], $gone['name']));
        }
        if ($action !== 'save' || ($id && !$row)) {
            return;
        }
        $d = [
            'service_id' => (int)($_POST['service_id'] ?? 0),
            'name'       => strtoupper(preg_replace('/\s+/', ' ', trim((string)($_POST['name'] ?? '')))),
            'mobile'     => trim((string)($_POST['mobile'] ?? '')),
            'age'        => (int)($_POST['age'] ?? 0),
            'gender'     => (string)($_POST['gender'] ?? ''),
            'area'       => (string)($_POST['area'] ?? ''),
            'address'    => $this->text('address', 200),
            'note'       => $this->text('note', 255) ?: null,
            'status'     => (string)($_POST['status'] ?? ''),
            'photo'      => $row['photo'] ?? null,
            'old_service_id' => $row['service_id'] ?? null,
        ];
        $e = [];
        if (!HomeContentModel::service($d['service_id']))                       { $e['service_id'] = $t['adm_err_required']; }
        if ($m = RegisterValidator::nameError($_POST['name'] ?? '', $t))         { $e['name'] = $m; }
        if ($m = LoginValidator::mobileError($d['mobile'], $t))                  { $e['mobile'] = $m; }
        elseif (ProviderModel::mobileTaken($d['mobile'], $id))                   { $e['mobile'] = $t['acc_err_mobile_used']; }
        if ($d['age'] < 18 || $d['age'] > 80)                                    { $e['age'] = $t['pv_err_age']; }
        if (!in_array($d['gender'], RegisterValidator::GENDERS, true))          { $e['gender'] = $t['err_gender']; }
        if (!in_array($d['area'], AreaModel::getAll(), true))                    { $e['area'] = $t['err_area']; }
        if (mb_strlen($d['address'], 'UTF-8') < 5)                               { $e['address'] = $t['pv_err_address']; }
        if (!in_array($d['status'], self::STATUSES, true))                       { $e['status'] = $t['adm_err_required']; }
        if ($e) { $this->errors = $e; return; }

        $up = save_image_upload('photo', 'photos', ['png', 'jpg', 'webp'], $t, false, PHOTO);
        if ($up['error']) { $this->errors['photo'] = $up['error']; return; }
        if ($up['path']) { $d['photo'] = $up['path']; }
        try {
            ProviderModel::save($id, $d, $me);
        } catch (PDOException $ex) {
            delete_upload($up['path']);
            throw $ex;
        }
        if ($up['path'] && $row) { delete_upload($row['photo']); }
        return $this->done('providers', sprintf($t[$row ? 'acc_saved_changes' : 'acc_created'], $d['name']));
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
        redirect($this->base . '?page=' . $page);
    }
}
