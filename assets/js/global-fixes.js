document.addEventListener('DOMContentLoaded', function() {
    // Add the approved MOTOMOTUS × ARKETYPE lockup to all Colour landing and
    // artist pages. CSS reserves the final height before this enhancement runs
    // so the header cannot jump when the supplied artwork is inserted.
    const isColourPage = document.body.classList.contains('motomotus-colour-context') ||
                         document.body.classList.contains('page-id-3632') ||
                         Boolean(document.querySelector('.motomotus-container--artist'));
    const arketypeLogoUrl = window.motomotusSiteConfig && window.motomotusSiteConfig.arketypeLogoUrl;

    if (isColourPage && arketypeLogoUrl) {
        document.querySelectorAll('a[href*="/home/"]').forEach(link => {
            if (!link.querySelector('img')) {
                return;
            }

            if (link.querySelector('.motomotus-arketype-logo')) {
                return;
            }

            link.classList.add('motomotus-colour-logo-link');
            link.setAttribute('aria-label', 'MOTOMOTUS × ARKETYPE home');
            const mark = document.createElement('span');
            mark.className = 'motomotus-lockup-x';
            mark.textContent = '×';
            mark.setAttribute('aria-hidden', 'true');

            const logo = document.createElement('img');
            logo.className = 'motomotus-arketype-logo';
            logo.src = arketypeLogoUrl;
            logo.alt = '';
            logo.width = 800;
            logo.height = 123;
            logo.decoding = 'sync';
            logo.fetchPriority = 'high';
            link.appendChild(mark);
            link.appendChild(logo);
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
