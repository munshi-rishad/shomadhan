<?php
// app/views/admin/payouts.php - ?page=payouts  (?p=ID = one provider's statement)
// Money between the office and each provider. For a cash job the provider keeps the cash and owes the office
// its commission; for a wallet job the office owes the provider the bill minus commission.
// Top: what the office must get / must pay in total. Table: each provider with jobs, work, commission, cash, settled,
// balance, and "settle" (money given or received). Statement: every job and settlement of one provider.
// Shown inside admin/layout.php (AdminController). Gets: $rows, $q, $onlyDue, $provider (+ $lines) when ?p= is open,
// $commission (%) + the layout's $t, $base, $canEdit, $hidden.
$money = function ($n) { return '৳ ' . number_format(abs((int)$n)); };
// balance pill: + office pays, - provider pays, 0 settled
$pill = function ($n) use ($t, $money) {
    $n = (int)$n;
    if ($n === 0) {
        return '<span class="pill pill-on">' . e($t['po_even']) . '</span>';
    }
    return $n > 0 ? '<span class="pill po-pay">' . e(sprintf($t['po_office_pays'], $money($n))) . '</span>'
                  : '<span class="pill pill-bad">' . e(sprintf($t['po_provider_pays'], $money($n))) . '</span>';
};
$methods = ProviderAccountModel::METHODS;
?>
<h1 class="adm-h1"><?= e($t['adm_menu_payouts']) ?></h1>
<p class="adm-sub"><?= e(sprintf($t['po_sub'], $commission)) ?></p>

<?php if ($provider): /* ================= one provider's statement ================= */ ?>
<div class="card">
    <div class="po-head">
        <h2><?= e($provider['name']) ?> <small><?= e($provider['mobile']) ?> · <?= e($provider['area']) ?></small></h2>
        <?= $pill($provider['balance']) ?>
    </div>
    <?php if (!$lines): ?><p class="empty"><?= e($t['po_no_lines']) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list">
        <thead><tr><th><?= e($t['po_when']) ?></th><th><?= e($t['po_what']) ?></th><th><?= e($t['po_bill']) ?></th><th><?= e($t['po_commission']) ?></th><th><?= e($t['po_cash']) ?></th><th><?= e($t['po_effect']) ?></th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
            <tr>
                <td data-label="<?= e($t['po_when']) ?>"><?= e(date('d-m-Y', strtotime($l['at']))) ?></td>
                <?php if ($l['kind'] === 'job'): ?>
                    <td class="cell-name" data-label="<?= e($t['po_what']) ?>"><b>#<?= e(BookingModel::code($l['id'])) ?></b> <small><?= e($t['bk_pay_' . $l['payment']]) ?></small></td>
                    <td data-label="<?= e($t['po_bill']) ?>"><?= e($money($l['bill'])) ?></td>
                    <td data-label="<?= e($t['po_commission']) ?>"><?= e($money($l['commission'])) ?></td>
                    <td data-label="<?= e($t['po_cash']) ?>"><?= e($money($l['cash'])) ?></td>
                <?php else: ?>
                    <td class="cell-name" data-label="<?= e($t['po_what']) ?>"><b><?= e($t[$l['amount'] > 0 ? 'po_got_from' : 'po_paid_to']) ?></b> <small><?= e($t['po_m_' . $l['method']]) ?><?= $l['note'] ? ' · ' . e($l['note']) : '' ?> · <?= e($l['by_name']) ?></small></td>
                    <td data-label="<?= e($t['po_bill']) ?>">—</td><td data-label="<?= e($t['po_commission']) ?>">—</td><td data-label="<?= e($t['po_cash']) ?>">—</td>
                <?php endif; ?>
                <?php /* what this line did to the balance: + office owes more, - provider owes more */ ?>
                <td data-label="<?= e($t['po_effect']) ?>"><b class="<?= $l['amount'] >= 0 ? 'po-plus' : 'po-minus' ?>"><?= $l['amount'] >= 0 ? '+' : '−' ?> <?= e($money($l['amount'])) ?></b></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php else: /* ================= all providers ================= */ ?>
<?php /* ---- Totals: what the office must get back / must pay ---- */
$get = 0; $pay = 0;
foreach ($rows as $r) { if ($r['balance'] < 0) { $get -= $r['balance']; } else { $pay += $r['balance']; } } ?>
<div class="po-cards">
    <div class="card po-card po-get"><small><?= e($t['po_total_get']) ?></small><b><?= e($money($get)) ?></b></div>
    <div class="card po-card po-give"><small><?= e($t['po_total_pay']) ?></small><b><?= e($money($pay)) ?></b></div>
