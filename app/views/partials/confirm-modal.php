<?php
// app/views/partials/confirm-modal.php
// Our own "Are you sure?" box, used instead of the browser's confirm(). Hidden until assets/js/confirm.js opens it.
// Included once at the bottom of the admin layout and the user pages (user/home.php).
// The data-* texts are read by confirm.js. The password field shows only for changes that need the admin's password.
// Needs: $t
?>
<div class="cm-shade" id="cm" hidden role="dialog" aria-modal="true" aria-labelledby="cm-title"
     data-sure="<?= e($t['cm_sure']) ?>" data-none="<?= e($t['cm_none']) ?>" data-cancel="<?= e($t['adm_cancel']) ?>" data-close="<?= e($t['cm_close']) ?>"
     data-yes="<?= e($t['cm_yes']) ?>" data-no="<?= e($t['cm_no']) ?>" data-new-photo="<?= e($t['chg_photo_new']) ?>">
    <div class="cm-box">
        <h2 id="cm-title"></h2>
        <p id="cm-msg"></p>
        <ul class="cm-list" id="cm-list" hidden></ul>
        <label class="f" id="cm-pw-wrap" hidden>
            <span><?= e($t['acc_my_password']) ?> *</span>
            <input type="password" id="cm-pw" maxlength="128" autocomplete="current-password">
            <small><?= e($t['cm_pw_hint']) ?></small>
            <p class="error-text" id="cm-pw-err" hidden><?= e($t['cm_pw_empty']) ?></p>
        </label>
        <div class="row-btns">
            <button type="button" class="btn" id="cm-ok"><?= e($t['cm_ok']) ?></button>
            <button type="button" class="btn btn-ghost" id="cm-no"><?= e($t['adm_cancel']) ?></button>
        </div>
    </div>
</div>
