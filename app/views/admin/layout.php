<?php
// app/views/admin/layout.php
// The admin + employee dashboard frame: menu on the left (PC) / behind the ☰ button (phone), the page in the middle.
// Rendered by AdminController::index() (admin-dashboard.php, employee-dashboard.php) with view('admin/layout', $data).
// Gets: $t, $lang, $panel ('admin' | 'employee'), $account, $pages (the pages this role can open; the menu shows only those),
//       $page (open page), $base (this dashboard's address), $canEdit, $notice, $errors, $old, $badges (menu counts),
//       $unseen (bell count), $papers (government papers this page's work needs), optional $body (view file to show instead of $page), plus the page's own data.
// The page itself (admin/<page>.php) is pulled in with require below, so it sees every variable here,
// including the $err / $val / $hidden helpers.

// ---- Menu ----
// In working order: orders -> services -> what users see -> accounts -> office (reports, work log; admin only).
// My profile + notifications are only top-right (me chip, bell), not repeated here.
// [group title key, [page => [text key, icon paths]]]
$groups = [
    ['adm_grp_orders', [
        'bookings'     => ['adm_menu_bookings', '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M8 15l2.5 2.5L16 13"/>'],
        'messages'     => ['adm_menu_messages', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>'],
        'topups'       => ['adm_menu_topups',   '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"/><path d="M21 9h-5a3 3 0 0 0 0 6h5z"/>'],
        'payouts'      => ['adm_menu_payouts',  '<rect x="2" y="6" width="20" height="13" rx="2"/><circle cx="12" cy="12.5" r="2.5"/><path d="M6 10v5M18 10v5"/>'],
        'coupons'      => ['adm_menu_coupons',  '<path d="M3 9V6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a3 3 0 0 0 0 6v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a3 3 0 0 0 0-6z"/>'],
    ]],
    ['adm_grp_services', [
        'services'     => ['adm_menu_services','<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>'],
        'sub-services' => ['adm_menu_subs',    '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>'],
        'providers'    => ['adm_menu_providers','<path d="m14.7 6.3 3-3a4 4 0 0 1-5.4 5.4l-6.6 6.6a1.9 1.9 0 1 1-2.7-2.7l6.6-6.6a4 4 0 0 1 5.4-5.4z"/><path d="M15 15l5 5"/>'],
        'products'     => ['adm_menu_products','<path d="M21 8 12 3 3 8v8l9 5 9-5zM3 8l9 5 9-5M12 13v8"/>'],
        'popular'      => ['adm_menu_popular', '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>'],
    ]],
    ['adm_grp_content', [
        'banners'      => ['adm_menu_banners', '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 8"/>'],
        'popup'        => ['adm_menu_popup',   '<rect x="3" y="3" width="18" height="18" rx="3"/><rect x="7" y="7" width="10" height="8" rx="1.5"/><path d="M10 18h4"/>'],
        'offers'       => ['adm_menu_offers',  '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/>'],
        'welcome'      => ['adm_menu_welcome', '<path d="M4 20V9l8-5 8 5v11"/><rect x="8" y="12" width="8" height="5" rx="1"/>'],
        'site'         => ['adm_menu_site',    '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/>'],
    ]],
    ['adm_grp_accounts', [
        'users'        => ['adm_menu_users',   '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14a5.5 5.5 0 0 1 3 6"/>'],
        'employees'    => ['adm_menu_employees','<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>'],
        'admins'       => ['adm_menu_admins',  '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>'],
    ]],
    ['adm_grp_office', [
        'reports'      => ['adm_menu_reports', '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
        'log'          => ['adm_menu_log',     '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
    ]],
];
// Keep only the pages this role can open
foreach ($groups as $i => $g) {
    $groups[$i][1] = array_intersect_key($g[1], array_flip($pages));
}
// ---- Form helpers used by every admin page ----
// $err('name') -> the error line under a field (empty when there is no error)
// $val('name', $default) -> what the admin typed (after an error), else the saved value; already escaped
$err = function ($k) use ($errors) {
    return empty($errors[$k]) ? '' : '<p class="error-text">' . e($errors[$k]) . '</p>';
};
$val = function ($k, $default = '') use ($old) {
    return e($old[$k] ?? $default);
};
// $hidden('delete', 5) -> the hidden csrf + action + id fields of the small one-button forms in the tables
$hidden = function ($action, $id) {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="id" value="' . (int)$id . '">';
};
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['dash_' . $panel] . ' | Shomadhan', 'css' => 'admin']); ?>
</head>
<body>
<div class="adm">
    <?php /* ---- PHONE top bar: ☰ menu button + logo (language buttons sit top-right). PC: hidden, the menu is always open ---- */ ?>
    <header class="adm-bar">
        <button type="button" class="menu-btn" id="menu-btn" aria-label="<?= e($t['side_menu']) ?>" aria-controls="side-menu" aria-expanded="false">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a href="<?= e($base) ?>"><img class="adm-logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan"></a>
    </header>
    <?php /* ---- Left menu: logo + role, grouped page links with counts, logout. side-menu.js opens / closes it on phones ---- */ ?>
    <aside class="adm-side side-menu" id="side-menu" aria-label="<?= e($t['side_menu']) ?>">
        <div class="side-top">
            <a class="adm-brand" href="<?= e($base) ?>">
                <img class="adm-logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
                <span class="adm-role"><?= e($t['dash_' . $panel]) ?></span>
            </a>
            <button type="button" class="side-x" id="side-close" aria-label="<?= e($t['side_close']) ?>">×</button>
        </div>
        <nav class="side-nav adm-nav">
            <?php foreach ($groups as $g): if (!$g[1]) { continue; } ?>
                <?php if ($g[0] !== ''): ?><p class="nav-grp"><?= e($t[$g[0]]) ?></p><?php endif; ?>
                <?php foreach ($g[1] as $key => $m): ?>
                <a href="<?= e($base) ?>?page=<?= e($key) ?>"<?= $page === $key ? ' class="on" aria-current="page"' : '' ?>>
                    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><?= $m[1] ?></svg><span><?= e($t[$m[0]]) ?></span><?php if (!empty($badges[$key])): ?><span class="nav-n"><?= $badges[$key] > 99 ? '99+' : (int)$badges[$key] ?></span><?php endif; ?>
                </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="side-bottom">
            <?php view('partials/logout-form', ['t' => $t, 'class' => 'side-logout',
                'inner' => '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M20 12H9M16 8l4 4-4 4"/></svg>' . e($t['btn_logout'])]); ?>
        </div>
    </aside>
    <?php /* dark layer behind the open phone menu; a tap closes the menu */ ?>
    <div class="side-shade" id="side-shade" hidden></div>

    <main class="adm-main">
        <?php /* ---- Top bar (stays on top when the page scrolls): back button (left), bell + language + me (right). Same on every page ---- */ ?>
        <div class="adm-top">
            <?php if ($page !== $pages[0] || !empty($_GET['edit'])):
                $backTo = !empty($_GET['edit']) ? $base . '?page=' . $page : $base;   // edit form -> its list, other pages -> first page (opens after login) ?>
                <a class="page-back" href="<?= e($backTo) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
            <?php endif; ?>
            <?php /* bell = "what the office changed in my account" (changes page) */ ?>
            <div class="adm-top-r">
                <a class="bell<?= $page === 'changes' ? ' on' : '' ?>" href="<?= e($base) ?>?page=changes" aria-label="<?= e($t['chg_menu']) ?>" title="<?= e($t['chg_menu']) ?>">
                    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/></svg>
                    <?php if ($unseen > 0): ?><span class="count"><?= $unseen > 9 ? '9+' : (int)$unseen ?></span><?php endif; ?>
                </a>
                <?php view('partials/lang-switch', ['lang' => $lang]); ?>
                <?php view('partials/me-chip', ['t' => $t, 'account' => $account, 'href' => $base . '?page=profile', 'on' => $page === 'profile']); ?>
            </div>
        </div>
        <?php /* ---- Messages: saved, view-only (employee), form error ---- */ ?>
        <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>
        <?php if (!$canEdit && $page !== 'profile'): ?><p class="alert alert-info"><?= e($t['emp_view_only']) ?></p><?php endif; ?>
        <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
        <?php /* ---- Government papers this page's work needs (app/data/permits.php): one short line ---- */ ?>
        <?php if (!empty($papers)): ?>
        <div class="permit-note" role="note">
            <p><?= e($t['pmt_note']) ?></p>
            <?php foreach ($papers as $paper): /* each paper + "read more" = the page the info came from, in a new tab */ ?>
                <span class="permit-item"><b><?= e($paper['name']) ?></b>
                    <a class="btn btn-ghost btn-small" href="<?= e($paper['link']) ?>" target="_blank" rel="noopener"><?= e($t['pmt_more']) ?></a></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php /* ---- The open page (admin/<page>.php) ---- */ ?>
        <?php require __DIR__ . '/' . ($body ?? $page) . '.php'; ?>
    </main>
</div>
<?php /* ---- Scripts + the shared "Are you sure?" box ---- */ ?>
<script src="<?= e(asset('assets/js/side-menu.js')) ?>"></script>
<script src="<?= e(asset('assets/js/live-search.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-check.js')) ?>"></script>
<?php view('partials/confirm-modal', ['t' => $t]); ?>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
<script src="<?= e(asset('assets/js/confirm.js')) ?>"></script>
<script src="<?= e(asset('assets/js/zoom.js')) ?>"></script>
<?php if ($page === 'messages'): ?><script src="<?= e(asset('assets/js/chat.js')) ?>"></script><?php endif; ?>
</body>
</html>
