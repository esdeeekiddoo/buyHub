/* =====================================================================
   app.js - the only JavaScript in the project.
   ---------------------------------------------------------------------
   Everything on this site works with JavaScript switched off. This file
   only adds convenience:

     1. show/hide password
     2. a password strength hint
     3. "these two passwords do not match" before submitting
     4. the quantity stepper on the item page
     5. the toast message timing out on its own
     6. the hamburger toggle for the nav on small screens

   Nothing here is required for an account to be created or a purchase to
   go through. Every rule that matters is enforced again on the server.
   ===================================================================== */

(function () {
    'use strict';

    /* ---------------------------------------------------------------
       1. Show / hide password
       The button carries aria-pressed so a screen reader announces the
       new state, and the label swaps too - "Show" must not still say
       "Show" once the password is visible.
       --------------------------------------------------------------- */
    document.querySelectorAll('[data-reveal]').forEach(function (button) {
        button.addEventListener('click', function () {
            var field = document.getElementById(button.getAttribute('data-reveal'));
            if (!field) { return; }

            var showing = field.type === 'text';
            field.type = showing ? 'password' : 'text';

            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            button.classList.toggle('is-on', !showing);

            // Send focus back to the field so typing continues where the
            // user left off.
            field.focus();
        });
    });

    /* ---------------------------------------------------------------
       2. Password strength
       A rough hint, not a security control. It scores on length first,
       because length matters far more than symbol soup.
       --------------------------------------------------------------- */
    var strengthInput = document.querySelector('[data-strength-input]');
    var strengthMeter = document.querySelector('[data-strength-meter]');

    if (strengthInput && strengthMeter) {
        var fill  = strengthMeter.querySelector('.strength__fill');
        var label = strengthMeter.querySelector('.strength__label');

        var score = function (value) {
            if (value.length < 6) { return 0; }

            var points = 1;
            if (value.length >= 10) { points++; }
            if (value.length >= 14) { points++; }
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) { points++; }
            if (/\d/.test(value)) { points++; }
            if (/[^A-Za-z0-9]/.test(value)) { points++; }

            return Math.min(points, 5);
        };

        var words = ['Too short', 'Weak', 'Okay', 'Good', 'Strong', 'Very strong'];

        strengthInput.addEventListener('input', function () {
            var value = strengthInput.value;

            if (value === '') {
                // Hidden, not merely emptied: an empty bar is just clutter.
                strengthMeter.hidden = true;
                return;
            }

            strengthMeter.hidden = false;
            var points = score(value);

            fill.style.width = (points / 5 * 100) + '%';
            // The data attribute drives the colour, so all the colour
            // decisions stay in CSS where they can be reviewed at a glance.
            strengthMeter.setAttribute('data-score', String(points));
            label.textContent = words[points];
        });
    }

    /* ---------------------------------------------------------------
       3. Do the two passwords match?
       Checked while typing, so the user finds out before submitting
       rather than after a round trip. The server checks it again anyway.
       --------------------------------------------------------------- */
    var original      = document.querySelector('[data-strength-input]');
    var confirmField  = document.querySelector('[data-match]');
    var matchNote     = document.querySelector('[data-match-note]');

    if (original && confirmField && matchNote) {
        confirmField.addEventListener('input', function () {
            if (confirmField.value === '') {
                matchNote.textContent = '';
                matchNote.classList.remove('is-bad', 'is-good');
                return;
            }

            var same = confirmField.value === original.value;

            matchNote.textContent = same ? 'Passwords match' : 'Passwords do not match';
            matchNote.classList.toggle('is-good', same);
            matchNote.classList.toggle('is-bad', !same);
        });
    }

    /* ---------------------------------------------------------------
       4. Quantity stepper
       Keeps the number inside the min and max the server sent, so the form
       cannot submit 999 when only 3 are left. The server checks this again
       - never trust only the browser.
       --------------------------------------------------------------- */
    var stepper = document.querySelector('.stepper');

    if (stepper) {
        var input   = stepper.querySelector('input[name="quantity"]');
        var buttons = stepper.querySelectorAll('button[data-step]');

        var clamp = function (value) {
            var min = parseInt(input.min, 10) || 1;
            var max = parseInt(input.max, 10) || min;

            if (isNaN(value)) { return min; }
            if (value < min) { return min; }
            if (value > max) { return max; }
            return value;
        };

        /* Replay the keycap-pop spring each time the number changes.
           Remove the class, force a reflow, then add it back - setting the
           same class twice in a row would not restart the animation.
           Reading offsetWidth flushes pending style changes, so the browser
           treats the class as newly added rather than unchanged. */
        var pop = function () {
            stepper.classList.remove('is-bumped');
            void stepper.offsetWidth;
            stepper.classList.add('is-bumped');
        };

        // Clear the class when the spring finishes, ready for the next press.
        stepper.addEventListener('animationend', function (event) {
            if (event.animationName === 'keycapPop') {
                stepper.classList.remove('is-bumped');
            }
        });

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var step = parseInt(button.getAttribute('data-step'), 10);
                input.value = clamp((parseInt(input.value, 10) || 1) + step);
                pop();
            });
        });

        // Correct it on blur rather than on every keystroke, so the field
        // does not fight the user mid-typing.
        input.addEventListener('blur', function () {
            input.value = clamp(parseInt(input.value, 10));
        });
    }

    /* ---------------------------------------------------------------
       Auto-submit the filter form
       -------------------------------------------------------------------
       Sort and price changes apply without pressing Apply. The submit
       button stays in the DOM for anyone using the keyboard or a screen
       reader - this only skips the click for a mouse user who has
       already changed a <select> or left a number field.

       .tagName on a <select> inside a <form> works without any options
       having been added yet, which is what you want here.
       --------------------------------------------------------------- */
    var filters = document.querySelector('.filters');

    if (filters) {
        var apply = filters.querySelector('.filters__apply');
        var sortSelect = filters.querySelector('select[name="sort"]');

        // Nothing to auto-submit if the form has no explicit apply button,
        // which would mean this page changed shape.
        if (apply) {
            var submit = function () { filters.submit(); };

            if (sortSelect) {
                sortSelect.addEventListener('change', submit);
            }

            // Price and seller fields apply on blur, not per keystroke, so
            // typing "100" does not fire a search on 1, 10 and 100.
            filters.querySelectorAll('input[name="min"], input[name="max"], input[name="seller"]')
                .forEach(function (input) {
                    input.addEventListener('change', submit);
                });
        }
    }

    /* ---------------------------------------------------------------
       Search box: "/" focuses it, like every other site
       --------------------------------------------------------------- */
    /* The search box exists in two shapes: the dashboard's .searchbar and,
       on browse, a .searchbar nested inside the .filters form. The nested
       case is still a .searchbar, so one selector covers both. */
    var searchInput = document.querySelector('.searchbar input[name="q"]');

    if (searchInput) {
        document.addEventListener('keydown', function (event) {
            // Ignore the shortcut while they are already typing somewhere
            if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            var active = document.activeElement;
            var typing = active && (
                active.tagName === 'INPUT' ||
                active.tagName === 'TEXTAREA' ||
                active.tagName === 'SELECT'
            );

            if (typing) { return; }

            // preventDefault stops "/" being typed into the page
            event.preventDefault();
            searchInput.focus();
            searchInput.select();
        });
    }

    /* ---------------------------------------------------------------
       5. Toast
       The server sends the message; this just removes it after a few
       seconds so it does not sit there covering the page.
       --------------------------------------------------------------- */
    var toast = document.querySelector('.toast');

    if (toast) {
        window.setTimeout(function () {
            toast.style.transition = 'opacity 240ms ease';
            toast.style.opacity = '0';
            window.setTimeout(function () { toast.remove(); }, 260);
        }, 5000);
    }

    /* ---------------------------------------------------------------
       6. Mobile nav toggle
       The button only appears on small screens (CSS), but the handler
       is harmless anywhere: it flips a class, updates aria-expanded so
       screen readers announce the change, and closes the menu after a
       link is tapped or Escape is pressed.
       --------------------------------------------------------------- */
    var navToggle = document.querySelector('[data-nav-toggle]');
    var nav = document.getElementById('main-nav');

    if (navToggle && nav) {
        var setOpen = function (open) {
            nav.classList.toggle('nav--open', open);
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Menu');
        };

        navToggle.addEventListener('click', function () {
            setOpen(!nav.classList.contains('nav--open'));
        });

        nav.addEventListener('click', function (event) {
            if (event.target.closest('a, button')) { setOpen(false); }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { setOpen(false); }
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.topbar')) { setOpen(false); }
        });
    }
})();
