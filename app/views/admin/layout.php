<?php
// app/views/admin/layout.php - admin dashboard frame: menu on the left (PC) / on top (phone), page in the middle
$menu = [
    'home'         => ['adm_menu_home',    '<path d="M3 11 12 3l9 8M5 10v10h14V10"/>'],
    'welcome'      => ['adm_menu_welcome', '<path d="M4 20V9l8-5 8 5v11"/><rect x="8" y="12" width="8" height="5" rx="1"/>'],
    'banners'      => ['adm_menu_banners', '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 8"/>'],
    'popup'        => ['adm_menu_popup',   '<rect x="3" y="3" width="18" height="18" rx="3"/><rect x="7" y="7" width="10" height="8" rx="1.5"/><path d="M10 18h4"/>'],
    'services'     => ['adm_menu_services','<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>'],
    'sub-services' => ['adm_menu_subs',    '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>'],
    'popular'      => ['adm_menu_popular', '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>'],
    'offers'       => ['adm_menu_offers',  '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/>'],
];
// error text under a field + keep what the admin typed when there is an error
$err = function ($k) use ($errors) { return empty($errors[$k]) ? '' : '<p class="error-text">' . e($errors[$k]) . '</p>'; };
$val = function ($k, $default = '') use ($old) { return e($old[$k] ?? $default); };
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($t['dash_admin']) ?> | Shomadhan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body>
<div class="adm">
    <aside class="adm-side">
        <img class="adm-logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
        <p class="adm-role"><?= e($t['dash_admin']) ?></p>
        <nav class="adm-nav">
            <?php foreach ($menu as $key => $m): ?>
                <a href="admin-dashboard.php?page=<?= e($key) ?>"<?= $page === $key ? ' class="on" aria-current="page"' : '' ?>>
                    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><?= $m[1] ?></svg><span><?= e($t[$m[0]]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="adm-side-foot">
            <p class="adm-who"><?= e($account['name']) ?></p>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="btn btn-ghost" type="submit"><?= e($t['btn_logout']) ?></button>
            </form>
        </div>
    </aside>

    <main class="adm-main">
        <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>
        <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
        <?php require __DIR__ . '/' . $page . '.php'; ?>
    </main>
</div>
<script>
// "Are you sure?" before deleting
document.addEventListener('submit', function (ev) {
    var msg = ev.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); }
});
</script>
</body>
</html>
