<?php
// app/views/partials/site-contact.php
// Contact items that sit INSIDE the page's one top bar (no separate bar). Used by user/home.php and pages/info.php:
// phone, email, hours (only those listed in $items) + the green "কাস্টমার কেয়ার" button (WhatsApp; phone call if no WhatsApp).
// All from Admin -> "সাইট তথ্য". view('partials/site-contact', ['site' => ..., 'lang' => $lang, 't' => $t, 'items' => ['phone', 'email', 'hours']])
$items = $items ?? [];
// "Customer care" link: WhatsApp chat when a WhatsApp number is set, else a phone call
$care  = $site['whatsapp'] !== '' ? 'https://wa.me/' . phone_intl($site['whatsapp']) : 'tel:+' . phone_intl($site['phone']);
?>
<?php /* ---- Phone / email / hours (only the ones asked for; email only when it is set) ---- */ ?>
<?php if ($items): ?>
<div class="sc-items">
    <?php if (in_array('phone', $items, true)): ?><a class="sc-phone" href="tel:+<?= e(phone_intl($site['phone'])) ?>"><span class="sc-dot"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span><?= e($site['phone']) ?></a><?php endif; ?>
    <?php if (in_array('email', $items, true) && $site['email'] !== ''): ?><a class="sc-email" href="mailto:<?= e($site['email']) ?>"><span class="sc-dot"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><?= e($site['email']) ?></a><?php endif; ?>
    <?php if (in_array('hours', $items, true)): ?><span class="sc-hours"><span class="sc-dot"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><span data-bn="<?= e(site_val($site, 'hours', 'bn')) ?>" data-en="<?= e(site_val($site, 'hours', 'en')) ?>"><?= e(site_val($site, 'hours', $lang)) ?></span></span><?php endif; ?>
</div>
<?php endif; ?>
<?php /* ---- Green customer care button (always shown) ---- */ ?>
<a class="care-btn" href="<?= e($care) ?>"<?= $site['whatsapp'] !== '' ? ' target="_blank" rel="noopener"' : '' ?> title="<?= e($t['site_whatsapp_chat']) ?>"><span class="sc-dot"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/><path d="M19 20c0 1.5-2 2-5 2"/></svg></span><span class="care-txt" data-i18n="site_whatsapp_chat"><?= e($t['site_whatsapp_chat']) ?></span></a>
