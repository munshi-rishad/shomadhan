<?php
// Helpers for this view: red error text + red border
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" id="err-' . e($key) . '" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$inv = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
$msg = [
    'name_empty' => $t['err_name_empty'], 'name_format' => $t['err_name_format'],
    'mobile_empty' => $t['err_mobile_empty'], 'mobile_length' => $t['err_mobile_length'], 'mobile_format' => $t['err_mobile_format'],
    'email' => $t['err_email'],
    'password_empty' => $t['err_password_empty'], 'password_ascii' => $t['err_password_ascii'],
    'password_min' => $t['err_password_min'], 'password_max' => $t['err_password_max'],
    'area' => $t['err_area'], 'gender' => $t['err_gender'], 'terms' => $t['err_terms'],
];
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

        <!-- LEFT (PC) / TOP (phone) -->
        <section class="intro">
            <a class="back" href="login.php" aria-label="<?= e($t['login_back']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><span class="back-txt"><?= e($t['login_back']) ?></span></a>
            <div class="brand">
                <img class="logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
                <p class="tagline"><?= e($t['tagline']) ?></p>
            </div>
            <img class="illus-m" src="<?= e(asset('assets/images/register-illustration.svg')) ?>" alt="">
            <p class="intro-sub"><?= e($t['reg_sub1']) ?><br><?= e($t['reg_sub2']) ?></p>

            <h1 class="pc-h1"><span><?= e($t['reg_h1_a']) ?></span><span class="green"><?= e($t['reg_h1_b']) ?></span></h1>
            <p class="pc-sub"><?= e($t['reg_h1_c1']) ?><br><?= e($t['reg_h1_c2']) ?></p>
            <div class="pc-extra">
                <?php view('partials/features', ['t' => $t]); ?>
                <img class="illus-pc" src="<?= e(asset('assets/images/register-illustration.svg')) ?>" alt="">
            </div>
        </section>

        <!-- RIGHT (PC) / BOTTOM (phone): the form -->
        <section class="card card--reg">
            <div class="card-head">
                <h2><?= e($t['reg_title']) ?></h2>
                <p><?= e($t['reg_pc_sub']) ?><br><?= e($t['reg_h1_c1']) ?> <?= e($t['reg_sub2']) ?></p>
            </div>

            <form class="form" method="post" action="register.php" novalidate data-check="check-availability.php" data-msg="<?= e(json_encode($msg, JSON_UNESCAPED_UNICODE)) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
                <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>

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
                            <svg class="ic eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c5 0 8.5 4 9.5 6a14 14 0 0 1-2.9 3.7M6.6 7.6A14 14 0 0 0 2.5 12c1 2 4.5 6 9.5 6 1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            <svg class="ic eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12C3.5 10 7 6 12 6s8.5 4 9.5 6c-1 2-4.5 6-9.5 6s-8.5-4-9.5-6z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <?= $err('password') ?>
                </div>

                <div class="field-row">
                    <!-- Area: dropdown with an (optional) search box -->
                    <div class="field">
                        <label id="area-label"><?= e($t['label_area']) ?></label>
                        <div class="combo">
                            <input type="hidden" name="area" value="<?= e($old['area']) ?>">
                            <button type="button" class="input combo-btn<?= $inv('area') ?>" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="area-label">
                                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></svg>
                                <span class="combo-text<?= $old['area'] === '' ? ' placeholder' : '' ?>"><?= e($old['area'] !== '' ? $old['area'] : $t['ph_area']) ?></span>
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

                <label class="check terms">
                    <input type="checkbox" name="terms" value="1"<?= $old['terms'] ? ' checked' : '' ?>><span class="box"></span>
                    <span><?= e($t['terms_1']) ?> <a href="#"><?= e($t['terms_link1']) ?></a> <?= e($t['terms_and']) ?> <a href="#"><?= e($t['terms_link2']) ?></a> <?= e($t['terms_2']) ?></span>
                </label>
                <?= $err('terms') ?>

                <button class="btn-submit" type="submit">
                    <span><?= e($t['btn_create']) ?></span>
                    <svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                </button>

                <p class="or"><span><?= e($t['or_text']) ?></span></p>

                <button class="btn-google" type="button">
                    <svg viewBox="0 0 48 48" width="20" height="20" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                    <span><?= e($t['btn_google_continue']) ?></span>
                </button>

                <p class="signup"><?= e($t['have_account']) ?> <a class="link" href="login.php"><?= e($t['go_login']) ?></a></p>
            </form>
        </section>

    </main>
</div>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-check.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
