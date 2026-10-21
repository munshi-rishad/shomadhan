// chat.js - the open chat (user messages page + office messages page):
//  - starts at the newest message
//  - checks for new messages every 5 seconds (no page reload) while the page is visible
//  - Enter sends, Shift+Enter = new line; the box grows with the text
(function () {
    var chat = document.getElementById('chat');
    if (!chat) {
        return;
    }
    var body = document.getElementById('chat-body');
    var form = chat.querySelector('.chat-send');
    var box = form.querySelector('textarea');
    var lastId = parseInt(chat.dataset.last, 10) || 0;   // id of the newest message on screen
    var busy = false;                                     // a check is already running

    // ---- Scroll to the newest message ----
    function toEnd() {
        body.scrollTop = body.scrollHeight;
    }
    toEnd();

    // ---- Text box grows with the text ----
    // The scroll bar (up / down arrows) shows only when the text is taller than the biggest box.
    function grow() {
        box.style.height = 'auto';
        var height = box.scrollHeight + (box.offsetHeight - box.clientHeight);     // + top and bottom border
        box.style.height = Math.min(height, 140) + 'px';
        box.style.overflowY = height > 140 ? 'auto' : 'hidden';
    }
    box.addEventListener('input', grow);
    grow();

    // Put the cursor in the box when there is a draft, or on PC when the chat already has messages.
    if (box.value || (matchMedia('(min-width: 900px)').matches && document.querySelector('.ch-msg'))) {
        box.focus({ preventScroll: true });
    }

    // ---- Keep the blinking cursor inside the box ----
    // The PC pages use CSS zoom; Chrome then sometimes leaves the cursor at its old place after
    // the page or the chat list scrolls. Focusing the box again redraws it in the right place.
    function redrawCursor() {
        if (document.activeElement !== box) {
            return;
        }
        var start = box.selectionStart, end = box.selectionEnd;
        box.blur();
        box.focus({ preventScroll: true });
        box.setSelectionRange(start, end);
    }
    var redrawTimer = null;
    function redrawSoon() {
        clearTimeout(redrawTimer);
        redrawTimer = setTimeout(redrawCursor, 120);
    }
    window.addEventListener('scroll', redrawSoon, { passive: true });
    window.addEventListener('resize', redrawSoon);
    window.addEventListener('load', redrawSoon);
    document.addEventListener('scroll', function (e) {
        if (e.target !== document && e.target !== box) {
            redrawSoon();
        }
    }, { capture: true, passive: true });

    // ---- Enter sends, Shift+Enter = new line ----
    box.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            if (box.value.trim() !== '') {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        }
    });

    // ---- Send: empty text is not sent, the button is locked against a double send ----
    form.addEventListener('submit', function (e) {
        if (box.value.trim() === '') {
            e.preventDefault();
            box.focus();
            return;
        }
        form.querySelector('button').disabled = true;     // no double send
    });

    // ---- Ask the server for messages newer than lastId and add them to the list ----
    function check() {
        if (busy || document.hidden) {
            return;
        }
        busy = true;
        fetch(chat.dataset.live + '&after=' + lastId, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (!data || !data.last) {
                    return;
                }
                // only follow the new messages when the user was already at the bottom
                var nearBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 80;
                var holder = document.createElement('ol');
                holder.innerHTML = data.html;
                var empty = body.querySelector('.ch-empty');
                if (empty) {
                    empty.remove();
                }
                Array.prototype.forEach.call(holder.children, function (li) {
                    // a day line that is already shown is not added twice
                    if (li.classList.contains('ch-day') && body.querySelector('.ch-day[data-day="' + li.dataset.day + '"]')) {
                        return;
                    }
                    // nor a message that is already shown
                    if (li.dataset.id && body.querySelector('.ch-msg[data-id="' + li.dataset.id + '"]')) {
                        return;
                    }
                    body.appendChild(li.cloneNode(true));
                });
                lastId = data.last;
                if (nearBottom) {
                    toEnd();
                }
            })
            .catch(function () {})
            .then(function () {
                busy = false;
            });
    }
    setInterval(check, 5000);
    document.addEventListener('visibilitychange', check);
})();
