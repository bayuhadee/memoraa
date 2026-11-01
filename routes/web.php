<?php

use App\Http\Controllers\Guest;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Host;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Owner;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');

/*
|--------------------------------------------------------------------------
| Tamu: /e/{event_code}
|--------------------------------------------------------------------------
*/
Route::prefix('e/{event:event_code}')
    ->middleware('guest.device')
    ->name('guest.')
    ->group(function () {
        Route::get('/', [Guest\GalleryController::class, 'show'])->name('gallery');
        Route::get('buku-tamu', [Guest\GuestbookController::class, 'edit'])->name('guestbook');
        Route::get('foto-saya', [Guest\MyPhotosController::class, 'index'])->name('my-photos');
        Route::get('foto-saya/status', [Guest\MyPhotosController::class, 'status'])->name('my-photos.status');

        // Semua aksi tulis hanya sampai 23:59:59 di tanggal acara.
        Route::middleware('upload.open')->group(function () {
            Route::middleware('throttle:guestbook')->group(function () {
                Route::post('buku-tamu', [Guest\GuestbookController::class, 'store'])->name('guestbook.store');
                Route::put('buku-tamu', [Guest\GuestbookController::class, 'update'])->name('guestbook.update');
            });

            // scopeBindings: {photo} hanya dicari di dalam event ini (isolasi antar event).
            Route::middleware('throttle:photo-upload')->scopeBindings()->group(function () {
                Route::post('foto', [Guest\PhotoController::class, 'store'])->name('photos.store');
                Route::post('foto/{photo}/ganti', [Guest\PhotoController::class, 'replace'])->name('photos.replace');
                Route::put('foto/{photo}', [Guest\PhotoController::class, 'update'])->name('photos.update');
                Route::delete('foto/{photo}', [Guest\PhotoController::class, 'destroy'])->name('photos.destroy');
            });
        });
    });

/*
|--------------------------------------------------------------------------
| Media dari storage private
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:media')->group(function () {
    Route::get('media/{photo}/download', [MediaController::class, 'download'])->name('media.download');
    Route::get('media/{photo}/{size}', [MediaController::class, 'show'])
        ->whereIn('size', ['thumb', 'full'])
        ->name('media.show');
});

/*
|--------------------------------------------------------------------------
| Host: login dengan ID event + password event
|--------------------------------------------------------------------------
*/
Route::prefix('host')->name('host.')->group(function () {
    Route::middleware('guest:host')->group(function () {
        Route::get('login', [Host\AuthController::class, 'create'])->name('login');
        Route::post('login', [Host\AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
    });

    Route::middleware(['auth:host', 'host.session'])->group(function () {
        Route::get('/', [Host\DashboardController::class, 'index'])->name('dashboard');
        Route::get('qrcode', [Host\DashboardController::class, 'qrcode'])->name('qrcode');

        Route::get('foto', [Host\PhotoController::class, 'index'])->name('photos.index');
        Route::delete('foto', [Host\PhotoController::class, 'bulkDestroy'])->name('photos.bulk-destroy');
        Route::delete('foto/{photo}', [Host\PhotoController::class, 'destroy'])->name('photos.destroy');

        Route::get('buku-tamu', [Host\GuestbookController::class, 'index'])->name('guestbook.index');
        Route::delete('buku-tamu/{guest}', [Host\GuestbookController::class, 'destroy'])->name('guestbook.destroy');

        Route::get('download-zip', [Host\ZipController::class, 'index'])->name('zip.index');
        Route::post('download-zip', [Host\ZipController::class, 'store'])->middleware('throttle:zip')->name('zip.store');
        Route::get('download-zip/{export}/status', [Host\ZipController::class, 'status'])->name('zip.status');
        Route::get('download-zip/{export}', [Host\ZipController::class, 'download'])->name('zip.download');

        Route::post('logout', [Host\AuthController::class, 'destroy'])->name('logout');
    });
});

/*
|--------------------------------------------------------------------------
| Owner: panel admin seluruh event
|--------------------------------------------------------------------------
*/
Route::prefix('owner')->name('owner.')->group(function () {
    Route::middleware('guest:owner')->group(function () {
        Route::get('login', [Owner\AuthController::class, 'create'])->name('login');
        Route::post('login', [Owner\AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
    });

    Route::middleware('auth:owner')->group(function () {
        Route::get('/', Owner\DashboardController::class)->name('dashboard');

        Route::resource('events', Owner\EventController::class);
        Route::patch('events/{event}/payment-status', [Owner\EventController::class, 'updatePaymentStatus'])->name('events.payment-status');
        Route::post('events/{event}/regenerate-password', [Owner\EventController::class, 'regeneratePassword'])->name('events.regenerate-password');
        Route::get('events/{event}/qrcode', [Owner\EventController::class, 'qrcode'])->name('events.qrcode');

        Route::delete('events/{event}/photos', [Owner\EventPhotoController::class, 'bulkDestroy'])->name('events.photos.bulk-destroy');
        Route::delete('events/{event}/photos/{photo}', [Owner\EventPhotoController::class, 'destroy'])->name('events.photos.destroy');
        Route::post('events/{event}/reset-photos', [Owner\EventPhotoController::class, 'reset'])->name('events.reset-photos');

        Route::get('reports', [Owner\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [Owner\ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/print', [Owner\ReportController::class, 'print'])->name('reports.print');

        Route::post('logout', [Owner\AuthController::class, 'destroy'])->name('logout');
    });
});
