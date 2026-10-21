<?php
// app/views/partials/profile-page.php
// The ONE profile page for every role (user, employee, admin). Same look everywhere:
// Top: photo (camera = new photo + the photo rule), name, mobile, email, area + "Edit profile".
// Below: "My account" list (left on PC) and the open part (right on PC):
//   #details (default) my info   #info edit my info   #password change password
//   users only: #favourites hearted services, help = customer care, terms / about, log out
//   (staff have log out in their left menu and no info-page links inside the dashboard).
// PHONE: the list is the page; each part opens as its own screen (CSS :target) with a back button.
// Used by user/profile.php and admin/profile.php.
// Needs: $t, $account (with 'role'), $errors, $old, $box, $areas, $action (form address).
// Users also: $site, $favs (hearted sub-services), $card (service card maker).

$role   = $account['role'];
$isUser = $role === 'user';

// ---- Icons and texts used below ----
$ic = function ($p) {
    return '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">' . $p . '</svg>';
};
$arrow  = $ic('<path d="m9 5 7 7-7 7"/>');
$editIc = $ic('<path d="M4 20h4L19 9l-4-4L4 16z"/>');
$none   = '<span class="pf-none">' . e($t['pf_not_given']) . '</span>';
$icons  = [
    'person' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    'phone'  => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
    'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'lock'   => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
    'pin'    => '<path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/>',
    'gender' => '<circle cx="10" cy="14" r="5"/><path d="M14 10l6-6M15 4h5v5"/>',
    'cal'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'badge'  => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
    'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'home'   => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/>',
];

// ---- "My account" list: [link, icon, title key, small text key, open in a new tab] ----
$menu = [
    ['#details',  $icons['person'], 'pf_m_personal', $isUser ? 'pf_m_personal_sub' : 'pf_m_personal_sub_staff', false],
    ['#password', $icons['lock'],   'cp_title',      'pf_m_password_sub', false],
];
if ($isUser) {
    // "Help" link: WhatsApp chat when a WhatsApp number is set, else a phone call
    $care = $site['whatsapp'] !== '' ? 'https://wa.me/' . phone_intl($site['whatsapp']) : 'tel:+' . phone_intl($site['phone']);
    $menu[] = ['#favourites', '<path d="M12 20s-7-4.4-9-8.8C1.6 8 3.5 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.5 0 5.4 3.5 4 6.7C19 15.6 12 20 12 20z"/>', 'pf_fav', 'pf_fav_sub', false];
    $menu[] = [$care, '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/><path d="M19 20c0 1.5-2 2-5 2"/>', 'pf_m_help', 'pf_m_help_sub', $site['whatsapp'] !== ''];
    $menu[] = ['terms.php', '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>', 'pf_m_terms', 'pf_m_terms_sub', false];
    $menu[] = ['about.php', '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/>', 'page_about', 'pf_m_about_sub', false];
}

// ---- "Basic info" rows: [icon, label key, value html] ----
$basic = [
    [$icons['person'], 'pf_full_name', e($account['name'])],
    [$icons['phone'],  'pf_mobile',    e($account['mobile'])],
    [$icons['mail'],   'pf_email',     $account['email'] ? e($account['email']) : $none],
];
if ($isUser) {
    $basic[] = [$icons['cal'], 'pf_birth', !empty($account['birth_date']) ? e(date('d M Y', strtotime($account['birth_date']))) : $none];
}
$basic[] = [$icons['gender'], 'pf_gender', e($t['adm_g_' . $account['gender']])];

