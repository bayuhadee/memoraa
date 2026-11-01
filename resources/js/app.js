import Alpine from '@alpinejs/csp';
import { initLightbox } from './modules/lightbox';
import { initUploader, initReplaceForms } from './modules/uploader';
import { initUi } from './modules/ui';

/*
 * Alpine versi CSP: komponen didaftarkan di sini (tanpa eval),
 * di Blade cukup pakai nama properti/metode, mis. @click="toggle".
 */
Alpine.data('toggle', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    },
    get chevron() {
        return this.open ? 'rotate-180' : '';
    },
}));

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initUi();
    initLightbox();
    initUploader();
    initReplaceForms();
});
