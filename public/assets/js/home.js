// home.js - user pages (user/home.php): banner slider, category arrows, notification bell, offer popups,
// booking price summary, favourite hearts, and "See more" categories.
// (language buttons: lang.js, left menu: side-menu.js)
(function () {
    // ---- Banner slider ----
    // Moves by itself every 5 seconds, stops while the mouse is on it,
    // drag with the mouse (PC) or swipe with a finger (phone) to go left / right, dots jump to a banner.
    var slides = document.getElementById('slides');
    if (slides) {
        var dots = [].slice.call(document.querySelectorAll('.dots span'));
        var count = slides.children.length;
        var timer = null;

        // which banner is on screen now
        function index() {
            return Math.round(slides.scrollLeft / slides.clientWidth);
        }
        // scroll to banner i (after the last one comes the first again)
        function go(i) {
            slides.scrollTo({ left: ((i + count) % count) * slides.clientWidth, behavior: 'smooth' });
        }
        // (re)start the 5 second timer
        function auto() {
            clearInterval(timer);
            if (count > 1) {
                timer = setInterval(function () {
                    go(index() + 1);
                }, 5000);
            }
        }

        // the dot of the banner on screen is green; a click on a dot goes to its banner
        slides.addEventListener('scroll', function () {
            var current = index();
            dots.forEach(function (dot, k) {
                dot.classList.toggle('on', k === current);
            });
        });
        dots.forEach(function (dot, k) {
            dot.addEventListener('click', function () {
                go(k);
            });
        });

        // mouse on the banner = stop, mouse leaves = go on
        var hover = false;
        slides.addEventListener('mouseenter', function () {
            hover = true;
            clearInterval(timer);
        });
        slides.addEventListener('mouseleave', function () {
            hover = false;
            if (!drag) {
                auto();
            }
        });

        // drag with the mouse (touch already swipes by itself)
        var drag = null;
        slides.addEventListener('mousedown', function (ev) {
            if (ev.button !== 0 || count < 2) {
                return;
            }
            drag = { x: ev.clientX, left: slides.scrollLeft, start: index(), moved: false };
            slides.classList.add('dragging');
            ev.preventDefault();
        });
        window.addEventListener('mousemove', function (ev) {
            if (!drag) {
                return;
            }
            var dx = ev.clientX - drag.x;
            if (Math.abs(dx) > 3) {
                drag.moved = true;
            }
            slides.scrollLeft = drag.left - dx;
        });
        window.addEventListener('mouseup', function (ev) {
            if (!drag) {
                return;
            }
            var dx = ev.clientX - drag.x;
            var target = drag.start;
            if (dx < -40) {             // a short drag is enough
                target++;
            } else if (dx > 40) {
                target--;
            }
            slides.classList.remove('dragging');
            target = Math.max(0, Math.min(count - 1, target));
            slides.scrollTo({ left: target * slides.clientWidth, behavior: 'smooth' });
            drag = null;
            if (!hover) {
                auto();
            }
        });
        auto();
    }

    // ---- Service categories on home: one row; if there are more, slide with the < > arrows ----
    var cats = document.getElementById('cats');
    var prev = document.getElementById('cat-prev');
    var next = document.getElementById('cat-next');
    if (cats && prev && next) {
        // hide an arrow when the row is already at that end
        var arrows = function () {
            prev.hidden = cats.scrollLeft < 2;
            next.hidden = cats.scrollLeft + cats.clientWidth >= cats.scrollWidth - 2;
        };
        prev.addEventListener('click', function () {
            cats.scrollBy({ left: -cats.clientWidth, behavior: 'smooth' });
        });
        next.addEventListener('click', function () {
            cats.scrollBy({ left: cats.clientWidth, behavior: 'smooth' });
        });
        cats.addEventListener('scroll', arrows, { passive: true });
        window.addEventListener('resize', arrows);
        arrows();
    }

    // ---- Notification bell: open the list, then tell the server "seen" so the red number goes away ----
    var bell = document.getElementById('bell');
    var panel = document.getElementById('notif-panel');
    var content = document.querySelector('.content');
    var sent = false;       // "seen" is sent only once
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
                    .then(function (response) {
                        if (response.ok) {
                            badge.remove();
                        }
                    })
                    .catch(function () {
                        sent = false;
                    });
            }
        });
        // a click inside the list keeps it open, a click anywhere else closes it
        panel.addEventListener('click', function (ev) {
            ev.stopPropagation();
        });
        document.addEventListener('click', function () {
            panel.hidden = true;
            bell.setAttribute('aria-expanded', 'false');
        });
        // bell on the phone profile page opens this list
        if (location.hash === '#notif') {
            setTimeout(function () {
                bell.click();
            }, 0);
        }
    }

    // ---- Offer popups: close with ×, a click outside the picture, or Esc. If there are more, the next one opens ----
    var popups = [].slice.call(document.querySelectorAll('.popup'));
    var showPopup = function () {
        var popup = popups.shift();
        if (!popup) {
            document.body.classList.remove('no-scroll');
            return;
        }
        popup.hidden = false;
        document.body.classList.add('no-scroll');
        var done = false;
        var closePopup = function () {
            if (done) {
                return;
            }
            done = true;
            document.removeEventListener('keydown', onKey);
            popup.classList.add('hide');          // fade out, then remove and show the next one
            setTimeout(function () {
                popup.remove();
                showPopup();
            }, 250);
        };
        var onKey = function (ev) {
            if (ev.key === 'Escape') {
                closePopup();
            }
        };
        popup.querySelector('.popup-x').addEventListener('click', closePopup);
        var popLink = popup.querySelector('.popup-link');
        if (popLink) {      // link opens in a new tab, popup closes here
            popLink.addEventListener('click', function () {
                setTimeout(closePopup, 100);
            });
        }
        popup.addEventListener('click', function (ev) {
            if (ev.target === popup) {
                closePopup();
            }
        });
        document.addEventListener('keydown', onKey);
        popup.querySelector('.popup-x').focus();
    };
    showPopup();
})();

