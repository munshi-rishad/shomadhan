<?php
// app/views/user/home.php - user home page. Everything here is set by the admin.
$nm  = function ($row, $f = 'name') use ($lang) { return $row[$f . '_' . $lang] ?? $row[$f . '_bn']; };   // name in the chosen language
$card = function ($x, $isNew = false) use ($nm, $t) {
    ob_start(); ?>
    <a class="svc-card" href="user-dashboard.php?service=<?= (int)$x['service_id'] ?>">
        <span class="svc-img tile-<?= e($x['service_color']) ?>">
            <?php if ($x['image']): ?><img src="<?= e($x['image']) ?>" alt="" loading="lazy"><?php else: ?><img class="ph" src="<?= e($x['service_icon']) ?>" alt=""><?php endif; ?>
            <?php if ($isNew): ?><span class="badge-new"><?= e($t['home_new_badge']) ?></span><?php endif; ?>
        </span>
        <span class="svc-body">
            <span class="svc-name"><?= e($nm($x)) ?><?php if ($nm($x, 'detail')): ?> <span class="svc-detail"><?= e($nm($x, 'detail')) ?></span><?php endif; ?></span>
            <span class="svc-meta">
                <?php if ((float)$x['rating'] > 0): ?><span class="stars">★ <b><?= e($x['rating']) ?></b> <span>(<?= (int)$x['rating_count'] ?>)</span></span><?php else: ?><span></span><?php endif; ?>
                <span class="price">৳ <?= e(number_format((int)$x['price'])) ?> <?= e($t['home_from']) ?></span>
            </span>
        </span>
    </a>
    <?php return ob_get_clean();
};
$tile = function ($s) use ($nm) { ?>
    <a class="cat" href="user-dashboard.php?service=<?= (int)$s['id'] ?>">
        <span class="cat-ic tile-<?= e($s['color']) ?>"><img src="<?= e($s['icon']) ?>" alt=""></span>
        <span class="cat-name"><?= e($nm($s)) ?></span>
    </a>
<?php };
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['home_page_title'], 'css' => 'home']); ?>
</head>
<body>
<?php
// left menu: which item is open now
$active = $mode === 'home' ? 'home' : (string)($_GET['list'] ?? '');
// only what is not already on the page: all services / popular / new are opened from the home page's "see all" links, offers from the bell
$sideGroups = [
    ['', [
        'home'     => ['user-dashboard.php',               'side_home',     '<path d="M3 11 12 3l9 8M5 10v10h14V10"/>'],
    ]],
];
?>
<!-- LEFT MENU: always open on PC, opens with the ☰ button on phone -->
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
            <a href="<?= e($l[0]) ?>"<?= $active === $key ? ' class="on" aria-current="page"' : '' ?>><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><?= $l[2] ?></svg><?= e($t[$l[1]]) ?></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <div class="side-bottom">
        <?php view('partials/logout-form', ['t' => $t, 'class' => 'side-logout',
            'inner' => '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M20 12H9M16 8l4 4-4 4"/></svg>' . e($t['btn_logout'])]); ?>
    </div>
</aside>
<div class="side-shade" id="side-shade" hidden></div>

