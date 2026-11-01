/** Helper XHR dengan progress upload & CSRF. */
export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function sendForm(url, formData, { onProgress } = {}) {
    return new Promise((resolve) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && onProgress) onProgress(Math.round((e.loaded / e.total) * 100));
        });

        xhr.onload = () => {
            let data = {};
            try {
                data = JSON.parse(xhr.responseText);
            } catch {
                /* bukan JSON */
            }
            resolve({ status: xhr.status, data });
        };
        xhr.onerror = () => resolve({ status: 0, data: {} });
        xhr.send(formData);
    });
}

/** Ubah respons gagal menjadi pesan Bahasa Indonesia. */
export function errorMessages(status, data) {
    if (status === 0) return ['Koneksi terputus. Periksa internet Anda lalu coba lagi.'];
    if (status === 413) return [data.message || 'Ukuran file melebihi batas server.'];
    if (status === 419) return ['Sesi halaman kedaluwarsa. Muat ulang halaman lalu coba lagi.'];
    if (status === 429) return [data.message || 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'];
    if (data.errors) return [...new Set(Object.values(data.errors).flat())];
    return [data.message || 'Terjadi kesalahan. Silakan coba lagi.'];
}
