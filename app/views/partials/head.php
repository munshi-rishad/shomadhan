<?php
// app/views/partials/head.php - the same <head> for every page: meta, fonts, base.css (shared colors + parts) and the page's own CSS
// view('partials/head', ['title' => '...', 'css' => 'admin'])
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Hind Siliguri = Bangla, Poppins = English letters and numbers -->
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/' . $css . '.css')) ?>">
