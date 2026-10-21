<?php
// app/views/partials/features.php
// The 4 feature points (trusted, nearby, fast, support) on the green side of the login,
// register, forgot-password and set-password pages. Needs: $t
?>
<ul class="features">
<li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z"/><path d="m9 12 2.2 2.2L15.5 10"/></svg><span><b><?= e($t['feat1_t']) ?></b><small><?= e($t['feat1_s']) ?></small></span></li>
<li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></svg><span><b><?= e($t['feat2_t']) ?></b><small><?= e($t['feat2_s']) ?></small></span></li>
<li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3 5 13.5h6L10 21l8-10.5h-6z" fill="currentColor"/></svg><span><b><?= e($t['feat3_t']) ?></b><small><?= e($t['feat3_s']) ?></small></span></li>
<li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/><path d="M19 20c0 1.5-2 2-5 2"/></svg><span><b><?= e($t['feat4_t']) ?></b><small><?= e($t['feat4_s']) ?></small></span></li>
</ul>
