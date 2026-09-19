/**
 * assets/js/home.js - repeatable scroll-reveal for index.php's Browse by
 * Category, Why Shop With Us and Featured Parts sections.
 *
 * Each section carries the .js-reveal class; base.css keeps it (and its
 * cards) invisible until .is-visible is present. Unlike a one-shot
 * reveal, .is-visible is toggled on AND off as the section enters and
 * leaves the viewport, so the animation replays every time it's
 * scrolled into view - down past it and back up, or vice versa - not
 * just the first time. Cards inside cascade in one after another via a
 * transition-delay set per card as an inline style in index.php.
 */

document.addEventListener('DOMContentLoaded', function () {
    var sections = document.querySelectorAll('.js-reveal');

    if (!sections.length) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        sections.forEach(function (section) {
            section.classList.add('is-visible');
        });
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            entry.target.classList.toggle('is-visible', entry.isIntersecting);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -80px 0px' });

    sections.forEach(function (section) {
        observer.observe(section);
    });
});