<div class="shell">

    <!-- HEADER: menu button (phone), logo (phone), area, search, notifications -->
    <header class="top">
        <button type="button" class="menu-btn" id="menu-btn" aria-label="<?= e($t['side_menu']) ?>" aria-controls="side-menu" aria-expanded="false">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a class="brand" href="user-dashboard.php">
            <img src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
        </a>
        <span class="loc">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
            <?= e($t['home_location']) ?>
        </span>
        <form class="search" method="get" action="user-dashboard.php" role="search">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="<?= e($q) ?>" maxlength="60" placeholder="<?= e($t['home_search_ph']) ?>" aria-label="<?= e($t['home_search_ph']) ?>">
        </form>
        <div class="tools">
            <?php view('partials/lang-switch', ['lang' => $lang]); ?>
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
                            <?php if ($n['image']): ?><img src="<?= e($n['image']) ?>" alt=""><?php endif; ?>
                            <div><b><?= e($n['title']) ?></b><p><?= e($n['message']) ?></p><small><?= e(date('d M, h:i A', strtotime($n['created_at']))) ?></small></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php view('partials/me-chip', ['t' => $t, 'account' => $user + ['role' => 'user'], 'href' => 'profile.php']); ?>
        </div>
    </header>

    <main class="content" data-csrf="<?= e(csrf_token()) ?>">
        <?php if ($notice !== ''): ?><p class="flash" role="status"><?= e($notice) ?></p><?php endif; ?>

        <?php if ($mode === 'home'): ?>

            <!-- 1) BANNERS (admin uploads) -->
            <?php if ($banners): ?>
            <section class="slider" aria-label="<?= e($t['home_offers']) ?>">
                <div class="slides" id="slides">
                    <?php foreach ($banners as $i => $b): ?>
                        <div class="slide"><img src="<?= e($b['image']) ?>" alt="<?= e($b['title'] ?? '') ?>"<?= $i ? ' loading="lazy"' : '' ?>></div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($banners) > 1): ?>
                    <button type="button" class="arrow prev" aria-label="Previous">‹</button>
                    <button type="button" class="arrow next" aria-label="Next">›</button>
                    <div class="dots"><?php foreach ($banners as $i => $b): ?><span<?= $i ? '' : ' class="on"' ?>></span><?php endforeach; ?></div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <!-- 2) SERVICES (admin adds with PNG logo) -->
            <section class="sec">
                <div class="sec-head"><h2><?= e($t['home_categories']) ?></h2><a href="user-dashboard.php?list=services"><?= e($t['home_see_all_services']) ?> →</a></div>
                <div class="cats">
                    <?php foreach (array_slice($services, 0, 6) as $s) { $tile($s); } ?>
                </div>
            </section>

            <!-- 3) POPULAR (admin ranking) -->
            <?php if ($popular): ?>
            <section class="sec">
                <div class="sec-head"><h2><?= e($t['home_popular']) ?></h2><a href="user-dashboard.php?list=popular"><?= e($t['home_see_all']) ?> →</a></div>
                <div class="cards home-cards"><?php foreach ($popular as $x) { echo $card($x); } ?></div>
            </section>
            <?php endif; ?>

            <!-- 4) NEWLY ADDED -->
            <?php if ($newest): ?>
            <section class="sec">
                <div class="sec-head"><h2><?= e($t['home_new']) ?></h2><a href="user-dashboard.php?list=new"><?= e($t['home_see_all']) ?> →</a></div>
                <div class="cards cards-new home-cards"><?php foreach ($newest as $x) { echo $card($x, true); } ?></div>
            </section>
            <?php endif; ?>

        <?php elseif ($mode === 'services'): ?>
            <section class="sec">
                <a class="page-back" href="user-dashboard.php"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
                <div class="sec-head"><h2><?= e($t['home_categories']) ?></h2></div>
                <div class="cats cats-all"><?php foreach ($services as $s) { $tile($s); } ?></div>
            </section>

        <?php else: ?>
            <section class="sec">
                <a class="page-back" href="user-dashboard.php"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
                <div class="sec-head"><h2><?= e($title) ?></h2></div>
                <?php if (!$items): ?><p class="empty"><?= e($t['home_nothing']) ?></p><?php endif; ?>
                <div class="cards cards-grid"><?php foreach ($items as $x) { echo $card($x); } ?></div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php if (!empty($popup)): ?>
<!-- POPUP (admin sets the picture) -->
<div class="popup" id="popup" role="dialog" aria-modal="true" aria-label="<?= e($popup['title'] ?: $t['home_offers']) ?>">
    <div class="popup-box">
        <button type="button" class="popup-x" id="popup-x" aria-label="<?= e($t['home_close']) ?>">×</button>
        <?php if (!empty($popup['link'])): ?>
            <a class="popup-link" href="<?= e($popup['link']) ?>" target="_blank" rel="noopener noreferrer sponsored" title="<?= e($t['home_popup_open']) ?>"><img src="<?= e($popup['image']) ?>" alt="<?= e($popup['title'] ?: $t['home_popup_open']) ?>"></a>
        <?php else: ?>
            <img src="<?= e($popup['image']) ?>" alt="<?= e($popup['title'] ?? '') ?>">
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<script src="<?= e(asset('assets/js/side-menu.js')) ?>"></script>
<script src="<?= e(asset('assets/js/home.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
