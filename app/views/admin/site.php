<?php
// app/views/admin/site.php - ?page=site (admin only)
// Site info: contact + social links (top bar, footers), the payment numbers for wallet top-ups,
// and the texts of the About / Terms / Privacy pages. One form, saved at once.
// Shown inside admin/layout.php (AdminController). Gets: $site (HomeContentModel::site()) + the layout's $t, $base, $old, $err.

// ---- Field helpers ----
// $v('phone')                  -> the typed value after an error, else the saved one (escaped)
// $in(name, label key, type, placeholder) -> one text input with its label and error line
// $area(name, label key)       -> one large text box (page texts)
$v = function ($k) use ($old, $site) { return e($old[$k] ?? $site[$k]); };
$in = function ($k, $label, $type = 'text', $ph = '') use ($t, $v, $err) {
    return '<label class="f"><span>' . e($t[$label]) . '</span><input type="' . $type . '" name="' . $k . '" data-label="' . e($t[$label]) . '" maxlength="200" value="' . $v($k) . '"'
        . ($ph !== '' ? ' placeholder="' . e($ph) . '"' : '') . '>' . $err($k) . '</label>';
};
$area = function ($k, $label) use ($t, $v, $err) {
    return '<label class="f f-half"><span>' . e($t[$label]) . '</span><textarea name="' . $k . '" data-label="' . e($t[$label]) . '" rows="7" maxlength="20000">' . $v($k) . '</textarea>' . $err($k) . '</label>';
};
?>
<h1 class="adm-h1"><?= e($t['adm_menu_site']) ?></h1>

<form method="post" action="<?= e($base) ?>?page=site" data-confirm-changes data-confirm-title="<?= e($t['adm_menu_site']) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">

    <?php /* ---- Contact + social links ---- */ ?>
    <div class="card form-grid">
        <h2><?= e($t['site_contact']) ?></h2>
        <?= $in('phone', 'site_phone', 'text', '01XXXXXXXXX') ?>
        <?= $in('whatsapp', 'site_whatsapp', 'text', '01XXXXXXXXX') ?>
        <?= $in('email', 'site_email', 'email', 'name@gmail.com') ?>
        <?= $in('address_bn', 'site_address_bn') ?>
        <?= $in('address_en', 'site_address_en') ?>
        <?= $in('hours_bn', 'site_hours_bn') ?>
        <?= $in('hours_en', 'site_hours_en') ?>
        <?= $in('facebook', 'site_facebook', 'url', 'https://facebook.com/...') ?>
        <?= $in('youtube', 'site_youtube', 'url', 'https://youtube.com/...') ?>
        <?= $in('instagram', 'site_instagram', 'url', 'https://instagram.com/...') ?>
        <?= $in('linkedin', 'site_linkedin', 'url', 'https://linkedin.com/...') ?>
    </div>

    <?php /* ---- Payment numbers (users send top-up money here) ---- */ ?>
    <div class="card form-grid">
        <h2><?= e($t['site_pay']) ?></h2>
        <p class="f-wide note-line"><?= e($t['site_pay_hint']) ?></p>
        <?= $in('pay_bkash', 'site_pay_bkash', 'text', '01XXXXXXXXX') ?>
        <?= $in('pay_nagad', 'site_pay_nagad', 'text', '01XXXXXXXXX') ?>
        <?= $in('pay_rocket', 'site_pay_rocket', 'text', '01XXXXXXXXX') ?>
    </div>

    <?php /* ---- Business rules: office commission, days a user can report a problem ---- */ ?>
    <div class="card form-grid">
        <h2><?= e($t['site_rules']) ?></h2>
        <p class="f-wide note-line"><?= e($t['site_rules_hint']) ?></p>
        <?= $in('commission', 'site_commission', 'number', '15') ?>
        <?= $in('complaint_days', 'site_complaint_days', 'number', '3') ?>
    </div>

    <?php /* ---- About / mission / terms / privacy texts, Bangla + English side by side ---- */ ?>
    <div class="card form-grid">
        <h2><?= e($t['site_texts']) ?></h2>
        <p class="f-wide note-line"><?= e($t['site_texts_hint']) ?></p>
        <?= $area('about_bn', 'site_about_bn') ?><?= $area('about_en', 'site_about_en') ?>
        <?= $area('mission_bn', 'site_mission_bn') ?><?= $area('mission_en', 'site_mission_en') ?>
        <?= $area('terms_bn', 'site_terms_bn') ?><?= $area('terms_en', 'site_terms_en') ?>
        <?= $area('privacy_bn', 'site_privacy_bn') ?><?= $area('privacy_en', 'site_privacy_en') ?>
        <div class="f-wide row-btns">
            <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
            <a class="btn btn-ghost" href="about.php" target="_blank" rel="noopener"><?= e($t['site_view']) ?> ↗</a>
        </div>
    </div>
</form>
