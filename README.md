# Memoraa — Digital Guestbook (Galeri Foto & Buku Tamu)

Aplikasi Laravel untuk acara (pernikahan, ulang tahun, gathering). Setiap event punya 1 link unik
`https://domain.com/e/{event_code}`. Tamu mengisi buku tamu dan meng-upload hingga 2 foto tanpa login,
host mengelola & mengunduh foto, owner mengelola seluruh event.

| Peran | URL | Login |
|---|---|---|
| Publik | `/` | — |
| Tamu | `/e/{event_code}` | Tidak (cookie perangkat terenkripsi, 1 tahun) |
| Host | `/host` | ID event + password event (4 karakter) |
| Owner | `/owner` | Email + password (1 akun dari `.env`) |

> **Catatan versi:** dibangun di **Laravel 12** (struktur & API sama dengan Laravel 11).
> Laravel 11 sudah tidak menerima patch keamanan sehingga Composer menolak memasangnya.

---

## 1. Kebutuhan server

- PHP **8.2+** dengan ekstensi: `pdo_mysql`, `gd` (atau `imagick`), `fileinfo`, `exif`, `zip`, `mbstring`, `intl`, `openssl`
- MySQL 8 / MariaDB 10.6+
- Composer 2, Node.js 20+ (hanya untuk build aset)
- Nginx atau Apache
- **HEIC/HEIF** (foto iPhone) hanya diterima jika ekstensi `imagick` terpasang dengan dukungan `libheif`.
  Tanpa itu, aplikasi otomatis hanya menerima JPG/PNG/WEBP/GIF. (iPhone umumnya mengonversi ke JPG saat upload via browser.)

## 2. Instalasi

```bash
git clone <repo> galeri-tamu && cd galeri-tamu
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Edit .env: APP_URL, DB_*, OWNER_EMAIL, OWNER_PASSWORD (min. 10 karakter)
php artisan migrate --seed          # membuat tabel + 1 akun owner

npm ci && npm run build             # build Tailwind + Alpine + PhotoSwipe

php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`php artisan storage:link` **tidak diperlukan**: semua foto disimpan privat di `storage/app/private`
dan hanya disajikan lewat route `/media/...`.

Izin folder (sesuaikan user web server):

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Pengembangan lokal

```bash
cp .env.example .env   # lalu set APP_ENV=local, APP_DEBUG=true, SESSION_SECURE_COOKIE=false
php artisan migrate --seed   # di env local juga membuat event demo DEMO-2026 (password tampil di terminal)
composer run dev             # server + queue + vite sekaligus
```

## 3. Queue & scheduler (wajib)

Resize foto dan ZIP besar diproses di queue (driver `database`).

**Supervisor** — `/etc/supervisor/conf.d/galeri-worker.conf`:

```ini
[program:galeri-worker]
command=php /var/www/galeri-tamu/artisan queue:work --sleep=2 --tries=2 --timeout=1800 --memory=1024
user=www-data
numprocs=2
autostart=true
autorestart=true
stopwaitsecs=1810
redirect_stderr=true
stdout_logfile=/var/www/galeri-tamu/storage/logs/worker.log
```

**Cron** (scheduler — menjalankan `photos:cleanup` tiap hari pukul 03:00):

```cron
* * * * * cd /var/www/galeri-tamu && php artisan schedule:run >> /dev/null 2>&1
```

Setelah deploy ulang: `php artisan queue:restart`.

## 4. Konfigurasi `.env` penting

| Variabel | Keterangan |
|---|---|
| `APP_URL` | URL publik (dipakai di link event & QR code) |
| `APP_TIMEZONE` / `GALERI_TIMEZONE` | Default `Asia/Makassar`. Batas upload = 23:59:59 di tanggal acara pada zona ini |
| `OWNER_EMAIL`, `OWNER_PASSWORD` | Akun owner (dibuat/diperbarui oleh `php artisan db:seed --class=OwnerSeeder`) |
| `ADMIN_WHATSAPP`, `ADMIN_WHATSAPP_DISPLAY` | Nomor WA admin di home page |
| `IMAGE_DRIVER` | `auto` / `gd` / `imagick` |
| `ZIP_SYNC_MAX_PHOTOS` | Di bawah angka ini ZIP langsung diunduh; di atasnya dibuat via queue |
| `SESSION_SECURE_COOKIE=true` | Wajib di HTTPS (cookie sesi & cookie perangkat jadi `Secure`) |
| `SESSION_ENCRYPT=true` | Isi sesi dienkripsi |
| `TRUSTED_PROXIES` | Isi jika di belakang Cloudflare / load balancer (agar IP untuk rate limit benar) |

## 5. Upload file besar (tanpa batas dari aplikasi)

Aplikasi **tidak membatasi ukuran file**, tapi PHP & web server tetap punya batas fisik. Naikkan:

**php.ini** (FPM dan CLI untuk worker):

```ini
upload_max_filesize = 200M
post_max_size       = 210M     ; > upload_max_filesize × jumlah file per request
memory_limit        = 1024M    ; decode gambar hingga 50 MP butuh ±200–600 MB
max_execution_time  = 300
max_input_time      = 300
max_file_uploads    = 10
```

Jika `post_max_size` terlampaui, tamu melihat pesan "Ukuran file melebihi batas server" (Bahasa Indonesia).
Batas resolusi tetap ada: **maks. 50 megapiksel** (`config/galeri.php`) untuk mencegah *decompression bomb*.

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name domain.com;
    root /var/www/galeri-tamu/public;
    index index.php;

    client_max_body_size 210M;
    client_body_timeout 300s;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Hanya index.php yang boleh dieksekusi.
    location = /index.php {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    location ~ \.php$ { return 404; }

    # Jangan pernah sajikan file tersembunyi / storage secara langsung.
    location ~ /\.(?!well-known) { deny all; }
    location ^~ /storage/ { deny all; }

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Karena `root` mengarah ke `public/`, folder `storage/` memang berada di luar web root dan tidak bisa
diakses maupun dieksekusi. Aturan di atas adalah lapisan tambahan.

### Apache

`public/.htaccess` bawaan Laravel sudah dipakai. Tambahkan di VirtualHost:

```apache
<VirtualHost *:443>
    DocumentRoot /var/www/galeri-tamu/public
    LimitRequestBody 220200960          # 210 MB
    TimeOut 300

    <Directory /var/www/galeri-tamu/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Lapisan tambahan: storage tidak boleh diakses / mengeksekusi PHP
    <Directory /var/www/galeri-tamu/storage>
        Require all denied
        php_admin_flag engine off
        <FilesMatch "\.(php|phtml|phar)$">
            Require all denied
        </FilesMatch>
    </Directory>
