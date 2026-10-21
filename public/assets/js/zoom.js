// assets/js/zoom.js - click (or tap) a PROFILE picture = see it a little bigger (small round box, not full screen).
// Only profile pictures (partials/avatar.php gives them class "has-photo"); other pictures don't zoom.
// Close: click anywhere, × button or Esc.
(function () {
    // ---- The zoom box, made once and added to the page (hidden) ----
    var box = document.createElement('div');
    box.className = 'zoom-box';
    box.hidden = true;
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.innerHTML = '<button type="button" class="zoom-x" aria-label="close">×</button><img alt="">';
    document.body.appendChild(box);
    var big = box.querySelector('img');

    // ---- Show a picture big; the page behind does not scroll (same class as the confirm box) ----
    function open(img) {
        big.src = img.currentSrc || img.src;
        box.hidden = false;
        document.body.classList.add('cm-on');
        box.querySelector('.zoom-x').focus();
    }

    // ---- Hide the box again ----
    function close() {
        box.hidden = true;
        big.removeAttribute('src');
        document.body.classList.remove('cm-on');
    }

    // ---- Any click closes an open box; a click on a profile picture opens it ----
    // Pictures inside a link, button or tick box keep their own click.
    document.addEventListener('click', function (ev) {
        if (!box.hidden) {
            close();
            return;
        }
        var img = ev.target.closest ? ev.target.closest('img.has-photo') : null;
        if (img && !img.closest('a, button, .chk, .zoom-box')) {
            ev.preventDefault();
            open(img);
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !box.hidden) {
            close();
        }
    });
})();