// ---- BOOK A SERVICE: price summary changes with the coupon; "not enough in wallet" shows when the wallet can't pay ----
(function () {
    var sum = document.getElementById('bk-sum');
    if (!sum) {
        return;
    }
    var price = parseInt(sum.dataset.price, 10) || 0;
    var coupon = document.getElementById('bk-coupon');
    var low = document.getElementById('bk-low');
    var wallet = document.querySelector('input[name="payment"][value="wallet"]');
    var money = function (n) {
        return '৳ ' + n.toLocaleString('en-US');
    };

    // recount the discount and total; the coupon can't take off more than the price
    function update() {
        var option = coupon.options[coupon.selectedIndex];
        var off = Math.min(parseInt(option && option.dataset.off, 10) || 0, price);
        var total = price - off;
        document.getElementById('bk-off').textContent = '− ' + money(off);
        document.getElementById('bk-total').textContent = money(total);
        low.hidden = !(wallet && wallet.checked && (parseInt(wallet.dataset.balance, 10) || 0) < total);
    }
    coupon.addEventListener('change', update);
    document.querySelectorAll('input[name="payment"]').forEach(function (radio) {
        radio.addEventListener('change', update);
    });
    update();
})();

// ---- HEART on a service card: favourite on / off without leaving the page (Profile -> প্রিয় সেবা lists them) ----
document.addEventListener('click', function (e) {
    var heart = e.target.closest('.fav-btn');
    if (!heart) {
        return;
    }
    e.preventDefault();
    var main = document.querySelector('[data-csrf]');
    var body = new URLSearchParams({ action: 'fav', id: heart.dataset.id, csrf: main ? main.dataset.csrf : '' });
    heart.disabled = true;
    fetch('user-dashboard.php', { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data.ok) {
                return;
            }
            // the same service can be on the page twice (popular + new): update every heart of it
            document.querySelectorAll('.fav-btn[data-id="' + heart.dataset.id + '"]').forEach(function (other) {
                other.classList.toggle('on', data.on);
                other.setAttribute('aria-pressed', data.on ? 'true' : 'false');
            });
            // profile favourites list: an un-hearted card leaves the list
            var wrap = heart.closest('.pf-favs .svc-wrap');
            if (wrap && !data.on) {
                wrap.remove();
            }
        })
        .catch(function () {})
        .then(function () {
            heart.disabled = false;
        });
});

// ---- Phone home categories: "See more" opens every category, "See less" folds them again ----
(function () {
    var btn = document.getElementById('cat-see');
    if (!btn) {
        return;
    }
    btn.addEventListener('click', function () {
        var row = btn.closest('.cat-row');
        var open = row.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.querySelector('span').textContent = btn.dataset[open ? 'less' : 'more'];
    });
})();
