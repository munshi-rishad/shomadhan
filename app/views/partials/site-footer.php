<?php
// app/views/partials/site-footer.php
// One slim bar at the bottom of the user pages (user/home.php) and the info pages (pages/info.php)
// (the user does not like scrolling, so it is a single line on PC): copyright, About/Terms/Privacy links, address, social icons.
// All from Admin -> "সাইট তথ্য". view('partials/site-footer', ['site' => ..., 'lang' => $lang, 't' => $t])
// Nothing twice on one page: logo is in the header / left menu, phone / email / hours are in the top bar.
// 'links' => false  hides About/Terms/Privacy (the info pages have them in the header).
$links = $links ?? true;
// Social icons: site info field => icon paths (shown only when the link is set)
$socials = [
    'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8"/>',
    'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
    'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>',
];
?>
<footer class="site-footer">
    <div class="sf-bar">
        <?php /* ---- Copyright ---- */ ?>
        <span class="sf-copy">© <?= date('Y') ?> <?= e($t['foot_brand']) ?>. <?= e($t['foot_rights']) ?></span>
        <?php /* ---- About / Terms / Privacy links ---- */ ?>
        <?php if ($links): ?>
        <nav class="sf-links" aria-label="<?= e($t['foot_links']) ?>">
            <a href="about.php"><?= e($t['page_about']) ?></a>
            <a href="terms.php"><?= e($t['terms_link1']) ?></a>
            <a href="privacy.php"><?= e($t['terms_link2']) ?></a>
        </nav>
        <?php endif; ?>
        <?php /* ---- Office address ---- */ ?>
        <span class="sf-addr" title="<?= e($t['foot_address']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></svg><?= e(site_val($site, 'address', $lang)) ?></span>
        <?php /* ---- Social icons (the whole group is left out when no link is set) ---- */ ?>
        <?php if ($site['facebook'] . $site['youtube'] . $site['instagram'] !== ''): ?>
        <span class="sf-social">
            <?php foreach ($socials as $k => $svg): if ($site[$k] === '') { continue; } ?>
                <a href="<?= e($site[$k]) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($k)) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><?= $svg ?></svg></a>
            <?php endforeach; ?>
        </span>
        <?php endif; ?>
    </div>
</footer>
