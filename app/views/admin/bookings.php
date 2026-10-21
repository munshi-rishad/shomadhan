<?php
// app/views/admin/bookings.php - ?page=bookings
// Users' bookings (admin + employee). Tabs: pending / ongoing / completed / cancelled / complaints, search, 25 per page.
//   pending : pick a provider (nearest first) -> call -> "send" (becomes ongoing); office note about the calls
//   ongoing : change provider, "work done" with the real bill, cancel
//   completed: real bill, the user's stars / review; complaints: the user's problem -> "solved" with what was done
// Every change is shown to the user under their bell. "Message" opens the chat about that booking.
// Shown inside admin/layout.php (AdminController). Gets: $list (rows, total, p, pages), $tab, $q, $counts (per tab)
// + the layout's $t, $lang, $base, $canEdit, $hidden.
$L  = $list;
$nm = $lang === 'en' ? 'en' : 'bn';      // column suffix for names in the chosen language

// Link to this list keeping tab / search / page, with some values changed (page 1 and empty values are left out)
$url = function (array $set) use ($base, $tab, $q, $L) {
    $qs = array_merge(['page' => 'bookings', 'tab' => $tab, 'q' => $q, 'p' => $L['p']], $set);
    $qs = array_filter($qs, function ($v) { return $v !== '' && $v !== 1; });
    return $base . '?' . http_build_query($qs);
};
$formUrl = $url([]);                     // the row forms post back to the same list
?>
<h1 class="adm-h1"><?= e($t['adm_menu_bookings']) ?></h1>

<?php /* ---- Status tabs with counts ---- */ ?>
<nav class="u-tabs" aria-label="<?= e($t['adm_menu_bookings']) ?>">
    <?php foreach (array_merge(BookingModel::STATUSES, ['complaints']) as $s): /* last tab: problems users reported (count = still open) */ ?>
        <a class="u-tab<?= $tab === $s ? ' on' : '' ?>" href="<?= e($base) ?>?page=bookings&amp;tab=<?= $s ?>"<?= $tab === $s ? ' aria-current="page"' : '' ?>><?= e($t['bk_st_' . $s]) ?> (<?= (int)$counts[$s] ?>)</a>
    <?php endforeach; ?>
</nav>

<?php if ($canEdit && in_array($tab, ['pending', 'ongoing'], true)): /* how to send a provider: once, above the list */ ?>
<?php endif; ?>

