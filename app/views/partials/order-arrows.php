<?php
// app/views/partials/order-arrows.php
// ▲ ▼ buttons that move one row up / down in the admin lists (banners, services).
// Posts action=move + id + dir=up|down. The top row's ▲ and the bottom row's ▼ are disabled.
// Needs: $t, $action (form address), $id, $first, $last
?>
<form class="mover" method="post" action="<?= e($action) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="move">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <button type="submit" class="arw" name="dir" value="up" aria-label="<?= e($t['adm_move_up']) ?>" title="<?= e($t['adm_move_up']) ?>"<?= $first ? ' disabled' : '' ?>>▲</button>
    <button type="submit" class="arw" name="dir" value="down" aria-label="<?= e($t['adm_move_down']) ?>" title="<?= e($t['adm_move_down']) ?>"<?= $last ? ' disabled' : '' ?>>▼</button>
</form>
