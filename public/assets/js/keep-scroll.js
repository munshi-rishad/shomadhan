// keep-scroll.js - after a save, a filter, a button press (anything that sends a form) the page opens again
// at the same place on the screen, not at the top. Loaded on every page by partials/head.php.
// How: just before the form is sent, the scroll position is noted (sessionStorage) with the page it belongs to;
// when that same page opens again within a minute, it scrolls back there. A link with #something wins (it has its own place).
(function () {
    var KEY = 'keep_scroll';

    // "Which page": file + ?page= (admin pages), so list filters / tabs still count as the same page
    function pageKey() {
        var params = new URLSearchParams(location.search);
        return location.pathname + '?' + (params.get('page') || '');
    }

    function remember() {
        try {
            sessionStorage.setItem(KEY, JSON.stringify({ page: pageKey(), y: window.scrollY, t: Date.now() }));
        } catch (e) { /* storage blocked: the page just opens at the top */ }
    }

    // ---- Coming back: scroll to the noted place ----
    try {
        var saved = JSON.parse(sessionStorage.getItem(KEY) || 'null');
        sessionStorage.removeItem(KEY);
        if (saved && saved.page === pageKey() && Date.now() - saved.t < 60000 && !location.hash) {
            if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }
            var back = function () { window.scrollTo(0, saved.y); };
            document.addEventListener('DOMContentLoaded', back);
            window.addEventListener('load', back);
        }
    } catch (e) { /* nothing noted */ }

    // ---- Leaving: a form is sent (button, Enter) or a filter dropdown sends itself on change ----
    document.addEventListener('submit', remember, true);
    document.addEventListener('change', function (ev) {
        var el = ev.target;
        if (el.form && el.getAttribute('onchange')) { remember(); }
    }, true);
})();
