// side-menu.js - left menu on phone (user, employee and admin pages): ☰ opens it; ×, the dark area or Esc closes it.
// On PC the menu is always open and these buttons are hidden.
// The menu also keeps its scroll place: after clicking an item low in the list, the new page opens
// with the menu where it was (not back at the top).
(function () {
    var side = document.getElementById('side-menu');
    var shade = document.getElementById('side-shade');
    var menuBtn = document.getElementById('menu-btn');

    // ---- Remember / bring back the menu's scroll place (per browser tab) ----
    var KEY = 'side_menu_scroll';
    if (side) {
        var saved = null;
        try { saved = sessionStorage.getItem(KEY); } catch (e) { /* storage blocked: start at the top */ }
        if (saved !== null) {
            side.scrollTop = parseInt(saved, 10) || 0;
        }
        // the open page's item must be visible (first visit, or the menu got shorter / longer)
        var current = side.querySelector('.side-nav a.on, .side-nav a[aria-current="page"]');
        if (current) {
            var box = side.getBoundingClientRect();
            var item = current.getBoundingClientRect();
            if (item.top < box.top || item.bottom > box.bottom) {
                side.scrollTop += item.top - box.top - box.height / 3;
            }
        }
        var remember = function () {
            try { sessionStorage.setItem(KEY, String(side.scrollTop)); } catch (e) { /* ignore */ }
        };
        side.addEventListener('click', remember);
        window.addEventListener('pagehide', remember);
    }
    if (!side || !shade || !menuBtn) {
        return;
    }

    // ---- Open (true) or close (false) the menu; the page behind does not scroll while it is open ----
    function sideOpen(open) {
        side.classList.toggle('open', open);
        shade.hidden = !open;
        menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('no-scroll', open);
    }

    // ---- ☰ opens; ×, the dark area and Esc close (focus goes back to ☰) ----
    menuBtn.addEventListener('click', function () {
        sideOpen(true);
    });
    document.getElementById('side-close').addEventListener('click', function () {
        sideOpen(false);
        menuBtn.focus();
    });
    shade.addEventListener('click', function () {
        sideOpen(false);
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && side.classList.contains('open')) {
            sideOpen(false);
            menuBtn.focus();
        }
    });
})();
