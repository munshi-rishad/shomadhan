<?php
// app/views/partials/public-footer.php
// The dark green footer of the welcome (landing/home.php), login and register pages.
// It sits BELOW the first screen: the page fills the screen first, scrolling down shows the footer.
// Contact + social links come from Admin -> "সাইট তথ্য" (a social icon shows only when its link is set).
// Texts carry data-i18n so the welcome page language buttons (landing.js) change them too.
// view('partials/public-footer', ['t' => $t, 'lang' => $lang])  - it reads the site info itself.
require_once APP_PATH . '/models/HomeContentModel.php';
$site = HomeContentModel::site();
// "Customer care" link: WhatsApp chat when a WhatsApp number is set, else a phone call
$care = $site['whatsapp'] !== '' ? 'https://wa.me/' . phone_intl($site['whatsapp']) : 'tel:+' . phone_intl($site['phone']);
// $tx('key') -> the text in a <span data-i18n> (so landing.js can switch it), $ic('<path ...>') -> an icon
$tx = function ($k) use ($t) { return '<span data-i18n="' . e($k) . '">' . e($t[$k]) . '</span>'; };
$ic = function ($p) { return '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">' . $p . '</svg>'; };
// Social icons: site info field => icon paths
$socials = [
    'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8"/>',
    'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
    'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>',
    'linkedin'  => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
];
$arrow = $ic('<path d="m9 6 6 6-6 6"/>');
?>
<footer class="pub-foot">
    <div class="pf-grid">
        <?php /* ---- Column 1: logo, tagline, about text, social icons ---- */ ?>
        <div class="pf-col pf-brand">
            <img class="pf-logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
            <p class="pf-tag"><?= $tx('tagline') ?></p>
            <p class="pf-about"><?= $tx('ft_about') ?></p>
            <div class="pf-social">
                <?php foreach ($socials as $k => $svg): if (($site[$k] ?? '') === '') { continue; } ?>
                    <a href="<?= e($site[$k]) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($k)) ?>"><?= $ic($svg) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php /* ---- Column 2: quick links ---- */ ?>
        <nav class="pf-col" aria-label="<?= e($t['ft_quick']) ?>">
            <h3><?= $tx('ft_quick') ?></h3>
            <a href="index.php"><?= $arrow ?><?= $tx('ft_home') ?></a>
            <a href="about.php"><?= $arrow ?><?= $tx('page_about') ?></a>
            <a href="index.php#services"><?= $arrow ?><?= $tx('ft_services') ?></a>
            <a href="#contact"><?= $arrow ?><?= $tx('ft_contact') ?></a>
        </nav>
        <?php /* ---- Column 3: contact info (phone, email, hours, address; hours + address in both languages) ---- */ ?>
        <div class="pf-col pf-contact" id="contact">
            <h3><?= $tx('ft_contact_info') ?></h3>
            <a href="tel:+<?= e(phone_intl($site['phone'])) ?>"><?= $ic('<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>') ?><?= e($site['phone']) ?></a>
            <?php if ($site['email'] !== ''): ?><a href="mailto:<?= e($site['email']) ?>"><?= $ic('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>') ?><?= e($site['email']) ?></a><?php endif; ?>
            <p><?= $ic('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>') ?><span data-bn="<?= e(site_val($site, 'hours', 'bn')) ?>" data-en="<?= e(site_val($site, 'hours', 'en')) ?>"><?= e(site_val($site, 'hours', $lang)) ?></span></p>
            <p><?= $ic('<path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/>') ?><span data-bn="<?= e(site_val($site, 'address', 'bn')) ?>" data-en="<?= e(site_val($site, 'address', 'en')) ?>"><?= e(site_val($site, 'address', $lang)) ?></span></p>
        </div>
        <?php /* ---- Column 4: support + policy links ---- */ ?>
        <nav class="pf-col" aria-label="<?= e($t['ft_support_head']) ?>">
            <h3><?= $tx('ft_support_head') ?></h3>
            <a href="<?= e($care) ?>"<?= $site['whatsapp'] !== '' ? ' target="_blank" rel="noopener"' : '' ?>><?= $arrow ?><?= $tx('ft_support') ?></a>
            <a href="terms.php"><?= $arrow ?><?= $tx('terms_link1') ?></a>
            <a href="privacy.php"><?= $arrow ?><?= $tx('terms_link2') ?></a>
            <a href="terms.php#sec-4"><?= $arrow ?><?= $tx('ft_refund') ?></a>
        </nav>
    </div>
    <?php /* ---- Bottom line: copyright + badges ---- */ ?>
    <div class="pf-bottom">
        <span>© <?= date('Y') ?> Shomadhan. <?= $tx('ft_rights') ?></span>
        <span class="pf-badges"><?= $tx('ft_b1') ?><i>|</i><?= $tx('ft_b2') ?><i>|</i><?= $tx('ft_b3') ?><i>|</i><?= $tx('ft_b4') ?></span>
    </div>
</footer>
