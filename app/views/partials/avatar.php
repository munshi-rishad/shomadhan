<?php
// app/views/partials/avatar.php - round profile picture, or the first letter of the name when there is no picture.
// view('partials/avatar', ['name' => ..., 'photo' => ..., 'class' => 'avatar'])  (same look for every role)
$cls = $class ?? 'avatar';
?>
<?php if (!empty($photo)): ?><img class="<?= e($cls) ?> has-photo" src="<?= e($photo) ?>" alt=""><?php else: ?><span class="<?= e($cls) ?>" aria-hidden="true"><?= e(mb_substr(($name ?? '') !== '' ? $name : 'U', 0, 1, 'UTF-8')) ?></span><?php endif; ?>
