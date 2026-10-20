<?php // app/views/partials/confirm-modal.php - our own "Are you sure?" box (assets/js/confirm.js runs it). needs $t ?>
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
