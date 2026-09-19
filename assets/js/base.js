/**
 * assets/js/base.js
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3, Rule 2.
 *
 * Generic, page-agnostic behaviour shared by every page: dismissing
 * flash messages, confirming destructive actions via a data attribute,
 * and a lightweight required-field check. Module-specific interaction
 * (live filtering, cart totals, image previews, etc.) belongs in each
 * module's own JS file, loaded after this one.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Dismiss flash messages manually or after a short delay.
    document.querySelectorAll('[data-flash]').forEach(function (flash) {
        var timer = setTimeout(function () {
            flash.remove();
        }, 6000);

        var closeBtn = flash.querySelector('[data-flash-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                clearTimeout(timer);
                flash.remove();
            });
        }
    });

    // Confirm before following a link or submitting a form marked
    // data-confirm="Message to show".
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            var message = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Basic required-field validation for forms opting in with
    // data-validate, giving instant feedback before the server round trip.
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var valid = true;

            form.querySelectorAll('[required]').forEach(function (field) {
                if (!String(field.value || '').trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                event.preventDefault();
            }
        });
    });

    // Dark Mode Toggle Logic
    var themeToggle = document.getElementById('themeToggle');
    var themeIcon = document.getElementById('themeIcon');
    
    var moonSvg = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
    var sunSvg = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';

    function updateThemeIcon(theme) {
        if (themeIcon) {
            themeIcon.innerHTML = (theme === 'dark') ? sunSvg : moonSvg;
        }
    }

    if (themeToggle) {
        var currentTheme = localStorage.getItem('theme') || 'light';
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
        updateThemeIcon(currentTheme);
        
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            var theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                updateThemeIcon('light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                updateThemeIcon('dark');
            }
        });
    }
});
