<?php
// app/views/partials/head.php
// The inside of <head> for every page: meta tags, fonts, the zoom script, base.css (shared colors + parts)
// and the page's own CSS file. Called right after <head> by every full-page view.
// view('partials/head', ['title' => '...', 'css' => 'admin'])
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php /* Fonts: Hind Siliguri = Bangla, Poppins = English letters and numbers */ ?>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php /* PC / laptop size + browser zoom (Ctrl + mouse wheel, Ctrl +/-):
             At browser zoom 100% every PC / laptop shows the same page, fitted to its own screen width
             (a 1920 px wide window = scale 1). When the visitor zooms the browser to 90%, 80% ... the page
             gets smaller like on any normal website, and bigger at 110%, 125% ...
             The browser keeps the chosen zoom for the whole site, so it stays the same after a refresh
             and on every other page (the script below reads it again on each page).
             --zs = the fit-to-screen scale, --bz = the browser zoom. Phones (< 1024 px) keep 1. */ ?>
    <script>
    (function () {
        var root = document.documentElement;
        var KEY = 'shomadhan_screen_scale';
        // the zoom steps Chrome / Edge / Firefox use; a measured value close to one of them is that step
        var STEPS = [0.25, 0.33, 0.5, 0.67, 0.75, 0.8, 0.9, 1, 1.1, 1.25, 1.5, 1.75, 2, 2.5, 3, 4, 5];
        // Windows / Mac display scale settings, and common real screen widths (pixels)
        var SCALES = [1, 1.25, 1.5, 1.75, 2, 2.25, 2.5, 3];
        var SCREENS = [1280, 1360, 1366, 1440, 1536, 1600, 1680, 1920, 2048, 2160, 2256, 2560, 2736, 2880, 3000, 3024, 3200, 3456, 3840, 5120];
        var ua = navigator.userAgent;
        var chromeLike = /Chrome|CriOS|Edg/.test(ua) && !/Firefox/.test(ua);
        var safari = /Safari/.test(ua) && !chromeLike;

        function near(list, value, tolerance) {
            for (var i = 0; i < list.length; i++) {
                if (Math.abs(value - list[i]) / list[i] < tolerance) { return list[i]; }
            }
            return 0;
        }
        function load() {
            try { return parseFloat(localStorage.getItem(KEY)) || 0; } catch (e) { return 0; }
        }
        function save(scale) {
            try { localStorage.setItem(KEY, scale); } catch (e) { /* private window: just not remembered */ }
        }

        // Chrome / Edge: devicePixelRatio = display scale x browser zoom, and screen.width does not change
        // with the zoom. The display scales that give a real screen width and a real zoom step are possible;
        // when more than one fits, the one remembered from an earlier visit wins, else the one nearest 100%.
        function guessScale(dpr, remembered) {
            if (!chromeLike || !window.screen || !screen.width) { return 0; }
            var best = 0;
            for (var i = 0; i < SCALES.length; i++) {
                var s = SCALES[i];
                if (!near(SCREENS, screen.width * s, 0.004) || !near(STEPS, dpr / s, 0.02)) { continue; }
                if (s === remembered) { return s; }
                if (!best || Math.abs(dpr / s - 1) < Math.abs(dpr / best - 1)) { best = s; }
            }
            return best;
        }

        // Safari keeps devicePixelRatio the same when zooming: there the window / page width tells the zoom
        function ratioZoom() {
            if (!window.outerWidth || !window.innerWidth) { return 0; }
            return near(STEPS, window.outerWidth / window.innerWidth, 0.03);
        }

        function browserZoom() {
            if (safari) { return ratioZoom() || 1; }
            var dpr = window.devicePixelRatio || 1;
            // display scale: worked out from the screen, else the one remembered from an earlier visit
            var remembered = load();
            var scale = guessScale(dpr, remembered) || remembered;
            if (!scale) {
                // first visit and nothing to go on: take this as 100%
                scale = dpr / (ratioZoom() || 1);
            }
            save(scale);
            var zoom = dpr / scale;
            return near(STEPS, zoom, 0.03) || zoom;
        }

        // Smallest page scale on a small laptop: below this the Bangla text gets too small to read
        // (0.88 = text the same size as on a 1920 PC). A smaller screen then shows a little less per screen.
        var MIN_SCALE = 0.88;

        function fit() {
            var pc = window.innerWidth >= 1024;
            var zoom = pc ? browserZoom() : 1;
            // page width the design sees = 1920 / browser zoom (90% -> 2133 px wide, so everything looks smaller)
            var fitScale = pc ? window.innerWidth * zoom / 1920 : 1;
            var base = root.classList.contains('u-page') ? 0.99 : 0.9;   // the same numbers as --zb in base.css
            var scale = pc ? Math.max(fitScale, MIN_SCALE / base) : 1;
            root.style.setProperty('--zs', scale);
            root.style.setProperty('--fit', fitScale / scale);   // < 1 when the text floor kicks in (base.css --dvh / --dvw)
            root.style.setProperty('--bz', zoom);
        }

        fit();
        window.addEventListener('resize', fit);   // changing the browser zoom also fires "resize"
        window.addEventListener('pageshow', fit); // back / forward button
    })();
    </script>
    <?php /* Shared styles first, then the page's own */ ?>
    <link rel="stylesheet" href="<?= e(asset('assets/css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/' . $css . '.css')) ?>">
    <script src="<?= e(asset('assets/js/keep-scroll.js')) ?>"></script>
