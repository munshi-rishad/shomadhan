<?php
// app/controllers/AdminController.php
// CONTROLLER for the admin AND employee dashboards (same pages, same look).
//   admin    -> admin-dashboard.php     all pages, can add / edit / delete
//   employee -> employee-dashboard.php  only the pages in ACCESS['employee'];
//               can change the pages in EMPLOYEE_EDITS and their own profile, the rest is view only
// The admin sets what users see on their home page:
//   ?page=banners       pictures at the top (slider)
//   ?page=welcome       coupon picture on the welcome page
//   ?page=popup         picture that pops up when the user opens the home page
//   ?page=site          contact numbers, social links, payment numbers, about / terms / privacy texts (admin only)
//   ?page=services      service categories with a PNG logo
//   ?page=sub-services  services inside a category (name, photo, price, rating ...)
//   ?page=popular       ranking for "জনপ্রিয় সেবা"
//   ?page=offers        notifications / latest offers sent to users
//   ?page=providers     service providers (electrician, plumber ...): add + edit + delete, linked to sub-services
//   ?page=users / employees / admins   lists with search, sort and pages
//                       users: edit + delete; employees and admins: add + edit + delete + move (employee <-> admin)
//                       every account change needs the admin's own password, and the person is told what changed
//   ?page=bookings      users' bookings: send a provider, work done, cancel (the user is told)
//   ?page=messages      chats with users (support + one per booking); every admin and employee can answer
//   ?page=topups        add-money requests (bKash / Nagad / Rocket transaction IDs): approve or reject
//   ?page=coupons       taka-off coupons: add, edit, show / hide, delete
//   ?page=changes       what an admin changed in MY account (who, when, what)
//   ?page=profile       the logged-in person's info, photo + change password
// Every form uses POST + CSRF token. After saving, the page reloads (refresh won't save twice).
//
// How this file is laid out:
//   handle()            entry point: picks the page, runs the POST (if any), loads the page's data, shows the layout
//   load...Page()       GET: the data one page needs
//   save...()           POST: one method per page; each ends with done() (= redirect) or leaves $this->errors set
//   small helpers       at the bottom (reading form fields, uploads, redirect after saving)

require_once APP_PATH . '/models/HomeContentModel.php';
require_once APP_PATH . '/models/AdminListModel.php';
require_once APP_PATH . '/helpers/upload.php';
require_once APP_PATH . '/models/AreaModel.php';
require_once APP_PATH . '/models/ProviderAccountModel.php';
require_once APP_PATH . '/models/ReportModel.php';
require_once APP_PATH . '/models/ActivityLogModel.php';
require_once APP_PATH . '/validators/AccountValidator.php';
require_once APP_PATH . '/controllers/ProfileController.php';
require_once APP_PATH . '/models/AccountChangeModel.php';
require_once APP_PATH . '/models/ProviderModel.php';
require_once APP_PATH . '/models/BookingModel.php';
require_once APP_PATH . '/models/WalletModel.php';
require_once APP_PATH . '/models/MessageModel.php';
require_once APP_PATH . '/controllers/MessageController.php';

class AdminController
{
    // Which pages each role can open. The first one opens after login (= first item of the menu; there is no overview page).
    // Nobody has the 'products' page any more: the same sub-services are on the sub-services page.
    const ACCESS = [
        'admin'    => ['bookings', 'messages', 'topups', 'payouts', 'coupons', 'services', 'sub-services', 'providers', 'popular', 'banners', 'popup', 'offers', 'welcome', 'site', 'users', 'employees', 'admins', 'reports', 'log', 'profile', 'changes'],
        // employee = office staff: does the daily work like the admin.
        // Admin only: staff accounts (employees, admins), site info (payment numbers) and the government papers list.
        'employee' => ['bookings', 'messages', 'topups', 'payouts', 'coupons', 'services', 'sub-services', 'providers', 'popular', 'banners', 'popup', 'offers', 'welcome', 'users', 'profile', 'changes'],
    ];
    // pages the employee may change (not only view)
    const EMPLOYEE_EDITS = ['bookings', 'messages', 'topups', 'payouts', 'coupons', 'services', 'sub-services', 'providers', 'popular', 'users', 'banners', 'popup', 'offers', 'welcome'];
    const ROLE_OF  = ['users' => 'user', 'employees' => 'employee', 'admins' => 'admin'];   // list page -> account role
    const STATUSES = ['active', 'inactive'];      // inactive = can't log in or do anything (no separate "block")
    const COLORS   = ['sand', 'sky', 'mint', 'rose', 'lilac', 'gray'];   // background colours a service card can have

    const PICTURE_TYPES = ['png', 'jpg', 'webp'];   // file types allowed for banners, popups, offers, photos ...

    private $t;           // language texts
    private $me;          // the logged-in admin / employee (accounts row)
    private $errors = []; // form errors from the POST, shown next to the fields
    private $base;        // this dashboard's address: admin-dashboard.php or employee-dashboard.php
    private $posted;      // [page, action] of the form being saved (for the work log)

    // ================================================================ ENTRY POINT

    // Shows one dashboard page for $role ('admin' or 'employee'). Called from public/*-dashboard.php.
    public function handle($role = 'admin')
    {
        $account = require_role($role);
        list($lang, $t) = lang_texts();
        $this->t  = $t;
        $this->me = $account;
        HomeContentModel::$by = (int)$account['id'];   // saved as created_by on new banners, services, offers ...
        $this->base = dashboard_url($role);

        $pages = self::ACCESS[$role];
        $page  = $this->queryChoice('page', $pages, $pages[0]);
        // admin can change everything; employee only the pages in EMPLOYEE_EDITS (+ own profile), the rest is view only
        $canEdit = $role === 'admin' || in_array($page, self::EMPLOYEE_EDITS, true);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($page, $canEdit);
        }

