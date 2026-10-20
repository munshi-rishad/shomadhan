// side-menu.js - left menu on phone (user, employee and admin pages): ☰ opens it; ×, the dark area or Esc closes it.
// On PC the menu is always open and these buttons are hidden.
(function () {
    var side = document.getElementById('side-menu'), shade = document.getElementById('side-shade'), menuBtn = document.getElementById('menu-btn');
    if (!side || !shade || !menuBtn) { return; }
    function sideOpen(open) {
        side.classList.toggle('open', open);
        shade.hidden = !open;
        menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('no-scroll', open);
    }
    menuBtn.addEventListener('click', function () { sideOpen(true); });
    document.getElementById('side-close').addEventListener('click', function () { sideOpen(false); menuBtn.focus(); });
    shade.addEventListener('click', function () { sideOpen(false); });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && side.classList.contains('open')) { sideOpen(false); menuBtn.focus(); } });
})();
