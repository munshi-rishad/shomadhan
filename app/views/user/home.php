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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($t['home_page_title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/home.css')) ?>">
</head>
<body>
<div class="shell">

    <!-- HEADER: logo, area, search, notifications, language, logout -->
    <header class="top">
        <a class="brand" href="user-dashboard.php">
            <img src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
            <small><?= e($t['tagline']) ?></small>
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
            <div class="notif">
                <button type="button" class="tool" id="bell" aria-expanded="false" aria-controls="notif-panel">
                    <span class="tool-ic">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/></svg>
                        <?php if ($unread > 0): ?><span class="count" id="bell-count"><?= $unread > 9 ? '9+' : (int)$unread ?></span><?php endif; ?>
                    </span>
                    <span class="tool-txt"><?= e($t['home_notifications']) ?></span>
                </button>
                <div class="notif-panel" id="notif-panel" hidden>
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
            <div class="lang" role="group" aria-label="Language / ভাষা">
                <button type="button" data-lang="bn" aria-pressed="<?= $lang === 'bn' ? 'true' : 'false' ?>">বাংলা</button>
                <button type="button" data-lang="en" aria-pressed="<?= $lang === 'en' ? 'true' : 'false' ?>">EN</button>
            </div>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="tool" type="submit" title="<?= e($t['btn_logout']) ?>">
                    <span class="tool-ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M20 12H9M16 8l4 4-4 4"/></svg></span>
                    <span class="tool-txt"><?= e($t['btn_logout']) ?></span>
                </button>
            </form>
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
                        <div class="slide"><span class="slide-bg" style="background-image:url('<?= e($b['image']) ?>')"></span><img src="<?= e($b['image']) ?>" alt="<?= e($b['title'] ?? '') ?>"<?= $i ? ' loading="lazy"' : '' ?>></div>
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
                    <a class="cat" href="user-dashboard.php?list=services">
                        <span class="cat-ic tile-gray"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="7" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/></svg></span>
                        <span class="cat-name"><?= e($t['home_more_services']) ?></span>
                    </a>
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
                <div class="sec-head"><h2><a class="back" href="user-dashboard.php" aria-label="<?= e($t['login_back']) ?>">‹</a> <?= e($t['home_categories']) ?></h2></div>
                <div class="cats cats-all"><?php foreach ($services as $s) { $tile($s); } ?></div>
            </section>

        <?php else: ?>
            <section class="sec">
                <div class="sec-head"><h2><a class="back" href="user-dashboard.php" aria-label="<?= e($t['login_back']) ?>">‹</a> <?= e($title) ?></h2></div>
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
        <img src="<?= e($popup['image']) ?>" alt="<?= e($popup['title'] ?? '') ?>">
    </div>
</div>
<?php endif; ?>
<script src="<?= e(asset('assets/js/home.js')) ?>"></script>
</body>
</html>
