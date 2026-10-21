<?php
// app/views/auth/register.php - register.php
// Create a user account: name, mobile, email (optional), password, area, gender, accept the terms.
// Rendered by AuthController::register(). Gets: $t, $lang, $errors, $notice, $old, $areas.
// validation.js checks the fields in the browser (texts in data-msg) and asks check-availability.php
// whether the mobile / email is already used. The server checks everything again.

// ---- Helpers for this view ----
// $err('name') -> red error line under a field (kept hidden when empty, the script fills it), $inv('name') -> red border
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" id="err-' . e($key) . '" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$inv = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
// Error texts for validation.js
$msg = [
    'name_empty' => $t['err_name_empty'], 'name_format' => $t['err_name_format'],
    'mobile_empty' => $t['err_mobile_empty'], 'mobile_length' => $t['err_mobile_length'], 'mobile_format' => $t['err_mobile_format'],
    'email' => $t['err_email'],
    'password_empty' => $t['err_password_empty'], 'password_ascii' => $t['err_password_ascii'],
    'password_min' => $t['err_password_min'], 'password_max' => $t['err_password_max'],
    'area' => $t['err_area'], 'gender' => $t['err_gender'], 'terms' => $t['err_terms'],
];
// Icons used more than once
$chev = '<svg class="ic chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
$person = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/></svg>';
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['reg_page_title'], 'css' => 'auth']); ?>
</head>
<body>
<div class="lang-corner"><?php view('partials/lang-switch', ['lang' => $lang]); ?></div>
<div class="page">
    <div class="bg" aria-hidden="true"></div>

    <main class="auth auth--reg">

        <?php /* ---- LEFT (PC) / TOP (phone) ---- */ ?>
        <?php view('partials/auth-intro', [
            't'     => $t,
            'back'  => 'login.php',
            'illus' => 'register',
            'sub'   => [$t['reg_sub1'], $t['reg_sub2']],
            'h1'    => [$t['reg_h1_a'], $t['reg_h1_b']],
            'pcSub' => [$t['reg_h1_c1'], $t['reg_h1_c2']],
        ]); ?>

        <?php /* ---- RIGHT (PC) / BOTTOM (phone): the form ---- */ ?>
        <section class="card card--reg">
            <div class="card-head">
                <h2><?= e($t['reg_title']) ?></h2>
                <p><?= e($t['reg_pc_sub']) ?><br><?= e($t['reg_h1_c1']) ?> <?= e($t['reg_sub2']) ?></p>
            </div>

            <form class="form" method="post" action="register.php" novalidate data-check="check-availability.php" data-msg="<?= e(json_encode($msg, JSON_UNESCAPED_UNICODE)) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
                <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>

                <?php /* ---- Name, mobile, email, password ---- */ ?>
                <div class="field">
                    <label for="name"><?= e($t['label_name']) ?></label>
                    <div class="input<?= $inv('name') ?>">
                        <?= $person ?>
                        <input id="name" name="name" type="text" autocomplete="name" autocapitalize="characters" maxlength="50"
                               placeholder="<?= e($t['ph_name']) ?>" value="<?= e($old['name']) ?>">
                    </div>
                    <?= $err('name') ?>
                </div>

                <div class="field">
                    <label for="mobile"><?= e($t['label_mobile']) ?></label>
                    <div class="input<?= $inv('mobile') ?>">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
                        <span class="cc"><select name="cc" aria-label="+880"><option value="+880">+880</option></select><?= $chev ?></span>
                        <input id="mobile" name="mobile" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="11"
                               placeholder="<?= e($t['ph_mobile']) ?>" value="<?= e($old['mobile']) ?>">
                    </div>
                    <?= $err('mobile') ?>
                </div>

                <div class="field">
                    <label for="email"><?= e($t['label_email']) ?> <span class="opt"><?= e($t['optional']) ?></span></label>
                    <div class="input<?= $inv('email') ?>">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input id="email" name="email" type="text" inputmode="email" autocomplete="email" autocapitalize="none" maxlength="60"
                               placeholder="<?= e($t['ph_email']) ?>" value="<?= e($old['email']) ?>">
                    </div>
                    <?= $err('email') ?>
                </div>

                <div class="field">
                    <label for="password"><?= e($t['label_password']) ?></label>
                    <div class="input<?= $inv('password') ?>">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" name="password" type="password" autocomplete="new-password" maxlength="128" placeholder="<?= e($t['ph_password_reg']) ?>">
                        <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>">
                            <?php view('partials/eye-icons'); ?>
                        </button>
                    </div>
                    <?= $err('password') ?>
                </div>

                <div class="field-row">
                    <?php /* ---- Area: our own dropdown with a search box (validation.js runs it); the choice goes in the hidden input ---- */ ?>
                    <div class="field">
                        <label id="area-label"><?= e($t['label_area']) ?></label>
                        <div class="combo">
                            <input type="hidden" name="area" value="<?= e($old['area']) ?>">
                            <button type="button" class="input combo-btn<?= $inv('area') ?>" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="area-label">
                                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
                                <span class="combo-text<?= $old['area'] === '' ? ' placeholder' : '' ?>"><?= e($old['area'] !== '' ? $old['area'] : $t['ph_gender']) ?></span>
                                <?= $chev ?>
                            </button>
                            <div class="combo-panel" hidden>
                                <input type="search" class="combo-search" autocomplete="off" placeholder="<?= e($t['area_search']) ?>" aria-label="<?= e($t['area_search']) ?>">
                                <ul class="combo-list" role="listbox" aria-labelledby="area-label">
                                    <?php foreach ($areas as $area): ?>
                                        <li role="option" tabindex="-1" data-value="<?= e($area) ?>"<?= $area === $old['area'] ? ' aria-selected="true"' : '' ?>><?= e($area) ?></li>
                                    <?php endforeach; ?>
                                    <li class="combo-empty" hidden><?= e($t['area_none']) ?></li>
                                </ul>
                            </div>
                        </div>
                        <?= $err('area') ?>
                    </div>

                    <?php /* ---- Gender: the same dropdown without a search box ---- */ ?>
                    <div class="field">
                        <label id="gender-label"><?= e($t['label_gender']) ?></label>
                        <div class="combo">
                            <input type="hidden" name="gender" value="<?= e($old['gender']) ?>">
                            <?php
                            $genderNames = ['male' => $t['gender_male'], 'female' => $t['gender_female'], 'other' => $t['gender_other']];
                            $genderShown = $genderNames[$old['gender']] ?? '';
                            ?>
                            <button type="button" class="input combo-btn<?= $inv('gender') ?>" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="gender-label">
                                <span class="combo-text<?= $genderShown === '' ? ' placeholder' : '' ?>"><?= e($genderShown !== '' ? $genderShown : $t['ph_gender']) ?></span>
                                <?= $chev ?>
                            </button>
                            <div class="combo-panel combo-panel--plain" hidden>
                                <ul class="combo-list" role="listbox" aria-labelledby="gender-label">
                                    <?php foreach ($genderNames as $value => $label): ?>
                                        <li role="option" tabindex="-1" data-value="<?= e($value) ?>"<?= $value === $old['gender'] ? ' aria-selected="true"' : '' ?>><?= e($label) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?= $err('gender') ?>
                    </div>
                </div>

                <?php /* ---- Accept terms + privacy ---- */ ?>
                <label class="check terms">
                    <input type="checkbox" name="terms" value="1"<?= $old['terms'] ? ' checked' : '' ?>><span class="box"></span>
                    <span><?= e($t['terms_1']) ?> <a href="terms.php" target="_blank" rel="noopener"><?= e($t['terms_link1']) ?></a> <?= e($t['terms_and']) ?> <a href="privacy.php" target="_blank" rel="noopener"><?= e($t['terms_link2']) ?></a> <?= e($t['terms_2']) ?></span>
                </label>
                <?= $err('terms') ?>

                <?php /* ---- Create button, Google button, link to login ---- */ ?>
                <button class="btn-submit" type="submit">
                    <span><?= e($t['btn_create']) ?></span>
                    <svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                </button>

                <p class="or"><span><?= e($t['or_text']) ?></span></p>

                <?php view('partials/google-btn', ['label' => $t['btn_google_continue']]); ?>

                <p class="signup"><?= e($t['have_account']) ?> <a class="link" href="login.php"><?= e($t['go_login']) ?></a></p>
            </form>
        </section>

    </main>
</div>
<?php view('partials/public-footer', ['t' => $t, 'lang' => $lang]); ?>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-check.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
