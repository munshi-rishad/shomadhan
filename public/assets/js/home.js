// home.js - user home page: banner slider, notification bell (language buttons: lang.js)
(function () {
    // ---- banner slider (swipe on phone, arrows + dots, moves by itself every 5 seconds)
    var slides = document.getElementById('slides');
    if (slides) {
        var dots = [].slice.call(document.querySelectorAll('.dots span'));
        var count = slides.children.length, timer = null;
        function index() { return Math.round(slides.scrollLeft / slides.clientWidth); }
        function go(i) { slides.scrollTo({ left: ((i + count) % count) * slides.clientWidth, behavior: 'smooth' }); }
        function auto() { clearInterval(timer); if (count > 1) { timer = setInterval(function () { go(index() + 1); }, 5000); } }
        slides.addEventListener('scroll', function () {
            var i = index();
            dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
        });
        var prev = document.querySelector('.slider .prev'), next = document.querySelector('.slider .next');
        if (prev) { prev.addEventListener('click', function () { go(index() - 1); auto(); }); }
        if (next) { next.addEventListener('click', function () { go(index() + 1); auto(); }); }
        dots.forEach(function (d, k) { d.addEventListener('click', function () { go(k); auto(); }); });
        auto();
    }

    // ---- notification bell: open the list, then tell the server "seen" so the red number goes away
    var bell = document.getElementById('bell'), panel = document.getElementById('notif-panel');
    var content = document.querySelector('.content'), sent = false;
    if (bell && panel) {
        bell.addEventListener('click', function (ev) {
            ev.stopPropagation();
            var open = panel.hidden;
            panel.hidden = !open;
            bell.setAttribute('aria-expanded', open ? 'true' : 'false');
            var badge = document.getElementById('bell-count');
            if (open && badge && !sent) {
                sent = true;
                var body = new URLSearchParams();
                body.append('action', 'seen');
                body.append('csrf', content.getAttribute('data-csrf'));
                fetch('user-dashboard.php', { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { if (r.ok) { badge.remove(); } })
                    .catch(function () { sent = false; });
            }
        });
        panel.addEventListener('click', function (ev) { ev.stopPropagation(); });
        document.addEventListener('click', function () { panel.hidden = true; bell.setAttribute('aria-expanded', 'false'); });
    }

    // ---- popup: close with ×, a click outside the picture, or Esc
    var popup = document.getElementById('popup');
    if (popup) {
        document.body.classList.add('no-scroll');
        var closePopup = function () { popup.classList.add('hide'); document.body.classList.remove('no-scroll'); setTimeout(function () { popup.remove(); }, 250); };
        document.getElementById('popup-x').addEventListener('click', closePopup);
        var popLink = popup.querySelector('.popup-link');
        if (popLink) { popLink.addEventListener('click', function () { setTimeout(closePopup, 100); }); }   // link opens in a new tab, popup closes here
        popup.addEventListener('click', function (ev) { if (ev.target === popup) { closePopup(); } });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && document.body.contains(popup)) { closePopup(); } });
        document.getElementById('popup-x').focus();
    }
})();
