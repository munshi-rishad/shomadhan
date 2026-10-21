<?php
// app/views/admin/coupons.php - ?page=coupons
// Taka-off coupons. Each user can use a coupon once (picked when booking).
// "valid days" = only for that many days after the user opened the account (welcome coupon); "last day" = ends for everyone.
// Admin + employee: add / edit / show-hide / delete (a used coupon can only be hidden).
// Shown inside admin/layout.php (AdminController). Gets: $coupons (with uses), $edit (row or null)
// + the layout's $t, $lang, $base, $canEdit, $old, $err, $val, $hidden.
$c = $edit ?: [];
$isEdit = (bool)$edit;
?>
<h1 class="adm-h1"><?= e($t['adm_menu_coupons']) ?></h1>
<p class="adm-sub"><?= e($t['cp_sub']) ?></p>

<?php /* ---- Add / edit form ---- */ ?>
<?php if ($canEdit): ?>
<form class="card form-grid" method="post" action="<?= e($base) ?>?page=coupons<?= $isEdit ? '&edit=' . (int)$c['id'] : '' ?>" novalidate<?= $isEdit ? ' data-confirm-changes data-confirm-title="' . e($t['cp_edit']) . '"' : '' ?>>
    <h2><?= e($isEdit ? $t['cp_edit'] : $t['cp_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $isEdit ? 'save' : 'add' ?>">
    <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>">
    <label class="f"><span><?= e($t['cp_code']) ?> *</span><input type="text" name="code" maxlength="20" required style="text-transform:uppercase" placeholder="SAVE100" data-label="<?= e($t['cp_code']) ?>" value="<?= $val('code', $c['code'] ?? '') ?>"><?= $err('code') ?></label>
    <label class="f"><span><?= e($t['cp_amount']) ?> *</span><input type="number" name="amount" min="1" max="5000" required data-label="<?= e($t['cp_amount']) ?>" value="<?= $val('amount', $c['amount'] ?? '') ?>"><?= $err('amount') ?></label>
    <label class="f"><span><?= e($t['cp_min']) ?></span><input type="number" name="min_order" min="0" max="999999" placeholder="0" data-label="<?= e($t['cp_min']) ?>" value="<?= $val('min_order', $c['min_order'] ?? '') ?>"><?= $err('min_order') ?></label>
    <label class="f"><span><?= e($t['cp_days']) ?></span><input type="number" name="valid_days" min="1" max="365" placeholder="<?= e($t['cp_no_limit']) ?>" data-label="<?= e($t['cp_days']) ?>" value="<?= $val('valid_days', $c['valid_days'] ?? '') ?>"><small><?= e($t['cp_days_hint']) ?></small><?= $err('valid_days') ?></label>
    <label class="f f-half"><span><?= e($t['cp_title_bn']) ?> *</span><input type="text" name="title_bn" maxlength="100" required data-label="<?= e($t['cp_title_bn']) ?>" value="<?= $val('title_bn', $c['title_bn'] ?? '') ?>"><?= $err('title_bn') ?></label>
    <label class="f f-half"><span><?= e($t['cp_title_en']) ?> *</span><input type="text" name="title_en" maxlength="100" required data-label="<?= e($t['cp_title_en']) ?>" value="<?= $val('title_en', $c['title_en'] ?? '') ?>"><?= $err('title_en') ?></label>
    <label class="f"><span><?= e($t['cp_last']) ?></span><input type="date" name="expires_on" data-label="<?= e($t['cp_last']) ?>" value="<?= $val('expires_on', $c['expires_on'] ?? '') ?>"><small><?= e($t['cp_last_hint']) ?></small><?= $err('expires_on') ?></label>
    <label class="chk f-chk"><input type="checkbox" name="is_active" value="1" data-label="<?= e($t['adm_shown']) ?>"<?= ($old ? !empty($old['is_active']) : ($c['is_active'] ?? 1)) ? ' checked' : '' ?>><span><?= e($t['cp_active']) ?></span></label>
    <div class="f-wide row-btns">
        <button class="btn" type="submit"><?= e($isEdit ? $t['adm_save'] : $t['cp_add']) ?></button>
        <?php if ($isEdit): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=coupons"><?= e($t['adm_cancel']) ?></a><?php endif; ?>
    </div>
</form>
<?php endif; ?>

<?php /* ---- All coupons ---- */ ?>
<div class="card">
    <h2><?= e($t['cp_all']) ?></h2>
    <?php if (!$coupons): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list">
        <thead><tr><th><?= e($t['cp_code']) ?></th><th><?= e($t['cp_amount']) ?></th><th><?= e($t['cpn_title']) ?></th><th><?= e($t['cp_rules']) ?></th><th><?= e($t['cp_uses']) ?></th><th><?= e($t['adm_status']) ?></th><?php if ($canEdit): ?><th><?= e($t['acc_actions']) ?></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($coupons as $x): ?>
            <tr<?= $x['is_active'] ? '' : ' class="dim"' ?>>
                <td class="cell-name" data-label="<?= e($t['cp_code']) ?>"><b class="mono"><?= e($x['code']) ?></b></td>
                <td data-label="<?= e($t['cp_amount']) ?>"><b>৳ <?= (int)$x['amount'] ?></b></td>
                <td class="cell-wrap" data-label="<?= e($t['cpn_title']) ?>"><?= e($lang === 'en' ? $x['title_en'] : $x['title_bn']) ?></td>
                <?php /* rules: minimum order, valid days after joining, last day (or "no end") */ ?>
                <td class="cell-wrap" data-label="<?= e($t['cp_rules']) ?>"><small>
                    <?= (int)$x['min_order'] > 0 ? e(sprintf($t['wl_cp_min'], number_format($x['min_order']))) . '<br>' : '' ?>
                    <?= $x['valid_days'] !== null ? e(sprintf($t['cp_days_n'], (int)$x['valid_days'])) . '<br>' : '' ?>
                    <?= $x['expires_on'] !== null ? e(sprintf($t['wl_cp_until'], date('d-m-Y', strtotime($x['expires_on'])))) : ($x['valid_days'] === null ? e($t['wl_cp_no_end']) : '') ?></small></td>
                <td data-label="<?= e($t['cp_uses']) ?>"><?= (int)$x['uses'] ?></td>
                <td data-label="<?= e($t['adm_status']) ?>"><span class="pill <?= $x['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($x['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></td>
                <?php if ($canEdit): ?>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=coupons&amp;edit=<?= (int)$x['id'] ?>"><?= e($t['adm_edit']) ?></a>
                    <form method="post" action="<?= e($base) ?>?page=coupons">
                        <?= $hidden('toggle', $x['id']) ?>
                        <button class="btn btn-small btn-ghost" type="submit"><?= e($x['is_active'] ? $t['adm_hide'] : $t['adm_show']) ?></button>
                    </form>
                    <?php if (!(int)$x['uses']): /* a used coupon cannot be deleted, only hidden */ ?>
                    <form method="post" action="<?= e($base) ?>?page=coupons" data-confirm-danger data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                        <?= $hidden('delete', $x['id']) ?>
                        <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                    </form>
                    <?php endif; ?>
                </div></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
