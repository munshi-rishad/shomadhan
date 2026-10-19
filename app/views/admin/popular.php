<?php // app/views/admin/popular.php - admin orders the "জনপ্রিয় সেবা" with ▲ ▼ arrows. Unticked = not popular. ?>
<h1 class="adm-h1"><?= e($t['adm_menu_popular']) ?></h1>
<p class="adm-sub"><?= e($t['adm_popular_sub']) ?></p>
<form class="card" method="post" action="admin-dashboard.php?page=popular" id="rank-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <?php if (!$items): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php endif; ?>
    <div class="table-wrap"><table class="tbl rank-tbl">
        <thead><tr><th><?= e($t['adm_rank_short']) ?></th><th><?= e($t['adm_name']) ?></th><th><?= e($t['adm_menu_services']) ?></th><th><?= e($t['adm_in_popular']) ?></th><th><?= e($t['adm_status']) ?></th></tr></thead>
        <tbody id="rank-rows">
        <?php foreach ($items as $x): $on = $x['popular_rank'] !== null; ?>
            <tr class="rank-row<?= $on ? '' : ' dim' ?>">
                <td>
                    <div class="mover">
                        <span class="rank-no"><?= $on ? (int)$x['popular_rank'] : '–' ?></span>
                        <button type="button" class="arw up" aria-label="<?= e($t['adm_move_up']) ?>" title="<?= e($t['adm_move_up']) ?>">▲</button>
                        <button type="button" class="arw down" aria-label="<?= e($t['adm_move_down']) ?>" title="<?= e($t['adm_move_down']) ?>">▼</button>
                    </div>
                    <input type="hidden" class="rank-val" name="rank[<?= (int)$x['id'] ?>]" value="<?= $on ? (int)$x['popular_rank'] : '' ?>">
                </td>
                <td><b><?= e($x['name_bn']) ?></b> <small><?= e($x['detail_bn']) ?></small></td>
                <td><?= e($x['service_bn']) ?></td>
                <td><label class="chk"><input type="checkbox" class="rank-on"<?= $on ? ' checked' : '' ?> aria-label="<?= e($t['adm_in_popular']) ?>"></label></td>
                <td><span class="pill <?= $x['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($x['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <div class="row-btns"><button class="btn" type="submit"><?= e($t['adm_save_ranking']) ?></button></div>
</form>
<script>
// ▲ ▼ move a row; ticked rows get numbers 1, 2, 3 ... from top to bottom (that is the ranking)
(function () {
    var body = document.getElementById('rank-rows');
    if (!body) { return; }
    function renumber() {
        var n = 0;
        [].slice.call(body.querySelectorAll('.rank-row')).forEach(function (tr, i, all) {
            var on = tr.querySelector('.rank-on').checked;
            if (on) { n++; }
            tr.querySelector('.rank-no').textContent = on ? n : '–';
            tr.querySelector('.rank-val').value = on ? n : '';
            tr.classList.toggle('dim', !on);
            tr.querySelector('.up').disabled = i === 0;
            tr.querySelector('.down').disabled = i === all.length - 1;
        });
    }
    body.addEventListener('click', function (ev) {
        var b = ev.target.closest('.arw');
        if (!b) { return; }
        var tr = b.closest('tr');
        if (b.classList.contains('up') && tr.previousElementSibling) { body.insertBefore(tr, tr.previousElementSibling); }
        if (b.classList.contains('down') && tr.nextElementSibling) { body.insertBefore(tr.nextElementSibling, tr); }
        renumber();
        b.focus();
    });
    body.addEventListener('change', function (ev) { if (ev.target.classList.contains('rank-on')) { renumber(); } });
    renumber();
})();
</script>