</VirtualHost>
```

## 6. Keamanan yang diterapkan

1. Validasi Laravel: `file`, `image`, `mimes`, `mimetypes`, `dimensions:min 200×200` (tanpa aturan `max`).
2. MIME asli via `finfo` + cek **magic bytes** ([ImageInspector](app/Services/ImageInspector.php)).
3. `getimagesize()` wajib berhasil dan MIME-nya cocok.
4. Batas **50 megapiksel**.
5. **Re-encode total** ke JPEG (Intervention Image v3): EXIF/GPS hilang, payload polyglot hilang.
   File yang mengandung `<?php`, `<script`, `__halt_compiler` langsung ditolak; GIF animasi ditolak.
6. Nama file **UUID** acak, ekstensi `.jpg` ditentukan sistem.
7. Storage **privat**; disajikan oleh [MediaController](app/Http/Controllers/MediaController.php) dengan `Content-Type: image/jpeg`, `nosniff`, `Content-Disposition`.
8. SVG ditolak.
9. Contoh konfigurasi Nginx/Apache di atas (PHP tidak bisa dieksekusi dari storage).
10. Rate limit: buku tamu & upload per IP **dan** per perangkat; login host 5/menit/IP + 10 gagal/15 menit per ID event + kunci 1 jam setelah 20 gagal/IP; login owner 5/menit.
11. CSRF di semua form; semua teks di-escape Blade; CSP ketat tanpa inline script (Alpine versi CSP).
12. Kepemilikan via **Policy** ([PhotoPolicy](app/Policies/PhotoPolicy.php), [GuestPolicy](app/Policies/GuestPolicy.php)); URL foto memakai UUID; route tamu memakai `scopeBindings()` sehingga foto event lain = 404.
13. Honeypot pada form buku tamu.
14. Log audit (IP, user agent, waktu) di `storage/logs/audit-YYYY-MM-DD.log`.

Tambahan: password event disimpan **terenkripsi** (untuk ditampilkan ke owner) dan **bcrypt** (untuk login);
generate ulang password otomatis mengeluarkan host yang sedang login. Security headers (CSP, X-Frame-Options,
Referrer-Policy, Permissions-Policy, HSTS di HTTPS) via [SecurityHeaders](app/Http/Middleware/SecurityHeaders.php).

## 7. Perintah artisan

| Perintah | Fungsi |
|---|---|
| `php artisan photos:cleanup` | Hapus file temporary yatim, ZIP > 24 jam, tandai foto macet (terjadwal harian) |
| `php artisan db:seed --class=OwnerSeeder` | Buat / perbarui akun owner dari `.env` |
| `php artisan test` | Jalankan feature test |

## 8. Testing

```bash
php artisan test
```

Mencakup: batas 2 foto (termasuk upload bertahap), 1× buku tamu per perangkat, penolakan file berbahaya
(teks, PHP, SVG, polyglot, GIF animasi, terlalu kecil, terlalu banyak piksel), resize 1200×800 / 800×1200,
EXIF terhapus, penutupan upload setelah tanggal acara (termasuk batas zona waktu), isolasi data antar event,
login host + rate limit, ZIP, owner CRUD, reset foto, laporan & export CSV.

## 9. Struktur folder utama

```
app/
  Console/Commands/CleanupPhotos.php      photos:cleanup
  Enums/PaymentStatus.php
  Http/
    Controllers/{Guest,Host,Owner}/...    controller tipis
    Controllers/MediaController.php       sajikan foto privat
    Middleware/                           IdentifyGuestDevice, EnsureUploadWindowOpen,
                                          EnsureHostSessionValid, SecurityHeaders
    Requests/{Guest,Host,Owner}/...       semua validasi
  Jobs/ProcessPhoto.php, BuildEventZip.php
  Models/ Owner, Event (juga user guard host), Guest, Photo, ZipExport
  Policies/ PhotoPolicy, GuestPolicy
  Rules/SafeImage.php
  Services/ ImageInspector, ImageProcessor, PhotoUploadService, PhotoStorage,
            GuestbookService, EventService, HostAuthService, ZipService,
            QrCodeService, ReportService
  Support/ GuestContext, PhotoRules, Audit
config/galeri.php                         semua pengaturan aplikasi
resources/views/ home, guest/, host/, owner/, layouts/, partials/, errors/
resources/js/ app.js + modules/ (lightbox, uploader, ui, http)
tests/Feature/                            feature test
```

## 10. Mengganti gambar placeholder

Home page memakai gambar contoh dari picsum.photos. Simpan foto Anda di `public/images/`, lalu ubah fungsi
`$img` di bagian atas [resources/views/home.blade.php](resources/views/home.blade.php). Setelah itu host
`picsum.photos` bisa dihapus dari `placeholder_img_hosts` di `config/galeri.php` (CSP).
