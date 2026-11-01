<?php

return [

    /*
    | Zona waktu acuan untuk batas upload (sampai 23:59:59 pada tanggal acara).
    */
    'timezone' => env('GALERI_TIMEZONE', env('APP_TIMEZONE', 'Asia/Makassar')),

    /*
    | Akun owner default (dipakai oleh OwnerSeeder).
    */
    'owner' => [
        'name' => env('OWNER_NAME', 'Owner'),
        'email' => env('OWNER_EMAIL'),
        'password' => env('OWNER_PASSWORD'),
    ],

    /*
    | Nomor WhatsApp admin (format internasional tanpa +) dan pesan pembuka.
    */
    'whatsapp' => [
        'number' => env('ADMIN_WHATSAPP', '6281995034478'),
        'display' => env('ADMIN_WHATSAPP_DISPLAY', '0819-9503-4478'),
        'message' => 'Halo Admin, saya ingin mendaftar event galeri foto.',
    ],

    /*
    | Identitas perangkat tamu (cookie terenkripsi, HttpOnly).
    */
    'device_cookie' => [
        'name' => 'gt_device',
        'minutes' => 60 * 24 * 365,
    ],

    /*
    | Aturan foto.
    */
    'photos' => [
        'max_per_guest' => 2,
        'max_megapixels' => 50,
        'min_dimension' => 200,
        'landscape' => [1200, 800],
        'portrait' => [800, 1200],
        'thumb_width' => 400,
        'quality' => 85,
        'per_page' => 24,
        'caption_max' => 500,
    ],

    /*
    | ZIP: di bawah batas ini dibuat langsung (sinkron), di atasnya via queue.
    */
    'zip' => [
        'sync_max_photos' => (int) env('ZIP_SYNC_MAX_PHOTOS', 80),
        'keep_hours' => 24,
    ],

    /*
    | Driver Intervention Image: auto (imagick jika ada, lalu gd), gd, imagick.
    */
    'image_driver' => env('IMAGE_DRIVER', 'auto'),

    /*
    | Gambar placeholder untuk home page (ganti dengan foto sendiri di public/images).
    */
    'placeholder_img_hosts' => ['https://picsum.photos', 'https://fastly.picsum.photos'],
];
