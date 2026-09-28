document.addEventListener('DOMContentLoaded', function() {
    // Keep the shared MOTOMOTUS wordmark identical to Work. Add only the
    // client-supplied ARKETYPE strip below it, outside the header layout flow.
    const isColourPage = document.body.classList.contains('motomotus-colour-context') ||
                         document.body.classList.contains('page-id-3632') ||
                         Boolean(document.querySelector('.motomotus-container--artist'));
    const siteConfig = window.motomotusSiteConfig || {};
    const logoAssetUrl = siteConfig.colourLogoUrl || siteConfig.arketypeLogoUrl;
    const stripUrl = logoAssetUrl && logoAssetUrl.replace(/[^/]+$/, 'arketype-strip.svg');

    if (isColourPage && stripUrl) {
        document.querySelectorAll('a[href*="/home/"]').forEach(link => {
            const logo = link.querySelector('img');
            if (!logo || link.querySelector('.motomotus-arketype-strip')) {
                return;
            }

            link.classList.add('motomotus-colour-logo-link');
            const strip = document.createElement('img');
            strip.className = 'motomotus-arketype-strip';
            strip.src = stripUrl;
            strip.alt = '';
            strip.width = 5630;
            strip.height = 700;
            strip.decoding = 'sync';
            link.appendChild(strip);

            const syncStripWidth = () => {
                const width = logo.getBoundingClientRect().width;
                if (width > 0) {
                    strip.style.setProperty('width', `${width}px`, 'important');
                }
            };
            syncStripWidth();
            if ('ResizeObserver' in window) {
                new ResizeObserver(syncStripWidth).observe(logo);
            } else {
                window.addEventListener('resize', syncStripWidth, { passive: true });
            }
        });
    }

    // 1. Google Maps popup for the address on the Info page
    const headings = document.querySelectorAll('.elementor-heading-title');
    headings.forEach(h => {
        if (h.innerHTML.includes('158 Sterling Road') || h.innerHTML.includes('Toronto, ON')) {
            h.style.cursor = 'pointer';
            h.title = 'Open in Google Maps';
            h.addEventListener('click', () => {
                window.open('https://maps.app.goo.gl/u7fZfQeebo26GX416', '_blank');
            });
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