</div>

<div class="card">
    <form class="list-search" method="get" action="<?= e($base) ?>" role="search">
        <input type="hidden" name="page" value="payouts">
        <input type="search" name="q" value="<?= e($q) ?>" maxlength="60" placeholder="<?= e($t['po_search']) ?>" aria-label="<?= e($t['po_search']) ?>">
        <label class="chk"><input type="checkbox" name="due" value="1"<?= $onlyDue ? ' checked' : '' ?> onchange="this.form.submit()"><span><?= e($t['po_only_due']) ?></span></label>
        <button class="btn" type="submit"><?= e($t['adm_search']) ?></button>
    </form>
    <?php if (!$rows): ?><p class="empty"><?= e($t['adm_no_match']) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list po-tbl">
        <thead><tr><th>ID</th><th><?= e($t['adm_menu_providers']) ?></th><th><?= e($t['po_jobs']) ?></th><th><?= e($t['po_work']) ?></th><th><?= e($t['po_commission']) ?></th><th><?= e($t['po_cash']) ?></th><th><?= e($t['po_settled']) ?></th><th><?= e($t['po_balance']) ?></th><th><?= e($t['acc_actions']) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr<?= $r['status'] !== 'active' ? ' class="dim"' : '' ?>>
                <td class="cell-id" data-label="ID"><?= (int)$r['id'] ?></td>
                <td class="cell-name" data-label="<?= e($t['adm_menu_providers']) ?>"><b><?= e($r['name']) ?></b><br><small><?= e($r['mobile']) ?></small></td>
                <td data-label="<?= e($t['po_jobs']) ?>"><?= (int)$r['jobs'] ?></td>
                <td data-label="<?= e($t['po_work']) ?>"><?= e($money($r['work'])) ?></td>
                <td data-label="<?= e($t['po_commission']) ?>"><?= e($money($r['commission'])) ?></td>
                <td data-label="<?= e($t['po_cash']) ?>"><?= e($money($r['cash'])) ?></td>
                <td data-label="<?= e($t['po_settled']) ?>"><?= (int)$r['paid'] === 0 ? '—' : e($money($r['paid'])) ?></td>
                <td data-label="<?= e($t['po_balance']) ?>"><?= $pill($r['balance']) ?></td>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=payouts&amp;p=<?= (int)$r['id'] ?>"><?= e($t['po_statement']) ?></a>
                    <?php if ($canEdit && (int)$r['balance'] !== 0): /* settle: the amount starts with what is left, the direction from the sign */ ?>
                    <details class="bk-pop">
                        <summary class="btn btn-small"><?= e($t['po_settle']) ?></summary>
                        <form method="post" action="<?= e($base) ?>?page=payouts<?= $q !== '' ? '&amp;q=' . e(rawurlencode($q)) : '' ?><?= $onlyDue ? '&amp;due=1' : '' ?>"
                              data-confirm-title="<?= e($t['po_settle']) ?>" data-confirm="<?= e(sprintf($t['po_settle_q'], $r['name'])) ?>" data-confirm-summary>
                            <?= $hidden('pay', $r['id']) ?>
                            <select name="dir" data-label="<?= e($t['po_dir']) ?>" aria-label="<?= e($t['po_dir']) ?>">
                                <option value="out"<?= $r['balance'] > 0 ? ' selected' : '' ?>><?= e($t['po_dir_out']) ?></option>
                                <option value="in"<?= $r['balance'] < 0 ? ' selected' : '' ?>><?= e($t['po_dir_in']) ?></option>
                            </select>
                            <input type="number" name="amount" min="1" max="1000000" required value="<?= abs((int)$r['balance']) ?>" data-label="<?= e($t['po_amount']) ?>" aria-label="<?= e($t['po_amount']) ?>">
                            <select name="method" data-label="<?= e($t['po_method']) ?>" aria-label="<?= e($t['po_method']) ?>">
                                <?php foreach ($methods as $m): ?><option value="<?= e($m) ?>"><?= e($t['po_m_' . $m]) ?></option><?php endforeach; ?>
                            </select>
                            <input type="text" name="note" maxlength="200" placeholder="<?= e($t['po_note']) ?>" data-label="<?= e($t['po_note']) ?>" aria-label="<?= e($t['po_note']) ?>">
                            <button class="btn btn-small" type="submit"><?= e($t['adm_save']) ?></button>
                        </form>
                    </details>
                    <?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php endif; ?>
