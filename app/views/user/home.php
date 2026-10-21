<?php
// app/views/user/home.php
// The frame of every logged-in user page: left menu, top bar (area, contact, language, bell), phone tab bar,
// popups. In the middle it shows, by $mode:
//   home      banners, search, service categories, popular, newly added   (UserHomeController, user-dashboard.php)
//   services  all categories                                              (user-dashboard.php?list=services)
//   list      one list of service cards: search result, category, popular, new (user-dashboard.php?q= / ?service= / ?list=)
//   book / bookings / wallet / messages / profile  -> the page file user/<mode>.php is pulled in with require
//             (BookingController, WalletController, MessageController, ProfileController)
// Everything shown here is set by the admin. Gets: $t, $lang, $mode, $user, $site, $notice, $unread, $changes,
// $notifications, $favIds, $popups, and per mode $banners, $services, $popular, $newest, $items, $title, $q.
// The inner page files see every variable of this file ($card, $tile, $nm, $active ...).

// ---- Small helpers for names and cards ----
// $nm($row)   -> name in the chosen language,  $alt($row) -> the same name in the other language
$nm  = function ($row, $f = 'name') use ($lang) { return $row[$f . '_' . $lang] ?? $row[$f . '_bn']; };   // name in the chosen language
$other = $lang === 'en' ? 'bn' : 'en';
$alt = function ($row, $f = 'name') use ($other) { return (string)($row[$f . '_' . $other] ?? ''); };   // the same name in the other language
// $card($subService, $isNew) -> HTML of one service card (heart button + picture, name, rating, price)
$card = function ($x, $isNew = false) use ($nm, $alt, $t, $favIds) {
    $fav = in_array((int)$x['id'], $favIds, true);
    ob_start(); ?>
    <div class="svc-wrap">
    <button type="button" class="fav-btn<?= $fav ? ' on' : '' ?>" data-id="<?= (int)$x['id'] ?>" aria-pressed="<?= $fav ? 'true' : 'false' ?>" aria-label="<?= e($t['fav_toggle']) ?>" title="<?= e($t['fav_toggle']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.4-9-8.8C1.6 8 3.5 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.5 0 5.4 3.5 4 6.7C19 15.6 12 20 12 20z"/></svg></button>
    <a class="svc-card" href="book.php?id=<?= (int)$x['id'] ?>">
        <span class="svc-img tile-<?= e($x['service_color']) ?>">
            <?php if ($x['image']): ?><img src="<?= e($x['image']) ?>" alt="" loading="lazy"><?php else: ?><img class="ph" src="<?= e($x['service_icon']) ?>" alt=""><?php endif; ?>
            <?php if ($isNew): ?><span class="badge-new"><?= e($t['home_new_badge']) ?></span><?php endif; ?>
        </span>
        <span class="svc-body">
            <span class="svc-name"><?= e($nm($x)) ?></span>
            <span class="svc-alt"><?= e(trim($alt($x) . ' ' . $alt($x, 'detail'))) ?></span>
            <span class="svc-meta">
                <?php if ((float)$x['rating'] > 0): ?><span class="stars">★ <b><?= e($x['rating']) ?></b> <span>(<?= (int)$x['rating_count'] ?>)</span></span><?php else: ?><span></span><?php endif; ?>
                <span class="price">৳ <?= e(number_format((int)$x['price'])) ?> <small><?= e($t['home_from']) ?></small></span>
            </span>
        </span>
    </a>
    </div>
    <?php return ob_get_clean();
};
// $tile($service) -> prints one round category tile
$tile = function ($s) use ($nm, $alt) { ?>
    <a class="cat tile-<?= e($s['color']) ?>" href="user-dashboard.php?service=<?= (int)$s['id'] ?>">
        <span class="cat-ic tile-<?= e($s['color']) ?>"><img src="<?= e($s['icon']) ?>" alt=""></span>
        <span class="cat-name"><?= e($nm($s)) ?></span>
        <small class="cat-alt"><?= e($alt($s)) ?></small>
    </a>
<?php };
// $searchBox($value) -> prints the service search form.
// $value = null leaves the value attribute out (home page), a string fills it (result pages).
$searchBox = function ($value = null) use ($t, $lang) { ?>
            <form class="search home-search" method="get" action="user-dashboard.php" role="search">
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q"<?= $value === null ? '' : ' value="' . e($value) . '"' ?> maxlength="60" placeholder="<?= e($t['home_search_ph']) ?>" aria-label="<?= e($t['home_search_ph']) ?>">
            </form>
<?php };
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" class="u-page">
<head>
<?php view('partials/head', ['title' => $t['home_page_title'], 'css' => 'home']); ?>
<?php
// booking / wallet / chat / profile pages use the same frame + one more CSS file
$innerPage = in_array($mode, ['book', 'bookings', 'wallet', 'messages', 'profile'], true);
?>
<?php if ($innerPage): ?><link rel="stylesheet" href="<?= e(asset('assets/css/user-pages.css')) ?>"><?php endif; ?>
</head>
<body class="um-<?= e($mode) ?>">
<?php
// ---- Left menu ----
// Which item is open now (booking a service belongs to home)
$active = in_array($mode, ['home', 'book'], true) ? 'home' : $mode;
$msgUnread = MessageModel::unreadForUser($user['id']);
// Only what is not already on the page: all services / popular / new are opened from the home page's
// "see all" links, offers from the bell. [group title key, [key => [address, text key, icon paths]]]
$sideGroups = [
    ['', [
        'home'     => ['user-dashboard.php',               'side_home',     '<path d="M3 11 12 3l9 8M5 10v10h14V10"/>'],
        'bookings' => ['bookings.php',                     'tab_bookings',  '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M8 15l2.5 2.5L16 13"/>'],
        'wallet'   => ['wallet.php',                       'tab_wallet',    '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"/><path d="M21 9h-5a3 3 0 0 0 0 6h5z"/>'],
        'messages' => ['messages.php',                     'tab_messages',  '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>'],
        'profile'  => ['profile.php',                      'tab_profile',   '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>'],
    ]],
];
?>
<?php /* ---- LEFT MENU: always open on PC, opens with the ☰ button on phone (side-menu.js) ---- */ ?>
<aside class="side-menu u-side" id="side-menu" aria-label="<?= e($t['side_menu']) ?>">
    <div class="side-top">
        <a class="side-brand" href="user-dashboard.php">
            <img src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
            <small><?= e($t['tagline']) ?></small>
        </a>
        <button type="button" class="side-x" id="side-close" aria-label="<?= e($t['side_close']) ?>">×</button>
    </div>
    <nav class="side-nav">
        <?php foreach ($sideGroups as $g): ?>
            <?php if ($g[0] !== ''): ?><p class="nav-grp"><?= e($t[$g[0]]) ?></p><?php endif; ?>
            <?php foreach ($g[1] as $key => $l): ?>
            <a href="<?= e($l[0]) ?>"<?= $active === $key ? ' class="on" aria-current="page"' : '' ?>><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><?= $l[2] ?></svg><?= e($t[$l[1]]) ?><?php if ($key === 'messages' && $msgUnread > 0): ?><span class="nav-n"><?= $msgUnread > 9 ? '9+' : $msgUnread ?></span><?php endif; ?></a>
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

