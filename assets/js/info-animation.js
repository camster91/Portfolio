/**
 * Info Page Animation — "VFX Timeline"
 * Intersection Observer triggers staggered animation when content enters viewport.
 * No GSAP dependency — pure CSS animations + JS trigger.
 */
(function () {
    'use strict';

    var target = document.querySelector('[data-id="333009b"]');
    if (!target) {
        return;
    }

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.body.classList.add('info-animate-in');
        return;
    }

    if (!('IntersectionObserver' in window)) {
        document.body.classList.add('info-animate-in');
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                document.body.classList.add('info-animate-in');
                observer.disconnect();
            }
        });
    }, {
        rootMargin: '0px 0px -10% 0px',
        threshold: 0.1
    });

    observer.observe(target);
})();
