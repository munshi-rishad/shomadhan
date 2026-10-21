<?php
// app/views/partials/profile-forms.php
// The "edit my info" + "change password" cards. The SAME forms for admin, employee and user;
// only the page around them differs (admin/profile.php, user/profile.php).
// Needs: $t, $account, $errors, $box ('info' | 'password' = which form had the error), $old, $areas, $action (form address)

// ---- Small helpers for the fields below ----
// $err('name')  -> the error line under a field (kept in the page but hidden when there is no error, form-check.js fills it)
// $bad('name')  -> ' invalid' class for a field that has an error
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$bad = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
$role = $account['role'];

// The three password fields: name => [label key, placeholder key, autocomplete, data-rule, error text key]
$pwFields = [
    'current' => ['cp_current', 'cp_ph_current', 'current-password', 'any',      'err_password_empty'],
    'new'     => ['cp_new',     'ph_password',   'new-password',     'password', 'err_password_rule'],
    'again'   => ['cp_again',   'cp_ph_again',   'new-password',     'same:new', 'cp_err_again'],
];
// Errors are shown only on the form that was sent
$infoErr = $box === 'info';
$pwErr   = $box === 'password';
?>
<?php /* ---- Card 1: edit my info (name, email, area, gender; users also birth date + address) ---- */ ?>
<section class="card pf-form" id="info">
    <h2><?= e($t['pf_edit']) ?></h2>
    <form method="post" action="<?= e($action) ?>#info" novalidate data-check
          data-unique-url="check-availability.php" data-unique-kind="account" data-unique-role="<?= e($role) ?>" data-unique-id="<?= (int)$account['id'] ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="info">
        <?php /* email is checked live for "already used" (check-availability.php) through the data-unique-* above */ ?>
        <div class="row2">
            <div class="field">
                <label for="pf-name"><?= e($t['adm_name']) ?></label>
                <input class="box<?= $infoErr ? $bad('name') : '' ?>" id="pf-name" name="name" type="text" maxlength="50" required
                       data-rule="name" data-msg="<?= e($t['err_name_format']) ?>" value="<?= e($old['name'] ?? $account['name']) ?>">
                <?= $infoErr ? $err('name') : '' ?>
            </div>
            <div class="field">
                <label for="pf-email"><?= e($t['pf_email']) ?></label>
                <input class="box<?= $infoErr ? $bad('email') : '' ?>" id="pf-email" name="email" type="email" maxlength="60" placeholder="name@gmail.com"
                       data-rule="gmail" data-unique="email" data-msg="<?= e($t['err_email']) ?>" value="<?= e($old['email'] ?? (string)$account['email']) ?>">
                <?= $infoErr ? $err('email') : '' ?>
            </div>
            <div class="field">
                <label for="pf-area"><?= e($t['pf_area']) ?></label>
                <select class="box<?= $infoErr ? $bad('area') : '' ?>" id="pf-area" name="area">
                    <?php $cur = $old['area'] ?? $account['area']; ?>
                    <?php foreach ($areas as $ar): ?>
                        <option<?= $ar === $cur ? ' selected' : '' ?>><?= e($ar) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $infoErr ? $err('area') : '' ?>
            </div>
            <div class="field">
                <label for="pf-gender"><?= e($t['pf_gender']) ?></label>
                <select class="box<?= $infoErr ? $bad('gender') : '' ?>" id="pf-gender" name="gender">
                    <?php $cur = $old['gender'] ?? $account['gender']; ?>
                    <?php foreach (['male', 'female', 'other'] as $g): ?>
                        <option value="<?= $g ?>"<?= $g === $cur ? ' selected' : '' ?>><?= e($t['adm_g_' . $g]) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $infoErr ? $err('gender') : '' ?>
            </div>
            <?php if ($role === 'user'): /* customers: date of birth + full address (optional) */ ?>
            <div class="field">
                <label for="pf-birth"><?= e($t['pf_birth']) ?></label>
                <input class="box<?= $infoErr ? $bad('birth_date') : '' ?>" id="pf-birth" name="birth_date" type="date" max="<?= e(date('Y-m-d', strtotime('-10 years'))) ?>"
                       value="<?= e($old['birth_date'] ?? (string)($account['birth_date'] ?? '')) ?>">
                <?= $infoErr ? $err('birth_date') : '' ?>
            </div>
            <div class="field">
                <label for="pf-address"><?= e($t['pf_address']) ?></label>
                <input class="box<?= $infoErr ? $bad('address') : '' ?>" id="pf-address" name="address" type="text" maxlength="200" placeholder="<?= e($t['bk_address_ph']) ?>"
                       value="<?= e($old['address'] ?? (string)($account['address'] ?? '')) ?>">
                <?= $infoErr ? $err('address') : '' ?>
            </div>
            <?php endif; ?>
        </div>
        <p class="hint"><?= e($t[$role === 'admin' ? 'pf_mobile_note_admin' : 'pf_mobile_note']) ?></p>
        <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
    </form>
</section>

<?php /* ---- Card 2: change password (current, new, new again) ---- */ ?>
<section class="card pf-form" id="password">
    <h2><?= e($t['cp_title']) ?></h2>
    <form method="post" action="<?= e($action) ?>#password" novalidate data-check>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="password">
        <div class="row3">
        <?php foreach ($pwFields as $name => $f): ?>
            <div class="field">
                <label for="<?= e($name) ?>"><?= e($t[$f[0]]) ?></label>
                <div class="input box<?= $pwErr ? $bad($name) : '' ?>">
                    <input id="<?= e($name) ?>" name="<?= e($name) ?>" type="password" autocomplete="<?= e($f[2]) ?>" maxlength="128"
                           placeholder="<?= e($t[$f[1]]) ?>" required data-rule="<?= e($f[3]) ?>" data-msg="<?= e($t[$f[4]]) ?>">
                    <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>"><?php view('partials/eye-icons'); ?></button>
                </div>
                <?= $pwErr ? $err($name) : '' ?>
            </div>
        <?php endforeach; ?>
        </div>
        <p class="hint"><?= e($t['cp_rule']) ?></p>
        <button class="btn" type="submit"><?= e($t['cp_btn']) ?></button>
    </form>
</section>