        // ---- data every page needs (menu, bell, red numbers, form errors)
        $bookingCounts = BookingModel::countByStatus();
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
            // red numbers in the left menu: what is waiting for the office
            'badges'  => [
                'bookings' => $bookingCounts['pending'],
                'messages' => MessageModel::unreadForStaff(),
                'topups'   => WalletModel::pendingTopups(),
            ],
            // government papers this page's work needs: one yellow line on top of the page
            'papers'  => self::papersFor($page, $lang),
        ];

        // ---- data for the page that is open
        $editId = (int)($_GET['edit'] ?? 0);    // ?edit=ID opens the edit form for that row
        switch ($page) {
            case 'bookings':
                $this->loadBookingsPage($data, $bookingCounts);
                break;
            case 'topups':
                $this->loadTopupsPage($data);
                break;
            case 'coupons':
                $data['coupons'] = WalletModel::coupons();
                $data['edit']    = $editId ? WalletModel::coupon($editId) : null;
                break;
            case 'messages':
                if (!$this->loadMessagesPage($data)) {
                    return;     // the page was already answered (live update for an open chat)
                }
                break;
            case 'banners':
                $data['banners'] = HomeContentModel::banners();
                break;
            case 'welcome':
                $data['welcome'] = HomeContentModel::setting('welcome_banner', HomeContentModel::DEFAULT_WELCOME_BANNER);
                $data['default'] = HomeContentModel::DEFAULT_WELCOME_BANNER;
                break;
            case 'site':
                $data['site'] = HomeContentModel::site();
                break;
            case 'popup':
                $data['popups'] = HomeContentModel::popups();
                $data['edit']   = $editId ? HomeContentModel::popup($editId) : null;
                break;
            case 'services':
                $data['services'] = HomeContentModel::services();
                $data['edit']     = $editId ? HomeContentModel::service($editId) : null;
                break;
            case 'sub-services':
                $this->loadSubServicesPage($data, $editId);
                break;
            case 'payouts':
                $data['q']          = $this->searchText();
                $data['onlyDue']    = !empty($_GET['due']);
                $data['rows']       = ProviderAccountModel::summary($data['q'], $data['onlyDue']);
                $data['commission'] = (int)HomeContentModel::site()['commission'];
                $data['provider']   = null;
                if ($pid = (int)($_GET['p'] ?? 0)) {        // one provider's statement
                    foreach (ProviderAccountModel::summary() as $row) {
                        if ((int)$row['id'] === $pid) {
                            $data['provider'] = $row;
                            $data['lines'] = ProviderAccountModel::statement($pid);
                        }
                    }
                }
                break;
            case 'reports':
                $this->loadReportsPage($data, $lang);
                break;
            case 'log':
                $data['staffId'] = (int)($_GET['staff'] ?? 0);
                $data['logPage'] = $this->queryChoice('of', self::ACCESS['admin'], '');
                list($rows, $total, $p, $pageCount) = ActivityLogModel::search($data['staffId'], $data['logPage'], $_GET['p'] ?? 1);
                $data['list']      = $this->pagedList($rows, $total, $p, $pageCount);
                $data['logStaff']  = ActivityLogModel::staffList();
                break;
            case 'popular':
                $data['items'] = $this->subServicesByRank();
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
                $this->loadListPage($data, $page, $lang, $canEdit, $editId);
                break;
            case 'changes':
                $data['changes'] = AccountChangeModel::forAccount($role, $account['id'], 50);
                AccountChangeModel::markSeen($role, $account['id']);        // opened = seen (the list still shows which were new)
                $data['unseen'] = 0;
                break;
            case 'profile':
                // after a failed save, open the same box again (info or password)
                $data['box'] = $this->errors ? (($_POST['action'] ?? '') === 'info' ? 'info' : 'password') : '';
                break;
        }
        view('admin/layout', $data);
    }

    // ---- POST: check the form token and the right to change this page, then save
    private function handlePost($page, $canEdit)
    {
        $t = $this->t;
        if (!csrf_check($_POST['csrf'] ?? '')) {
            $this->errors['form'] = $t['err_csrf'];
            return;
        }
        if (!$canEdit && $page !== 'profile') {     // employee: read-only (checked here, not only hidden in the page)
            $this->errors['form'] = $t['emp_view_only'];
            return;
        }
        $this->posted = [$page, (string)($_POST['action'] ?? '')];   // written to the work log by done()
        try {
            $this->save($page, (string)($_POST['action'] ?? ''));
        } catch (PDOException $ex) {
            app_log('error', 'admin ' . $page . ': ' . $ex->getMessage());
            $this->errors['form'] = $t['err_server'];
        }
    }

    // ================================================================ PAGE DATA (GET)

    // ---- Bookings: one tab per status, search, and the providers that can be sent for each open booking
    private function loadBookingsPage(array &$data, array $bookingCounts)
    {
        $data['tab'] = $this->queryChoice('tab', array_merge(BookingModel::STATUSES, ['complaints']), 'pending');
        $data['q']   = $this->searchText();
        list($rows, $total, $p, $pageCount) = BookingModel::adminList($data['tab'], $data['q'], $_GET['p'] ?? 1);

        foreach ($rows as $i => $row) {
            // closed booking: nobody can be sent any more. Open one: its providers + how busy each is that day
            $rows[$i]['choices'] = in_array($row['status'], ['pending', 'ongoing'], true) ? BookingModel::providersFor($row) : [];
        }
        $data['counts'] = $bookingCounts;
        $data['list']   = $this->pagedList($rows, $total, $p, $pageCount);
    }

    // ---- Add-money requests: one tab per status + search
    private function loadTopupsPage(array &$data)
    {
        $data['tab'] = $this->queryChoice('tab', ['pending', 'successful', 'rejected'], 'pending');
        $data['q']   = $this->searchText();
        list($rows, $total, $p, $pageCount) = WalletModel::topups($data['tab'], $data['q'], $_GET['p'] ?? 1);
        $data['list'] = $this->pagedList($rows, $total, $p, $pageCount);
    }

    // ---- Messages: chat list on the left, the open chat (?c=ID) on the right.
    // Returns false when the answer was already sent (live update of an open chat), so nothing else is shown.
    private function loadMessagesPage(array &$data)
    {
        // "message the user" button on a booking: open (or create) that booking's chat
        if (!empty($_GET['booking']) && ($booking = BookingModel::find((int)$_GET['booking']))) {
            $chat = MessageModel::open($booking['user_id'], $booking['id']);
            redirect($this->base . '?page=messages&c=' . (int)$chat['id']);
        }

        $conv = !empty($_GET['c']) ? MessageModel::find((int)$_GET['c']) : null;
        if ($conv && !empty($_GET['live'])) {      // the open chat asks for new messages every few seconds
            MessageController::live($conv, 'staff', $this->t);
            return false;
        }
        if ($conv) {
            MessageModel::markSeen($conv['id'], 'staff');
            $data['badges']['messages'] = MessageModel::unreadForStaff();   // the red number drops right away
        }
        $data['filter'] = $this->queryChoice('f', MessageModel::STAFF_FILTERS, 'all');
        $data['chipCounts'] = MessageModel::countsForStaff();
        $data['q']      = mb_substr($this->searchText(), 0, 60, 'UTF-8');
        $data['chats']  = MessageModel::inbox($data['filter'], $data['q']);
        $data['conv']   = $conv;
        $data['msgs']   = $conv ? MessageModel::messages($conv['id']) : [];
        $data['draft']  = $this->errors ? (string)($_POST['body'] ?? '') : '';   // keep the text if sending failed
        return true;
    }

    // ---- Sub-services: list (filtered by ?service=ID) + add / edit form with the providers who give it
    private function loadSubServicesPage(array &$data, $editId)
    {
        $data['services']  = HomeContentModel::services();
        $data['filter']    = (int)($_GET['service'] ?? 0);
        $data['items']     = HomeContentModel::subServices($data['filter']);
        $data['edit']      = $editId ? HomeContentModel::subService($editId) : null;
        $data['providers'] = ProviderModel::choices();
        $data['picked']    = $data['edit'] ? ProviderModel::idsFor($editId) : [];
        $data['pvNames']   = ProviderModel::namesBySub();
    }

    // ---- Popular ranking: all sub-services, ranked ones first (1, 2, 3 ...), then the rest by id
    private function subServicesByRank()
    {
        $items = HomeContentModel::subServices();
        usort($items, function ($a, $b) {
            $rankA = $a['popular_rank'] === null ? PHP_INT_MAX : (int)$a['popular_rank'];
            $rankB = $b['popular_rank'] === null ? PHP_INT_MAX : (int)$b['popular_rank'];
            return $rankA <=> $rankB ?: $a['id'] <=> $b['id'];
        });
        return $items;
    }

    // ---- Users / employees / admins / providers: table with search, filters, sort and pages (+ edit form)
    private function loadListPage(array &$data, $page, $lang, $canEdit, $editId)
    {
        $filters = [];
        foreach (AdminListModel::filterKeys($page) as $key) {
            $filters[$key] = is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';     // ?x[]= (array) = ignored
        }
        list($rows, $total, $p, $pageCount, $sort, $dir) = AdminListModel::find(
            $page, $lang, $_GET['q'] ?? '', $_GET['sort'] ?? 'id', $_GET['dir'] ?? 'asc', $_GET['p'] ?? 1, $filters);

        $data['body'] = 'list';
        // the add / edit form (only for those who may change this page)
        if (isset(self::ROLE_OF[$page]) && $canEdit) {
            $data['role'] = self::ROLE_OF[$page];
            $data['edit'] = $editId ? AccountModel::find(self::ROLE_OF[$page], $editId) : null;
        }
        if ($page === 'providers' && $canEdit) {
            $data['role'] = 'provider';
            $data['edit'] = $editId ? ProviderModel::find($editId) : null;
            $data['leaves'] = $data['edit'] ? ProviderModel::leaves($editId) : [];     // leave days (edit page)
        }
        $data['list'] = $this->pagedList($rows, $total, $p, $pageCount) + [
            'sort'    => $sort,
            'dir'     => $dir,
            'q'       => $this->searchText(),
            'cols'    => AdminListModel::sortKeys($page, $lang),
            'filters' => $filters,
        ];
        $data['areas']    = AreaModel::getAll();
        $data['onLeave']  = $page === 'providers' ? ProviderModel::onLeaveToday() : [];
        $data['services'] = in_array($page, ['products', 'providers'], true) ? HomeContentModel::services() : [];
    }

    // ================================================================ SAVE (POST)

    // Sends the form to the save method of the open page. Unknown page / action = nothing happens.
    private function save($page, $action)
    {
        $id = (int)($_POST['id'] ?? 0);     // the row being changed (0 = new)
        switch ($page) {
            case 'profile':      return $this->saveProfile($action);
            case 'providers':    return $this->saveProvider($action, $id);
            case 'bookings':     return $this->saveBooking($action, $id);
            case 'topups':       return $this->saveTopup($action, $id);
            case 'coupons':      return $this->saveCoupon($action, $id);
            case 'payouts':      return $this->savePayout($action, $id);
            case 'messages':     return $this->sendMessage($action);
            case 'banners':      return $this->saveBanner($action, $id);
            case 'welcome':      return $this->saveWelcome($action);
            case 'site':         return $this->saveSiteInfo($action);
            case 'popup':        return $this->savePopup($action, $id);
            case 'services':     return $this->saveService($action, $id);
            case 'sub-services': return $this->saveSubService($action, $id);
            case 'popular':      return $this->savePopular($action);
            case 'offers':       return $this->saveOffer($action, $id);
            case 'users':
            case 'employees':
            case 'admins':
                return $this->saveAccount($page, self::ROLE_OF[$page], $action, $id);
        }
    }

    // ---- My profile: name / email, photo, password (the same code as the user's own profile page)
    private function saveProfile($action)
    {
        $t = $this->t;
        if ($action === 'info') {
            $this->errors = ProfileController::saveInfo($this->me, $_POST, $t);
            $message = $t['pf_saved'];
        } elseif ($action === 'photo' || $action === 'photo_remove') {
            $this->errors = ProfileController::savePhoto($this->me, $action === 'photo_remove', $t);
            $message = $t['ph_saved'];
        } elseif ($action === 'password') {
            $this->errors = ProfileController::changePassword($this->me, $_POST, $t);
            $message = $t['cp_done'];
        } else {
            return;
        }
        if (!$this->errors) {
            return $this->done('profile#details', $message);   // back to my info, like the user's profile
        }
    }

    // ---- Messages: answer a chat as the office
    private function sendMessage($action)
    {
        if ($action !== 'send') {
            return;
        }
        $conv = MessageModel::find((int)($_POST['c'] ?? 0));
        $this->errors = MessageController::sendError($conv, $this->t);
        if ($this->errors) {
            return;
        }
        MessageModel::send($conv['id'], 'staff', $this->me['id'], MessageController::body());
        return $this->done('messages&c=' . (int)$conv['id'], '');
    }

    // ---- Banners (home page slider): add, edit title / show-hide, move up / down, delete
    private function saveBanner($action, $id)
    {
        $t = $this->t;
        if ($action === 'add') {
            $up = $this->uploadPicture('image', 'banners', true);
            if ($up['error']) {
                $this->errors['image'] = $up['error'];
                return;
            }
            HomeContentModel::addBanner($up['path'], $this->text('title', 100));
            return $this->done('banners', $t['adm_saved']);
        }
        if ($action === 'update' && HomeContentModel::banner($id)) {
            HomeContentModel::updateBanner($id, $this->text('title', 100), $this->checkbox('is_active'));
            return $this->done('banners', $t['adm_saved']);
        }
        if ($action === 'move') {
            return $this->moveRow('banners', $id);
        }
        if ($action === 'delete' && ($banner = HomeContentModel::banner($id))) {
            HomeContentModel::deleteBanner($id);
            delete_upload($banner['image']);
            return $this->done('banners', $t['adm_deleted']);
        }
    }

    // ---- ▲ ▼ arrows on the banner / service list
    private function moveRow($page, $id)
    {
        HomeContentModel::move($page, $id, ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down');
        return $this->done($page, '');
    }

    // ---- Welcome page coupon picture
    // A new upload replaces the old one; "default" puts back the built-in picture.
    private function saveWelcome($action)
    {
        if ($action !== 'save' && $action !== 'default') {
            return;
        }
        $old = HomeContentModel::setting('welcome_banner', HomeContentModel::DEFAULT_WELCOME_BANNER);
        $new = HomeContentModel::DEFAULT_WELCOME_BANNER;
        if ($action === 'save') {
            $up = $this->uploadPicture('image', 'welcome', true);
            if ($up['error']) {
                $this->errors['image'] = $up['error'];
                return;
            }
            $new = $up['path'];
        }
        HomeContentModel::setSetting('welcome_banner', $new);
        if ($old !== $new) {
            delete_upload($old);       // only deletes uploaded files, never the built-in one
        }
        return $this->done('welcome', $this->t['adm_saved']);
    }

    // ---- Site info: contact, social links, payment numbers, about / mission / terms / privacy texts
    // (shown in the footer and the info pages). The field list comes from data/site-defaults.php.
    private function saveSiteInfo($action)
    {
        if ($action !== 'save') {
            return;
        }
        $t = $this->t;
        $site = [];
        foreach (array_keys(require APP_PATH . '/data/site-defaults.php') as $key) {
            $value = trim(str_replace("\r\n", "\n", (string)($_POST[$key] ?? '')));
            if (preg_match('/^(about|mission|terms|privacy)_/', $key)) {
                $site[$key] = mb_substr($value, 0, 20000, 'UTF-8');          // long text: keeps its lines
            } else {
                $site[$key] = mb_substr(preg_replace('/\s+/u', ' ', $value), 0, 200, 'UTF-8');   // one line
            }
        }

        // every field may be empty; what is filled in must be valid
        foreach (['phone', 'whatsapp', 'pay_bkash', 'pay_nagad', 'pay_rocket'] as $key) {
            if ($site[$key] !== '' && ($m = LoginValidator::mobileError($site[$key], $t))) {
                $this->errors[$key] = $m;
            }
        }
        // business rules: whole numbers (empty = the built-in value)
        if ($site['commission'] !== '' && (!ctype_digit($site['commission']) || (int)$site['commission'] > 50)) {
            $this->errors['commission'] = $t['site_err_commission'];
        }
        if ($site['complaint_days'] !== '' && (!ctype_digit($site['complaint_days']) || (int)$site['complaint_days'] < 1 || (int)$site['complaint_days'] > 30)) {
            $this->errors['complaint_days'] = $t['site_err_days'];
        }
        if ($site['email'] !== '' && !filter_var($site['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = $t['err_email'];
        }
        foreach (['facebook', 'youtube', 'instagram', 'linkedin'] as $key) {
            $link = $site[$key];
            if ($link !== '' && (!filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $link))) {
                $this->errors[$key] = $t['adm_err_link'];
            }
        }
        if ($this->errors) {
            return;
        }
        HomeContentModel::saveSite($site);
        return $this->done('site', $t['adm_saved']);
    }

    // ---- Popup picture on the home page: add, edit (title, link, new picture), show / hide, delete
    private function savePopup($action, $id)
    {
        $t = $this->t;
        if ($action === 'add') {
            $link = $this->popupLink();
            if ($link === null) {
                return;
            }
            $up = $this->uploadPicture('image', 'popups', true);
            if ($up['error']) {
                $this->errors['image'] = $up['error'];
                return;
            }
            HomeContentModel::addPopup($up['path'], $this->text('title', 100), $link);
            return $this->done('popup', $t['adm_saved']);
        }
        if ($action === 'save' && ($old = HomeContentModel::popup($id))) {     // edit: the new picture is optional
            $link = $this->popupLink();
            if ($link === null) {
                return;
            }
            $up = $this->uploadPicture('image', 'popups', false);
            if ($up['error']) {
                $this->errors['image'] = $up['error'];
                return;
            }
            HomeContentModel::updatePopup($id, $up['path'] ?: $old['image'], $this->text('title', 100), $link);
            if ($up['path']) {
                delete_upload($old['image']);
            }
            return $this->done('popup', $t['adm_saved']);
        }
        if ($action === 'toggle' && ($popup = HomeContentModel::popup($id))) {
            HomeContentModel::setPopupActive($id, $popup['is_active'] ? 0 : 1);
            return $this->done('popup', $t['adm_saved']);
        }
        if ($action === 'delete' && ($popup = HomeContentModel::popup($id))) {
            HomeContentModel::deletePopup($id);
            delete_upload($popup['image']);
            return $this->done('popup', $t['adm_deleted']);
        }
    }

    // The popup's optional link (http / https). Returns null and sets the error when it is not a valid link.
    private function popupLink()
    {
        $link = trim((string)($_POST['link'] ?? ''));
        if ($link === '') {
            return $link;
        }
        if (strlen($link) > 255 || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $link)) {
            $this->errors['link'] = $this->t['adm_err_link'];
            return null;
        }
        return $link;
    }

    // ---- Service categories: add / edit (names, colour, PNG logo), move up / down, delete
    private function saveService($action, $id)
    {
        $t = $this->t;
        if ($action === 'save') {
            $old = $id ? HomeContentModel::service($id) : null;
            if ($id && !$old) {
                return $this->done('services', '');      // deleted meanwhile
            }
            $service = [
                'name_bn'   => $this->text('name_bn', 60),
                'name_en'   => $this->text('name_en', 60),
                'color'     => in_array($_POST['color'] ?? '', self::COLORS, true) ? $_POST['color'] : 'mint',
                'is_active' => $this->checkbox('is_active'),
                'icon'      => $old['icon'] ?? 'assets/images/icons/service.svg',   // no logo given = default icon
            ];
            $this->requireFilled(['name_bn' => $service['name_bn'], 'name_en' => $service['name_en']]);
            // logo: PNG only, optional
            $up = $this->uploadIfValid('icon', 'services', ['png'], IMG_SIZES['services']);
            if (!$up) {
                return;
            }
            if ($up['path']) {
                if ($old) {
                    delete_upload($old['icon']);
                }
                $service['icon'] = $up['path'];
            }
            HomeContentModel::saveService($id, $service);
            return $this->done('services', $t['adm_saved']);
        }
        if ($action === 'move') {
            return $this->moveRow('services', $id);
        }
        if ($action === 'delete' && ($service = HomeContentModel::service($id))) {
            // A category with services inside can't be deleted (so no service is lost by one click).
            // Hide it or empty it first. Deleted providers count too: they keep their history.
            if ($n = count(HomeContentModel::subServices($id))) {
                $this->errors['form'] = sprintf($t['adm_err_service_has_subs'], $n);
                return;
            }
            if ($n = self::countWhere('providers', 'service_id', $id)) {
                $this->errors['form'] = sprintf($t['adm_err_service_has_providers'], $n);
                return;
            }
            HomeContentModel::deleteService($id);
            delete_upload($service['icon']);
            return $this->done('services', $t['adm_deleted']);
        }
    }

    // ---- Sub-services: add / edit (names, price, rating, photo, providers), delete
    private function saveSubService($action, $id)
    {
        $t = $this->t;
        // back to the same category filter the list had
        $backTo = 'sub-services&service=' . (int)($_GET['service'] ?? 0);

        if ($action === 'save') {
            $old = $id ? HomeContentModel::subService($id) : null;
            if ($id && !$old) {
                return $this->done('sub-services', '');      // deleted meanwhile
            }
            $sub = [
                'service_id'   => (int)($_POST['service_id'] ?? 0),
                'name_bn'      => $this->text('name_bn', 80),
                'name_en'      => $this->text('name_en', 80),
                'detail_bn'    => $this->text('detail_bn', 120) ?: null,
                'detail_en'    => $this->text('detail_en', 120) ?: null,
                'price'        => max(0, min(9999999, (int)($_POST['price'] ?? 0))),
                'rating'       => min(5, max(0, round((float)($_POST['rating'] ?? 0), 1))),
                'rating_count' => max(0, min(9999999, (int)($_POST['rating_count'] ?? 0))),
                'popular_rank' => $old['popular_rank'] ?? null,   // set on the "popular" page with ▲ ▼
                'is_active'    => $this->checkbox('is_active'),
                'image'        => $old['image'] ?? null,
            ];
            if (!HomeContentModel::service($sub['service_id'])) {
                $this->errors['service_id'] = $t['adm_err_required'];
            }
            $this->requireFilled(['name_bn' => $sub['name_bn'], 'name_en' => $sub['name_en']]);
            $up = $this->uploadIfValid('image', 'sub-services', self::PICTURE_TYPES, IMG_SIZES['sub-services']);
            if (!$up) {
                return;
            }
            if ($up['path']) {
                if ($old) {
                    delete_upload($old['image']);
                }
                $sub['image'] = $up['path'];
            }
            $subId = HomeContentModel::saveSubService($id, $sub);
            ProviderModel::setFor($subId, $sub['service_id'], (array)($_POST['providers'] ?? []));   // who gives this service
            return $this->done($backTo, $t['adm_saved']);
        }
        if ($action === 'delete' && ($sub = HomeContentModel::subService($id))) {
            // booked before = keep it for the booking history; hide it instead
            if ($n = self::countWhere('bookings', 'sub_service_id', $id)) {
                $this->errors['form'] = sprintf($t['adm_err_sub_has_bookings'], $n);
                return;
            }
            HomeContentModel::deleteSubService($id);
            delete_upload($sub['image']);
            return $this->done($backTo, $t['adm_deleted']);
        }
    }

    // ---- Popular ranking: rank[ID] = number (empty or 0 = not popular)
    private function savePopular($action)
    {
        if ($action !== 'save') {
            return;
        }
        foreach ((array)($_POST['rank'] ?? []) as $subId => $rank) {
            $rank = trim((string)$rank);
            $isRanked = ctype_digit($rank) && (int)$rank > 0;
            HomeContentModel::setPopularRank((int)$subId, $isRanked ? min(9999, (int)$rank) : null);
        }
        return $this->done('popular', $this->t['adm_saved']);
    }

    // ---- Offers / notifications: send a new one, edit (title, text, picture new / remove / keep), show / hide, delete
    private function saveOffer($action, $id)
    {
        $t = $this->t;
        if ($action === 'add') {
            list($title, $message, $up) = $this->offerForm();
            if (!$up) {
                return;
            }
            HomeContentModel::addNotification($title, $message, $up['path']);
            return $this->done('offers', $t['adm_offer_sent']);
        }
        if ($action === 'save' && ($old = HomeContentModel::notification($id))) {
            list($title, $message, $up) = $this->offerForm();
            if (!$up) {
                return;
            }
            $image = $up['path'] ?: (!empty($_POST['image_remove']) ? null : $old['image']);
            HomeContentModel::updateNotification($id, $title, $message, $image);
            if ($old['image'] && $image !== $old['image']) {
                delete_upload($old['image']);
            }
            return $this->done('offers', $t['adm_saved']);
        }
        if ($action === 'toggle' && ($offer = HomeContentModel::notification($id))) {
            HomeContentModel::setNotificationActive($id, $offer['is_active'] ? 0 : 1);
            return $this->done('offers', $t['adm_saved']);
        }
        if ($action === 'delete' && ($offer = HomeContentModel::notification($id))) {
            HomeContentModel::deleteNotification($id);
            delete_upload($offer['image']);
            return $this->done('offers', $t['adm_deleted']);
        }
    }

    // The offer form: [title, message, upload]. Upload is null when something is wrong (errors are set).
    private function offerForm()
    {
        $title   = $this->text('title', 100);
        $message = $this->text('message', 500);
        $this->requireFilled(['title' => $title, 'message' => $message]);
        $up = $this->uploadIfValid('image', 'offers', self::PICTURE_TYPES, IMG_SIZES['offers']);
        return [$title, $message, $up];
    }

    // ================================================================ ACCOUNTS (users, employees, admins)
    // Every change to someone's account (add, edit, role change, delete, restore) needs the admin's OWN password
    // (typed in the "Are you sure?" box). Nobody can see or set another person's password:
    // a new admin / employee gets a one-time password and must set their own on the first login.
    // The person whose account was changed sees who changed what, and when (account_changes).

    // account column -> text key used in the "what changed" list
    const CHANGE_LABELS = [
        'name' => 'adm_name', 'mobile' => 'adm_col_mobile', 'email' => 'adm_col_email',
        'area' => 'adm_col_area', 'gender' => 'adm_col_gender', 'status' => 'adm_status',
    ];

    // ---- Accounts: delete, restore, move (employee <-> admin) or save the add / edit form
    private function saveAccount($page, $role, $action, $id)
    {
        $row  = $id ? AccountModel::find($role, $id) : null;
        $self = $row && $role !== 'user' && (int)$row['id'] === (int)$this->me['id'];   // the admin who is logged in now

        if ($action === 'delete' && $row) {
            return $this->deleteAccount($page, $role, $row, $self);
        }
        if ($action === 'restore' && ($gone = AccountModel::findDeleted($role, $id))) {
            return $this->restoreAccount($page, $role, $gone);
        }
        if ($action === 'reset_pw' && $row) {
            return $this->resetPassword($page, $role, $row, $self);
        }
        if ($action === 'move' && $row && $role !== 'user') {
            return $this->moveAccount($page, $role, $row, $self);
        }
        // admin can't add users (they register themselves)
        if ($action !== 'save' || ($id && !$row) || (!$id && $role === 'user')) {
            return;
        }
        return $this->saveAccountForm($page, $role, $id, $row, $self);
    }

    // ---- Delete an account (soft delete: the row stays, marked deleted). Nobody can delete themselves.
    private function deleteAccount($page, $role, array $row, $self)
    {
        $me = (int)$this->me['id'];
        if ($self) {
            $this->errors['form'] = $this->t['acc_err_self'];
            return;
        }
        if (!$this->myPasswordConfirmed()) {
            return;
        }
        $id = (int)$row['id'];
        AccountModel::delete($role, $id, $me);
        AccountChangeModel::add($role, $id, $me, [['chg_deleted', '', '']]);
        return $this->done($page, sprintf($this->t['acc_deleted'], $row['name']));
    }

    // ---- Forgotten password: a new one-time password, shown once here (the office tells it on the phone).
    // On the next login the person must set their own. Not for myself (my profile page has "change password").
    private function resetPassword($page, $role, array $row, $self)
    {
        if ($self) {
            $this->errors['form'] = $this->t['acc_err_self'];
            return;
        }
        if (!$this->myPasswordConfirmed()) {
            return;
        }
        $password = AccountModel::resetPassword($role, (int)$row['id']);
        AccountChangeModel::add($role, (int)$row['id'], (int)$this->me['id'], [['chg_pw_reset', '', '']]);
        return $this->done($page, sprintf($this->t['acc_pw_reset_done'], $row['name'], $password));
    }

    // ---- Undo a delete (not possible when someone else uses that mobile / email now)
    private function restoreAccount($page, $role, array $gone)
    {
        $t = $this->t;
        if (AccountModel::takenBy('mobile', $gone['mobile']) || AccountModel::takenBy('email', $gone['email'])) {
            $this->errors['form'] = sprintf($t['acc_err_restore_used'], $gone['name']);
            return;
        }
        if (!$this->myPasswordConfirmed()) {
            return;
        }
        $id = (int)$gone['id'];
        AccountModel::restore($role, $id);
        AccountChangeModel::add($role, $id, (int)$this->me['id'], [['chg_restored', '', '']]);
        return $this->done($page, sprintf($t['acc_restored'], $gone['name']));
    }

    // ---- Make an employee an admin, or an admin an employee. Nobody can move themselves.
    private function moveAccount($page, $role, array $row, $self)
    {
        $to = $role === 'admin' ? 'employee' : 'admin';
        if ($self) {
            $this->errors['form'] = $this->t['acc_err_self'];
            return;
        }
        if (!$this->myPasswordConfirmed()) {
            return;
        }
        $id = (int)$row['id'];
        AccountModel::changeRole($id, $to);
        AccountChangeModel::add($role, $id, (int)$this->me['id'], [['pf_role', $role, $to]]);
        return $this->done($page, sprintf($this->t['acc_moved_' . $to], $row['name']));
    }

    // ---- Add a new employee / admin, or edit any account ($row = the account before the edit)
    private function saveAccountForm($page, $role, $id, $row, $self)
    {
        $t  = $this->t;
        $me = (int)$this->me['id'];
        list($info, $errors) = $this->accountForm($role, $id, $row, $self);
        // photo is optional (can be added later from the edit form or the person's own profile)
        $hasFile = isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;

        $changes = [];                      // edit: what is different from before
        if ($row) {
            $changes = $this->accountChanges($role, $row, $info, $hasFile);
            if (!$errors && !$changes) {
                return $this->done($page, $t['cm_none']);
            }
        }
        if ($errors) {
            $this->errors = $errors;
            return;
        }
        if (!$this->myPasswordConfirmed()) {
            return;
        }

        $photo = null;
        if ($hasFile) {
            $up = $this->uploadPicture('photo', 'photos', true, PHOTO);
            if ($up['error']) {
                $this->errors['photo'] = $up['error'];
                return;
            }
            $photo = $up['path'];
        }
        try {
            if ($row) {
                AccountModel::update($role, $id, $info);
                if ($photo) {
                    AccountModel::setPhoto($role, $id, $photo);
                    delete_upload($row['photo']);
                }
                AccountChangeModel::add($role, $id, $me, $changes);
                return $this->done($page, sprintf($t['acc_saved_changes'], $info['name']));
            }
            AccountModel::createStaff($role, $info + ['photo' => $photo, 'created_by' => $me]);
        } catch (PDOException $ex) {
            delete_upload($photo);      // the account was not saved: don't keep its photo
            throw $ex;
        }
        return $this->done($page, sprintf($t['acc_created'], $info['name']));
    }

    // Reads and checks the account form. Returns [values, errors].
    private function accountForm($role, $id, $row, $self)
    {
        $t = $this->t;
        list($info, $errors) = AccountValidator::info($_POST, $role, $id, $t);      // name, email (+ area, gender)
        // employee editing: email stays (a new email + "forgot password" would take the account)
        if ($row && $this->me['role'] !== 'admin') {
            $info['email'] = $row['email'];
            unset($errors['email']);
        }
        $info['mobile'] = trim((string)($_POST['mobile'] ?? ''));
        $info['status'] = (string)($_POST['status'] ?? '');

        if ($m = LoginValidator::mobileError($info['mobile'], $t)) {
            $errors['mobile'] = $m;
        } elseif (AccountModel::takenBy('mobile', $info['mobile'], $role, $id)) {
            $errors['mobile'] = $t['acc_err_mobile_used'];
        }
        if (!in_array($info['status'], self::STATUSES, true)) {
            $errors['status'] = $t['adm_err_required'];
        } elseif ($self && $info['status'] !== 'active') {
            $errors['status'] = $t['acc_err_self'];       // can't lock yourself out
        }
        if (!$id) {                                        // new admin / employee: one-time password
            $info['password'] = (string)($_POST['password'] ?? '');
            if ($m = LoginValidator::passwordError($info['password'], $t)) {
                $errors['password'] = $m;
            }
        }
        return [$info, $errors];
    }

    // What the edit changes, as [label key, before, after] rows for account_changes.
    private function accountChanges($role, array $row, array $info, $hasFile)
    {
        $changes = [];
        foreach (AccountModel::infoColumns($role) as $col) {
            $before = $col === 'email' ? (string)$row[$col] : $row[$col];
            if ((string)$before !== (string)$info[$col]) {
                $changes[] = [self::CHANGE_LABELS[$col], (string)$before, (string)$info[$col]];
            }
        }
        if ($row['status'] !== $info['status']) {
            $changes[] = ['adm_status', $row['status'], $info['status']];
        }
        if ($hasFile) {
            $changes[] = ['adm_photo', '', ''];
        }
        return $changes;
    }

    // The admin's own password, typed in the "Are you sure?" box. '' = correct. 5 wrong in 10 minutes = wait.
    private function myPasswordError()
    {
        $t = $this->t;
        $wrong = array_filter($_SESSION['confirm_pw_fails'] ?? [], function ($ts) {
            return $ts > time() - 600;
        });
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

    // true when the admin's own password is right; otherwise the form error is set and it returns false
    private function myPasswordConfirmed()
    {
        $m = $this->myPasswordError();
        if ($m) {
            $this->errors['form'] = $m;
            return false;
        }
        return true;
    }

    // how many rows of $table point at this id (table + column are fixed names from this file, never from the browser)
    private static function countWhere($table, $col, $id)
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE `' . $col . '` = ?');
        $st->execute([(int)$id]);
        return (int)$st->fetchColumn();
    }

    // ================================================================ SERVICE PROVIDERS (electrician, plumber ...)

    // ---- Providers: delete, restore or save the add / edit form (no password box: providers don't log in)
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
            if (ProviderModel::mobileTaken($gone['mobile'], $id) || ProviderModel::emailTaken($gone['email'], $id)) {
                $this->errors['form'] = sprintf($t['acc_err_restore_used'], $gone['name']);
                return;
            }
            ProviderModel::restore($id);
            return $this->done('providers', sprintf($t['acc_restored'], $gone['name']));
        }
        if (($action === 'leave_add' || $action === 'leave_delete') && $row) {
            return $this->saveLeave($action, $row);
        }
        if ($action !== 'save' || ($id && !$row)) {
            return;
        }

        $provider = $this->providerForm($row);
        $errors = $this->providerErrors($provider, $id);
        if ($errors) {
            $this->errors = $errors;
            return;
        }

        $up = $this->uploadPicture('photo', 'photos', false, PHOTO);
        if ($up['error']) {
            $this->errors['photo'] = $up['error'];
            return;
        }
        if ($up['path']) {
            $provider['photo'] = $up['path'];
        }
        try {
            ProviderModel::save($id, $provider, $me);
        } catch (PDOException $ex) {
            delete_upload($up['path']);
            throw $ex;
        }
        if ($up['path'] && $row) {
            delete_upload($row['photo']);       // the new photo replaced the old one
        }
        return $this->done('providers', sprintf($t[$row ? 'acc_saved_changes' : 'acc_created'], $provider['name']));
    }

    // The provider form, cleaned up. $row = the provider before the edit (null = new).
    private function providerForm($row)
    {
        return [
            'service_id' => (int)($_POST['service_id'] ?? 0),
            'name'       => strtoupper(preg_replace('/\s+/', ' ', trim((string)($_POST['name'] ?? '')))),
            'mobile'     => trim((string)($_POST['mobile'] ?? '')),
            'email'      => strtolower(trim((string)($_POST['email'] ?? ''))) ?: null,
            'age'        => (int)($_POST['age'] ?? 0),
            'gender'     => (string)($_POST['gender'] ?? ''),
            'area'       => (string)($_POST['area'] ?? ''),
            'address'    => $this->text('address', 200),
            'note'       => $this->text('note', 255) ?: null,
            'status'     => (string)($_POST['status'] ?? ''),
            'photo'      => $row['photo'] ?? null,
            'old_service_id' => $row['service_id'] ?? null,
        ];
    }

    // Checks the provider form. Returns field => error message (empty = all good).
    private function providerErrors(array $provider, $id)
    {
        $t = $this->t;
        $errors = [];
        if (!HomeContentModel::service($provider['service_id'])) {
            $errors['service_id'] = $t['adm_err_required'];
        }
        if ($m = RegisterValidator::nameError($_POST['name'] ?? '', $t)) {
            $errors['name'] = $m;
        }
        if ($m = LoginValidator::mobileError($provider['mobile'], $t)) {
            $errors['mobile'] = $m;
        } elseif (ProviderModel::mobileTaken($provider['mobile'], $id)) {
            $errors['mobile'] = $t['acc_err_mobile_used'];
        }
        if ($m = RegisterValidator::emailError((string)$provider['email'], $t)) {
            $errors['email'] = $m;
        } elseif (ProviderModel::emailTaken($provider['email'], $id)) {
            $errors['email'] = $t['acc_err_email_used'];
        }
        if ($provider['age'] < 18 || $provider['age'] > 80) {
            $errors['age'] = $t['pv_err_age'];
        }
        if (!in_array($provider['gender'], RegisterValidator::GENDERS, true)) {
            $errors['gender'] = $t['err_gender'];
        }
        if (!in_array($provider['area'], AreaModel::getAll(), true)) {
            $errors['area'] = $t['err_area'];
        }
        if (mb_strlen($provider['address'], 'UTF-8') < 5) {
            $errors['address'] = $t['pv_err_address'];
        }
        if (!in_array($provider['status'], self::STATUSES, true)) {
            $errors['status'] = $t['adm_err_required'];
        }
        return $errors;
    }

    // ================================================================ BOOKINGS, ADD-MONEY, COUPONS

    // ---- Bookings: send a provider / work done / cancel. The user is told under the bell.
    private function saveBooking($action, $id)
    {
        $t = $this->t;
        $booking = BookingModel::find($id);
        if (!$booking) {
            $this->errors['form'] = $t['bk_err_gone'];
            return;
        }
        $code = BookingModel::code($booking['id']);

        if ($action === 'assign') {
            // only a provider from the list for this booking's service
            $providerId = (int)($_POST['provider_id'] ?? 0);
            $allowedIds = array_map('intval', array_column(BookingModel::providersFor($booking), 'id'));
            if (!in_array($providerId, $allowedIds, true)) {
                $this->errors['form'] = $t['bk_err_provider'];
                return;
            }
            $ok        = BookingModel::assign($booking['id'], $providerId, $this->me['id']);
            $newStatus = 'ongoing';
            $message   = sprintf($t['bk_adm_assigned'], $code);
        } elseif ($action === 'complete') {
            // the real bill: 0 - 500000 taka; a changed bill needs a reason (the user sees it)
            $final = trim((string)($_POST['final'] ?? ''));
            $note  = $this->text('final_note', 200);
            if (!preg_match('/^\d{1,6}$/', $final) || (int)$final > 500000) {
                $this->errors['form'] = $t['bk_err_final'];
                return;
            }
            if ((int)$final !== (int)$booking['total'] && $note === '') {
                $this->errors['form'] = $t['bk_err_final_note'];
                return;
            }
            $ok        = BookingModel::complete($booking, $this->me['id'], (int)$final, $note !== '' ? $note : null);
            $newStatus = 'completed';
            $message   = sprintf($t['bk_adm_completed'], $code);
        } elseif ($action === 'solve') {
            // a user's complaint: what the office did (the user sees it)
            $answer = $this->text('answer', 300);
            if ($answer === '') {
                $this->errors['form'] = $t['bk_err_answer'];
                return;
            }
            if (!BookingModel::solveComplaint($booking['id'], $answer, $this->me['id'])) {
                $this->errors['form'] = $t['bk_err_changed'];
                return;
            }
            AccountChangeModel::add('user', $booking['user_id'], $this->me['id'], [['bk_event', 'solved', $code]]);
            return $this->done('bookings&tab=complaints', sprintf($t['bk_adm_solved'], $code));
        } elseif ($action === 'note') {
            BookingModel::setStaffNote($booking['id'], $this->text('staff_note', 300) ?: null);
            return $this->done('bookings&tab=' . $booking['status'], $t['adm_saved']);
        } elseif ($action === 'cancel') {
            $reason    = $this->text('reason', 200);
            $ok        = BookingModel::cancel($booking, 'staff', $this->me['id'], $reason !== '' ? $reason : null);
            $newStatus = 'cancelled';
            $message   = sprintf($t['bk_cancelled'], $code);
        } else {
            return;
        }
        if (!$ok) {
            $this->errors['form'] = $t['bk_err_changed'];      // someone else changed it first
            return;
        }
        // A new provider for an already ongoing booking is also told (status stays "ongoing").
        AccountChangeModel::add('user', $booking['user_id'], $this->me['id'], [['bk_event', $newStatus, $code]]);
        return $this->done('bookings&tab=' . $newStatus, $message);     // open the tab the booking moved to
    }

    // ---- Add-money requests: approve / reject. The user is told under the bell.
    private function saveTopup($action, $id)
    {
        $t = $this->t;
        $topup = WalletModel::topup($id);
        if (!$topup || !in_array($action, ['approve', 'reject'], true)) {
            $this->errors['form'] = $t['wl_err_gone'];
            return;
        }
        $approve = $action === 'approve';
        if (!WalletModel::checkTopup($id, $approve, $this->me['id'])) {
            $this->errors['form'] = $t['wl_err_checked'];      // already approved / rejected by someone else
            return;
        }
        $status = $approve ? 'successful' : 'rejected';
        AccountChangeModel::add('user', $topup['user_id'], $this->me['id'], [['wl_event', $status, (int)$topup['amount']]]);
        $message = sprintf($t[$approve ? 'wl_adm_approved' : 'wl_adm_rejected'], number_format($topup['amount']), $topup['trx_id']);
        return $this->done('topups', $message);
    }

    // ---- Coupons: show / hide, delete (only if never used), add, edit
    private function saveCoupon($action, $id)
    {
        $t = $this->t;
        $row = $id ? WalletModel::coupon($id) : null;
        if ($action === 'toggle' && $row) {
            WalletModel::setCouponActive($id, $row['is_active'] ? 0 : 1);
            return $this->done('coupons', $t['adm_saved']);
        }
        if ($action === 'delete' && $row) {
            if (!WalletModel::deleteCoupon($id)) {
                $this->errors['form'] = $t['cp_err_used'];
                return;
            }
            return $this->done('coupons', $t['adm_deleted']);
        }
        if ($action !== 'add' && !($action === 'save' && $row)) {
            return;
        }

        $coupon = $this->couponForm($id, $row);
        if ($this->errors) {
            return;
        }
        // form text -> database values (empty = no limit)
        $coupon['amount']     = (int)$coupon['amount'];
        $coupon['min_order']  = (int)$coupon['min_order'];
        $coupon['valid_days'] = $coupon['valid_days'] === '' ? null : (int)$coupon['valid_days'];
        $coupon['expires_on'] = $coupon['expires_on'] === '' ? null : $coupon['expires_on'];
        WalletModel::saveCoupon($row ? $id : 0, $coupon, $this->me['id']);
        return $this->done('coupons', $t['adm_saved']);
    }

    // ---- Provider leave days (from the provider's edit page): add one (from - to, max 60 days, not in the past) or delete one
    private function saveLeave($action, array $provider)
    {
        $t = $this->t;
        $id = (int)$provider['id'];
        $back = 'providers&edit=' . $id;
        if ($action === 'leave_delete') {
            ProviderModel::deleteLeave($id, (int)($_POST['leave_id'] ?? 0));
            return $this->done($back, $t['lv_deleted']);
        }
        $from = (string)($_POST['from_date'] ?? '');
        $to   = (string)($_POST['to_date'] ?? '') ?: $from;
        $ok = function ($d) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4)); };
        if (!$ok($from) || !$ok($to) || $to < $from || $to < date('Y-m-d') || (strtotime($to) - strtotime($from)) / 86400 > 60) {
            $this->errors['leave'] = $t['lv_err_dates'];
            return;
        }
        ProviderModel::addLeave($id, $from, $to, $this->text('leave_note', 100) ?: null, (int)$this->me['id']);
        return $this->done($back, $t['lv_added']);
    }

    // ---- Provider accounts: money given to / taken from a provider
    private function savePayout($action, $id)
    {
        $t = $this->t;
        if ($action !== 'pay' || !ProviderModel::find($id)) {
            return;
        }
        $amount = trim((string)($_POST['amount'] ?? ''));
        $method = (string)($_POST['method'] ?? '');
        if (!preg_match('/^\d{1,7}$/', $amount) || (int)$amount < 1 || (int)$amount > 1000000) {
            $this->errors['form'] = $t['po_err_amount'];
            return;
        }
        if (!in_array($method, ProviderAccountModel::METHODS, true)) {
            $method = 'cash';
        }
        // out = the office paid the provider (+), in = the provider paid the office (-)
        $signed = ($_POST['dir'] ?? '') === 'in' ? -(int)$amount : (int)$amount;
        ProviderAccountModel::addPayment($id, $signed, $method, $this->text('note', 200) ?: null, (int)$this->me['id']);
        $back = 'payouts' . (($q = $this->searchText()) !== '' ? '&q=' . rawurlencode($q) : '') . (!empty($_GET['due']) ? '&due=1' : '');
        return $this->done($back, $t['adm_saved']);
    }

    // Reads and checks the coupon form (values stay text). Errors go to $this->errors.
    private function couponForm($id, $row)
    {
        $t = $this->t;
        $coupon = [
            'code'       => strtoupper(preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''))),
            'title_bn'   => $this->text('title_bn', 100),
            'title_en'   => $this->text('title_en', 100),
            'amount'     => trim((string)($_POST['amount'] ?? '')),
            'min_order'  => trim((string)($_POST['min_order'] ?? '0')),
            'valid_days' => trim((string)($_POST['valid_days'] ?? '')),
            'expires_on' => trim((string)($_POST['expires_on'] ?? '')),
            'is_active'  => $this->checkbox('is_active'),
        ];

        // code: 4-20 letters / digits, not used by another coupon
        if (!preg_match('/^[A-Z0-9]{4,20}$/', $coupon['code'])) {
            $this->errors['code'] = $t['cp_err_code'];
        } elseif (($same = WalletModel::couponByCode($coupon['code'])) && (int)$same['id'] !== (int)$id) {
            $this->errors['code'] = $t['cp_err_code_used'];
        }
        if ($coupon['title_bn'] === '') {
            $this->errors['title_bn'] = $t['cp_err_title'];
        }
        if ($coupon['title_en'] === '') {
            $this->errors['title_en'] = $t['cp_err_title'];
        }
        // amount: 1 - 5000 taka
        $amount = $coupon['amount'];
        if (!preg_match('/^\d{1,4}$/', $amount) || (int)$amount < 1 || (int)$amount > 5000) {
            $this->errors['amount'] = $t['cp_err_amount'];
        }
        // minimum order: empty = 0
        if ($coupon['min_order'] === '') {
            $coupon['min_order'] = '0';
        }
        if (!preg_match('/^\d{1,6}$/', $coupon['min_order'])) {
            $this->errors['min_order'] = $t['cp_err_min'];
        }
        // valid for 1 - 365 days after the user gets it (empty = no limit)
        $days = $coupon['valid_days'];
        if ($days !== '' && (!preg_match('/^\d{1,3}$/', $days) || (int)$days < 1 || (int)$days > 365)) {
            $this->errors['valid_days'] = $t['cp_err_days'];
        }
        // last day: a real date, not in the past (an edit may keep the old date even if it has passed)
        $expires = $coupon['expires_on'];
        if ($expires !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $expires);
            $isRealDate = $date && $date->format('Y-m-d') === $expires;
            $isNewPastDate = $expires < date('Y-m-d') && (!$row || $row['expires_on'] !== $expires);
            if (!$isRealDate || $isNewPastDate) {
                $this->errors['expires_on'] = $t['cp_err_date'];
            }
        }
        return $coupon;
    }

    // ================================================================ SMALL HELPERS

    // ?$key= when it is one of $allowed, otherwise $default
    private function queryChoice($key, array $allowed, $default)
    {
        return in_array($_GET[$key] ?? '', $allowed, true) ? $_GET[$key] : $default;
    }

    // the search box text (?q=)
    private function searchText()
    {
        return trim((string)($_GET['q'] ?? ''));
    }

    // ---- Work log: one row per save that worked (who, page, action, which row, a short name of the item).
    // Only the names of things are kept, never passwords or what was typed in a password box.
    private function logWork()
    {
        if (!$this->posted) {
            return;
        }
        list($page, $action) = $this->posted;
        $what = '';
        foreach (['name', 'name_bn', 'code', 'title_bn', 'mobile'] as $field) {
            if (isset($_POST[$field]) && is_string($_POST[$field]) && trim($_POST[$field]) !== '') {
                $what = trim($_POST[$field]);
                break;
            }
        }
        $target = (int)($_POST['id'] ?? 0);
        if ($what === '' && $target) {
            $what = $this->logName($page, $target);
        }
        try {
            ActivityLogModel::add($this->me['id'], $page, $action, $target, $what);   // not the flash text: a password reset shows the new password there
        } catch (PDOException $ex) {
            app_log('error', 'work log: ' . $ex->getMessage());   // the save itself already worked
        }
    }

    // the name of the row a form changed, so the log says "RAHIM MIA" and not only "#6"
    private function logName($page, $id)
    {
        if ($page === 'bookings') {
            return '#' . BookingModel::code($id);
        }
        $from = [
            'providers' => 'providers', 'payouts' => 'providers', 'users' => 'users', 'employees' => 'staff', 'admins' => 'staff',
            'services' => 'services', 'sub-services' => 'sub_services', 'coupons' => 'coupons',
        ][$page] ?? '';
        if ($from === '') {
            return '';
        }
        $column = in_array($from, ['services', 'sub_services'], true) ? 'name_bn' : ($from === 'coupons' ? 'code' : 'name');
        $st = Database::get()->prepare("SELECT $column FROM $from WHERE id = ?");
        $st->execute([$id]);
        return (string)$st->fetchColumn();
    }

    // ---- Reports: date range from ?from=&to= or a quick choice ?r=7|30|month|year|all (default 30 days)
    private function loadReportsPage(array &$data, $lang)
    {
        $today = date('Y-m-d');
        $quick = $this->queryChoice('r', ['today', '7', '30', 'month', 'year', 'all'], '');
        $isDate = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false; };
        if (!$quick && $isDate($_GET['from'] ?? null) && $isDate($_GET['to'] ?? null)) {
            $from = min($_GET['from'], $_GET['to']);
            $to   = max($_GET['from'], $_GET['to']);
        } else {
            $quick = $quick ?: '30';
            $starts = [
                'today' => $today,
                '7'     => date('Y-m-d', strtotime('-6 days')),
                '30'    => date('Y-m-d', strtotime('-29 days')),
                'month' => date('Y-m-01'),
                'year'  => date('Y-01-01'),
                'all'   => substr((string)Database::get()->query('SELECT MIN(created_at) FROM bookings')->fetchColumn() ?: $today, 0, 10),
            ];
            $from = $starts[$quick];
            $to   = $today;
        }
        $data['quick']      = $quick;
        $data['from']       = $from;
        $data['to']         = $to;
        $data['sum']        = ReportModel::summary($from, $to);
        $data['byService']  = ReportModel::byService($from, $to, $lang);
        $data['byArea']     = ReportModel::byArea($from, $to);
        $data['byProvider'] = ReportModel::byProvider($from, $to);
        list($data['perDay'], $data['perMonth']) = ReportModel::perDay($from, $to);
    }

    // one page of a list, in the shape the list views and the pager expect
    private function pagedList($rows, $total, $p, $pageCount)
    {
        return ['rows' => $rows, 'total' => $total, 'p' => $p, 'pages' => $pageCount];
    }

    // a one-line form field: spaces squeezed, cut to $max letters
    private function text($field, $max)
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($_POST[$field] ?? ''))), 0, $max, 'UTF-8');
    }

    // a checkbox: 1 = ticked, 0 = not
    private function checkbox($field)
    {
        return isset($_POST[$field]) ? 1 : 0;
    }

    // sets "required" on every field in [field => value] that is empty
    private function requireFilled(array $values)
    {
        foreach ($values as $field => $value) {
            if ($value === '') {
                $this->errors[$field] = $this->t['adm_err_required'];
            }
        }
    }

    // Saves an uploaded picture (png / jpg / webp). Sizes default to the folder's entry in IMG_SIZES.
    // Returns ['path' => ..., 'error' => ...] like save_image_upload().
    private function uploadPicture($field, $folder, $required, ?array $sizes = null)
    {
        return save_image_upload($field, $folder, self::PICTURE_TYPES, $this->t, $required, $sizes ?? IMG_SIZES[$folder]);
    }

    // For forms with an optional picture that are checked first: saves the upload, and when the form
    // (or the upload) has any error, deletes the new file again and returns null. Otherwise returns the upload.
    private function uploadIfValid($field, $folder, array $types, array $sizes)
    {
        $up = save_image_upload($field, $folder, $types, $this->t, false, $sizes);
        if ($up['error']) {
            $this->errors[$field] = $up['error'];
        }
        if ($this->errors) {
            delete_upload($up['path']);
            return null;
        }
        return $up;
    }

    // After a successful save: show $message on the next page (if any) and reload ?page=$page.
    // redirect() stops the script, so nothing after done() runs.
    // ---- Government papers this page's work needs (app/data/permits.php): [name in the current language, source link]
    private static function papersFor($page, $lang)
    {
        $names = [];
        foreach (require APP_PATH . '/data/permits.php' as $paper) {
            if (in_array($page, $paper['pages'], true)) {
                $names[] = ['name' => $paper[$lang] ?? $paper['bn'], 'link' => $paper['link']];
            }
        }
        return $names;
    }

    private function done($page, $message)
    {
        $this->logWork();
        if ($message !== '') {
            flash_set('notice', $message);
        }
        redirect($this->base . '?page=' . $page);
    }
}
