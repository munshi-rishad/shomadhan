// public/assets/js/gps.js
// Booking page: asks the phone for its location (partials/gps-pick.php) and puts "lat, lng" in the hidden box.
// Asked once when the page opens (if not given yet) and again with the button. Optional: booking works without it.
(function () {
    'use strict';
    var box = document.querySelector('[data-gps]');
    if (!box) {
        return;
    }
    var input = box.querySelector('input[name="point"]');
    var msg = box.querySelector('.gps-msg');

    function say(text, bad) {
        msg.textContent = text;
        msg.classList.toggle('bad', !!bad);
    }
    function locate() {
        if (!window.isSecureContext || !navigator.geolocation) {
            say(box.getAttribute('data-msg-https'), true);
            return;
        }
        say(box.getAttribute('data-msg-wait'));
        navigator.geolocation.getCurrentPosition(function (pos) {
            input.value = pos.coords.latitude.toFixed(6) + ', ' + pos.coords.longitude.toFixed(6);
            say(box.getAttribute('data-msg-ok').replace('%d', Math.round(pos.coords.accuracy)));
        }, function (err) {
            say(box.getAttribute(err.code === 1 ? 'data-msg-denied' : 'data-msg-fail'), true);
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
    }
    box.querySelector('.gps-btn').addEventListener('click', locate);
    if (input.value === '') {
        locate();
    }
})();
