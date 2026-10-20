<?php
// app/views/partials/profile-forms.php - "edit my info" + "change password" cards.
// The SAME forms for admin, employee and user (only the page around them differs).
// needs: $t, $account, $errors, $box ('info' | 'password' = which form had the error), $old, $areas, $action (form address)
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$bad = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
$role = $account['role'];
$eyes = '<svg class="ic eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c5 0 8.5 4 9.5 6a14 14 0 0 1-2.9 3.7M6.6 7.6A14 14 0 0 0 2.5 12c1 2 4.5 6 9.5 6 1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>'
      . '<svg class="ic eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12C3.5 10 7 6 12 6s8.5 4 9.5 6c-1 2-4.5 6-9.5 6s-8.5-4-9.5-6z"/><circle cx="12" cy="12" r="3"/></svg>';
$pwFields = [
    'current' => ['cp_current', 'cp_ph_current', 'current-password', 'any',      'err_password_empty'],
    'new'     => ['cp_new',     'ph_password',   'new-password',     'password', 'err_password_rule'],
    'again'   => ['cp_again',   'cp_ph_again',   'new-password',     'same:new', 'cp_err_again'],
];
$infoErr = $box === 'info';
$pwErr   = $box === 'password';
?>
<!-- edit my info -->
<section class="card pf-form" id="info">
    <h2><?= e($t['pf_edit']) ?></h2>
    <form method="post" action="<?= e($action) ?>#info" novalidate data-check>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="info">
        <div class="row2">
            <div class="field">
                <label for="pf-name"><?= e($t['adm_name']) ?></label>
                <input class="box<?= $infoErr ? $bad('name') : '' ?>" id="pf-name" name="name" type="text" maxlength="50" required
                       data-rule="name" data-msg="<?= e($t['err_name_format']) ?>" value="<?= e($old['name'] ?? $account['name']) ?>">
                <?= $infoErr ? $err('name') : '' ?>
            </div>
            <div class="field">
                <label for="pf-email"><?= e($t['pf_email']) ?></label>
                <input class="box<?= $infoErr ? $bad('email') : '' ?>" id="pf-email" name="email" type="email" maxlength="60" placeholder="name@gmail.com"<?= $role === 'admin' ? ' required' : '' ?>
                       data-rule="gmail" data-msg="<?= e($t['err_email']) ?>" value="<?= e($old['email'] ?? (string)$account['email']) ?>">
                <?= $infoErr ? $err('email') : '' ?>
            </div>
            <?php if ($role === 'user'): ?>
            <div class="field">
                <label for="pf-area"><?= e($t['pf_area']) ?></label>
                <select class="box<?= $infoErr ? $bad('area') : '' ?>" id="pf-area" name="area">
                    <?php $cur = $old['area'] ?? $account['area']; foreach ($areas as $ar): ?><option<?= $ar === $cur ? ' selected' : '' ?>><?= e($ar) ?></option><?php endforeach; ?>
                </select>
                <?= $infoErr ? $err('area') : '' ?>
            </div>
            <div class="field">
                <label for="pf-gender"><?= e($t['pf_gender']) ?></label>
                <select class="box<?= $infoErr ? $bad('gender') : '' ?>" id="pf-gender" name="gender">
                    <?php $cur = $old['gender'] ?? $account['gender']; foreach (['male', 'female', 'other'] as $g): ?><option value="<?= $g ?>"<?= $g === $cur ? ' selected' : '' ?>><?= e($t['adm_g_' . $g]) ?></option><?php endforeach; ?>
                </select>
                <?= $infoErr ? $err('gender') : '' ?>
            </div>
            <?php endif; ?>
        </div>
        <p class="hint"><?= e($t[$role === 'admin' ? 'pf_mobile_note_admin' : 'pf_mobile_note']) ?></p>
        <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
    </form>
</section>

<!-- change password -->
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
                    <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>"><?= $eyes ?></button>
                </div>
                <?= $pwErr ? $err($name) : '' ?>
            </div>
        <?php endforeach; ?>
        </div>
        <p class="hint"><?= e($t['cp_rule']) ?></p>
        <button class="btn" type="submit"><?= e($t['cp_btn']) ?></button>
    </form>
</section>
