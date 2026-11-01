/** Interaksi kecil berbasis atribut data-* (tanpa inline script, ramah CSP). */

function confirmations() {
    // <form data-confirm="..."> atau <button data-confirm="..."> di dalam form.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const message = e.submitter?.dataset.confirm || form.dataset.confirm;
        if (message && !window.confirm(message)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);
}

function autosubmit() {
    document.querySelectorAll('[data-autosubmit]').forEach((el) => {
        el.addEventListener('change', () => el.form?.requestSubmit());
    });
}

function copyButtons() {
    document.querySelectorAll('[data-copy-target]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const target = document.querySelector(btn.dataset.copyTarget);
            const text = target?.value ?? target?.textContent ?? '';
            const original = btn.dataset.label || btn.textContent;
            btn.dataset.label = original;
            try {
                await navigator.clipboard.writeText(text.trim());
                btn.textContent = 'Tersalin ✓';
            } catch {
                target?.select?.();
                btn.textContent = 'Tekan Ctrl+C untuk menyalin';
            }
            setTimeout(() => (btn.textContent = original), 2000);
        });
    });
}

function printButtons() {
    document.querySelectorAll('[data-print]').forEach((btn) => btn.addEventListener('click', () => window.print()));
}

function reveal() {
    const items = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    items.forEach((el) => io.observe(el));
}

function navbar() {
    const nav = document.querySelector('[data-navbar]');
    if (!nav) return;
    const scrolled = (nav.dataset.scrolledClass || '').split(' ').filter(Boolean);
    const top = (nav.dataset.topClass || '').split(' ').filter(Boolean);
    const update = () => {
        const isScrolled = window.scrollY > 40;
        nav.classList.toggle('is-scrolled', isScrolled);
        scrolled.forEach((c) => nav.classList.toggle(c, isScrolled));
        top.forEach((c) => nav.classList.toggle(c, !isScrolled));
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
}

function heroSlider() {
    const slider = document.querySelector('[data-hero-slider]');
    if (!slider) return;
    const slides = [...slider.querySelectorAll('.hero-slide')];
    const dots = [...slider.querySelectorAll('[data-hero-dot]')];
    if (slides.length < 2) return;
    let index = 0;
    let timer;
    const show = (i) => {
        index = (i + slides.length) % slides.length;
        slides.forEach((s, n) => s.classList.toggle('is-active', n === index));
        dots.forEach((d, n) => {
            d.classList.toggle('bg-white', n === index);
            d.classList.toggle('w-8', n === index);
            d.setAttribute('aria-current', n === index ? 'true' : 'false');
        });
    };
    const start = () => {
        clearInterval(timer);
        timer = setInterval(() => show(index + 1), 5500);
    };
    dots.forEach((d, n) => d.addEventListener('click', () => { show(n); start(); }));
    show(0);
    start();
}

/** Galeri: muat halaman berikutnya otomatis saat mendekati bawah. */
function infiniteScroll() {
    const container = document.querySelector('[data-infinite]');
    const sentinel = document.querySelector('[data-infinite-sentinel]');
    const fallback = document.querySelector('[data-infinite-fallback]');
    if (!container || !sentinel) return;

    let next = container.dataset.next;
    let loading = false;
    if (fallback) fallback.hidden = true;
    if (!next) { sentinel.hidden = true; return; }

    const load = async () => {
        if (loading || !next) return;
        loading = true;
        try {
            const res = await fetch(next, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
            const data = await res.json();
            container.insertAdjacentHTML('beforeend', data.html); // HTML dari server, sudah di-escape oleh Blade
            next = data.next;
        } catch {
            if (fallback) fallback.hidden = false;
            next = null;
        }
        loading = false;
        if (!next) { sentinel.hidden = true; io.disconnect(); }
    };

    const io = new IntersectionObserver((entries) => entries[0].isIntersecting && load(), { rootMargin: '600px' });
    io.observe(sentinel);
}

/** Foto Saya: muat ulang halaman setelah semua foto selesai diproses. */
function pollPhotoStatus() {
    const el = document.querySelector('[data-poll-status]');
    if (!el) return;
    let tries = 0;
    const tick = async () => {
        tries++;
        try {
            const res = await fetch(el.dataset.pollStatus, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data.processing === 0) { window.location.reload(); return; }
        } catch { /* coba lagi */ }
        if (tries < 200) setTimeout(tick, tries < 10 ? 2500 : 6000);
    };
    setTimeout(tick, 2500);
}

/** Host: tunggu ZIP dari queue selesai. */
function pollZip() {
    document.querySelectorAll('[data-zip-poll]').forEach((el) => {
        const tick = async () => {
            try {
                const res = await fetch(el.dataset.zipPoll, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.status === 'ready' || data.status === 'failed') { window.location.reload(); return; }
            } catch { /* coba lagi */ }
            setTimeout(tick, 4000);
        };
        setTimeout(tick, 3000);
    });
}

/** Pilih banyak foto untuk dihapus sekaligus. */
function bulkSelect() {
    document.querySelectorAll('form[data-bulk-form]').forEach((form) => {
        const boxes = () => [...form.querySelectorAll('input[name="ids[]"]')];
        const counter = form.querySelector('[data-bulk-count]');
        const submit = form.querySelector('[data-bulk-submit]');
        const toggleAll = form.querySelector('[data-bulk-toggle-all]');

        const update = () => {
            const n = boxes().filter((b) => b.checked).length;
            if (counter) counter.textContent = String(n);
            if (submit) submit.disabled = n === 0;
            boxes().forEach((b) => b.closest('[data-bulk-item]')?.classList.toggle('ring-4', b.checked));
        };

        form.addEventListener('change', (e) => e.target.matches('input[name="ids[]"]') && update());
        toggleAll?.addEventListener('click', () => {
            const all = boxes();
            const check = all.some((b) => !b.checked);
            all.forEach((b) => (b.checked = check));
            update();
        });
        update();
    });
}

export function initUi() {
    confirmations();
    autosubmit();
    copyButtons();
    printButtons();
    reveal();
    navbar();
    heroSlider();
    infiniteScroll();
    pollPhotoStatus();
    pollZip();
    bulkSelect();
}
