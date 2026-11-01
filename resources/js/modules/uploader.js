import { sendForm, errorMessages } from './http';

const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'];

function isImage(file) {
    const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
    return (file.type.startsWith('image/') && file.type !== 'image/svg+xml') || ['heic', 'heif'].includes(ext);
}

function hasAllowedExt(file) {
    return ALLOWED_EXT.includes(file.name.split('.').pop()?.toLowerCase() ?? '');
}

function formatSize(bytes) {
    if (bytes > 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
}

function showErrors(box, messages) {
    box.replaceChildren();
    if (!messages.length) {
        box.hidden = true;
        return;
    }
    const ul = document.createElement('ul');
    ul.className = 'list-disc space-y-1 pl-5';
    messages.forEach((m) => {
        const li = document.createElement('li');
        li.textContent = m;
        ul.append(li);
    });
    box.append(ul);
    box.hidden = false;
}

function setProgress(wrap, pct, label) {
    if (!wrap) return;
    wrap.hidden = false;
    wrap.querySelector('[data-bar]').style.width = pct + '%';
    wrap.querySelector('[data-pct]').textContent = label ?? pct + '%';
}

/**
 * Form multiple upload di halaman "Foto Saya":
 * preview sebelum upload, caption per foto, batas sisa kuota, progress bar.
 */
export function initUploader() {
    const form = document.querySelector('form[data-uploader]');
    if (!form) return;

    const input = form.querySelector('[data-uploader-input]');
    const previews = form.querySelector('[data-uploader-previews]');
    const errorBox = form.querySelector('[data-uploader-error]');
    const progress = form.querySelector('[data-uploader-progress]');
    const submit = form.querySelector('[data-uploader-submit]');
    const dropzone = form.querySelector('[data-uploader-dropzone]');
    const remaining = parseInt(form.dataset.remaining || '0', 10);
    const captionMax = parseInt(form.dataset.captionMax || '500', 10);

    /** @type {{file: File, url: string|null, caption: string}[]} */
    let selected = [];

    const render = () => {
        previews.replaceChildren();
        selected.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'card overflow-hidden';

            const media = document.createElement('div');
            media.className = 'relative aspect-[4/3] bg-sand flex items-center justify-center';
            if (item.url) {
                const img = document.createElement('img');
                img.src = item.url;
                img.alt = 'Pratinjau foto';
                img.className = 'absolute inset-0 h-full w-full object-cover';
                // Pratinjau gagal (mis. HEIC di browser yang tidak mendukung): tampilkan nama file.
                img.onerror = () => img.remove();
                media.append(img);
            }
            const name = document.createElement('span');
            name.className = 'px-3 text-center text-xs text-muted';
            name.textContent = item.file.name + ' · ' + formatSize(item.file.size);
            media.prepend(name);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'absolute right-2 top-2 z-10 rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-red-700 shadow';
            remove.textContent = 'Batal';
            remove.addEventListener('click', () => {
                if (item.url) URL.revokeObjectURL(item.url);
                selected.splice(index, 1);
                render();
            });
            media.append(remove);

            const body = document.createElement('div');
            body.className = 'p-3';
            const label = document.createElement('label');
            label.className = 'field-label text-xs';
            label.textContent = 'Caption / ucapan (opsional)';
            const textarea = document.createElement('textarea');
            textarea.className = 'field text-sm';
            textarea.rows = 2;
            textarea.maxLength = captionMax;
            textarea.placeholder = 'Tulis pesan singkat untuk foto ini…';
            textarea.value = item.caption;
            textarea.addEventListener('input', () => (item.caption = textarea.value));
            body.append(label, textarea);

            card.append(media, body);
            previews.append(card);
        });

        submit.disabled = selected.length === 0;
        submit.querySelector('[data-count]')?.replaceChildren(String(selected.length));
    };

    const addFiles = (files) => {
        const errors = [];
        for (const file of files) {
            if (!isImage(file) || !hasAllowedExt(file)) {
                errors.push(`"${file.name}" bukan file gambar yang didukung.`);
                continue;
            }
            if (selected.length >= remaining) {
                errors.push(`Sisa kuota Anda hanya ${remaining} foto. File lain diabaikan.`);
                break;
            }
            selected.push({ file, url: URL.createObjectURL(file), caption: '' });
        }
        showErrors(errorBox, [...new Set(errors)]);
        render();
    };

    input.addEventListener('change', () => {
        addFiles(Array.from(input.files));
        input.value = '';
    });

    if (dropzone) {
        ['dragenter', 'dragover'].forEach((t) =>
            dropzone.addEventListener(t, (e) => {
                e.preventDefault();
                dropzone.classList.add('ring-2', 'ring-gold');
            }),
        );
        ['dragleave', 'drop'].forEach((t) =>
            dropzone.addEventListener(t, (e) => {
                e.preventDefault();
                dropzone.classList.remove('ring-2', 'ring-gold');
            }),
        );
        dropzone.addEventListener('drop', (e) => addFiles(Array.from(e.dataTransfer?.files ?? [])));
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!selected.length) return;

        const data = new FormData();
        data.append('_token', form.querySelector('input[name="_token"]').value);
        selected.forEach((item, i) => {
            data.append(`photos[${i}]`, item.file, item.file.name);
            data.append(`captions[${i}]`, item.caption);
        });

        submit.disabled = true;
        input.disabled = true;
        showErrors(errorBox, []);
        setProgress(progress, 0);

        const { status, data: res } = await sendForm(form.action, data, {
            onProgress: (pct) => setProgress(progress, pct, pct < 100 ? `Mengupload… ${pct}%` : 'Memproses foto…'),
        });

        if (status >= 200 && status < 300) {
            setProgress(progress, 100, 'Berhasil! Memuat ulang…');
            window.location.href = res.redirect || window.location.href;
            return;
        }

        progress.hidden = true;
        submit.disabled = false;
        input.disabled = false;
        showErrors(errorBox, errorMessages(status, res));
    });

    render();
}

/** Form "Ganti foto": pilih file -> langsung upload dengan progress. */
export function initReplaceForms() {
    document.querySelectorAll('form[data-replace-form]').forEach((form) => {
        const input = form.querySelector('input[type="file"]');
        const progress = form.querySelector('[data-uploader-progress]');
        const errorBox = form.querySelector('[data-uploader-error]');

        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file) return;
            if (!isImage(file) || !hasAllowedExt(file)) {
                showErrors(errorBox, [`"${file.name}" bukan file gambar yang didukung.`]);
                input.value = '';
                return;
            }

            const data = new FormData(form);
            input.disabled = true;
            showErrors(errorBox, []);
            setProgress(progress, 0);

            const { status, data: res } = await sendForm(form.action, data, {
                onProgress: (pct) => setProgress(progress, pct, pct < 100 ? `Mengupload… ${pct}%` : 'Memproses…'),
            });

            if (status >= 200 && status < 300) {
                window.location.href = res.redirect || window.location.href;
                return;
            }

            progress.hidden = true;
            input.disabled = false;
            input.value = '';
            showErrors(errorBox, errorMessages(status, res));
        });
    });
}
