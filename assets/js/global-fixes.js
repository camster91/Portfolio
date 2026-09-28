document.addEventListener('DOMContentLoaded', function() {
    const isColourPage = document.body.classList.contains('motomotus-colour-context') ||
                         document.body.classList.contains('page-id-3632') ||
                         Boolean(document.querySelector('.motomotus-container--artist'));
    const siteConfig = window.motomotusSiteConfig || {};

    // Give the shared header's home link a name without changing its artwork.
    document.querySelectorAll('.elementor-location-header a[href*="/home/"]').forEach(link => {
        if (link.querySelector('img') && !link.getAttribute('aria-label')) {
            link.setAttribute('aria-label', isColourPage ? 'Motomotus / Arketype — home' : 'Motomotus — home');
        }
    });

    // Keep the approved VFX label consistent across desktop and mobile menus.
    document.querySelectorAll('.e-n-menu-heading a, .mm-custom-nav a').forEach(link => {
        if (link.textContent.trim().toUpperCase() !== 'WORK') return;
        const label = link.querySelector('.e-n-menu-title-text') || link;
        label.textContent = 'VFX';
    });

    // These Elementor pages omit a page heading; retain their visible layout.
    const pageHeading = document.body.classList.contains('page-id-9') ? 'Info' :
                        document.body.classList.contains('page-id-11') ? 'VFX work' : '';
    if (pageHeading && !document.querySelector('h1')) {
        const content = document.querySelector('#content');
        if (content) {
            const heading = document.createElement('h1');
            heading.className = 'motomotus-accessible-heading';
            heading.textContent = pageHeading;
            content.prepend(heading);
        }
    }

    // Preserve the existing wordmark's layout box while the supplied lockup
    // extends below it. This keeps the menu and first grid row stationary.
    if (isColourPage && siteConfig.colourLogoUrl) {
        document.querySelectorAll('.elementor-location-header a[href*="/home/"]').forEach(link => {
            const logo = link.querySelector('img');
            if (!logo || link.querySelector('.motomotus-colour-lockup')) return;
            const lockup = document.createElement('img');
            lockup.className = 'motomotus-colour-lockup';
            lockup.alt = 'Motomotus / Arketype';
            lockup.width = 5630;
            lockup.height = 3886;
            lockup.decoding = 'sync';
            // Keep the original visible if the replacement cannot load.
            lockup.addEventListener('load', () => link.classList.add('motomotus-colour-logo-link'));
            lockup.addEventListener('error', () => lockup.remove());
            lockup.src = siteConfig.colourLogoUrl;
            link.appendChild(lockup);
            const syncWidth = () => {
                const width = logo.getBoundingClientRect().width;
                if (width > 0) lockup.style.setProperty('width', `${width}px`, 'important');
            };
            syncWidth();
            if ('ResizeObserver' in window) new ResizeObserver(syncWidth).observe(logo);
            else window.addEventListener('resize', syncWidth, { passive: true });
        });
    }

    // PHP supplies a draft preview only to users who may edit Colour; the
    // public route is supplied automatically once that page is published.
    if (siteConfig.colourNavUrl) {
        document.querySelectorAll('.e-n-menu-heading, .mm-custom-nav').forEach(menu => {
            if (menu.querySelector('.motomotus-colour-nav')) return;
            const info = Array.from(menu.querySelectorAll('a')).find(a => a.textContent.trim().toUpperCase() === 'INFO');
            if (!info) return;
            const original = info.closest('.e-n-menu-item') || info;
            const item = original.cloneNode(true);
            item.classList.add('motomotus-colour-nav');
            [item, ...item.querySelectorAll('*')].forEach(el => {
                el.removeAttribute('id');
                el.removeAttribute('aria-current');
                el.classList.remove('e-current');
            });
            const anchor = item.matches('a') ? item : item.querySelector('a');
            anchor.href = siteConfig.colourNavUrl;
            const label = anchor.querySelector('.e-n-menu-title-text') || anchor;
            label.textContent = 'COLOUR';
            if (document.body.classList.contains('page-id-3632')) anchor.setAttribute('aria-current', 'page');
            original.before(item);
            menu.querySelectorAll('.e-n-menu-title-container').forEach((a, index) => a.dataset.focusIndex = String(index + 1));
        });
    }

    // Center the Colour names in the viewport, accounting for the theme's
    // header, admin toolbar and content margins without fixed offsets.
    const colourLanding = document.querySelector('.motomotus-colour-landing');
    if (colourLanding) {
        const alignColourLanding = () => {
            const top = Math.max(0, colourLanding.getBoundingClientRect().top + window.scrollY);
            colourLanding.style.setProperty('--motomotus-colour-top', `${top}px`);
            // clientWidth excludes a visible scrollbar, unlike 100vw.
            colourLanding.style.setProperty('--motomotus-colour-width', `${document.documentElement.clientWidth}px`);
        };
        alignColourLanding();
        window.addEventListener('load', alignColourLanding, { once: true });
        window.addEventListener('resize', alignColourLanding);
        if (document.fonts) document.fonts.ready.then(alignColourLanding);
    }

    // 1. Use a real link for the Info address so keyboard users can open Maps.
    const headings = document.querySelectorAll('.elementor-heading-title');
    headings.forEach(h => {
        if (h.innerHTML.includes('158 Sterling Road') || h.innerHTML.includes('Toronto, ON')) {
            h.title = 'Open in Google Maps';
            // The mobile Elementor heading already contains an address link.
            if (h.closest('a') || h.querySelector('a')) return;
            const link = document.createElement('a');
            link.className = 'motomotus-address-link';
            link.href = 'https://maps.app.goo.gl/u7fZfQeebo26GX416';
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            while (h.firstChild) link.appendChild(h.firstChild);
            h.appendChild(link);
        }
    });

    // 2. Contact Info Animate In (Info / Contact pages)
    const isInfoPage = document.querySelector('.elementor-11') ||
                       document.querySelector('.elementor-9') ||
                       document.body.innerHTML.includes('MORGAN CAMPBELL');
    if (isInfoPage) {
        const possibleContacts = document.querySelectorAll('.elementor-widget-heading, .elementor-widget-text-editor');
        const contactBlocks = [];

        possibleContacts.forEach(el => {
            const text = el.innerText || '';
            // Match contact info blocks: names, emails, or phone numbers
            if (/MORGAN CAMPBELL|LAUREN REMPEL|@motomotus\.com|\b416[\s\-]?\d{3}[\s\-]?\d{4}\b|\b647[\s\-]?\d{3}[\s\-]?\d{4}\b/.test(text)) {
                const parent = el.closest('.e-con-child') || el;
                if (!contactBlocks.includes(parent)) {
                    contactBlocks.push(parent);
                }
            }
        });

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });

        contactBlocks.forEach((block, index) => {
            block.style.opacity = '0';
            block.style.transform = 'translateY(20px)';
            block.style.transition = `opacity 0.8s ease-out ${index * 0.15}s, transform 0.8s ease-out ${index * 0.15}s`;
            observer.observe(block);
        });
    }
});
