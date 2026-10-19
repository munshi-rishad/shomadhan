<?php // app/views/admin/home.php - admin start page: short counts + shortcuts ?>
<h1 class="adm-h1"><?= e($t['adm_menu_home']) ?></h1>
<p class="adm-sub"><?= e($t['adm_home_sub']) ?></p>
<div class="adm-stats">
    <a class="stat" href="admin-dashboard.php?page=banners"><b><?= (int)$counts['banners'] ?></b><span><?= e($t['adm_menu_banners']) ?></span></a>
    <a class="stat" href="admin-dashboard.php?page=popup"><b><?= (int)$counts['popups'] ?></b><span><?= e($t['adm_menu_popup']) ?></span></a>
    <a class="stat" href="admin-dashboard.php?page=services"><b><?= (int)$counts['services'] ?></b><span><?= e($t['adm_menu_services']) ?></span></a>
    <a class="stat" href="admin-dashboard.php?page=sub-services"><b><?= (int)$counts['subs'] ?></b><span><?= e($t['adm_menu_subs']) ?></span></a>
    <a class="stat" href="admin-dashboard.php?page=popular"><b><?= (int)$counts['popular'] ?></b><span><?= e($t['adm_menu_popular']) ?></span></a>
    <a class="stat" href="admin-dashboard.php?page=offers"><b><?= (int)$counts['offers'] ?></b><span><?= e($t['adm_menu_offers']) ?></span></a>
</div>
