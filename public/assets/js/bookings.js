// bookings.js - admin / employee bookings page.
// Picking a provider in a booking's list shows his mobile number as a "call" button at once,
// so the office can phone him, ask if he is free, and only then press "send".
// The list itself turns light green when the picked provider is from the booking's own area.
// (Listens on the whole page, so it keeps working after the live search swaps the list.)
(function () {
    function nearColour(select) {
        var opt = select.options[select.selectedIndex];
        select.classList.toggle('is-near', !!(opt && opt.parentNode.classList && opt.parentNode.classList.contains('bk-near')));
    }
    [].slice.call(document.querySelectorAll('.bk-assign select')).forEach(nearColour);
    new MutationObserver(function () {
        [].slice.call(document.querySelectorAll('.bk-assign select')).forEach(nearColour);
    }).observe(document.body, { childList: true, subtree: true });

    document.addEventListener('change', function (ev) {
        var select = ev.target;
        if (!select.matches || !select.matches('.bk-assign select')) {
            return;
        }
        nearColour(select);
        var call = select.form.parentNode.querySelector('[data-call]');
        if (!call) {
            return;
        }
        var opt = select.options[select.selectedIndex];
        var tel = opt && opt.getAttribute('data-tel');
        call.hidden = !tel;
        call.href = tel ? 'tel:' + tel : '#';
        call.querySelector('[data-call-no]').textContent = tel ? opt.getAttribute('data-mobile') : '';
    });
})();