// ---- Second box: address (users) / account (staff: area, status, joined, last login; the role is under the name) ----
if ($isUser) {
    $box2Title = 'pf_addr_info';
    $box2 = [
        [$icons['pin'],  'pf_area',    e($account['area']) . ', ' . e($t['pf_city'])],
        [$icons['home'], 'pf_address', !empty($account['address']) ? e($account['address']) : $none],
    ];
} else {
    $box2Title = 'pf_acc_info';
    $box2 = [
        [$icons['pin'],   'pf_area',   e($account['area']) . ', ' . e($t['pf_city'])],
        [$icons['badge'], 'pf_status', e($t['adm_st_' . $account['status']])],
    ];
    if (!empty($account['created_at'])) {
        $box2[] = [$icons['cal'], 'pf_joined', e(date('d M Y', strtotime($account['created_at'])))];
    }
    if (!empty($account['last_login_at'])) {
        $box2[] = [$icons['clock'], 'pf_last_login', e(date('d M Y, h:i A', strtotime($account['last_login_at'])))];
    }
}
$hasPhoto = !empty($account['photo']);
?>
<section class="sec u-page-sec pf2 pf-<?= e($isUser ? 'user' : 'staff') ?>">
    <?php if ($isUser): ?>
        <div class="u-head"><h1><?= e($t['pf_title']) ?></h1><p><?= e($t['pf_head_sub']) ?></p></div>
    <?php else: ?>
        <div class="pf-head"><h1 class="adm-h1"><?= e($t['pf_title']) ?></h1><p class="adm-sub"><?= e($t['pf_head_sub']) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>

    <?php /* ---- Who am I: photo (camera button sends a new one at once), name, mobile, email, area ---- */ ?>
    <div class="pf-hero">
        <form class="pf-pic" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="photo">
            <?php view('partials/avatar', ['name' => $account['name'], 'photo' => $account['photo'] ?? null, 'class' => 'pf-av']); ?>
            <label class="pf-cam" title="<?= e($t[$hasPhoto ? 'ph_change' : 'ph_add']) ?>" aria-label="<?= e($t[$hasPhoto ? 'ph_change' : 'ph_add']) ?>">
                <?= $ic('<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>') ?>
                <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" hidden onchange="this.form.submit()">
            </label>
        </form>
        <div class="pf-who">
            <h2><?= e($account['name']) ?><?php if (!$isUser): ?> <span class="pill pill-on pf-role"><?= e($t['pf_role_' . $role]) ?></span><?php endif; ?></h2>
            <p><?= $ic($icons['phone']) ?><?= e($account['mobile']) ?></p>
            <?php if ($account['email']): ?><p><?= $ic($icons['mail']) ?><?= str_replace('@', '@<wbr>', e($account['email'])) ?></p><?php endif; ?>
            <p><?= $ic($icons['pin']) ?><?= e($account['area']) ?>, <?= e($t['pf_city']) ?></p>
            <?php /* the photo rule: what picture the camera button takes */ ?>
            <p class="pf-ph-rule"><?= $ic('<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>') ?><?= e(sprintf($t['ph_hint'], PHOTO_MIN)) ?></p>
            <a class="pf-edit-btn" href="#info"><?= $editIc ?><?= e($t['pf_m_edit']) ?></a>
        </div>
        <?php if (!empty($errors['photo'])): ?><p class="error-text pf-photo-err" role="alert"><?= e($errors['photo']) ?></p><?php endif; ?>
    </div>

    <div class="pf-body">
        <?php /* ---- "My account" list (+ log out for users) ---- */ ?>
        <nav class="u-card pf-menu" aria-label="<?= e($t['pf_m_account']) ?>">
            <h2><?= e($t['pf_m_account']) ?></h2>
            <ul>
                <?php foreach ($menu as $m): ?>
                <li><a href="<?= e($m[0]) ?>" data-part="<?= e(ltrim($m[0], '#')) ?>"<?= $m[4] ? ' target="_blank" rel="noopener"' : '' ?>>
                    <?= $ic($m[1]) ?><span><b><?= e($t[$m[2]]) ?></b><small><?= e($t[$m[3]]) ?></small></span><?= $arrow ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($isUser): ?>
            <?php view('partials/logout-form', ['t' => $t, 'class' => 'pf-out', 'inner' =>
                $ic('<path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M20 12H9M16 8l4 4-4 4"/>') . '<span><b>' . e($t['btn_logout']) . '</b><small>' . e($t['pf_m_logout_sub']) . '</small></span>']); ?>
            <?php endif; ?>
        </nav>

        <?php /* ---- The open part (right on PC, own screen on phone) ---- */ ?>
        <div class="pf-panel">
            <a class="page-back pf-back" href="<?= e($action) ?>"><?= $ic('<path d="m15 5-7 7 7 7"/>') ?><?= e($t['login_back']) ?></a>

            <?php /* my info (opens first on PC) */ ?>
            <section class="u-card pf-sec" id="details">
                <div class="pf-sec-head">
                    <div><h2><?= e($t['pf_m_personal']) ?></h2><p><?= e($t['pf_personal_sub']) ?></p></div>
                    <a class="pf-small-btn" href="#info"><?= $editIc ?><?= e($t['adm_edit']) ?></a>
                </div>
                <?php foreach ([['pf_basic', $basic], [$box2Title, $box2]] as [$title, $rows]): ?>
                <div class="pf-box">
                    <h3><?= e($t[$title]) ?></h3>
                    <dl class="pf-rows">
                        <?php foreach ($rows as $r): ?>
                            <div><dt><?= $ic($r[0]) ?><?= e($t[$r[1]]) ?></dt><dd><?= $r[2] ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </div>
                <?php endforeach; ?>
            </section>

            <?php /* edit my info + change password (#info, #password) */ ?>
            <?php view('partials/profile-forms', [
                't'       => $t,
                'account' => $account,
                'errors'  => $errors,
                'box'     => $box,
                'old'     => $old,
                'areas'   => $areas,
                'action'  => $action,
            ]); ?>

            <?php if ($isUser): /* favourite services (hearted on the home page) */ ?>
            <section class="u-card pf-sec" id="favourites">
                <div class="pf-sec-head"><div><h2><?= e($t['pf_fav']) ?></h2><p><?= e($t['pf_fav_long']) ?></p></div></div>
                <?php if (!$favs): ?><p class="muted"><?= e($t['pf_fav_none']) ?></p><?php endif; ?>
                <div class="cards cards-grid pf-favs"><?php foreach ($favs as $x) { echo $card($x); } ?></div>
            </section>
            <?php endif; ?>
        </div>
    </div>
</section>
