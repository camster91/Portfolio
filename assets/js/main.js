document.addEventListener('DOMContentLoaded', function() {
    const grid = document.querySelector('.motomotus-grid');
    if (!grid) return;

    if (typeof gsap === 'undefined') {
        console.warn('Motomotus Portfolio: GSAP is not loaded. Animations will be disabled.');
    }

    const items = document.querySelectorAll('.motomotus-item');
    const filterBtns = document.querySelectorAll('.motomotus-filter-btn');
    const modal = document.getElementById('motomotus-video-modal');
    if (!modal) return;

    const modalContent = modal.querySelector('.motomotus-video-container');
    const modalCaption = modal.querySelector('.motomotus-modal-caption');
    const modalClose = modal.querySelector('.motomotus-modal-close');
    const modalOverlay = modal.querySelector('.motomotus-modal-overlay');

    // HTML-escape a string to prevent XSS when used in attribute values
    function escapeAttr(str) {
        return str.replace(/&/g, '&amp;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#39;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;');
    }

    function embedVideo(url) {
        // Clear existing content safely
        while (modalContent.firstChild) {
            modalContent.removeChild(modalContent.firstChild);
        }

        const ytRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^\"&?\/\s]{11})/i;
        const ytMatch = url.match(ytRegex);
        const vimeoRegex = /(?:vimeo\.com\/|player\.vimeo\.com\/video\/)([0-9]+)/i;
        const vimeoMatch = url.match(vimeoRegex);

        if (ytMatch && ytMatch[1]) {
            const iframe = document.createElement('iframe');
            iframe.src = 'https://www.youtube.com/embed/' + encodeURIComponent(ytMatch[1]) + '?autoplay=1&rel=0';
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'autoplay; fullscreen');
            iframe.setAttribute('allowfullscreen', '');
            modalContent.appendChild(iframe);
        } else if (vimeoMatch && vimeoMatch[1]) {
            const iframe = document.createElement('iframe');
            iframe.src = 'https://player.vimeo.com/video/' + encodeURIComponent(vimeoMatch[1]) + '?autoplay=1';
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'autoplay; fullscreen');
            iframe.setAttribute('allowfullscreen', '');
            modalContent.appendChild(iframe);
        } else {
            // Only allow http/https URLs for video src
            if (/^https?:\/\//i.test(url)) {
                const video = document.createElement('video');
                video.src = url;
                video.setAttribute('controls', '');
                video.setAttribute('autoplay', '');
                video.setAttribute('playsinline', '');
                modalContent.appendChild(video);
            }
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });

    items.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const videoUrl = item.getAttribute('data-video');
            const caption = item.getAttribute('data-caption');
            if (!videoUrl) return;

            embedVideo(videoUrl);
            // Use textContent for caption to prevent stored XSS
            modalCaption.textContent = caption || '';

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            if (typeof gsap !== 'undefined') {
                gsap.fromTo(modal, { opacity: 0 }, { opacity: 1, duration: 0.3 });
            } else {
                modal.style.opacity = '1';
            }
        });
    });

    if(filterBtns) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.getAttribute('data-filter');
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                items.forEach(item => {
                    const isVisible = (filter === 'all' || item.classList.contains(filter));

                    if (typeof gsap !== 'undefined') {
                        if (isVisible) {
                            gsap.to(item, { opacity: 1, scale: 1, duration: 0.4, display: 'block', ease: 'power2.out' });
                        } else {
                            gsap.to(item, { opacity: 0, scale: 0.8, duration: 0.4, display: 'none', ease: 'power2.out' });
                        }
                    } else {
                        item.style.display = isVisible ? 'block' : 'none';
                        item.style.opacity = isVisible ? '1' : '0';
                    }
                });
            });
        });
    }

    const closeModal = () => {
        if (typeof gsap !== 'undefined') {
            gsap.to(modal, {
                opacity: 0,
                duration: 0.3,
                onComplete: () => {
                    modal.style.display = 'none';
                    // Clear modal content safely
                    while (modalContent.firstChild) {
                        modalContent.removeChild(modalContent.firstChild);
                    }
                    modalCaption.textContent = '';
                    document.body.style.overflow = '';
                }
            });
        } else {
            modal.style.display = 'none';
            while (modalContent.firstChild) {
                modalContent.removeChild(modalContent.firstChild);
            }
            modalCaption.textContent = '';
            document.body.style.overflow = '';
        }
    };

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalOverlay) modalOverlay.addEventListener('click', closeModal);

    if (typeof gsap !== 'undefined') {
        gsap.from('.motomotus-item', { y: 50, opacity: 0, duration: 0.8, stagger: 0.1, ease: 'power3.out' });
    }
});