<div class="shell">

    <?php /* ---- HEADER: menu button (phone), logo (phone), area, contact, language, notifications ---- */ ?>
    <header class="top">
        <button type="button" class="menu-btn" id="menu-btn" aria-label="<?= e($t['side_menu']) ?>" aria-controls="side-menu" aria-expanded="false">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a class="brand" href="user-dashboard.php">
            <img src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
        </a>
        <?php /* my area (the service location); click = change it in my profile */ ?>
        <a class="loc" href="profile.php#info" title="<?= e($t['home_loc_change']) ?>">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
            <span class="loc-txt"><small><?= e($t['home_loc_label']) ?></small><b><?= e($user['area']) ?></b></span>
            <svg class="ic loc-down" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </a>
        <div class="tools">
            <?php view('partials/site-contact', ['site' => $site, 'lang' => $lang, 't' => $t]); ?>
            <?php view('partials/lang-switch', ['lang' => $lang]); ?>
            <?php /* bell: account changes by the office, then the latest offers (home.js opens the panel) */ ?>
            <div class="notif">
                <button type="button" class="tool" id="bell" aria-expanded="false" aria-controls="notif-panel">
                    <span class="tool-ic">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/></svg>
                        <?php if ($unread > 0): ?><span class="count" id="bell-count"><?= $unread > 9 ? '9+' : (int)$unread ?></span><?php endif; ?>
                    </span>
                    <span class="tool-txt"><?= e($t['home_notifications']) ?></span>
                </button>
                <div class="notif-panel" id="notif-panel" hidden>
                    <?php if ($changes): ?>
                        <p class="notif-head"><?= e($t['chg_title']) ?></p>
                        <?php view('partials/account-changes', ['t' => $t, 'changes' => $changes, 'compact' => true]); ?>
                    <?php endif; ?>
                    <p class="notif-head"><?= e($t['home_latest_offers']) ?></p>
                    <?php if (!$notifications): ?><p class="notif-empty"><?= e($t['home_no_offers']) ?></p><?php endif; ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="notif-item">
                            <?php if ($n['image']): ?><img loading="lazy" decoding="async" src="<?= e($n['image']) ?>" alt=""><?php endif; ?>
                            <div><b><?= e($n['title']) ?></b><p><?= e($n['message']) ?></p><small><?= e(date('d M, h:i A', strtotime($n['created_at']))) ?></small></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="content" data-csrf="<?= e(csrf_token()) ?>">
        <?php /* one-time message after an action (saved, booked ...) */ ?>
        <?php if ($notice !== ''): ?><p class="flash" role="status"><?= e($notice) ?></p><?php endif; ?>

        <?php if ($mode === 'home'): /* ================= HOME ================= */ ?>

            <?php /* ---- 1) BANNERS (admin uploads), slider with dots (home.js) ---- */ ?>
            <?php if ($banners): ?>
            <section class="slider" aria-label="<?= e($t['home_offers']) ?>">
                <div class="slides" id="slides">
                    <?php foreach ($banners as $i => $b): ?>
                        <div class="slide"><img src="<?= e($b['image']) ?>" alt="<?= e($b['title'] ?? '') ?>" draggable="false"<?= $i ? ' loading="lazy"' : '' ?>></div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($banners) > 1): ?>
                    <div class="dots"><?php foreach ($banners as $i => $b): ?><span<?= $i ? '' : ' class="on"' ?>></span><?php endforeach; ?></div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <?php /* ---- Search (PC: under the banner; phone: in the header) ---- */ ?>
            <?php $searchBox(); ?>

            <?php /* ---- 2) SERVICES (admin adds with PNG logo) ---- */ ?>
            <section class="sec">
                <div class="sec-head"><h2><?= e($t['home_categories']) ?></h2><a href="user-dashboard.php?list=services"><?= e($t['home_all_services']) ?> →</a></div>
                <?php $n = count($services); ?>
                <?php /* One row of round icons across the whole width (at most 8 on PC / 5 on phone at a time);
                         more = slide with the arrows (home.js) */ ?>
                <div class="cat-row">
                    <button type="button" class="cat-arrow prev" id="cat-prev" hidden aria-label="<?= e($t['home_cat_prev']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
                    <div class="cats cats-home" id="cats" style="--n: <?= max(1, min($n, 8)) ?>; --n-m: <?= max(1, min($n, 5)) ?>">
                        <?php foreach ($services as $s) { $tile($s); } ?>
                        <?php if ($n > 8): ?>
                        <?php /* PC: 7 tiles + this one when there are more than 8 */ ?>
                        <a class="cat cat-more tile-rose" href="user-dashboard.php?list=services">
                            <span class="cat-ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="6" r="2.5"/><circle cx="12" cy="6" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="12" cy="12" r="2.5"/><circle cx="18" cy="12" r="2.5"/></svg></span>
                            <span class="cat-name"><?= e($t['home_more_services']) ?></span>
                            <small class="cat-alt"><?= e($t['home_more_sub']) ?></small>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php if ($n > 8): ?>
                    <?php /* phone: 2 rows show, the 3rd row is faded under this button (like bKash); tap = all categories */ ?>
                    <button type="button" class="cat-see" id="cat-see" aria-expanded="false" data-more="<?= e($t['home_see_more']) ?>" data-less="<?= e($t['home_see_less']) ?>"><span><?= e($t['home_see_more']) ?></span><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
                    <?php endif; ?>
                    <button type="button" class="cat-arrow next" id="cat-next" hidden aria-label="<?= e($t['home_cat_next']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
                </div>
            </section>

            <?php /* ---- 3) POPULAR (admin ranking) ---- */ ?>
            <?php if ($popular): ?>
            <section class="sec">
                <div class="sec-head"><h2><?= e($t['home_popular']) ?></h2><a href="user-dashboard.php?list=popular"><?= e($t['home_see_all']) ?> →</a></div>
                <div class="cards home-cards"><?php foreach ($popular as $x) { echo $card($x); } ?></div>
            </section>
            <?php endif; ?>

            <?php /* ---- 4) NEWLY ADDED ---- */ ?>
            <?php if ($newest): ?>
            <section class="sec sec-new">
                <div class="sec-head"><h2><?= e($t['home_new']) ?></h2><a href="user-dashboard.php?list=new"><?= e($t['home_see_all']) ?> →</a></div>
                <div class="cards cards-new home-cards"><?php foreach ($newest as $x) { echo $card($x, true); } ?></div>
            </section>
            <?php endif; ?>

        <?php elseif ($mode === 'services'): /* ================= ALL CATEGORIES ================= */ ?>
            <section class="sec">
                <a class="page-back" href="user-dashboard.php"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
                <div class="sec-head"><h2><?= e($t['home_categories']) ?></h2></div>
                <div class="cats cats-all"><?php foreach ($services as $s) { $tile($s); } ?></div>
            </section>

        <?php elseif ($innerPage): /* ================= book / bookings / wallet / messages / profile ================= */ ?>
            <?php require __DIR__ . '/' . $mode . '.php'; ?>
        <?php else: /* ================= ONE LIST: search result, category, popular, new ================= */ ?>
            <?php $searchBox($q); ?>
            <section class="sec">
                <a class="page-back" href="user-dashboard.php"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
                <div class="sec-head"><h2><?= e($title) ?></h2></div>
                <?php if (!$items): ?><p class="empty"><?= e($t['home_nothing']) ?></p><?php endif; ?>
                <div class="cards cards-grid"><?php foreach ($items as $x) { echo $card($x); } ?></div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php /* ---- POPUP (admin sets the picture). More than one: the next opens when this one is closed (home.js) ---- */ ?>
