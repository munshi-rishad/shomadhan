<?php
// app/views/partials/gps-pick.php
// Booking page: the customer's phone location (optional). The browser asks once when the page opens;
// the button asks again. Saved as "lat, lng" in a hidden box, so the office sees "📍 on map" and the nearest providers.
// gps.js does the asking. Needs: $t, $value ("lat, lng" or '').
?>
<div class="uf uf-wide gps-pick" data-gps
     data-msg-denied="<?= e($t['mp_denied']) ?>" data-msg-fail="<?= e($t['mp_fail']) ?>" data-msg-https="<?= e($t['mp_https']) ?>"
     data-msg-ok="<?= e($t['mp_ok']) ?>" data-msg-wait="<?= e($t['mp_wait']) ?>">
    <input type="hidden" name="point" value="<?= e($value) ?>">
    <button class="btn btn-ghost btn-small gps-btn" type="button">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="7"/></svg>
        <?= e($t['mp_gps']) ?></button>
    <small class="gps-msg"><?= e($t[$value !== '' ? 'mp_have' : 'bk_point_hint']) ?></small>
</div>
