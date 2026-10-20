<?php // app/views/partials/who-card.php - "স্বাগতম + name" card of the logged-in person (top of the menu, every role). needs $t, $name, $photo ?>
<div class="side-user">
    <?php view('partials/avatar', ['name' => $name, 'photo' => $photo ?? null, 'class' => 'avatar']); ?>
    <span><small><?= e($t['side_hello']) ?></small><b><?= e($name) ?></b></span>
</div>