<?php foreach ($popups ?? [] as $i => $popup): ?>
<div class="popup" role="dialog" aria-modal="true" aria-label="<?= e($popup['title'] ?: $t['home_offers']) ?>"<?= $i ? ' hidden' : '' ?>>
    <div class="popup-box">
        <button type="button" class="popup-x" aria-label="<?= e($t['home_close']) ?>">×</button>
        <?php if (!empty($popup['link'])): ?>
            <a class="popup-link" href="<?= e($popup['link']) ?>" target="_blank" rel="noopener noreferrer sponsored" title="<?= e($t['home_popup_open']) ?>"><img src="<?= e($popup['image']) ?>" alt="<?= e($popup['title'] ?: $t['home_popup_open']) ?>"></a>
        <?php else: ?>
            <img src="<?= e($popup['image']) ?>" alt="<?= e($popup['title'] ?? '') ?>">
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php /* ---- Phone tab bar ---- */ ?>
<?php view('partials/user-tabbar', ['t' => $t, 'on' => $active, 'msgUnread' => $msgUnread]); ?>
<?php /* ---- Scripts: page-specific ones first, then the ones every user page needs ---- */ ?>
<?php if ($mode === 'bookings'): ?>
<?php view('partials/confirm-modal', ['t' => $t]); ?>
<script src="<?= e(asset('assets/js/confirm.js')) ?>"></script>
<?php endif; ?>
<?php if ($mode === 'messages'): ?><script src="<?= e(asset('assets/js/chat.js')) ?>"></script><?php endif; ?>
<?php if ($mode === 'book'): ?><script src="<?= e(asset('assets/js/gps.js')) ?>"></script><?php endif; ?>
<?php if ($mode === 'profile'): ?><script src="<?= e(asset('assets/js/form-check.js')) ?>"></script><script src="<?= e(asset('assets/js/zoom.js')) ?>"></script><?php endif; ?>
<script src="<?= e(asset('assets/js/live-search.js')) ?>"></script>
<script src="<?= e(asset('assets/js/side-menu.js')) ?>"></script>
<script src="<?= e(asset('assets/js/home.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
