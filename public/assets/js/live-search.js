// live-search.js - search while typing, on every search box (admin / employee lists, bookings, top-ups,
// messages, the user's service search and message search).
// How: a moment after the last key, the same address the form would open is fetched in the background,
// and the parts of the page around the search box are swapped for the new ones. The search box itself stays,
// so typing goes on without a jump; the address bar gets the new address (refresh / back keep the search).
// Parts swapped: everything next to the search form (same parent), plus any element marked data-live="name".
// The Enter key / search button still work as before (normal page load).
(function () {
    var forms = [].slice.call(document.querySelectorAll('form[role="search"]')).filter(function (f) {
        return f.querySelector('input[type="search"]:not(.combo-search)');
    });
    if (!forms.length || !window.fetch || !window.DOMParser) {
        return;
    }

    var WAIT = 350;   // ms after the last key
    var asked = 0;    // number of the latest request (older answers are dropped)

    // ---- Address the form would open (GET), from its current fields ----
    function formUrl(form) {
        var url = new URL(form.getAttribute('action') || location.pathname, location.href);
        url.search = '';
        new FormData(form).forEach(function (value, name) {
            if (String(value) !== '') {
                url.searchParams.set(name, value);
            }
        });
        return url;
    }

    // ---- Swap the elements next to `oldForm` for the ones next to `newForm` (same order) ----
    function swapAround(oldForm, newForm) {
        var parent = oldForm.parentNode;
        [].slice.call(parent.children).forEach(function (el) {
            if (el !== oldForm) { parent.removeChild(el); }
        });
        var before = true;
        [].slice.call(newForm.parentNode.children).forEach(function (el) {
            if (el === newForm) { before = false; return; }
            var copy = document.importNode(el, true);
            if (before) { parent.insertBefore(copy, oldForm); } else { parent.appendChild(copy); }
        });
    }

    function search(form) {
        var url = formUrl(form);
        var input = form.querySelector('input[type="search"]');
        // the service search with nothing typed = back to the full home page
        if (input.value.trim() === '' && form.classList.contains('home-search')) {
            location.href = url.href;
            return;
        }
        var mine = ++asked;
        form.classList.add('is-loading');
        fetch(url.href, { credentials: 'same-origin' })
            .then(function (res) { return res.ok ? res.text() : Promise.reject(res.status); })
            .then(function (html) {
                if (mine !== asked) { return; }   // a newer search is on its way
                var doc = new DOMParser().parseFromString(html, 'text/html');
                // the same search box on the new page: same class and same field name
                var name = input.getAttribute('name');
                var newForm = [].slice.call(doc.querySelectorAll('form[role="search"]')).filter(function (f) {
                    return f.className === form.dataset.liveClass && f.querySelector('input[type="search"][name="' + name + '"]');
                })[0];
                if (!newForm) {   // the page looks different now: just open it
                    location.href = url.href;
                    return;
                }
                swapAround(form, newForm);
                [].slice.call(document.querySelectorAll('[data-live]')).forEach(function (el) {
                    var fresh = doc.querySelector('[data-live="' + el.getAttribute('data-live') + '"]');
                    if (fresh) { el.replaceWith(document.importNode(fresh, true)); }
                });
                history.replaceState(null, '', url.href);
            })
            .catch(function () { /* offline / server error: the button still works */ })
            .then(function () {
                if (mine === asked) { form.classList.remove('is-loading'); }
            });
    }

    forms.forEach(function (form) {
        var input = form.querySelector('input[type="search"]:not(.combo-search)');
        var timer = null;
        var last = input.value;
        form.dataset.liveClass = form.className;   // to find the same box on the new page (before "is-loading" is added)
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (input.value === last) { return; }
                last = input.value;
                search(form);
            }, WAIT);
        });
    });
})();
