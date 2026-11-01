import PhotoSwipeLightbox from 'photoswipe/lightbox';

/**
 * Lightbox untuk setiap [data-gallery]. Anak: a[data-pswp] dengan
 * data-pswp-width/height, data-caption, data-author, data-download.
 * Mendukung pinch-zoom (HP), scroll/klik zoom (desktop), geser kiri/kanan.
 */
export function initLightbox() {
    document.querySelectorAll('[data-gallery]').forEach((gallery) => {
        const lightbox = new PhotoSwipeLightbox({
            gallery,
            children: 'a[data-pswp]',
            pswpModule: () => import('photoswipe'),
            wheelToZoom: true,
            bgOpacity: 0.92,
            showHideAnimationType: 'fade',
            closeTitle: 'Tutup',
            zoomTitle: 'Perbesar',
            arrowPrevTitle: 'Sebelumnya',
            arrowNextTitle: 'Berikutnya',
            errorMsg: 'Foto tidak dapat dimuat.',
        });

        lightbox.on('uiRegister', () => {
            lightbox.pswp.ui.registerElement({
                name: 'download',
                order: 8,
                isButton: true,
                tagName: 'a',
                title: 'Download foto',
                html: '<svg class="pswp__icn" viewBox="0 0 32 32" aria-hidden="true"><path d="M16 5v14m0 0-5-5m5 5 5-5M7 25h18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                onInit: (el, pswp) => {
                    el.setAttribute('rel', 'noopener');
                    pswp.on('change', () => {
                        el.href = pswp.currSlide.data.element?.dataset.download || pswp.currSlide.data.src;
                    });
                },
            });

            lightbox.pswp.ui.registerElement({
                name: 'custom-caption',
                order: 9,
                isButton: false,
                appendTo: 'root',
                onInit: (el, pswp) => {
                    el.classList.add('pswp__custom-caption');
                    pswp.on('change', () => {
                        const src = pswp.currSlide.data.element;
                        el.replaceChildren();
                        if (!src) return;
                        const author = src.dataset.author;
                        const caption = src.dataset.caption;
                        if (author) {
                            const strong = document.createElement('strong');
                            strong.textContent = author; // textContent: aman dari XSS
                            el.append(strong);
                        }
                        if (caption) {
                            const p = document.createElement('span');
                            p.textContent = caption;
                            el.append(p);
                        }
                    });
                },
            });
        });

        lightbox.init();
    });
}