<div class="card">
    <?php /* ---- Search ---- */ ?>
    <form class="list-search" method="get" action="<?= e($base) ?>" role="search">
        <input type="hidden" name="page" value="bookings"><input type="hidden" name="tab" value="<?= e($tab) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" maxlength="60" placeholder="<?= e($t['bk_adm_search']) ?>" aria-label="<?= e($t['bk_adm_search']) ?>">
        <button class="btn" type="submit"><?= e($t['adm_search']) ?></button>
        <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=bookings&amp;tab=<?= e($tab) ?>"><?= e($t['adm_clear']) ?></a><?php endif; ?>
    </form>

    <?php /* ---- Bookings table ---- */ ?>
    <?php if (!$L['rows']): ?><p class="empty"><?= e($q !== '' ? $t['adm_no_match'] : $t['bk_none_' . $tab]) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list bk-tbl">
        <thead><tr>
            <th><?= e($t['bk_id']) ?></th><th><?= e($t['bk_adm_user']) ?></th><th><?= e($t['bk_adm_service']) ?></th><th><?= e($t['bk_adm_when']) ?></th>
            <th><?= e($t['bk_address']) ?></th><th><?= e($t['bk_total']) ?></th><th><?= e($t['bk_provider']) ?></th><th><?= e($t['acc_actions']) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($L['rows'] as $b): $code = BookingModel::code($b['id']); ?>
            <tr>
                <td class="cell-id" data-label="<?= e($t['bk_id']) ?>"><b>#<?= e($code) ?></b></td>
                <td class="cell-name" data-label="<?= e($t['bk_adm_user']) ?>"><b><?= e($b['user_name']) ?></b><br><a class="tel" href="tel:+<?= e(phone_intl($b['user_mobile'])) ?>"><?= e($b['user_mobile']) ?></a></td>
                <?php $sub = $b['name_' . $nm] ?: $b['name_bn']; /* the category only when it is not the same words */ ?>
                <td data-label="<?= e($t['bk_adm_service']) ?>"><?= e($sub) ?><?php if ($b['service_' . $nm] !== $sub): ?><br><small><?= e($b['service_' . $nm]) ?></small><?php endif; ?></td>
                <td data-label="<?= e($t['bk_adm_when']) ?>"><b><?= e(date('d-m-Y', strtotime($b['scheduled_at']))) ?></b><br><small><?= e(date('h:i A', strtotime($b['scheduled_at']))) ?></small></td>
                <td class="cell-wrap" data-label="<?= e($t['bk_address']) ?>"><?= e($b['area']) ?>, <?= e($b['address']) ?><?php if ($b['lat'] !== null): /* the customer's phone location: opens in Google Maps */ ?><br><a class="bk-map" href="https://www.google.com/maps?q=<?= e($b['lat'] . ',' . $b['lng']) ?>" target="_blank" rel="noopener">📍 <?= e($t['bk_adm_on_map']) ?></a><?php endif; ?><?php if ($b['note']): ?><br><small class="bk-note">“<?= e($b['note']) ?>”</small><?php endif; ?></td>
                <?php /* amount: the real bill when the work is done and it changed (+ why), else the booked total */ ?>
                <td data-label="<?= e($t['bk_total']) ?>"><b>৳ <?= e(number_format(BookingModel::bill($b))) ?></b>
                    <?php if ($b['final_total'] !== null): ?><br><small class="bk-final"><?= e(sprintf($t['bk_adm_was'], number_format($b['total']))) ?><?= $b['final_note'] ? ' · ' . e($b['final_note']) : '' ?></small><?php endif; ?><br><small><?= e($t['bk_pay_' . $b['payment']]) ?><?= $b['coupon_code'] ? ' · ' . e($b['coupon_code']) . ' −৳' . (int)$b['discount'] : '' ?></small></td>
                <?php /* provider: a pick list (send / change), or the provider's name, or "no provider" */ ?>
                <td class="<?= $canEdit && $b['choices'] ? 'cell-full' : '' ?>" data-label="<?= e($t['bk_provider']) ?>">
                    <?php if ($canEdit && $b['choices']): ?>
                        <?php /* 1. pick a provider  2. call him (his number shows at once)  3. he is free -> "send" */ ?>
                        <form class="bk-assign" method="post" action="<?= e($formUrl) ?>"<?= $b['provider_id'] ? ' data-confirm-title="' . e($t['bk_adm_change']) . '" data-confirm-changes' : '' ?>>
                            <?= $hidden('assign', $b['id']) ?>
                            <select name="provider_id" required data-label="<?= e($t['bk_provider']) ?>" aria-label="<?= e($t['bk_provider']) ?>">
                                <?php if (!$b['provider_id']): ?><option value=""><?= e($t['bk_adm_pick']) ?></option><?php endif; ?>
                                <?php /* two groups, nearest first: near the booking's area (light green, within AreaModel::NEAR_KM), then the rest.
                                           Each line: name · area · km from the booking's area · jobs that day */ ?>
                                <?php foreach ([1, 0] as $near):
                                    $group = array_filter($b['choices'], function ($pv) use ($near) { return (int)$pv['near'] === $near; });
                                    if (!$group) { continue; } ?>
                                    <optgroup label="<?= e($near ? sprintf($t['bk_adm_near'], $b['area'], AreaModel::NEAR_KM) : $t['bk_adm_far']) ?>"<?= $near ? ' class="bk-near"' : '' ?>>
                                    <?php foreach ($group as $pv): ?>
                                        <option value="<?= (int)$pv['id'] ?>" data-tel="+<?= e(phone_intl($pv['mobile'])) ?>" data-mobile="<?= e($pv['mobile']) ?>"<?= (int)$pv['id'] === (int)$b['provider_id'] ? ' selected' : '' ?>><?= e($pv['name']) ?> · <?= e($pv['same'] ? $t['bk_adm_same'] : $pv['area'] . ($pv['km'] === null ? '' : ' · ' . sprintf($t['bk_adm_km'], $pv['km']))) ?><?= $pv['busy'] ? ' · ' . e(sprintf($t['bk_adm_busy'], (int)$pv['busy'])) : '' ?><?= $pv['on_leave'] ? ' · ' . e($t['lv_on_leave']) . ($pv['leave_note'] ? ' (' . e($pv['leave_note']) . ')' : '') : '' ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-small" type="submit"><?= e($t[$b['provider_id'] ? 'bk_adm_change' : 'bk_adm_send']) ?></button>
                        </form>
                        <?php $cur = null; foreach ($b['choices'] as $pv) { if ((int)$pv['id'] === (int)$b['provider_id']) { $cur = $pv; } } ?>
                        <a class="bk-call" data-call href="<?= $cur ? 'tel:+' . e(phone_intl($cur['mobile'])) : '#' ?>"<?= $cur ? '' : ' hidden' ?>>
                            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
                            <span data-call-no><?= $cur ? e($cur['mobile']) : '' ?></span> · <?= e($t['bk_adm_call']) ?></a>
                    <?php elseif ($b['provider_name']): ?>
                        <b><?= e($b['provider_name']) ?></b><br><a class="tel" href="tel:+<?= e(phone_intl($b['provider_mobile'])) ?>"><?= e($b['provider_mobile']) ?></a>
                    <?php elseif (in_array($b['status'], ['pending', 'ongoing'], true)): ?>
                        <span class="pill pill-bad"><?= e($t['bk_adm_no_provider']) ?></span>
                    <?php else: ?>—<?php endif; ?>
                    <?php if ($b['status'] === 'completed'): /* extra lines: points earned, why it was cancelled, who handled it */ ?><br><small class="bk-pts">+<?= (int)$b['points'] ?> <?= e($t['wl_points_word']) ?></small><?php endif; ?>
                    <?php if ($b['status'] === 'cancelled'): ?><br><small class="bk-why"><?= e($t[$b['cancelled_by'] === 'staff' ? 'bk_adm_cancel_by_staff' : 'bk_adm_cancel_by_user']) ?><?= $b['cancel_reason'] ? ': ' . e($b['cancel_reason']) : '' ?></small><?php endif; ?>
                    <?php if ($b['handled_name']): ?><br><small><?= e(sprintf($t['bk_adm_by'], $b['handled_name'])) ?></small><?php endif; ?>
                    <?php /* the user's stars + words about the work */ ?>
                    <?php if ($b['rating'] !== null): ?><br><span class="bk-stars" title="<?= (int)$b['rating'] ?>/5"><?= str_repeat('★', (int)$b['rating']) ?><s><?= str_repeat('★', 5 - (int)$b['rating']) ?></s></span><?php if ($b['review']): ?> <small class="bk-review">“<?= e($b['review']) ?>”</small><?php endif; ?><?php endif; ?>
                    <?php /* a problem the user reported, and what the office did */ ?>
                    <?php if ($b['complaint'] !== null): ?>
                        <div class="bk-complaint<?= $b['complaint_status'] === 'open' ? ' open' : '' ?>">
                            <b><?= e($t[$b['complaint_status'] === 'open' ? 'bk_cmp_open' : 'bk_cmp_solved']) ?></b> <?= e($b['complaint']) ?>
                            <?php if ($b['complaint_answer']): ?><br><small>→ <?= e($b['complaint_answer']) ?></small><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php /* office-only note (calls made ...) */ ?>
                    <?php if ($b['staff_note']): ?><br><small class="bk-staff-note">📝 <?= e($b['staff_note']) ?></small><?php endif; ?>
                </td>
                <?php /* actions: message the user, work done (ongoing), cancel with a reason (pending / ongoing) */ ?>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=messages&amp;booking=<?= (int)$b['id'] ?>"><?= e($t['msg_about_booking']) ?></a>
                    <?php if ($b['status'] === 'completed'): ?><a class="btn btn-small btn-ghost" href="receipt.php?id=<?= (int)$b['id'] ?>"><?= e($t['rc_title']) ?></a><?php endif; ?>
                    <?php if ($canEdit && $b['status'] === 'ongoing'): /* work done: the real bill (starts with the booked total) + why it changed */ ?>
                        <details class="bk-pop bk-done">
                            <summary class="btn btn-small"><?= e($t['bk_adm_done']) ?></summary>
                            <form method="post" action="<?= e($formUrl) ?>" data-confirm-title="<?= e($t['bk_adm_done']) ?>" data-confirm="<?= e(sprintf($t['bk_adm_done_q2'], $code)) ?>" data-confirm-summary>
                                <?= $hidden('complete', $b['id']) ?>
                                <label><span><?= e($t['bk_adm_final']) ?></span><input type="number" name="final" min="0" max="500000" required value="<?= (int)$b['total'] ?>" data-label="<?= e($t['bk_adm_final']) ?>"></label>
                                <input type="text" name="final_note" maxlength="200" placeholder="<?= e($t['bk_adm_final_note']) ?>" aria-label="<?= e($t['bk_adm_final_note']) ?>" data-label="<?= e($t['bk_adm_final_why']) ?>">
                                <button class="btn btn-small" type="submit"><?= e($t['bk_adm_done_go']) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($canEdit && $b['complaint_status'] === 'open'): /* complaint: what was done -> solved */ ?>
                        <details class="bk-pop">
                            <summary class="btn btn-small btn-move"><?= e($t['bk_adm_solve']) ?></summary>
                            <form method="post" action="<?= e($formUrl) ?>">
                                <?= $hidden('solve', $b['id']) ?>
                                <input type="text" name="answer" maxlength="300" required placeholder="<?= e($t['bk_adm_answer']) ?>" aria-label="<?= e($t['bk_adm_answer']) ?>">
                                <button class="btn btn-small" type="submit"><?= e($t['adm_save']) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($canEdit && in_array($b['status'], ['pending', 'ongoing'], true)): /* office note: who was called, who said no ... */ ?>
                        <details class="bk-pop">
                            <summary class="btn btn-small btn-ghost"><?= e($t['bk_adm_note']) ?></summary>
                            <form method="post" action="<?= e($formUrl) ?>">
                                <?= $hidden('note', $b['id']) ?>
                                <input type="text" name="staff_note" maxlength="300" value="<?= e($b['staff_note'] ?? '') ?>" placeholder="<?= e($t['bk_adm_note_ph']) ?>" aria-label="<?= e($t['bk_adm_note']) ?>">
                                <button class="btn btn-small" type="submit"><?= e($t['adm_save']) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($canEdit && in_array($b['status'], ['pending', 'ongoing'], true)): ?>
                        <details class="bk-cancel">
                            <summary class="btn btn-small btn-danger"><?= e($t['bk_cancel']) ?></summary>
                            <form method="post" action="<?= e($formUrl) ?>" data-confirm-danger data-confirm-title="<?= e($t['bk_cancel']) ?>"
                                  data-confirm="<?= e(sprintf($t['bk_cancel_q'], $code) . ($b['payment'] === 'wallet' ? ' ' . $t['bk_cancel_refund'] : '')) ?>">
                                <?= $hidden('cancel', $b['id']) ?>
                                <input type="text" name="reason" maxlength="200" placeholder="<?= e($t['bk_adm_reason']) ?>" aria-label="<?= e($t['bk_adm_reason']) ?>">
                                <button class="btn btn-small btn-danger" type="submit"><?= e($t['bk_adm_cancel_go']) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>

    <?php /* ---- Pages ---- */ ?>
    <?php view('partials/pager', ['t' => $t, 'p' => $L['p'], 'pages' => $L['pages'], 'url' => $url]); ?>
</div>
<script src="<?= e(asset('assets/js/bookings.js')) ?>"></script>
