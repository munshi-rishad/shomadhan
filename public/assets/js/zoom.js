// assets/js/zoom.js - click (or tap) a picture = see it big on a dark screen, with its real size in pixels.
// Works for every uploaded picture on the dashboards and the profile page (img inside .zoom-area, or img[data-zoom]).
// Close: click anywhere, × button or Esc.
(function () {
    var box = document.createElement('div');
    box.className = 'zoom-box';
    box.hidden = true;
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.innerHTML = '<button type="button" class="zoom-x" aria-label="close">×</button><img alt=""><p class="zoom-size"></p>';
    document.body.appendChild(box);
    var big = box.querySelector('img'), size = box.querySelector('.zoom-size');

    function zoomable(img) {
        return img && img.tagName === 'IMG' && !img.closest('.zoom-box') && !img.classList.contains('no-zoom')
            && (img.hasAttribute('data-zoom') || img.closest('.zoom-area'));
    }
    function open(img) {
        big.src = img.currentSrc || img.src;
        size.textContent = '';
        box.hidden = false;
        document.body.classList.add('cm-on');
        box.querySelector('.zoom-x').focus();
    }
    function close() {
        box.hidden = true;
        big.removeAttribute('src');
        document.body.classList.remove('cm-on');
    }
    big.addEventListener('load', function () { size.textContent = big.naturalWidth + ' x ' + big.naturalHeight + ' px'; });
    document.addEventListener('click', function (ev) {
        if (!box.hidden) { close(); return; }
        var img = ev.target.closest ? ev.target.closest('img') : null;
        if (zoomable(img) && !img.closest('a, button, .chk')) { ev.preventDefault(); open(img); }
    });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && !box.hidden) { close(); } });
})();
