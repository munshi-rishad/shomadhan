<?php
// app/views/partials/avatar.php
// Round profile picture, or the first letter of the name when there is no picture.
// Same look for every role. Used by the me chip, profile info, chat heads and admin lists.
// view('partials/avatar', ['name' => ..., 'photo' => ..., 'class' => 'avatar'])
$cls = $class ?? 'avatar';
?>
<?php if (!empty($photo)): ?><img class="<?= e($cls) ?> has-photo" src="<?= e($photo) ?>" alt=""><?php else: ?><span class="<?= e($cls) ?>" aria-hidden="true"><?= e(mb_substr(($name ?? '') !== '' ? $name : 'U', 0, 1, 'UTF-8')) ?></span><?php endif; ?>
